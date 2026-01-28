<?php
// api/get_payment_delays.php
require_once "../include/fonksiyon.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['yonetici_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Increase Limits for heavy report
set_time_limit(1800); 
ini_set('memory_limit', '1024M');
ini_set('display_errors', 0);

// Connect to MERVE2 (Firm 565)
// User specifically requested MERVE2 data
merve2_veritabani();
if (!$GLOBALS['merve2_db']) {
    echo json_encode(['error' => 'MERVE2 veritabanına bağlanılamadı.']);
    exit;
}

$pdo = $GLOBALS['merve2_db'];
$firmNr = '565';
$period = '01'; // Default period, adjust if needed

$type = $_GET['type'] ?? '320';

// Determine SQL logic based on Type
if ($type === '120') {
    // Collection Delays (120 - Customers)
    // We are looking for UNCOLLECTED or LATE COLLECTED invoices.
    // Invoice is usually DEBIT (0) for Customers. Payment is CREDIT (1).
    $accountLike = '120%';
    $invoiceSign = 0; // Debit
    $invoiceCol = 'DEBIT'; // Use Debit Amount
    $paymentSign = 1; // Credit
    $paymentCol = 'CREDIT';
} else {
    // Payment Delays (320 - Suppliers - Default)
    // Invoice is CREDIT (1) for Suppliers. Payment is DEBIT (0).
    $accountLike = '320%';
    $invoiceSign = 1; // Credit
    $invoiceCol = 'CREDIT'; // Use Credit Amount
    $paymentSign = 0; // Debit
    $paymentCol = 'DEBIT';
}

// Simpler SQL using PAYTRANS (Borç Takip Modülü) where matching is already done
// Only fetch items with remaining balance > 0
$sql = "
    SELECT 
        CL.CODE AS ACCOUNTCODE,
        CL.DEFINITION_ AS ACCOUNTNAME,
        (PT.TOTAL - PT.PAID) AS OpenAmount,
        PT.PROCDATE AS DueDate, -- Vade Tarihi
        INV.DATE_ AS InvoiceDate,
        INV.FICHENO AS FicheNo,
        PT.LOGICALREF AS InvoiceId,
        DATEDIFF(DAY, PT.PROCDATE, GETDATE()) AS DelayDays
    FROM LG_{$firmNr}_01_PAYTRANS PT
    LEFT JOIN LG_{$firmNr}_01_INVOICE INV ON PT.FICHEREF = INV.LOGICALREF
    LEFT JOIN LG_{$firmNr}_CLCARD CL ON PT.CARDREF = CL.LOGICALREF
    WHERE PT.CANCELLED = 0
      AND (PT.TOTAL - PT.PAID) > 0.01 -- Filter fully paid
      AND PT.SIGN = {$invoiceSign} -- 0 for Debit (Receivables), 1 for Credit (Payables)
      AND PT.MODULENR = 4 -- Invoice Module
      -- Filter by Account Type
      AND CL.CODE LIKE '{$type}%'
    ORDER BY CL.CODE, PT.PROCDATE
";

// If Type 120, we need to adjust DueDate and DelayDays calculation in SQL or PHP?
// User said: "vade tarihi fatura tarihinden 60 gün sonrasını göstermesi gerekiyor"
// In PAYTRANS, PROCDATE is the DueDate. If checks are already matched, this date is fixed.
// BUT for report purposes, if the user wants to assume 60 days FROM INVOICE DATE for everything:

if ($type === '120') {
    $sql = "
    SELECT 
        CL.CODE AS ACCOUNTCODE,
        CL.DEFINITION_ AS ACCOUNTNAME,
        (PT.TOTAL - PT.PAID) AS OpenAmount,
        DATEADD(day, 60, INV.DATE_) AS DueDate, -- Override DueDate: InvoiceDate + 60
        INV.DATE_ AS InvoiceDate,
        INV.FICHENO AS FicheNo,
        PT.LOGICALREF AS InvoiceId,
        DATEDIFF(DAY, DATEADD(day, 60, INV.DATE_), GETDATE()) AS DelayDays -- Recalculate Delay
    FROM LG_{$firmNr}_01_PAYTRANS PT
    LEFT JOIN LG_{$firmNr}_01_INVOICE INV ON PT.FICHEREF = INV.LOGICALREF
    LEFT JOIN LG_{$firmNr}_CLCARD CL ON PT.CARDREF = CL.LOGICALREF
    WHERE PT.CANCELLED = 0
      AND (PT.TOTAL - PT.PAID) > 0.01
      AND PT.SIGN = 0 -- Customer Debit
      AND PT.MODULENR = 4
      AND CL.CODE LIKE '120%'
    ORDER BY CL.CODE, INV.DATE_
    ";
}

try {
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PHP Aggregation (Same as before)
    $groupedData = [];

    foreach ($rows as $row) {
        $code = $row['ACCOUNTCODE'];
        if (!isset($groupedData[$code])) {
            $groupedData[$code] = [
                'ACCOUNTCODE' => $code,
                'ACCOUNTNAME' => $row['ACCOUNTNAME'],
                'OCAK' => 0, 'SUBAT' => 0, 'MART' => 0, 'NISAN' => 0, 'MAYIS' => 0, 'HAZIRAN' => 0,
                'TEMMUZ' => 0, 'AGUSTOS' => 0, 'EYLUL' => 0, 'EKIM' => 0, 'KASIM' => 0, 'ARALIK' => 0,
                'GECIKME_0_30' => 0, 'GECIKME_31_60' => 0, 'GECIKME_61_90' => 0, 'GECIKME_90_PLUS' => 0,
                'TOPLAM_GECIKMIS' => 0,
                'GECIKME_BEDELI' => 0,
                'details' => []
            ];
        }

        $amt = (float)$row['OpenAmount'];
        $days = (int)$row['DelayDays'];
        // Logic: If delay < 0, it means it is NOT delayed yet (future due date).
        // Should we show it? The table is "Delay Report". Usually we only show > 0.
        // User asked for "Geciken faturalar".
        
        if ($days <= 0) continue; // Skip if not delayed

        $dueDate = $row['DueDate'];
        // Use DueDate month for aging columns? Or do we place it in the bucket?
        // Usually Monthly columns = "Payment Expected In Month X" or "Invoice Date Month X"?
        // Standard aging is based on Due Date.
        $month = (int)date('m', strtotime($dueDate));

        // Add to details
        $groupedData[$code]['details'][] = [
            'InvoiceDate' => $row['InvoiceDate'],
            'DueDate' => $dueDate,
            'FicheNo' => $row['FicheNo'],
            'Amount' => $amt,
            'DelayDays' => $days
        ];

        // Aggregations
        $months = [1=>'OCAK', 2=>'SUBAT', 3=>'MART', 4=>'NISAN', 5=>'MAYIS', 6=>'HAZIRAN', 
                   7=>'TEMMUZ', 8=>'AGUSTOS', 9=>'EYLUL', 10=>'EKIM', 11=>'KASIM', 12=>'ARALIK'];
        if (isset($months[$month])) {
            $groupedData[$code][$months[$month]] += $amt;
        }

        // Delay Buckets
        if ($days <= 30) $groupedData[$code]['GECIKME_0_30'] += $amt;
        elseif ($days <= 60) $groupedData[$code]['GECIKME_31_60'] += $amt;
        elseif ($days <= 90) $groupedData[$code]['GECIKME_61_90'] += $amt;
        else $groupedData[$code]['GECIKME_90_PLUS'] += $amt;

        // Total Delayed & Penalty
        // "Gecikmiş" includes everything with delay > 0 ?
        // Or strictly > 60?? User's previous logic in cursor implied > 60 for "TOPLAM_GECIKMIS".
        // Let's keep it > 60 to allow Penalty calculation to be consistent.
        
        // Wait, standard report "Toplam Gecikmiş" usually means ALL overdue.
        // But the 3% penalty logic was specifically for > 60 days (user rule).
        // Let's sum ALL > 0 into a Total Overdue? Or just stick to the specific columns?
        
        // Let's sum all positive delays for display logic if needed, but the array key 'TOPLAM_GECIKMIS'
        // was used for the "Total" column in datatable.
        // Let's set TOPLAM_GECIKMIS = sum of all buckets.
        $groupedData[$code]['TOPLAM_GECIKMIS'] += $amt;

        // Penalty only if > 60 days
        if ($days > 60) {
            $penaltyCycles = ceil(($days - 60) / 30.0);
            $penalty = $amt * ($penaltyCycles * 0.03);
            $groupedData[$code]['GECIKME_BEDELI'] += $penalty;
        }
    }
    
    echo json_encode([
        'success' => true,
        'source' => 'MERVE2 (Firm 565) - PAYTRANS',
        'data' => array_values($groupedData)
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'SQL Error: ' . $e->getMessage()]);
}
?>
