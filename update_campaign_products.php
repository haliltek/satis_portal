<?php
require_once 'include/vt.php';

// Establish connection
$db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
mysqli_set_charset($db, "utf8");

$category = 'FİLTRE MEDYA';
$newProduct = '023411';

echo "Updating campaign for category: $category\n";

$res = mysqli_query($db, "SELECT * FROM custom_campaigns WHERE category_name LIKE '%$category%' LIMIT 1");
if ($row = mysqli_fetch_assoc($res)) {
    echo "Found Campaign ID: " . $row['id'] . "\n";
    echo "Current Products: " . $row['products'] . "\n";
    
    $products = json_decode($row['products'], true);
    if (!is_array($products)) {
        $products = [];
    }
    
    if (!in_array($newProduct, $products)) {
        $products[] = $newProduct;
        $json = json_encode($products);
        
        $stmt = $db->prepare("UPDATE custom_campaigns SET products = ? WHERE id = ?");
        $stmt->bind_param("si", $json, $row['id']);
        
        if ($stmt->execute()) {
            echo "SUCCESS: Product $newProduct added to campaign.\n";
            echo "New List: $json\n";
        } else {
            echo "ERROR: Could not update database.\n";
        }
    } else {
        echo "Product $newProduct is ALREADY in the list.\n";
    }
} else {
    echo "Campaign for '$category' not found.\n";
}
?>
