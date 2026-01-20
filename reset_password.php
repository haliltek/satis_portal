<?php
include "fonk.php";

$email = "bilgi@gemas.com";
$new_pass = "123456";
$new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

echo "<h1>Password Reset Tool</h1>";

// Check if user exists
$check = $db->query("SELECT * FROM yonetici WHERE eposta='$email'");
if ($check->num_rows > 0) {
    echo "User found: $email<br>";
    $update = $db->query("UPDATE yonetici SET parola='$new_hash' WHERE eposta='$email'");
    if ($update) {
        echo "<h2 style='color:green'>Password updated successfully!</h2>";
        echo "New password: <b>$new_pass</b><br>";
        echo "Please delete this file after use.";
    } else {
        echo "<h2 style='color:red'>Update failed: " . $db->error . "</h2>";
    }
} else {
    echo "<h2 style='color:red'>User $email not found!</h2>";
}
?>
