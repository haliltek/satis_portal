<?php
// config/config.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// .env dosyası varsa yükle, yoksa varsayılan değerler kullanılacak
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

return [
    'clientId' => $_ENV['CLIENT_ID'] ?? '',
    'clientSecret' => $_ENV['CLIENT_SECRET'] ?? '',
    'tokenUrl' => $_ENV['TOKEN_URL'] ?? '',
    'apiBaseUrl' => $_ENV['API_BASE_URL'] ?? '',
    'username' => 'LOGO',
    'password' => $_ENV['PASSWORD'] ?? '',
    'firmNr' => $_ENV['GEMPA_FIRM_NR'] ?? '0',

    'db' => [
        'host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost'),
        'port' => getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306'),
        'user' => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root'),
        'pass' => getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? ''),
        'name' => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'gemas_portal'),
    ],

    'logo' => [
        'host' => $_ENV['LOGO_HOST'] ?? '',
        'user' => $_ENV['LOGO_USER'] ?? '',
        'pass' => $_ENV['LOGO_PASS'] ?? '',
        'db'   => $_ENV['LOGO_DB']   ?? '',
    ],
    
    'garanti' => [
        'client_id' => $_ENV['GARANTI_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GARANTI_CLIENT_SECRET'] ?? '',
        'token_url' => 'https://apis.garantibbva.com.tr:443/auth/oauth/v2/token',
        'currency_api_url' => 'https://apis.garantibbva.com.tr:443/garantileasing/integration/currencyrate/v1',
    ],
];
