<?php
// scripts/sync_campaigns.php
// Fetch campaign data from Logo MSSQL and sync into MySQL

date_default_timezone_set('Europe/Istanbul');

use Proje\DatabaseManager;

require_once __DIR__ . '/../vendor/autoload.php';

function ensureSqlsrv()
{
    if (extension_loaded('pdo_sqlsrv')) {
        return;
    }
    if (function_exists('dl')) {
        $prefix = stripos(PHP_OS, 'WIN') === 0 ? 'php_pdo_sqlsrv' : 'pdo_sqlsrv';
        $suffix = stripos(PHP_OS, 'WIN') === 0 ? '.dll' : '.so';
        @dl($prefix . $suffix);
    }
    if (!extension_loaded('pdo_sqlsrv')) {
        fwrite(STDERR, "PDO SQLSRV extension required\n");
        exit(1);
    }
}

function sync_campaigns()
{
    ensureSqlsrv();
    $config = require __DIR__ . '/../config/config.php';
    $logo = $config['logo'];
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    $mssql = new PDO("sqlsrv:Server={$logo['host']};Database={$logo['db']}", $logo['user'], $logo['pass'], $options);
    $dbManager = new DatabaseManager($config['db']);

    // Example query - adjust according to actual LOGO schema
    $sql = "SELECT LOGICALREF, STOCKREF, CLREF, DISRATE, BEGDATE, ENDDATE, DEFINITION_ FROM LG_".$config['firmNr']."_CAMPAIGN";
    $rows = $mssql->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $id = (int)($r['LOGICALREF'] ?? 0);
        $data = [
            'product_id'   => $r['STOCKREF'] ?? null,
            'group_id'     => $r['CLREF'] ?? null,
            'discount_rate'=> (float)($r['DISRATE'] ?? 0),
            'start_date'   => $r['BEGDATE'] ? substr($r['BEGDATE'],0,10) : null,
            'end_date'     => $r['ENDDATE'] ? substr($r['ENDDATE'],0,10) : null,
            'description'  => $r['DEFINITION_'] ?? ''
        ];
        $conn = $dbManager->getConnection();
        $stmt = $conn->prepare('SELECT id FROM campaigns WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($exists) {
            $dbManager->updateCampaign($id, $data);
        } else {
            $data['id'] = $id;
            $dbManager->createCampaign($data);
        }
    }
    echo "Campaign sync completed\n";
}

if (PHP_SAPI === 'cli') {
    sync_campaigns();
}
