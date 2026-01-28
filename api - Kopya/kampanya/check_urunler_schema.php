<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../include/vt.php';

try {
    $db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    if (!$db) die("Connect failed: " . mysqli_connect_error());

    echo "--- SCHEMA: urunler ---\n";
    $res = mysqli_query($db, "DESCRIBE urunler");
    while($row = mysqli_fetch_assoc($res)) {
        echo "Field: {$row['Field']} | Type: {$row['Type']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
