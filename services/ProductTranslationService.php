<?php
// services/ProductTranslationService.php

/**
 * Ürün çeviri ve detay işlemleri tek bir sınıf altında toplanmıştır.
 * Bu sınıf, stok koduna göre malzeme ve ilişkili ürün verilerini,
 * tüm sütunları içerecek şekilde döndürür ve güncelleme işlemlerini gerçekleştirir.
 *
 * Not: Aşağıdaki sürüm PDO yerine MySQLi kullanılarak hazırlanmıştır.
 */
class ProductTranslationService
{
    private $gemasDb;      // MySQLi bağlantı nesnesi (Gemas Web)
    private $logger;       // LoggerService örneği
    private $gemasLogoDb;  // PDO bağlantısı (Gemas Logo)
    private $gempaLogoDb;  // PDO bağlantısı (Gempa Logo)
    private $localDb;      // MySQLi bağlantı nesnesi (lokal b2b veritabanı)
    private int $gempaFirmNr;
    private int $gemasFirmNr;

    /**
     * Constructor
     *
     * @param mysqli $gemasDb  MySQLi bağlantı nesnesi (Gemas Web).
     * @param LoggerService $logger Loglama işlemleri için servis.
     * @param PDO|null $gemasLogoDb Gemas Logo veritabanı bağlantısı.
     * @param PDO|null $gempaLogoDb Gempa Logo veritabanı bağlantısı.
     * @param mysqli|null $localDb Lokal b2b veritabanı bağlantısı.
     */
    public function __construct(
        $gemasDb,
        LoggerService $logger,
        $gemasLogoDb = null,
        $gempaLogoDb = null,
        $localDb = null
    ) {
        $this->gemasDb     = $gemasDb;
        $this->logger      = $logger;
        $this->gemasLogoDb = $gemasLogoDb;
        $this->gempaLogoDb = $gempaLogoDb;
        $this->localDb     = $localDb;
        $this->gempaFirmNr = (int)($_ENV['GEMPA_FIRM_NR'] ?? 565);
        $this->gemasFirmNr = (int)($_ENV['GEMAS_FIRM_NR'] ?? 525);
    }

    /**
     * Belirtilen stok koduna ait malzeme bilgilerini, istenen dillerde getirir.
     * Eğer $locales null veya boşsa, dil filtresi uygulanmadan tüm kayıtlar alınır.
     *
     * @param string $stokKodu Kullanıcının gönderdiği stok kodu.
     * @param array|null $locales İstenilen diller, örn: ['tr', 'en', 'ru', 'fr'].
     * @return array|null Malzeme çeviri kayıtları, bulunamazsa null.
     */
    public function getMaterialTranslationsByStockCode($stokKodu, $locales = null)
    {
        $this->logger->log("getMaterialTranslationsByStockCode başlatıldı. Aranan Stok Kodu: " . $stokKodu);
        
        $resultData = null;

        if (!$this->gemasDb) {
            $this->logger->log("getMaterialTranslationsByStockCode: Gemas DB bağlantısı yok (NULL).");
            return null;
        }

        $sqlId = "SELECT id FROM malzeme WHERE stok_kodu = ? LIMIT 1";
        $this->logger->log("Malzeme ID sorgusu: " . $sqlId);
        $stmtId = $this->gemasDb->prepare($sqlId);
        if (!$stmtId) {
            $this->logger->log("Prepare hatası (malzeme id): " . $this->gemasDb->error);
            return null;
        }
        $stmtId->bind_param("s", $stokKodu);
        if (!$stmtId->execute()) {
            $this->logger->log("Execute hatası (malzeme id): " . $stmtId->error);
            $stmtId->close();
            return null;
        }
        $resultId = $stmtId->get_result();
        if (!$resultId) {
            $this->logger->log("get_result hatası (malzeme id): " . $stmtId->error);
            $stmtId->close();
            return null;
        }
        $row = $resultId->fetch_assoc();
        $stmtId->close();
        if (!$row) {
            $this->logger->log("Stok kodu malzeme tablosunda bulunamadı: " . $stokKodu);
            return null;
        }

        $malzemeId = $row['id'];
        $this->logger->log("Bulunan malzeme_id: " . $malzemeId);

        if ($locales === null || empty($locales)) {
            $sql = "SELECT * FROM malzeme_translations WHERE malzeme_id = ?";
            $this->logger->log("Sorgu (malzeme_id): " . $sql);
            $stmt = $this->gemasDb->prepare($sql);
            if (!$stmt) {
                $this->logger->log("Prepare hatası: " . $this->gemasDb->error);
                return null;
            }
            $stmt->bind_param("i", $malzemeId);
            $params = [$malzemeId];
        } else {
            $placeholders = implode(',', array_fill(0, count($locales), '?'));
            $sql = "SELECT * FROM malzeme_translations WHERE malzeme_id = ? AND locale IN ($placeholders)";
            $params = array_merge([$malzemeId], $locales);
            $this->logger->log("Sorgu (malzeme_id): " . $sql);
            $this->logger->log("Params (malzeme_id): " . print_r($params, true));
            $stmt = $this->gemasDb->prepare($sql);
            if (!$stmt) {
                $this->logger->log("Prepare hatası: " . $this->gemasDb->error);
                return null;
            }
            $types = 'i' . str_repeat('s', count($locales));
            $stmt->bind_param($types, $malzemeId, ...$locales);
        }

        $this->logger->log("SQL Query: " . $sql);
        $this->logger->log("Params: " . print_r($params, true));

        if (!$stmt->execute()) {
            $this->logger->log("Execute hatası: " . $stmt->error);
            $stmt->close();
            return null;
        }
        $this->logger->log("SQL execute tamamlandı.");

        $result = $stmt->get_result();
        if (!$result) {
            $this->logger->log("get_result hatası: " . $stmt->error);
            $stmt->close();
            return null;
        }
        $materials = $result->fetch_all(MYSQLI_ASSOC);
        $this->logger->log("fetch_all çağrıldı.");

        $stmt->close();

        if (!empty($materials)) {
            $this->logger->log("Malzeme çeviri bilgileri başarıyla alındı. Kayıt sayısı: " . count($materials));
            $this->logger->log("Alınan veriler: " . print_r($materials, true));
            $resultData = $materials;
        } else {
            $this->logger->log("malzeme_id için veri bulunamadı: " . $malzemeId);
        }

        return $resultData;
    }

    /**
     * Belirtilen malzeme_id değeriyle ilişkili olan ürün ID'lerini malzeme_urun tablosundan getirir.
     *
     * @param int $malzemeId Malzeme anahtar değeri.
     * @return array Ürün ID'lerinin dizisi (boş dizi dönebilir).
     */
    public function getAssociatedProductIds($malzemeId)
    {
        $sql = "SELECT urun_id FROM malzeme_urun WHERE malzeme_id = ?";
        $stmt = $this->gemasDb->prepare($sql);
        if (!$stmt) {
            $this->logger->log("Prepare hatası: " . $this->gemasDb->error);
            return [];
        }
        $stmt->bind_param("i", $malzemeId);
        if (!$stmt->execute()) {
            $this->logger->log("Execute hatası: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        if (!$result) {
            $this->logger->log("get_result hatası: " . $stmt->error);
            return [];
        }
        $urunIdsArr = $result->fetch_all(MYSQLI_ASSOC);
        $ids = [];
        foreach ($urunIdsArr as $row) {
            $ids[] = $row['urun_id'];
        }
        $this->logger->log("İlişkili ürün ID'leri alındı. Sayı: " . count($ids));
        $this->logger->log("Ürün ID'leri: " . print_r($ids, true));
        return $ids;
    }

    /**
     * Logo tarafındaki logicalref değerlerini getirir.
     */
    private function getLogoReferences(string $stokKodu): ?array
    {
        if (!$this->localDb) {
            $this->logger->log("getLogoReferences: Lokal veritabanı bağlantısı yok.");
            return null;
        }

        $sql  = "SELECT GEMPA2026LOGICAL, GEMAS2026LOGICAL FROM urunler WHERE stokkodu = ? LIMIT 1";
        $stmt = $this->localDb->prepare($sql);
        if (!$stmt) {
            $this->logger->log("getLogoReferences prepare hatası: " . $this->localDb->error);
            return null;
        }
        $stmt->bind_param("s", $stokKodu);
        if (!$stmt->execute()) {
            $this->logger->log("getLogoReferences execute hatası: " . $stmt->error);
            $stmt->close();
            return null;
        }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            $this->logger->log("getLogoReferences: urunler tablosunda stok kodu bulunamadı: " . $stokKodu);
            return null;
        }

        return [
            'gempa_ref' => (int)($row['GEMPA2026LOGICAL'] ?? 0),
            'gemas_ref' => (int)($row['GEMAS2026LOGICAL'] ?? 0),
        ];
    }

    /**
     * Logo veritabanlarından, stok koduna karşılık gelen ürün adlarını getirir.
     *
     * @param string $stokKodu
     * @param array|null $refs Opsiyonel olarak daha önce bulunan logicalref değerleri
     * @return array|null ['gempa_name' => ?string, 'gemas_name' => ?string,
     *                    'gempa_name3' => ?string, 'gemas_name3' => ?string,
     *                    'gempa_name4' => ?string, 'gemas_name4' => ?string,
     *                    'gempa_ref' => ?int, 'gemas_ref' => ?int]
    */
    private function getLogoNames(string $stokKodu, ?array $refs = null): ?array
    {
        $refs = $refs ?? $this->getLogoReferences($stokKodu);
        if (!$refs) {
            return null;
        }

        $gempaRef   = (int)($refs['gempa_ref'] ?? 0);
        $gemasRef   = (int)($refs['gemas_ref'] ?? 0);
        $gempaName  = null;
        $gemasName  = null;
        $gempaName3 = null;
        $gemasName3 = null;
        $gempaName4 = null;
        $gemasName4 = null;

        if ($gempaRef > 0 && $this->gempaLogoDb) {
            $stmtL = $this->gempaLogoDb->prepare("SELECT NAME, NAME3, NAME4 FROM LG_566_ITEMS WHERE LOGICALREF = ?");
            $stmtL->execute([$gempaRef]);
            $rowL = $stmtL->fetch(PDO::FETCH_ASSOC);
            if ($rowL) {
                $gempaName  = $rowL['NAME'] ?? null;
                $gempaName3 = $rowL['NAME3'] ?? null;
                $gempaName4 = $rowL['NAME4'] ?? null;
            }
        }

        if ($gemasRef > 0 && $this->gemasLogoDb) {
            $stmtL = $this->gemasLogoDb->prepare("SELECT NAME, NAME3, NAME4 FROM LG_526_ITEMS WHERE LOGICALREF = ?");
            $stmtL->execute([$gemasRef]);
            $rowL = $stmtL->fetch(PDO::FETCH_ASSOC);
            if ($rowL) {
                $gemasName  = $rowL['NAME'] ?? null;
                $gemasName3 = $rowL['NAME3'] ?? null;
                $gemasName4 = $rowL['NAME4'] ?? null;
            }
        }

        return [
            'gempa_name'  => $gempaName,
            'gemas_name'  => $gemasName,
            'gempa_name3' => $gempaName3,
            'gemas_name3' => $gemasName3,
            'gempa_name4' => $gempaName4,
            'gemas_name4' => $gemasName4,
            'gempa_ref'   => $gempaRef,
            'gemas_ref'   => $gemasRef,
        ];
    }

    /**
     * Logo veritabanlarında stok koduna karşılık gelen ürün adlarını günceller.
     * NAME, NAME3 ve NAME4 alanları isteğe bağlı olarak güncellenir.
     */
    private function updateLogoNames(
        string $stokKodu,
        ?string $gempaName,
        ?string $gemasName,
        ?string $gempaName3 = null,
        ?string $gemasName3 = null,
        ?string $gempaName4 = null,
        ?string $gemasName4 = null
    ): bool
    {
        $sql  = "SELECT GEMPA2026LOGICAL, GEMAS2026LOGICAL FROM urunler WHERE stokkodu = ? LIMIT 1";
        $stmt = $this->localDb ? $this->localDb->prepare($sql) : null;
        if (!$stmt) {
            $err = $this->localDb ? $this->localDb->error : 'no local db';
            $this->logger->log("Prepare hatası (updateLogoNames): " . $err);
            return false;
        }
        $stmt->bind_param("s", $stokKodu);
        if (!$stmt->execute()) {
            $this->logger->log("Execute hatası (updateLogoNames): " . $stmt->error);
            $stmt->close();
            return false;
        }
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            $this->logger->log("updateLogoNames: urunler tablosunda stok kodu bulunamadı: " . $stokKodu);
            return false;
        }

        $gempaRef = (int)($row['GEMPA2026LOGICAL'] ?? 0);
        $gemasRef = (int)($row['GEMAS2026LOGICAL'] ?? 0);
        $success  = true;

        if ($gempaRef > 0 && $this->gempaLogoDb) {
            if ($gempaName !== null) {
                $stmtL = $this->gempaLogoDb->prepare("UPDATE LG_566_ITEMS SET NAME = ? WHERE LOGICALREF = ?");
                $success = $stmtL->execute([$gempaName, $gempaRef]) && $success;
            }
            if ($gempaName3 !== null) {
                $stmtL = $this->gempaLogoDb->prepare("UPDATE LG_566_ITEMS SET NAME3 = ? WHERE LOGICALREF = ?");
                $success = $stmtL->execute([$gempaName3, $gempaRef]) && $success;
            }
            if ($gempaName4 !== null) {
                $stmtL = $this->gempaLogoDb->prepare("UPDATE LG_566_ITEMS SET NAME4 = ? WHERE LOGICALREF = ?");
                $success = $stmtL->execute([$gempaName4, $gempaRef]) && $success;
            }
        }
        if ($gemasRef > 0 && $this->gemasLogoDb) {
            if ($gemasName !== null) {
                $stmtL = $this->gemasLogoDb->prepare("UPDATE LG_526_ITEMS SET NAME = ? WHERE LOGICALREF = ?");
                $success = $stmtL->execute([$gemasName, $gemasRef]) && $success;
            }
            if ($gemasName3 !== null) {
                $stmtL = $this->gemasLogoDb->prepare("UPDATE LG_526_ITEMS SET NAME3 = ? WHERE LOGICALREF = ?");
                $success = $stmtL->execute([$gemasName3, $gemasRef]) && $success;
            }
            if ($gemasName4 !== null) {
                $stmtL = $this->gemasLogoDb->prepare("UPDATE LG_526_ITEMS SET NAME4 = ? WHERE LOGICALREF = ?");
                $success = $stmtL->execute([$gemasName4, $gemasRef]) && $success;
            }
        }

        return $success;
    }

    /**
     * Belirtilen urun_id için ürün çeviri bilgilerini tüm sütunları ile getirir.
     *
     * @param int $urunId Ürün ID'si.
     * @return array Urun_translations tablosuna ait tüm kayıtları içerir.
     */
    public function getProductDetails($urunId)
    {
        $sql = "SELECT * FROM urun_translations WHERE urun_id = ?";
        $stmt = $this->gemasDb->prepare($sql);
        if (!$stmt) {
            $this->logger->log("Prepare hatası: " . $this->gemasDb->error);
            return [];
        }
        $stmt->bind_param("i", $urunId);
        if (!$stmt->execute()) {
            $this->logger->log("Execute hatası: " . $stmt->error);
            return [];
        }
        $result = $stmt->get_result();
        if (!$result) {
            $this->logger->log("get_result hatası: " . $stmt->error);
            return [];
        }
        $details = $result->fetch_all(MYSQLI_ASSOC);
        $this->logger->log("Ürün detay bilgileri alındı. Kayıt sayısı: " . count($details));
        
        // Eğer trim() kullanmak istemiyorsanız, bu döngüyü tamamen kaldırabilirsiniz:
        /*
        foreach ($details as &$detail) {
            if (isset($detail['ad'])) {
                $detail['ad'] = trim($detail['ad'], "\" \t\n\r\0\x0B");
            }
        }
        unset($detail); // referansları temizleyelim
        */
        
        $this->logger->log("Ürün detay verileri (güncellenmiş): " . print_r($details, true));
        return $details;
    }
    
    

    /**
     * Kullanıcının gönderdiği stok koduna göre malzeme bilgileri (tüm sütunlar)
     * ve ilişkili ürünlerin tüm dildeki çeviri verilerini getirir.
     *
     * @param string $stokKodu Kullanıcının gönderdiği stok kodu.
     * @param array $locales İstenen diller (örn: ['tr', 'en', 'ru', 'fr']).
     * @return array|null 'material_translations', 'associated_products' ve Logo isimlerini içeren dizi.
     */
    public function getMaterialAndProductsByStockCode($stokKodu, $locales = ['tr', 'en', 'ru', 'fr'])
    {
        $this->logger->log("getMaterialAndProductsByStockCode başlatıldı. StokKodu: " . $stokKodu);
        $materials = $this->getMaterialTranslationsByStockCode($stokKodu, $locales);
        
        // Return null if NO data found at all?
        // User wants to see Logo details even if web data (materials) is missing.
        // So we proceed even if $materials is empty.
        
        if (empty($materials)) {
            $this->logger->log("getMaterialAndProductsByStockCode: Malzeme bilgisi alınamadı (Web tarafı). Logo için devam ediliyor.");
            $materials = []; // Ensure it's an array
            $malzemeId = 0;
            $products  = [];
        } else {
            $malzemeId = $materials[0]['malzeme_id'];
            $this->logger->log("Malzeme ID belirlendi: " . $malzemeId);
            
            $urunIds = $this->getAssociatedProductIds($malzemeId);
            $products = [];
            foreach ($urunIds as $urunId) {
                $productDetails = $this->getProductDetails($urunId);
                if (!empty($productDetails)) {
                    $products[$urunId] = $productDetails;
                } else {
                    $this->logger->log("Ürün detay bilgisi boş geldi. Ürün ID: " . $urunId);
                }
            }
        }
        
        $logoRefs  = $this->getLogoReferences($stokKodu);
        $logoNames = $this->getLogoNames($stokKodu, $logoRefs);
        
        // If both materials and Logo references are missing, then truly not found.
        if (empty($materials) && empty($logoRefs)) {
             $this->logger->log("Hem Web malzemesi hem Logo referansı bulunamadı. Null dönülüyor.");
             return null;
        }

        $result_array = [
            'material_translations' => $materials,
            'associated_products'   => $products,
            'gempa_name'            => $logoNames['gempa_name'] ?? null,
            'gemas_name'            => $logoNames['gemas_name'] ?? null,
            'gempa_name3'           => $logoNames['gempa_name3'] ?? null,
            'gemas_name3'           => $logoNames['gemas_name3'] ?? null,
            'gempa_name4'           => $logoNames['gempa_name4'] ?? null,
            'gemas_name4'           => $logoNames['gemas_name4'] ?? null,
            'gempa_ref'             => $logoNames['gempa_ref'] ?? ($logoRefs['gempa_ref'] ?? null),
            'gemas_ref'             => $logoNames['gemas_ref'] ?? ($logoRefs['gemas_ref'] ?? null),
        ];
        
        $this->logger->log("getMaterialAndProductsByStockCode işlemi tamamlandı.");
        $this->logger->log("Dönen sonuç: " . print_r($result_array, true));
        return $result_array;
    }

    /**
     * Logo (Gempa & Gemas) veritabanlarından ilgili stok kodu için son satış faturalarını çeker.
     */
    public function getInvoiceHistory(string $stokKodu, int $limit = 5): array
    {
        $stokKodu = trim($stokKodu);
        if ($stokKodu === '') {
            return [
                'success' => false,
                'message' => 'Stok kodu eksik.',
                'sources' => [],
            ];
        }

        $limit = max(1, min($limit, 10));
        $refs = $this->getLogoReferences($stokKodu);
        if (!$refs) {
            return [
                'success' => false,
                'message' => 'Logo referansı bulunamadı.',
                'sources' => [],
            ];
        }

        $sources = [];
        if ($refs['gempa_ref'] > 0 && $this->gempaLogoDb) {
            $sources['gempa'] = [
                'label'      => 'Gempa Logo',
                'firm_nr'    => $this->gempaFirmNr,
                'logicalref' => $refs['gempa_ref'],
                'invoices'   => $this->fetchInvoiceHistory($this->gempaLogoDb, $this->gempaFirmNr, $refs['gempa_ref'], $limit),
            ];
        }
        if ($refs['gemas_ref'] > 0 && $this->gemasLogoDb) {
            $sources['gemas'] = [
                'label'      => 'Gemas Logo',
                'firm_nr'    => $this->gemasFirmNr,
                'logicalref' => $refs['gemas_ref'],
                'invoices'   => $this->fetchInvoiceHistory($this->gemasLogoDb, $this->gemasFirmNr, $refs['gemas_ref'], $limit),
            ];
        }

        if (empty($sources)) {
            return [
                'success' => false,
                'message' => 'Logo bağlantısı bulunamadı.',
                'sources' => [],
            ];
        }

        $hasData = false;
        foreach ($sources as $source) {
            if (!empty($source['invoices'])) {
                $hasData = true;
                break;
            }
        }

        return [
            'success'    => true,
            'has_data'   => $hasData,
            'stock_code' => $stokKodu,
            'limit'      => $limit,
            'sources'    => $sources,
        ];
    }

    /**
     * İlgili Logo veritabanından fatura geçmişini çeker.
     */
    private function fetchInvoiceHistory(\PDO $pdo, int $firmNr, int $itemLogicalRef, int $limit = 5): array
    {
        if (!$pdo) {
            return [];
        }

        $limit = max(1, min($limit, 10));
        $sql = "
            SELECT TOP {$limit}
                inv.LOGICALREF AS invoice_ref,
                inv.FICHENO     AS invoice_no,
                inv.DATE_       AS invoice_date,
                inv.TRCODE      AS invoice_trcode,
                inv.TRCURR      AS invoice_currency,
                inv.TRRATE      AS invoice_rate,
                inv.REPORTRATE  AS report_rate,
                cli.CODE        AS client_code,
                cli.DEFINITION_ AS client_name,
                st.AMOUNT       AS quantity,
                st.PRICE        AS unit_price,
                st.PRPRICE      AS pr_price,
                st.TOTAL        AS line_total,
                st.TRCURR       AS currency_code,
                st.DISCPER      AS discount_percent,
                st.VAT          AS vat_rate,
                unit.CODE       AS unit_code
            FROM LG_{$firmNr}_01_STLINE st
            INNER JOIN LG_{$firmNr}_01_INVOICE inv ON st.INVOICEREF = inv.LOGICALREF
            LEFT JOIN LG_{$firmNr}_CLCARD cli     ON inv.CLIENTREF = cli.LOGICALREF
            LEFT JOIN LG_{$firmNr}_UNITSETL unit  ON st.UOMREF = unit.LOGICALREF
            WHERE st.STOCKREF = :itemRef
              AND st.LINETYPE = 0
              AND inv.CANCELLED = 0
              AND inv.TRCODE IN (8, 9)
            ORDER BY inv.DATE_ DESC, inv.LOGICALREF DESC
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['itemRef' => $itemLogicalRef]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->log("Logo fatura sorgusu hatası ({$firmNr}): " . $e->getMessage());
            return [];
        }

        $history = [];
        foreach ($rows as $row) {
            // Faturanın para birimini kontrol et
            $invoiceCurrency = (int)($row['invoice_currency'] ?? 0);
            $lineCurrency = (int)($row['currency_code'] ?? 0);
            
            // Logo'da TRCURR değerleri: 0=TL, 1=USD, 20=EUR (veya 2=EUR bazı versiyonlarda), 160=TL (yeni)
            $unitPrice = (float)($row['unit_price'] ?? 0); // Bu zaten birim fiyat (miktar ile çarpılmamış)
            $prPrice = (float)($row['pr_price'] ?? 0); // Raporlama para birimindeki birim fiyat
            $reportRate = (float)($row['report_rate'] ?? 0); // Euro kuru
            
            // Öncelik sırası:
            // 1. Önce PRPRICE'a bakılır (raporlama para birimi genellikle Euro)
            // 2. PRPRICE > 0 ise, bu Euro fiyatıdır, direkt kullan
            // 3. PRPRICE = 0 veya yok ise:
            //    - Fatura Euro ise -> unit_price Euro'dur
            //    - Fatura TL ise -> unit_price / report_rate ile Euro'ya çevir
            
            $displayPrice = 0;
            $displayCurrency = ['code' => 'EUR', 'label' => '€'];
            
            // Euro kontrolü: Logo'da EUR için 2 veya 20 kullanılabilir
            $isInvoiceEuro = ($invoiceCurrency === 2 || $invoiceCurrency === 20);
            $isLineEuro = ($lineCurrency === 2 || $lineCurrency === 20);
            
            if ($prPrice > 0) {
                // PRPRICE varsa, bu zaten Euro (raporlama para birimi)
                $displayPrice = $prPrice;
            } elseif ($isInvoiceEuro || $isLineEuro) {
                // Fatura veya satır Euro ise, unit_price zaten Euro
                $displayPrice = $unitPrice;
            } else {
                // Fatura TL ise, Euro kuruna böl
                if ($reportRate > 0) {
                    $displayPrice = $unitPrice / $reportRate;
                } else {
                    // Kur yoksa, TL olarak göster
                    $displayPrice = $unitPrice;
                    $displayCurrency = ['code' => 'TRY', 'label' => 'TL'];
                }
            }
            
            $history[] = [
                'invoice_ref'      => (int)($row['invoice_ref'] ?? 0),
                'invoice_no'       => trim((string)($row['invoice_no'] ?? '')),
                'invoice_date'     => $this->formatLogoDate($row['invoice_date'] ?? null),
                'invoice_trcode'   => (int)($row['invoice_trcode'] ?? 0),
                'customer_code'    => trim((string)($row['client_code'] ?? '')),
                'customer_name'    => trim((string)($row['client_name'] ?? '')),
                'quantity'         => (float)($row['quantity'] ?? 0),
                'unit_code'        => $row['unit_code'] ?? null,
                'unit_price'       => $displayPrice,
                'line_total'       => (float)($row['line_total'] ?? 0),
                'currency_code'    => $displayCurrency['code'],
                'currency_label'   => $displayCurrency['label'],
                'discount_percent' => isset($row['discount_percent']) ? (float)$row['discount_percent'] : null,
                'vat_rate'         => isset($row['vat_rate']) ? (float)$row['vat_rate'] : null,
            ];
        }

        return $history;
    }

    /**
     * Logo para birimi kodunu okunabilir hale getirir.
     */
    private function mapCurrency(int $currencyCode): array
    {
        $map = [
            0 => ['code' => 'TRY', 'label' => 'TL'],
            1 => ['code' => 'USD', 'label' => 'USD'],
            2 => ['code' => 'EUR', 'label' => 'EUR'],
            3 => ['code' => 'GBP', 'label' => 'GBP'],
            4 => ['code' => 'CHF', 'label' => 'CHF'],
        ];

        return $map[$currencyCode] ?? ['code' => 'TRY', 'label' => 'TL'];
    }

    /**
     * Logo tarih değerini okunabilir formata çevirir.
     */
    private function formatLogoDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            $dt = new DateTime(is_string($value) ? $value : (string)$value);
            return $dt->format('d.m.Y');
        } catch (Exception $e) {
            $this->logger->log("Logo tarih format hatası: " . $e->getMessage());
            return is_string($value) ? $value : null;
        }
    }

    /**
     * POST edilen verilerle malzeme ve ürün çeviri kayıtlarını günceller.
     * Beklenen yapı:
     * $_POST['material'][<locale>]['aciklama'], ['malzeme_id'], ['locale']
     * $_POST['products'][<urun_id>][<locale>]['ad'], ['aciklama'], ['urun_id'], ['locale']
     *
     * @param array $postData POST verileri.
     * @return array Güncelleme sonucunu belirten dizi.
     */
    public function updateMaterialAndProducts($postData)
    {
        $this->logger->log("updateMaterialAndProducts işlemi başlatıldı. Gelen POST verileri: " . print_r($postData, true));
        
        // Transaction başlatıyoruz
        $this->gemasDb->begin_transaction();
        $this->logger->log("Veritabanı transaction başladı.");

        try {
            if (isset($postData['material']) && is_array($postData['material'])) {
                foreach ($postData['material'] as $locale => $data) {
                    if (!isset($data['aciklama'], $data['malzeme_id'], $data['locale'])) {
                        $this->logger->log("updateMaterial: Eksik veri tespit edildi. Locale: " . $locale . " - Data: " . print_r($data, true));
                        continue;
                    }
                    $sql = "UPDATE malzeme_translations SET aciklama = ? WHERE malzeme_id = ? AND locale = ?";
                    $stmt = $this->gemasDb->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("Prepare hatası (material): " . $this->gemasDb->error);
                    }
                    // aciklama string, malzeme_id integer, locale string
                    $stmt->bind_param("sis", $data['aciklama'], $data['malzeme_id'], $data['locale']);
                    if (!$stmt->execute()) {
                        throw new Exception("Execute hatası (material): " . $stmt->error);
                    }
                    $this->logger->log("Malzeme çeviri güncellendi. Malzeme ID: " . $data['malzeme_id'] . ", Locale: " . $data['locale']);
                }
            } else {
                $this->logger->log("Güncellenecek malzeme çeviri verisi bulunamadı.");
            }

            // 2. Ürün çeviri bilgilerini güncelle
            if (isset($postData['products']) && is_array($postData['products'])) {
                foreach ($postData['products'] as $urun_id => $localeData) {
                    if (!is_array($localeData)) {
                        $this->logger->log("updateProducts: Ürün ID {$urun_id} için geçersiz veri formatı.");
                        continue;
                    }
                    foreach ($localeData as $locale => $data) {
                        if (!isset($data['ad'], $data['aciklama'], $data['urun_id'], $data['locale'])) {
                            $this->logger->log("updateProducts: Eksik veri tespit edildi. Ürün ID: {$urun_id}, Locale: " . $locale . " - Data: " . print_r($data, true));
                            continue;
                        }
                        $sql = "UPDATE urun_translations SET ad = ?, aciklama = ? WHERE urun_id = ? AND locale = ?";
                        $stmt = $this->gemasDb->prepare($sql);
                        if (!$stmt) {
                            throw new Exception("Prepare hatası (products): " . $this->gemasDb->error);
                        }
                        $stmt->bind_param("ssis", $data['ad'], $data['aciklama'], $data['urun_id'], $data['locale']);
                        if (!$stmt->execute()) {
                            throw new Exception("Execute hatası (products): " . $stmt->error);
                        }
                        $this->logger->log("Ürün çeviri güncellendi. Ürün ID: " . $data['urun_id'] . ", Locale: " . $data['locale']);
                    }
                }
            } else {
                $this->logger->log("Güncellenecek ürün çeviri verisi bulunamadı.");
            }

            // 3. Logo NAME güncellemesi
            if (!empty($postData['stok_kodu'])) {
                $gempaName  = $postData['gempa_name'] ?? null;
                $gemasName  = $postData['gemas_name'] ?? null;
                $gempaName3 = $postData['gempa_name3'] ?? null;
                $gemasName3 = $postData['gemas_name3'] ?? null;
                $gempaName4 = $postData['gempa_name4'] ?? null;
                $gemasName4 = $postData['gemas_name4'] ?? null;
                if ($gempaName !== null || $gemasName !== null || $gempaName3 !== null || $gemasName3 !== null || $gempaName4 !== null || $gemasName4 !== null) {
                    $ok = $this->updateLogoNames($postData['stok_kodu'], $gempaName, $gemasName, $gempaName3, $gemasName3, $gempaName4, $gemasName4);
                    if (!$ok) {
                        throw new Exception('Logo NAME güncellemesi başarısız');
                    }
                    $this->logger->log('Logo NAME güncellendi: ' . $postData['stok_kodu']);
                }
            }

            $this->gemasDb->commit();
            $this->logger->log("Transaction commit edildi. Güncelleme başarılı.");
            return ['success' => true, 'message' => 'Güncelleme başarılı'];
        } catch (Exception $e) {
            $this->gemasDb->rollback();
            $this->logger->log("Detay güncelleme hatası: " . $e->getMessage());
            return ['success' => false, 'message' => 'Güncelleme sırasında bir hata oluştu: ' . $e->getMessage()];
        }
    }
}
?>
