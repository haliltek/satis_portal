<?php
// scripts/arp_api_example.php
// Simple CLI utility to create or update a Logo ARP card

use Proje\DatabaseManager;
use Proje\LogoService;
use Proje\LoggerService;
use App\Models\ArpMap;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables if available
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

$config = require __DIR__ . '/../config/config.php';

$logger = new LoggerService(__DIR__ . '/../logs/arp_api.log');
$db     = new DatabaseManager($config['db']);
$logo   = new LogoService(db: $db, configArray: $config, logErrorFile: __DIR__ . '/../logs/error.log', logDebugFile: __DIR__ . '/../logs/debug.log');

$code = $argv[1] ?? 'TEST001';
$company = $db->getCompanyInfo($code);

if ($company) {
    // Update existing company both in Logo and DB
    $logo->updateArpFromDb($company);
    $logger->info('ARP updated: ' . $code);
} else {
    // Minimal example data for new record
    $new = [
        's_turu'        => 3,
        's_arp_code'    => $code,
        's_adi'         => 'TEST ŞİRKETİ',
        's_adresi'      => '',
        's_adresi2'     => '',
        's_il'          => 'İSTANBUL',
        's_ilce'        => '',
        's_country_code'=> 'TR',
        's_postal_code' => '',
        's_auxil_code'  => '',
        's_auth_code'   => '',
        's_country'     => '',
        's_corresp_lang'=> '',
        's_telefonu'    => '',
        's_fax'         => '',
        's_web'         => '',
        's_vno'         => '',
        's_vd'          => '',
        's_tax_office_code' => '',
        'yetkili'       => '',
        'yetkili2'      => '',
        'yetkili3'      => '',
        'mail'          => '',
        'mail2'         => '',
        'mail3'         => '',
        'payplan_code'  => '',
        'payplan_def'   => '',
        's_gl_code'     => '',
        's_subscriber_ext' => '',
        'cl_ord_freq'   => 0,
        'logoid'        => '',
        'invoice_prnt_cnt' => 0,
        'accept_einv'   => 0,
        'profile_id'    => 0,
        'post_label'    => '',
        'sender_label'  => '',
        'factory_div_nr'=> 0,
        'create_wh_fiche' => 0,
        'disp_print_cnt'=> 0,
        'ord_print_cnt' => 0,
        'guid'          => '',
        'riskfact_chq'  => 0,
        'riskfact_promnt'=> 0,
        'low_level_codes1'=> '',
        'logo_company_code' => '',
        'currency'      => 0,
        'credit_limit'  => 0,
        'risk_limit'    => 0,
        'blocked'       => 0,
        'record_status' => 0,
    ];

    $db->insertCompany($new);
    $logo->createArpFromDb($new);
    $logger->info('ARP created: ' . $code);
}

echo "Operation completed.\n";
