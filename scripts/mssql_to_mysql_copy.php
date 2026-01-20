<?php
/**
 * PHP CLI: Read-only MSSQL → MySQL copier for selected tables.
 *
 * Usage examples:
 *  php scripts/mssql_to_mysql_copy.php --config scripts/config.gemas_dys.local.json
 *  php scripts/mssql_to_mysql_copy.php --config scripts/config.gemas_dys.local.json --tables CariEtiketTanimi,StokOzellikleri --batch-size 2000 --overwrite 0
 *
 * Notes:
 * - Source is MSSQL via PDO sqlsrv; only SELECT is used (read-only behavior).
 * - Destination is MySQL via PDO mysql; tables created if missing using type mapping.
 * - Primary keys are detected from MSSQL INFORMATION_SCHEMA and applied on MySQL.
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

function abort(string $msg): void {
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

function info(string $msg): void {
    fwrite(STDOUT, $msg . "\n");
}

function loadConfig(string $path): array {
    if (!is_file($path)) {
        abort("Config not found: {$path}");
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        abort("Cannot read config: {$path}");
    }
    $cfg = json_decode($raw, true);
    if (!is_array($cfg)) {
        abort("Invalid JSON in config: {$path}");
    }
    $cfg['source'] = $cfg['source'] ?? [];
    $cfg['target'] = $cfg['target'] ?? [];
    $cfg['options'] = $cfg['options'] ?? [];
    $cfg['tables'] = $cfg['tables'] ?? [];
    $cfg['source']['schema'] = $cfg['source']['schema'] ?? 'dbo';
    $cfg['options']['batch_size'] = (int)($cfg['options']['batch_size'] ?? 1000);
    $cfg['options']['overwrite'] = (bool)($cfg['options']['overwrite'] ?? false);
    $cfg['options']['engine'] = $cfg['options']['engine'] ?? 'InnoDB';
    $cfg['options']['charset'] = $cfg['options']['charset'] ?? 'utf8mb4';
    $cfg['options']['collation'] = $cfg['options']['collation'] ?? null;
    return $cfg;
}

function parseArgs(): array {
    $longopts = [
        'config:',
        'tables::',
        'batch-size::',
        'overwrite::',
    ];
    $args = getopt('', $longopts);
    if (!isset($args['config'])) {
        abort('Usage: --config <path> [--tables <comma,list>] [--batch-size <n>] [--overwrite 0|1]');
    }
    return $args;
}

function connectMssql(array $source): PDO {
    $host = $source['server'] ?? null;
    $port = $source['port'] ?? null;
    $db   = $source['database'] ?? null;
    $user = $source['user'] ?? null;
    $pass = $source['password'] ?? null;
    if (!$host || !$db || !$user || $pass === null) {
        abort('source.server, source.database, source.user, source.password are required');
    }
    // Accept both "host,port" or host + port fields
    if ($port && strpos($host, ',') === false) {
        $host = $host . ',' . $port;
    }
    $drivers = PDO::getAvailableDrivers();
    if (!in_array('sqlsrv', $drivers, true)) {
        $driversStr = $drivers ? implode(', ', $drivers) : '(none)';
        abort(
            "PDO SQLSRV driver not available. PDO drivers: {$driversStr}.\n" .
            "Enable the pdo_sqlsrv extension in the php.ini used by this CLI.\n" .
            "Tip: run 'php --ini' to see the loaded php.ini; 'php -m' should list pdo_sqlsrv and sqlsrv."
        );
    }
    $dsn = "sqlsrv:Server={$host};Database={$db}";
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $opts[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    $pdo = new PDO($dsn, $user, $pass, $opts);
    return $pdo;
}

function connectMysql(array $target): PDO {
    $host = $target['host'] ?? 'localhost';
    $port = (int)($target['port'] ?? 3306);
    $db   = $target['database'] ?? null;
    $user = $target['user'] ?? null;
    $pass = $target['password'] ?? null;
    $charset = $target['charset'] ?? 'utf8mb4';
    if (!$db || !$user || $pass === null) {
        abort('target.database, target.user, target.password are required');
    }
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
    ];
    return new PDO($dsn, $user, $pass, $opts);
}

function qi(string $name): string { return '`' . str_replace('`', '``', $name) . '`'; }

function fetchColumnsMssql(PDO $mssql, string $db, string $schema, string $table): array {
    $sql = "
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH,
               NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_CATALOG = :db AND TABLE_SCHEMA = :sch AND TABLE_NAME = :tbl
        ORDER BY ORDINAL_POSITION
    ";
    $st = $mssql->prepare($sql);
    $st->execute([':db'=>$db, ':sch'=>$schema, ':tbl'=>$table]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        abort("No columns found for {$schema}.{$table} in {$db}");
    }
    return $rows;
}

function fetchPkMssql(PDO $mssql, string $db, string $schema, string $table): array {
    $sql = "
        SELECT kcu.COLUMN_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
          ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
         AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
         AND tc.TABLE_NAME = kcu.TABLE_NAME
        WHERE tc.TABLE_CATALOG = :db AND tc.TABLE_SCHEMA = :sch
          AND tc.TABLE_NAME = :tbl AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
        ORDER BY kcu.ORDINAL_POSITION
    ";
    $st = $mssql->prepare($sql);
    $st->execute([':db'=>$db, ':sch'=>$schema, ':tbl'=>$table]);
    return array_map(fn($r) => $r['COLUMN_NAME'], $st->fetchAll(PDO::FETCH_ASSOC));
}

function mapType(array $col): string {
    $dt = strtolower($col['DATA_TYPE']);
    $len = $col['CHARACTER_MAXIMUM_LENGTH'];
    $prec = $col['NUMERIC_PRECISION'];
    $scale = $col['NUMERIC_SCALE'];

    if ($dt === 'int') return 'INT';
    if ($dt === 'bigint') return 'BIGINT';
    if ($dt === 'smallint') return 'SMALLINT';
    if ($dt === 'tinyint') return 'TINYINT';
    if ($dt === 'bit') return 'TINYINT(1)';
    if ($dt === 'real') return 'FLOAT';
    if ($dt === 'float') return 'DOUBLE';
    if (in_array($dt, ['decimal','numeric','money','smallmoney'], true)) {
        $p = $prec !== null ? (int)$prec : 19;
        $s = $scale !== null ? (int)$scale : 4;
        return "DECIMAL({$p},{$s})";
    }
    if ($dt === 'char' || $dt === 'nchar') {
        if ((int)$len === -1) return 'LONGTEXT';
        return 'CHAR(' . (int)$len . ')';
    }
    if ($dt === 'varchar' || $dt === 'nvarchar') {
        if ((int)$len === -1) return 'LONGTEXT';
        return 'VARCHAR(' . (int)$len . ')';
    }
    if ($dt === 'text' || $dt === 'ntext') return 'LONGTEXT';
    if ($dt === 'varbinary') {
        if ((int)$len === -1) return 'LONGBLOB';
        return 'VARBINARY(' . (int)$len . ')';
    }
    if ($dt === 'binary') return 'BINARY(' . ((int)$len > 0 ? (int)$len : 1) . ')';
    if ($dt === 'image') return 'LONGBLOB';
    if ($dt === 'date') return 'DATE';
    if ($dt === 'smalldatetime' || $dt === 'datetime') return 'DATETIME';
    if ($dt === 'datetime2') return 'DATETIME(6)';
    if ($dt === 'time') return 'TIME';
    if ($dt === 'uniqueidentifier') return 'CHAR(36)';
    if ($dt === 'xml') return 'LONGTEXT';
    return 'LONGTEXT';
}

function mysqlTableExists(PDO $mysql, string $table): bool {
    // MySQL native prepared statements cannot prepare SHOW statements; build SQL safely
    $sql = 'SHOW TABLES LIKE ' . $mysql->quote($table);
    $st = $mysql->query($sql);
    return $st && $st->fetchColumn() !== false;
}

function buildCreateTable(string $table, array $columns, array $pkCols, array $opts): string {
    $defs = [];
    foreach ($columns as $col) {
        $name = $col['COLUMN_NAME'];
        $nullable = strtoupper((string)$col['IS_NULLABLE']) === 'YES';
        $type = mapType($col);
        $defs[] = qi($name) . ' ' . $type . ' ' . ($nullable ? 'NULL' : 'NOT NULL');
    }
    $pk = '';
    if ($pkCols) {
        $pk = ', PRIMARY KEY (' . implode(', ', array_map('qi', $pkCols)) . ')';
    }
    $engine = $opts['engine'] ?? 'InnoDB';
    $charset = $opts['charset'] ?? 'utf8mb4';
    $collation = $opts['collation'] ?? null;
    $collateSql = $collation ? ' COLLATE=' . $collation : '';
    $sql = 'CREATE TABLE ' . qi($table) . " (\n  " . implode(",\n  ", $defs) . $pk .
           "\n) ENGINE={$engine} DEFAULT CHARSET={$charset}{$collateSql};";
    return $sql;
}

function copyTable(PDO $mssql, PDO $mysql, string $db, string $schema, string $table, array $opts): void {
    info("\n=== {$schema}.{$table} ===");
    $columns = fetchColumnsMssql($mssql, $db, $schema, $table);
    $pkCols = fetchPkMssql($mssql, $db, $schema, $table);

    $exists = mysqlTableExists($mysql, $table);
    if (!$exists) {
        $ddl = buildCreateTable($table, $columns, $pkCols, $opts);
        info("Creating table {$table} in MySQL...");
        $mysql->exec($ddl);
    } else {
        if (!empty($opts['overwrite'])) {
            info("Overwriting table {$table} (drop + recreate)...");
            $mysql->exec('DROP TABLE ' . qi($table));
            $ddl = buildCreateTable($table, $columns, $pkCols, $opts);
            $mysql->exec($ddl);
        } else {
            info("Table {$table} already exists; appending rows.");
        }
    }

    $colNames = array_map(fn($c) => $c['COLUMN_NAME'], $columns);
    $select = 'SELECT ' . implode(', ', array_map(fn($n) => '['.$n.']', $colNames)) . ' FROM ['.$schema.'].['.$table.']';
    $placeholders = implode(', ', array_fill(0, count($colNames), '?'));
    $insert = 'INSERT INTO ' . qi($table) . ' (' . implode(', ', array_map('qi', $colNames)) . ") VALUES (" . $placeholders . ')';

    $batchSize = (int)($opts['batch_size'] ?? 1000);
    $insertStmt = $mysql->prepare($insert);

    // Begin copying
    $total = 0;
    $mssql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $mysql->beginTransaction();
    $selStmt = $mssql->prepare($select, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
    $selStmt->execute();

    $buffer = [];
    while ($row = $selStmt->fetch(PDO::FETCH_NUM)) {
        $buffer[] = $row;
        if (count($buffer) >= $batchSize) {
            foreach ($buffer as $r) { $insertStmt->execute($r); }
            $mysql->commit();
            $total += count($buffer);
            info("Inserted " . count($buffer) . " rows into {$table} (total {$total})");
            $mysql->beginTransaction();
            $buffer = [];
        }
    }
    if ($buffer) {
        foreach ($buffer as $r) { $insertStmt->execute($r); }
        $mysql->commit();
        $total += count($buffer);
        info("Inserted " . count($buffer) . " rows into {$table} (total {$total})");
    }
    info("Done {$table}: {$total} rows copied.");
}

// Main
$args = parseArgs();
$cfg = loadConfig($args['config']);
if (!empty($args['tables'])) {
    $cfg['tables'] = array_values(array_filter(array_map('trim', explode(',', $args['tables']))));
}
if (!empty($args['batch-size'])) { $cfg['options']['batch_size'] = (int)$args['batch-size']; }
if (isset($args['overwrite'])) { $cfg['options']['overwrite'] = (bool)((int)$args['overwrite']); }

$source = $cfg['source'];
$target = $cfg['target'];
$options = $cfg['options'];
$tables = $cfg['tables'];
if (!$tables) { abort('No tables specified (config.tables or --tables)'); }

info('Connecting MSSQL (read-only usage) ...');
$mssql = connectMssql($source);
info('Connecting MySQL (target) ...');
$mysql = connectMysql($target);

try {
    foreach ($tables as $t) {
        copyTable($mssql, $mysql, $source['database'], $source['schema'] ?? 'dbo', $t, $options);
    }
} finally {
    $mssql = null; // close
    $mysql = null;
}

info("All done.");
