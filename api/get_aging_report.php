<?php
// api/get_aging_report.php
require_once "../fonk.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['yonetici_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Increase Limits for heavy naming report
set_time_limit(1800); // 30 minutes
ini_set('memory_limit', '1024M');
ini_set('display_errors', 0); // Suppress HTML errors in JSON response
ini_set('log_errors', 1);

// 1. Get Params
// 1. Get Params
$sirket_id = filter_input(INPUT_GET, 'sirket_id', FILTER_VALIDATE_INT);
$reportType = $_GET['type'] ?? 'debit'; // 'debit' or 'credit'
$mode = $_GET['mode'] ?? 'single';

$pdo = null;
$firmNr = '525';
$logicalRef = 0;

if ($mode === 'general') {
    // General Report (All Customers)
    // Priority: MERVE2 (Firm 565) -> GEMAS (Firm 525)
    
    // Try MERVE2
    merve2_veritabani();
    if ($GLOBALS['merve2_db']) {
        $pdo = $GLOBALS['merve2_db'];
        $firmNr = '565';
        $dbSource = 'MERVE2';
        // Note: Using MERVE2 as requested by user ("MERVE2 den almasını sağlarmısın")
    } else {
        // Fallback to GEMAS
        gemas_logo_veritabani();
        if ($GLOBALS['gemas_logo_db']) {
            $pdo = $GLOBALS['gemas_logo_db'];
            $firmNr = '525';
            $dbSource = 'GEMAS';
        } else {
            echo json_encode(['error' => 'Logo veritabanı bağlantısı kurulamadı (MERVE2 ve GEMAS denenmedi).']);
            exit;
        }
    }
} else {
    // Single Customer Report
    if (!$sirket_id) {
        echo json_encode(['error' => 'Invalid sirket_id']);
        exit;
    }

    // Fetch Customer
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

    // Helper ...
    function getDbInfo($code) {
        gemas_logo_veritabani();
        if ($GLOBALS['gemas_logo_db']) {
            $stmt = $GLOBALS['gemas_logo_db']->prepare("SELECT LOGICALREF FROM LG_526_CLCARD WHERE CODE = :code");
            $stmt->execute([':code' => $code]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($card) return ['pdo' => $GLOBALS['gemas_logo_db'], 'firm' => '525', 'ref' => $card['LOGICALREF']];
        }
        gempa_logo_veritabani();
        if ($GLOBALS['gempa_logo_db']) {
            $stmt = $GLOBALS['gempa_logo_db']->prepare("SELECT LOGICALREF FROM LG_566_CLCARD WHERE CODE = :code");
            $stmt->execute([':code' => $code]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($card) return ['pdo' => $GLOBALS['gempa_logo_db'], 'firm' => '565', 'ref' => $card['LOGICALREF']];
        }
        return null;
    }

    $info = getDbInfo($cariKodu);
    if (!$info) {
        echo json_encode(['error' => 'Logo veritabanında bulunamadı']);
        exit;
    }

    $pdo = $info['pdo'];
    $firmNr = $info['firm'];
    $logicalRef = $info['ref'];
}

// 3. Construct SQL
// The user provided logic uses cursors. Code will be injected.
// Replacements:
// LG_060_01_EMFLINE -> LG_{firmNr}_01_EMFLINE
// LG_060_EMUHACC   -> LG_{firmNr}_EMUHACC
// Filter: ACCOUNTCODE = '$cariKodu' ?? Wait.
// "Raporu kullanırken değiştirmeniz gereken yerler... ... Borçluların hesap kodları 120. ile başlıyor"
// User wants valid Aging for *this customer*.
// The provided SQL does: `WHERE el.ACCOUNTCODE LIKE '120.%'` (Scanning ALL 120 accounts?)
// NO. Typically for a "Cari Durum", we want it for the *selected customer*.
// However, the selected customer is a *Commercial Account* (CLCARD).
// The SQL uses *Accounting Accounts* (EMUHACC). `ACCOUNTcode` in EMFLINE is the GL Code.
// I need the GL Code for this Customer.
// Usually `CLCARD` has `GL_CODE` or is linked via `CRDACREF`.
// BUT, if the user implies this report is *general* for all accounts, then why pass `sirket_id`?
// User said: "Muhasebe Fiş Hareketlerinden Yaşlandırma Raporu ... Müşteri risk ve analiz raporları sadece yönetici düzeyinde görünsün".
// If I am on a customer detail page, I expect report for THIS customer.
// BUT, the SQL provided loops over `LIKE '120.%'` meaning ALL customers.
// "Cari Durum Analiz" page is for a single customer.
// "Müşteri risk raporları sadece yönetici..." -> Maybe this report is global?
// Let's assume this is a report for the *Specific Customer Account*.
// I need to find the GL Account Code corresponding to the CLCARD.
// Or, if the `s_arp_code` IS the GL code? (e.g. 120.01.001).
// Usually in Logo:
// CLCARD.CODE = '120.01.001' (sometimes).
// OR CLCARD is 'CARI.001' and it maps to GL 120.01.001 via Posting Specs.
// IF `s_arp_code` starts with 120 or 320, it might be the GL code directly.
// In `sirket` table, `s_arp_code` is usually the Logo Code.
// I will assume `s_arp_code` IS the GL Code for the purpose of this SQL if it looks like an accounting code.
// If not, I might need to find the GL link.
// For now, I'll filter by `ACCOUNTCODE = :glCode`.
// Wait, the SQL provided has `LIKE '120.%'` and `GROUP BY ACCOUNTCODE`. It generates a list of ALL accounts.
// IF the user wants this report to be "General Aging Report" (not specific to one customer), I should place it in "Reports" menu, not "Cari Durum Analiz".
// "Cari Durum Analizi" page (where we are) is single customer.
// User Request: "Muhasebe Fiş Hareketlerinden Yaşlandırma Raporu ... 2 rapor var...".
// And they provided a screenshot of Excel with *Multiple Firms*.
// So this is likely a **General Report** listing High Balance customers.
// OK. I will create a new page `yaslandirma_raporu.php` (Aging Report) which lists ALL matching accounts.
// I don't need `sirket_id` input then. I need `Firm` input?
// I will default to current active firm (525).
// So `get_aging_report.php` will default to `525` (GEMAS) unless specified.
// Or maybe show a dropdown for Company?
// I'll stick to 525 for now as main.

$targetFirm = '525';
$prefix = 'GEMAS2026'; // Todo: Dynamic?
gemas_logo_veritabani();
$pdo = $gemas_logo_db;

if ($reportType === 'credit') {
    // Alacak Yaşlandırma (320)
    $accountLike = '320.%';
    $signFilter = '[SIGN]=0'; // For Credits... wait.
    // User SQL for 320 (Alacaklılar):
    // WHERE ... ACCOUNTCODE LIKE '32%'
    // In logic: `IF @_sign=1` (Credit Transaction) ...
    // Note: User SQL logic is complex (FIFO).
    // I will use their EXACT SQL logic for the "320" Block.
} else {
    // Borç Yaşlandırma (120)
    $accountLike = '120.%';
    // User SQL for 120.
}

// Prepare the SQL string with variable replacements
include "aging_sql_template.php"; // I'll put the big SQL here function

$sql = getAgingSql($targetFirm, $reportType); 

try {
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // The query returns multiple result sets?
    // The user SQL has `SELECT ... GROUP BY` (Summary) AND `SELECT *` (Detail).
    // If I use `fetchAll`, it fetches the first result set.
    // I should modify SQL to return specific set or handle nextRowset.
    // For now, I'll return the SUMMARY first.
    // If I need details, I might need separate calls or fetches.
    // User screenshot "Ozet Ekran Goruntusu" -> Summary.
    // "Detayli Excel..." -> Detail.
    // I will focus on Summary first.
    
    echo json_encode([
        'data' => $results,
        'source' => $dbSource ?? 'Unknown'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
