<?php
// api/get_overdue_invoices.php
require_once "../fonk.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['yonetici_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sirket_id = filter_input(INPUT_GET, 'sirket_id', FILTER_VALIDATE_INT);
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

// Helper function to fetch invoices
function fetchInvoices($pdo, $firmNr, $code) {
    if (!$pdo) return null;
    
    // Check if card exists
    $stmt = $pdo->prepare("SELECT LOGICALREF FROM LG_{$firmNr}_CLCARD WHERE CODE = :code");
    $stmt->execute([':code' => $code]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$card) return null; // Not in this DB

    $sql = "
    SELECT
        INV.FICHENO AS [Fatura No],
        CL.CODE AS [Cari Kodu],
        CL.DEFINITION_ AS [Cari Ünvanı],
        PT.TOTAL,
        PT.PAID,
        (PT.TOTAL - PT.PAID) AS [Kalan Bakiye],
        CONVERT(VARCHAR, INV.DATE_, 104) AS [Fatura Tarihi],
        CONVERT(VARCHAR, PT.PROCDATE, 104) AS [Vade Tarihi],
        DATEDIFF(DAY, PT.PROCDATE, GETDATE()) AS [Geçen Gün Sayısı]
    FROM
        LG_{$firmNr}_01_PAYTRANS AS PT WITH(NOLOCK) 
    LEFT JOIN
        LG_{$firmNr}_01_INVOICE AS INV WITH(NOLOCK) ON PT.FICHEREF = INV.LOGICALREF 
    LEFT JOIN
        LG_{$firmNr}_CLCARD AS CL WITH(NOLOCK) ON PT.CARDREF = CL.LOGICALREF 
    WHERE
        PT.MODULENR = 4              
        AND PT.SIGN = 0              
        AND PT.CANCELLED = 0        
        AND (PT.TOTAL - PT.PAID) > 0 
        AND DATEDIFF(DAY, PT.PROCDATE, GETDATE()) > 60 
        AND CL.CODE = :code
    ORDER BY
        DATEDIFF(DAY, PT.PROCDATE, GETDATE()) DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':code' => $code]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Try Gemas (525)
gemas_logo_veritabani();
global $gemas_logo_db;
$data = fetchInvoices($gemas_logo_db, '525', $cariKodu);

if ($data !== null) {
    echo json_encode(['success' => true, 'source' => 'Gemas 525', 'data' => $data]);
    exit;
}

// Try Gempa (565)
gempa_logo_veritabani();
global $gempa_logo_db;
$data = fetchInvoices($gempa_logo_db, '565', $cariKodu);

if ($data !== null) {
    echo json_encode(['success' => true, 'source' => 'Gempa 565', 'data' => $data]);
    exit;
}

echo json_encode(['error' => 'Cari kod Logo veritabanlarında bulunamadı']);
