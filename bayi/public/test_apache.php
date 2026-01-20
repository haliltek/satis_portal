<?php
/**
 * Apache Test - Bu dosya çalışıyorsa Apache çalışıyor demektir
 * http://localhost/b2b-gemas-project-main/bayi/public/test_apache.php
 */

echo "<h2>✅ Apache Çalışıyor!</h2>";
echo "<p>Eğer bu mesajı görüyorsanız, Apache ve PHP çalışıyor demektir.</p>";
echo "<hr>";

echo "<h3>Test Linkler:</h3>";
echo "<ul>";
echo "<li><a href='test_direct.php'>Login Sayfası Testi</a></li>";
echo "<li><a href='login'>Login Sayfası (Laravel Route)</a></li>";
echo "<li><a href='index.php'>Laravel Index.php</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>PHP Bilgileri:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

