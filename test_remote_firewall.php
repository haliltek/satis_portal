<?php
// test_remote_firewall.php
// This script specifically tests the network path to the Remote Portal DB

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Remote DB Connectivity Test</h1>";

// Configuration (Defaults matching codebase)
$host = getenv('GEMAS_WEB_HOST') ?: "89.43.31.214";
$port = getenv('GEMAS_WEB_PORT') ?: 3306;

echo "<p><strong>Target:</strong> $host : $port</p>";
echo "<p><strong>Server IP (This Machine):</strong> " . $_SERVER['SERVER_ADDR'] . "</p>";
echo "<p><strong>Outbound IP (approx):</strong> " . file_get_contents('https://api.ipify.org') . "</p>";

echo "<hr>";

// 1. TCP Socket Test
echo "<h3>1. TCP Socket Test (fsockopen)</h3>";
$timeout = 5;
$start = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
$duration = round(microtime(true) - $start, 4);

if ($fp) {
    echo "<p style='color:green'><strong>SUCCESS:</strong> Connected to $host on port $port in $duration seconds.</p>";
    fclose($fp);
} else {
    echo "<p style='color:red'><strong>FAILURE:</strong> Could not connect.</p>";
    echo "<ul>";
    echo "<li>Error Number: $errno</li>";
    echo "<li>Error String: $errstr</li>";
    echo "<li>Time Taken: $duration seconds</li>";
    echo "</ul>";
    
    if ($errno == 10060 || $errno == 110) {
        echo "<p style='background:orange; padding:10px;'><strong>DIAGNOSIS: TIMEOUT</strong><br>The server did not respond. This usually means the IP address is completely filtered (DROP packet) by a firewall.</p>";
    } elseif ($errno == 10061 || $errno == 111) {
         echo "<p style='background:salmon; padding:10px;'><strong>DIAGNOSIS: CONNECTION REFUSED</strong><br>The server actively rejected the connection. This usually means the service is running but explicitly blocking this IP, or the port is closed.</p>";
    }
}

echo "<hr>";

// 2. MySQLi Test
echo "<h3>2. MySQL Driver Test</h3>";
$user = getenv('GEMAS_WEB_USER') ?: "gemas_mehmet";
$pass = getenv('GEMAS_WEB_PASS') ?: "2261686Me!";
$db   = getenv('GEMAS_WEB_DB')   ?: "gemas_pool_technology";

$mysqli = mysqli_init();
$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

echo "Attempting real_connect...<br>";
try {
    if (@$mysqli->real_connect($host, $user, $pass, $db, $port)) {
         echo "<p style='color:green'><strong>SUCCESS:</strong> MySQL Connection Established!</p>";
         echo "Server Info: " . $mysqli->get_server_info();
         $mysqli->close();
    } else {
         echo "<p style='color:red'><strong>FAILURE:</strong> " . mysqli_connect_error() . "</p>";
         echo "<p>Error No: " . mysqli_connect_errno() . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'><strong>EXCEPTION:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><em>If these tests fail, please screenshot this page and send it to your hosting provider for <strong>$host</strong> to request whitelisting of this server's IP.</em></p>";
?>
