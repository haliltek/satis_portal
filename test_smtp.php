<?php
// SMTP Test Script - Mail sunucusu ayarlarını test et
require_once 'fonk.php';

// Kullanıcı bilgilerini veritabanından çek
$userId = $_SESSION['yonetici_id'] ?? 1; // Default olarak 1 kullan

$yon = $db->prepare("SELECT adsoyad, mailposta, mailsmtp, mailport, mailparola FROM yonetici WHERE yonetici_id=?");
if ($yon) {
    $yon->bind_param('i', $userId);
    $yon->execute();
    $resYon = $yon->get_result();
    $yRow = $resYon->fetch_assoc();
    $yon->close();
    
    echo "<h2>Veritabanından Çekilen Mail Ayarları:</h2>";
    echo "<pre>";
    echo "Kullanıcı: " . ($yRow['adsoyad'] ?? 'Bulunamadı') . "\n";
    echo "Mail Adresi: " . ($yRow['mailposta'] ?? 'Bulunamadı') . "\n";
    echo "SMTP Host: " . ($yRow['mailsmtp'] ?? 'satis.gemas.com.tr (default)') . "\n";
    echo "SMTP Port: " . ($yRow['mailport'] ?? '465 (default)') . "\n";
    echo "Mail Şifresi: " . ($yRow['mailparola'] ?? 'Halil12621262. (default)') . "\n";
    echo "</pre>";
    
    // SMTP bağlantı testi
    echo "<h2>SMTP Bağlantı Testi:</h2>";
    $host = $yRow['mailsmtp'] ?? 'satis.gemas.com.tr';
    $port = $yRow['mailport'] ?? 465;
    
    echo "<p>Bağlantı test ediliyor: $host:$port</p>";
    
    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    if ($fp) {
        echo "<p style='color: green;'>✓ SMTP sunucusuna bağlantı BAŞARILI</p>";
        fclose($fp);
    } else {
        echo "<p style='color: red;'>✗ SMTP sunucusuna bağlantı BAŞARISIZ</p>";
        echo "<p>Hata: $errstr ($errno)</p>";
        echo "<p><strong>Sorun:</strong> Mail sunucusu erişilemiyor. Lütfen:</p>";
        echo "<ul>";
        echo "<li>Sunucu adresini kontrol edin</li>";
        echo "<li>Port numarasını kontrol edin</li>";
        echo "<li>Firewall/güvenlik duvarı ayarlarını kontrol edin</li>";
        echo "</ul>";
    }
    
} else {
    echo "<p style='color: red;'>Veritabanı sorgusu hazırlanamadı!</p>";
}

echo "<hr>";
echo "<h2>Önerilen Çözümler:</h2>";
echo "<ol>";
echo "<li><strong>Mail sunucusu ayarlarını kontrol edin:</strong> 'yonetici' tablosunda mailsmtp, mailport, mailparola kolonları doğru mu?</li>";
echo "<li><strong>Alternatif SMTP kullanın:</strong> Gmail SMTP (smtp.gmail.com:587) veya başka bir servis deneyin</li>";
echo "<li><strong>SSL/TLS ayarlarını kontrol edin:</strong> Port 465 için ssl, port 587 için tls kullanılmalı</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='teklifsiparisler.php'>← Tekliflere Dön</a></p>";
?>
