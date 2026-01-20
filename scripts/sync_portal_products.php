<?php
// scripts/sync_portal_products.php
// Synchronize local urunler with remote portal_urunler table

date_default_timezone_set('Europe/Istanbul');

function sync_portal_products()
{
    $config = require __DIR__ . '/../config/config.php';

    $mysqlHost = $config['db']['host'];
    $mysqlDb   = $config['db']['name'];
    $mysqlUser = $config['db']['user'];
    $mysqlPass = $config['db']['pass'];

    $portalHost = $_ENV['GEMAS_WEB_HOST'];
    $portalUser = $_ENV['GEMAS_WEB_USER'];
    $portalPass = $_ENV['GEMAS_WEB_PASS'];
    $portalDb   = $_ENV['GEMAS_WEB_DB'];
    $portalPort = $_ENV['GEMAS_WEB_PORT'] ?? '3306';

    try {
        $mysql = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8", $mysqlUser, $mysqlPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        fwrite(STDERR, "Local DB connection failed: " . $e->getMessage() . "\n");
        return;
    }

    try {
        $portal = new PDO(
            "mysql:host=$portalHost;port=$portalPort;dbname=$portalDb;charset=utf8",
            $portalUser,
            $portalPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        fwrite(STDERR, "Portal DB connection failed: " . $e->getMessage() . "\n");
        return;
    }

$localCols  = $mysql->query('SHOW COLUMNS FROM urunler')->fetchAll(PDO::FETCH_COLUMN);
$portalCols = $portal->query('SHOW COLUMNS FROM portal_urunler')->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('stokkodu', $localCols) || !in_array('stokkodu', $portalCols)) {
        fwrite(STDERR, "Both tables must contain a stokkodu column\n");
        return;
    }

$insertCols = array_filter($portalCols, fn($c) => $c !== 'urun_id');
$updateCols = array_filter($insertCols, fn($c) => !in_array($c, ['stokkodu', 'durum', 'last_updated']));

$placeholders = implode(',', array_fill(0, count($insertCols), '?'));
$insertSql = 'INSERT INTO portal_urunler (' . implode(',', $insertCols) . ') VALUES (' . $placeholders . ')';
$insPortal = $portal->prepare($insertSql);

$setList  = implode(',', array_map(fn($c) => "$c=?", $updateCols));
$updateSql = 'UPDATE portal_urunler SET ' . $setList . ' WHERE stokkodu=?';
$upPortal  = $portal->prepare($updateSql);

$checkSql = 'SELECT 1 FROM portal_urunler WHERE stokkodu=? LIMIT 1';
$checkStmt = $portal->prepare($checkSql);

$localRows = $mysql->query('SELECT * FROM urunler')->fetchAll();

$inserted = 0;
$updated  = 0;

    foreach ($localRows as $row) {
        $code = $row['stokkodu'];

        $checkStmt->execute([$code]);
        $exists = (bool)$checkStmt->fetchColumn();

        if ($exists) {
            $valuesUp = [];
            foreach ($updateCols as $col) {
                $valuesUp[] = $row[$col] ?? null;
            }
            $valuesUp[] = $code;
            $upPortal->execute($valuesUp);
            echo "updated $code\n";
            $updated++;
        } else {
            $valuesIns = [];
            foreach ($insertCols as $col) {
                if ($col === 'durum') {
                    $valuesIns[] = 0;
                } elseif ($col === 'last_updated') {
                    $valuesIns[] = null;
                } else {
                    $valuesIns[] = $row[$col] ?? null;
                }
            }
            $insPortal->execute($valuesIns);
            echo "inserted $code\n";
            $inserted++;
        }
        ob_flush();
        flush();
    }

    echo "Inserted: $inserted\n";
    echo "Updated: $updated\n";

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    @file_put_contents($logDir . '/portal_products_sync_time.txt', date('Y-m-d H:i:s'));
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    sync_portal_products();
}
