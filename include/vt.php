<?php
$sql_details = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '', 
    'db'   => getenv('DB_NAME') ?: 'b2bgemascom_teklif',
    'port' => getenv('DB_PORT') ?: 3306
];

