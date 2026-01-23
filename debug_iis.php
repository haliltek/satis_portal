<?php
/**
 * IIS Hata Ayıklama ve Bağlantı Test Sayfası
 * Bu dosyayı sunucunuza atıp çalıştırarak sorunları tespit edebilirsiniz.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>IIS Sistem Teşhis Sayfası</h1>";

// 1. PHP Eklentilerini Kontrol Et
echo "<h3>1. PHP Eklentileri Kontrolü</h3>";
$required_extensions = [
    'mysqli',
    'pdo_mysql',
    'sqlsrv',
    'pdo_sqlsrv',
    'curl',
    'openssl',
    'mbstring',
    'fileinfo'
];

echo "<ul>";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li><span style='color:green'>[✔] $ext</span> yüklü.</li>";
    } else {
        echo "<li><span style='color:red'>[✘] $ext</span> YÜKLÜ DEĞİL!</li>";
    }
}
echo "</ul>";

// 2. .env Dosyası Kontrolü
echo "<h3>2. Yapılandırma (.env) Kontrolü</h3>";
if (file_exists('.env')) {
    echo "<p style='color:green'>[✔] .env dosyası bulundu.</p>";
    $env_lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($env_lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name !== NULL) {
            $env[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
        }
    }
} else {
    echo "<p style='color:red'>[✘] .env dosyası BULUNAMADI!</p>";
}

// 3. Veritabanı Bağlantı Testleri
echo "<h3>3. Veritabanı Bağlantı Testleri</h3>";

require_once 'include/fonksiyon.php';

// MySQL Test
echo "<h4>MySQL (Yerel DB) Testi:</h4>";
try {
    $db = local_database();
    if ($db && $db->ping()) {
        echo "<p style='color:green'>[✔] MySQL bağlantısı başarılı. (Host: " . $db->host_info . ")</p>";
    } else {
        echo "<p style='color:red'>[✘] MySQL bağlantısı başarısız!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>[✘] MySQL Hatası: " . $e->getMessage() . "</p>";
}

// MSSQL GEMPA Test
echo "<h4>MSSQL Logo GEMPA Testi:</h4>";
if (!extension_loaded('sqlsrv')) {
    echo "<p style='color:orange'>[!] sqlsrv eklentisi yüklü olmadığı için test edilemiyor.</p>";
} else {
    try {
        $gempa = gempa_logo_veritabani();
        if ($gempa) {
            echo "<p style='color:green'>[✔] Logo GEMPA bağlantısı başarılı.</p>";
        } else {
            echo "<p style='color:red'>[✘] Logo GEMPA bağlantısı başarısız (bağlantı nesnesi null dönüyor).</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>[✘] Logo GEMPA Hatası: " . $e->getMessage() . "</p>";
    }
}

// MSSQL GEMAS Test
echo "<h4>MSSQL Logo GEMAS Testi:</h4>";
if (!extension_loaded('sqlsrv')) {
    echo "<p style='color:orange'>[!] sqlsrv eklentisi yüklü olmadığı için test edilemiyor.</p>";
} else {
    try {
        $gemas = gemas_logo_veritabani();
        if ($gemas) {
            echo "<p style='color:green'>[✔] Logo GEMAS bağlantısı başarılı.</p>";
        } else {
            echo "<p style='color:red'>[✘] Logo GEMAS bağlantısı başarısız (bağlantı nesnesi null dönüyor).</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>[✘] Logo GEMAS Hatası: " . $e->getMessage() . "</p>";
    }
}

// 4. Klasör Yazma İzinleri
echo "<h3>4. Klasör İzinleri Kontrolü</h3>";
$folders = ['logs', 'tmp', 'uploads'];
echo "<ul>";
foreach ($folders as $folder) {
    if (is_dir($folder)) {
        if (is_writable($folder)) {
            echo "<li><span style='color:green'>[✔] $folder</span> klasörü yazılabilir.</li>";
        } else {
            echo "<li><span style='color:red'>[✘] $folder</span> klasörüne YAZILAMIYOR! (IIS kullanıcısına izin verilmeli)</li>";
        }
    } else {
        echo "<li><span style='color:orange'>[!] $folder</span> klasörü bulunamadı (isteğe bağlı).</li>";
    }
}
echo "</ul>";

echo "<hr><p>Teşhis tamamlandı. Eğer her şey yeşil yanıyorsa ve hala 500 hatası alıyorsanız, PHP error log dosyasını inceleyiniz.</p>";
