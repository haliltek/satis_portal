<?php
require 'fonk.php';

// Sirket tablosu sütunlarını göster
$result = $db->query('SHOW COLUMNS FROM sirket');
echo "Sirket tablosu sütunları:\n";
while($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . "\n";
}
