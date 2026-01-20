<?php
$config = require 'config/config.php';
$db_cfg = $config['db'];

mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($db_cfg['host'], $db_cfg['user'], $db_cfg['pass'], $db_cfg['name'], $db_cfg['port']);
if ($db->connect_errno) {
    die("Connect Error: " . $db->connect_error);
}

$result = $db->query("SHOW COLUMNS FROM ogteklif2 LIKE 'genel_iskonto'");
if ($result && $result->num_rows > 0) {
    echo "EXISTS";
} else {
    echo "MISSING";
}
$db->close();
?>
