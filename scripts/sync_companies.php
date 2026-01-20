<?php
// scripts/sync_companies.php
// Fetch company data from Logo MSSQL, detect differences and apply them one by one
date_default_timezone_set('Europe/Istanbul');

use Proje\DatabaseManager;

require_once __DIR__ . '/../vendor/autoload.php';

function ensureSqlsrv()
{
    if (extension_loaded('pdo_sqlsrv')) {
        return;
    }

    if (function_exists('dl')) {
        $dir = ini_get('extension_dir');
        $prefix = stripos(PHP_OS, 'WIN') === 0 ? 'php_pdo_sqlsrv' : 'pdo_sqlsrv';
        $suffix = stripos(PHP_OS, 'WIN') === 0 ? '.dll' : '.so';
        $ver = PHP_MAJOR_VERSION . PHP_MINOR_VERSION; // e.g. 82 for PHP 8.2
        $candidates = [
            "$prefix$suffix",
            "$prefix_$ver$suffix",
            "$prefix_{$ver}_ts$suffix",
        ];
        foreach ($candidates as $lib) {
            if (@dl($lib)) {
                break;
            }
        }
    }

    if (!extension_loaded('pdo_sqlsrv')) {
        fwrite(
            STDERR,
            "PDO SQLSRV extension is required. Enable it in the php.ini used by the CLI or specify '-d extension=php_pdo_sqlsrv.dll' with the correct path.\n"
        );
        exit(1);
    }
}

function sync_companies() {
    ensureSqlsrv();

    function valuesEqual($a, $b): bool {
        if (is_numeric($a) && is_numeric($b)) {
            return (float)$a == (float)$b;
        }
        return (string)$a === (string)$b;
    }

    $columnExists = function (PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID(?) AND name = ?");
        if(!$stmt) return false;
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    };

$config = require __DIR__ . '/../config/config.php';

// MSSQL connection details are hard coded just like in urunler_senkron.php
$logo        = $config['logo'];
$mssqlHost    = $logo['host'];
$mssqlUser    = $logo['user'];
$mssqlPass    = $logo['pass'];
$mssqlDbGempa = $logo['db'];

$mysqlHost = $config['db']['host'];
$mysqlDb   = $config['db']['name'];
$mysqlUser = $config['db']['user'];
$mysqlPass = $config['db']['pass'];

$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if (defined('PDO::SQLSRV_ATTR_ENCODING') && defined('PDO::SQLSRV_ENCODING_UTF8')) {
    $options[PDO::SQLSRV_ATTR_ENCODING] = PDO::SQLSRV_ENCODING_UTF8;
}

try {
    $mssqlGempa = new PDO("sqlsrv:Server=$mssqlHost;Database=$mssqlDbGempa", $mssqlUser, $mssqlPass, $options);
} catch (PDOException $e) {
    fwrite(STDERR, "MSSQL connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$dbManager = new DatabaseManager([
    'host' => $mysqlHost,
    'user' => $mysqlUser,
    'pass' => $mysqlPass,
    'name' => $mysqlDb,
    'port' => $config['db']['port'] ?? 3306,
]);

function mapLogoRow(array $row): array {
    return [
        'internal_reference' => $row['LOGICALREF'] ?? null,
        's_adi'            => $row['DEFINITION_'] ?? null,
        's_arp_code'       => $row['CODE'] ?? null,
        's_adresi'         => trim(($row['ADDR1'] ?? '') . ' ' . ($row['ADDR2'] ?? '')) ?: null,
        's_il'             => $row['CITY'] ?? null,
        's_ilce'           => null,
        's_country_code'   => isset($row['COUNTRY_CODE']) ? substr((string)$row['COUNTRY_CODE'], 0, 5) : null,
        's_country'        => $row['COUNTRY'] ?? null,
        'trading_grp'      => $row['TRADING_GRP'] ?? null,
        's_telefonu'       => $row['TELNRS1'] ?? null,
        's_vno'            => null,
        's_vd'             => null,
        'yetkili'          => null,
        'mail'             => $row['EMAILADDR'] ?? null,
        'mailsifre'        => null,
        'smtp'             => null,
        'port'             => null,
        'kategori'         => null,
        'acikhesap'        => $row['BAKIYE'] ?? 0,
        'payplan_code'     => $row['PAYPLAN_CODE'] ?? null,
        'payplan_def'      => $row['PAYPLAN_DEF'] ?? null,
        'logo_company_code'=> $row['CODE'] ?? null
    ];
}

    $countryCol = $columnExists($mssqlGempa, 'LG_566_CLCARD', 'COUNTRY_CODE')
        ? 'COUNTRY_CODE'
        : ($columnExists($mssqlGempa, 'LG_566_CLCARD', 'COUNTRYCODE') ? 'COUNTRYCODE' : null);
    $countryField  = $countryCol ? "CL.$countryCol AS COUNTRY_CODE," : "'' AS COUNTRY_CODE,";
    $tradingCol = null;
    foreach (['TRADINGGRP','TRADING_GRP'] as $c) {
        if ($columnExists($mssqlGempa, 'LG_566_CLCARD', $c)) { $tradingCol = $c; break; }
    }
    $tradingField = $tradingCol ? "CL.$tradingCol AS TRADING_GRP," : "'' AS TRADING_GRP,";

    $sqlBase = "
    SELECT
        CL.LOGICALREF,
        CL.CODE,
        CL.DEFINITION_,
        CL.ADDR1,
        CL.ADDR2,
        CL.CITY,
        {$countryField}
        CL.COUNTRY,
        {$tradingField}
        CL.TELNRS1,
        CL.EMAILADDR,
        PP.CODE        AS PAYPLAN_CODE,
        PP.DEFINITION_ AS PAYPLAN_DEF,
        BAL.BAKIYE
    FROM LG_566_CLCARD CL
    LEFT JOIN LG_566_PAYPLANS PP ON CL.PAYMENTREF = PP.LOGICALREF
    LEFT JOIN (
        SELECT CLIENTREF,
               SUM(CASE WHEN SIGN = 0 THEN AMOUNT ELSE -AMOUNT END) AS BAKIYE
        FROM LG_566_01_CLFLINE
        WHERE CANCELLED = 0
        GROUP BY CLIENTREF
    ) BAL ON BAL.CLIENTREF = CL.LOGICALREF";

$sqlGempa = $sqlBase;

$logoRows = [];
$stmt = $mssqlGempa->query($sqlGempa);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $code = $row['CODE'];
    if (!$code) continue;
    $logoRows[$code] = $row;
}

$mysqlRows = $dbManager->getConnection()->query(
    'SELECT s_arp_code,s_adi,s_adresi,s_il,s_ilce,s_country_code,s_country,s_telefonu,s_vno,s_vd,yetkili,mail,mailsifre,smtp,port,kategori,acikhesap,logo_company_code,payplan_code,payplan_def,trading_grp,internal_reference FROM sirket'
)->fetch_all(MYSQLI_ASSOC);
$mysqlMap = [];
foreach ($mysqlRows as $r) {
    $mysqlMap[$r['s_arp_code']] = $r;
}

$new = [];
$updates = [];
foreach ($logoRows as $code => $row) {
    $mapped = mapLogoRow($row);
    if (!$code || empty($mapped['s_adi'])) continue;
    if (!isset($mysqlMap[$code])) {
        $new[$code] = $mapped;
    } else {
        $existing = $mysqlMap[$code];
        $diff = [];
        foreach ($mapped as $k => $v) {
            if ($k === 's_arp_code' || $k === 'logo_company_code') continue;
            $old = $existing[$k] ?? null;
            if (valuesEqual($old, $v)) continue;
            $diff[$k] = ['old'=>$old,'new'=>$v];
        }
        if ($diff) $updates[$code] = ['data'=>$mapped,'diff'=>$diff];
    }
}

echo "New companies: ".count($new)."\n";
echo "Updated companies: ".count($updates)."\n";

$toSave = array_values($new);
foreach ($updates as $info) {
    $toSave[] = $info['data'];
}
$conn = $dbManager->getConnection();
$conn->begin_transaction();
$affected = $dbManager->upsertCompanies($toSave);
$conn->commit();
echo "Upserted $affected rows\n";

echo "Done.\n";
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
@file_put_contents($logDir . '/companies_sync_time.txt', date('Y-m-d H:i:s'));
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    sync_companies();
}
