<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../include/vt.php';

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    echo "--- PRODUCT 02400 ---\n";
    $stmt = $pdo->query("SELECT * FROM kampanya_ozel_fiyatlar WHERE stok_kodu = '02400'");
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($prod);

    if ($prod) {
        $cat = $prod['kategori'];
        echo "\n--- RULE FOR CATEGORY: [{$cat}] ---\n";
        $stmt = $pdo->prepare("SELECT * FROM custom_campaigns WHERE category_name = ?");
        $stmt->execute([$cat]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        print_r($rule);
        
        echo "\n--- FUZZY RULE SEARCH ---\n";
        $stmt = $pdo->prepare("SELECT * FROM custom_campaigns WHERE category_name LIKE ?");
        $stmt->execute(['%' . $cat . '%']);
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($rules);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
