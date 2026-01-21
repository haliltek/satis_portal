<?php
// Minimal test - sadece fonk.php'yi yükle
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(5);

echo "START<br>";flush();

echo "Loading fonk.php...<br>";flush();
require_once 'fonk.php';
echo "fonk.php loaded!<br>";flush();

echo "Database: " . (isset($db) ? "OK" : "FAIL") . "<br>";flush();
echo "Config: " . (isset($config) ? "OK" : "FAIL") . "<br>";flush();

echo "<br>DONE - fonk.php works fine!<br>";
echo "<p><strong>Sorun:</strong> Muhtemelen logoService->getPayPlans() çağrısı takılıyor.</p>";
echo "<p><strong>Çözüm:</strong> teklif-olustur.php'de bu satırı yoruma al:</p>";
echo "<pre>\$payPlans = \$logoService->getPayPlans(\$firmNr);</pre>";
