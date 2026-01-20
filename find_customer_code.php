<?php
// find_customer_code.php
require_once "include/fonksiyon.php";

header('Content-Type: text/plain; charset=utf-8');

$search = "ERTEK"; // User mentioned "ERTEK YAPI" earlier
echo "Searching for company like '%$search%' in LG_526_CLCARD...\n";

gemas_logo_veritabani();

if (!isset($gemas_logo_db) || $gemas_logo_db === null) {
    die("Logo DB Connection Failed.");
}

try {
    $sql = "SELECT TOP 10 CODE, DEFINITION_ FROM LG_526_CLCARD WHERE DEFINITION_ LIKE :search OR CODE LIKE :codeSearch";
    $stmt = $gemas_logo_db->prepare($sql);
    $stmt->execute([
        ':search' => "%$search%",
        ':codeSearch' => "%120.01.E%"
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        echo "No matches found.\n";
    } else {
        foreach ($results as $r) {
            echo "Code: " . $r['CODE'] . " | Name: " . $r['DEFINITION_'] . "\n";
        }
    }

} catch (PDOException $e) {
    echo "SQL Exception: " . $e->getMessage();
}
