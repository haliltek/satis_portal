<?php
// Redis Connection Debug Tool

$redis_host = 'redis'; // Docker compose service name
$redis_port = 6379;
$pass = getenv('REDIS_PASSWORD');

echo "<h1>Redis Connectivity Check</h1>";

try {
    if (!class_exists('Redis')) {
        die("<h2 style='color:red'>Redis extension NOT installed in PHP!</h2>");
    }

    $redis = new Redis();
    try {
        if ($pass) {
            // Debugging info (Masked)
            $len = strlen($pass);
            $first = $len > 0 ? $pass[0] : '?';
            $last = $len > 0 ? $pass[$len-1] : '?';
            echo "Using Password from ENV: Length=$len, Starts='$first', Ends='$last'<br>";
            
            if ($redis->auth($pass)) {
                echo "<h2 style='color:green'>Redis Auth SUCCESSFUL!</h2>";
                $connected = true;
            } else {
                 echo "<h2 style='color:red'>Redis Auth FAILED! (WRONGPASS)</h2>";
                 // Fallback: Try without password
                 echo "Attempting connection WITHOUT password...<br>";
                 try {
                     $redis = new Redis();
                     if ($redis->connect($redis_host, $redis_port, 1.0)) {
                         // Try a command
                         $redis->ping();
                         echo "<h2 style='color:orange'>WARNING: Redis connected WITHOUT password!</h2>";
                         $connected = true;
                     }
                 } catch (Exception $ex) {
                     echo "No-Auth connection also failed.<br>";
                 }
            }
        } else {
            echo "No REDIS_PASSWORD found in environment!<br>";
            $connected = true; // Assume no auth needed
        }

        if ($connected) {
            echo "<h2 style='color:green'>Redis Connection Verified</h2>";
            $redis->set('test_key', 'Redis is working securely!');
            $val = $redis->get('test_key');
            echo "Test Key Value: <b>$val</b><br>";
            try {
                echo "Redis Server Info: " . $redis->info()['redis_version'] . "<br>";
            } catch (Exception $e) { echo "Info failed: " . $e->getMessage(); }
        } else {
            echo "<h2 style='color:red'>Could NOT connect to Redis at $redis_host:$redis_port</h2>";
        }
    } catch (Exception $e) {
        echo "<h2 style='color:red'>Exception: " . $e->getMessage() . "</h2>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red'>Exception: " . $e->getMessage() . "</h2>";
}
?>
