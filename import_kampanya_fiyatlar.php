<?php
// Kampanya Özel Fiyatlar - Data Import Script
// kampanyafiyat.txt dosyasını parse edip veritabanına yükler

require_once 'include/vt.php';

try {
    // PDO connection
    $pdo = new PDO(
        "mysql:host={$sql_details['host']};dbname={$sql_details['db']};charset=utf8mb4",
        $sql_details['user'],
        $sql_details['pass'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    echo "<h2>Kampanya Özel Fiyatlar - Data Import</h2>";
    
    // 1. Tablo oluştur
    echo "<h3>1. Tablo Oluşturuluyor...</h3>";
    $sqlFile = file_get_contents(__DIR__ . '/sql/20260122_kampanya_ozel_fiyatlar.sql');
    $pdo->exec($sqlFile);
    echo "<p style='color: green;'>✓ Tablo oluşturuldu/zaten mevcut</p>";
    
    // 2. Mevcut verileri temizle
    echo "<h3>2. Mevcut Veriler Temizleniyor...</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM kampanya_ozel_fiyatlar");
    $oldCount = $stmt->fetchColumn();
    echo "<p>Mevcut kayıt sayısı: {$oldCount}</p>";
    
    $pdo->exec("TRUNCATE TABLE kampanya_ozel_fiyatlar");
    echo "<p style='color: orange;'>✓ Tablo temizlendi</p>";
    
    // 3. kampanyafiyat.txt dosyasını oku
    echo "<h3>3. Kampanyafiyat.txt Parse Ediliyor...</h3>";
    $txtFile = __DIR__ . '/kampanyafiyat.txt';
    
    if (!file_exists($txtFile)) {
        throw new Exception("kampanyafiyat.txt dosyası bulunamadı!");
    }
    
    $lines = file($txtFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    $currentCategory = '';
    $products = [];
    $skipped = 0;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Tab ile ayrılmış satırları parse et
        $parts = explode("\t", $line);
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts); // Boş elemanları temizle
        
        // Başlık satırlarını atla (KOD, AÇIKLAMA gibi)
        if (stripos($line, 'KOD') !== false && stripos($line, 'AÇIKLAMA') !== false) {
            continue;
        }
        
        // Kategori başlığı mı kontrol et (örn: "FİLTRELER", "POMPALAR")
        if (count($parts) === 1 && mb_strlen($parts[0]) > 3 && 
            !preg_match('/^[0-9]/', $parts[0])) {
            $currentCategory = $parts[0];
            echo "<p><strong>Kategori: {$currentCategory}</strong></p>";
            continue;
        }
        
        // Ürün satırı mı kontrol et (en az 3 parça olmalı: kod, açıklama, fiyat)
        if (count($parts) >= 3) {
            $code = $parts[0];
            $description = $parts[1];
            $priceStr = $parts[2];
            
            // Stok kodu geçerli mi kontrol et (rakam veya harf ile başlamalı)
            if (!preg_match('/^[A-Z0-9]/i', $code)) {
                $skipped++;
                continue;
            }
            
            // Fiyat parse et (€ işareti ve boşlukları temizle)
            $priceStr = str_replace(['€', ' ', ','], ['', '', '.'], $priceStr);
            $price = floatval($priceStr);
            
            if ($price <= 0) {
                $skipped++;
                echo "<p style='color: orange;'>⚠ Atlandi: {$code} - Geçersiz fiyat: {$parts[2]}</p>";
                continue;
            }
            
            $products[] = [
                'code' => $code,
                'name' => $description,
                'price' => $price,
                'category' => $currentCategory
            ];
        }
    }
    
    echo "<p style='color: green;'>✓ {count($products)} ürün parse edildi</p>";
    echo "<p style='color: orange;'>⚠ {$skipped} satır atlandı</p>";
    
    // 4. Veritabanına kaydet
    echo "<h3>4. Veritabanına Kaydediliyor...</h3>";
    
    $stmt = $pdo->prepare("
        INSERT INTO kampanya_ozel_fiyatlar 
        (stok_kodu, stok_adi, yurtici_fiyat, ihracat_fiyat, ozel_fiyat, kategori, logicalref) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        ozel_fiyat = VALUES(ozel_fiyat),
        kategori = VALUES(kategori),
        yurtici_fiyat = VALUES(yurtici_fiyat),
        ihracat_fiyat = VALUES(ihracat_fiyat),
        logicalref = VALUES(logicalref)
    ");
    
    $inserted = 0;
    $errors = 0;
    $pricesFetched = 0;
    
    foreach ($products as $product) {
        try {
            // Önce urunler tablosundan bu ürünün fiyatlarını çek
            $stmtPrice = $pdo->prepare("
                SELECT fiyat, export_fiyat, logicalref, stokadi
                FROM urunler 
                WHERE stokkodu = ? OR stokkodu = ?
                LIMIT 1
            ");
            $stmtPrice->execute([$product['code'], ltrim($product['code'], '0')]);
            $priceData = $stmtPrice->fetch(PDO::FETCH_ASSOC);
            
            $yurtici_fiyat = 0.00;
            $ihracat_fiyat = 0.00;
            $logicalref = null;
            $stok_adi = $product['name'];
            
            if ($priceData) {
                $yurtici_fiyat = floatval($priceData['fiyat']);
                $ihracat_fiyat = floatval($priceData['export_fiyat']);
                $logicalref = intval($priceData['logicalref']);
                $stok_adi = $priceData['stokadi'] ?: $product['name'];
                $pricesFetched++;
            }
            
            $stmt->execute([
                $product['code'],
                $stok_adi,
                $yurtici_fiyat,
                $ihracat_fiyat,
                $product['price'],
                $product['category'],
                $logicalref
            ]);
            $inserted++;
        } catch (PDOException $e) {
            $errors++;
            echo "<p style='color: red;'>✗ Hata ({$product['code']}): " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p style='color: green;'><strong>✓ {$inserted} ürün başarıyla eklendi!</strong></p>";
    echo "<p style='color: blue;'>ℹ {$pricesFetched} ürün için Logo fiyatları bulundu ve eklendi</p>";
    if ($errors > 0) {
        echo "<p style='color: red;'>✗ {$errors} kayıt eklenemedi</p>";
    }
    
    // 5. Doğrulama
    echo "<h3>5. Doğrulama</h3>";
    $stmt = $pdo->query("
        SELECT kategori, COUNT(*) as count 
        FROM kampanya_ozel_fiyatlar 
        GROUP BY kategori 
        ORDER BY kategori
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Kategori</th><th>Ürün Sayısı</th></tr>";
    $total = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['kategori']}</td>";
        echo "<td>{$row['count']}</td>";
        echo "</tr>";
        $total += $row['count'];
    }
    echo "<tr style='background: #d4edda; font-weight: bold;'>";
    echo "<td>TOPLAM</td><td>{$total}</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<br><p><a href='kampanyalar.php' class='btn btn-primary'>Kampanyalar Sayfasına Git →</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>HATA:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
h3 { color: #555; margin-top: 20px; }
table { margin: 10px 0; }
th { background: #007bff; color: white; padding: 8px; }
td { padding: 6px 10px; }
.btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
.btn:hover { background: #0056b3; }
</style>
