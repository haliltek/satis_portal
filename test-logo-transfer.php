<?php
// test-logo-transfer.php - Logo İskonto Aktarım Test Sayfası
ob_start();

// Config ve database bağlantısı
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/fonk.php';
require_once __DIR__ . '/classes/DatabaseManager.php';
require_once __DIR__ . '/classes/LogoService.php';

use Proje\LogoService;
use Proje\DatabaseManager;

// Session kontrolü
oturumkontrol();
$yonetici_id = $_SESSION['yonetici_id'];

// Database manager
$dbConfig = [
    'host' => $config['db']['host'],
    'port' => $config['db']['port'],
    'user' => $config['db']['user'],
    'pass' => $config['db']['pass'],
    'name' => $config['db']['name'],
];
$dbManager = new DatabaseManager($dbConfig);
$conn = $dbManager->getConnection();

// Logo service
$logoService = new LogoService(
    db: $dbManager,
    configArray: $config,
    logErrorFile: __DIR__ . '/error.log',
    logDebugFile: __DIR__ . '/debug.log'
);

// Debug çıktıları
$debugOutput = [];

function addDebug($step, $message, $data = null) {
    global $debugOutput;
    $debugOutput[] = [
        'step' => $step,
        'message' => $message,
        'data' => $data,
        'time' => date('H:i:s')
    ];
}

// Test verisi
$testData = [
    'cari_code' => '120.01.E04',  // ERTEK
    'cari_name' => 'ERTEK YAPI VE MALZEME',
    'product_code' => '021612T',
    'product_name' => '021612T Ürün',
    'quantity' => 1,
    'list_price' => 1000.00,
    'currency' => 'EUR',  // Döviz (TL/EUR/USD)
    'exchange_rate' => 50.55,  // Kur bilgisi (EUR için)
    'unit' => 'ADET',
    'discount' => 50,  // Tek iskonto
    'discount_formula' => '50-5-10',  // Kademeli iskonto
    'salesman_ref' => 44,  // İZMİR SATIŞ TEMSİLCİSİ (İLKNUR ŞEN)
    'division' => 0,       // 0 - ITOB
    'department' => 0,     // 0 - Vana
    'source_wh' => 0,      // 0 - ITOB
    'factory' => 0,        // 0 - ITOB
    'auxil_code' => 'İzmir',   // Özel Kod
    'trading_grp' => '001',    // Ticari Grup: STOPAJLI
    'payment_code' => '060',   // Tahsilat Planı: 60 GÜN
    'shipping_agent' => 'GEMPA',  // Taşıyıcı kodu
];


// Test modu seç
$useFormula = isset($_POST['use_formula']) && $_POST['use_formula'] == '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_transfer') {
    
    addDebug('START', 'Test aktarım başlatıldı', $testData);
    
    try {
        // ═══════════════════════════════════════════════════════════
        // ADIM 1: TEKLİF KAYDI OLUŞTUR
        // ═══════════════════════════════════════════════════════════
        addDebug('STEP_1', 'Teklif kaydı oluşturuluyor...');
        
        $sql1 = "INSERT INTO ogteklif2 (
            musteriadi, teklifsiparis, hazirlayanid, musteriid, kime, projeadi,
            tekliftarihi, teklifkodu, teklifsartid, odemeturu, sirketid, sirket_arp_code,
            tltutar, dolartutar, eurotutar, toplamtutar, kdv, geneltoplam, kurtarih,
            eurokur, dolarkur, tur, teklifgecerlilik, teslimyer,
            durum, statu, notes1, order_status, sozlesme_id, doviz_goster,
            auxil_code, auth_code, division, department, source_wh, factory, salesmanref,
            is_special_offer, approval_status, genel_iskonto, currency, shipping_agent
        ) VALUES (
            ?, 'Teklif', ?, ?, 'Müşteriye', '',
            NOW(), 'TEST-001', '1', ?, ?, ?,
            ?, 0, 0, ?, ?, ?,
            CURDATE(), ?, ?, 'urun', '30 Gün', 'Test',
            'Test Teklif', 'Test için oluşturuldu', 'Test Notes', 1, 5, 'TUMU',
            ?, 'GMP', ?, ?, ?,
            ?, ?, 0, 'none', 0, ?, ?
        )";
        
        $stmt1 = $conn->prepare($sql1);
        
        $toplamTutar = $testData['list_price'] * $testData['quantity'];
        $kdv = $toplamTutar * 0.20;
        $genelToplam = $toplamTutar + $kdv;
        
        // Kurları belirle
        $curr = $testData['currency'];
        $rate = $testData['exchange_rate'] ?? 1.0;
        $eurokur = ($curr == 'EUR') ? $rate : 1.0;
        $dolarkur = ($curr == 'USD') ? $rate : 1.0;

        $stmt1->bind_param(
            'siisssddddddsiiiiiss',
            $testData['cari_name'],       // 1. musteriadi (s)
            $yonetici_id,                  // 2. hazirlayanid (i)
            $yonetici_id,                  // 3. musteriid (i)
            $testData['payment_code'],     // 4. odemeturu (s)
            $testData['cari_code'],        // 5. sirketid (s)
            $testData['cari_code'],        // 6. sirket_arp_code (s)
            $toplamTutar,                  // 7. tltutar (d)
            $toplamTutar,                  // 8. toplamtutar (d)
            $kdv,                          // 9. kdv (d)
            $genelToplam,                  // 10. geneltoplam (d)
            $eurokur,                      // 11. eurokur (d) -> YENİ
            $dolarkur,                     // 12. dolarkur (d) -> YENİ
            $testData['auxil_code'],       // 13. auxil_code (s)
            $testData['division'],         // 14. division (i)
            $testData['department'],       // 15. department (i)
            $testData['source_wh'],        // 16. source_wh (i)
            $testData['factory'],          // 17. factory (i)
            $testData['salesman_ref'],     // 18. salesmanref (i)
            $curr,                         // 19. currency (s) -> YENİ
            $testData['shipping_agent']    // 20. shipping_agent (s) -> YENİ
        );
        
        if (!$stmt1->execute()) {
            throw new Exception("Teklif kaydı oluşturulamadı: " . $stmt1->error);
        }
        
        $teklifId = $conn->insert_id;
        $stmt1->close();
        
        addDebug('STEP_1_SQL', 'Teklif kaydı oluşturuldu', [
            'sql' => $sql1,
            'teklif_id' => $teklifId,
            'toplam' => $toplamTutar,
            'kdv' => $kdv,
            'genel_toplam' => $genelToplam
        ]);
        
        // ═══════════════════════════════════════════════════════════
        // ADIM 2: ÜRÜN KALEMLERİNİ EKLE
        // ═══════════════════════════════════════════════════════════
        addDebug('STEP_2', 'Ürün kalemleri ekleniyor...');
        
        $testItems = [
            [
                'code' => $testData['product_code'],
                'name' => $testData['product_name'],
                'qty' => $testData['quantity'],
                'price' => $testData['list_price'],
                'formula' => $testData['discount_formula'] // 50-5-10
            ],
            [
                'code' => '021133A',
                'name' => '021133A Test Ürün',
                'qty' => 2,
                'price' => 500.00,
                'formula' => '20-10-5' // 3 İskonto
            ]
        ];

        $sql2 = "INSERT INTO ogteklifurun2 (
            teklifid, kod, adi, aciklama, miktar, birim, liste, doviz, 
            iskonto, iskonto_formulu, nettutar, tutar, product_internal_ref
        ) VALUES (
            ?, ?, ?, '', ?, ?, ?, ?,
            ?, ?, ?, ?, 0
        )";
        
        $stmt2 = $conn->prepare($sql2);
        
        foreach ($testItems as $item) {
            $iskonto = $useFormula ? 0 : $testData['discount'];
            $iskontoFormula = $useFormula ? $item['formula'] : '';
            
            // Net tutar hesapla
            if ($useFormula && !empty($iskontoFormula)) {
                $rates = explode('-', $iskontoFormula);
                $currPrice = $item['price'];
                foreach ($rates as $r) {
                    $currPrice = $currPrice * (1 - floatval($r)/100);
                }
                $netTutar = $currPrice;
            } else {
                $netTutar = $item['price'] * (1 - $iskonto/100);
            }
            
            $tutar = $netTutar * $item['qty'];
            
            $stmt2->bind_param(
                'issdsdsdsdd',
                $teklifId,
                $item['code'],
                $item['name'],
                $item['qty'],
                $testData['unit'],
                $item['price'],
                $testData['currency'],
                $iskonto,
                $iskontoFormula,
                $netTutar,
                $tutar
            );
            
            if (!$stmt2->execute()) {
                throw new Exception("Ürün kalemi eklenemedi ({$item['code']}): " . $stmt2->error);
            }
            
            addDebug('STEP_2_ITEM', "Ürün kalemi eklendi: {$item['code']}", [
                'code' => $item['code'],
                'iskonto_formulu' => $iskontoFormula,
                'net_tutar' => $netTutar,
                'tutar' => $tutar
            ]);
        }
        
        $stmt2->close();
        
        // ═══════════════════════════════════════════════════════════
        // ADIM 3: DATABASE'DEN VERİYİ OKU
        // ═══════════════════════════════════════════════════════════
        addDebug('STEP_3', 'Database\'den veri okunuyor...');
        
        $items = $dbManager->getOfferItems($teklifId);
        
        addDebug('STEP_3_DATA', 'Database\'den okunan veri', $items);
        
        // ═══════════════════════════════════════════════════════════
        // ADIM 4: LOGO'YA AKTAR
        // ═══════════════════════════════════════════════════════════
        addDebug('STEP_4', 'Logo aktarımı başlatılıyor...');
        
        $result = $logoService->transferOrder($teklifId);
        
        addDebug('STEP_4_RESULT', 'Logo aktarım sonucu', $result);
        
        // ═══════════════════════════════════════════════════════════
        // ADIM 5: LOGO'DAN KONTROL
        // ═══════════════════════════════════════════════════════════
        if ($result['status'] && !empty($result['response']['INTERNAL_REFERENCE'])) {
            addDebug('STEP_5', 'Logo\'dan sipariş kontrol ediliyor...');
            
            $logoOrder = $logoService->getSalesOrder($result['response']['INTERNAL_REFERENCE']);
            
            addDebug('STEP_5_LOGO_DATA', 'Logo\'dan okunan sipariş', $logoOrder);
            
            // İskonto bilgilerini kontrol et
            $logoItems = $logoService->getSalesOrderTransactions($result['response']['INTERNAL_REFERENCE']);
            
            addDebug('STEP_5_LOGO_ITEMS', 'Logo\'dan okunan kalemler', $logoItems);
        }
        
        addDebug('SUCCESS', '✅ Test tamamlandı!', [
            'teklif_id' => $teklifId,
            'logo_ref' => $result['response']['INTERNAL_REFERENCE'] ?? null,
            'logo_number' => $result['response']['NUMBER'] ?? null
        ]);
        
    } catch (Exception $e) {
        addDebug('ERROR', '❌ Hata: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo İskonto Aktarım Test</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .debug-step { 
            background: white; 
            padding: 15px; 
            margin: 10px 0; 
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .debug-step.error { border-left-color: #dc3545; }
        .debug-step.success { border-left-color: #28a745; }
        .debug-data {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        .test-config {
            background: #fff3cd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .step-header {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        .step-time {
            color: #666;
            font-size: 11px;
            float: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Logo İskonto Aktarım Test Sayfası</h1>
        <p class="text-muted">Bu sayfa iskonto aktarımını test etmek için basitleştirilmiş bir ortam sağlar.</p>
        
        <div class="test-config">
            <h5>📋 Test Yapılandırması</h5>
            <div class="row">
                <div class="col-md-6">
                    <strong>Cari:</strong> <?= $testData['cari_name'] ?> (<?= $testData['cari_code'] ?>)<br>
                    <strong>Ürün:</strong> <?= $testData['product_code'] ?><br>
                    <strong>Miktar:</strong> <?= $testData['quantity'] ?> <?= $testData['unit'] ?><br>
                    <strong>Liste Fiyatı:</strong> <?= number_format($testData['list_price'], 2) ?> <?= $testData['currency'] ?>
                </div>
                <div class="col-md-6">
                    <strong>Satış Temsilcisi REF:</strong> <?= $testData['salesman_ref'] ?> (İZMİR SATIŞ TEMSİLCİSİ)<br>
                    <strong>Bölüm:</strong> <?= $testData['division'] ?><br>
                    <strong>Departman:</strong> <?= $testData['department'] ?><br>
                    <strong>Ambar:</strong> <?= $testData['source_wh'] ?>
                </div>
            </div>
        </div>
        
        <form method="POST" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="test_transfer">
            
            <div class="card">
                <div class="card-body">
                    <h5>İskonto Tipi Seçin:</h5>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="use_formula" value="0" id="radio1" <?= !$useFormula ? 'checked' : '' ?>>
                        <label class="form-check-label" for="radio1">
                            <strong>Tek İskonto:</strong> %<?= $testData['discount'] ?> → DISCOUNT_RATE: 50
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="use_formula" value="1" id="radio2" <?= $useFormula ? 'checked' : '' ?>>
                        <label class="form-check-label" for="radio2">
                            <strong>Kademeli İskonto:</strong> <?= $testData['discount_formula'] ?> → DISCPER1:50, DISCPER2:5, DISCPER3:10
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg mt-3">
                        🚀 Test Et ve Logo'ya Aktar
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($debugOutput)): ?>
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">🔍 Debug Çıktıları (<?= count($debugOutput) ?> adım)</h5>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php foreach ($debugOutput as $debug): ?>
                    <?php 
                    $class = '';
                    if ($debug['step'] === 'ERROR') $class = 'error';
                    if ($debug['step'] === 'SUCCESS') $class = 'success';
                    ?>
                    <div class="debug-step <?= $class ?>">
                        <div class="step-header">
                            <?= htmlspecialchars($debug['step']) ?>: <?= htmlspecialchars($debug['message']) ?>
                            <span class="step-time"><?= $debug['time'] ?></span>
                        </div>
                        <?php if ($debug['data'] !== null): ?>
                            <div class="debug-data"><?= htmlspecialchars(json_encode($debug['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="teklifsiparisler.php" class="btn btn-secondary">← Tekliflere Dön</a>
        </div>
    </div>
    
    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
