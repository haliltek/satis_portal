<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><style>body{font-family:sans-serif;padding:20px;} .ok{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;} .section{border:1px solid #ccc;padding:15px;margin-bottom:15px;border-radius:5px;} h3{margin-top:0;}</style></head><body>";
echo "<h1>System Health Check</h1>";

// 1. PHP Environment
echo "<div class='section'><h3>1. PHP Environment</h3>";
echo "PHP Version: " . phpversion() . "<br>";
$exts = ['mysqli', 'pdo_mysql', 'sqlsrv', 'pdo_sqlsrv', 'redis', 'openssl', 'curl'];
echo "Extensions: ";
foreach ($exts as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='ok'>$ext &#10004;</span> ";
    } else {
        echo "<span class='fail'>$ext X</span> ";
    }
}
echo "</div>";

// 2. MySQL Connection (Local Interface)
echo "<div class='section'><h3>2. MySQL Connection (Local)</h3>";
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';
$db_name = getenv('DB_NAME') ?: 'b2bgemascom_teklif';

echo "Trying to connect to: <b>$db_host</b> / DB: <b>$db_name</b><br>";
$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    echo "Result: <span class='fail'>FAILED: " . $mysqli->connect_error . "</span><br>";
} else {
    echo "Result: <span class='ok'>CONNECTED &#10004;</span><br>";
    echo "Charset: " . $mysqli->character_set_name() . "<br>";
    
    // Check key tables
    $tables = ['yonetici', 'fiyat_onerileri', 'urunler'];
    foreach ($tables as $tbl) {
        $check = $mysqli->query("SHOW TABLES LIKE '$tbl'");
        if ($check && $check->num_rows > 0) {
             echo "Table '$tbl': <span class='ok'>EXISTS</span><br>";
        } else {
             echo "Table '$tbl': <span class='fail'>MISSING</span> - <a href='fix_schema.php'>[FIX NOW]</a><br>";
        }
    }
}
echo "</div>";

// 3. MySQL Connection (Remote/Gempa)
echo "<div class='section'><h3>3. MySQL Remote (Web DB)</h3>";
$r_host = "89.43.31.214"; // From fonk.php
$r_user = "gemas_mehmet";
$r_pass = "2261686Me!";
echo "Connecting to $r_host...<br>";
$remote = @mysqli_init();
$remote->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    try {
        if (@$remote->real_connect($r_host, $r_user, $r_pass, "gemas_pool_technology")) {
            echo "Result: <span class='ok'>CONNECTED &#10004;</span><br>";
        } else {
            echo "Result: <span class='fail'>FAILED: " . mysqli_connect_error() . "</span><br>";
        }
    } catch (Throwable $e) {
        echo "Result: <span class='fail'>CRASHED: " . $e->getMessage() . "</span><br>";
        echo "<small>Check firewall or remote server allow-list.</small><br>";
    }
echo "</div>";

// 4. MSSQL Connection (Logo)
echo "<div class='section'><h3>4. MSSQL Connection (Logo ERP)</h3>";
$ms_server = "192.168.5.253,1433";
$ms_user = "halil";
$ms_pass = "12621262";
echo "Target: $ms_server<br>";

if (!extension_loaded('pdo_sqlsrv')) {
    echo "<span class='fail'>pdo_sqlsrv extension missing! Drivers not installed?</span>";
} else {
    try {
        $conn = new PDO("sqlsrv:Server=$ms_server;Database=GEMAS2026;LoginTimeout=3", $ms_user, $ms_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Result: <span class='ok'>CONNECTED &#10004;</span><br>";
    } catch (Exception $e) {
        echo "Result: <span class='fail'>FAILED: " . $e->getMessage() . "</span><br>";
        echo "<small>Hint: If 'Login Timeout' or 'TCP Provider', check firewall/VPN.</small><br>";
    }
}
echo "</div>";

// 5. Redis
echo "<div class='section'><h3>5. Redis Connection</h3>";
$redis_host = 'redis';
$redis_pass = getenv('REDIS_PASSWORD');
try {
    $redis = new Redis();
    if (@$redis->connect($redis_host, 6379, 2.5)) {
        if ($redis_pass) {
            if ($redis->auth($redis_pass)) {
                 echo "Result: <span class='ok'>CONNECTED & AUTHENTICATED &#10004;</span><br>";
            } else {
                 echo "Result: <span class='fail'>CONNECTED but AUTH FAILED</span><br>";
            }
        } else {
             echo "Result: <span class='ok'>CONNECTED (No Pass?)</span><br>";
        }
    } else {
        echo "Result: <span class='fail'>CONNECTION FAILED</span><br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
echo "</div>";

// 6. Permissions
echo "<div class='section'><h3>6. Directory Permissions</h3>";
$dirs = ['upload', 'vendor']; // Check upload dir
foreach ($dirs as $d) {
    $path = __DIR__ . "/$d";
    if (file_exists($path)) {
        $perm = substr(sprintf('%o', fileperms($path)), -4);
        $write = is_writable($path) ? "<span class='ok'>WRITABLE</span>" : "<span class='fail'>NOT WRITABLE</span>";
        echo "$d: $perm - $write<br>";
    } else {
        echo "$d: <span class='fail'>MISSING</span><br>";
    }
}
echo "</div>";

echo "</body></html>";
?>
