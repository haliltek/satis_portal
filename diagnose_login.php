<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

include "fonk.php";

echo "<h1>Login/Session Diagnostic Tool</h1>";

// 1. Session Test
echo "<h3>1. Session Persistence Test</h3>";
if (!isset($_SESSION['test_time'])) {
    $_SESSION['test_time'] = time();
    echo "Session variable 'test_time' set to: " . $_SESSION['test_time'] . "<br>";
    echo "<b>Refresh this page to see if it persists.</b><br>";
} else {
    echo "Session variable 'test_time' FOUND: " . $_SESSION['test_time'] . "<br>";
    echo "<span style='color:green'>Session persistence seems to be WORKING.</span><br>";
}
echo "Session ID: " . session_id() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

// 2. DB Connection
echo "<h3>2. Database Check</h3>";
if ($db) {
    echo "<span style='color:green'>Database connected.</span><br>";
} else {
    echo "<span style='color:red'>Database connection FAILED: " . mysqli_connect_error() . "</span><br>";
}

// 3. Auth Test Form
echo "<h3>3. Authentication Tester</h3>";
?>
<form method="post" style="border:1px solid #ccc; padding:10px; background:#f9f9f9;">
    Email: <input type="text" name="test_email" value="<?php echo $_POST['test_email'] ?? 'bilgi@gemas.com'; ?>"><br>
    Password: <input type="text" name="test_pass" value="<?php echo $_POST['test_pass'] ?? '123456'; ?>"><br>
    <input type="submit" value="Test Login Logic">
</form>

<?php
if ($_POST) {
    echo "<h4>Test Results:</h4>";
    $input_email = $_POST['test_email'];
    $input_pass = $_POST['test_pass'];

    // Check Yonetici
    $stmt = $db->prepare("SELECT * FROM yonetici WHERE eposta = ?");
    $stmt->bind_param('s', $input_email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user) {
        echo "User FOUND in 'yonetici'<br>";
        echo "Stored Hash: " . $user['parola'] . "<br>";
        
        $bcrypt = password_verify($input_pass, $user['parola']);
        echo "Bcrypt Verify: " . ($bcrypt ? "<font color=green>TRUE</font>" : "<font color=red>FALSE</font>") . "<br>";
        
        $md5 = (md5($input_pass) === $user['parola']);
        echo "MD5 Verify: " . ($md5 ? "<font color=green>TRUE</font>" : "<font color=red>FALSE</font>") . "<br>";
        
        echo "Calculated MD5: " . md5($input_pass) . "<br>";
        echo "Input Hex: " . bin2hex($input_pass) . "<br>";
        echo "User ID: " . $user['yonetici_id'] . "<br>";
    } else {
        echo "User NOT found in 'yonetici'<br>";
    }

    echo "<hr>";

    // Check Dealer
    $stmt2 = $db->prepare("SELECT * FROM b2b_users WHERE email = ?");
    $stmt2->bind_param('s', $input_email);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $dealer = $res2->fetch_assoc();

    if ($dealer) {
        echo "User FOUND in 'b2b_users'<br>";
        echo "Stored Hash: " . $dealer['password'] . "<br>";
        
        $bcrypt = password_verify($input_pass, $dealer['password']);
        echo "Bcrypt Verify: " . ($bcrypt ? "<font color=green>TRUE</font>" : "<font color=red>FALSE</font>") . "<br>";

        $md5 = (md5($input_pass) === $dealer['password']);
        echo "MD5 Verify: " . ($md5 ? "<font color=green>TRUE</font>" : "<font color=red>FALSE</font>") . "<br>";
    } else {
        echo "User NOT found in 'b2b_users'<br>";
    }
}
?>
