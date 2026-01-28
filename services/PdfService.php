<?php
// services/PdfService.php

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\MpdfException;

class PdfService
{
    private $logger;
    private $pdfDir;

    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->pdfDir = __DIR__ . '/../pdfs';
        
        // PDF klasörünü oluştur
        if (!is_dir($this->pdfDir)) {
            mkdir($this->pdfDir, 0755, true);
        }
    }

    /**
     * HTML içeriğini PDF'e çevirir ve dosya olarak kaydeder
     */
    public function createPdfFromHtml($html, $filename, $config = [])
    {
        try {
            // Varsayılan konfigürasyon
            $defaultConfig = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9,
                'orientation' => 'P'
            ];

            // Kullanıcı konfigürasyonu ile birleştir
            $config = array_merge($defaultConfig, $config);

            // mPDF oluştur
            $mpdf = new Mpdf($config);

            // HTML'i PDF'e yaz
            $mpdf->WriteHTML($html);

            // Dosyayı kaydet
            $mpdf->Output($filename, \Mpdf\Output\Destination::FILE);

            $this->logger->log("PdfService: PDF oluşturuldu → {$filename}");
            return true;

        } catch (MpdfException $e) {
            $this->logger->log("PdfService Exception: " . $e->getMessage(), "ERROR");
            return false;
        }
    }

    /**
     * Teklif için detaylı PDF oluşturur
     */
    public function createOfferPdf($teklifId, $db)
    {
        try {
            $this->logger->log("PdfService: Teklif PDF oluşturuluyor → ID: {$teklifId}");
            
            // Teklif bilgilerini çek
            $teklifQuery = "SELECT * FROM ogteklif2 WHERE id = ?";
            $stmt = $db->prepare($teklifQuery);
            $stmt->bind_param("i", $teklifId);
            $stmt->execute();
            $teklif = $stmt->get_result()->fetch_assoc();
            
            if (!$teklif) {
                throw new Exception("Teklif bulunamadı");
            }
            
            // Müşteri bilgilerini çek (sirket tablosundan)
            $sirketArpCode = $teklif['sirket_arp_code'] ?? '';
            $musteri = null;
            
            if (!empty($sirketArpCode)) {
                $musteriQuery = "SELECT * FROM sirket WHERE s_arp_code = ?";
                $stmt = $db->prepare($musteriQuery);
                $stmt->bind_param("s", $sirketArpCode);
                $stmt->execute();
                $musteri = $stmt->get_result()->fetch_assoc();
            }
            
            // Eğer müşteri bulunamadıysa, teklif bilgilerinden al
            if (!$musteri) {
                $musteri = [
                    's_adi' => $teklif['musteriadi'] ?? 'Müşteri',
                    's_adres' => '',
                    's_telefonu' => '',
                    'mailposta' => '',
                    'ulke' => 'TÜRKİYE'
                ];
            }

            
            // Ürünleri çek (sadece ürünler, indirim satırları hariç)
            $urunlerQuery = "SELECT * FROM ogteklifurun2 WHERE teklifid = ? AND (transaction_type = 0 OR transaction_type IS NULL) ORDER BY id";
            $stmt = $db->prepare($urunlerQuery);
            $stmt->bind_param("i", $teklifId);
            $stmt->execute();
            $urunler = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Hazırlayan bilgilerini çek
            $hazirlayanQuery = "SELECT * FROM yonetici WHERE yonetici_id = ?";
            $stmt = $db->prepare($hazirlayanQuery);
            $hazirlayanId = (int)($teklif['hazirlayanid'] ?? 0);
            $stmt->bind_param("i", $hazirlayanId);
            $stmt->execute();
            $hazirlayan = $stmt->get_result()->fetch_assoc() ?: ['adsoyad' => ''];
            
            // Teklif şartlarını çek (teklifsartlari tablosundan)
            $teklifSartlari = '';
            $teklifSartId = $teklif['teklifsartid'] ?? '';
            if (!empty($teklifSartId)) {
                // Önce teklifsartlari tablosuna bak
                $sartQuery = "SELECT aciklama FROM teklifsartlari WHERE id = ?";
                $stmt = $db->prepare($sartQuery);
                $stmt->bind_param("s", $teklifSartId);
                $stmt->execute();
                $sartResult = $stmt->get_result()->fetch_assoc();
                if ($sartResult) {
                    $teklifSartlari = $sartResult['aciklama'] ?? '';
                }
            }
            
            // Eğer teklifsartid ile bulamadıysak, sirket bazlı şartları çek
            if (empty($teklifSartlari) && $musteri) {
                $sirketId = $musteri['sirket_id'] ?? '';
                if (!empty($sirketId)) {
                    $sartQuery = "SELECT aciklama FROM teklifsartlari WHERE sirketid = ?";
                    $stmt = $db->prepare($sartQuery);
                    $stmt->bind_param("s", $sirketId);
                    $stmt->execute();
                    $sartResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    if (!empty($sartResults)) {
                        $sartTexts = array_map(function($s) { return '• ' . ($s['aciklama'] ?? ''); }, $sartResults);
                        $teklifSartlari = implode("\n", $sartTexts);
                    }
                }
            }
            
            // Teklif nesnesine şartları ekle
            $teklif['sartlar'] = $teklifSartlari;
            
            $this->logger->log("PdfService: Veriler çekildi - " . count($urunler) . " ürün");
            
            // HTML şablonu oluştur
            $html = $this->generateDetailedOfferHtml($teklif, $musteri, $urunler, $hazirlayan);
            
            // PDF dosya adı
            $filename = $this->pdfDir . '/teklif_' . $teklifId . '_' . date('YmdHis') . '.pdf';
            
            // PDF oluştur
            $config = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'default_font' => 'dejavusans'
            ];
            
            if ($this->createPdfFromHtml($html, $filename, $config)) {
                $fileSize = filesize($filename);
                $this->logger->log("PdfService: PDF başarıyla oluşturuldu → {$filename} ({$fileSize} bytes)");
                return $filename;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->log("PdfService Exception: " . $e->getMessage(), "ERROR");
            return false;
        }
    }
    
    /**
     * offer_detail.php sayfasından HTML alarak PDF oluşturur
     * Bu yöntem, yazdırma görünümüyle birebir aynı tasarımı kullanır
     */
    public function createOfferPdfFromPage($teklifId, $db, $baseUrl = null)
    {
        try {
            $this->logger->log("PdfService: Sayfa tabanlı PDF oluşturuluyor → ID: {$teklifId}");
            
            // Teklif bilgilerini çek (dosya adı için)
            $teklifQuery = "SELECT teklifkodu FROM ogteklif2 WHERE id = ?";
            $stmt = $db->prepare($teklifQuery);
            $stmt->bind_param("i", $teklifId);
            $stmt->execute();
            $teklif = $stmt->get_result()->fetch_assoc();
            
            if (!$teklif) {
                throw new Exception("Teklif bulunamadı");
            }
            
            // Base URL belirleme
            if (empty($baseUrl)) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $baseUrl = $protocol . '://' . $host . '/b2b-gemas-project-main';
            }
            
            // offer_detail.php'nin PDF modunu aç
            $pdfUrl = $baseUrl . "/offer_detail.php?te={$teklifId}&sta=Teklif&pdf=1";
            
            $this->logger->log("PdfService: HTML alınıyor → {$pdfUrl}");
            
            // cURL ile HTML al
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $pdfUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $html = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($html === false || $httpCode !== 200) {
                $this->logger->log("PdfService: HTML alınamadı - HTTP {$httpCode}, Hata: {$error}", "ERROR");
                // Fallback: Kendi şablonumuzu kullan
                return $this->createOfferPdf($teklifId, $db);
            }
            
            $this->logger->log("PdfService: HTML alındı - " . strlen($html) . " bytes");
            
            // no-print sınıfını kaldır (butonlar, tablar vs)
            $html = preg_replace('/<[^>]+class="[^"]*no-print[^"]*"[^>]*>.*?<\/[^>]+>/is', '', $html);
            
            // PDF dosya adı
            $filename = $this->pdfDir . '/teklif_' . $teklifId . '_' . date('YmdHis') . '.pdf';
            
            // PDF oluştur
            $config = [
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'default_font' => 'dejavusans'
            ];
            
            if ($this->createPdfFromHtml($html, $filename, $config)) {
                $fileSize = filesize($filename);
                $this->logger->log("PdfService: Sayfa tabanlı PDF oluşturuldu → {$filename} ({$fileSize} bytes)");
                return $filename;
            }

            return false;

        } catch (Exception $e) {
            $this->logger->log("PdfService createOfferPdfFromPage Exception: " . $e->getMessage(), "ERROR");
            // Fallback
            return $this->createOfferPdf($teklifId, $db);
        }
    }
    
    /**
     * Detaylı teklif HTML şablonu oluşturur (Yeni Tasarım)
     */
    private function generateDetailedOfferHtml($teklif, $musteri, $urunler, $hazirlayan)
    {
        // Dil kontrolü - Çoklu kriter ile yurtdışı müşteri tespiti
        // Öncelik sırası: 1) is_export flag, 2) SPECODE, 3) country_code, 4) ulke
        $isForeign = false;
        
        // 1. Önce is_export flag'ini kontrol et
        if (isset($musteri['is_export']) && $musteri['is_export'] == 1) {
            $isForeign = true;
        }
        
        // 2. SPECODE alanında "İhracat" kontrolü
        if (!$isForeign && !empty($musteri['specode'])) {
            $specode = $musteri['specode'];
            if (stripos($specode, 'İhracat') !== false || stripos($specode, 'Ihracat') !== false || stripos($specode, 'EXPORT') !== false) {
                $isForeign = true;
            }
        }
        
        // 3. Ülke kodu kontrolü (TR değilse yurtdışı)
        if (!$isForeign && !empty($musteri['s_country_code'])) {
            $countryCode = strtoupper(trim($musteri['s_country_code']));
            if ($countryCode !== 'TR' && $countryCode !== 'TUR' && $countryCode !== 'TURKEY') {
                $isForeign = true;
            }
        }
        
        // 4. Ülke adı kontrolü (TÜRKİYE değilse yurtdışı)
        if (!$isForeign && !empty($musteri['ulke'])) {
            $ulke = strtoupper(trim($musteri['ulke']));
            if ($ulke !== 'TÜRKİYE' && $ulke !== 'TURKIYE' && $ulke !== 'TURKEY') {
                $isForeign = true;
            }
        }
        
        // Tarih formatla
        $teklifTarihi = !empty($teklif['tekliftarihi']) ? date('d.m.Y', strtotime($teklif['tekliftarihi'])) : date('d.m.Y');
        $gecerlilikTarihi = !empty($teklif['teklifgecerlilik']) ? date('d.m.Y', strtotime($teklif['teklifgecerlilik'])) : '-';
        $onayTarihi = date('d.m.Y H:i:s');
        
        // Toplam hesapla
        $araTopla = 0;
        $toplamIskonto = 0;
        $toplamKdv = 0;
        $kdvOrani = 20;
        
        foreach ($urunler as $urun) {
            $liste = (float)($urun['liste'] ?? 0);
            $miktar = (float)($urun['miktar'] ?? 0);
            $iskonto = (float)($urun['iskonto'] ?? 0);
            
            $satirToplam = $liste * $miktar;
            $iskontoTutar = $satirToplam * ($iskonto / 100);
            $netTutar = $satirToplam - $iskontoTutar;
            $kdvTutar = $netTutar * ($kdvOrani / 100);
            
            $araTopla += $satirToplam;
            $toplamIskonto += $iskontoTutar;
            $toplamKdv += $kdvTutar;
        }
        
        $genelToplam = $araTopla - $toplamIskonto + $toplamKdv;
        
        // Para birimi
        $doviz = !empty($urunler[0]['doviz']) ? $urunler[0]['doviz'] : 'TL';
        $dovizSembol = $doviz === 'USD' ? '$' : ($doviz === 'EUR' ? '€' : '₺');
        
        // Teklif şartları
        $teklifSartlari = $teklif['sartlar'] ?? '';
        
        // Logo base64 olarak embed et (mPDF için)
        $logoPath = __DIR__ . '/../logogemas.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
            padding: 10px;
        }
        
        /* Header */
        .header {
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
        }
        .header table { width: 100%; border: none; }
        .header td { border: none; vertical-align: middle; }
        .header-left { width: 60px; }
        .header-left img { width: 54px; height: auto; }
        .header-center { text-align: center; }
        .header h1 {
            font-size: 14pt;
            color: #2563eb;
            margin-bottom: 2px;
            font-weight: bold;
        }
        .header .subtitle {
            font-size: 8pt;
            color: #64748b;
        }
        
        /* Onay Kutusu */
        .approval-box {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            border-radius: 5px;
            padding: 8px;
            margin-bottom: 10px;
            text-align: center;
        }
        .approval-box .status {
            color: #16a34a;
            font-size: 10pt;
            font-weight: bold;
        }
        .approval-box .date {
            color: #15803d;
            font-size: 7pt;
        }
        
        /* Bilgi Kutuları */
        .info-table { width: 100%; border-collapse: separate; border-spacing: 6px; margin-bottom: 10px; }
        .info-table > tbody > tr > td { width: 50%; vertical-align: top; }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px;
        }
        .info-box h3 {
            color: #2563eb;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 2px solid #2563eb;
        }
        .info-box .field { margin-bottom: 2px; font-size: 7pt; }
        .info-box .label { color: #64748b; display: inline-block; width: 70px; }
        .info-box .value { color: #1e293b; font-weight: 500; }
        
        /* Ürün Tablosu */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 6.5pt;
        }
        .product-table thead { background: #2563eb; color: white; }
        .product-table th {
            padding: 6px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 6pt;
            border: 1px solid #1d4ed8;
        }
        .product-table td {
            padding: 5px 3px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .product-table tbody tr:nth-child(even) { background: #f8fafc; }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        
        /* Toplam Kutusu */
        .totals-box {
            width: 220px;
            margin: 12px 0 12px auto;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        .totals-box table { width: 100%; border-collapse: collapse; font-size: 7pt; }
        .totals-box td { padding: 5px 8px; border: none; }
        .totals-box .label-cell { font-weight: 600; color: #475569; }
        .totals-box .grand-total td { background: #2563eb; color: white; font-weight: bold; font-size: 9pt; }
        
        /* Teklif Şartları */
        .terms-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 4px;
            padding: 8px;
            margin: 12px 0;
        }
        .terms-box h3 {
            color: #b45309;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 2px solid #f59e0b;
        }
        .terms-box .terms-content { font-size: 7pt; color: #78350f; line-height: 1.4; }
        
        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7pt;
            color: #64748b;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header" style="text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 12px;">
        <h1 style="font-size: 16pt; color: #2563eb; margin-bottom: 3px; font-weight: bold;">' . ($isForeign ? 'APPROVED QUOTATION' : 'ONAYLI TEKLİFİNİZ') . '</h1>
        <div style="font-size: 9pt; color: #64748b;">Gemaş Genel Müh. Mek. San. Tic. A.Ş.</div>
    </div>
    
    <!-- Onay Kutusu -->
    <div class="approval-box">
        <div class="status">✓ ' . ($isForeign ? 'Order Confirmed' : 'Sipariş Onaylandı') . '</div>
        <div class="date">' . ($isForeign ? 'Approval Date' : 'Onay Tarihi') . ': ' . $onayTarihi . '</div>
    </div>
    
    <!-- Bilgi Kutuları -->
    <table class="info-table">
        <tr>
            <td>
                <div class="info-box">
                    <h3>' . ($isForeign ? 'QUOTATION INFO' : 'TEKLİF BİLGİLERİ') . '</h3>
                    <div class="field"><span class="label">' . ($isForeign ? 'Code:' : 'Teklif Kodu:') . '</span><span class="value">' . htmlspecialchars($teklif['teklifkodu']) . '</span></div>
                    <div class="field"><span class="label">' . ($isForeign ? 'Date:' : 'Tarih:') . '</span><span class="value">' . $teklifTarihi . '</span></div>
                    <div class="field"><span class="label">' . ($isForeign ? 'Valid Until:' : 'Geçerlilik:') . '</span><span class="value">' . $gecerlilikTarihi . '</span></div>
                    <div class="field"><span class="label">' . ($isForeign ? 'Currency:' : 'Para Birimi:') . '</span><span class="value">' . $doviz . '</span></div>
                    <div class="field"><span class="label">' . ($isForeign ? 'Prepared By:' : 'Hazırlayan:') . '</span><span class="value">' . htmlspecialchars($hazirlayan['adsoyad'] ?? '') . '</span></div>
                </div>
            </td>
            <td>
                <div class="info-box">
                    <h3>' . ($isForeign ? 'CUSTOMER INFO' : 'MÜŞTERİ BİLGİLERİ') . '</h3>
                    <div class="field"><span class="label">' . ($isForeign ? 'Company:' : 'Firma:') . '</span><span class="value">' . htmlspecialchars($musteri['s_adi'] ?? $musteri['unvan'] ?? '') . '</span></div>
                    <div class="field"><span class="label">' . ($isForeign ? 'Address:' : 'Adres:') . '</span><span class="value">' . htmlspecialchars(substr($musteri['s_adres'] ?? '', 0, 45)) . '</span></div>
                    <div class="field"><span class="label">' . ($isForeign ? 'Phone:' : 'Telefon:') . '</span><span class="value">' . htmlspecialchars($musteri['s_telefonu'] ?? '') . '</span></div>
                    <div class="field"><span class="label">Email:</span><span class="value">' . htmlspecialchars($musteri['mailposta'] ?? '') . '</span></div>
                    <div class="field"><span class="label">' . ($isForeign ? 'Country:' : 'Ülke:') . '</span><span class="value">' . htmlspecialchars($musteri['ulke'] ?? 'TÜRKİYE') . '</span></div>
                </div>
            </td>
        </tr>
    </table>
    
    <!-- Ürün Tablosu -->
    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 20%;" class="text-left">' . ($isForeign ? 'PRODUCT/SERVICE' : 'MAL/HİZMET') . '</th>
                <th style="width: 6%;">' . ($isForeign ? 'QTY' : 'MİKTAR') . '</th>
                <th style="width: 5%;">' . ($isForeign ? 'UNIT' : 'BİRİM') . '</th>
                <th style="width: 10%;">' . ($isForeign ? 'LIST PRICE' : 'LİSTE FİYATI') . '</th>
                <th style="width: 7%;">' . ($isForeign ? 'DISC (%)' : 'İSKONTO (%)') . '</th>
                <th style="width: 10%;">' . ($isForeign ? 'DISC UNIT PRICE' : 'İSKONTOLU BİRİM FİYAT') . '</th>
                <th style="width: 10%;">' . ($isForeign ? 'TOTAL' : 'TOPLAM') . '</th>
                <th style="width: 5%;">' . ($isForeign ? 'VAT' : 'KDV') . '</th>
                <th style="width: 10%;">' . ($isForeign ? 'VAT UNIT PRICE' : 'KDV\'Lİ BİRİM FİYAT') . '</th>
                <th style="width: 12%;">' . ($isForeign ? 'TOTAL' : 'TOPLAM') . '</th>
            </tr>
        </thead>
        <tbody>';
        
        $satirNo = 1;
        foreach ($urunler as $urun) {
            $liste = (float)($urun['liste'] ?? 0);
            $miktar = (float)($urun['miktar'] ?? 0);
            $iskonto = (float)($urun['iskonto'] ?? 0);
            
            // Hesaplamalar
            $iskontoTutarBirim = $liste * ($iskonto / 100);
            $iskontolusiBirimFiyat = $liste - $iskontoTutarBirim;
            $toplamIskontolu = $iskontolusiBirimFiyat * $miktar;
            $kdvTutar = $toplamIskontolu * ($kdvOrani / 100);
            $kdvliBirimFiyat = $iskontolusiBirimFiyat * (1 + $kdvOrani / 100);
            $genelToplamSatir = $toplamIskontolu + $kdvTutar;
            
            $urunAciklama = trim(($urun['kod'] ?? '') . ' - ' . ($urun['adi'] ?? ''));
            if ($urunAciklama === '-') $urunAciklama = ($urun['adi'] ?? 'Ürün');
            
            $html .= '
            <tr>
                <td>' . $satirNo++ . '</td>
                <td class="text-left">' . htmlspecialchars($urunAciklama) . '</td>
                <td>' . number_format($miktar, 0, ',', '.') . '</td>
                <td>' . htmlspecialchars($urun['birim'] ?? 'ADET') . '</td>
                <td>' . number_format($liste, 2, ',', '.') . ' ' . $dovizSembol . '</td>
                <td>' . number_format($iskonto, 0) . '%</td>
                <td>' . number_format($iskontolusiBirimFiyat, 2, ',', '.') . ' ' . $dovizSembol . '</td>
                <td>' . number_format($toplamIskontolu, 2, ',', '.') . ' ' . $dovizSembol . '</td>
                <td>' . $kdvOrani . '%</td>
                <td>' . number_format($kdvliBirimFiyat, 2, ',', '.') . ' ' . $dovizSembol . '</td>
                <td>' . number_format($genelToplamSatir, 2, ',', '.') . ' ' . $dovizSembol . '</td>
            </tr>';
        }
        
        $html .= '
        </tbody>
    </table>
    
    <!-- Toplam Kutusu -->
    <div class="totals-box">
        <table>
            <tr>
                <td class="label-cell">' . ($isForeign ? 'Subtotal:' : 'Ara Toplam:') . '</td>
                <td class="text-right">' . number_format($araTopla, 2, ',', '.') . ' ' . $dovizSembol . '</td>
            </tr>
            <tr>
                <td class="label-cell">' . ($isForeign ? 'Discount:' : 'İskonto:') . '</td>
                <td class="text-right">-' . number_format($toplamIskonto, 2, ',', '.') . ' ' . $dovizSembol . '</td>
            </tr>
            <tr>
                <td class="label-cell">' . ($isForeign ? 'VAT' : 'KDV') . ' (' . $kdvOrani . '%):</td>
                <td class="text-right">' . number_format($toplamKdv, 2, ',', '.') . ' ' . $dovizSembol . '</td>
            </tr>
            <tr class="grand-total">
                <td>' . ($isForeign ? 'TOTAL:' : 'TOPLAM:') . '</td>
                <td class="text-right">' . number_format($genelToplam, 2, ',', '.') . ' ' . $dovizSembol . '</td>
            </tr>
        </table>
    </div>';

        // Teklif Şartları - sadece varsa göster
        if (!empty($teklifSartlari)) {
            $html .= '
    <div class="terms-box">
        <h3>' . ($isForeign ? 'TERMS & CONDITIONS' : 'TEKLİF ŞARTLARI') . '</h3>
        <div class="terms-content">' . nl2br(htmlspecialchars($teklifSartlari)) . '</div>
    </div>';
        }
        
        $html .= '
    <!-- Footer -->
    <div class="footer">
        <strong>Gemaş Genel Müh. Mek. San. Tic. A.Ş.</strong><br>
        İTOB Organize Sanayi Bölgesi 10001 Sokak No:28 Tekeli-Menderes / İZMİR<br>
        Tel: (0232) 799 03 33 | Web: www.gemas.com.tr
        <div style="margin-top: 6px; font-style: italic;">
            Bu belge elektronik olarak oluşturulmuştur.
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}
