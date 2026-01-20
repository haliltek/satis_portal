<?php
/**
 * Ürün Test Scripti
 * http://localhost/b2b-gemas-project-main/bayi/public/test_products.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "<h2>🔍 Ürün Testi</h2>";
echo "<hr>";

// 1. Toplam ürün sayısı
$totalProducts = DB::table('urunler')->count();
echo "<h3>1. Toplam Ürün Sayısı:</h3>";
echo "Toplam: " . $totalProducts . "<br><br>";

// 2. Fiyatı olan ürünler
$productsWithPrice = DB::table('urunler')
    ->whereNotNull('fiyat')
    ->where('fiyat', '!=', '')
    ->where('fiyat', '!=', '0')
    ->count();
echo "<h3>2. Fiyatı Olan Ürünler (Yurtiçi):</h3>";
echo "Sayı: " . $productsWithPrice . "<br><br>";

// 3. İlk 5 ürünü göster
echo "<h3>3. İlk 5 Yurtiçi Ürün:</h3>";
$sampleProducts = DB::table('urunler')
    ->whereNotNull('fiyat')
    ->where('fiyat', '!=', '')
    ->where('fiyat', '!=', '0')
    ->select('urun_id', 'stokkodu', 'stokadi', 'kat1', 'marka', 'fiyat', 'miktar')
    ->limit(5)
    ->get();

if ($sampleProducts->count() > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Stok Kodu</th><th>Stok Adı</th><th>Kategori</th><th>Marka</th><th>Fiyat</th><th>Stok</th></tr>";
    foreach ($sampleProducts as $product) {
        echo "<tr>";
        echo "<td>" . ($product->urun_id ?? 'N/A') . "</td>";
        echo "<td>" . ($product->stokkodu ?? 'N/A') . "</td>";
        echo "<td>" . substr($product->stokadi ?? 'N/A', 0, 50) . "...</td>";
        echo "<td>" . ($product->kat1 ?? 'N/A') . "</td>";
        echo "<td>" . ($product->marka ?? 'N/A') . "</td>";
        echo "<td>" . number_format(floatval($product->fiyat ?? 0), 2, ',', '.') . "₺</td>";
        echo "<td>" . ($product->miktar ?? 0) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Hiç ürün bulunamadı!<br>";
}

// 4. Tablo yapısını kontrol et
echo "<h3>4. Tablo Yapısı:</h3>";
try {
    $columns = DB::select("SHOW COLUMNS FROM urunler");
    echo "<pre>";
    foreach ($columns as $column) {
        echo $column->Field . " (" . $column->Type . ")<br>";
    }
    echo "</pre>";
} catch (\Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "<br>";
}

// 5. Fiyat kolonu değerlerini kontrol et
echo "<h3>5. Fiyat Kolonu Örnekleri:</h3>";
$priceSamples = DB::table('urunler')
    ->select('urun_id', 'stokkodu', 'fiyat')
    ->whereNotNull('fiyat')
    ->limit(10)
    ->get();

if ($priceSamples->count() > 0) {
    echo "<pre>";
    foreach ($priceSamples as $sample) {
        echo "ID: " . $sample->urun_id . " | Kod: " . $sample->stokkodu . " | Fiyat: " . $sample->fiyat . " | Tip: " . gettype($sample->fiyat) . "<br>";
    }
    echo "</pre>";
} else {
    echo "❌ Fiyatı olan ürün bulunamadı!<br>";
}

