<?php
// Giriş Testi ve Kullanıcı Oluşturma
include "../include/vt.php";

echo "<h2>B2B Giriş Sistemi Test</h2>";
echo "<hr>";

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8mb4');

if ($db->connect_error) {
    die("Veritabanı bağlantı hatası: " . $db->connect_error);
}

echo "✅ Veritabanı bağlantısı başarılı<br><br>";

// b2b_users tablosunu kontrol et
$tableCheck = $db->query("SHOW TABLES LIKE 'b2b_users'");
if ($tableCheck->num_rows == 0) {
    echo "❌ b2b_users tablosu bulunamadı! Tablo oluşturuluyor...<br>";
    
    $createTable = "CREATE TABLE `b2b_users` (
        `id` int NOT NULL AUTO_INCREMENT,
        `company_id` int NOT NULL,
        `cari_code` varchar(50) DEFAULT NULL,
        `username` varchar(255) NOT NULL,
        `email` varchar(255) NOT NULL,
        `password` varchar(255) NOT NULL,
        `status` tinyint NOT NULL DEFAULT '1',
        `role` varchar(50) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($db->query($createTable)) {
        echo "✅ b2b_users tablosu oluşturuldu<br><br>";
    } else {
        echo "❌ Tablo oluşturma hatası: " . $db->error . "<br><br>";
    }
} else {
    echo "✅ b2b_users tablosu mevcut<br><br>";
}

// Mevcut kullanıcıları listele
echo "<h3>Mevcut B2B Kullanıcıları:</h3>";
$users = $db->query("SELECT id, username, email, company_id, cari_code, status FROM b2b_users");
if ($users && $users->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Kullanıcı Adı</th><th>E-posta</th><th>Şirket ID</th><th>Cari Kodu</th><th>Durum</th></tr>";
    while ($user = $users->fetch_assoc()) {
        $statusText = $user['status'] == 1 ? '✅ Aktif' : '❌ Pasif';
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['company_id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['cari_code']) . "</td>";
        echo "<td>" . $statusText . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Hiç kullanıcı bulunamadı!<br><br>";
}

// Şirketleri listele (test için)
echo "<h3>Mevcut Şirketler (İlk 5):</h3>";
$companies = $db->query("SELECT sirket_id, s_adi, s_arp_code FROM sirket LIMIT 5");
if ($companies && $companies->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Şirket ID</th><th>Ünvan</th><th>Cari Kodu</th></tr>";
    while ($company = $companies->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $company['sirket_id'] . "</td>";
        echo "<td>" . htmlspecialchars($company['s_adi']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($company['s_arp_code']) . "</strong></td>";
        echo "</tr>";
    }
    echo "</table><br>";
}

// Test kullanıcısı oluştur
echo "<hr>";
echo "<h3>Test Kullanıcısı Oluştur:</h3>";

// İlk şirketi al
$firstCompany = $db->query("SELECT sirket_id, s_arp_code, s_adi FROM sirket LIMIT 1")->fetch_assoc();

if ($firstCompany) {
    $testUsername = 'test_bayi';
    $testPassword = 'test123';
    $testEmail = 'test@bayi.com';
    $testCompanyId = $firstCompany['sirket_id'];
    $testCariCode = $firstCompany['s_arp_code'];
    
    // Kullanıcı var mı kontrol et
    $checkUser = $db->prepare("SELECT id FROM b2b_users WHERE username = ?");
    $checkUser->bind_param('s', $testUsername);
    $checkUser->execute();
    $existingUser = $checkUser->get_result();
    
    if ($existingUser->num_rows > 0) {
        echo "⚠️ '<strong>$testUsername</strong>' kullanıcısı zaten mevcut. Şifreyi güncelliyorum...<br>";
        
        $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
        $updateStmt = $db->prepare("UPDATE b2b_users SET password = ?, email = ?, company_id = ?, cari_code = ?, status = 1 WHERE username = ?");
        $updateStmt->bind_param('ssiss', $hashedPassword, $testEmail, $testCompanyId, $testCariCode, $testUsername);
        
        if ($updateStmt->execute()) {
            echo "✅ Kullanıcı güncellendi!<br>";
        } else {
            echo "❌ Güncelleme hatası: " . $updateStmt->error . "<br>";
        }
        $updateStmt->close();
    } else {
        echo "➕ Yeni kullanıcı oluşturuluyor...<br>";
        
        $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
        $insertStmt = $db->prepare("INSERT INTO b2b_users (company_id, cari_code, username, email, password, status, role) VALUES (?, ?, ?, ?, ?, 1, 'dealer')");
        $insertStmt->bind_param('issss', $testCompanyId, $testCariCode, $testUsername, $testEmail, $hashedPassword);
        
        if ($insertStmt->execute()) {
            echo "✅ Test kullanıcısı oluşturuldu!<br>";
        } else {
            echo "❌ Oluşturma hatası: " . $insertStmt->error . "<br>";
        }
        $insertStmt->close();
    }
    $checkUser->close();
    
    echo "<br>";
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>🎉 Test Giriş Bilgileri:</h4>";
    echo "<p style='margin: 5px 0;'><strong>URL:</strong> <a href='index.php' target='_blank'>http://localhost/b2b-gemas-project-main/dealer/</a></p>";
    echo "<p style='margin: 5px 0;'><strong>Kullanıcı Adı:</strong> <code style='background: #fff; padding: 2px 5px; border-radius: 3px;'>$testUsername</code></p>";
    echo "<p style='margin: 5px 0;'><strong>Şifre:</strong> <code style='background: #fff; padding: 2px 5px; border-radius: 3px;'>$testPassword</code></p>";
    echo "<p style='margin: 5px 0;'><strong>Bağlı Şirket:</strong> " . htmlspecialchars($firstCompany['s_adi']) . " (" . htmlspecialchars($testCariCode) . ")</p>";
    echo "</div><br>";
    
    // Şifre testi
    echo "<h4>Şifre Doğrulama Testi:</h4>";
    $testHash = password_hash($testPassword, PASSWORD_BCRYPT);
    $verifyResult = password_verify($testPassword, $testHash);
    echo "Test Hash: <code>" . substr($testHash, 0, 50) . "...</code><br>";
    echo "Doğrulama: " . ($verifyResult ? "✅ Başarılı" : "❌ Başarısız") . "<br>";
    
} else {
    echo "❌ Veritabanında hiç şirket bulunamadı! Önce sirket tablosuna veri eklemelisiniz.<br>";
}

$db->close();

echo "<hr>";
echo "<p><a href='index.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🔐 Giriş Sayfasına Git</a></p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    max-width: 1000px;
    margin: 0 auto;
}
h2 {
    color: #667eea;
}
table {
    width: 100%;
    margin: 10px 0;
}
th {
    background: #667eea;
    color: white;
    padding: 10px;
}
td {
    padding: 8px;
}
tr:nth-child(even) {
    background: #f8f9fa;
}
code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>

