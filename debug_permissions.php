<?php
// Debug script to check file permissions and Apache configuration

echo "<h1>🔍 Apache & File Permissions Debug</h1>";

echo "<h2>1. PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";

echo "<h2>2. File Permissions</h2>";
$files_to_check = [
    '/var/www/html',
    '/var/www/html/index.php',
    '/var/www/html/fonk.php',
    '/var/www/html/.htaccess',
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $owner = posix_getpwuid(fileowner($file));
        $group = posix_getgrgid(filegroup($file));
        
        echo "<strong>$file</strong><br>";
        echo "Permissions: " . substr(sprintf('%o', $perms), -4) . "<br>";
        echo "Owner: " . $owner['name'] . "<br>";
        echo "Group: " . $group['name'] . "<br>";
        echo "Readable: " . (is_readable($file) ? '✅ Yes' : '❌ No') . "<br>";
        echo "Writable: " . (is_writable($file) ? '✅ Yes' : '❌ No') . "<br>";
        echo "<br>";
    } else {
        echo "<strong>$file</strong> - ❌ NOT FOUND<br><br>";
    }
}

echo "<h2>3. Apache User</h2>";
echo "Current User: " . get_current_user() . "<br>";
echo "Process User: " . posix_getpwuid(posix_geteuid())['name'] . "<br>";

echo "<h2>4. Directory Listing</h2>";
echo "<pre>";
$output = shell_exec('ls -la /var/www/html/ 2>&1 | head -30');
echo htmlspecialchars($output);
echo "</pre>";

echo "<h2>5. Apache Configuration</h2>";
echo "<pre>";
$apache_conf = shell_exec('cat /etc/apache2/sites-available/000-default.conf 2>&1');
echo htmlspecialchars($apache_conf);
echo "</pre>";

echo "<h2>6. Database Connection Test</h2>";
try {
    include_once 'fonk.php';
    if (isset($db) && $db->ping()) {
        echo "✅ Database connection successful!<br>";
    } else {
        echo "❌ Database connection failed!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>7. Environment Variables</h2>";
echo "<pre>";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_PORT: " . getenv('DB_PORT') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";
echo "DB_USER: " . getenv('DB_USER') . "\n";
echo "REDIS_HOST: " . getenv('REDIS_HOST') . "\n";
echo "</pre>";
