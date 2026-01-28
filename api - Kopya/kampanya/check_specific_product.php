<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../include/vt.php';

$db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if (!$db) die("Connect failed: " . mysqli_connect_error());

$res = mysqli_query($db, "SELECT stokkodu, maliyet FROM urunler WHERE stokkodu = '0111STRN100M'");
$row = mysqli_fetch_assoc($res);
print_r($row);
