<?php
// test_sessions_mock.php

// 1. Setup DB (Assume it works now)
// invoke session_start
$_POST['phone'] = "905551234567";
ob_start();
include __DIR__ . "/api/fiyat/session_start.php";
$start_res = ob_get_clean();

echo "Session Start Result: " . $start_res . "\n";

// 2. Check Session
$_GET['phone'] = "905551234567";
ob_start();
include __DIR__ . "/api/fiyat/check_session.php";
$check_res = ob_get_clean();

echo "Check Session Result: " . $check_res . "\n";

// 3. Check invalid
$_GET['phone'] = "905550000000";
ob_start();
include __DIR__ . "/api/fiyat/check_session.php";
$invalid_res = ob_get_clean();

echo "Check Invalid Result: " . $invalid_res . "\n";
?>
