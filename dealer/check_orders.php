<?php
// Bayi Siparişlerini Kontrol Et
include "../include/vt.php";

echo "<h2>🔍 Bayi Siparişleri Kontrol Paneli</h2>";
echo "<hr>";

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8mb4');

if ($db->connect_error) {
    die("Veritabanı bağlantı hatası: " . $db->connect_error);
}

echo "✅ Veritabanı bağlantısı başarılı<br><br>";

// Tüm ogteklif2 kayıtlarını kontrol et
echo "<h3>📊 ogteklif2 Tablosu İstatistikleri:</h3>";

$totalOrders = $db->query("SELECT COUNT(*) as total FROM ogteklif2")->fetch_assoc()['total'];
echo "Toplam Sipariş: <strong>$totalOrders</strong><br>";

$bayiOrders = $db->query("SELECT COUNT(*) as total FROM ogteklif2 WHERE tur = 'bayi_siparis'")->fetch_assoc()['total'];
echo "Bayi Siparişi: <strong style='color: blue;'>$bayiOrders</strong><br>";

$withDate = $db->query("SELECT COUNT(*) as total FROM ogteklif2 WHERE tekliftarihi IS NOT NULL")->fetch_assoc()['total'];
echo "Tarihli Sipariş: <strong>$withDate</strong><br><br>";

// Son 10 bayi siparişini göster
echo "<h3>📦 Son Bayi Siparişleri:</h3>";

$query = "SELECT id, sirket_arp_code, musteriadi, tekliftarihi, durum, tur, geneltoplam, hazirlayanid 
          FROM ogteklif2 
          WHERE tur = 'bayi_siparis' 
          ORDER BY id DESC 
          LIMIT 10";

$result = $db->query($query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #2c3e50; color: white;'>";
    echo "<th>ID</th><th>Cari Kodu</th><th>Müşteri</th><th>Tarih</th><th>Durum</th><th>Tur</th><th>Toplam</th><th>Hazırlayan</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $tarih = $row['tekliftarihi'] ? date('d.m.Y H:i', strtotime($row['tekliftarihi'])) : '❌ NULL';
        $turStyle = $row['tur'] === 'bayi_siparis' ? 'background: #3498db; color: white; padding: 5px; border-radius: 4px;' : '';
        
        echo "<tr>";
        echo "<td><strong>#{$row['id']}</strong></td>";
        echo "<td>{$row['sirket_arp_code']}</td>";
        echo "<td>{$row['musteriadi']}</td>";
        echo "<td>$tarih</td>";
        echo "<td>{$row['durum']}</td>";
        echo "<td style='$turStyle'>{$row['tur']}</td>";
        echo "<td>₺" . number_format($row['geneltoplam'], 2) . "</td>";
        echo "<td>{$row['hazirlayanid']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
    echo "❌ <strong>Hiç bayi siparişi bulunamadı!</strong><br>";
    echo "Bayi panelinden sipariş oluşturun.";
    echo "</div><br>";
}

// Tüm siparişleri göster (son 5)
echo "<h3>📋 Tüm Siparişler (Son 5):</h3>";

$allQuery = "SELECT id, sirket_arp_code, musteriadi, tekliftarihi, durum, tur, geneltoplam 
             FROM ogteklif2 
             ORDER BY id DESC 
             LIMIT 5";

$allResult = $db->query($allQuery);

if ($allResult && $allResult->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #34495e; color: white;'>";
    echo "<th>ID</th><th>Cari Kodu</th><th>Müşteri</th><th>Tarih</th><th>Durum</th><th>Tur</th><th>Toplam</th></tr>";
    
    while ($row = $allResult->fetch_assoc()) {
        $tarih = $row['tekliftarihi'] ? date('d.m.Y H:i', strtotime($row['tekliftarihi'])) : '❌ NULL';
        $isBayi = $row['tur'] === 'bayi_siparis';
        $rowStyle = $isBayi ? 'background: #e8f4f8;' : '';
        
        echo "<tr style='$rowStyle'>";
        echo "<td><strong>#{$row['id']}</strong></td>";
        echo "<td>{$row['sirket_arp_code']}</td>";
        echo "<td>{$row['musteriadi']}" . ($isBayi ? " 🛒" : "") . "</td>";
        echo "<td>$tarih</td>";
        echo "<td>{$row['durum']}</td>";
        echo "<td>{$row['tur']}</td>";
        echo "<td>₺" . number_format($row['geneltoplam'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
}

// Sorun tespiti
echo "<hr>";
echo "<h3>🔧 Sorun Tespiti:</h3>";

$issues = [];

// Kontrol 1: tekliftarihi NULL olan kayıtlar
$nullDateQuery = "SELECT COUNT(*) as total FROM ogteklif2 WHERE tur = 'bayi_siparis' AND tekliftarihi IS NULL";
$nullDateCount = $db->query($nullDateQuery)->fetch_assoc()['total'];
if ($nullDateCount > 0) {
    $issues[] = "⚠️ <strong>$nullDateCount</strong> bayi siparişinin tarihi NULL (admin panelinde görünmez!)";
}

// Kontrol 2: tur kolonu boş olanlar
$emptyTurQuery = "SELECT COUNT(*) as total FROM ogteklif2 WHERE (tur IS NULL OR tur = '') AND hazirlayanid IN (SELECT id FROM b2b_users)";
$emptyTurCount = $db->query($emptyTurQuery)->fetch_assoc()['total'];
if ($emptyTurCount > 0) {
    $issues[] = "⚠️ <strong>$emptyTurCount</strong> sipariş 'tur' kolonu boş";
}

if (empty($issues)) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>Tüm kontroller başarılı!</strong><br>";
    echo "Bayi siparişleri düzgün kaydediliyor.";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
    foreach ($issues as $issue) {
        echo "$issue<br>";
    }
    echo "</div>";
}

echo "<br>";

// Düzeltme önerileri
if ($nullDateCount > 0) {
    echo "<h3>🔨 Otomatik Düzeltme:</h3>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='fix_dates' value='1'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "🔧 Tarihi NULL Olan Siparişleri Düzelt";
    echo "</button>";
    echo "<p style='color: #666; font-size: 12px;'>Bu işlem, tekliftarihi NULL olan bayi siparişlerine şu anki tarihi ekleyecek.</p>";
    echo "</form>";
}

// Düzeltme işlemi
if (isset($_POST['fix_dates'])) {
    $fixQuery = "UPDATE ogteklif2 SET tekliftarihi = NOW() WHERE tur = 'bayi_siparis' AND tekliftarihi IS NULL";
    if ($db->query($fixQuery)) {
        $affected = $db->affected_rows;
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-top: 15px;'>";
        echo "✅ <strong>$affected</strong> sipariş düzeltildi! Sayfayı yenileyin.";
        echo "</div>";
        echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
    }
}

$db->close();

echo "<hr>";
echo "<div style='text-align: center; margin-top: 20px;'>";
echo "<a href='../teklifsiparisler.php' style='background: #2c3e50; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>📊 Admin Paneli</a> ";
echo "<a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>🔐 Bayi Girişi</a> ";
echo "<a href='dashboard.php' style='background: #27ae60; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>📦 Bayi Dashboard</a>";
echo "</div>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
    background: #f5f7fa;
}
h2, h3 {
    color: #2c3e50;
}
table {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
th {
    text-align: left;
}
</style>

