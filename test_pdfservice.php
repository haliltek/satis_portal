<?php
// test_pdfservice.php - PdfService ile test

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/fonk.php';
require_once __DIR__ . '/services/LoggerService.php';
require_once __DIR__ . '/services/PdfService.php';

$teklifId = 66;

echo "<h1>PdfService Testi - Teklif ID: {$teklifId}</h1>";

try {
    $logger = new LoggerService(__DIR__ . '/logs/pdf_test.log');
    echo "<p>Logger oluşturuldu ✓</p>";
    
    $pdfService = new PdfService($logger);
    echo "<p>PdfService oluşturuldu ✓</p>";
    
    echo "<h3>PDF Oluşturuluyor...</h3>";
    
    $pdfPath = $pdfService->createOfferPdf($teklifId, $db);
    
    if ($pdfPath && file_exists($pdfPath)) {
        $fileSize = filesize($pdfPath);
        echo "<p style='color:green; font-size: 16pt;'>✅ PDF başarıyla oluşturuldu!</p>";
        echo "<p>Dosya yolu: {$pdfPath}</p>";
        echo "<p>Dosya boyutu: " . number_format($fileSize) . " bytes</p>";
        echo "<p><a href='pdfs/" . basename($pdfPath) . "' target='_blank' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>PDF'i Görüntüle</a></p>";
    } else {
        echo "<p style='color:red; font-size: 14pt;'>PDF oluşturulamadı!</p>";
        echo "<p>Dönen değer: " . var_export($pdfPath, true) . "</p>";
        
        // Log dosyasını kontrol et
        $logFile = __DIR__ . '/logs/pdf_test.log';
        if (file_exists($logFile)) {
            echo "<h4>Son Log Kayıtları:</h4>";
            echo "<pre style='background: #f0f0f0; padding: 10px;'>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red; font-size: 14pt;'>HATA: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background: #fee; padding: 10px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
