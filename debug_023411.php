<?php
require_once 'include/vt.php';

// Establish connection
$db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
mysqli_set_charset($db, "utf8");

$code = '023411';
echo "Checking Product: $code\n";

// 1. Check Product Details
$stmt = $db->prepare("SELECT * FROM urunler WHERE stokkodu LIKE ?");
$likeCode = "%$code%";
$stmt->bind_param("s", $likeCode);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();

if ($product) {
    echo "Found Product in urunler:\n";
    echo "ID: " . $product['urun_id'] . "\n";
    echo "Code: " . $product['stokkodu'] . "\n";
    echo "Name: " . $product['stokadi'] . "\n";
    // Check possible category columns
    echo "Category (kategori): " . ($product['kategori'] ?? 'NULL') . "\n"; 
    echo "LogicalRef: " . $product['LOGICALREF'] . "\n";
} else {
    echo "Product not found in urunler!\n";
}

echo "\n-------------------\n";

// 2. Check Valid Special Prices Table
echo "Checking kampanya_ozel_fiyatlar for this product:\n";
$stmt2 = $db->prepare("SELECT * FROM kampanya_ozel_fiyatlar WHERE stok_kodu LIKE ?");
$stmt2->bind_param("s", $likeCode);
$stmt2->execute();
$res2 = $stmt2->get_result();
$specialParam = $res2->fetch_assoc();

if ($specialParam) {
    echo "Found in kampanya_ozel_fiyatlar:\n";
    print_r($specialParam);
} else {
    echo "Product NOT FOUND in kampanya_ozel_fiyatlar table.\n";
}

echo "\n-------------------\n";

// 3. Check Custom Campaigns rules for Filter Media
echo "Checking custom_campaigns table for 'FİLTRE MEDYA':\n";
$res3 = mysqli_query($db, "SELECT * FROM custom_campaigns WHERE category_name LIKE '%FİL%' OR category_name LIKE '%FIL%'");
if(mysqli_num_rows($res3) > 0){
    while ($row = mysqli_fetch_assoc($res3)) {
        echo "Campaign ID: " . $row['id'] . "\n";
        echo "Category Name: " . $row['category_name'] . "\n";
        echo "Min Qty: " . $row['min_quantity'] . "\n";
        echo "Min Total Amount: " . $row['min_total_amount'] . "\n";
        echo "Discount Rate: " . $row['discount_rate'] . "\n";
        echo "Products JSON (Sample): " . substr($row['products'], 0, 50) . "...\n";
        
        // Log if 023411 is in the JSON
        $prods = json_decode($row['products'], true);
        if(is_array($prods) && in_array($code, $prods)){
             echo ">>> PRODUCT $code IS IN THIS CAMPAIGN RULE! <<<\n";
        } else {
             echo ">>> Product $code is NOT in this campaign rule explicitly.\n";
        }
        echo "\n";
    }
} else {
    echo "No campaign rules found for Filter Media.\n";
    // List all categories just in case
    echo "Listing all categories:\n";
    $resAll = mysqli_query($db, "SELECT id, category_name FROM custom_campaigns");
    while($r = mysqli_fetch_assoc($resAll)){
        echo $r['id'] . ": " . $r['category_name'] . "\n";
    }
}
?>
