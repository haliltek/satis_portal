<?php
// Redis Connection Debug Tool

$redis_host = 'redis'; // Docker compose service name
$redis_port = 6379;

echo "<h1>Redis Connectivity Check</h1>";

try {
    if (!class_exists('Redis')) {
        die("<h2 style='color:red'>Redis extension NOT installed in PHP!</h2>");
    }

    $redis = new Redis();
    $connected = $redis->connect($redis_host, $redis_port, 2.5); // 2.5 sec timeout
    $pass = getenv('REDIS_PASSWORD');
    if ($pass) {
        // Debugging info (Masked)
        $len = strlen($pass);
        $first = $len > 0 ? $pass[0] : '?';
        $last = $len > 0 ? $pass[$len-1] : '?';
        echo "Using Password from ENV: Length=$len, Starts='$first', Ends='$last'<br>";
        
        $redis->auth($pass);
    } else {
        echo "No REDIS_PASSWORD found in environment!<br>";
    }

    if ($connected) {
        echo "<h2 style='color:green'>Redis Connection SUCCESSFUL!</h2>";
        $redis->set('test_key', 'Redis is working securely!');
        $val = $redis->get('test_key');
        echo "Test Key Value: <b>$val</b><br>";
        echo "Redis Server Info: " . $redis->info()['redis_version'] . "<br>";
    } else {
        echo "<h2 style='color:red'>Could NOT connect to Redis at $redis_host:$redis_port</h2>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red'>Exception: " . $e->getMessage() . "</h2>";
}
?>
