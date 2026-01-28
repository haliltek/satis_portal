<?php
require_once 'include/vt.php';

try {
    $pdo = new PDO("mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4", $sql_details['user'], $sql_details['pass']);
    
    $stmt = $pdo->prepare("SELECT * FROM custom_campaigns WHERE category_name LIKE '%LED%'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $output = "";
    foreach ($rows as $row) {
        $output .= "Row for " . $row['category_name'] . ":\n";
        foreach ($row as $key => $val) {
            // Check for values close to 1500 (float comparison)
            if (abs(floatval($val) - 1500) < 0.01) {
                $output .= "FOUND 1500 in column: [$key] => $val\n";
            }
        }
        $output .= print_r($row, true);
    }
    file_put_contents('db_debug.txt', $output);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
