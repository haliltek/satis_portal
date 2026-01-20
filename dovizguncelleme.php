<?php
// dovizguncelleme.php

session_start();
include "fonk.php";
oturumkontrol();

// Autoload ve zaman dilimi
require __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('Europe/Istanbul');

$logFile = __DIR__ . '/logs/doviz_cron.log';

// 1) Eğer önceki adımdan bir mesaj varsa al ve sil
$mesaj = '';
if (isset($_SESSION['kur_mesaj'])) {
    $mesaj = $_SESSION['kur_mesaj'];
    unset($_SESSION['kur_mesaj']);
}

// 2) POST ve buton tetiklendiyse: güncelle, session'a mesaj ata, PRG ile GET'e yönlendir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duzenlemeButon'])) {
    try {
        // Logo'dan döviz kurlarını çek
        global $gempa_logo_db;
        
        if (!$gempa_logo_db) {
            throw new Exception('Logo veritabanı bağlantısı bulunamadı.');
        }
        
        // Logo'da LG_EXCHANGE_566 tablosundan güncel kurları çek
        // CRTYPE: 1=USD, 20=EUR
        // RATES1, RATES2, RATES3, RATES4: Farklı kur türleri (genelde RATES1=Alış, RATES2=Satış)
        $sql = "
            SELECT TOP 10
                DATE_,
                CRTYPE,
                RATES1,
                RATES2,
                RATES3,
                RATES4
            FROM LG_EXCHANGE_566
            WHERE CRTYPE IN (1, 20)
            ORDER BY DATE_ DESC
        ";
        
        $stmt = $gempa_logo_db->prepare($sql);
        $stmt->execute();
        $rates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (empty($rates)) {
            throw new Exception('Logo\'dan döviz kurları alınamadı. LG_EXCHANGE_566 tablosunda veri bulunamadı.');
        }
        
        // Debug: Gelen veriyi logla
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] Logo'dan gelen kurlar: " . json_encode($rates, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        
        // Kurları parse et - Aynı tarihten (en son tarih) hem USD hem EUR al
        // NOT: LG_EXCHANGE_566 tablosunda RATES1 genelde null, RATES2 kullanılıyor
        $usd_buying = null;
        $usd_selling = null;
        $eur_buying = null;
        $eur_selling = null;
        
        // En son tarihi bul
        $latestDate = $rates[0]['DATE_'] ?? null;
        
        // Aynı tarihten hem USD hem EUR'yu al
        foreach ($rates as $rate) {
            // Sadece en son tarihteki kayıtları kullan
            if ($rate['DATE_'] !== $latestDate) continue;
            
            $crtype = (int)$rate['CRTYPE'];
            $rates2 = (float)($rate['RATES2'] ?? 0); // Ana kur değeri burada
            
            if ($rates2 <= 0) continue; // Geçersiz kur atla
            
            // USD kurları (CRTYPE = 1)
            if ($crtype === 1 && !$usd_buying) {
                $usd_buying = floor($rates2 * 100) / 100;   // 2 basamağa kes (yuvarlama YOK)
                $usd_selling = floor($rates2 * 100) / 100;  // 2 basamağa kes (yuvarlama YOK)
            }
            
            // EUR kurları (CRTYPE = 20)
            if ($crtype === 20 && !$eur_buying) {
                $eur_buying = floor($rates2 * 100) / 100;   // 2 basamağa kes (yuvarlama YOK)
                $eur_selling = floor($rates2 * 100) / 100;  // 2 basamağa kes (yuvarlama YOK)
            }
            
            // Her ikisini de bulduk mu?
            if ($usd_buying && $eur_buying) break;
        }
        
        if (!$usd_buying || !$usd_selling || !$eur_buying || !$eur_selling) {
            $debugInfo = "USD Alış: $usd_buying, USD Satış: $usd_selling, EUR Alış: $eur_buying, EUR Satış: $eur_selling | ";
            $debugInfo .= "Gelen veri sayısı: " . count($rates) . " | ";
            $debugInfo .= "İlk kayıt: " . json_encode($rates[0] ?? [], JSON_UNESCAPED_UNICODE);
            throw new Exception('Logo\'dan USD veya EUR kurları eksik. ' . $debugInfo);
        }

        // Yeni kurlar ve zaman
        $tarih = date("d.m.Y H:i");

        // Veritabanını güncelle
        $sql = "
            UPDATE dovizkuru
               SET dolaralis   = '$usd_buying',
                   dolarsatis  = '$usd_selling',
                   euroalis    = '$eur_buying',
                   eurosatis   = '$eur_selling',
                   tarih       = '$tarih'
        ";
        if (!mysqli_query($db, $sql)) {
            throw new Exception('SQL Hatası: ' . mysqli_error($db));
        }

        // Log kaydı
        $yon      = $yonetici_id_sabit ?? 'cron-job';
        $logSql   = "
            INSERT INTO log_yonetim(islem, personel, tarih, durum)
            VALUES('USD/EUR Kuru Güncelleme', '$yon', '$tarih', 'Başarılı')
        ";
        mysqli_query($db, $logSql);

        // Başarı mesajını session’a ata
        $_SESSION['kur_mesaj'] = '<div class="alert alert-success">Kurlar başarıyla güncellendi (' . $tarih . ').</div>'
            . '<script>console.log(' . json_encode("Kurlar başarıyla güncellendi ($tarih)") . ');</script>';
    }
    catch (RequestException $e) {
        $hata = $e->getMessage();
        $responseBody = '';
        if ($e->hasResponse()) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            $hata .= ' | Response: ' . $responseBody;
        }
        $tarih = date("d.m.Y H:i");
        $logSql = "
            INSERT INTO log_yonetim(islem, personel, tarih, durum)
            VALUES('USD/EUR Kuru Güncelleme','cron-job','$tarih','Hata: " . addslashes($hata) . "')
        ";
        mysqli_query($db, $logSql);
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] $hata\n" . $e->getTraceAsString() . "\n", FILE_APPEND);

        $_SESSION['kur_mesaj'] = '<div class="alert alert-danger">Güncelleme hatası: ' . htmlspecialchars($hata) . '</div>'
            . '<script>console.error(' . json_encode($hata) . ');'
            . ($responseBody ? 'console.error(' . json_encode($responseBody) . ');' : '')
            . '</script>';
    }
    catch (Exception $e) {
        $hata = $e->getMessage();
        $tarih = date("d.m.Y H:i");
        $logSql = "
            INSERT INTO log_yonetim(islem, personel, tarih, durum)
            VALUES('USD/EUR Kuru Güncelleme','cron-job','$tarih','Hata: " . addslashes($hata) . "')
        ";
        mysqli_query($db, $logSql);
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] $hata\n" . $e->getTraceAsString() . "\n", FILE_APPEND);

        $_SESSION['kur_mesaj'] = '<div class="alert alert-danger">Güncelleme hatası: ' . htmlspecialchars($hata) . '</div>'
            . '<script>console.error(' . json_encode($hata) . ');</script>';
    }

    // PRG: POST’tan sonra aynı sayfaya GET ile yönlendir
    header('Location: dovizguncelleme.php');
    exit;
}

// 3) Veritabanındaki mevcut kurları al
$dbRes = mysqli_query($db, "SELECT dolaralis, dolarsatis, euroalis, eurosatis, tarih FROM dovizkuru LIMIT 1");
$dbKur = mysqli_fetch_assoc($dbRes);

// 4) "Canlı" kurları almaya çalış, hata olursa uyarı
$canliErr = '';
$canliUSD = $canliEUR = null;
try {
    // Logo'dan döviz kurlarını çek
    global $gempa_logo_db;
    
    if (!$gempa_logo_db) {
        throw new Exception('Logo veritabanı bağlantısı bulunamadı.');
    }
    
    // Logo'da LG_EXCHANGE_566 tablosundan güncel kurları çek
    $sql = "
        SELECT TOP 10
            DATE_,
            CRTYPE,
            RATES1,
            RATES2
        FROM LG_EXCHANGE_566
        WHERE CRTYPE IN (1, 20)
        ORDER BY DATE_ DESC
    ";
    
    $stmt = $gempa_logo_db->prepare($sql);
    $stmt->execute();
    $rates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (empty($rates)) {
        throw new Exception('Logo\'dan canlı kurlar alınamadı.');
    }
    
    // Kurları parse et - Aynı tarihten hem USD hem EUR al
    // NOT: LG_EXCHANGE_566 tablosunda RATES1 genelde null, RATES2 kullanılıyor
    $usd_buy = null;
    $usd_sell = null;
    $eur_buy = null;
    $eur_sell = null;
    
    // En son tarihi bul
    $latestDate = $rates[0]['DATE_'] ?? null;
    
    // Aynı tarihten hem USD hem EUR'yu al
    foreach ($rates as $rate) {
        // Sadece en son tarihteki kayıtları kullan
        if ($rate['DATE_'] !== $latestDate) continue;
        
        $crtype = (int)$rate['CRTYPE'];
        $rates2 = (float)($rate['RATES2'] ?? 0);
        
        if ($rates2 <= 0) continue;
        
        // USD kurları (CRTYPE = 1)
        if ($crtype === 1 && !$usd_buy) {
            $usd_buy = floor($rates2 * 100) / 100;
            $usd_sell = floor($rates2 * 100) / 100;
        }
        
        // EUR kurları (CRTYPE = 20)
        if ($crtype === 20 && !$eur_buy) {
            $eur_buy = floor($rates2 * 100) / 100;
            $eur_sell = floor($rates2 * 100) / 100;
        }
        
        // Her ikisini de bulduk mu?
        if ($usd_buy && $eur_buy) break;
    }
    
    if ($usd_buy && $usd_sell) {
        $canliUSD = ['buy' => $usd_buy, 'sell' => $usd_sell];
    }
    if ($eur_buy && $eur_sell) {
        $canliEUR = ['buy' => $eur_buy, 'sell' => $eur_sell];
    }
    
    if (!$canliUSD || !$canliEUR) {
        throw new Exception('Logo\'dan USD veya EUR kurları eksik. Gelen veri: ' . json_encode($rates, JSON_UNESCAPED_UNICODE));
    }
} catch (Exception $e) {
    $canliErr = $e->getMessage();
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] $canliErr\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta content="<?php echo $sistemayar["description"]; ?>" name="description"/>
    <meta content="<?php echo $sistemayar["keywords"]; ?>" name="keywords"/>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="assets/css/icons.min.css" rel="stylesheet"/>
    <link href="assets/css/app.min.css" rel="stylesheet"/>
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet"/>
</head>
<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <?php include "menuler/ustmenu.php"; ?>
        <?php include "menuler/solmenu.php"; ?>

        <div class="main-content">
            <div class="page-content container-fluid">

                <!-- 1) Mesaj (POST’tan sonra bir kere gösterilir) -->
                <?php echo $mesaj; ?>

                <!-- 2) Veritabanındaki Kurlar -->
                <div class="card mb-4">
                    <div class="card-header"><strong>Kaydedilmiş Döviz Kurları</strong></div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Dolar Alış</th><th>Dolar Satış</th>
                                    <th>Euro Alış</th><th>Euro Satış</th>
                                    <th>Son Güncelleme</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo $dbKur["dolaralis"]; ?></td>
                                    <td><?php echo $dbKur["dolarsatis"]; ?></td>
                                    <td><?php echo $dbKur["euroalis"]; ?></td>
                                    <td><?php echo $dbKur["eurosatis"]; ?></td>
                                    <td><?php echo $dbKur["tarih"]; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3) Canlı Kurlar -->
                <div class="card mb-4">
                    <div class="card-header"><strong>Canlı Döviz Kurları</strong></div>
                    <div class="card-body">
                        <?php if ($canliErr): ?>
                            <div class="alert alert-warning">Canlı veriler alınamadı: <?php echo htmlspecialchars($canliErr); ?></div>
                            <script>console.error(<?php echo json_encode($canliErr); ?>);</script>
                        <?php else: ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Dolar Alış</th><th>Dolar Satış</th>
                                        <th>Euro Alış</th><th>Euro Satış</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo $canliUSD['buy']; ?></td>
                                        <td><?php echo $canliUSD['sell']; ?></td>
                                        <td><?php echo $canliEUR['buy']; ?></td>
                                        <td><?php echo $canliEUR['sell']; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4) Güncelleme Butonu -->
                <form method="post" class="text-center mb-4">
                    <button type="submit" name="duzenlemeButon" class="btn btn-success btn-lg">
                        Veritabanını Güncelle
                    </button>
                </form>

            </div> <!-- /.page-content -->
            <?php include "menuler/footer.php"; ?>
        </div> <!-- /.main-content -->
    </div> <!-- /#layout-wrapper -->

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
