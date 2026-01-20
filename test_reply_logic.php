<?php
// test_reply_logic.php

$phone = "905551234567";
$bot_msg_id = "BOT_MSG_12345";
$user_reply_correct = "BOT_MSG_12345";
$user_reply_wrong = "WRONG_ID_999";

echo "1. Saving Message ID [$bot_msg_id] for phone [$phone]...\n";
$_POST = ['phone' => $phone, 'message_id' => $bot_msg_id];
ob_start();
include __DIR__ . "/api/fiyat/save_message_id.php";
$res1 = ob_get_clean();
echo "Save Result: " . $res1 . "\n\n";

echo "2. Checking Valid Reply [$user_reply_correct]...\n";
$_GET = ['phone' => $phone, 'replied_message_id' => $user_reply_correct];
ob_start();
include __DIR__ . "/api/fiyat/check_reply.php";
$res2 = ob_get_clean();
echo "Valid Check Result: " . $res2 . "\n\n";

echo "3. Checking Invalid Reply [$user_reply_wrong]...\n";
$_GET = ['phone' => $phone, 'replied_message_id' => $user_reply_wrong];
ob_start();
include __DIR__ . "/api/fiyat/check_reply.php";
$res3 = ob_get_clean();
echo "Invalid Check Result: " . $res3 . "\n\n";

?>
