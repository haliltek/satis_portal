<?php
/**
 * Health Check Endpoint for Coolify
 * Returns JSON status of critical services
 */

header('Content-Type: application/json');

$status = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// 1. Database Check
try {
    $config = require __DIR__ . '/config/config.php';
    $dbConfig = $config['db'];
    
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']}",
        $dbConfig['user'],
        $dbConfig['pass'],
        [PDO::ATTR_TIMEOUT => 3]
    );
    
    $status['checks']['database'] = [
        'status' => 'ok',
        'message' => 'Database connection successful'
    ];
} catch (Exception $e) {
    $status['status'] = 'unhealthy';
    $status['checks']['database'] = [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
}

// 2. Redis Check
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect(getenv('REDIS_HOST') ?: 'redis', 6379, 3);
        $redis->ping();
        
        $status['checks']['redis'] = [
            'status' => 'ok',
            'message' => 'Redis connection successful'
        ];
    } else {
        $status['checks']['redis'] = [
            'status' => 'warning',
            'message' => 'Redis extension not installed'
        ];
    }
} catch (Exception $e) {
    $status['status'] = 'unhealthy';
    $status['checks']['redis'] = [
        'status' => 'error',
        'message' => 'Redis connection failed: ' . $e->getMessage()
    ];
}

// 3. Disk Space Check
$freeSpace = disk_free_space('/');
$totalSpace = disk_total_space('/');
$usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

$status['checks']['disk'] = [
    'status' => $usedPercent > 90 ? 'warning' : 'ok',
    'used_percent' => round($usedPercent, 2),
    'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2)
];

// 4. PHP Version
$status['checks']['php'] = [
    'version' => PHP_VERSION,
    'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false
];

// Set HTTP status code
http_response_code($status['status'] === 'healthy' ? 200 : 503);

echo json_encode($status, JSON_PRETTY_PRINT);
