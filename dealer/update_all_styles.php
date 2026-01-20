<?php
// Bu dosya tüm dealer sayfalarının stil include'larını güncellemek için
echo "<h2>Tüm sayfaların stilleri güncelleniyor...</h2>";

$files = [
    'account.php',
    'cart.php',
    'create_order.php',
    'discounts.php',
    'invoices.php',
    'open_account.php',
    'order_detail.php',
    'orders.php',
    'payments.php',
    'products.php',
    'profile.php',
    'support.php'
];

$oldStylePattern = '/<link.*?bootstrap\.min\.css.*?>.*?<link.*?icons\.min\.css.*?>.*?<link.*?app\.min\.css.*?>/s';
$newStyles = '<?php include "includes/styles.php"; ?>';

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Eski stil linklerini bul ve değiştir
        $content = preg_replace(
            '/<link.*?href="\.\.\/assets\/css\/bootstrap\.min\.css".*?>/',
            '',
            $content
        );
        $content = preg_replace(
            '/<link.*?href="\.\.\/assets\/css\/icons\.min\.css".*?>/',
            '',
            $content
        );
        $content = preg_replace(
            '/<link.*?href="\.\.\/assets\/css\/app\.min\.css".*?>/',
            '<?php include "includes/styles.php"; ?>',
            $content
        );
        
        // Inline stilleri kaldır (page-header, stat-card, vb.)
        $content = preg_replace(
            '/<style>.*?\.page-header\s*{.*?}.*?<\/style>/s',
            '',
            $content
        );
        
        file_put_contents($file, $content);
        echo "✅ $file güncellendi<br>";
    } else {
        echo "❌ $file bulunamadı<br>";
    }
}

echo "<br><strong>✅ Tüm dosyalar güncellendi!</strong><br>";
echo "<a href='index.php'>Giriş sayfasına git</a>";
?>

