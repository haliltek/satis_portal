<?php
require_once 'include/vt.php';

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Establish connection
    $db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    mysqli_set_charset($db, "utf8");

    $category = 'FİLTRE MEDYA';
    $newProduct = '023411';

    echo "Updating campaign for category: $category\n";

    $res = mysqli_query($db, "SELECT * FROM custom_campaigns WHERE category_name LIKE '%$category%' LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) {
        echo "Found Campaign ID: " . $row['id'] . "\n";
        
        $products = json_decode($row['products'], true);
        if (!is_array($products)) {
            $products = [];
        }
        
        echo "Current Product Count: " . count($products) . "\n";
        
        if (!in_array($newProduct, $products)) {
            $products[] = $newProduct;
            $json = json_encode($products);
            
            echo "New Product Count: " . count($products) . "\n";
            echo "JSON Length: " . strlen($json) . "\n";

            $stmt = $db->prepare("UPDATE custom_campaigns SET products = ? WHERE id = ?");
            $stmt->bind_param("si", $json, $row['id']);
            $stmt->execute();
            
            echo "SUCCESS: Product $newProduct added to campaign.\n";
        } else {
            echo "Product $newProduct is ALREADY in the list.\n";
        }
    } else {
        echo "Campaign for '$category' not found.\n";
    }

} catch (mysqli_sql_exception $e) {
    echo "MySQL Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
?>
