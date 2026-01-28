<?php
require_once 'include/vt.php';

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = mysqli_connect($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    mysqli_set_charset($db, "utf8");

    $code = '023411';
    $newCategory = 'FİLTRE MEDYA';

    echo "Fixing category for $code...\n";

    $stmt = $db->prepare("UPDATE kampanya_ozel_fiyatlar SET kategori = ? WHERE stok_kodu LIKE ?");
    $likeCode = "%$code%";
    $stmt->bind_param("ss", $newCategory, $likeCode);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "SUCCESS: Updated category to '$newCategory' for $code.\n";
    } else {
        echo "WARNING: No rows updated. Maybe it was already correct or product not found?\n";
        
        // Check current value
        $stmt2 = $db->prepare("SELECT kategori FROM kampanya_ozel_fiyatlar WHERE stok_kodu LIKE ?");
        $stmt2->bind_param("s", $likeCode);
        $stmt2->execute();
        $res = $stmt2->get_result();
        if($row = $res->fetch_assoc()){
            echo "Current Category is: " . $row['kategori'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
