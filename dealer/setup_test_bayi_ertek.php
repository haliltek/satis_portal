<?php
/**
 * test_bayi Kullanıcısını Ertek Bayisi (120.01.E04) Olarak Yapılandır
 */

include "../include/vt.php";

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8mb4');

if ($db->connect_error) {
    die("Veritabanı bağlantı hatası: " . $db->connect_error);
}

$testUsername = 'test_bayi';
$testPassword = 'test123';
$testEmail = 'test_bayi@gemas.com';
$ertekCariCode = '120.01.E04';

echo "<h2>🔧 test_bayi Hesabını Ertek Bayisi Olarak Yapılandır</h2>";
echo "<hr>";

// Ertek bayisini bul
$ertekStmt = $db->prepare("SELECT sirket_id, s_adi, s_arp_code FROM sirket WHERE s_arp_code = ?");
$ertekStmt->bind_param('s', $ertekCariCode);
$ertekStmt->execute();
$ertekResult = $ertekStmt->get_result();
$ertekCompany = $ertekResult->fetch_assoc();
$ertekStmt->close();

if (!$ertekCompany) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>Hata:</strong> Ertek bayisi (120.01.E04) bulunamadı!";
    echo "</div>";
    exit;
}

echo "<div style='background: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>📋 Ertek Bayisi Bilgileri:</h3>";
echo "<ul>";
echo "<li><strong>Şirket ID:</strong> " . htmlspecialchars($ertekCompany['sirket_id']) . "</li>";
echo "<li><strong>Şirket Adı:</strong> " . htmlspecialchars($ertekCompany['s_adi']) . "</li>";
echo "<li><strong>Cari Kodu:</strong> " . htmlspecialchars($ertekCompany['s_arp_code']) . "</li>";
echo "</ul>";
echo "</div>";

$ertekCompanyId = (int)$ertekCompany['sirket_id'];

// test_bayi kullanıcısını kontrol et
$checkUser = $db->prepare("SELECT id, username, email, company_id, cari_code FROM b2b_users WHERE username = ?");
$checkUser->bind_param('s', $testUsername);
$checkUser->execute();
$userResult = $checkUser->get_result();
$existingUser = $userResult->fetch_assoc();
$checkUser->close();

if ($existingUser) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
    echo "⚠️ '<strong>$testUsername</strong>' kullanıcısı zaten mevcut. Ertek bayisi ile eşleştiriliyor...<br>";
    echo "</div>";
    
    $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
    $updateStmt = $db->prepare("UPDATE b2b_users SET password = ?, email = ?, company_id = ?, cari_code = ?, status = 1, role = 'Bayi' WHERE username = ?");
    $updateStmt->bind_param('ssiss', $hashedPassword, $testEmail, $ertekCompanyId, $ertekCariCode, $testUsername);
    
    if ($updateStmt->execute()) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Başarılı!</strong> test_bayi kullanıcısı Ertek bayisi ile eşleştirildi!<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ <strong>Hata:</strong> Güncelleme hatası: " . $updateStmt->error . "<br>";
        echo "</div>";
    }
    $updateStmt->close();
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
    echo "➕ Yeni kullanıcı oluşturuluyor...<br>";
    echo "</div>";
    
    $hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
    $insertStmt = $db->prepare("INSERT INTO b2b_users (company_id, cari_code, username, email, password, status, role) VALUES (?, ?, ?, ?, ?, 1, 'Bayi')");
    $insertStmt->bind_param('issss', $ertekCompanyId, $ertekCariCode, $testUsername, $testEmail, $hashedPassword);
    
    if ($insertStmt->execute()) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Başarılı!</strong> test_bayi kullanıcısı Ertek bayisi ile oluşturuldu!<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ <strong>Hata:</strong> Oluşturma hatası: " . $insertStmt->error . "<br>";
        echo "</div>";
    }
    $insertStmt->close();
}

echo "<br>";
echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
echo "<h3>✅ Giriş Bilgileri:</h3>";
echo "<ul>";
echo "<li><strong>Kullanıcı Adı:</strong> <code>$testUsername</code></li>";
echo "<li><strong>E-Posta:</strong> <code>$testEmail</code></li>";
echo "<li><strong>Şifre:</strong> <code>$testPassword</code></li>";
echo "<li><strong>Şirket:</strong> " . htmlspecialchars($ertekCompany['s_adi']) . " (120.01.E04)</li>";
echo "</ul>";
echo "<p><a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🔐 Giriş Sayfasına Git</a></p>";
echo "</div>";

$db->close();
?>







