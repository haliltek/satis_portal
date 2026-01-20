<?php
// Config ve database bağlantısı
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/DatabaseManager.php';

use Proje\DatabaseManager;

$dbConfig = [
    'host' => $config['db']['host'],
    'port' => $config['db']['port'],
    'user' => $config['db']['user'],
    'pass' => $config['db']['pass'],
    'name' => $config['db']['name'],
];
$dbManager = new DatabaseManager($dbConfig);
$conn = $dbManager->getConnection();

echo "Columns in ogteklif2:\n";
$res = $conn->query("SHOW COLUMNS FROM ogteklif2");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
