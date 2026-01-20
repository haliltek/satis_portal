<?php
/**
 * mod_rewrite Test Script
 * http://localhost/b2b-gemas-project-main/bayi/test_rewrite.php
 */

echo "<h2>Apache mod_rewrite Test</h2>";
echo "<hr>";

// Check if mod_rewrite is enabled
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "✅ <strong>mod_rewrite is ENABLED</strong><br>";
    } else {
        echo "❌ <strong>mod_rewrite is DISABLED</strong><br>";
        echo "   You need to enable mod_rewrite in Apache configuration.<br>";
    }
} else {
    echo "⚠️ Cannot check mod_rewrite status (apache_get_modules() not available)<br>";
}

echo "<hr>";
echo "<h3>Test URLs:</h3>";
echo "<ul>";
echo "<li><a href='/b2b-gemas-project-main/bayi/login'>/b2b-gemas-project-main/bayi/login</a> (with .htaccess)</li>";
echo "<li><a href='/b2b-gemas-project-main/bayi/public/login'>/b2b-gemas-project-main/bayi/public/login</a> (direct)</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>File Check:</h3>";
$htaccess = __DIR__ . '/.htaccess';
$index = __DIR__ . '/index.php';
$publicIndex = __DIR__ . '/public/index.php';

if (file_exists($htaccess)) {
    echo "✅ .htaccess exists<br>";
} else {
    echo "❌ .htaccess NOT found<br>";
}

if (file_exists($index)) {
    echo "✅ index.php exists<br>";
} else {
    echo "❌ index.php NOT found<br>";
}

if (file_exists($publicIndex)) {
    echo "✅ public/index.php exists<br>";
} else {
    echo "❌ public/index.php NOT found<br>";
}

