<?php
// run_sync.php - execute sync scripts within the current PHP process
$tz = 'Europe/Istanbul';
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set($tz);
}
$type = $_GET['type'] ?? '';
if (!in_array($type, ['companies', 'products', 'portal'])) {
    http_response_code(400);
    echo "invalid";
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
@set_time_limit(0);
ob_implicit_flush(true);

if ($type === 'companies') {
    require_once __DIR__ . '/scripts/sync_companies.php';
    sync_companies();
} elseif ($type === 'products') {
    require_once __DIR__ . '/scripts/sync_products.php';
    sync_products();
} else {
    require_once __DIR__ . '/scripts/sync_portal_products.php';
    sync_portal_products();
}
