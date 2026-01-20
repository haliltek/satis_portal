<?php
// scripts/get_arp.php
// Fetch a single ARP card from Logo and optionally store/update in the local DB

use Proje\DatabaseManager;
use Proje\LogoService;
use App\Models\ArpMap;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';

$db   = new DatabaseManager($config['db']);
$logo = new LogoService(
    db: $db,
    configArray: $config,
    logErrorFile: __DIR__ . '/../logs/error.log',
    logDebugFile: __DIR__ . '/../logs/debug.log'
);

$code = $argv[1] ?? null;
if (!$code) {
    fwrite(STDERR, "Usage: php get_arp.php <ARP_CODE|INTERNAL_REF>\n");
    exit(1);
}

$arp = is_numeric($code)
    ? $logo->getArpMappedByRef((int)$code)
    : $logo->getArpMapped($code);
if (!$arp) {
    echo "No record found\n";
    exit(0);
}

if ($db->companyExists($code)) {
    $db->updateCompany($code, $arp);
    echo "Company updated in DB\n";
} else {
    $db->insertCompany($arp);
    echo "Company inserted into DB\n";
}

print_r($arp);
