<?php
/**
 * 5 Ek Kategori Ekleme Scripti
 */

require_once __DIR__ . "/fonk.php";

echo "<h1>5 Ek Kategori Ekleniyor...</h1>";
echo "<style>
    body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}
    .success{color:#4ec9b0;font-weight:bold;}
    .error{color:#f48771;font-weight:bold;}
    table{border-collapse:collapse;margin:20px 0;}
    th,td{border:1px solid #3e3e3e;padding:8px;}
    th{background:#007acc;color:white;}
</style>";

try {
    $sql = file_get_contents(__DIR__ . '/sql/add_5_more_categories.sql');
    
    if (!$sql) {
        throw new Exception("SQL dosyası bulunamadı!");
    }
    
    echo "<div class='success'>✓ SQL dosyası okundu</div>";
    
    // Multi-query çalıştır
    if ($db->multi_query($sql)) {
        $queryCount = 0;
        do {
            $queryCount++;
            if ($result = $db->store_result()) {
                $result->free();
            }
        } while ($db->next_result());
        
        echo "<div class='success'>✓ {$queryCount} sorgu çalıştırıldı</div>";
    } else {
        throw new Exception("Sorgu hatası: " . $db->error);
    }
    
    // Kontrol
    echo "<h2>Eklenen Kategoriler</h2>";
    $campaigns = $db->query("SELECT category_name, COUNT(*) as product_count FROM custom_campaigns c LEFT JOIN custom_campaign_products p ON c.id = p.campaign_id GROUP BY c.id ORDER BY c.priority DESC");
    
    echo "<table>";
    echo "<tr><th>Kategori</th><th>Ürün Sayısı</th></tr>";
    
    $totalProducts = 0;
    while ($row = $campaigns->fetch_assoc()) {
        $count = intval($row['product_count']);
        $totalProducts += $count;
        echo "<tr><td>{$row['category_name']}</td><td>{$count}</td></tr>";
    }
    
    echo "<tr style='background:#007acc;'><td><strong>TOPLAM</strong></td><td><strong>{$totalProducts}</strong></td></tr>";
    echo "</table>";
    
    echo "<h2 class='success'>✅ EKLEME TAMAMLANDI!</h2>";
    echo "<p>Toplam: 10 Kampanya, {$totalProducts} Ürün</p>";
    echo "<p><a href='test_campaigns.php' style='color:#569cd6;font-size:18px;'>→ Tüm Kampanyaları Test Et</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>HATA: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
