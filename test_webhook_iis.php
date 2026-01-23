<?php
/**
 * IIS Webhook Debug Script
 * Bu dosyayı çalıştırarak IIS'teki webhook sorunlarını tespit edin
 */

header("Content-Type: text/html; charset=utf-8");
echo "<h1>IIS Webhook Debug Testi</h1>";
echo "<style>
    body { font-family: 'Segoe UI', Arial; padding: 20px; background: #f5f5f5; }
    .test { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #ccc; }
    .pass { border-color: #28a745; }
    .fail { border-color: #dc3545; }
    .warn { border-color: #ffc107; }
    h3 { margin: 0 0 10px 0; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";

// Test 1: cURL Extension
echo "<div class='test " . (extension_loaded('curl') ? 'pass' : 'fail') . "'>";
echo "<h3>1. cURL Extension</h3>";
if (extension_loaded('curl')) {
    echo "✅ cURL yüklü ve aktif<br>";
    $curlVersion = curl_version();
    echo "Versiyon: " . $curlVersion['version'] . "<br>";
    echo "SSL Versiyon: " . $curlVersion['ssl_version'];
} else {
    echo "❌ <strong>cURL YÜKLENMEMİŞ!</strong><br>";
    echo "IIS'te <code>php.ini</code> dosyasında <code>extension=curl</code> satırını aktif edin.";
}
echo "</div>";

// Test 2: OpenSSL Extension
echo "<div class='test " . (extension_loaded('openssl') ? 'pass' : 'fail') . "'>";
echo "<h3>2. OpenSSL Extension</h3>";
if (extension_loaded('openssl')) {
    echo "✅ OpenSSL yüklü ve aktif<br>";
    echo "Versiyon: " . OPENSSL_VERSION_TEXT;
} else {
    echo "❌ <strong>OpenSSL YÜKLENMEMİŞ!</strong><br>";
    echo "HTTPS istekleri için gerekli.";
}
echo "</div>";

// Test 3: Log Klasörü Yazma İzni
$logDir = __DIR__ . '/api/teklif';
$logFile = $logDir . '/onay-gonder.log';

echo "<div class='test'>";
echo "<h3>3. Log Klasörü Yazma İzni</h3>";
echo "Log Klasörü: <code>$logDir</code><br>";

if (!file_exists($logDir)) {
    echo "⚠️ <span style='color: orange;'>Klasör yok, oluşturulmaya çalışılıyor...</span><br>";
    @mkdir($logDir, 0777, true);
}

if (is_writable($logDir)) {
    echo "✅ Klasör yazılabilir<br>";
    $testContent = date('Y-m-d H:i:s') . " - Test log entry\n";
    if (@file_put_contents($logFile, $testContent, FILE_APPEND)) {
        echo "✅ Log dosyasına yazma başarılı<br>";
        echo "Log dosyası: <code>$logFile</code>";
    } else {
        echo "❌ Log dosyasına yazılamadı!";
    }
} else {
    echo "❌ <strong>KLASÖR YAZILAMAZ!</strong><br>";
    echo "IIS Application Pool kullanıcısına yazma izni verin:<br>";
    echo "1. Klasöre sağ tıklayın → Properties → Security<br>";
    echo "2. IIS AppPool\\DefaultAppPool kullanıcısını ekleyin<br>";
    echo "3. Modify izni verin";
}
echo "</div>";

// Test 4: Webhook URL'e Erişim
$webhookUrl = "https://flow.gemas.com.tr/webhook/teklifOnay";
echo "<div class='test'>";
echo "<h3>4. Webhook URL Erişim Testi</h3>";
echo "Hedef URL: <code>$webhookUrl</code><br><br>";

if (extension_loaded('curl')) {
    $testData = [
        'test' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => 'IIS Debug Script'
    ];
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    curl_close($ch);
    
    if ($curlErrno === 0 && $httpCode >= 200 && $httpCode < 400) {
        echo "✅ Webhook'a başarıyla erişildi<br>";
        echo "HTTP Kod: <strong>$httpCode</strong><br>";
        echo "Yanıt: <pre style='background:#f4f4f4;padding:10px;'>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    } elseif ($curlErrno !== 0) {
        echo "❌ <strong>CURL HATASI!</strong><br>";
        echo "Hata Kodu: $curlErrno<br>";
        echo "Hata Mesajı: <strong style='color:red;'>$curlError</strong><br><br>";
        
        // Hata koduna göre çözüm önerileri
        if ($curlErrno == 6) {
            echo "💡 <strong>Çözüm:</strong> DNS çözümlenemedi. <code>flow.gemas.com.tr</code> adresinin doğru olduğundan emin olun.";
        } elseif ($curlErrno == 7) {
            echo "💡 <strong>Çözüm:</strong> Bağlantı reddedildi. Firewall veya güvenlik duvarı engelliyor olabilir.";
        } elseif ($curlErrno == 28) {
            echo "💡 <strong>Çözüm:</strong> Zaman aşımı. Sunucu yanıt vermiyor veya yavaş.";
        } elseif ($curlErrno == 60 || $curlErrno == 77) {
            echo "💡 <strong>Çözüm:</strong> SSL sertifika hatası. Geçerli bir SSL sertifikası yok veya güvenilir değil.";
        }
    } else {
        echo "⚠️ Webhook yanıt verdi ama beklenmeyen HTTP kodu<br>";
        echo "HTTP Kod: <strong>$httpCode</strong><br>";
        echo "Yanıt: <pre style='background:#f4f4f4;padding:10px;'>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }
} else {
    echo "⚠️ cURL yüklü olmadığı için test edilemedi.";
}
echo "</div>";

// Test 5: Outbound Firewall
echo "<div class='test warn'>";
echo "<h3>5. IIS Outbound Request İzinleri</h3>";
echo "⚠️ IIS'te outbound (dışarıya giden) isteklerin engellenmiş olabileceğini kontrol edin:<br><br>";
echo "<strong>Windows Firewall İçin:</strong><br>";
echo "1. Windows Defender Firewall → Advanced Settings<br>";
echo "2. Outbound Rules → New Rule<br>";
echo "3. Program: PHP executable path'ini seçin<br>";
echo "4. Allow the connection<br><br>";
echo "<strong>IIS Request Filtering:</strong><br>";
echo "1. IIS Manager → Site → Request Filtering<br>";
echo "2. URL tab'ında engellemeler olup olmadığını kontrol edin";
echo "</div>";

// Test 6: PHP Error Display
echo "<div class='test'>";
echo "<h3>6. PHP Hata Görüntüleme Ayarları</h3>";
echo "display_errors: <strong>" . ini_get('display_errors') . "</strong><br>";
echo "error_reporting: <strong>" . error_reporting() . "</strong><br>";
echo "log_errors: <strong>" . ini_get('log_errors') . "</strong><br>";
echo "error_log: <strong>" . (ini_get('error_log') ?: 'Belirtilmemiş') . "</strong><br><br>";
if (!ini_get('display_errors')) {
    echo "⚠️ Hatalar görüntülenmiyor. Debug için <code>php.ini</code>'de şu ayarları yapın:<br>";
    echo "<code>display_errors = On</code><br>";
    echo "<code>error_reporting = E_ALL</code>";
}
echo "</div>";

echo "<hr>";
echo "<h2>Sonuç ve Öneriler</h2>";
echo "<ol>";
echo "<li>Yukarıdaki testleri inceleyin</li>";
echo "<li>❌ işaretli testleri çözün</li>";
echo "<li>IIS'i yeniden başlatın</li>";
echo "<li>Tekrar deneyin</li>";
echo "</ol>";
?>
