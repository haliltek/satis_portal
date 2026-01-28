<?php
require_once __DIR__ . '/../../include/vt.php';

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    $stmt = $pdo->query("SELECT * FROM custom_campaigns WHERE is_active = 1");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents('rules_dump.txt', print_r($rules, true));
    echo "Dumped " . count($rules) . " rules to api/kampanya/rules_dump.txt\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
