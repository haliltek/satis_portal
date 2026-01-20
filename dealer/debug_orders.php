<?php
// Bayi Siparişlerini Debug Et
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giriş kontrolü
if (!isset($_SESSION['yonetici_id']) || ($_SESSION['user_type'] ?? '') !== 'Bayi') {
    echo "❌ Lütfen bayi panelinden giriş yapın!<br>";
    echo "<a href='index.php'>Giriş Sayfası</a>";
    exit;
}

include "../include/vt.php";

$db = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
$db->set_charset('utf8mb4');

$cariCode = $_SESSION['dealer_cari_code'] ?? '';

echo "<h2>🔍 Sipariş Debug - Bayi: $cariCode</h2>";
echo "<hr>";

// Sipariş #49 ve #50'yi kontrol et
echo "<h3>📦 Sipariş #49 ve #50 Detayları:</h3>";

$orderIds = [49, 50];

foreach ($orderIds as $id) {
    $query = "SELECT * FROM ogteklif2 WHERE id = $id";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        echo "<div style='background: " . ($order['tur'] === 'bayi_siparis' ? '#d4edda' : '#fff3cd') . "; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid " . ($order['tur'] === 'bayi_siparis' ? '#28a745' : '#ffc107') . ";'>";
        echo "<h4>Sipariş #$id</h4>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; background: white;'>";
        
        // Kritik alanlar
        $kritikAlanlar = [
            'id' => 'ID',
            'sirket_arp_code' => 'Cari Kod',
            'musteriadi' => 'Müşteri Adı',
            'tekliftarihi' => '⚠️ Teklif Tarihi (KRITIK)',
            'durum' => 'Durum',
            'tur' => '⚠️ Tür (KRITIK)',
            'geneltoplam' => 'Genel Toplam',
            'hazirlayanid' => 'Hazırlayan ID'
        ];
        
        foreach ($kritikAlanlar as $key => $label) {
            $value = $order[$key] ?? 'NULL';
            $isNull = ($value === null || $value === 'NULL' || $value === '');
            $style = '';
            
            if ($key === 'tekliftarihi' || $key === 'tur') {
                if ($isNull) {
                    $style = "background: #f8d7da; color: #721c24; font-weight: bold;";
                    $value = "❌ NULL (SORUN!)";
                } else if ($key === 'tur' && $value !== 'bayi_siparis') {
                    $style = "background: #fff3cd; color: #856404; font-weight: bold;";
                    $value = "⚠️ $value (Bayi değil!)";
                } else {
                    $style = "background: #d4edda; color: #155724; font-weight: bold;";
                    $value = "✅ $value";
                }
            }
            
            echo "<tr>";
            echo "<td style='font-weight: bold; width: 200px;'>$label</td>";
            echo "<td style='$style'>$value</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "❌ Sipariş #$id bulunamadı!";
        echo "</div>";
    }
}

echo "<hr>";

// Admin paneli koşulunu test et
echo "<h3>🔧 Admin Paneli Koşulu Testi:</h3>";
echo "<p>Admin paneli şu SQL koşulunu kullanır: <code>WHERE t.tekliftarihi IS NOT NULL</code></p>";

$adminQuery = "SELECT id, tekliftarihi, tur, sirket_arp_code, durum, geneltoplam 
               FROM ogteklif2 
               WHERE id IN (49, 50) AND tekliftarihi IS NOT NULL";

$adminResult = $db->query($adminQuery);

if ($adminResult && $adminResult->num_rows > 0) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>";
    echo "✅ <strong>Admin paneli bu siparişleri görebilir:</strong><br><br>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; background: white;'>";
    echo "<tr><th>ID</th><th>Tarih</th><th>Tür</th><th>Durum</th></tr>";
    while ($row = $adminResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>#{$row['id']}</td>";
        echo "<td>{$row['tekliftarihi']}</td>";
        echo "<td>{$row['tur']}</td>";
        echo "<td>{$row['durum']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
    echo "❌ <strong>SORUN TESPİT EDİLDİ!</strong><br><br>";
    echo "Bu siparişler admin panelinde GÖRÜNMÜYOR çünkü:<br>";
    echo "• <code>tekliftarihi</code> NULL veya<br>";
    echo "• Başka bir koşul tutmuyor<br><br>";
    echo "<strong>Çözüm:</strong> Aşağıdaki düzeltme butonuna tıklayın.";
    echo "</div>";
}

echo "<hr>";

// Düzeltme formu
echo "<h3>🔨 Otomatik Düzeltme:</h3>";
echo "<form method='post' style='background: #e7f3ff; padding: 20px; border-radius: 8px;'>";
echo "<input type='hidden' name='fix_orders' value='1'>";
echo "<p><strong>Bu işlem şunları yapacak:</strong></p>";
echo "<ul>";
echo "<li>Sipariş #49 ve #50'nin <code>tekliftarihi</code> NULL ise şu anki tarihi ekleyecek</li>";
echo "<li>Sipariş #49 ve #50'nin <code>tur</code> kolonunu 'bayi_siparis' yapacak</li>";
echo "<li>Admin panelinde görünür hale getirecek</li>";
echo "</ul>";
echo "<button type='submit' style='background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>";
echo "🔧 SİPARİŞLERİ DÜZELT VE ADMIN PANELİNDE GÖSTER";
echo "</button>";
echo "</form>";

// Düzeltme işlemi
if (isset($_POST['fix_orders'])) {
    echo "<hr>";
    echo "<h3>⚙️ Düzeltme İşlemi:</h3>";
    
    $fixed = 0;
    foreach ($orderIds as $id) {
        // Önce mevcut durumu kontrol et
        $checkQuery = "SELECT tekliftarihi, tur FROM ogteklif2 WHERE id = $id";
        $checkResult = $db->query($checkQuery);
        $current = $checkResult->fetch_assoc();
        
        $updates = [];
        
        if (empty($current['tekliftarihi'])) {
            $updates[] = "tekliftarihi = NOW()";
        }
        
        if ($current['tur'] !== 'bayi_siparis') {
            $updates[] = "tur = 'bayi_siparis'";
        }
        
        if (!empty($updates)) {
            $updateQuery = "UPDATE ogteklif2 SET " . implode(', ', $updates) . " WHERE id = $id";
            if ($db->query($updateQuery)) {
                echo "✅ Sipariş #$id düzeltildi<br>";
                $fixed++;
            } else {
                echo "❌ Sipariş #$id düzeltilemedi: " . $db->error . "<br>";
            }
        } else {
            echo "ℹ️ Sipariş #$id zaten düzgün<br>";
        }
    }
    
    if ($fixed > 0) {
        echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-top: 15px;'>";
        echo "✅ <strong>$fixed sipariş başarıyla düzeltildi!</strong><br><br>";
        echo "Şimdi admin panelini kontrol edin:<br>";
        echo "<a href='../teklifsiparisler.php' target='_blank' style='background: #2c3e50; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 10px;'>";
        echo "📊 Admin Panelini Aç";
        echo "</a>";
        echo "</div>";
        
        echo "<script>setTimeout(function(){ location.href = location.href.split('?')[0]; }, 3000);</script>";
    }
}

$db->close();

echo "<hr>";
echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='dashboard.php' style='background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>🏠 Dashboard</a> ";
echo "<a href='orders.php' style='background: #27ae60; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>📦 Siparişlerim</a> ";
echo "<a href='../teklifsiparisler.php' target='_blank' style='background: #e74c3c; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin: 5px;'>👨‍💼 Admin Panel</a>";
echo "</div>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
    background: #f5f7fa;
    max-width: 1200px;
    margin: 0 auto;
}
h2, h3, h4 {
    color: #2c3e50;
}
code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
</style>

