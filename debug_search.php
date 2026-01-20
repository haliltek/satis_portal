<?php
include "fonk.php";
include "include/vt.php";
// Mock session
$_SESSION['user_type'] = 'Admin'; 

// Prepare GET
$_GET['q'] = '120.01.E04';
$_GET['page'] = 1;

// Capture output
ob_start();
include 'musteri-search.php';
$json = ob_get_clean();

echo "JSON OUTPUT:\n";
echo $json;
?>
