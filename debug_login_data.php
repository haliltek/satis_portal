<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "fonk.php";

echo "<h1>Database Debug Info</h1>";

if (!$db) {
    die("<h2 style='color:red'>Database Connection Failed!</h2>" . mysqli_connect_error());
} else {
    echo "<h2 style='color:green'>Database Connection Successful</h2>";
    echo "Host: " . (getenv('DB_HOST') ?: 'localhost') . "<br>";
    echo "DB Name: " . (getenv('DB_NAME') ?: 'b2bgemascom_teklif') . "<br>";
}

echo "<h3>Tables in Database:</h3>";
$tables = mysqli_query($db, "SHOW TABLES");
if ($tables) {
    echo "<ul>";
    $tableCount = 0;
    while ($row = mysqli_fetch_array($tables)) {
        echo "<li>" . $row[0] . "</li>";
        $tableCount++;
    }
    echo "</ul>";
    echo "<b>Total Tables: $tableCount</b>";
} else {
    echo "<p style='color:red'>Could not list tables: " . mysqli_error($db) . "</p>";
}

echo "<h3>Yonetici (Admins) Table Check:</h3>";
$admins = mysqli_query($db, "SELECT * FROM yonetici");
if ($admins) {
    if (mysqli_num_rows($admins) > 0) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Password Hash (First 10 chars)</th></tr>";
        while ($admin = mysqli_fetch_assoc($admins)) {
            echo "<tr>";
            echo "<td>" . $admin['yonetici_id'] . "</td>";
            echo "<td>" . $admin['adsoyad'] . "</td>";
            echo "<td>" . $admin['eposta'] . "</td>";
            echo "<td>" . $admin['tur'] . "</td>";
            echo "<td>" . substr($admin['parola'], 0, 10) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>Yonetici table is empty!</p>";
    }
} else {
    echo "<p style='color:red'>Query failed: " . mysqli_error($db) . "</p>";
}

echo "<h3>B2B Users (Dealers) Table Check (First 5):</h3>";
$dealers = mysqli_query($db, "SELECT * FROM b2b_users LIMIT 5");
if ($dealers) {
     if (mysqli_num_rows($dealers) > 0) {
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Company ID</th><th>Email</th><th>Status</th></tr>";
        while ($dealer = mysqli_fetch_assoc($dealers)) {
            echo "<tr>";
             echo "<td>" . $dealer['id'] . "</td>";
             echo "<td>" . $dealer['company_id'] . "</td>";
             echo "<td>" . $dealer['email'] . "</td>";
             echo "<td>" . $dealer['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>B2B Users table is empty!</p>";
    }
} else {
    echo "<p style='color:red'>Query failed: " . mysqli_error($db) . "</p>";
}

echo "<h3>Ayarlar Check:</h3>";
$settings = mysqli_query($db, "SELECT * FROM ayarlar LIMIT 1");
if ($settings) {
    $row = mysqli_fetch_assoc($settings);
    if ($row) {
        echo "<p>Settings found: " . htmlspecialchars($row['title'] ?? 'No Title') . "</p>";
    } else {
        echo "<p style='color:orange'>Settings table is empty.</p>";
    }
} else {
    echo "<p style='color:red'>Query failed: " . mysqli_error($db) . "</p>";
}
?>
