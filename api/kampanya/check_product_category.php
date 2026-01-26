<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/../../include/vt.php';

$code = '02400';

try {
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    echo "--- PRODUCT '{$code}' ---\n";
    $stmt = $pdo->prepare("SELECT stok_kodu, kategori FROM kampanya_ozel_fiyatlar WHERE stok_kodu = ?");
    $stmt->execute([$code]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($prod) {
        echo "Category in DB: [{$prod['kategori']}]\n";
        echo "Hex: " . bin2hex($prod['kategori']) . "\n";
        echo "Normalized (Upper+Trim): [" . mb_strtoupper(trim($prod['kategori']), 'UTF-8') . "]\n";
    } else {
        echo "Product not found in kampanya_ozel_fiyatlar.\n";
    }

    echo "\n--- RULES MATCHING 'VANA' ---\n";
    $stmt = $pdo->query("SELECT * FROM custom_campaigns WHERE is_active = 1 AND category_name LIKE '%VANA%'");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($rules as $r) {
        echo "Rule: [{$r['category_name']}]\n";
        echo "Hex: " . bin2hex($r['category_name']) . "\n";
        echo "Normalized: [" . mb_strtoupper(trim($r['category_name']), 'UTF-8') . "]\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
