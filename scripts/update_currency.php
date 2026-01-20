#!/usr/bin/env php
<?php
// CLI’den çalıştırıldığında rahatça görebilmek için:
if (PHP_SAPI !== 'cli') {
    exit("Bu script sadece CLI modunda çalışır.\n");
}

// Zaman dilimini sabitle (sunucu UTC ise bile İstanbul saatiyle tarih yazsın)
date_default_timezone_set('Europe/Istanbul');

// Proje ayarları, autoload, DB bağlantısı vs:
require __DIR__ . '/../fonk.php';            // oturumkontrol falan varsa kaldırabilirsin
require __DIR__ . '/../vendor/autoload.php'; // Ahmeti\BankExchangeRates vs

use GuzzleHttp\Exception\RequestException;

$logFile = __DIR__ . '/../logs/doviz_cron.log';

try {
    $rates = (new \Ahmeti\BankExchangeRates\Service)->get();
    $usdRates = $rates['USD/TRY'] ?? [];
    $eurRates = $rates['EUR/TRY'] ?? [];

    // Garanti verilerini ayıkla
    $garantiUSD = null;
    foreach ($usdRates as $r) {
        if (isset($r['name']) && strtolower($r['name']) === 'garanti') {
            $garantiUSD = $r; break;
        }
    }
    $garantiEUR = null;
    foreach ($eurRates as $r) {
        if (isset($r['name']) && strtolower($r['name']) === 'garanti') {
            $garantiEUR = $r; break;
        }
    }

    if (!$garantiUSD || !$garantiEUR) {
        throw new Exception('Garanti USD veya EUR verisi bulunamadı.');
    }

    $tarih       = date("d.m.Y H:i");
    $usd_buying  = $garantiUSD['buy'];
    $usd_selling = $garantiUSD['sell'];
    $eur_buying  = $garantiEUR['buy'];
    $eur_selling = $garantiEUR['sell'];

    $sql = "
        UPDATE dovizkuru
           SET dolaralis   = '$usd_buying',
               dolarsatis  = '$usd_selling',
               euroalis    = '$eur_buying',
               eurosatis   = '$eur_selling',
               tarih       = '$tarih'
    ";
    if (!mysqli_query($db, $sql)) {
        throw new Exception("Güncelleme hatası: " . mysqli_error($db));
    }

    // Log kaydı (isteğe bağlı)
    $yonetici = 'cron-job';
    $logSql = "
        INSERT INTO log_yonetim(islem, personel, tarih, durum)
        VALUES('USD/EUR Kuru Güncelleme', '$yonetici', '$tarih', 'Başarılı')
    ";
    mysqli_query($db, $logSql);

    echo "Başarıyla güncellendi: $tarih\n";

} catch (RequestException $ex) {
    $err = $ex->getMessage();
    $body = '';
    if ($ex->hasResponse()) {
        $body = $ex->getResponse()->getBody()->getContents();
        $err .= ' | Response: ' . $body;
    }
    echo "Hata: $err\n";
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] $err\n" . $ex->getTraceAsString() . "\n", FILE_APPEND);
    $tarih = date("d.m.Y H:i");
    $logSql = "
        INSERT INTO log_yonetim(islem, personel, tarih, durum)
        VALUES('USD/EUR Kuru Güncelleme', 'cron-job', '$tarih', 'Hata: " . addslashes($err) . "')
    ";
    mysqli_query($db, $logSql);
    exit(1);
} catch (Exception $ex) {
    $err = $ex->getMessage();
    echo "Hata: $err\n";
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] $err\n", FILE_APPEND);
    $tarih = date("d.m.Y H:i");
    $logSql = "
        INSERT INTO log_yonetim(islem, personel, tarih, durum)
        VALUES('USD/EUR Kuru Güncelleme', 'cron-job', '$tarih', 'Hata: " . addslashes($err) . "')
    ";
    mysqli_query($db, $logSql);
    exit(1);
}
