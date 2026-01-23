<?php
// Debug script - teklif-olustur.php'nin hangi satırda takıldığını bulmak için
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 10); // 10 saniye sonra dursun

echo "1. Başlangıç OK<br>";
flush();

try {
    echo "2. fonk.php yükleniyor...<br>";
    flush();
    require_once 'fonk.php';
    echo "2. fonk.php OK<br>";
    flush();
    
    echo "3. Session kontrol...<br>";
    flush();
    // oturumkontrol(); // Bu fonksiyon redirect yapıyor olabilir, şimdilik skip
    echo "3. Session OK (skipped)<br>";
    flush();
    
    echo "4. Database bağlantısı test...<br>";
    flush();
    if (isset($db) && $db) {
        echo "4. DB bağlantısı OK<br>";
    } else {
        echo "4. DB bağlantısı YOK!<br>";
    }
    flush();
    
    echo "5. Config kontrol...<br>";
    flush();
    if (isset($config)) {
        echo "5. Config OK<br>";
    } else {
        echo "5. Config YOK!<br>";
    }
    flush();
    
    echo "6. LogoService kontrol...<br>";
    flush();
    if (isset($logoService)) {
        echo "6. LogoService OK<br>";
    } else {
        echo "6. LogoService YOK!<br>";
    }
    flush();
    
    // Payment plans çekme testi
    echo "7. Payment plans çekiliyor...<br>";
    flush();
    $firmNr = (int)($config['firmNr'] ?? 0);
    echo "7a. Firm number: $firmNr<br>";
    flush();
    
    if (isset($logoService)) {
        echo "7b. getPayPlans çağrılıyor...<br>";
        flush();
        $payPlans = $logoService->getPayPlans($firmNr);
        echo "7c. Payment plans alındı: " . count($payPlans) . " adet<br>";
        flush();
    }
    
    echo "8. Döviz kurları test...<br>";
    flush();
    $kurQuery = mysqli_query($db, "SELECT dolaralis, dolarsatis, euroalis, eurosatis FROM dovizkuru LIMIT 1");
    if ($kurQuery) {
        $kurlar = mysqli_fetch_assoc($kurQuery);
        echo "8. Kurlar OK: EUR=" . ($kurlar['euroalis'] ?? 'N/A') . "<br>";
    } else {
        echo "8. Kurlar HATA: " . mysqli_error($db) . "<br>";
    }
    flush();
    
    echo "<hr><h2 style='color:green;'>✓ TÜM TESTLER BAŞARILI!</h2>";
    echo "<p>Sorun başka bir yerde olabilir. ob_start() veya redirect olabilir.</p>";
    
} catch (Exception $e) {
    echo "<hr><h2 style='color:red;'>✗ HATA!</h2>";
    echo "<p><strong>Hata Mesajı:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
