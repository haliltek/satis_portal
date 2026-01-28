<?php
// test_logo.php - Logo Base64 Test
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logoPath = __DIR__ . '/logogemas.png';

echo "<h2>Logo Test</h2>";
echo "<p>Dosya yolu: " . $logoPath . "</p>";
echo "<p>Dosya var mı: " . (file_exists($logoPath) ? 'EVET ✓' : 'HAYIR ✗') . "</p>";

if (file_exists($logoPath)) {
    $fileSize = filesize($logoPath);
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
    
    echo "<p>Dosya boyutu: {$fileSize} bytes</p>";
    echo "<p>Base64 uzunluğu: " . strlen($logoBase64) . " karakter</p>";
    echo "<p>Logo görseli (HTML img):</p>";
    echo '<img src="' . $logoBase64 . '" style="max-width: 100px; border: 1px solid #ccc;">';
    
    echo "<br><br><p><strong>Logo doğrudan src ile:</strong></p>";
    echo '<img src="logogemas.png" style="max-width: 100px; border: 1px solid #ccc;">';
} else {
    echo "<p style='color:red'>HATA: Logo dosyası bulunamadı!</p>";
    
    // Alternatif yolları dene
    $altPaths = [
        __DIR__ . '/assets/images/logo-dark.png',
        __DIR__ . '/images/logo.png',
    ];
    
    echo "<p>Alternatif logolar:</p>";
    foreach ($altPaths as $alt) {
        echo "<p>{$alt}: " . (file_exists($alt) ? 'VAR' : 'YOK') . "</p>";
    }
}
?>
