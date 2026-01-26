<?php
// Direct migration runner
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'b2b_gemas';

$db = new mysqli($host, $user, $pass, $dbname);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$db->set_charset('utf8mb4');

echo "Connected to database: $dbname\n\n";

// Add columns
$queries = [
    "ALTER TABLE sirket ADD COLUMN IF NOT EXISTS specode1 VARCHAR(50) NULL AFTER trading_grp",
    "ALTER TABLE sirket ADD COLUMN IF NOT EXISTS specode2 VARCHAR(50) NULL AFTER specode1",
    "ALTER TABLE sirket ADD COLUMN IF NOT EXISTS specode3 VARCHAR(50) NULL AFTER specode2",
    "ALTER TABLE sirket ADD COLUMN IF NOT EXISTS specode4 VARCHAR(50) NULL AFTER specode3",
    "ALTER TABLE sirket ADD COLUMN IF NOT EXISTS specode5 VARCHAR(50) NULL AFTER specode4",
    "ALTER TABLE sirket ADD COLUMN IF NOT EXISTS is_export TINYINT(1) DEFAULT 0 AFTER specode5",
    "CREATE INDEX IF NOT EXISTS idx_is_export ON sirket(is_export)"
];

foreach ($queries as $query) {
    echo "Executing: " . substr($query, 0, 60) . "...\n";
    
    if ($db->query($query)) {
        echo "✓ Success\n\n";
    } else {
        // Check if error is "Duplicate column" which is OK
        if (strpos($db->error, 'Duplicate column') !== false) {
            echo "⚠ Column already exists (OK)\n\n";
        } else {
            echo "✗ Error: " . $db->error . "\n\n";
        }
    }
}

echo "Migration completed!\n";
$db->close();
