<?php
/**
 * Kampanyalı Ürünler Test Sayfası
 * Logo veritabanından ürünleri çekip test eder
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
    <title>Kampanyalı Ürünler Test</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h2>🔍 Kampanyalı Ürünler Test Sayfası</h2>
        <hr>

        <?php
        try {
            // LogoExtreService kullanarak direkt PDO bağlantısı oluştur
            $logoService = app(\App\Services\LogoExtreService::class);
            $pdo = $logoService->createDirectPdoConnection('logo_gemas');
            
            if (!$pdo) {
                throw new \Exception('Logo veritabanı bağlantısı kurulamadı');
            }
            
            // 1. Logo veritabanı bağlantısını test et
            echo '<div class="info"><h3>1. Logo Veritabanı Bağlantısı</h3>';
            try {
                $testStmt = $pdo->query("SELECT TOP 1 LOGICALREF, CODE, NAME FROM LG_526_ITEMS");
                $testConnection = $testStmt->fetchAll(\PDO::FETCH_OBJ);
                echo '<p>✅ GEMAS Logo veritabanına bağlanıldı!</p>';
                echo '<p>Test sorgusu sonucu: ' . count($testConnection) . ' kayıt</p>';
                if (count($testConnection) > 0) {
                    echo '<p>Örnek ürün: CODE=' . htmlspecialchars($testConnection[0]->CODE ?? 'N/A') . ', NAME=' . htmlspecialchars(substr($testConnection[0]->NAME ?? '', 0, 50)) . '</p>';
                }
            } catch (\Exception $e) {
                echo '<p class="error">❌ Bağlantı hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            echo '</div>';

            // 2. Tüm açıklama sütunlarını kontrol et
            echo '<div class="info"><h3>2. Tüm Açıklama Sütunlarını Kontrol Et</h3>';
            try {
                // Önce tablo yapısını kontrol et
                $columnsStmt = $pdo->query("
                    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = 'LG_526_ITEMS'
                    AND (COLUMN_NAME LIKE '%NAME%' OR COLUMN_NAME LIKE '%AÇIKLAMA%' OR COLUMN_NAME LIKE '%DESC%' OR COLUMN_NAME LIKE '%EXPLAIN%')
                    ORDER BY COLUMN_NAME
                ");
                $columns = $columnsStmt->fetchAll(\PDO::FETCH_OBJ);
                
                echo '<p><strong>LG_526_ITEMS tablosundaki açıklama sütunları:</strong></p>';
                echo '<table>';
                echo '<tr><th>Sütun Adı</th><th>Veri Tipi</th><th>Maksimum Uzunluk</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr>';
                    echo '<td class="code">' . htmlspecialchars($col->COLUMN_NAME ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($col->DATA_TYPE ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($col->CHARACTER_MAXIMUM_LENGTH ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // Şimdi tüm açıklama sütunlarında "Outlet" ara
                echo '<p><strong>"Outlet" içeren değerleri tüm sütunlarda ara:</strong></p>';
                
                $searchColumns = ['NAME', 'NAME2', 'NAME3', 'NAME4', 'AÇIKLAMA', 'AÇIKLAMA2', 'DESCR', 'EXPLAIN', 'EXPLAIN2'];
                $foundInColumns = [];
                
                foreach ($searchColumns as $colName) {
                    try {
                        // Önce sütunun var olup olmadığını kontrol et
                        $colCheck = $pdo->query("
                            SELECT COUNT(*) as col_exists
                            FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_NAME = 'LG_526_ITEMS' AND COLUMN_NAME = '$colName'
                        ");
                        $colExists = $colCheck->fetch(\PDO::FETCH_OBJ);
                        
                        if ($colExists && $colExists->col_exists > 0) {
                            $outletStmt = $pdo->prepare("
                                SELECT TOP 10 LOGICALREF, CODE, $colName as COL_VALUE
                                FROM LG_526_ITEMS 
                                WHERE $colName IS NOT NULL 
                                AND LEN(LTRIM(RTRIM($colName))) > 0
                                AND (UPPER($colName) LIKE UPPER(?) OR UPPER($colName) LIKE UPPER(?))
                                ORDER BY CODE
                            ");
                            $outletStmt->execute(['%Outlet%', '%Ürünüdür%']);
                            $outletTest = $outletStmt->fetchAll(\PDO::FETCH_OBJ);
                            
                            if (count($outletTest) > 0) {
                                $foundInColumns[$colName] = $outletTest;
                                echo '<p class="success">✅ <strong>' . $colName . '</strong> sütununda ' . count($outletTest) . ' ürün bulundu!</p>';
                                
                                echo '<table>';
                                echo '<tr><th>LOGICALREF</th><th>CODE</th><th>' . $colName . '</th></tr>';
                                foreach ($outletTest as $item) {
                                    echo '<tr>';
                                    echo '<td class="code">' . htmlspecialchars($item->LOGICALREF ?? 'N/A') . '</td>';
                                    echo '<td class="code">' . htmlspecialchars($item->CODE ?? 'N/A') . '</td>';
                                    echo '<td><pre style="margin:0;">' . htmlspecialchars($item->COL_VALUE ?? '') . '</pre></td>';
                                    echo '</tr>';
                                }
                                echo '</table>';
                            }
                        }
                    } catch (\Exception $colE) {
                        // Sütun yoksa veya hata varsa sessizce geç
                    }
                }
                
                if (empty($foundInColumns)) {
                    echo '<p class="warning">⚠️ Hiçbir sütunda "Outlet" içeren değer bulunamadı!</p>';
                }
                
            } catch (\Exception $e) {
                echo '<p class="error">❌ Açıklama sütunları kontrolü hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            echo '</div>';
            
            // 3. "Outlet Ürünüdür" araması - Bulunan sütunlarda ara
            echo '<div class="info"><h3>3. "Outlet Ürünüdür" Araması</h3>';
            
            // Önce hangi sütunda bulunduğunu belirle
            $targetColumn = null;
            $foundItems = [];
            
            try {
                // Tüm olası sütunları dene
                $possibleColumns = ['NAME2', 'NAME3', 'NAME4', 'AÇIKLAMA', 'AÇIKLAMA2', 'DESCR', 'EXPLAIN', 'EXPLAIN2'];
                
                foreach ($possibleColumns as $colName) {
                    try {
                        // Sütunun var olup olmadığını kontrol et
                        $colCheck = $pdo->query("
                            SELECT COUNT(*) as col_exists
                            FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_NAME = 'LG_526_ITEMS' AND COLUMN_NAME = '$colName'
                        ");
                        $colExists = $colCheck->fetch(\PDO::FETCH_OBJ);
                        
                        if ($colExists && $colExists->col_exists > 0) {
                            // CHARINDEX ile ara
                            $stmt = $pdo->query("
                                SELECT TOP 50 LOGICALREF, CODE, $colName as COL_VALUE
                                FROM LG_526_ITEMS 
                                WHERE $colName IS NOT NULL 
                                AND LEN(LTRIM(RTRIM($colName))) > 0
                                AND (CHARINDEX('Outlet', UPPER($colName)) > 0 OR CHARINDEX('Ürünüdür', $colName) > 0)
                                ORDER BY CODE
                            ");
                            $items = $stmt->fetchAll(\PDO::FETCH_OBJ);
                            
                            if (count($items) > 0) {
                                $targetColumn = $colName;
                                $foundItems = $items;
                                echo '<p class="success">✅ <strong>' . $colName . '</strong> sütununda CHARINDEX ile ' . count($items) . ' ürün bulundu!</p>';
                                
                                echo '<table>';
                                echo '<tr><th>LOGICALREF</th><th>CODE</th><th>' . $colName . '</th></tr>';
                                foreach (array_slice($items, 0, 20) as $item) {
                                    echo '<tr>';
                                    echo '<td class="code">' . htmlspecialchars($item->LOGICALREF ?? 'N/A') . '</td>';
                                    echo '<td class="code">' . htmlspecialchars($item->CODE ?? 'N/A') . '</td>';
                                    echo '<td><pre style="margin:0;">' . htmlspecialchars($item->COL_VALUE ?? '') . '</pre></td>';
                                    echo '</tr>';
                                }
                                echo '</table>';
                                break; // İlk bulunan sütunu kullan
                            }
                        }
                    } catch (\Exception $colE) {
                        // Sütun yoksa veya hata varsa sessizce geç
                    }
                }
                
                if (empty($foundItems)) {
                    echo '<p class="warning">⚠️ Hiçbir sütunda "Outlet Ürünüdür" içeren ürün bulunamadı!</p>';
                }
                
            } catch (\Exception $e) {
                echo '<p class="error">❌ Arama hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            echo '</div>';
            

            // 3. "Outlet Ürünüdür" araması - Farklı varyasyonlar
            echo '<div class="info"><h3>3. "Outlet Ürünüdür" Araması</h3>';
            $searchTerms = [
                '%Outlet Ürünüdür%',
                '%Outlet%',
                '%Ürünüdür%',
                '%OUTLET ÜRÜNÜDÜR%',
                '%OUTLET%',
                '%outlet%',
                '%Outlet%',
                '%OUTLET ÜRÜNÜDÜR%',
                '%Outlet Ürünüdür%',
                '%Outlet  Ürünüdür%', // Çift boşluk
                '%Outlet  Ürünüdür%', // Farklı boşluklar
            ];
            
            $foundItems = [];
            foreach ($searchTerms as $searchTerm) {
                try {
                    // Önce LIKE ile dene
                    $stmt = $pdo->prepare("
                        SELECT LOGICALREF, CODE, NAME2 
                        FROM LG_526_ITEMS 
                        WHERE NAME2 IS NOT NULL 
                        AND LEN(LTRIM(RTRIM(NAME2))) > 0
                        AND UPPER(LTRIM(RTRIM(NAME2))) LIKE UPPER(?)
                    ");
                    $stmt->execute([$searchTerm]);
                    $items = $stmt->fetchAll(\PDO::FETCH_OBJ);
                    
                    if (count($items) > 0) {
                        echo '<p class="success">✅ "' . htmlspecialchars($searchTerm) . '" ile ' . count($items) . ' ürün bulundu!</p>';
                        $foundItems = $items;
                        
                        echo '<table>';
                        echo '<tr><th>LOGICALREF</th><th>CODE</th><th>NAME2</th></tr>';
                        foreach (array_slice($items, 0, 10) as $item) {
                            echo '<tr>';
                            echo '<td class="code">' . htmlspecialchars($item->LOGICALREF ?? 'N/A') . '</td>';
                            echo '<td class="code">' . htmlspecialchars($item->CODE ?? 'N/A') . '</td>';
                            echo '<td><pre style="margin:0;">' . htmlspecialchars($item->NAME2 ?? '') . '</pre></td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                        break; // İlk başarılı aramayı kullan
                    } else {
                        echo '<p>❌ "' . htmlspecialchars($searchTerm) . '" ile ürün bulunamadı</p>';
                    }
                } catch (\Exception $e) {
                    echo '<p class="error">❌ Arama hatası (' . htmlspecialchars($searchTerm) . '): ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            }
            
            // Eğer hiçbir şey bulunamadıysa, CHARINDEX ile dene
            if (empty($foundItems)) {
                echo '<p class="warning">⚠️ LIKE ile bulunamadı, CHARINDEX ile deneniyor...</p>';
                try {
                    $stmt = $pdo->query("
                        SELECT TOP 50 LOGICALREF, CODE, NAME2 
                        FROM LG_526_ITEMS 
                        WHERE NAME2 IS NOT NULL 
                        AND LEN(LTRIM(RTRIM(NAME2))) > 0
                        AND (CHARINDEX('Outlet', UPPER(NAME2)) > 0 OR CHARINDEX('Ürünüdür', NAME2) > 0)
                        ORDER BY CODE
                    ");
                    $items = $stmt->fetchAll(\PDO::FETCH_OBJ);
                    
                    if (count($items) > 0) {
                        echo '<p class="success">✅ CHARINDEX ile ' . count($items) . ' ürün bulundu!</p>';
                        $foundItems = $items;
                        
                        echo '<table>';
                        echo '<tr><th>LOGICALREF</th><th>CODE</th><th>NAME2</th></tr>';
                        foreach (array_slice($items, 0, 20) as $item) {
                            echo '<tr>';
                            echo '<td class="code">' . htmlspecialchars($item->LOGICALREF ?? 'N/A') . '</td>';
                            echo '<td class="code">' . htmlspecialchars($item->CODE ?? 'N/A') . '</td>';
                            echo '<td><pre style="margin:0;">' . htmlspecialchars($item->NAME2 ?? '') . '</pre></td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                } catch (\Exception $e) {
                    echo '<p class="error">❌ CHARINDEX hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            }
            echo '</div>';

            // 4. Local MySQL'de LogicalRef eşleşmesi kontrolü
            if (!empty($foundItems)) {
                echo '<div class="info"><h3>4. Local MySQL Eşleşme Kontrolü</h3>';
                $logicalRefs = array_map(function($item) {
                    return (int)$item->LOGICALREF;
                }, array_slice($foundItems, 0, 20));
                
                echo '<p>Kontrol edilecek LogicalRef\'ler: ' . implode(', ', $logicalRefs) . '</p>';
                
                // Önce kolon adlarını kontrol et
                try {
                    $columns = DB::select("SHOW COLUMNS FROM urunler");
                    $columnNames = array_map(function($col) { return $col->Field; }, $columns);
                    echo '<p><strong>urunler tablosu kolonları:</strong> ' . implode(', ', $columnNames) . '</p>';
                } catch (\Exception $colE) {
                    echo '<p class="error">❌ Kolon kontrolü hatası: ' . htmlspecialchars($colE->getMessage()) . '</p>';
                }
                
                try {
                    // Fiyat filtresi olmadan kontrol et
                    $localUrunlerAll = DB::table('urunler')
                        ->where(function($query) use ($logicalRefs) {
                            $query->whereIn('GEMPA2026LOGICAL', $logicalRefs)
                                  ->orWhereIn('GEMAS2026LOGICAL', $logicalRefs);
                        })
                        ->select('id', 'stokkodu', 'stokadi', 'GEMAS2026LOGICAL', 'GEMPA2026LOGICAL', 'fiyat')
                        ->get();
                    
                    echo '<p><strong>Local MySQL\'de eşleşen ürün sayısı (fiyat filtresi olmadan):</strong> ' . count($localUrunlerAll) . '</p>';
                    
                    // Fiyat filtresi ile kontrol et
                    $localUrunler = DB::table('urunler')
                        ->where(function($query) use ($logicalRefs) {
                            $query->whereIn('GEMPA2026LOGICAL', $logicalRefs)
                                  ->orWhereIn('GEMAS2026LOGICAL', $logicalRefs);
                        })
                        ->where(function($query) {
                            $query->whereNotNull('fiyat')
                                  ->where('fiyat', '!=', '')
                                  ->where('fiyat', '!=', '0')
                                  ->whereRaw("CAST(fiyat AS DECIMAL(10,2)) > 0");
                        })
                        ->select('id', 'stokkodu', 'stokadi', 'GEMAS2026LOGICAL', 'GEMPA2026LOGICAL', 'fiyat')
                        ->get();
                    
                    echo '<p><strong>Local MySQL\'de eşleşen ürün sayısı (fiyat filtresi ile):</strong> ' . count($localUrunler) . '</p>';
                    
                    if (count($localUrunlerAll) > 0) {
                        echo '<table>';
                        echo '<tr><th>ID</th><th>Stok Kodu</th><th>Stok Adı</th><th>GEMAS2026LOGICAL</th><th>GEMPA2026LOGICAL</th><th>Fiyat</th></tr>';
                        foreach ($localUrunlerAll as $urun) {
                            $rowClass = (empty($urun->fiyat) || $urun->fiyat == '0') ? 'style="background-color:#fff3cd;"' : '';
                            echo '<tr ' . $rowClass . '>';
                            echo '<td>' . htmlspecialchars($urun->id ?? 'N/A') . '</td>';
                            echo '<td class="code">' . htmlspecialchars($urun->stokkodu ?? 'N/A') . '</td>';
                            echo '<td>' . htmlspecialchars(substr($urun->stokadi ?? '', 0, 50)) . '</td>';
                            echo '<td class="code">' . htmlspecialchars($urun->GEMAS2026LOGICAL ?? 'NULL') . '</td>';
                            echo '<td class="code">' . htmlspecialchars($urun->GEMPA2026LOGICAL ?? 'NULL') . '</td>';
                            echo '<td>' . htmlspecialchars($urun->fiyat ?? '0') . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                        
                        if (count($localUrunlerAll) > count($localUrunler)) {
                            echo '<p class="warning">⚠️ ' . (count($localUrunlerAll) - count($localUrunler)) . ' ürün fiyat filtresi nedeniyle elendi!</p>';
                        }
                    } else {
                        echo '<p class="warning">⚠️ Local MySQL\'de eşleşen ürün bulunamadı!</p>';
                        echo '<p>Bu LogicalRef\'lere sahip ürünler local MySQL\'de olmayabilir veya kolon adları farklı olabilir.</p>';
                        
                        // İlk birkaç LogicalRef'i tek tek kontrol et
                        echo '<p><strong>Tek tek LogicalRef kontrolü:</strong></p>';
                        echo '<table>';
                        echo '<tr><th>Logo LogicalRef</th><th>Local MySQL\'de Bulundu mu?</th><th>Stok Kodu</th></tr>';
                        foreach (array_slice($logicalRefs, 0, 10) as $logicalRef) {
                            $singleTest = DB::table('urunler')
                                ->where('GEMAS2026LOGICAL', $logicalRef)
                                ->orWhere('GEMPA2026LOGICAL', $logicalRef)
                                ->select('id', 'stokkodu', 'GEMAS2026LOGICAL', 'GEMPA2026LOGICAL')
                                ->first();
                            echo '<tr>';
                            echo '<td class="code">' . $logicalRef . '</td>';
                            if ($singleTest) {
                                echo '<td class="success">✅ Evet</td>';
                                echo '<td class="code">' . htmlspecialchars($singleTest->stokkodu ?? 'N/A') . '</td>';
                            } else {
                                echo '<td class="error">❌ Hayır</td>';
                                echo '<td>-</td>';
                            }
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                } catch (\Exception $e) {
                    echo '<p class="error">❌ Local MySQL kontrolü hatası: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                }
                echo '</div>';
            }

        } catch (\Exception $e) {
            echo '<div class="error"><h3>❌ Genel Hata</h3>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        ?>

        <div class="info" style="margin-top: 30px;">
            <h3>💡 Notlar</h3>
            <ul>
                <li>Bu sayfa Logo veritabanından ürünleri çekip test eder</li>
                <li>NAME2 sütununda "Outlet Ürünüdür" içeren ürünleri arar</li>
                <li>Local MySQL'de LogicalRef eşleşmesini kontrol eder</li>
                <li>Sonuçları inceleyerek sorunun kaynağını bulabilirsiniz</li>
            </ul>
        </div>
    </div>
</body>
</html>

