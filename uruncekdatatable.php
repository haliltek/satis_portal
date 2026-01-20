<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require "ssp.php";
include "include/vt.php";

// Uzak MySQL bağlantısı (çeviri tabloları için)
function getTranslationDb() {
    static $translationDb = null;
    if ($translationDb === null) {
        $hostname = "89.43.31.214";
        $username = "gemas_mehmet";
        $password = "2261686Me!";
        $dbname = "gemas_pool_technology";
        $port = 3306;
        
        try {
            $translationDb = new mysqli($hostname, $username, $password, $dbname, $port);
            if ($translationDb->connect_error) {
                error_log("Çeviri veritabanı bağlantı hatası: " . $translationDb->connect_error);
                $translationDb = false; // false olarak işaretle, tekrar deneme
                return null;
            }
            $translationDb->set_charset("utf8");
        } catch (Exception $e) {
            error_log("Çeviri veritabanı bağlantı hatası: " . $e->getMessage());
            $translationDb = false;
            return null;
        }
    }
    return ($translationDb === false) ? null : $translationDb;
}

// Stok kodlarına göre İngilizce adları çekme fonksiyonu
function getEnglishNamesByStockCodes($stockCodes) {
    $translationDb = getTranslationDb();
    if (!$translationDb || empty($stockCodes)) {
        return [];
    }
    
    $englishNames = [];
    
    // Placeholder'ları hazırla
    $placeholders = implode(',', array_fill(0, count($stockCodes), '?'));
    
    // malzeme tablosundan stok kodlarına göre ID'leri bul
    // Stok kodlarını trim ve uppercase yaparak eşleştirme yapalım (case-insensitive)
    $stockCodesNormalized = array_map(function($code) {
        return strtoupper(trim($code));
    }, $stockCodes);
    
    $sql = "SELECT m.id, m.stok_kodu 
            FROM malzeme m 
            WHERE UPPER(TRIM(m.stok_kodu)) IN (" . implode(',', array_fill(0, count($stockCodesNormalized), '?')) . ")";
    
    $stmt = $translationDb->prepare($sql);
    if (!$stmt) {
        error_log("malzeme sorgusu hazırlanamadı: " . $translationDb->error);
        return [];
    }
    
    // Bind parametreleri
    $types = str_repeat('s', count($stockCodesNormalized));
    $stmt->bind_param($types, ...$stockCodesNormalized);
    
    if (!$stmt->execute()) {
        error_log("malzeme sorgusu çalıştırılamadı: " . $stmt->error);
        $stmt->close();
        return [];
    }
    
    $result = $stmt->get_result();
    $malzemeIds = [];
    $stockCodeToMalzemeId = [];
    $foundStockCodes = [];
    
    while ($row = $result->fetch_assoc()) {
        $malzemeIds[] = $row['id'];
        $normalizedCode = strtoupper(trim($row['stok_kodu']));
        $stockCodeToMalzemeId[$normalizedCode] = $row['id'];
        $foundStockCodes[] = $normalizedCode;
    }
    $stmt->close();
    
    // Debug: kaç stok kodu eşleşti
    error_log("getEnglishNamesByStockCodes - " . count($stockCodes) . " stok kodu sorgulandı, " . count($foundStockCodes) . " tanesi malzeme tablosunda bulundu");
    
    if (empty($malzemeIds)) {
        return [];
    }
    
    // malzeme_translations tablosundan İngilizce adları çek
    // Önce tablo yapısını kontrol et
    $checkColumns = $translationDb->query("SHOW COLUMNS FROM malzeme_translations");
    $columnNames = [];
    if ($checkColumns) {
        while ($col = $checkColumns->fetch_assoc()) {
            $columnNames[] = $col['Field'];
        }
    }
    
    // Ürün adı için olası sütun adları
    $nameColumn = null;
    $possibleColumns = ['ad', 'name', 'title', 'baslik', 'urun_adi', 'malzeme_adi', 'aciklama'];
    foreach ($possibleColumns as $col) {
        if (in_array($col, $columnNames)) {
            $nameColumn = $col;
            break;
        }
    }
    
    // Eğer hiçbir sütun bulunamazsa boş dizi döndür
    if (!$nameColumn) {
        error_log("malzeme_translations tablosunda ürün adı sütunu bulunamadı. Mevcut sütunlar: " . implode(', ', $columnNames));
        return [];
    }
    
    // Debug: hangi sütun kullanılıyor
    error_log("malzeme_translations tablosunda kullanılan sütun: " . $nameColumn);
    
    $placeholders2 = implode(',', array_fill(0, count($malzemeIds), '?'));
    // Sütun adını backtick ile güvenli hale getir
    $nameColumnEscaped = '`' . $translationDb->real_escape_string($nameColumn) . '`';
    $sql2 = "SELECT mt.malzeme_id, mt.{$nameColumnEscaped}, m.stok_kodu
             FROM malzeme_translations mt
             INNER JOIN malzeme m ON m.id = mt.malzeme_id
             WHERE mt.malzeme_id IN ($placeholders2) AND mt.locale = 'en'";
    
    $stmt2 = $translationDb->prepare($sql2);
    if (!$stmt2) {
        error_log("malzeme_translations sorgusu hazırlanamadı: " . $translationDb->error);
        return [];
    }
    
    $types2 = str_repeat('i', count($malzemeIds));
    $stmt2->bind_param($types2, ...$malzemeIds);
    
    if (!$stmt2->execute()) {
        error_log("malzeme_translations sorgusu çalıştırılamadı: " . $stmt2->error);
        $stmt2->close();
        return [];
    }
    
    $result2 = $stmt2->get_result();
    $foundCount = 0;
    while ($row2 = $result2->fetch_assoc()) {
        $nameValue = $row2[$nameColumn] ?? '';
        if (!empty($nameValue)) {
            $stokKodu = $row2['stok_kodu'];
            $normalizedStokKodu = strtoupper(trim($stokKodu));
            $englishNames[$normalizedStokKodu] = trim($nameValue);
            // Orijinal stok kodunu da ekle (case-sensitive eşleşme için)
            $englishNames[$stokKodu] = trim($nameValue);
            $foundCount++;
        }
    }
    $stmt2->close();
    
    // Debug: kaç kayıt bulundu
    error_log("getEnglishNamesByStockCodes - " . count($malzemeIds) . " malzeme_id için " . $foundCount . " İngilizce ad bulundu");
    
    return $englishNames;
}

$foreign = false;
// Önce GET parametresinden kontrol et (teklif/sipariş oluşturma sayfasından gelen)
// DataTable parametreleri $_GET içinde gelir
if (isset($_GET['pazar_tipi']) && $_GET['pazar_tipi'] === 'yurtdisi') {
    $foreign = true;
} elseif (!empty($_SESSION['pazar_tipi']) && $_SESSION['pazar_tipi'] === 'yurtdisi') {
    // Session'dan kontrol et
    $foreign = true;
} elseif (!empty($_SESSION['yonetici_id'])) {
    // Eski yöntem: Yönetici satış tipinden kontrol et
    $mysqli = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    if (!$mysqli->connect_errno) {
        $stmt = $mysqli->prepare("SELECT satis_tipi FROM yonetici WHERE yonetici_id = ?");
        $stmt->bind_param('i', $_SESSION['yonetici_id']);
        $stmt->execute();
        $stmt->bind_result($satisTipi);
        if ($stmt->fetch()) {
            $foreign = (strpos(strtolower($satisTipi), 'dışı') !== false);
        }
        $stmt->close();
        $mysqli->close();
    }
}

// Debug için log (gerekirse açılabilir)
// error_log("uruncekdatatable.php - pazar_tipi GET: " . ($_GET['pazar_tipi'] ?? 'yok') . ", Session: " . ($_SESSION['pazar_tipi'] ?? 'yok') . ", foreign: " . ($foreign ? 'true' : 'false'));

$table = 'urunler';
$primaryKey = 'urun_id';

// Pazar tipini dinamik olarak belirleme fonksiyonu
$getForeign = function() {
    // Her çağrıda yeniden kontrol et
    if (isset($_GET['pazar_tipi']) && $_GET['pazar_tipi'] === 'yurtdisi') {
        return true;
    } elseif (!empty($_SESSION['pazar_tipi']) && $_SESSION['pazar_tipi'] === 'yurtdisi') {
        return true;
    }
    return false;
};

$columns = array(
    array('db' => 'urun_id',  'dt' => 0, 'formatter' => function ($id, $row) use ($getForeign) {
        $foreign = $getForeign();
        $domesticRaw = is_numeric($row['fiyat']) ? $row['fiyat'] : 0;
        $exportRaw   = is_numeric($row['export_fiyat']) ? $row['export_fiyat'] : 0;
        // Yurtdışı seçildiyse sadece export fiyatını kullan, 0 ise 0 göster
        $price       = $foreign ? $exportRaw : $domesticRaw;
        $class       = $price > 0 ? 'btn-success select-btn' : 'btn-secondary select-btn no-price-btn';
        
        return "<div class='d-flex align-items-center gap-1'>" .
               "<input type='number' class='form-control form-control-sm quantity-input-list' value='1' min='1' style='width: 60px;' data-id='{$id}' onclick='event.stopPropagation();'>" .
               "<button type='button' class='btn btn-sm {$class}' data-id='{$id}' data-price='{$price}'>Seç</button>" .
               "</div>";
    }),
    array('db' => 'stokkodu', 'dt' => 1, 'formatter' => function ($stokkodu, $row) {
        $stokkoduEscaped = htmlspecialchars($stokkodu);
        return "<div style='display: inline-flex; align-items: center; gap: 2px; white-space: nowrap; width: 100%;'>" .
               "<span style='flex: 1; overflow: hidden; text-overflow: ellipsis;'>" . $stokkoduEscaped . "</span>" .
               "<button type='button' class='btn btn-sm product-search-btn-modal' data-stokkodu='" . $stokkoduEscaped . "' data-product-id='" . $row['urun_id'] . "' style='padding: 0; width: 22px; height: 22px; border: 1px solid #ccc; background: white; flex-shrink: 0;' title='Ürün Ara'>" .
               "<span style='font-size: 12px; line-height: 1;'>⋯</span>" .
               "</button>" .
               "</div>";
    }),
    array('db' => 'stokadi',  'dt' => 2, 'formatter' => function ($stokadi, $row) use ($getForeign) {
        $foreign = $getForeign();
        
        // Önce çeviri tablosundan İngilizce adı kontrol et
        $stokadiEn = '';
        if ($foreign) {
            // Çeviri tablosundan gelen İngilizce adı kullan
            if (isset($row['stokadi_en_translation']) && !empty($row['stokadi_en_translation'])) {
                $stokadiEn = trim($row['stokadi_en_translation']);
            }
            // Eğer çeviri tablosunda yoksa, eski stokadi_en alanını kullan (fallback)
            if (empty($stokadiEn) && isset($row['stokadi_en']) && !empty($row['stokadi_en'])) {
                $stokadiEn = trim($row['stokadi_en']);
            }
            
            // Debug: İlk birkaç ürün için log
            static $debugCount = 0;
            if ($debugCount < 3 && isset($row['stokkodu'])) {
                error_log("Formatter - Stok: " . $row['stokkodu'] . ", foreign: " . ($foreign ? 'true' : 'false') . ", stokadi_en_translation: " . ($row['stokadi_en_translation'] ?? 'yok') . ", stokadi_en: " . ($row['stokadi_en'] ?? 'yok') . ", sonuç: " . ($stokadiEn ?: 'Türkçe kullanılacak'));
                $debugCount++;
            }
        }
        
        // Yurtdışı seçildiyse ve İngilizce ad varsa İngilizce göster
        if ($foreign && !empty($stokadiEn)) {
            $displayName = $stokadiEn;
        } else {
            $displayName = trim($stokadi);
        }
        $aciklama = !empty($row['aciklama']) ? trim($row['aciklama']) : '';
        if (!empty($aciklama)) {
            return htmlspecialchars($displayName) . '<br><small class="text-muted">' . nl2br(htmlspecialchars($aciklama)) . '</small>';
        }
        return htmlspecialchars($displayName);
    }),
    array('db' => 'olcubirimi', 'dt' => 3),
    array('db' => 'fiyat',  'dt' => 4, 'formatter' => function ($fiyat, $row) use ($getForeign) {
        $foreign = $getForeign();
        $domesticRaw = is_numeric($fiyat) ? $fiyat : 0;
        $exportRaw   = is_numeric($row['export_fiyat']) ? $row['export_fiyat'] : 0;
        // Yurtdışı seçildiyse sadece export fiyatını kullan, 0 ise 0 göster
        $price       = $foreign ? $exportRaw : $domesticRaw;
        return $price > 0 ? htmlspecialchars(number_format($price, 2, ',', '.')) : '<span class="text-danger">Fiyat Yok</span>';
    }),
    array('db' => 'doviz', 'dt' => 5),
    array('db' => 'miktar', 'dt' => 6, 'formatter' => function ($data) {
        if ($data == "0") {
            return "<button class='btn btn-sm btn-danger'>{$data}</button>";
        } else {
            return "<button class='btn btn-sm btn-success'>{$data}</button>";
        }
    }),
    array('db' => 'marka', 'dt' => 7),
    array('db' => 'export_fiyat', 'dt' => 8, 'formatter' => function ($export, $row) use ($getForeign) {
        $foreign = $getForeign();
        $domesticRaw = is_numeric($row['fiyat']) ? $row['fiyat'] : 0;
        $exportRaw   = is_numeric($export) ? $export : 0;
        // Yurtdışı seçildiyse sadece export fiyatını göster, yurtiçi seçildiyse domestic fiyatını göster
        return $foreign ? htmlspecialchars(number_format($exportRaw, 2, ',', '.')) : htmlspecialchars(number_format($domesticRaw, 2, ',', '.'));
    }),
);

$extraWhere = '';
if (!empty($_GET['onlyPriced'])) {
    $extraWhere = '( (fiyat > 0) OR (export_fiyat > 0) )';
}

// aciklama alanını SELECT'e dahil etmek için özel bir SSP wrapper kullanıyoruz
class SSPWithExtraFields extends SSP {
    public static function complex($request, $conn, $table, $primaryKey, $columns, $whereResult = null, $whereAll = null, $extraFields = []) {
        $bindings = array();
        $db = self::db($conn);
        $localWhereResult = array();
        $localWhereAll = array();
        $whereAllSql = '';
        // Build the SQL query string from the request
        $limit = self::limit($request, $columns);
        $order = self::order($request, $columns);
        $where = self::filter($request, $columns, $bindings);
        $whereResult = self::_flatten($whereResult);
        $whereAll = self::_flatten($whereAll);
        if ($whereResult) {
            $where = $where ?
                $where . ' AND ' . $whereResult :
                'WHERE ' . $whereResult;
        }
        if ($whereAll) {
            $where = $where ?
                $where . ' AND ' . $whereAll :
                'WHERE ' . $whereAll;
            $whereAllSql = 'WHERE ' . $whereAll;
        }
        // Main query to actually get the data - extraFields ekleniyor
        $selectFields = self::pluck($columns, 'db');
        foreach ($extraFields as $field) {
            if (!in_array($field, $selectFields)) {
                $selectFields[] = $field;
            }
        }
        $data = self::sql_exec(
            $db,
            $bindings,
            "SELECT `" . implode("`, `", $selectFields) . "`
             FROM `$table`
             $where
             $order
             $limit"
        );
        // Data set length after filtering
        $resFilterLength = self::sql_exec(
            $db,
            $bindings,
            "SELECT COUNT(`{$primaryKey}`)
             FROM   `$table`
             $where"
        );
        $recordsFiltered = $resFilterLength[0][0];
        // Total data set length
        $resTotalLength = self::sql_exec(
            $db,
            [],
            "SELECT COUNT(`{$primaryKey}`)
             FROM   `$table` " .
                $whereAllSql
        );
        $recordsTotal = $resTotalLength[0][0];
        // İngilizce adları çeviri tablolarından çek (yurtdışı seçildiyse)
        $getForeign = function() {
            if (isset($_GET['pazar_tipi']) && $_GET['pazar_tipi'] === 'yurtdisi') {
                return true;
            } elseif (!empty($_SESSION['pazar_tipi']) && $_SESSION['pazar_tipi'] === 'yurtdisi') {
                return true;
            }
            return false;
        };
        $isForeign = $getForeign();
        
        $englishNames = [];
        if ($isForeign && !empty($data)) {
            // Tüm stok kodlarını topla
            $stockCodes = [];
            foreach ($data as $row) {
                if (!empty($row['stokkodu'])) {
                    $stockCodes[] = $row['stokkodu'];
                }
            }
            // İngilizce adları çek
            if (!empty($stockCodes)) {
                $englishNames = getEnglishNamesByStockCodes($stockCodes);
                // Debug: kaç İngilizce ad bulundu
                error_log("uruncekdatatable.php - Yurtdışı seçildi, " . count($stockCodes) . " stok kodu için " . count($englishNames) . " İngilizce ad bulundu");
            }
        }
        
        // data_output'u override ediyoruz - aciklama alanını $row dizisine eklemek için
        $out = array();
        for ($i = 0, $ien = count($data); $i < $ien; $i++) {
            $row = array();
            // İngilizce adı ekle (eğer varsa)
            if ($isForeign && isset($data[$i]['stokkodu'])) {
                $stokKodu = $data[$i]['stokkodu'];
                // Hem orijinal hem de normalize edilmiş stok kodunu kontrol et
                if (isset($englishNames[$stokKodu])) {
                    $data[$i]['stokadi_en_translation'] = $englishNames[$stokKodu];
                } elseif (isset($englishNames[strtoupper(trim($stokKodu))])) {
                    $data[$i]['stokadi_en_translation'] = $englishNames[strtoupper(trim($stokKodu))];
                }
            }
            
            for ($j = 0, $jen = count($columns); $j < $jen; $j++) {
                $column = $columns[$j];
                // Is there a formatter?
                if (isset($column['formatter'])) {
                    if (empty($column['db'])) {
                        $row[$column['dt']] = $column['formatter']($data[$i]);
                    } else {
                        // Formatter'a $data[$i] dizisini geçiriyoruz (aciklama dahil)
                        $row[$column['dt']] = $column['formatter']($data[$i][$column['db']], $data[$i]);
                    }
                } else {
                    if (!empty($column['db'])) {
                        $row[$column['dt']] = $data[$i][$columns[$j]['db']];
                    } else {
                        $row[$column['dt']] = "";
                    }
                }
            }
            $out[] = $row;
        }
        
        return array(
            "draw"            => intval($request['draw']),
            "recordsTotal"    => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
            "data"            => $out
        );
    }
}

try {
    // stokadi_en alanını kontrol et - eğer sütun yoksa sadece aciklama ekle
    $checkStmt = new mysqli($sql_details['host'], $sql_details['user'], $sql_details['pass'], $sql_details['db']);
    $checkResult = $checkStmt->query("SHOW COLUMNS FROM urunler LIKE 'stokadi_en'");
    $hasStokadiEn = $checkResult && $checkResult->num_rows > 0;
    $checkStmt->close();
    
    $extraFields = ['aciklama'];
    if ($hasStokadiEn) {
        $extraFields[] = 'stokadi_en';
    }
    
    // Debug: pazar_tipi parametresini kontrol et
    $pazarTipi = $_GET['pazar_tipi'] ?? ($_SESSION['pazar_tipi'] ?? 'yurtici');
    // error_log("uruncekdatatable.php - pazar_tipi: " . $pazarTipi . ", GET: " . ($_GET['pazar_tipi'] ?? 'yok') . ", SESSION: " . ($_SESSION['pazar_tipi'] ?? 'yok'));
    
    $result = SSPWithExtraFields::complex($_GET, $sql_details, $table, $primaryKey, $columns, $extraWhere, null, $extraFields);
    echo json_encode($result);
} catch (Exception $e) {
    error_log("uruncekdatatable.php error: " . $e->getMessage());
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Veri yüklenirken bir hata oluştu: ' . $e->getMessage()
    ]);
}
