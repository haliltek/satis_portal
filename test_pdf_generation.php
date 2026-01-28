<?php
// test_pdf_generation.php - Detaylı PDF oluşturma testi

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/fonk.php';
require_once __DIR__ . '/services/LoggerService.php';

// Mpdf yükle
require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;

$teklifId = 66; // Test teklif ID

echo "<h1>PDF Oluşturma Testi - Teklif ID: {$teklifId}</h1>";

try {
    // Önce teklif var mı kontrol et
    $stmt = $db->prepare("SELECT * FROM ogteklif2 WHERE id = ?");
    $stmt->bind_param("i", $teklifId);
    $stmt->execute();
    $teklif = $stmt->get_result()->fetch_assoc();
    
    if (!$teklif) {
        echo "<p style='color:red'>Teklif bulunamadı!</p>";
        exit;
    }
    echo "<p>Teklif bulundu: " . htmlspecialchars($teklif['teklifkodu']) . " ✓</p>";
    echo "<p>Müşteri: " . htmlspecialchars($teklif['musteriadi'] ?? 'Bilinmiyor') . "</p>";
    echo "<p>Tarih: " . htmlspecialchars($teklif['tekliftarihi'] ?? '') . "</p>";
    
    // Ürünleri kontrol et
    $stmt = $db->prepare("SELECT * FROM ogteklifurun2 WHERE teklifid = ? AND (transaction_type = 0 OR transaction_type IS NULL) ORDER BY id");
    $stmt->bind_param("i", $teklifId);
    $stmt->execute();
    $urunler = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<p>Ürün sayısı: " . count($urunler) . "</p>";
    
    if (count($urunler) > 0) {
        echo "<h3>Ürünler:</h3><table border='1'><tr><th>Kod</th><th>Adı</th><th>Liste</th><th>Miktar</th><th>Birim</th><th>İskonto</th><th>Döviz</th></tr>";
        foreach ($urunler as $u) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($u['kod'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($u['adi'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($u['liste'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($u['miktar'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($u['birim'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($u['iskonto'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($u['doviz'] ?? 'TL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>ÜRÜN YOK! Bu yüzden PDF boş oluyor olabilir.</p>";
    }
    
    // Basit HTML oluştur
    echo "<h3>PDF Oluşturuluyor...</h3>";
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12pt; }
            h1 { color: #2563eb; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #2563eb; color: white; padding: 10px; }
            td { padding: 8px; border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <h1>Teklif: ' . htmlspecialchars($teklif['teklifkodu']) . '</h1>
        <p>Müşteri: ' . htmlspecialchars($teklif['musteriadi'] ?? 'Bilinmiyor') . '</p>
        <p>Tarih: ' . date('d.m.Y H:i:s') . '</p>
        
        <table>
            <tr><th>#</th><th>Ürün</th><th>Miktar</th><th>Birim Fiyat</th><th>Toplam</th></tr>';
    
    $no = 1;
    $genelToplam = 0;
    foreach ($urunler as $u) {
        $liste = (float)($u['liste'] ?? 0);
        $miktar = (float)($u['miktar'] ?? 0);
        $toplam = $liste * $miktar;
        $genelToplam += $toplam;
        
        $html .= '<tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($u['kod'] ?? '') . ' - ' . htmlspecialchars($u['adi'] ?? '') . '</td>
            <td>' . number_format($miktar, 2, ',', '.') . '</td>
            <td>' . number_format($liste, 2, ',', '.') . ' TL</td>
            <td>' . number_format($toplam, 2, ',', '.') . ' TL</td>
        </tr>';
    }
    
    $html .= '</table>
        <p style="text-align: right; font-weight: bold; margin-top: 20px;">Genel Toplam: ' . number_format($genelToplam, 2, ',', '.') . ' TL</p>
    </body>
    </html>';
    
    echo "<h4>HTML oluşturuldu ✓</h4>";
    
    // PDF oluştur
    $pdfDir = __DIR__ . '/pdfs';
    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0755, true);
        echo "<p>pdfs klasörü oluşturuldu ✓</p>";
    }
    
    $config = [
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'default_font' => 'dejavusans'
    ];
    
    $mpdf = new Mpdf($config);
    echo "<p>mPDF oluşturuldu ✓</p>";
    
    $mpdf->WriteHTML($html);
    echo "<p>HTML yazıldı ✓</p>";
    
    $filename = $pdfDir . '/test_teklif_' . $teklifId . '_' . date('YmdHis') . '.pdf';
    $mpdf->Output($filename, \Mpdf\Output\Destination::FILE);
    
    if (file_exists($filename)) {
        $fileSize = filesize($filename);
        echo "<p style='color:green; font-size: 16pt;'>✅ PDF başarıyla oluşturuldu!</p>";
        echo "<p>Dosya yolu: {$filename}</p>";
        echo "<p>Dosya boyutu: " . number_format($fileSize) . " bytes</p>";
        echo "<p><a href='pdfs/" . basename($filename) . "' target='_blank' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>PDF'i Görüntüle</a></p>";
    } else {
        echo "<p style='color:red'>PDF dosyası oluşturulamadı!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red; font-size: 14pt;'>HATA: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background: #fee; padding: 10px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
