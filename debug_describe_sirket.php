<?php
require_once 'include/fonksiyon.php';

try {
    $db = new PDO("mysql:host=localhost;dbname=gemas_web_db;charset=utf8", "root", "");
    $stmt = $db->query("DESCRIBE sirket");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
