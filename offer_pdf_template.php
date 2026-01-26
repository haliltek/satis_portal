<?php
// offer_pdf_template.php
// Bu dosya sadece PDF oluşturmak için kullanılır

require_once 'fonk.php';

$teklifId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$teklifId) {
    die('Teklif ID gerekli');
}

// Teklif bilgilerini çek
$teklif = $db->query("SELECT * FROM ogteklif2 WHERE id = $teklifId")->fetch_assoc();
if (!$teklif) {
    die('Teklif bulunamadı');
}

// Müşteri bilgilerini çek
$sirketArpCode = $teklif['sirket_arp_code'] ?? '';
$musteri = null;
if ($sirketArpCode) {
    $musteri = $db->query("SELECT * FROM sirket WHERE s_arp_code = '$sirketArpCode'")->fetch_assoc();
}

// Ürünleri çek (sadece ürünler, indirim satırları hariç)
$urunler = $db->query("SELECT * FROM ogteklifurun2 WHERE teklifid = $teklifId AND (transaction_type = 0 OR transaction_type IS NULL) ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Hazırlayan bilgilerini çek
$hazirlayan = $db->query("SELECT * FROM yonetici WHERE yonetici_id = " . ($teklif['hazirlayanid'] ?? 0))->fetch_assoc();

// Dil kontrolü
$isForeign = !empty($musteri['trading_grp']) && stripos($musteri['trading_grp'], 'yd') !== false;

// Tarih formatla
$teklifTarihi = !empty($teklif['tekliftarihi']) ? date('d.m.Y', strtotime($teklif['tekliftarihi'])) : date('d.m.Y');
$gecerlilikTarihi = !empty($teklif['teklifgecerlilik']) ? date('d.m.Y', strtotime($teklif['teklifgecerlilik'])) : '-';
$onayTarihi = date('d.m.Y H:i:s');

// Toplam hesapla (ogteklifurun2 tablosu: liste, iskonto, miktar)
$araTopla = 0;
$toplamIskonto = 0;
$toplamKdv = 0;
$kdvOrani = 20; // Sabit KDV oranı

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

// Para birimi - ilk üründen al
$doviz = !empty($urunler[0]['doviz']) ? $urunler[0]['doviz'] : 'TL';
$dovizSembol = $doviz === 'USD' ? '$' : ($doviz === 'EUR' ? '€' : '₺');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teklif - <?= htmlspecialchars($teklif['teklifkodu']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 25px;
            margin: -20px -20px 20px -20px;
            text-align: center;
        }
        .header h1 {
            font-size: 24pt;
            margin-bottom: 5px;
        }
        
        .approval-box {
            background: #f0fdf4;
            border: 2px solid #22c55e;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .approval-box .status {
            color: #16a34a;
            font-size: 12pt;
            font-weight: bold;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            margin-bottom: 10px;
        }
        .info-box h3 {
            color: #2563eb;
            font-size: 10pt;
            margin-bottom: 8px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }
        .field {
            margin-bottom: 4px;
            font-size: 9pt;
        }
        .label {
            color: #64748b;
            display: inline-block;
            width: 120px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9pt;
        }
        thead {
            background: #2563eb;
            color: white;
        }
        th {
            padding: 10px 8px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .totals-box {
            width: 350px;
            margin-left: auto;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .totals-box td {
            border: none;
            padding: 8px 12px;
        }
        .grand-total {
            background: #2563eb;
            color: white;
            font-weight: bold;
        }
        
        .terms-section {
            margin-top: 30px;
            page-break-before: always;
        }
        .terms-section h3 {
            color: #2563eb;
            font-size: 12pt;
            margin-bottom: 10px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
        }
        .terms-content {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            white-space: pre-wrap;
            font-size: 9pt;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= $isForeign ? 'QUOTATION CONFIRMATION' : 'TEKLİF ONAY BELGESİ' ?></h1>
        <div>GEMAS ELEKTRİK MAKİNA SANAYİ VE TİCARET A.Ş.</div>
    </div>
    
    <div class="approval-box">
        <div class="status">✓ <?= $isForeign ? 'Order Confirmed / Awaiting Logo Transfer' : 'Sipariş Onaylandı / Logoya Aktarım Bekliyor' ?></div>
        <div><?= $isForeign ? 'Approval Date' : 'Onay Tarihi' ?>: <?= $onayTarihi ?></div>
    </div>
    
    <div class="info-section">
        <div class="info-box">
            <h3><?= $isForeign ? 'QUOTATION INFORMATION' : 'TEKLİF BİLGİLERİ' ?></h3>
            <div class="field">
                <span class="label"><?= $isForeign ? 'Quotation Code' : 'Teklif Kodu' ?>:</span>
                <span><?= htmlspecialchars($teklif['teklifkodu']) ?></span>
            </div>
            <div class="field">
                <span class="label"><?= $isForeign ? 'Date' : 'Tarih' ?>:</span>
                <span><?= $teklifTarihi ?></span>
            </div>
            <div class="field">
                <span class="label"><?= $isForeign ? 'Valid Until' : 'Geçerlilik' ?>:</span>
                <span><?= $gecerlilikTarihi ?></span>
            </div>
        </div>
        
        <div class="info-box">
            <h3><?= $isForeign ? 'CUSTOMER INFORMATION' : 'MÜŞTERİ BİLGİLERİ' ?></h3>
            <div class="field">
                <span class="label"><?= $isForeign ? 'Company' : 'Firma' ?>:</span>
                <span><?= htmlspecialchars($musteri['unvan'] ?? $teklif['musteriadi'] ?? '') ?></span>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;"><?= $isForeign ? 'Product' : 'Ürün' ?></th>
                <th style="width: 10%;" class="text-center"><?= $isForeign ? 'Qty' : 'Miktar' ?></th>
                <th style="width: 12%;" class="text-right"><?= $isForeign ? 'Unit Price' : 'Birim Fiyat' ?></th>
                <th style="width: 8%;" class="text-center"><?= $isForeign ? 'Disc %' : 'İsk %' ?></th>
                <th style="width: 8%;" class="text-center"><?= $isForeign ? 'VAT %' : 'KDV %' ?></th>
                <th style="width: 14%;" class="text-right"><?= $isForeign ? 'Total' : 'Toplam' ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $satirNo = 1; foreach ($urunler as $urun): 
                $liste = (float)($urun['liste'] ?? 0);
                $miktar = (float)($urun['miktar'] ?? 0);
                $iskonto = (float)($urun['iskonto'] ?? 0);
                
                $satirToplam = $liste * $miktar;
                $iskontoTutar = $satirToplam * ($iskonto / 100);
                $netTutar = $satirToplam - $iskontoTutar;
                $kdvTutar = $netTutar * ($kdvOrani / 100);
                $satirGenelToplam = $netTutar + $kdvTutar;
                
                // Ürün açıklaması: kod + ad
                $urunAciklama = trim(($urun['kod'] ?? '') . ' - ' . ($urun['adi'] ?? ''));
                if ($urunAciklama === '-') $urunAciklama = ($urun['adi'] ?? 'Ürün');
            ?>
            <tr>
                <td class="text-center"><?= $satirNo++ ?></td>
                <td><?= htmlspecialchars($urunAciklama) ?></td>
                <td class="text-center"><?= number_format($miktar, 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($liste, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                <td class="text-center"><?= number_format($iskonto, 2, ',', '.') ?>%</td>
                <td class="text-center"><?= $kdvOrani ?>%</td>
                <td class="text-right"><?= number_format($satirGenelToplam, 2, ',', '.') ?> <?= $dovizSembol ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <table class="totals-box">
        <tr>
            <td><?= $isForeign ? 'Subtotal:' : 'Ara Toplam:' ?></td>
            <td class="text-right"><?= number_format($araTopla, 2, ',', '.') ?> <?= $dovizSembol ?></td>
        </tr>
        <tr>
            <td><?= $isForeign ? 'Total Discount:' : 'Toplam İskonto:' ?></td>
            <td class="text-right">-<?= number_format($toplamIskonto, 2, ',', '.') ?> <?= $dovizSembol ?></td>
        </tr>
        <tr>
            <td><?= $isForeign ? 'Total VAT:' : 'Toplam KDV:' ?></td>
            <td class="text-right"><?= number_format($toplamKdv, 2, ',', '.') ?> <?= $dovizSembol ?></td>
        </tr>
        <tr class="grand-total">
            <td><?= $isForeign ? 'GRAND TOTAL:' : 'GENEL TOPLAM:' ?></td>
            <td class="text-right"><?= number_format($genelToplam, 2, ',', '.') ?> <?= $dovizSembol ?></td>
        </tr>
    </table>
    
    <?php if (!empty($teklif['sartlar'])): ?>
    <div class="terms-section">
        <h3><?= $isForeign ? 'TERMS & CONDITIONS' : 'TEKLİF ŞARTLARI' ?></h3>
        <div class="terms-content"><?= nl2br(htmlspecialchars($teklif['sartlar'])) ?></div>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <div>
            <strong>GEMAS ELEKTRİK MAKİNA SANAYİ VE TİCARET A.Ş.</strong><br>
            Organize Sanayi Bölgesi 11. Cadde No:7, 06935 Sincan / ANKARA<br>
            Tel: +90 312 267 08 00 | Email: info@gemas.com.tr
        </div>
        <div style="margin-top: 10px; font-style: italic;">
            <?= $isForeign ? 'This document has been generated electronically.' : 'Bu belge elektronik olarak oluşturulmuştur.' ?>
        </div>
    </div>
</body>
</html>
