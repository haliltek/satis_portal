<?php
// test_pdf_design.php - PDF Tasarım Önizleme

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/fonk.php';

$teklifId = isset($_GET['id']) ? (int)$_GET['id'] : 66;

// Teklif bilgilerini çek
$stmt = $db->prepare("SELECT * FROM ogteklif2 WHERE id = ?");
$stmt->bind_param("i", $teklifId);
$stmt->execute();
$teklif = $stmt->get_result()->fetch_assoc();

if (!$teklif) {
    die("Teklif bulunamadı. ?id=XX ile teklif ID belirtin.");
}

// Müşteri bilgilerini çek
$sirketArpCode = $teklif['sirket_arp_code'] ?? '';
$musteri = null;
if (!empty($sirketArpCode)) {
    $stmt = $db->prepare("SELECT * FROM sirket WHERE s_arp_code = ?");
    $stmt->bind_param("s", $sirketArpCode);
    $stmt->execute();
    $musteri = $stmt->get_result()->fetch_assoc();
}

if (!$musteri) {
    $musteri = [
        's_adi' => $teklif['musteriadi'] ?? 'Müşteri',
        's_adres' => '',
        's_telefonu' => '',
        'mailposta' => '',
        'ulke' => 'TÜRKİYE'
    ];
}

// Ürünleri çek
$stmt = $db->prepare("SELECT * FROM ogteklifurun2 WHERE teklifid = ? AND (transaction_type = 0 OR transaction_type IS NULL) ORDER BY id");
$stmt->bind_param("i", $teklifId);
$stmt->execute();
$urunler = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hazırlayan bilgilerini çek
$stmt = $db->prepare("SELECT * FROM yonetici WHERE yonetici_id = ?");
$hazirlayanId = (int)($teklif['hazirlayanid'] ?? 0);
$stmt->bind_param("i", $hazirlayanId);
$stmt->execute();
$hazirlayan = $stmt->get_result()->fetch_assoc() ?: ['adsoyad' => ''];

// Dil kontrolü
$isForeign = !empty($musteri['ulke']) && $musteri['ulke'] !== 'TÜRKİYE';

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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PDF Tasarım Önizleme - <?= htmlspecialchars($teklif['teklifkodu']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
            background: #f0f0f0;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            padding: 12px;
        }
        
        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
        }
        .header-left {
            display: table-cell;
            width: 100px;
            vertical-align: middle;
        }
        .header-left img {
            width: 54px;
            height: auto;
        }
        .header-center {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }
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
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 10px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px;
            height: 100%;
        }
        .info-box h3 {
            color: #2563eb;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 2px solid #2563eb;
        }
        .info-box .field {
            margin-bottom: 2px;
            font-size: 7pt;
        }
        .info-box .label {
            color: #64748b;
            display: inline-block;
            width: 70px;
        }
        .info-box .value {
            color: #1e293b;
            font-weight: 500;
        }
        
        /* Tablo */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 6.5pt;
        }
        .product-table thead {
            background: #2563eb;
            color: white;
        }
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
        .product-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        .text-right { text-align: right !important; }
        .text-left { text-align: left !important; }
        
        /* Toplam Kutusu */
        .totals-box {
            width: 220px;
            margin: 12px 0 12px auto;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .totals-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        .totals-box td {
            padding: 5px 8px;
            border: none;
        }
        .totals-box .label-cell {
            font-weight: 600;
            color: #475569;
        }
        .totals-box .grand-total td {
            background: #2563eb;
            color: white;
            font-weight: bold;
            font-size: 9pt;
        }
        
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
        .terms-box .terms-content {
            font-size: 7pt;
            color: #78350f;
            line-height: 1.4;
        }
        
        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 7pt;
            color: #64748b;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .no-print button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 0 5px;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { 
                background: white !important; 
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page { 
                box-shadow: none !important; 
                margin: 0 !important;
                padding: 8mm !important;
                width: 100% !important;
                min-height: auto !important;
            }
            
            /* Sayfa kesmelerini kontrol et */
            .header, .approval-box, .info-table, .totals-box, .terms-box, .footer {
                page-break-inside: avoid !important;
            }
            .product-table {
                page-break-inside: auto !important;
            }
            .product-table tr {
                page-break-inside: avoid !important;
            }
            
            /* Tablo genişliklerini zorla */
            .product-table { 
                font-size: 6pt !important;
                table-layout: fixed !important;
                width: 100% !important;
            }
            .product-table th,
            .product-table td {
                padding: 3px 2px !important;
                word-wrap: break-word !important;
                overflow: hidden !important;
            }
            
            /* Info table düzeni */
            .info-table {
                width: 100% !important;
                table-layout: fixed !important;
            }
            .info-table td {
                width: 50% !important;
            }
            
            /* Header düzeni */
            .header {
                display: table !important;
                width: 100% !important;
            }
            .header-left {
                display: table-cell !important;
                width: 90px !important;
            }
            .header-center {
                display: table-cell !important;
            }
            
            /* Renkleri koru */
            .product-table thead {
                background: #2563eb !important;
                -webkit-print-color-adjust: exact !important;
            }
            .totals-box .grand-total td {
                background: #2563eb !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
            }
            .approval-box {
                background: #f0fdf4 !important;
                border: 2px solid #22c55e !important;
                -webkit-print-color-adjust: exact !important;
            }
            .terms-box {
                background: #fffbeb !important;
                -webkit-print-color-adjust: exact !important;
            }
        }
        
        /* Sayfa boyutu ayarı */
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Yazdır / PDF Kaydet</button>
        <button onclick="location.href='test_pdf_design.php?id=<?= $teklifId ?>'">🔄 Yenile</button>
    </div>
    
    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="logogemas.png" alt="Gemaş Logo">
            </div>
            <div class="header-center">
                <h1><?= $isForeign ? 'APPROVED QUOTATION' : 'ONAYLI TEKLİFİNİZ' ?></h1>
                <div class="subtitle">Gemaş Genel Müh. Mek. San. Tic. A.Ş.</div>
            </div>
        </div>
        
        <!-- Onay Kutusu -->
        <div class="approval-box">
            <div class="status">✓ <?= $isForeign ? 'Order Confirmed' : 'Sipariş Onaylandı' ?></div>
            <div class="date"><?= $isForeign ? 'Approval Date' : 'Onay Tarihi' ?>: <?= $onayTarihi ?></div>
        </div>
        
        <!-- Bilgi Kutuları -->
        <table class="info-table">
            <tr>
                <td>
                    <div class="info-box">
                        <h3><?= $isForeign ? 'QUOTATION INFO' : 'TEKLİF BİLGİLERİ' ?></h3>
                        <div class="field">
                            <span class="label">Teklif Kodu:</span>
                            <span class="value"><?= htmlspecialchars($teklif['teklifkodu']) ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Tarih:</span>
                            <span class="value"><?= $teklifTarihi ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Geçerlilik:</span>
                            <span class="value"><?= $gecerlilikTarihi ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Para Birimi:</span>
                            <span class="value"><?= $doviz ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Hazırlayan:</span>
                            <span class="value"><?= htmlspecialchars($hazirlayan['adsoyad'] ?? '') ?></span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <h3><?= $isForeign ? 'CUSTOMER INFO' : 'MÜŞTERİ BİLGİLERİ' ?></h3>
                        <div class="field">
                            <span class="label">Firma:</span>
                            <span class="value"><?= htmlspecialchars($musteri['s_adi'] ?? $musteri['unvan'] ?? '') ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Adres:</span>
                            <span class="value"><?= htmlspecialchars(substr($musteri['s_adres'] ?? '', 0, 45)) ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Telefon:</span>
                            <span class="value"><?= htmlspecialchars($musteri['s_telefonu'] ?? '') ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Email:</span>
                            <span class="value"><?= htmlspecialchars($musteri['mailposta'] ?? '') ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Ülke:</span>
                            <span class="value"><?= htmlspecialchars($musteri['ulke'] ?? 'TÜRKİYE') ?></span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <!-- Ürün Listesi -->
        <table class="product-table">
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 22%;" class="text-left">MAL/HİZMET</th>
                    <th style="width: 6%;">MİKTAR</th>
                    <th style="width: 5%;">BİRİM</th>
                    <th style="width: 10%;">LİSTE FİYATI</th>
                    <th style="width: 7%;">İSKONTO (%)</th>
                    <th style="width: 10%;">İSKONTOLU BİRİM FİYAT</th>
                    <th style="width: 10%;">TOPLAM</th>
                    <th style="width: 5%;">KDV</th>
                    <th style="width: 10%;">KDV'Lİ BİRİM FİYAT</th>
                    <th style="width: 12%;">TOPLAM</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $satirNo = 1;
                foreach ($urunler as $urun): 
                    $liste = (float)($urun['liste'] ?? 0);
                    $miktar = (float)($urun['miktar'] ?? 0);
                    $iskonto = (float)($urun['iskonto'] ?? 0);
                    
                    // Hesaplamalar
                    $iskontoTutarBirim = $liste * ($iskonto / 100);
                    $iskontolusiBirimFiyat = $liste - $iskontoTutarBirim;
                    $toplamIskontosuz = $liste * $miktar;
                    $toplamIskontolu = $iskontolusiBirimFiyat * $miktar;
                    $kdvTutar = $toplamIskontolu * ($kdvOrani / 100);
                    $kdvliBirimFiyat = $iskontolusiBirimFiyat * (1 + $kdvOrani / 100);
                    $genelToplamSatir = $toplamIskontolu + $kdvTutar;
                    
                    $urunAciklama = trim(($urun['kod'] ?? '') . ' - ' . ($urun['adi'] ?? ''));
                    if ($urunAciklama === '-') $urunAciklama = ($urun['adi'] ?? 'Ürün');
                ?>
                <tr>
                    <td><?= $satirNo++ ?></td>
                    <td class="text-left"><?= htmlspecialchars($urunAciklama) ?></td>
                    <td><?= number_format($miktar, 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($urun['birim'] ?? 'ADET') ?></td>
                    <td><?= number_format($liste, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                    <td><?= number_format($iskonto, 0) ?>%</td>
                    <td><?= number_format($iskontolusiBirimFiyat, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                    <td><?= number_format($toplamIskontolu, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                    <td><?= $kdvOrani ?>%</td>
                    <td><?= number_format($kdvliBirimFiyat, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                    <td><?= number_format($genelToplamSatir, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Toplam Kutusu -->
        <div class="totals-box">
            <table>
                <tr>
                    <td class="label-cell">Ara Toplam:</td>
                    <td class="text-right"><?= number_format($araTopla, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                </tr>
                <tr>
                    <td class="label-cell">İskonto:</td>
                    <td class="text-right">-<?= number_format($toplamIskonto, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                </tr>
                <tr>
                    <td class="label-cell">KDV (<?= $kdvOrani ?>%):</td>
                    <td class="text-right"><?= number_format($toplamKdv, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                </tr>
                <tr class="grand-total">
                    <td>TOPLAM:</td>
                    <td class="text-right"><?= number_format($genelToplam, 2, ',', '.') ?> <?= $dovizSembol ?></td>
                </tr>
            </table>
        </div>
        
        <!-- TEKLİF ŞARTLARI - EN ALTTA -->
        <?php if (!empty($teklifSartlari)): ?>
        <div class="terms-box">
            <h3>TEKLİF ŞARTLARI</h3>
            <div class="terms-content"><?= nl2br(htmlspecialchars($teklifSartlari)) ?></div>
        </div>
        <?php else: ?>
        <div class="terms-box">
            <h3>TEKLİF ŞARTLARI</h3>
            <div class="terms-content" style="color: #999; font-style: italic;">
                - Teklifte belirtilen fiyatlara KDV dahildir.<br>
                - Teslim süresi sipariş onayından itibaren 2-3 hafta içerisindedir.<br>
                - Ödeme: Sipariş onayında %50, teslimde %50 olarak yapılacaktır.
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <strong>Gemaş Genel Müh. Mek. San. Tic. A.Ş.</strong><br>
            İTOB Organize Sanayi Bölgesi 10001 Sokak No:28 Tekeli-Menderes / İZMİR<br>
            Tel: (0232) 799 03 33 | Web: www.gemas.com.tr
            <div style="margin-top: 6px; font-style: italic;">
                Bu belge elektronik olarak oluşturulmuştur.
            </div>
        </div>
    </div>
</body>
</html>
