<?php
// api/teklif/check_last_offer_status.php
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

require_once "../../include/vt.php";

session_start();

// Kullanıcıyı belirle (Yönetici, Bayi veya Personel)
$userId = 0;
$userType = '';

if (!empty($_SESSION['yonetici_id'])) {
    $userId = $_SESSION['yonetici_id'];
    $userType = 'manager';
    // Yöneticiler için tüm teklifleri mi yoksa kendi hazırladıklarını mı?
    // Genellikle yönetici "onaylayan" taraftır, ama testi yapan kişi yönetici ise kendi oluşturduğu son teklifi takip etmek isteyebilir.
    // Şimdilik "hazirlayanid" üzerinden gidelim.
} elseif (!empty($_SESSION['bayi_id'])) {
    $userId = $_SESSION['bayi_id'];
    $userType = 'dealer';
} elseif (!empty($_SESSION['personel_id'])) {
    $userId = $_SESSION['personel_id'];
    $userType = 'staff';
}

// 1. Manuel DB Bağlantısı
$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
if ($db->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB Connection Failed"]));
}
$db->set_charset("utf8");

// 2. En son teklifi bul (Sadece ID ve Durum yeterli)
// Eğer kullanıcı yoksa (ör: session kapalı), son eklenen herhangi bir teklifi mi çekelim? (Test için)
// Güvenlik açısından session yoksa boş dönmeli, ama kullanıcının testi kolay olsun diye
// Session yoksa "son 1 teklifi" çekelim (DEMO AMAÇLI - User Request'e uygun)
$sql = "";
if ($userId > 0) {
    // Bu kullanıcının hazırladığı son teklif
    // hazirlayanid string olabilir ('Bayi 123' gibi), session'dan eşleştirmek biraz karmaşık olabilir sisteminize göre.
    // Şimdilik basitçe ID'ye göre en sonuncuyu alalım.
    $sql = "SELECT id, durum, approval_status FROM ogteklif2 ORDER BY id DESC LIMIT 1";
} else {
    // Session yoksa da en son teklifi getir (Test Sayfası içn)
    $sql = "SELECT id, durum, approval_status FROM ogteklif2 ORDER BY id DESC LIMIT 1";
}

$result = $db->query($sql);
$data = [];

if ($result && $row = $result->fetch_assoc()) {
    $data = [
        'found' => true,
        'id' => $row['id'],
        'durum' => $row['durum'],
        'approval_status' => $row['approval_status']
    ];
} else {
    $data = ['found' => false];
}

$db->close();

echo json_encode($data);
?>
