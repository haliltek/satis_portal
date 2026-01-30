<?php
require_once __DIR__ . '/../src/Repositories/LogoPriceUpsertRepository.php';

use App\Repositories\LogoPriceUpsertRepository;

class PriceUpdater
{
    private $localMysqlDB;
    private $gemasLogoDB;
    private $gempaLogoDB;
    private $gemasWebDB;
    private $logger;
    private $yoneticiID;

    public function __construct($localMysqlDB, $gemasLogoDB, $gempaLogoDB, $gemasWebDB, $logger, $yoneticiID)
    {
        $this->localMysqlDB    = $localMysqlDB;
        $this->gemasLogoDB = $gemasLogoDB;
        $this->gempaLogoDB = $gempaLogoDB;
        $this->gemasWebDB = $gemasWebDB;
        $this->logger     = $logger;
        $this->yoneticiID = $yoneticiID;
    }

    /**
     * Hem yurtiçi hem ihracat fiyatlarını kontrol edip, yalnızca fark olanları ilgili platformlarda günceller.
     * Artık, LOGO güncellemeleri iki farklı alanda gerçekleşmektedir:
     *   - GEMPA (Logo) : Gemas Pazarlama için.
     *   - GEMAS (Logo) : Diğer LOGO güncellemesi.
     * Web tarafındaki Gemas Güncellemesi kısmı olduğu gibi kalmaya devam eder.
     *
     * @param string $stokKodu
     * @param int    $gempaLogoLogicalRef  GEMPA (Logo) için logical ref değeri (0 ise güncelleme yapılamaz)
     * @param int    $gemasLogoLogicalRef   GEMAS (Logo) için logical ref değeri (0 ise güncelleme yapılamaz)
     * @param float  $yeniDomesticFiyat
     * @param float  $yeniExportFiyat
     * @return array Güncelleme sonuçları.
     */
    public function updatePrices(string $stokKodu, int $gempaLogoLogicalRef, int $gemasLogoLogicalRef, float $yeniDomesticFiyat, float $yeniExportFiyat): array
    {
        $this->logger->log("INFO", "Fiyat güncelleme başladı: $stokKodu");

        // 1. Mevcut fiyatları al
        $mevcutDomesticFiyat = $this->localMysqlDB ? $this->getCurrentMysqlPrice($stokKodu) : null;
        $mevcutExportFiyat   = $this->localMysqlDB ? $this->getCurrentMysqlExportPrice($stokKodu) : null;

        $hasMysqlData = ($mevcutDomesticFiyat !== null && $mevcutExportFiyat !== null);

        // 2. Platform isimleri
        $platformDisplayName = [
            'mysql'      => 'Satış Web Veritabanı',
            'logo_gempa' => 'Logo Gempa Veritabanı',
            'logo_gemas' => 'Logo Gemas Veritabanı',
            'web'        => 'Gemaş Web Veritabanı',
        ];

        // 3. Her platformun güncelleme sonuçlarını tut
        $platformResults = [];

        // --- 3.1 Satış Web Veritabanı (MySQL) güncellemesi ---
        if ($hasMysqlData) {
            $mysqlDomesticResult = ['success' => true, 'error' => 'No change'];
            if ($yeniDomesticFiyat != $mevcutDomesticFiyat) {
                $mysqlDomesticResult = $this->updateMysqlPrice($stokKodu, $yeniDomesticFiyat);
                if ($mysqlDomesticResult['success']) {
                    $this->updateTimestamp('mysql_guncelleme', $stokKodu);
                    $this->insertPriceLogDomestic($stokKodu, $mevcutDomesticFiyat, $yeniDomesticFiyat);
                }
            }

            $mysqlExportResult = ['success' => true, 'error' => 'No change'];
            if ($yeniExportFiyat != $mevcutExportFiyat) {
                $mysqlExportResult = $this->updateMysqlExportPrice($stokKodu, $yeniExportFiyat);
                if ($mysqlExportResult['success']) {
                    $this->updateTimestamp('export_mysql_guncelleme', $stokKodu);
                    $this->insertPriceLogExport($stokKodu, $mevcutExportFiyat, $yeniExportFiyat);
                }
            }
        } else {
            $mysqlDomesticResult = [
                'success' => null,
                'error'   => 'MySQL kaydı bulunamadı'
            ];
            $mysqlExportResult   = [
                'success' => null,
                'error'   => 'MySQL kaydı bulunamadı'
            ];
        }

        $platformResults['mysql'] = [
            'domestic' => $mysqlDomesticResult,
            'export'   => $mysqlExportResult
        ];

        // --- 3.2 LogoGempaVeritabanı güncellemesi ---
        $gempaDomesticResult = ['success' => true, 'error' => 'No change'];
        if ($yeniDomesticFiyat != $mevcutDomesticFiyat) {
            if ($gempaLogoLogicalRef > 0) {
                $gempaDomesticResult = $this->updateGempaLogoPrice($gempaLogoLogicalRef, $yeniDomesticFiyat, $stokKodu);
            } else {
                $gempaDomesticResult = [
                    'success' => null,
                    'error'   => 'Logo Gempa kaydı bulunamadı'
                ];
            }
        }

        $gempaExportResult = ['success' => true, 'error' => 'No change'];
        if ($yeniExportFiyat != $mevcutExportFiyat) {
            if ($gempaLogoLogicalRef > 0) {
                $gempaExportResult = $this->updateGempaLogoExportPrice($gempaLogoLogicalRef, $yeniExportFiyat, $stokKodu);
            } else {
                $gempaExportResult = [
                    'success' => null,
                    'error'   => 'Logo Gempa kaydı bulunamadı'
                ];
            }
        }

        $platformResults['logo_gempa'] = [
            'domestic' => $gempaDomesticResult,
            'export'   => $gempaExportResult
        ];

        // --- 3.3 LogoGemasVeritabanı güncellemesi ---
        $gemasDomesticResult = ['success' => true, 'error' => 'No change'];
        if ($yeniDomesticFiyat != $mevcutDomesticFiyat) {
            if ($gemasLogoLogicalRef > 0) {
                $gemasDomesticResult = $this->updateGemasLogoPrice($gemasLogoLogicalRef, $yeniDomesticFiyat, $stokKodu);
            } else {
                $gemasDomesticResult = [
                    'success' => null,
                    'error'   => 'Logo Gemas kaydı bulunamadı'
                ];
            }
        }

        $gemasExportResult = ['success' => true, 'error' => 'No change'];
        if ($yeniExportFiyat != $mevcutExportFiyat) {
            if ($gemasLogoLogicalRef > 0) {
                $gemasExportResult = $this->updateGemasLogoExportPrice($gemasLogoLogicalRef, $yeniExportFiyat, $stokKodu);
            } else {
                $gemasExportResult = [
                    'success' => null,
                    'error'   => 'Logo Gemas kaydı bulunamadı'
                ];
            }
        }

        $platformResults['logo_gemas'] = [
            'domestic' => $gemasDomesticResult,
            'export'   => $gemasExportResult
        ];

        // --- 3.4 GemasWebSite güncellemesi ---
        $webDomesticResult = ['success' => true, 'error' => 'No change'];
        if ($yeniDomesticFiyat != $mevcutDomesticFiyat) {
            // 1) malzemeviews
            $webDomesticResult = $this->updateGemasTRPrice($stokKodu, $yeniDomesticFiyat);
            // 2) portal_urunler.fiyat
            if ($webDomesticResult['success']) {
                $portalResult = $this->updatePortalLocalPrice($stokKodu, $yeniDomesticFiyat);
                if (! $portalResult['success']) {
                    // portal güncellemesi başarısızsa hata olarak birleştir
                    $webDomesticResult['success'] = false;
                    $webDomesticResult['error']   .= ' | Portal lokal fiyat: ' . $portalResult['error'];
                }
            }
            if ($webDomesticResult['success']) {
                $this->updateTimestamp('gemas_guncelleme', $stokKodu);
            }
        }

        $webExportResult = ['success' => true, 'error' => 'No change'];
        if ($yeniExportFiyat != $mevcutExportFiyat) {
            // 1) malzeme_viewEN
            $webExportResult = $this->updateGemasExportPrice($stokKodu, $yeniExportFiyat);
            // 2) portal_urunler.export_fiyat
            if ($webExportResult['success']) {
                $portalExp = $this->updatePortalExportPrice($stokKodu, $yeniExportFiyat);
                if (! $portalExp['success']) {
                    $webExportResult['success'] = false;
                    $webExportResult['error']   .= ' | Portal export fiyat: ' . $portalExp['error'];
                }
            }
            if ($webExportResult['success']) {
                $this->updateTimestamp('export_gemas_guncelleme', $stokKodu);
            }
        }

        $platformResults['web'] = [
            'domestic' => $webDomesticResult,
            'export'   => $webExportResult
        ];

        // 4. Başarı ve hata listelerini hazırla
        $successfulPlatforms = [];
        $failedPlatforms     = [];
        $skippedPlatforms    = [];

        foreach ($platformResults as $platformKey => $updateResults) {
            $hasSuccess = false;
            $hasFailure = false;
            $hasSkip    = false;

            foreach (['domestic', 'export'] as $priceType) {
                $err = $updateResults[$priceType]['error'];
                $successVal = $updateResults[$priceType]['success'];

                if ($successVal === true && $err !== 'No change') {
                    $hasSuccess = true;
                }
                if ($successVal === false) {
                    $hasFailure = true;
                }
                if ($successVal === null) {
                    $hasSkip = true;
                }
            }

            if ($hasSuccess) {
                $successfulPlatforms[] = $platformDisplayName[$platformKey];
            }
            if ($hasFailure) {
                $failedPlatforms[] = $platformDisplayName[$platformKey];
            }
            if ($hasSkip && !$hasSuccess && !$hasFailure) {
                $skippedPlatforms[] = $platformDisplayName[$platformKey];
            }
        }

        // 5. overallStatus ve mesaj oluştur
        if (empty($successfulPlatforms) && empty($failedPlatforms) && empty($skippedPlatforms)) {
            $overallStatus = 'no_change';
            $statusMessage = 'Hiçbir platformda fiyat güncellemesi yapılmadı.';
        } elseif (!empty($failedPlatforms)) {
            $overallStatus = 'partial';
            $statusMessage = 'Güncellemesi başarılı olan platformlar: '
                . implode(', ', $successfulPlatforms);
            $statusMessage .= '. Başarısız olan: ' . implode(', ', $failedPlatforms);
            if (!empty($skippedPlatforms)) {
                $statusMessage .= '. Atlanan: ' . implode(', ', $skippedPlatforms);
            }
            $statusMessage .= '.';
        } else {
            $overallStatus = 'success';
            $statusMessage = 'Güncellemesi başarılı olan platformlar: '
                . implode(', ', $successfulPlatforms);
            if (!empty($skippedPlatforms)) {
                $statusMessage .= '. Atlanan: ' . implode(', ', $skippedPlatforms) . '.';
            } else {
                $statusMessage .= '.';
            }
        }

        // 6. Log ve dönüş
        $this->insertManagementLog($overallStatus);
        $this->logger->log("INFO", "Fiyat güncelleme tamamlandı: $overallStatus");

        if (
            isset($platformResults['logo_gemas']['export']) &&
            $platformResults['logo_gemas']['export']['success'] === false
        ) {
            $platformResults['logo_gemas']['export']['ignored_error'] = $platformResults['logo_gemas']['export']['error'];
            $platformResults['logo_gemas']['export']['success']       = true;
        }

        return [
            'stokKodu'         => $stokKodu,
            'oldDomesticPrice' => $mevcutDomesticFiyat,
            'oldExportPrice'   => $mevcutExportFiyat,
            'newDomesticPrice' => $yeniDomesticFiyat,
            'newExportPrice'   => $yeniExportFiyat,
            'platforms'        => $platformResults,
            'overallStatus'    => $overallStatus,
            'message'          => $statusMessage
        ];
    }

    // Aşağıdaki metodlar, önceki haliyle devam ediyor.
    private function updateTimestamp(string $column, string $stokKodu): array
    {
        $query = "UPDATE urunler SET $column = NOW() WHERE stokkodu = ?";
        $stmt = $this->localMysqlDB->prepare($query);
        if (!$stmt) {
            $msg = "$column güncelleme tarihi statement oluşturulamadı.";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param("s", $stokKodu);
        if (!$stmt->execute()) {
            $msg = "$column güncelleme tarihi güncelleme hatası: " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }
        $stmt->close();
        $this->logger->log("updateMysqlActive success for {$stokKodu}");
        return ['success' => true, 'error' => ''];
    }

    private function getCurrentMysqlPrice(string $stokKodu): ?float
    {
        $stmt = $this->localMysqlDB->prepare("SELECT fiyat FROM urunler WHERE stokkodu = ?");
        if (!$stmt) {
            $this->logger->log("MySQL SELECT statement oluşturulamadı.", "ERROR");
            return null;
        }
        $stmt->bind_param("s", $stokKodu);
        if (!$stmt->execute()) {
            $this->logger->log("MySQL SELECT hatası: " . $stmt->error, "ERROR");
            $stmt->close();
            return null;
        }
        $fiyat = null;
        $stmt->bind_result($fiyat);
        $stmt->fetch();
        $stmt->close();
        return $fiyat;
    }

    private function getCurrentMysqlExportPrice(string $stokKodu): ?float
    {
        $stmt = $this->localMysqlDB->prepare("SELECT export_fiyat FROM urunler WHERE stokkodu = ?");
        if (!$stmt) {
            $this->logger->log("MySQL SELECT (export) statement oluşturulamadı.", "ERROR");
            return null;
        }
        $stmt->bind_param("s", $stokKodu);
        if (!$stmt->execute()) {
            $this->logger->log("MySQL SELECT (export) hatası: " . $stmt->error, "ERROR");
            $stmt->close();
            return null;
        }
        $exportFiyat = null;
        $stmt->bind_result($exportFiyat);
        $stmt->fetch();
        $stmt->close();
        return $exportFiyat;
    }

    private function getProductName(string $stokKodu): ?string
    {
        $stmt = $this->localMysqlDB->prepare("SELECT stokadi FROM urunler WHERE stokkodu = ?");
        if (!$stmt) {
            $this->logger->log('Stok adı sorgusu hazırlanamadı.', 'ERROR');
            return null;
        }
        $stmt->bind_param('s', $stokKodu);
        if (!$stmt->execute()) {
            $this->logger->log('Stok adı sorgusu hatası: ' . $stmt->error, 'ERROR');
            $stmt->close();
            return null;
        }
        $name = null;
        $stmt->bind_result($name);
        $stmt->fetch();
        $stmt->close();
        return $name;
    }

    private function updateMysqlPrice(string $stokKodu, float $yeniFiyat): array
    {
        if (!$this->localMysqlDB) {
            return ['success' => false, 'error' => 'MySQL bağlantısı yok.'];
        }
        $stmt = $this->localMysqlDB->prepare("UPDATE urunler SET fiyat = ? WHERE stokkodu = ?");
        if (!$stmt) {
            $msg = "Ana DB UPDATE statement oluşturulamadı.";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param("ds", $yeniFiyat, $stokKodu);
        if (!$stmt->execute()) {
            $msg = "Ana DB güncelleme hatası: " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }
        $stmt->close();
        return ['success' => true, 'error' => ''];
    }

    private function updateMysqlExportPrice(string $stokKodu, float $yeniExportFiyat): array
    {
        if (!$this->localMysqlDB) {
            return ['success' => false, 'error' => 'MySQL bağlantısı yok.'];
        }
        $stmt = $this->localMysqlDB->prepare("UPDATE urunler SET export_fiyat = ? WHERE stokkodu = ?");
        if (!$stmt) {
            $msg = "Ana DB (export) UPDATE statement oluşturulamadı.";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param("ds", $yeniExportFiyat, $stokKodu);
        if (!$stmt->execute()) {
            $msg = "Ana DB (export) güncelleme hatası: " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }
        $stmt->close();
        return ['success' => true, 'error' => ''];
    }

    /**
     * GEMPA (Logo) için fiyat güncellemesi.
     */
    private function updateGempaLogoPrice(int $cardRef, float $price, string $stokKodu): array
    {
        // Satış fiyatı (ptype=2)
        $salesRepo = new LogoPriceUpsertRepository(
            $this->gempaLogoDB,
            $this->logger,
            2
        );
        $salesResult = $salesRepo->upsertPrice('LG_566_PRCLIST', $cardRef, $price, '');

        // Satın alma fiyatını da aynı değere güncelle (ptype=1)
        $purchaseRepo = new LogoPriceUpsertRepository(
            $this->gempaLogoDB,
            $this->logger,
            1
        );
        $purchaseRepo->upsertPrice('LG_566_PRCLIST', $cardRef, $price, '');

        if ($salesResult['success']) {
            $this->updateTimestamp('logo_guncelleme', $stokKodu);
            $this->logger->log(
                'INFO',
                "Gempa Logo price {$salesResult['action']} (LogicalRef: {$salesResult['newLogicalRef']})"
            );
        }

        return $salesResult;
    }

    /**
     * GEMPA (Logo) ihracat fiyatı.
     */
    private function updateGempaLogoExportPrice(int $cardRef, float $price, string $stokKodu): array
    {
        $repo = new LogoPriceUpsertRepository(
            $this->gempaLogoDB,
            $this->logger
        );
        $result = $repo->upsertPrice('LG_566_PRCLIST', $cardRef, $price, 'EXPORT');
        if ($result['success']) {
            $this->updateTimestamp('export_logo_guncelleme', $stokKodu);
            $this->logger->log("INFO", "Gempa Logo EXPORT price {$result['action']} (LogicalRef: {$result['newLogicalRef']})");
        }
        return $result;
    }

    /**
     * GEMAS (Logo) için fiyat güncellemesi.
     */
    private function updateGemasLogoPrice(int $cardRef, float $price, string $stokKodu): array
    {
        // Satış fiyatı (ptype=2)
        $salesRepo = new LogoPriceUpsertRepository(
            $this->gemasLogoDB,
            $this->logger,
            2
        );
        $salesResult = $salesRepo->upsertPrice('LG_526_PRCLIST', $cardRef, $price, '');

        // Satın alma fiyatı da aynı değere güncellensin (ptype=1)
        $purchaseRepo = new LogoPriceUpsertRepository(
            $this->gemasLogoDB,
            $this->logger,
            1
        );
        $purchaseRepo->upsertPrice('LG_526_PRCLIST', $cardRef, $price, '');

        if ($salesResult['success']) {
            $this->updateTimestamp('logo_guncelleme', $stokKodu);
            $this->logger->log(
                'INFO',
                "Gemas Logo price {$salesResult['action']} (LogicalRef: {$salesResult['newLogicalRef']})"
            );
        }

        return $salesResult;
    }

    /**
     * GEMAS (Logo) ihracat fiyatı.
     */
    private function updateGemasLogoExportPrice(int $cardRef, float $price, string $stokKodu): array
    {
        $repo = new LogoPriceUpsertRepository(
            $this->gemasLogoDB,
            $this->logger
        );
        $result = $repo->upsertPrice('LG_526_PRCLIST', $cardRef, $price, 'EXPORT');
        if ($result['success']) {
            $this->updateTimestamp('export_logo_guncelleme', $stokKodu);
            $this->logger->log("INFO", "Gemas Logo EXPORT price {$result['action']} (LogicalRef: {$result['newLogicalRef']})");
        }
        return $result;
    }

    private function updateGemasTRPrice(string $stokKodu, float $yeniFiyat): array
    {
        if (!$this->gemasWebDB) {
            return ['success' => false, 'error' => 'Gemaş Web DB bağlantısı yok.'];
        }
        $stmt = $this->gemasWebDB->prepare("UPDATE malzemeviews SET fiyat = ? WHERE stok_kodu = ?");
        if (!$stmt) {
            $msg = "Gemas DB UPDATE statement oluşturulamadı.";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param("ds", $yeniFiyat, $stokKodu);
        if (!$stmt->execute()) {
            $msg = "Gemas DB güncelleme hatası: " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            return [
                'success' => null,
                'error'   => 'Web veritabanında kayıt yok'
            ];
        }

        $stmt->close();
        return ['success' => true, 'error' => ''];
    }

    private function updateGemasExportPrice(string $stokKodu, float $yeniExportFiyat): array
    {
        if (!$this->gemasWebDB) {
            return ['success' => false, 'error' => 'Gemaş Web DB bağlantısı yok.'];
        }
        $stmt = $this->gemasWebDB->prepare("UPDATE malzeme_viewEN SET fiyat = ? WHERE stok_kodu = ?");
        if (!$stmt) {
            $msg = "Gemas DB (export) UPDATE statement oluşturulamadı.";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param("ds", $yeniExportFiyat, $stokKodu);
        if (!$stmt->execute()) {
            $msg = "Gemas DB (export) güncelleme hatası: " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            return [
                'success' => null,
                'error'   => 'Web veritabanında kayıt yok'
            ];
        }

        $stmt->close();
        return ['success' => true, 'error' => ''];
    }

    // 3) portal_urunler.fiyat sütununu güncelleyen metot
    /**
     * Suggested Items tablosuna stok koduna göre ekleme veya güncelleme yapar.
     * Web ve Logo fiyatını gelen $fiyat ile ayarlar,
     * notlar sütununa "2025 güncel fiyat" yazar.
     *
     * @param string $stokKodu  Ürünün stok kodu (unique)
     * @param float  $fiyat     Hem web_fiyat hem logo_fiyat olarak atanacak değer
     * @return array            ['success' => bool, 'error' => string]
     */
    private function updateSuggestedItemsPrice(string $stokKodu, float $fiyat): array
    {
        if (!$this->gemasWebDB) {
            return ['success' => false, 'error' => 'Gemaş Web DB bağlantısı yok.'];
        }
        // Sabit not bilgisi
        $notlar = '2025 güncel fiyat';

        $sql = "
        INSERT INTO suggested_items
            (stok_kodu, web_fiyat, logo_fiyat, notlar, last_updated, durum)
        VALUES
            (?,         ?,         ?,          ?,      NOW(),        2)
        ON DUPLICATE KEY UPDATE
            web_fiyat    = VALUES(web_fiyat),
            logo_fiyat   = VALUES(logo_fiyat),
            notlar       = VALUES(notlar),
            last_updated = NOW(),
            durum        = 2
    ";

        $stmt = $this->gemasWebDB->prepare($sql);
        if (!$stmt) {
            $msg = "Statement oluşturulamadı (suggested_items sync).";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }

        // Bind parametreler: stok_kodu (s), web_fiyat (d), logo_fiyat (d), notlar (s)
        $stmt->bind_param("sdds", $stokKodu, $fiyat, $fiyat, $notlar);

        if (!$stmt->execute()) {
            $msg = "Sync hatası (suggested_items): " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }

        $stmt->close();
        return ['success' => true, 'error' => ''];
    }

    // Lokal fiyat güncelleme fonksiyonu
    private function updatePortalLocalPrice(string $stokKodu, float $yeniFiyat): array
    {
        if (!$this->gemasWebDB) {
            return ['success' => false, 'error' => 'Gemaş Web DB bağlantısı yok.'];
        }
        $stmt = $this->gemasWebDB->prepare(
            "UPDATE portal_urunler SET fiyat = ? WHERE stokkodu = ?"
        );
        if (!$stmt) {
            $msg = "Statement oluşturulamadı (portal_urunler.fiyat).";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param("ds", $yeniFiyat, $stokKodu);
        if (!$stmt->execute()) {
            $msg = "Güncelleme hatası (portal_urunler.fiyat): " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            return [
                'success' => null,
                'error'   => 'Web veritabanında kayıt yok'
            ];
        }

        $stmt->close();

        // suggested_items tablosunu da güncelle
        $suggestedResult = $this->updateSuggestedItemsPrice($stokKodu, $yeniFiyat);
        if (!$suggestedResult['success']) {
            return $suggestedResult;
        }

        return ['success' => true, 'error' => ''];
    }

    // Export fiyat güncelleme fonksiyonu
    private function updatePortalExportPrice(string $stokKodu, float $yeniExportFiyat): array
    {
        if (!$this->gemasWebDB) {
            return ['success' => false, 'error' => 'Gemaş Web DB bağlantısı yok.'];
        }
        $stmt = $this->gemasWebDB->prepare(
            "UPDATE portal_urunler SET export_fiyat = ? WHERE stokkodu = ?"
        );
        if (!$stmt) {
            $msg = "Statement oluşturulamadı (portal_urunler.export_fiyat).";
            $this->logger->log($msg, "ERROR");
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param("ds", $yeniExportFiyat, $stokKodu);
        if (!$stmt->execute()) {
            $msg = "Güncelleme hatası (portal_urunler.export_fiyat): " . $stmt->error;
            $this->logger->log($msg, "ERROR");
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }

        if ($stmt->affected_rows === 0) {
            $stmt->close();
            return [
                'success' => null,
                'error'   => 'Web veritabanında kayıt yok'
            ];
        }

        $stmt->close();

        // suggested_items tablosunu da güncelle
        $suggestedResult = $this->updateSuggestedItemsPrice($stokKodu, $yeniExportFiyat);
        if (!$suggestedResult['success']) {
            return $suggestedResult;
        }

        return ['success' => true, 'error' => ''];
    }

    /**
     * Update product active status across MySQL and Logo databases.
     */
    public function updateActiveStatus(string $stokKodu, int $gempaLogoLogicalRef, int $gemasLogoLogicalRef, int $active): array
    {
        $this->logger->log("updateActiveStatus: {$stokKodu} active={$active} gempa={$gempaLogoLogicalRef} gemas={$gemasLogoLogicalRef}");
        $results = [];
        $results['mysql'] = $this->updateMysqlActive($stokKodu, $active);
        if ($gempaLogoLogicalRef > 0) {
            $results['logo_gempa'] = $this->updateGempaLogoActive($gempaLogoLogicalRef, $active);
        } else {
            $results['logo_gempa'] = ['success' => false, 'error' => 'LogicalRef 0'];
        }
        if ($gemasLogoLogicalRef > 0) {
            $results['logo_gemas'] = $this->updateGemasLogoActive($gemasLogoLogicalRef, $active);
        } else {
            $results['logo_gemas'] = ['success' => false, 'error' => 'LogicalRef 0'];
        }

        $overall = ($results['mysql']['success'] && $results['logo_gempa']['success'] && $results['logo_gemas']['success']) ? 'success' : 'partial';

        $names = [
            'mysql'      => 'Yerel Veritabanı',
            'logo_gempa' => 'Logo Gempa',
            'logo_gemas' => 'Logo Gemas',
        ];
        $successes = [];
        $fails     = [];
        foreach ($results as $key => $r) {
            if ($r['success']) {
                $successes[] = $names[$key];
            } else {
                $fails[] = $names[$key];
            }
        }
        $message = '';
        if (empty($fails)) {
            $message = 'Tüm veritabanlarında güncelleme başarılı.';
        } else {
            if (!empty($successes)) {
                $message .= 'Başarılı: ' . implode(', ', $successes) . '. ';
            }
            $message .= 'Başarısız: ' . implode(', ', $fails) . '.';
        }

        $this->logger->log('updateActiveStatus results: ' . json_encode($results));
        return ['status' => $overall, 'message' => $message, 'results' => $results];
    }

    private function updateMysqlActive(string $stokKodu, int $active): array
    {
        if (!$this->localMysqlDB) {
            return ['success' => false, 'error' => 'MySQL bağlantısı yok.'];
        }
        $this->logger->log("updateMysqlActive: {$stokKodu} -> {$active}");
        $stmt = $this->localMysqlDB->prepare("UPDATE urunler SET logo_active = ? WHERE stokkodu = ?");
        if (!$stmt) {
            $msg = 'MySQL active UPDATE statement oluşturulamadı.';
            $this->logger->log($msg, 'ERROR');
            return ['success' => false, 'error' => $msg];
        }
        $stmt->bind_param('is', $active, $stokKodu);
        if (!$stmt->execute()) {
            $msg = 'MySQL active güncelleme hatası: ' . $stmt->error;
            $this->logger->log($msg, 'ERROR');
            $stmt->close();
            return ['success' => false, 'error' => $msg];
        }
        $stmt->close();
        return ['success' => true, 'error' => ''];
    }

    private function updateGempaLogoActive(int $logicalRef, int $active): array
    {
        if (!$this->gempaLogoDB) {
            return ['success' => false, 'error' => 'Gempa Logo bağlantısı yok.'];
        }
        $this->logger->log("updateGempaLogoActive: {$logicalRef} -> {$active}");
        $stmt = $this->gempaLogoDB->prepare("UPDATE LG_566_ITEMS SET ACTIVE = ? WHERE LOGICALREF = ?");
        if (!$stmt) {
            $msg = 'Gempa Logo ACTIVE statement oluşturulamadı.';
            $this->logger->log($msg, 'ERROR');
            return ['success' => false, 'error' => $msg];
        }
        $stmt->execute([$active, $logicalRef]);
        $ok = $stmt->rowCount() >= 0;
        if ($ok) {
            $this->logger->log("updateGempaLogoActive success for {$logicalRef}");
        }
        return ['success' => $ok, 'error' => $ok ? '' : ''];
    }

    private function updateGemasLogoActive(int $logicalRef, int $active): array
    {
        if (!$this->gemasLogoDB) {
            return ['success' => false, 'error' => 'Gemas Logo bağlantısı yok.'];
        }
        $this->logger->log("updateGemasLogoActive: {$logicalRef} -> {$active}");
        $stmt = $this->gemasLogoDB->prepare("UPDATE LG_526_ITEMS SET ACTIVE = ? WHERE LOGICALREF = ?");
        if (!$stmt) {
            $msg = 'Gemas Logo ACTIVE statement oluşturulamadı.';
            $this->logger->log($msg, 'ERROR');
            return ['success' => false, 'error' => $msg];
        }
        $stmt->execute([$active, $logicalRef]);
        $ok = $stmt->rowCount() >= 0;
        if ($ok) {
            $this->logger->log("updateGemasLogoActive success for {$logicalRef}");
        }
        return ['success' => $ok, 'error' => $ok ? '' : ''];
    }

    private function insertPriceLogDomestic(string $stokKodu, float $oncekiFiyat, float $yeniFiyat): void
    {
        try {
            $stokAdi = $this->getProductName($stokKodu) ?? '';
            $stmt = $this->localMysqlDB->prepare(
                "INSERT INTO urun_fiyat_log (stokkodu, stokadi, guncelleyen, onceki_fiyat, yeni_fiyat, fiyat_tipi) VALUES (?, ?, ?, ?, ?, 'domestic')"
            );
            if (!$stmt) {
                $this->logger->log("Domestic fiyat log statement oluşturulamadı.", "ERROR");
                return;
            }
            $stmt->bind_param("sssdd", $stokKodu, $stokAdi, $this->yoneticiID, $oncekiFiyat, $yeniFiyat);
            if (!$stmt->execute()) {
                $this->logger->log("Domestic fiyat log kaydı hatası: " . $stmt->error, "ERROR");
            }
            $stmt->close();
        } catch (\Exception $e) {
            $this->logger->log("Domestic fiyat log exception: " . $e->getMessage(), "ERROR");
        }
    }

    private function insertPriceLogExport(string $stokKodu, float $oncekiFiyat, float $yeniFiyat): void
    {
        try {
            $stokAdi = $this->getProductName($stokKodu) ?? '';
            $stmt = $this->localMysqlDB->prepare(
                "INSERT INTO urun_fiyat_log (stokkodu, stokadi, guncelleyen, onceki_fiyat, yeni_fiyat, fiyat_tipi) VALUES (?, ?, ?, ?, ?, 'export')"
            );
            if (!$stmt) {
                $this->logger->log("Export fiyat log statement oluşturulamadı.", "ERROR");
                return;
            }
            $stmt->bind_param("sssdd", $stokKodu, $stokAdi, $this->yoneticiID, $oncekiFiyat, $yeniFiyat);
            if (!$stmt->execute()) {
                $this->logger->log("Export fiyat log kaydı hatası: " . $stmt->error, "ERROR");
            }
            $stmt->close();
        } catch (\Exception $e) {
            $this->logger->log("Export fiyat log exception: " . $e->getMessage(), "ERROR");
        }
    }


    private function insertManagementLog(string $overallStatus): void
    {
        try {
            $query = "INSERT INTO log_yonetim (islem, personel, tarih, durum) VALUES ('Fiyat Güncelleme', '{$this->yoneticiID}', NOW(), '$overallStatus')";
            $this->localMysqlDB->query($query);
        } catch (\Exception $e) {
            $this->logger->log("Management log exception: " . $e->getMessage(), "ERROR");
        }
    }

    private function errorResponse(string $message): array
    {
        $this->logger->log($message, "ERROR");
        return ['status' => 'error', 'message' => $message];
    }
}

