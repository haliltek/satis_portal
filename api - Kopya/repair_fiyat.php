<?php
// api/repair_fiyat.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = '../fiyat_calismasi.php';
if (!file_exists($file)) die("Dosya bulunamadı");

$lines = file($file);
$newLines = [];
$skip = false;
$headerFixed = false;

foreach ($lines as $i => $line) {
    // 1. PHP Logic bitişinden sonraki çöpü atla
    // Satır 64 civarında 'if ($selectedWork)' bloğu bitiyor.
    // Ancak satır numarasına güvenmek yerine içeriğe bakalım.
    
    // Eğer satır '// ... (Aradaki kodlar' ile başlıyorsa (benim eklediğim comment)
    // Veya '<?php' ile başlıyor ama öncesinde HTML yoksa (syntax hatası olan yer)
    
    if (strpos($line, '// ... (Aradaki') !== false || strpos($line, '// ...') !== false) {
        $skip = true;
    }
    
    // <!doctype html> görünce skip bitir ve eksik PHP kapanışını ekle
    if (stripos($line, '<!doctype html>') !== false) {
        $skip = false;
        if (!$headerFixed) {
            $newLines[] = '$yonetici_id = $_SESSION[\'yonetici_id\'] ?? 0;' . "\n";
            $newLines[] = '?>' . "\n";
            $headerFixed = true;
        }
    }
    
    if (!$skip) {
        $newLines[] = $line;
    }
}

file_put_contents('../fiyat_calismasi.php', implode("", $newLines));
echo "Dosya onarıldı ve üzerine yazıldı.";
?>
