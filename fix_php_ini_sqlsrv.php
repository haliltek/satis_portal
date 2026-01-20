<?php
// php.ini dosyasındaki SQLSRV extension satırlarını düzeltme scripti

$phpIniPath = 'C:\xampp\php\php.ini';

if (!file_exists($phpIniPath)) {
    die("php.ini dosyası bulunamadı: $phpIniPath\n");
}

$content = file_get_contents($phpIniPath);

// Yanlış dosya adlarını doğru olanlarla değiştir
$replacements = [
    'php_sqlsrv_82_ts_x64.dll' => 'php_sqlsrv_82_ts.dll',
    'php_pdo_sqlsrv_82_ts_x64.dll' => 'php_pdo_sqlsrv_82_ts.dll',
    'php_sqlsrv_82_nts_x64.dll' => 'php_sqlsrv_82_nts.dll',
    'php_pdo_sqlsrv_82_nts_x64.dll' => 'php_pdo_sqlsrv_82_nts.dll',
];

$modified = false;
foreach ($replacements as $wrong => $correct) {
    if (strpos($content, $wrong) !== false) {
        $content = str_replace($wrong, $correct, $content);
        $modified = true;
        echo "✓ '$wrong' -> '$correct' değiştirildi\n";
    }
}

// Eğer extension satırları yoksa ekle
if (strpos($content, 'php_sqlsrv_82_ts.dll') === false) {
    $content .= "\n; Microsoft SQL Server PHP Driver\n";
    $content .= "extension=php_sqlsrv_82_ts.dll\n";
    $content .= "extension=php_pdo_sqlsrv_82_ts.dll\n";
    $modified = true;
    echo "✓ Extension satırları eklendi\n";
}

if ($modified) {
    // Yedek al
    copy($phpIniPath, $phpIniPath . '.backup.' . date('YmdHis'));
    
    // Dosyayı kaydet
    file_put_contents($phpIniPath, $content);
    echo "\n✓ php.ini dosyası güncellendi!\n";
    echo "  Yedek dosya: {$phpIniPath}.backup." . date('YmdHis') . "\n";
    echo "\n⚠ Apache'yi yeniden başlatmanız gerekiyor!\n";
} else {
    echo "php.ini dosyasında değişiklik yapılmasına gerek yok.\n";
}

