<?php
/**
 * Ürün Görseli Test Sayfası
 * Ürün kodu ile resimleri eşleştirip getirebiliyor muyuz test eder
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ürün Görseli Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #2c3e50; color: white; }
        .code { font-family: monospace; }
        .product-image { max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px; }
        .test-form { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .test-form input { padding: 10px; width: 300px; margin-right: 10px; }
        .test-form button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .test-form button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔍 Ürün Görseli Test Sayfası</h2>
        <hr>

        <!-- Test Formu -->
        <div class="test-form">
            <h3>Ürün Kodu ile Test Et</h3>
            <form method="GET" action="">
                <input type="text" name="stok_kodu" placeholder="Stok Kodu Girin" value="<?= htmlspecialchars($_GET['stok_kodu'] ?? '') ?>">
                <button type="submit">Test Et</button>
            </form>
        </div>

        <?php
        if (isset($_GET['stok_kodu']) && !empty($_GET['stok_kodu'])) {
            $stokKodu = trim($_GET['stok_kodu']);
            
            echo '<div class="info"><h3>Test Sonuçları - Stok Kodu: <code>' . htmlspecialchars($stokKodu) . '</code></h3>';
            
            // 1. urunler tablosundan kontrol et
            echo '<h4>1. urunler Tablosundan Kontrol</h4>';
            try {
                // Önce id kolonunu kontrol et
                $columns = DB::select("SHOW COLUMNS FROM urunler LIKE 'urun_id'");
                $idColumn = count($columns) > 0 ? 'urun_id' : 'id';
                
                $urun = DB::table('urunler')
                    ->where('stokkodu', $stokKodu)
                    ->select($idColumn . ' as id', 'stokkodu', 'stokadi', 'image')
                    ->first();
                
                if ($urun) {
                    echo '<p class="success">✅ Ürün bulundu!</p>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Stok Kodu</th><th>Stok Adı</th><th>Image Kolonu</th></tr>';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($urun->id ?? 'N/A') . '</td>';
                    echo '<td class="code">' . htmlspecialchars($urun->stokkodu ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars(substr($urun->stokadi ?? '', 0, 50)) . '</td>';
                    echo '<td class="code">' . htmlspecialchars($urun->image ?? 'NULL') . '</td>';
                    echo '</tr>';
                    echo '</table>';
                    
                    $urunImage = !empty($urun->image) ? trim((string)$urun->image) : '';
                } else {
                    echo '<p class="warning">⚠️ urunler tablosunda ürün bulunamadı!</p>';
                    $urunImage = '';
                }
            } catch (\Exception $e) {
                echo '<p class="error">❌ urunler tablosu hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
                $urunImage = '';
            }
            
            // 2. malzeme tablosundan kontrol et
            echo '<h4>2. malzeme Tablosundan Kontrol</h4>';
            try {
                $malzeme = DB::connection('mysql_remote')
                    ->table('malzeme')
                    ->where('stok_kodu', $stokKodu)
                    ->select('id', 'stok_kodu', 'image')
                    ->first();
                
                if ($malzeme) {
                    echo '<p class="success">✅ Malzeme bulundu!</p>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Stok Kodu</th><th>Image Kolonu</th></tr>';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($malzeme->id ?? 'N/A') . '</td>';
                    echo '<td class="code">' . htmlspecialchars($malzeme->stok_kodu ?? 'N/A') . '</td>';
                    echo '<td class="code">' . htmlspecialchars($malzeme->image ?? 'NULL') . '</td>';
                    echo '</tr>';
                    echo '</table>';
                    
                    $malzemeImage = !empty($malzeme->image) ? trim((string)$malzeme->image) : '';
                } else {
                    echo '<p class="warning">⚠️ malzeme tablosunda ürün bulunamadı!</p>';
                    $malzemeImage = '';
                }
            } catch (\Exception $e) {
                echo '<p class="error">❌ malzeme tablosu hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
                $malzemeImage = '';
            }
            
            // 3. Görsel URL'ini stok kodundan dinamik olarak oluştur
            echo '<h4>3. Görsel URL Oluşturma (Stok Kodundan)</h4>';
            
            if (empty($stokKodu)) {
                echo '<p class="warning">⚠️ Stok kodu boş!</p>';
                $imageUrl = asset('assets/front/assets/images/products/no-image.png');
            } else {
                // Stok kodunun ilk 2 karakterini al (klasör adı için)
                $ilkIkiKarakter = substr($stokKodu, 0, 2);
                
                echo '<p><strong>Stok Kodu:</strong> <code>' . htmlspecialchars($stokKodu) . '</code></p>';
                echo '<p><strong>İlk 2 Karakter (Klasör):</strong> <code>' . htmlspecialchars($ilkIkiKarakter) . '</code></p>';
                
                $hostBase = config('app.url', 'https://gemas.com.tr');
                // Eğer config'de yoksa, mevcut domain'i kullan
                if (empty($hostBase) || $hostBase === 'http://localhost') {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $hostBase = $protocol . '://' . $host;
                }
                
                // Base URL - Sadece domain, path olmamalı
                $hostBase = 'https://gemas.com.tr'; // Sabit olarak gemas.com.tr kullan
                
                echo '<p><strong>Host Base:</strong> <code>' . htmlspecialchars($hostBase) . '</code></p>';
                
                // Görsel URL'ini oluştur: /public/uploads/images/malzeme/{ilk_2_karakter}/{stok_kodu}.jpg
                $imagePath = '/public/uploads/images/malzeme/' . $ilkIkiKarakter . '/' . $stokKodu . '.jpg';
                $imageUrl = $hostBase . $imagePath;
                
                echo '<p><strong>Oluşturulan Görsel URL:</strong> <code>' . htmlspecialchars($imageUrl) . '</code></p>';
                
                // Görselin gerçekten var olup olmadığını kontrol et
                echo '<h4>4. Görsel Kontrolü</h4>';
                $headers = @get_headers($imageUrl);
                if ($headers && strpos($headers[0], '200') !== false) {
                    echo '<p class="success">✅ Görsel mevcut! (HTTP 200)</p>';
                } else {
                    echo '<p class="warning">⚠️ Görsel bulunamadı veya erişilemiyor!</p>';
                    echo '<p>HTTP Response: ' . ($headers ? htmlspecialchars($headers[0]) : 'Bağlantı hatası') . '</p>';
                }
            }
            
            // 5. Görseli göster
            echo '<h4>5. Görsel Önizleme</h4>';
            echo '<div style="text-align:center;">';
            echo '<img src="' . htmlspecialchars($imageUrl) . '" class="product-image" alt="Ürün Görseli" onerror="this.src=\'' . asset('assets/front/assets/images/products/no-image.png') . '\'; this.onerror=null;">';
            echo '<br><br>';
            echo '<a href="' . htmlspecialchars($imageUrl) . '" target="_blank" class="btn btn-primary">Görseli Yeni Sekmede Aç</a>';
            echo '</div>';
            
            echo '</div>';
        } else {
            // Örnek ürünler listesi göster
            echo '<div class="info"><h3>Örnek Ürünler</h3>';
            echo '<p>Aşağıdaki ürünlerden birini test edebilirsiniz:</p>';
            
            try {
                $ornekUrunler = DB::table('urunler')
                    ->whereNotNull('image')
                    ->where('image', '!=', '')
                    ->select('stokkodu', 'stokadi', 'image')
                    ->limit(10)
                    ->get();
                
                if (count($ornekUrunler) > 0) {
                    echo '<table>';
                    echo '<tr><th>Stok Kodu</th><th>Stok Adı</th><th>Image</th><th>Test</th></tr>';
                    foreach ($ornekUrunler as $urun) {
                        echo '<tr>';
                        echo '<td class="code">' . htmlspecialchars($urun->stokkodu ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars(substr($urun->stokadi ?? '', 0, 50)) . '</td>';
                        echo '<td class="code" style="font-size:11px;">' . htmlspecialchars(substr($urun->image ?? '', 0, 50)) . '</td>';
                        echo '<td><a href="?stok_kodu=' . urlencode($urun->stokkodu ?? '') . '">Test Et</a></td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                } else {
                    echo '<p class="warning">⚠️ urunler tablosunda image kolonu dolu ürün bulunamadı!</p>';
                }
            } catch (\Exception $e) {
                echo '<p class="error">❌ Örnek ürünler çekilemedi: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            
            echo '</div>';
        }
        ?>

        <div class="info" style="margin-top: 30px;">
            <h3>💡 Nasıl Çalışıyor?</h3>
            <ol>
                <li>Önce <code>urunler</code> tablosundan <code>stokkodu</code> ile ürün aranır</li>
                <li>Eğer <code>urunler</code> tablosunda <code>image</code> kolonu boşsa, <code>malzeme</code> tablosundan <code>stok_kodu</code> ile görsel aranır</li>
                <li>Görsel yolu normalize edilir (katalog kodundaki mantıkla)</li>
                <li>Normalize edilmiş URL ile görsel gösterilir</li>
            </ol>
        </div>
    </div>
</body>
</html>

