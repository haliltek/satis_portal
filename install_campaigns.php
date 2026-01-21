<?php
/**
 * Kampanya Sistemi Final Kurulum
 * Tek SQL dosyası ile tüm kurulum
 */

require_once __DIR__ . "/fonk.php";

echo "<h1>Kampanya Sistemi Kurulum (v3 - Düzeltilmiş)</h1>";
echo "<style>
    body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}
    .success{color:#4ec9b0;font-weight:bold;}
    .error{color:#f48771;font-weight:bold;}
    .info{color:#dcdcaa;}
    table{border-collapse:collapse;margin:20px 0;}
    th,td{border:1px solid #3e3e3e;padding:8px;text-align:left;}
    th{background:#007acc;color:white;}
</style>";

try {
    echo "<h2>Kurulum Başlıyor...</h2>";
    
    // Tek SQL dosyasını oku
    $sql = file_get_contents(__DIR__ . '/sql/kampanya_full_install.sql');
    
    if (!$sql) {
        throw new Exception("SQL dosyası okunamadı!");
    }
    
    echo "<div class='info'>SQL dosyası okundu (" . number_format(strlen($sql)) . " byte)</div>";
    
    // Multi-query çalıştır
    if ($db->multi_query($sql)) {
        echo "<div class='success'>✓ SQL scriptleri çalıştırılıyor...</div>";
        
        // Tüm sonuçları işle
        $queryCount = 0;
        do {
            $queryCount++;
            if ($result = $db->store_result()) {
                $result->free();
            }
        } while ($db->next_result());
        
        echo "<div class='success'>✓ Tüm komutlar çalıştırıldı ({$queryCount} sorgu)</div>";
    } else {
        throw new Exception("Multi-query hatası: " . $db->error);
    }
    
    // Kontroller
    echo "<h2>Kurulum Doğrulaması</h2>";
    
    $checks = [
        'custom_campaigns' => ['expected' => 5, 'name' => 'Kampanyalar'],
        'custom_campaign_products' => ['expected' => 78, 'name' => 'Kampanya Ürünleri'],
        'custom_campaign_rules' => ['expected' => 10, 'name' => 'İskonto Kuralları'],
        'custom_campaign_customers' => ['expected' => 1, 'name' => 'Ana Bayiler']
    ];
    
    echo "<table>";
    echo "<tr><th>Tablo</th><th>Kayıt Sayısı</th><th>Beklenen</th><th>Durum</th></tr>";
    
    $allGood = true;
    foreach ($checks as $table => $info) {
        $result = $db->query("SELECT COUNT(*) as cnt FROM `{$table}`");
        $count = $result ? $result->fetch_assoc()['cnt'] : 0;
        
        $status = ($count >= $info['expected']) ? 
            "<span class='success'>✓ TAMAM</span>" : 
            "<span class='error'>✗ HATA</span>";
        
        if ($count < $info['expected']) $allGood = false;
        
        echo "<tr>";
        echo "<td>{$info['name']}</td>";
        echo "<td>{$count}</td>";
        echo "<td>{$info['expected']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($allGood) {
        echo "<h2 class='success'>🎉 KURULUM BAŞARILI!</h2>";
        echo "<p><a href='test_campaigns.php' style='color:#569cd6;font-size:18px;'>→ Test Sayfasına Git</a></p>";
    } else {
        echo "<h2 class='error'>⚠️ Kurulum tamamlandı ama bazı veriler eksik</h2>";
        echo "<p>Lütfen hataları kontrol edin.</p>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>FATAL HATA: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($db->error) . "</pre>";
}
?>
