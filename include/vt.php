<?php
// DataTables için veritabanı bağlantı bilgileri
// Docker environment variables kullanılıyor
$sql_details = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '',
    'db'   => getenv('DB_NAME') ?: 'b2bgemascom_teklif',
];
