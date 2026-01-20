<?php
/**
 * API: Logo Kampanya İskonto Hesaplama
 * Girdiler: Sepet ürünleri (Stok Kodu, Miktar) ve Cari Kodu
 * Çıktı: Her satır için uygulanacak kampanya indirimi
 */



// Output Buffering'i en başta başlat ki olası warning'leri yutalım
ob_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../fonk.php";

// Eğer fonk.php bir şeyler yazdıysa temizle
if (ob_get_length()) ob_clean(); 


// Session başlatma (fonk.php başlatmamışsa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hata raporlamayı aç (debug için - production'da kapatılmalı)
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$response = [
    'success' => false,
    'message' => '', // Hata mesajı için
    'discounts' => [], 
    'applied_campaigns' => [], 
    'logs' => [] 
];

function apiLog($msg) {
    global $response;
    $response['logs'][] = $msg;
}

try {
    // 1. Girdi Verilerini Al
    global $TEST_INPUT;
    if (isset($TEST_INPUT)) {
        $input = $TEST_INPUT;
    } else {
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);
    }

    if (!$input) {
        throw new Exception("Geçersiz JSON verisi");
    }

    // Parametreleri Eşle (Frontend: items -> Backend: cartItems)
    $cartItems = $input['cartItems'] ?? ($input['items'] ?? []);
    
    // Cari kodu: Önce inputtan, sonra session'dan dene
    $clientCode = $input['clientCode'] ?? '';
    
    if (empty($clientCode)) {
        // Session'dan cari kodu bulmaya çalış
        // Teklif oluştur sayfasında genellikle seçili müşteri vardır, veya admin giriş yapmıştır
        // Admin girişinde 'cari_kodu' session'da olmayabilir, formdan gelmeli.
        // Ancak demo için ERTEK kodu veya sessiondaki kodu kullanmayı deneyelim.
        
        // 1. Durum: Müşteri girişi (Sade arayüz)
        if (isset($_SESSION['cari_kodu']) && !empty($_SESSION['cari_kodu'])) {
            $clientCode = $_SESSION['cari_kodu'];
        } 
        // 2. Durum: Yönetici girişi - Seçili Cari
        elseif (isset($_SESSION['selected_cari_code'])) {
             $clientCode = $_SESSION['selected_cari_code'];
        }
        // Fallback: Test için (Kaldırılmalı) -> 120.01.E04
        else {
             // throw new Exception("Cari kodu bulunamadı (Oturum veya Parametre)");
             $clientCode = "120.01.E04"; // Şimdilik hardcode (Demo)
             apiLog("UYARI: Cari kodu bulunamadı, varsayılan (120.01.E04) kullanılıyor.");
        }
    }

    if (empty($clientCode) || empty($cartItems)) {
        throw new Exception("Eksik parametreler: Cari Kodu veya Sepet Ürünleri");
    }

    apiLog("İşlem Başladı. Cari: $clientCode, Ürün Sayısı: " . count($cartItems));

    // 2. Veritabanı Bağlantıları
    $config = require __DIR__ . '/../config/config.php';
    $logo = $config['logo'];

    // Ana Logo bağlantısı (GEMPA2026 için kampanya tablosu)
    $dsn = "sqlsrv:Server={$logo['host']};Database={$logo['db']};Encrypt=no;TrustServerCertificate=yes";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ];
    $pdo = new PDO($dsn, $logo['user'], $logo['pass'], $options);

    // Tiger bağlantısı (MEG filtre tabloları için)
    $dsnTiger = "sqlsrv:Server={$logo['host']};Database=Tiger;Encrypt=no;TrustServerCertificate=yes";
    $pdoTiger = new PDO($dsnTiger, $logo['user'], $logo['pass'], $options);


    // 3. Kampanya Motoru Sınıfı
    class LogoCampaignCalculator {
        private $pdo;
        private $pdoTiger;
        public $campaigns = [];

        public function __construct($pdo, $pdoTiger = null) {
            $this->pdo = $pdo;
            $this->pdoTiger = $pdoTiger ?? $pdo;
        }

        /**
         * Aktif satış kampanyalarını çeker (ACTIVE=0, CARDTYPE=2)
         */
        public function loadCampaigns($clientCode) {
            // Tarih kontrolü de yapılmalı (BEGDATE <= NOW <= ENDDATE)
            // Logo tarih formatı integer gün sayısıdır (1899-12-30 bazlı)
            // Şimdilik tarih kontrolü ve cari kontrolünü es geçip tüm aktif satış kampanyalarını çekiyoruz.
            // İleride detaylandırılabilir.
            
            $sql = "SELECT * FROM LG_566_CAMPAIGN WHERE ACTIVE = 0 AND CARDTYPE = 2 ORDER BY PRIORITY DESC";
            $stmt = $this->pdo->query($sql);
            $this->campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return count($this->campaigns);
        }

        /**
         * _SQLINFO fonksiyonunu simüle eder ve çalıştırır.
         * Örn: VAL(_SQLINFO("COUNT(*)","MEG_565_FILTRE","KOD='"+P101+"'"))
         */
        public function executeSqlInfo($expression, $productCode) {
            // P101 replace - defined early
            $cleanProductCode = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $productCode);

            // GLOBAL PRE-PROCESS: P101 değişkenini en başta string içinde erit
            // Logo makrolarında string birleştirme: "KOD='"+P101+"'" -> "KOD='001'"
            // Bu yüzden önce "+P101+" yapısını temizleriz.
            
            // 1. Durum: Çift tırnaklı birleştirme ("+P101+")
            $expression = str_replace('"+P101+"', $cleanProductCode, $expression);
            
            // 2. Durum: Tek tırnaklı birleştirme ('+P101+')
            $expression = str_replace("'+P101+'", $cleanProductCode, $expression);
            
            // 3. Durum: Boşluklu (+ P101 +)
            $expression = preg_replace('/["\']\s*\+\s*P101\s*\+\s*["\']/', $cleanProductCode, $expression);
            
            // 4. Durum: Yalın P101 (Eğer tırnak içinde değilse, veya direkt kullanıldıysa)
            // Ancak dikkat: String içindeki P101 kelimesini bozmamalıyız. 
            // Şimdilik üsttekiler yeterli, çünkü _SQLINFO içinde her zaman string concat ile gelir.

            
            // Manuel Parser Improved
            // Örn: VAL(_SQLINFO("COUNT(*)","MEG_565_FILTRE","KOD='XY'")) -> Artık P101 yok
            
            $startPos = strpos($expression, '_SQLINFO("');
            if ($startPos !== false) {
                 // _SQLINFO(" sonrasını al
                 $sub = substr($expression, $startPos + 10); 
                 
                 // En fazla 3 parçaya böl: "COL","TABLE","REST
                 $parts = explode('","', $sub, 3);
                 
                 if (count($parts) >= 3) {
                     $selectCol = $parts[0];
                     $tableName = $parts[1];
                     $rest = $parts[2]; 
                     
                     // EĞER Tablo adı MEG_565_FILTRE ise, Tiger veritabanından çek
                     if ($tableName === 'MEG_565_FILTRE') {
                         $tableName = 'MEG_565_FILTRE';
                     } elseif ($tableName === 'MEG_565_FILTRE_HAR') {
                         $tableName = 'MEG_565_FILTRE_HAR';
                     }
                     
                     // Son parça: KOD='001'"))
                     // Son tırnağa kadar olanı al
                     $lastQuotePos = strrpos($rest, '"');
                     if ($lastQuotePos !== false) {
                         $whereClause = substr($rest, 0, $lastQuotePos); // Artık direkt WHERE
                         
                         // Clean up SelectCol (remove potentially leading quote)
                         $selectCol = trim($selectCol, '"');
                         
                         // Clean up Table (remove trailing/leading quotes if split wasn't perfect)
                         // explode '","' yediği için temizdir.
                        // But usually the outer quotes come from the original string KOD='...'

                        $sql = "SELECT TOP 1 $selectCol FROM $tableName WHERE $whereClause";
                        
                         // Debug SQL
                        global $response;
                        // $response['logs'][] = "DEBUG DEEP: RawWhere: [$whereClauseRaw] -> FinalWhere: [$whereClause]";
                        $response['logs'][] = "DEBUG SQL: $sql";
                        
                        try {
                            // Kampanya filtre tabloları Tiger'dadır (MEG_, HALIL_OZEL_FIYAT vs.)
                            // LG_ ile başlayan tablolar GEMPA2026'dadır  
                            $usePdo = (strpos($tableName, 'LG_') === 0) ? $this->pdo : $this->pdoTiger;
                            $stmt = $usePdo->query($sql);
                            $result = $stmt->fetchColumn();
                            return $result !== false ? $result : 0; 
                        } catch (Exception $e) {
                            global $response;
                            $response['logs'][] = "SQL Hatası: " . $e->getMessage() . " | SQL: $sql";
                            return 0;
                        }
                     }
                 }
            }
            
            // Eğer parse edilemezse logla
            global $response;
            $response['logs'][] = "Manual Parse Failed! Expr: " . $expression;
            return 0;
        }

        /**
         * VAL() fonksiyonunu simüle eder
         */
        public function evaluateVal($expr, $productCode) {
            // İçindeki _SQLINFO'yu bul ve çalıştır
            if (strpos($expr, '_SQLINFO') !== false) {
                return floatval($this->executeSqlInfo($expr, $productCode));
            }
            return 0;
        }

        /**
         * Tek bir ürün için en iyi iskontoyu hesaplar
         */
        public function calculateDiscount($productCode, $quantity) {
            $bestDiscount = 0;
            $appliedCampaignName = null;

            foreach ($this->campaigns as $camp) {
                // Debug log
                global $response; 
                $response['logs'][] = "Kampanya: {$camp['NAME']} - V1: {$camp['VARIABLEDEFS1']}";

                // Bu kampanya bu ürün için geçerli mi?
                // Değişkenleri Analiz Et
                
                // V1: Ürün Listede Var mı? (COUNT > 0)
                $var1 = $camp['VARIABLEDEFS1']; // VAL(_SQLINFO("COUNT(*)",...))
                $isInList = $this->evaluateVal($var1, $productCode);
                
                $response['logs'][] = "Ürün: $productCode - V1 Sonuç: $isInList";
                
                if ($isInList > 0) {
                    // V2: İskonto Oranı
                    $var2 = $camp['VARIABLEDEFS2']; // _SQLINFO("ORAN",...)
                    // Burada VAL yok, direkt string dönebilir, executeSqlInfo sayısal da dönebilir.
                    $discountRate = (float)$this->executeSqlInfo($var2, $productCode);
                    
                    // V3: Min Miktar
                    $var3 = $camp['VARIABLEDEFS3']; // VAL(_SQLINFO("MIN_MIKTAR",...)
                    $minQty = (float)$this->evaluateVal($var3, $productCode);
                    
                    // KOŞULLAR
                    // 1. Ürün listede var (isInList > 0) - Zaten if içinde
                    // 2. Miktar yeterli (quantity >= minQty)
                    
                    if ($quantity >= $minQty && $discountRate > 0) {
                        // Kampanya uygulanabilir!
                        // En iyi iskontoyu al (veya Logo mantığına göre öncelik?? Şimdilik en yükseği alıyoruz)
                        if ($discountRate > $bestDiscount) {
                            $bestDiscount = $discountRate;
                            $appliedCampaignName = $camp['NAME'];
                        }
                    }
                }
            }
            
            return ['rate' => $bestDiscount, 'campaign' => $appliedCampaignName];
        }
    }

    $calculator = new LogoCampaignCalculator($pdo, $pdoTiger);
    $campCount = $calculator->loadCampaigns($clientCode);
    apiLog("$campCount adet aktif kampanya yüklendi.");

    // 4. Gelistirilmis Kampanya Mantigi (Gruplama ile)
    
    // Adim 1: Her kampanya icin toplam miktarlari ve eslesen urunleri bul
    $campaignTotals = []; // [CampIndex => TotalQty]
    $itemMatches = [];    // [ProductCode => [CampIndices]]

    foreach ($cartItems as $item) {
        $pCode = $item['productCode'] ?? ($item['code'] ?? '');
        $qty = floatval($item['quantity'] ?? ($item['amount'] ?? 0));
        
        if (empty($pCode)) continue;
        
        // Her kampanya icin kontrol et
        foreach ($calculator->campaigns as $index => $camp) {
            // V1: Ürün Listede Var mı?
            $var1 = $camp['VARIABLEDEFS1']; 
            $isInList = $calculator->evaluateVal($var1, $pCode);
            
            if ($isInList > 0) {
                // Eslesti
                if (!isset($campaignTotals[$index])) {
                    $campaignTotals[$index] = 0;
                }
                $campaignTotals[$index] += $qty;
                
                $itemMatches[$pCode][] = $index;
            }
        }
    }

    // Adim 2: Her urun icin TÜM geçerli kampanyaları bul (CASCADE DISCOUNTS)
    foreach ($cartItems as $item) {
        $pCode = $item['productCode'] ?? ($item['code'] ?? '');
        
        if (empty($pCode) || empty($itemMatches[$pCode])) continue;

        $applicableCampaigns = []; // Her kampanya için: [priority, rate, name]

        foreach ($itemMatches[$pCode] as $campIndex) {
            $camp = $calculator->campaigns[$campIndex];
            $totalGroupQty = $campaignTotals[$campIndex];
            
            // V3: Min Miktar
            $var3 = $camp['VARIABLEDEFS3']; 
            $minQty = (float)$calculator->evaluateVal($var3, $pCode);

            if ($totalGroupQty >= $minQty) {
                // V2: Iskonto Orani
                $var2 = $camp['VARIABLEDEFS2'];
                $discountRate = (float)$calculator->executeSqlInfo($var2, $pCode);
                
                if ($discountRate > 0) {
                    // Tüm geçerli kampanyaları topla
                    $applicableCampaigns[] = [
                        'priority' => $camp['PRIORITY'] ?? 0,
                        'rate' => $discountRate,
                        'name' => $camp['NAME'],
                        'total_qty' => $totalGroupQty
                    ];
                }
            }
        }
        
        // Kampanyaları priority'ye göre sırala (yüksekten düşüğe)
        usort($applicableCampaigns, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        
        if (!empty($applicableCampaigns)) {
            // Cascade format oluştur
            $rates = array_map(function($c) { return $c['rate']; }, $applicableCampaigns);
            $names = array_map(function($c) { return $c['name']; }, $applicableCampaigns);
            $displayFormat = implode('-', array_map(function($r) { 
                return number_format($r, 2, ',', ''); 
            }, $rates));
            
            // Kümülatif toplam iskonto hesapla (Logo mantığı: 100 -> %15 = 85 -> %5 = 80.75)
            $cumulativePrice = 100;
            foreach ($rates as $rate) {
                $cumulativePrice = $cumulativePrice * (1 - ($rate / 100));
            }
            $totalDiscount = 100 - $cumulativePrice; // Toplam iskonto yüzdesi
            
            // Yeni response formatı
            $response['discounts'][$pCode] = [
                'rates' => $rates,                    // [15, 5]
                'display' => $displayFormat,          // "15,00-5,00"
                'campaigns' => $names,                // ['Kampanya 1', 'Ana Bayi']
                'total' => round($totalDiscount, 2)   // 19.25
            ];
            
            // Backward compatibility için düz rate de ekle
            $response['applied_campaigns'][$pCode] = implode(' + ', $names);
            
            apiLog("Ürün: $pCode -> Cascade: " . $displayFormat . " (Toplam: %" . round($totalDiscount, 2) . ") - Kampanyalar: " . implode(', ', $names));
        }
    }

    $response['success'] = true;

} catch (Exception $e) {
    if (isset($pdo) && $pdo) {
        $pdo = null;
    }
    $response['success'] = false;
    $response['message'] = $e->getMessage(); // Frontend bu alanı bekliyor
    apiLog("HATA: " . $e->getMessage());
}




$response['items_recieved_count'] = count($input['cartItems'] ?? $input['items'] ?? []);

// DEBUG LOGGING TO FILE
$logEntry = "--- " . date('Y-m-d H:i:s') . " ---\n";
// Olası output buffer içeriğini de loga ekle (Hata var mı diye)
$bufferedOutput = ob_get_contents();
if ($bufferedOutput) {
    $logEntry .= "BUFFERED_OUTPUT: " . $bufferedOutput . "\n";
    ob_clean(); // Bufferı temizle ki JSON bozulmasın
}

$logEntry .= "INPUT: " . print_r($input, true) . "\n";
$logEntry .= "RESPONSE: " . print_r($response, true) . "\n";
file_put_contents(__DIR__ . '/debug_campaign_request.txt', $logEntry, FILE_APPEND);

echo json_encode($response);
?>
