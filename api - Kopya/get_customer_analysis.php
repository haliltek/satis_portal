<?php
// api/get_customer_analysis.php
require_once "../fonk.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['yonetici_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sirket_id = filter_input(INPUT_GET, 'sirket_id', FILTER_VALIDATE_INT);
$type = $_GET['type'] ?? 'turnover'; // Analiz türü

if (!$sirket_id) {
    echo json_encode(['error' => 'Invalid sirket_id']);
    exit;
}

// 1. Get Code from Local DB
$stmt = $db->prepare("SELECT s_arp_code FROM sirket WHERE sirket_id = ?");
$stmt->bind_param("i", $sirket_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row || empty($row['s_arp_code'])) {
    echo json_encode(['error' => 'Cari kod bulunamadı']);
    exit;
}
$cariKodu = $row['s_arp_code'];

// Helper to determine DB and CardRef
function getDbConnectionAndRef($code) {
    global $gemas_logo_db, $gempa_logo_db;
    
    // Try Gempa 2026 (566) FIRST - most common
    gempa_logo_veritabani();
    if ($gempa_logo_db) {
        $stmt = $gempa_logo_db->prepare("SELECT LOGICALREF FROM LG_566_CLCARD WHERE CODE = :code");
        $stmt->execute([':code' => $code]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($card) {
            return ['pdo' => $gempa_logo_db, 'firm' => '566', 'ref' => $card['LOGICALREF']];
        }
        
        // Try Gempa 2025 (565) in same DB
        $stmt = $gempa_logo_db->prepare("SELECT LOGICALREF FROM LG_565_CLCARD WHERE CODE = :code");
        $stmt->execute([':code' => $code]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($card) {
            return ['pdo' => $gempa_logo_db, 'firm' => '565', 'ref' => $card['LOGICALREF']];
        }
    }

    // Try Gemas 2026 (526)
    gemas_logo_veritabani();
    if ($gemas_logo_db) {
        $stmt = $gemas_logo_db->prepare("SELECT LOGICALREF FROM LG_526_CLCARD WHERE CODE = :code");
        $stmt->execute([':code' => $code]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($card) {
            return ['pdo' => $gemas_logo_db, 'firm' => '526', 'ref' => $card['LOGICALREF']];
        }
        
        // Try Gemas 2025 (525) in same DB
        $stmt = $gemas_logo_db->prepare("SELECT LOGICALREF FROM LG_525_CLCARD WHERE CODE = :code");
        $stmt->execute([':code' => $code]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($card) {
            return ['pdo' => $gemas_logo_db, 'firm' => '525', 'ref' => $card['LOGICALREF']];
        }
    }

    return null;
}

$info = getDbConnectionAndRef($cariKodu);
if (!$info) {
    echo json_encode(['error' => 'Cari Logo veritabanlarında bulunamadı']);
    exit;
}

$pdo = $info['pdo'];
$firmNr = $info['firm'];
$clientRef = $info['ref'];

// --- TURNOVER ANALYSIS (Ciro) ---
// --- TURNOVER ANALYSIS (Ciro) ---
if ($type === 'turnover') {
    // Determine DB Prefix (GEMAS or GEMPA)
    // Firm 525 -> GEMAS, Firm 565 -> GEMPA
    // Simple heuristic: 52x -> GEMAS, 56x -> GEMPA
    $dbPrefix = 'GEMAS';
    if (substr((string)$firmNr, 0, 2) === '56') {
        $dbPrefix = 'GEMPA';
    }

    $currentYear = (int)date('Y');
    $mergedRaw = [];
    
    // Debug logging
    error_log("Turnover API - Cari: $cariKodu, Firm: $firmNr, DB Prefix: $dbPrefix, Current Year: $currentYear");

    // Loop current year and previous 2 years
    // Each year has its own database: GEMAS2026, GEMAS2025, GEMAS2024
    // Each database has only that year's tables: LG_526, LG_525, LG_524
    for ($i = 0; $i < 3; $i++) {
        $targetYear = $currentYear - $i;
        $targetFirm = (int)$firmNr - $i;
        $targetDb   = $dbPrefix . $targetYear; // e.g. GEMAS2026, GEMAS2025, GEMAS2024
        
        // Define full table paths for Cross-DB Query
        // Format: [DBName].[dbo].[TableName]
        $clcardTable  = "[{$targetDb}].[dbo].[LG_" . sprintf('%03d', $targetFirm) . "_CLCARD]";
        $invoiceTable = "[{$targetDb}].[dbo].[LG_" . sprintf('%03d', $targetFirm) . "_01_INVOICE]";

        try {
            // 1. Find ClientRef in Target DB
            $sqlRef = "SELECT LOGICALREF FROM {$clcardTable} WITH(NOLOCK) WHERE CODE = :code";
            $stmtRef = $pdo->prepare($sqlRef);
            $stmtRef->execute([':code' => $cariKodu]);
            $cardRow = $stmtRef->fetch(PDO::FETCH_ASSOC);

            if ($cardRow) {
                $tRef = $cardRow['LOGICALREF'];

                // 2. Fetch Invoices from Target DB
                $sqlInv = "
                SELECT 
                    YEAR(DATE_) as [Yil],
                    MONTH(DATE_) as [Ay],
                    SUM(NETTOTAL) as [ToplamTutar]
                FROM {$invoiceTable} WITH(NOLOCK)
                WHERE CLIENTREF = :ref
                  AND TRCODE IN (7, 8) 
                  AND CANCELLED = 0
                GROUP BY YEAR(DATE_), MONTH(DATE_)
                ";
                
                $stmtInv = $pdo->prepare($sqlInv);
                $stmtInv->execute([':ref' => $tRef]);
                $rows = $stmtInv->fetchAll(PDO::FETCH_ASSOC);
                
                if($rows) {
                    $mergedRaw = array_merge($mergedRaw, $rows);
                }
            }
        } catch (PDOException $e) {
            // Database or table doesn't exist
            error_log("Turnover Cross-DB Error ($targetDb, Firm $targetFirm): " . $e->getMessage());
            // Continue to next year
        }
    }

    // Process Merged Data
    $grouped = [];
    $years = [];

    foreach ($mergedRaw as $r) {
        $y = $r['Yil'];
        $m = $r['Ay'];
        $total = (float)$r['ToplamTutar'];

        if (!isset($grouped[$y])) {
            $grouped[$y] = [
                'year' => $y,
                'total' => 0,
                'months' => []
            ];
            $years[] = $y;
        }
        
        $grouped[$y]['total'] += $total;
        $grouped[$y]['months'][] = [
            'month' => $m,
            'total' => $total
        ];
    }
    
    // Sort Years Descending (2025 -> 2023)
    rsort($years);

    $result = [];
    foreach ($years as $y) {
        // Sort months ascending for chart
        usort($grouped[$y]['months'], function($a, $b) {
            return $a['month'] - $b['month'];
        });
        $result[] = $grouped[$y];
    }

    echo json_encode([
        'success' => true, 
        'data' => $result, 
        'currency' => 'TL',
        'debug' => [
            'cariKodu' => $cariKodu,
            'firmNr' => $firmNr,
            'dbPrefix' => $dbPrefix,
            'currentYear' => $currentYear,
            'queriedYears' => [$currentYear, $currentYear - 1, $currentYear - 2],
            'rawDataCount' => count($mergedRaw)
        ]
    ]);
}



// --- OVERDUE INVOICES (Geçikmiş Faturalar > 60 Gün) ---
if ($type === 'overdue') {
    try {
        // MERVE2 connection
        global $merve2_db;
        merve2_veritabani();
        
        // Use MERVE2 if available, otherwise fallback to standard logic
        $targetPdo = $merve2_db ?? $pdo;
        
        // If using MERVE2, we need to find the client reference in MERVE2
        $refToUse = $clientRef;
        $firmNrToUse = '565'; // MERVE2 uses LG_565

        if ($merve2_db) {
            // Find LogicalRef in MERVE2
            $stmtRef = $merve2_db->prepare("SELECT LOGICALREF FROM LG_{$firmNrToUse}_CLCARD WHERE CODE = :code");
            $stmtRef->execute([':code' => $cariKodu]);
            $cardRow = $stmtRef->fetch(PDO::FETCH_ASSOC);
            if ($cardRow) {
                $refToUse = $cardRow['LOGICALREF'];
            } else {
                 // Return empty if not found in MERVE2 as per specific request
                 echo json_encode(['success' => true, 'source' => 'MERVE2 (Not Found)', 'data' => []]);
                 exit;
            }
        } 
        
        $sql = "
        SELECT
            INV.FICHENO AS [Fatura No],
            CONVERT(VARCHAR, INV.DATE_, 104) AS [Fatura Tarihi],
            CONVERT(VARCHAR, PT.PROCDATE, 104) AS [Vade Tarihi],
            DATEDIFF(DAY, PT.PROCDATE, GETDATE()) AS [Geçen Gün],
            PT.TOTAL AS [Tutar],
            PT.PAID AS [Odenen],
            (PT.TOTAL - PT.PAID) AS [Kalan]
        FROM
            LG_{$firmNrToUse}_01_PAYTRANS AS PT WITH(NOLOCK) 
        LEFT JOIN
            LG_{$firmNrToUse}_01_INVOICE AS INV WITH(NOLOCK) ON PT.FICHEREF = INV.LOGICALREF 
        WHERE
            PT.CARDREF = :ref
            AND PT.MODULENR = 4              
            AND PT.SIGN = 0              
            AND PT.CANCELLED = 0        
            AND (PT.TOTAL - PT.PAID) > 0.01 
            AND DATEDIFF(DAY, PT.PROCDATE, GETDATE()) > 60 
        ORDER BY
            DATEDIFF(DAY, PT.PROCDATE, GETDATE()) DESC
        ";

        // If fallback to original PDO (gemas/gempa), modify firmNr accordingly
        if (!$merve2_db) {
             $firmNrToUse = $firmNr;
             $refToUse = $clientRef;
             // Reconstruct SQL with correct firmNr
             $sql = str_replace("LG_566_", "LG_{$firmNr}_", $sql);
        }

        $stmt = $targetPdo->prepare($sql);
        $stmt->execute([':ref' => $refToUse]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'source' => ($merve2_db ? 'MERVE2' : 'Standard'), 'data' => $results]);

    } catch (PDOException $e) {
        error_log("Logo/MERVE2 SQL Error: " . $e->getMessage());
        echo json_encode(['error' => 'Veri hatası: ' . $e->getMessage()]);
    }
}

// --- RETURN INVOICES (İade Edilen Ürünler) ---
if ($type === 'returns') {
    try {
        $sql = "
        SELECT 
            INV.DATE_ as [Tarih],
            INV.FICHENO as [FisNo],
            ITM.NAME as [UrunAdi],
            ITM.CODE as [UrunKodu],
            STL.AMOUNT as [Adet],
            STL.LINENET as [Tutar]
        FROM LG_{$firmNr}_01_STLINE STL WITH(NOLOCK)
        LEFT JOIN LG_{$firmNr}_ITEMS ITM WITH(NOLOCK) ON STL.STOCKREF = ITM.LOGICALREF
        LEFT JOIN LG_{$firmNr}_01_INVOICE INV WITH(NOLOCK) ON STL.INVOICEREF = INV.LOGICALREF
        WHERE STL.CLIENTREF = :ref
          AND STL.TRCODE IN (2, 3) 
          AND STL.CANCELLED = 0
          AND STL.LINETYPE = 0
        ORDER BY INV.DATE_ DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ref' => $clientRef]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format dates & numbers
        foreach ($rows as &$r) {
            $r['Tarih'] = isset($r['Tarih']) ? date('d.m.Y', strtotime($r['Tarih'])) : '-';
            $r['Tutar'] = (float)$r['Tutar'];
            $r['Adet'] = (float)$r['Adet'];
        }

        echo json_encode(['success' => true, 'data' => $rows]);

    } catch (PDOException $e) {
        error_log("Logo Returns SQL Error: " . $e->getMessage());
        echo json_encode(['error' => 'Veri hatası']);
    }
}

// --- FINANCIALS (Risk, Bakiye, Tahsilat, Ticari Bilgiler) ---
if ($type === 'financials') {
    try {
        // 1. Get Manual Data & Balance from MySQL 'sirket' table
        $sirketStmt = $db->prepare("SELECT credit_limit, acikhesap, ciro_hedefi, anlasilan_iskonto, ozel_risk_notu FROM sirket WHERE sirket_id = ?");
        $sirketStmt->bind_param("i", $sirket_id);
        $sirketStmt->execute();
        $res = $sirketStmt->get_result();
        $sirketRow = $res->fetch_assoc();
        $sirketStmt->close();

        $riskLimit = 0;
        $guncelBakiye = 0;
        $ciroHedefi = 0;
        $anlasilanIskonto = 0;
        $ozelRiskNotu = "";

        if ($sirketRow) {
            // Clean formatting (remove commas if numeric string)
            $riskLimit = floatval(str_replace(',', '', $sirketRow['credit_limit'] ?? '0'));
            $guncelBakiye = floatval(str_replace(',', '', $sirketRow['acikhesap'] ?? '0'));
            $ciroHedefi = floatval($sirketRow['ciro_hedefi'] ?? 0);
            $anlasilanIskonto = floatval($sirketRow['anlasilan_iskonto'] ?? 0);
            $ozelRiskNotu = $sirketRow['ozel_risk_notu'] ?? "";
        }

        // 2. Yearly Collection from Logo (CLFLINE)
        $yillikTahsilat = 0;
        try {
            $currentYear = date('Y');
            $firstDay = "$currentYear-01-01";
            $lastDay = date('Y-12-31');

            // TRCODE Filter for Collections:
            // 1: Nakit Tahsilat
            // 20: Gelen Havale
            // 61: Çek Tahsili
            // 62: Senet Tahsili
            // 70: Kredi Kartı Fişi
            // Excludes Sales Returns (32, 33) and Opening (14)
            
            $sqlColl = "
                SELECT SUM(AMOUNT) as YILLIKTAHSILAT 
                FROM LG_{$firmNr}_01_CLFLINE WITH(NOLOCK)
                WHERE CLIENTREF = :clientRef 
                  AND DATE_ >= :d1 
                  AND DATE_ <= :d2 
                  AND SIGN = 1 
                  AND CANCELLED = 0
                  AND TRCODE IN (1, 20, 61, 62, 70)
            ";
            
            $stmtColl = $pdo->prepare($sqlColl);
            $stmtColl->execute([
                ':clientRef' => $clientRef,
                ':d1' => $firstDay,
                ':d2' => $lastDay
            ]);
            $rowColl = $stmtColl->fetch(PDO::FETCH_ASSOC);
            if ($rowColl) {
                $yillikTahsilat = (float)$rowColl['YILLIKTAHSILAT'];
            }
        } catch (PDOException $e) {
            error_log("Logo Collection Error: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true, 
            'data' => [
                'RiskLimiti' => $riskLimit,
                'GuncelBakiye' => $guncelBakiye,
                'BuYilTahsilat' => $yillikTahsilat, // Renamed key
                'CiroHedefi' => $ciroHedefi,
                'AnlasilanIskonto' => $anlasilanIskonto,
                'OzelRiskNotu' => $ozelRiskNotu
            ]
        ]);

    } catch (Exception $e) {
        error_log("Financials Error: " . $e->getMessage());
        echo json_encode(['error' => 'Veri hatası: ' . $e->getMessage()]);
    }
}

// --- TOP PRODUCTS (En Çok Alınan Ürünler) ---
if ($type === 'top_products') {
    try {
        $sql = "
        SELECT TOP 10
            ITM.CODE AS [UrunKodu],
            ITM.NAME AS [UrunAdi],
            SUM(STL.AMOUNT) AS [ToplamAdet],
            SUM(STL.LINENET) AS [ToplamTutar]
        FROM LG_{$firmNr}_01_STLINE AS STL WITH(NOLOCK)
        LEFT JOIN LG_{$firmNr}_ITEMS AS ITM WITH(NOLOCK) ON STL.STOCKREF = ITM.LOGICALREF
        WHERE
            STL.CLIENTREF = :ref
            AND STL.TRCODE IN (7, 8) -- 7: Perakende, 8: Toptan Satış
            AND STL.LINETYPE = 0     -- Sadece Malzemeler
            AND STL.CANCELLED = 0
            AND STL.BILLED = 1       -- Faturalanmış
        GROUP BY
            ITM.CODE, ITM.NAME
        ORDER BY
            SUM(STL.LINENET) DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ref' => $clientRef]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $results]);

    } catch (PDOException $e) {
        error_log("Logo SQL Error: " . $e->getMessage());
        echo json_encode(['error' => 'Veri hatası: ' . $e->getMessage()]);
    }
}
// --- STATEMENT (Cari Hesap Ekstresi) ---
if ($type === 'statement') {
    try {
        // Tarih filtresi eklenebilir ama şimdilik tüm hareketleri çekiyoruz (User SP'si tarih aralığı alıyor)
        // Varsayılan olarak son 1 yıl veya 2 yıl çekilebilir. Şimdilik limitsiz (veya limitli) çekelim.
        // Hızlı olması için TOP 1000 koyabiliriz ama ekstrede bakiye tutması için tüm geçmişe veya devire ihtiyaç var.
        // Devir mantığı karışık olabilir, bu yüzden son 1-2 yılı çekip 'Öncesi Devir' hesaplamak en doğrusu.
        // Şimdilik sadece tüm hareketleri çekip PHP'de işleyelim (Performans izlenmeli).
        
        $sql = "
            SELECT 
                C.DATE_ as Tarih,
                C.FTIME as Saat,
                C.TRANNO as FisNo,
                C.TRCODE,
                C.MODULENR,
                C.LINEEXP as Aciklama,
                C.SIGN,
                C.AMOUNT as Tutar
            FROM LG_{$firmNr}_01_CLFLINE C WITH(NOLOCK)
            WHERE C.CLIENTREF = :ref
              AND C.CANCELLED = 0
            ORDER BY C.DATE_ ASC, C.FTIME ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ref' => $clientRef]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        $runningBalance = 0; // Borç - Alacak kümülatif

        foreach ($rows as $row) {
            $borc = ($row['SIGN'] == 0) ? (float)$row['Tutar'] : 0;
            $alacak = ($row['SIGN'] == 1) ? (float)$row['Tutar'] : 0;
            
            // Bakiye Hesabı: Borç(+), Alacak(-)
            $runningBalance += ($borc - $alacak);

            // Fiş Türü Mapping (SP'den alındı)
            $fisTuru = '';
            $trcode = (int)$row['TRCODE'];
            $modulenr = (int)$row['MODULENR'];

            if ($modulenr == 4) { // Fatura
                switch ($trcode) {
                    case 31: $fisTuru = 'Satınalma Faturası'; break;
                    case 32: $fisTuru = 'Perakende Satış İade Faturası'; break;
                    case 33: $fisTuru = 'Toptan Satış İade Faturası'; break;
                    case 34: $fisTuru = 'Alınan Hizmet Faturası'; break;
                    case 36: $fisTuru = 'Perakende Satış Faturası'; break; // SP'de eksik olabilir, eklendi
                    case 37: $fisTuru = 'Perakende Satış Faturası'; break; // SP'de 37 Perakende? (Genelde 37 Toptan değil mi? Kontrol et. Logo standart: 37 perakende, 38 toptan satış. SP: 37 Perakende, 38 Toptan)
                    case 38: $fisTuru = 'Toptan Satış Faturası'; break;
                    case 39: $fisTuru = 'Verilen Hizmet Faturası'; break;
                    case 43: $fisTuru = 'Satınalma Fiyat Farkı Faturası'; break;
                    case 44: $fisTuru = 'Satış Fiyat Farkı Faturası'; break;
                    case 56: $fisTuru = 'Müstahsil Makbuzu'; break;
                    default: $fisTuru = 'Fatura (' . $trcode . ')'; break;
                }
            } elseif ($modulenr == 7) { // Banka
                 switch ($trcode) {
                    case 20: $fisTuru = 'Gelen Havale'; break;
                    case 21: $fisTuru = 'Gönderilen Havale'; break;
                    case 24: $fisTuru = 'Döviz Alış Belgesi'; break;
                    case 25: $fisTuru = 'Döviz Satış Belgesi'; break;
                    case 28: $fisTuru = 'Alınan Hizmet Faturası (Banka)'; break; // SP: Alınan Hizmet Faturası
                    case 29: $fisTuru = 'Verilen Hizmet Faturası (Banka)'; break;
                    case 30: $fisTuru = 'Müstahsil Makbuzu (Banka)'; break; // SP: Müstahsil? 30 genelde farklı olabilir.
                    default: $fisTuru = 'Banka İşlemi (' . $trcode . ')'; break;
                 }
            } elseif ($modulenr == 6) { // Çek/Senet
                 switch ($trcode) {
                    case 61: $fisTuru = 'Çek Girişi'; break;
                    case 62: $fisTuru = 'Senet Girişi'; break;
                    case 63: $fisTuru = 'Çek Çıkış (Cari Hesaba)'; break;
                    case 64: $fisTuru = 'Senet Çıkış (Cari Hesaba)'; break;
                    default: $fisTuru = 'Çek/Senet (' . $trcode . ')'; break;
                 }
            } elseif (in_array($modulenr, [5, 10])) { // Kasa / Cari
                 switch ($trcode) {
                    case 1: $fisTuru = 'Nakit Tahsilat'; break;
                    case 2: $fisTuru = 'Nakit Ödeme'; break;
                    case 3: $fisTuru = 'Borç Dekontu'; break;
                    case 4: $fisTuru = 'Alacak Dekontu'; break;
                    case 5: $fisTuru = 'Virman Fişi'; break;
                    case 6: $fisTuru = 'Kur Farkı İşlemi'; break;
                    case 12: $fisTuru = 'Özel Fiş'; break;
                    case 14: $fisTuru = 'Açılış Fişi'; break;
                    case 41: $fisTuru = 'Verilen Vade Farkı Faturası'; break;
                    case 42: $fisTuru = 'Alınan Vade Farkı Faturası'; break;
                    case 45: $fisTuru = 'Verilen Serbest Meslek Makbuzu'; break;
                    case 46: $fisTuru = 'Alınan Serbest Meslek Makbuzu'; break;
                    case 70: $fisTuru = 'Kredi Kartı Fişi'; break;
                    case 71: $fisTuru = 'Kredi Kartı İade Fişi'; break;
                    case 72: $fisTuru = 'Firma Kredi Kartı Fişi'; break;
                    case 73: $fisTuru = 'Firma Kredi Kartı İade Fişi'; break;
                    default: $fisTuru = 'Cari İşlem (' . $trcode . ')'; break;
                 }
            } else {
                $fisTuru = 'Diğer (' . $modulenr . '-' . $trcode . ')';
            }

            // Durum (B/A)
            $durum = '';
            if ($runningBalance > 0.009) $durum = 'B'; // Borçlu
            elseif ($runningBalance < -0.009) $durum = 'A'; // Alacaklı (Parantez içinde gösterilebilir)
            
            $results[] = [
                'Tarih' => date('d.m.Y', strtotime($row['Tarih'])),
                'FisNo' => $row['FisNo'],
                'FisTuru' => $fisTuru,
                'Aciklama' => $row['Aciklama'],
                'Borc' => $borc,
                'Alacak' => $alacak,
                'Bakiye' => abs($runningBalance),
                'Durum' => $durum,
                'RawBakiye' => $runningBalance
            ];
        }

        // Sonuçları terse çevirip (en son işlem en üstte) mi gösterelim yoksa tarih sırasına göre mi?
        // Ekstre genelde Eskiden -> Yeniye gider. UI'da scroll en alta inebilir veya en üstte en yeni olabilir.
        // Kullanıcı "scrool ile kaydırabileyim" dedi, muhtemelen yeniye doğru. 
        // Ancak genelde en üstte en son işlemi görmek istenir.
        // Logo Ekstresi: Tarih Artan sıralı (Eskiden Yeniye).
        // Biz de Tarih Artan gönderdik. UI'da ters çevirebiliriz (reverse) eğer istenirse.
        // Şimdilik olduğu gibi bırakalım (Eskiden Yeniye).
        
        echo json_encode(['success' => true, 'data' => $results]);

    } catch (PDOException $e) {
        error_log("Statement Error: " . $e->getMessage());
        echo json_encode(['error' => 'Veri hatası: ' . $e->getMessage()]);
    }
}
?>
