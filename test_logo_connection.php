<?php
// Logo veritabanı bağlantısını test etme scripti

echo "Logo Veritabanı Bağlantı Testi\n";
echo "==============================\n\n";

// 1. .env dosyası kontrolü
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "✓ .env dosyası bulundu\n";
    require_once __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    echo "✗ .env dosyası bulunamadı\n";
    echo "  Lütfen proje kök dizininde .env dosyası oluşturun\n\n";
}

// 2. PDO SQLSRV extension kontrolü
if (extension_loaded('pdo_sqlsrv')) {
    echo "✓ PDO SQLSRV extension yüklü\n";
} else {
    echo "✗ PDO SQLSRV extension yüklü DEĞİL\n";
    echo "  Lütfen Microsoft SQL Server PHP Driver'ı yükleyin\n\n";
    exit(1);
}

// 3. Config dosyasından Logo bilgilerini al
$config = require __DIR__ . '/config/config.php';
$logo = $config['logo'];

echo "\nLogo Bağlantı Bilgileri:\n";
echo "  Host: " . ($logo['host'] ?: 'BOŞ') . "\n";
echo "  User: " . ($logo['user'] ?: 'BOŞ') . "\n";
echo "  Pass: " . ($logo['pass'] ? '***' : 'BOŞ') . "\n";
echo "  DB: " . ($logo['db'] ?: 'BOŞ') . "\n\n";

if (empty($logo['host']) || empty($logo['user']) || empty($logo['db'])) {
    echo "✗ Logo bağlantı bilgileri eksik!\n";
    echo "  Lütfen .env dosyasını kontrol edin\n";
    exit(1);
}

// 4. Bağlantıyı test et
try {
    $dsn = "sqlsrv:Server={$logo['host']};Database={$logo['db']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    
    if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
        $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
    }
    
    echo "Bağlanılıyor...\n";
    $pdo = new PDO($dsn, $logo['user'], $logo['pass'], $options);
    echo "✓ Logo veritabanına başarıyla bağlanıldı!\n\n";
    
    // Test sorgusu
    $stmt = $pdo->query("SELECT TOP 1 CODE, NAME, NAME3 FROM LG_565_ITEMS WHERE NAME3 IS NOT NULL AND NAME3 != ''");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "Test Sorgusu Sonucu:\n";
        echo "  CODE: " . $row['CODE'] . "\n";
        echo "  NAME (TR): " . substr($row['NAME'], 0, 50) . "\n";
        echo "  NAME3 (EN): " . substr($row['NAME3'], 0, 50) . "\n";
        echo "\n✓ NAME3 alanı dolu ürünler bulundu!\n";
    } else {
        echo "⚠ NAME3 alanı dolu ürün bulunamadı\n";
        echo "  Logo veritabanında NAME3 alanları boş olabilir\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Bağlantı hatası: " . $e->getMessage() . "\n";
    echo "\nSorun Giderme:\n";
    echo "  1. Logo sunucusuna erişim var mı kontrol edin\n";
    echo "  2. Kullanıcı adı ve şifre doğru mu kontrol edin\n";
    echo "  3. Firewall ayarlarını kontrol edin\n";
    exit(1);
}

echo "\n✓ Tüm testler başarılı!\n";

