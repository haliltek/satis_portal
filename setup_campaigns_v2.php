<?php
/**
 * Kampanya Sistemi Kurulum Script
 * 1. Tabloları yedekle
 * 2. Tabloları temizle/güncelle
 * 3. Kampanya verilerini yükle
 */

require_once __DIR__ . "/fonk.php";

echo "<h1>Kampanya Sistemi Kurulum</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}.success{color:#4ec9b0;}.error{color:#f48771;}</style>";

try {
    // 1. Reset script
    echo "<h2>1. Veritabanı Sıfırlama</h2>";
    $resetSQL = file_get_contents(__DIR__ . '/sql/20260121_reset_campaigns.sql');
    
    $statements = array_filter(array_map('trim', explode(';', $resetSQL)));
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $stmt) {
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;
        
        try {
            mysqli_query($db, $stmt);
            $successCount++;
        } catch (Exception $e) {
            echo "<div class='error'>HATA: " . $e->getMessage() . "</div>";
            echo "<pre>SQL: " . substr($stmt, 0, 100) . "...</pre>";
            $errorCount++;
        }
    }
    
    echo "<div class='success'>✓ {$successCount} komut başarıyla çalıştı</div>";
    if ($errorCount > 0) {
        echo "<div class='error'>✗ {$errorCount} komut hata verdi</div>";
    }
    
    // 2. Campaign data
    echo "<h2>2. Kampanya Verilerini Yükleme</h2>";
    $dataSQL = file_get_contents(__DIR__ . '/sql/20260121_campaign_data.sql');
    
    $dataStatements = array_filter(array_map('trim', explode(';', $dataSQL)));
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($dataStatements as $stmt) {
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;
        
        try {
            mysqli_query($db, $stmt);
            $successCount++;
        } catch (Exception $e) {
            echo "<div class='error'>HATA: " . $e->getMessage() . "</div>";
            echo "<pre>SQL: " . substr($stmt, 0, 100) . "...</pre>";
            $errorCount++;
        }
    }
    
    echo "<div class='success'>✓ {$successCount} komut başarıyla çalıştı</div>";
    if ($errorCount > 0) {
        echo "<div class='error'>✗ {$errorCount} komut hata verdi</div>";
    }
    
    // 3. Verification
    echo "<h2>3. Doğrulama</h2>";
    
    $campaignCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM custom_campaigns"))['cnt'];
    $productCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM custom_campaign_products"))['cnt'];
    $ruleCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM custom_campaign_rules"))['cnt'];
    $customerCount = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as cnt FROM custom_campaign_customers"))['cnt'];
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Tablo</th><th>Kayıt Sayısı</th><th>Beklenen</th><th>Durum</th></tr>";
    echo "<tr><td>Kampanyalar</td><td>{$campaignCount}</td><td>5</td><td>" . ($campaignCount == 5 ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td></tr>";
    echo "<tr><td>Ürünler</td><td>{$productCount}</td><td>78</td><td>" . ($productCount == 78 ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td></tr>";
    echo "<tr><td>Kurallar</td><td>{$ruleCount}</td><td>10</td><td>" . ($ruleCount >= 10 ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td></tr>";
    echo "<tr><td>Müşteriler</td><td>{$customerCount}</td><td>1+</td><td>" . ($customerCount >= 1 ? "<span class='success'>✓</span>" : "<span class='error'>✗</span>") . "</td></tr>";
    echo "</table>";
    
    echo "<h2>Kurulum Tamamlandı!</h2>";
    echo "<p><a href='test_campaigns.php' style='color:#569cd6;'>Test Sayfasına Git →</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>FATAL HATA: " . $e->getMessage() . "</div>";
}
?>
