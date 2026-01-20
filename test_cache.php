<?php
// test_cache.php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
$config = require __DIR__ . '/config/config.php';

use Proje\DatabaseManager;
use Proje\LogoService;

require_once __DIR__ . '/include/fonksiyon.php';
gemas_logo_veritabani();
gempa_logo_veritabani();

// Mock classes/dependencies slightly for testing if needed, 
// but here we can just instantiate the real service if config is correct.

$dbManager = new DatabaseManager($config['db']);
$logoService = new LogoService(
    db: $dbManager,
    configArray: $config,
    logErrorFile: __DIR__ . '/error.log',
    logDebugFile: __DIR__ . '/debug.log'
);

$firmNr = 997;

echo "1. Run: Fetching Departments (Should be slow/live first time, or fast if already cached)\n";
$start = microtime(true);
$deps = $logoService->getDepartments($firmNr);
$duration = microtime(true) - $start;
echo "1. Run Duration: " . number_format($duration, 4) . "s. Count: " . count($deps) . "\n";

echo "2. Run: Fetching Departments (Should be cached - FAST)\n";
$start = microtime(true);
$deps2 = $logoService->getDepartments($firmNr);
$duration = microtime(true) - $start;
echo "2. Run Duration: " . number_format($duration, 4) . "s. Count: " . count($deps2) . "\n";

$cacheFile = __DIR__ . '/cache/logo_metadata_' . md5("departments_{$firmNr}") . '.json';
if (file_exists($cacheFile)) {
    echo "SUCCESS: Cache file created: $cacheFile\n";
    echo "Cache Size: " . filesize($cacheFile) . " bytes\n";
} else {
    echo "FAILURE: Cache file NOT found!\n";
}
