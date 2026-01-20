<?php
declare(strict_types=1);

namespace Proje;

use App\Models\SalesOrderMap;
use App\Models\ArpMap;

use Exception;

class LogoService
{
    private TokenManager $tokenManager;
    private RestClient $restClient;
    private array $config;

    public function __construct(
        private DatabaseManager $db,
        private array $configArray,
        private string $logErrorFile,
        private string $logDebugFile
    ) {
        $this->config = $configArray;
        $this->tokenManager = new TokenManager(config: $this->configArray);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
    }

    /**
     * Creates a new ARP (customer/vendor) card in Logo.
     *
     * @param array $payload JSON payload using Logo API field names.
     * @return array The API response.
     */
    public function createArp(array $payload): array
    {
        $this->logDebug('POST Arps payload', 0, $payload);
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        return $this->restClient->post('Arps', ['Arps' => [$payload]]);
    }

    /**
     * Updates an ARP card using its CODE.
     *
     * @param string $code    ARP CODE
     * @param array  $payload Fields to update
     */
    public function updateArp(string $code, array $payload): array
    {
        $ref = $this->getArpRefByCode($code);
        if (!$ref) {
            throw new Exception('ARP code not found: ' . $code);
        }
        return $this->updateArpByRef($ref, $payload);
    }

    /**
     * Updates an ARP card using its internal reference.
     */
    public function updateArpByRef(int $ref, array $payload): array
    {
        $this->logDebug('PUT Arps payload', $ref, $payload);
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        // PUT requests expect the raw object rather than an array wrapper
        return $this->restClient->put("Arps/{$ref}", $payload);
    }

    /**
     * Creates an ARP using local DB column array.
     */
    public function createArpFromDb(array $company): array
    {
        $payload = ArpMap::unmap($company);
        if (!isset($payload['ACCOUNT_TYPE'])) {
            $payload['ACCOUNT_TYPE'] = 3; // default to Alıcı+Satıcı
        }
        if (empty($company['s_arp_code'])) {
            unset($payload['CODE']);
        }
        if (empty($company['s_gl_code'])) {
            unset($payload['GL_CODE']);
        }
        return $this->createArp($payload);
    }

    /**
     * Updates an ARP using local DB column array; requires s_arp_code.
     */
    public function updateArpFromDb(array $company): array
    {
        $payload = ArpMap::unmap($company);
        if (!isset($payload['ACCOUNT_TYPE'])) {
            $payload['ACCOUNT_TYPE'] = 3;
        }
        if (empty($company['s_gl_code'])) {
            unset($payload['GL_CODE']);
        }
        if (empty($company['internal_reference'])) {
            if (empty($company['s_arp_code'])) {
                throw new Exception('s_arp_code or internal_reference required for update');
            }
            $ref = $this->getArpRefByCode($company['s_arp_code']);
            if (!$ref) {
                throw new Exception('ARP code not found: ' . $company['s_arp_code']);
            }
            $company['internal_reference'] = $ref;
        }
        return $this->updateArpByRef((int)$company['internal_reference'], $payload);
    }

    /**
     * Fetch a single ARP record using its CODE.
     */
    public function getArp(string $code): array
    {
        $ref = $this->getArpRefByCode($code);
        if ($ref === null) {
            return [];
        }
        return $this->getArpByRef($ref);
    }

    /**
     * Fetch a single ARP record using its internal reference.
     */
    public function getArpByRef(int $internalRef): array
    {
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        return $this->restClient->get("Arps/{$internalRef}");
    }

    /**
     * Returns internal reference for a given ARP code or null if not found.
     */
    public function getArpRefByCode(string $code): ?int
    {
        $firmNr = $this->config['firmNr'] ?? 0;
        $sql = "SELECT LOGICALREF FROM LG_{$firmNr}_CLCARD WHERE CODE='" . addslashes($code) . "'";
        $rows = $this->executeSqlQuery($sql);
        if (!$rows) {
            return null;
        }
        return (int)($rows[0]['LOGICALREF'] ?? 0);
    }

    /**
     * Fetch an ARP and convert API fields to local DB column names.
     */
    public function getArpMapped(string|int $codeOrRef): array
    {
        if (is_numeric($codeOrRef)) {
            $resp = $this->getArpByRef((int)$codeOrRef);
        } else {
            $resp = $this->getArp((string)$codeOrRef);
        }
        if (!$resp) {
            return [];
        }
        $item = $resp['Arps'][0] ?? $resp['items'][0] ?? $resp;
        return ArpMap::map($item);
    }

    public function getArpMappedByRef(int $internalRef): array
    {
        $resp = $this->getArpByRef($internalRef);
        $item = $resp['Arps'][0] ?? $resp['items'][0] ?? $resp;
        return ArpMap::map($item);
    }

    /**
     * SalesOrder başlığını günceller.
     *
     * @param int   $internalRef Logo’daki internal reference
     * @param array $headerPayload API anahtarlarıyla hazırlanmış update gövdesi,
     *                             örn. ['DIVISION'=>2,'DEPARTMENT'=>5,…]
     * @return array API cevabı
     * @throws Exception
     */
    public function updateOrderHeader(int $internalRef, array $headerPayload): array
    {
        // mapHeader: DB alan adlarından API anahtarlarına ters remap
        $apiBody = $headerPayload;
        $this->logDebug("PUT header payload", $internalRef, $apiBody);

        try {
            $response = $this->restClient->put(
                "salesOrders/{$internalRef}",
                $apiBody
            );
            $this->logDebug("PUT header response", $internalRef, $response);
            return $response;
        } catch (Exception $ex) {
            $this->logError("Header update failed: " . $ex->getMessage(), $internalRef);
            throw $ex;
        }
    }

    /**
     * SalesOrder kalem listesini günceller.
     *
     * @param int   $orderId      DB’deki teklif ID’niz
     * @param int   $internalRef  Logo’daki internal reference
     * @param array $itemsPayload API raw formatında, [{'MASTER_CODE'=>…,…},…]
     * @return array  [
     *   'putResponse' => <PUT cevabı>,
     *   'items'       => <GET ile çekilen güncel kalem listesi>
     * ]
     * @throws Exception
     */
    public function updateOrderItems(int $orderId, int $internalRef, array $itemsPayload): array
    {
        foreach ($itemsPayload as &$item) {
            unset(
                $item['CURR_PRICE'],
                $item['EDT_CURR'],
                $item['VAT_BASE'],
                $item['TOTAL_NET'],
                $item['TOTAL'],
                $item['VAT_AMOUNT']
            );

            // Eğer bir indirim satırıysa…
            if (isset($item['TYPE']) && (int)$item['TYPE'] === 2) {
                if (empty($item['PARENTLNREF'])) {
                    $item['PARENTLNREF']  = null;
                    $item['DETAIL_LEVEL'] = 1;
                }
            }
        }
        unset($item);

        $apiBody = ['TRANSACTIONS' => ['items' => $itemsPayload]];
        $this->logDebug('PUT items payload', $internalRef, $apiBody);

        try {
            $putResponse = $this->restClient->put(
                "salesOrders/{$internalRef}",
                $apiBody
            );
            $this->logDebug('PUT items response', $internalRef, $putResponse);

            // ——— Hata varsa burada dur ———
            $errorMsg = $this->compileErrorMessage($putResponse);
            if ($errorMsg !== '') {
                $this->logError("API returned error: {$errorMsg}", $orderId);
                throw new Exception("Logo API error Code: {$errorMsg}");
            }

            // Başarılıysa DB güncelle (tutar, logo ref vs.)
            // updateLocalOrder(...) metodunu çağırabilirsiniz
            
            return [
                'putResponse' => $putResponse,
                'items'       => $itemsPayload 
            ];

        } catch (Exception $e) {
            $this->logError("updateOrderItems Exception: " . $e->getMessage(), $orderId);
            throw $e;
        }
    }

    /**
     * Döviz kodunu Logo formatına çevirir (Tiger 3 standardı).
     * TL=160, USD=1, EUR=20
     */
    private function getCurrencyCode($currency)
    {
        if (empty($currency)) return 160; // TL (Varsayılan)
        
        $currency = strtoupper(trim($currency));
        
        $map = [
            'TL'  => 160,
            'TRL' => 160,
            'TRY' => 160,
            'USD' => 1,
            'EUR' => 20,
            'EURO'=> 20
        ];
        
        return $map[$currency] ?? 160;
    }

    /**
     * Unix timestamp'i Logo integer saat formatına çevirir.
     * (Hour * 16777216) + (Minute * 65536) + (Second * 256)
     */
    private function getLogoTime($timestamp)
    {
        $h = (int)date('H', $timestamp);
        $m = (int)date('i', $timestamp);
        $s = (int)date('s', $timestamp);
        // Logo Tiger integer time bit yapısı
        return ($h * 16777216) + ($m * 65536) + ($s * 256);
    }

    /**
     * Fetch the main sales order header.
     */
    public function getSalesOrder(int $internalRef): array
    {
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        return $this->restClient->get("salesOrders/{$internalRef}");
    }

    /**
     * Fetch the line‐items for a given sales order.
     */
    public function getSalesOrderTransactions(int $internalRef, int $limit = 50, int $offset = 0): array
    {
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        return $this->restClient->get(
            "salesOrders/{$internalRef}/TRANSACTIONS",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    /**
     * Fetch the payment list for a given sales order.
     */
    public function getSalesOrderPaymentList(int $internalRef, int $limit = 50, int $offset = 0): array
    {
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        return $this->restClient->get(
            "salesOrders/{$internalRef}/PAYMENT_LIST",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    /**
     * Fetch any user-defined fields for a given sales order.
     */
    public function getSalesOrderDefnFldsList(int $internalRef, int $limit = 50, int $offset = 0): array
    {
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        return $this->restClient->get(
            "salesOrders/{$internalRef}/DEFNFLDSLIST",
            ['limit' => $limit, 'offset' => $offset]
        );
    }

    /**
     * Convenience: fetch all pieces of a sales order in one go.
     */
    public function getFullSalesOrderData(int $internalRef): array
    {
        return [
            'header' => $this->getSalesOrder($internalRef),
            'transactions' => $this->getSalesOrderTransactions($internalRef),
            'payments' => $this->getSalesOrderPaymentList($internalRef),
            'defnFields' => $this->getSalesOrderDefnFldsList($internalRef),
        ];
    }

    /**
     * Siparişi Logo'ya aktarır, DB'yi günceller ve sonucu döner.
     *
     * @param int $orderId
     * @return array{'status':bool,'message':string,'response'?:array}
     */
    public function transferOrder(int $orderId): array
    {
        try {
            // 1) Mevcut teklifi ve kalemleri hazırla, payload’ı oluştur
            $order = $this->fetchOrder($orderId);
            $items = $this->prepareItems($orderId, $order);
            $arpCode = $this->determineArpCode($order);
            $payload = $this->buildPayload($order, $items, $arpCode);

            // 2) POST → Logo
            $this->logDebug("Request", $orderId, $payload);
            $response = $this->executeApiCall('salesOrders', $payload);
            $this->logDebug("Raw response", $orderId, $response);

            // 3) Hata kontrolü
            $errorMsg = $this->compileErrorMessage($response);
            if ($errorMsg !== '') {
                return $this->fail($orderId, $errorMsg);
            }

            // 4) Başarı kayıtları (internal_reference, number, vs.)
            $respBody = $response['response'] ?? $response;
            $internalRef = $this->extractInternalReference($respBody);
            $logoNumberVal = $this->extractNumberFromHeaderResponse($respBody);
            $this->recordSuccess($orderId, $internalRef, $logoNumberVal);
            $this->updateItemInternals($orderId, $respBody);

            // 5) Gerçek header’ı çek
            $fullHeader = $this->getSalesOrder($internalRef);
            $this->logDebug("Fetched full header", $orderId, $fullHeader);
            $this->updateHeaderFields($orderId, $fullHeader);

            $fullTransResponse = $this->getSalesOrderTransactions($internalRef);
            $fullItems = $fullTransResponse['items']
                ?? ($fullTransResponse['TRANSACTIONS']['items'] ?? []);
            $this->logDebug("Fetched full transactions", $orderId, $fullItems);
            $this->updateItemDetails($orderId, ['TRANSACTIONS' => ['items' => $fullItems]]);

            return [
                'status' => true,
                'message' => 'Sipariş ve veriler başarıyla güncellendi.',
                'response' => $response,
            ];
        } catch (Exception $ex) {
            return $this->fail($orderId, 'Logo API exception: ' . $ex->getMessage());
        }
    }

    /**
     * Updates local offer header fields using a full header response from Logo.
     *
     * This is public so external scripts may refresh header data after manual
     * updates.
     */
    public function updateHeaderFields(int $orderId, array $respBody): void
    {
        // API → DB alan adlarını eşle
        $mapped = SalesOrderMap::mapHeader($respBody);
        if (empty($mapped)) {
            return;
        }

        // orderId’yi de parametre olarak ekleyelim
        $mapped['id'] = $orderId;

        // DatabaseManager içinde tanımlayacağımız metodu çağıralım
        $this->db->updateOfferHeader($mapped);
        $this->logDebug("Header alanları güncellendi", $orderId, $mapped);
    }

    public function updateItemDetails(int $orderId, array $respBody): void
    {
        $items = $respBody['TRANSACTIONS']['items'] ?? [];

        // 0) Önce var olan tüm indirim satırlarını temizle
        $this->db->deleteDiscountLines($orderId);

        $productUpdates = [];

        foreach ($items as $it) {
            // Ham Logo verisini map et
            $mapped = SalesOrderMap::mapTransaction($it);
            $type   = (int)$mapped['transaction_type'];

            if ($type === 2) {
                // İndirim satırı → yeni olarak ekle
                $this->db->insertDiscountLine(
                    teklifId:            $orderId,
                    parentInternalRef:   $mapped['parent_internal_reference'] ?? null,
                    rate:                (float)$mapped['iskonto'],
                    newInternalRef:      (int)$mapped['internal_reference'],
                    ordficheref:         (int)($mapped['ordficheref'] ?? 0),
                    miktar:              (float)($mapped['miktar'] ?? 0),
                    liste:               (float)($mapped['liste'] ?? 0),
                    vat_base:            (float)($mapped['vat_base'] ?? 0),
                    total_net:           (float)($mapped['total_net'] ?? 0),
                    unit_conv1:          (int)($mapped['unit_conv1'] ?? 1),
                    unit_conv2:          (int)($mapped['unit_conv2'] ?? 1),
                    data_reference:      (int)($mapped['data_reference'] ?? 0),
                    guid:                $mapped['guid'] ?? null,
                    eu_vat_status:       (int)($mapped['eu_vat_status'] ?? 0),
                    multi_add_tax:       (int)($mapped['multi_add_tax'] ?? 0),
                    affect_risk:         (int)($mapped['affect_risk'] ?? 0),
                    org_quantity:        (float)($mapped['org_quantity'] ?? 0),
                );
            } else {
                // Ürün satırı → var olduğu gibi güncelle
                $productUpdates[] = [
                    'kod'             => $mapped['kod'] ?? '',
                    'internal'        => (int)$mapped['internal_reference'],
                    'ordficheref'     => $mapped['ordficheref'] ?? 0,
                    'liste'           => $mapped['liste'] ?? 0,
                    'vat_base'        => $mapped['vat_base'] ?? 0,
                    'total_net'       => $mapped['total_net'] ?? 0,
                    'unit_conv1'      => $mapped['unit_conv1'] ?? 1,
                    'unit_conv2'      => $mapped['unit_conv2'] ?? 1,
                    'data_reference'  => $mapped['data_reference'] ?? 0,
                    'eu_vat_status'   => $mapped['eu_vat_status'] ?? 0,
                    'multi_add_tax'   => $mapped['multi_add_tax'] ?? 0,
                    'affect_risk'     => $mapped['affect_risk'] ?? 0,
                    'org_quantity'    => $mapped['org_quantity'] ?? 0,
                    'guid'            => $mapped['guid'] ?? null,
                ];
            }
        }

        // Ürün satırlarını topluca güncelle
        if (!empty($productUpdates)) {
            $this->db->updateProductsInternalRefs($orderId, $productUpdates);
        }
    }

    private function fetchOrder(int $orderId): array
    {
        $order = $this->db->getOffer($orderId);
        if (!$order) {
            throw new Exception("Order bulunamadı: {$orderId}");
        }
        return $order;
    }

    /**
     * Sipariş kalemlerini Logo'ya uygun hale getirir.
     */
    private function prepareItems(int $orderId, array $order): array
    {
        $raw = $this->db->getOfferItems($orderId);
        $items = [];

        // Başlıktaki kurları al (virgül varsa noktaya çevir)
        $euroKur  = (float)str_replace(',', '.', $order['eurokur'] ?? '0');
        $dolarKur = (float)str_replace(',', '.', $order['dolarkur'] ?? '0');

        foreach ($raw as $i) {
            // ═══ DEBUG: Database'den gelen iskonto değerlerini logla ═══
            error_log("DEBUG prepareItems - Ürün: {$i['kod']}, iskonto: {$i['iskonto']}, iskonto_formulu: " . ($i['iskonto_formulu'] ?? 'YOK'), 3, $this->logDebugFile);
            
            $currencyMap = $this->mapCurrency($i['doviz'] ?? 'TL');
            $qty = (float) $i['miktar'];
            $currPrice = (float) $i['liste'];
            $lineTotal = $currPrice * $qty;
            $vatAmount = $lineTotal * 0.20; // VAT_RATE sabit 20

            // Kur bilgisi belirle (Satır dövizine göre veya Header'dan zorla)
            // Header dövizi öncelikli olsun ki tüm fiş aynı döviz tipinde olsun (Logo genelde bunu sever)
            $headerCurrency = strtoupper($order['currency'] ?? 'TL');
            $rowCurrency = strtoupper($i['doviz'] ?? $headerCurrency); // Satırın kendi dövizini kullan, yoksa başlığınkini
            
            $exchangeRate = 1.0;
            $logoCurrCode = 160; // Varsayılan TL

            if ($rowCurrency === 'EUR') {
                $exchangeRate = $euroKur > 0 ? $euroKur : 1.0;
                $logoCurrCode = 20;
            } elseif ($rowCurrency === 'USD') {
                $exchangeRate = $dolarKur > 0 ? $dolarKur : 1.0;
                $logoCurrCode = 1;
            } else {
                $logoCurrCode = 160; // TL
                $exchangeRate = 1.0;
            }

            
            // Eğer test verisinden overriding gelirse (opsiyonel)
            if (!empty($i['exchange_rate'])) {
                $exchangeRate = (float)$i['exchange_rate'];
            }

            // ANA ÜRÜN SATIRI
            $item = [
                'MASTER_CODE'      => $i['kod'],
                'TYPE'             => 0,  // Ana ürün satırı için TYPE=0
                'QUANTITY'         => $qty,
                'VAT_RATE'         => 20,
                'TRANS_DESCRIPTION'=> $i['trans_description'],
                'UNIT_CODE'        => $i['birim'],
                
                // YENİ: Kur Bilgisi (Hem Raporlama hem İşlem Kuru)
                'PR_RATE'          => $exchangeRate,
                'TC_XRATE'         => $exchangeRate, // İşlem Döviz Kuru
                
                'RC_XRATE'         => 1, // Raporlama Döviz Kuru (TL olduğu için 1)
                
                // EDT_CURR: Satır döviz türü (Transaction Currency)
                'EDT_CURR'         => (string)$logoCurrCode,
                'CURR_TRANSACTIN'  => (string)$logoCurrCode, // Transaction Currency Tipi
                'CURR_TYPE'        => ($logoCurrCode == 160) ? 0 : 2, // 0=Yerel, 2=İşlem
                'PRICE'            => $currPrice,
                'PC_PRICE'         => $currPrice,
                'EDT_PRICE'        => $currPrice,
                'EXCLINE_PRICE'    => $currPrice,
                'EXCLINE_TOTAL'    => $lineTotal,
                'EXCLINE_VAT_MATRAH'=>$lineTotal,
                'EXCLINE_LINE_NET' => $lineTotal,
                'TOTAL'            => $lineTotal,
                'VAT_BASE'         => $lineTotal,
                'VAT_AMOUNT'       => $vatAmount,
                'TOTAL_NET'        => $lineTotal,
            ];
            
            // ═══ DEBUG: Logo'ya gönderilecek item payload'ını logla ═══
            error_log("DEBUG Item Payload: " . json_encode($item, JSON_UNESCAPED_UNICODE), 3, $this->logDebugFile);
            
            // ANA ÜRÜN SATIRINI EKLE
            $items[] = $item;
            
            // ─── KADEMELİ İSKONTO - HER İSKONTO İÇİN AYRI SATIR ───
            // Logo'da kademeli iskonto yapısı: Her iskonto seviyesi için ayrı LINETYPE=2 satırı
            if (!empty($i['iskonto_formulu']) && trim($i['iskonto_formulu']) !== '') {
                $discountFormula = trim($i['iskonto_formulu']);
                // Tire veya nokta ile ayrılmış iskontoları al (örn: "50-5-10")
                $discounts = preg_split('/[-.]/', $discountFormula);
                
                foreach ($discounts as $idx => $disc) {
                    $discValue = (float) trim($disc);
                    if ($discValue > 0) {
                        // Her iskonto için ayrı bir LINETYPE=2 satırı oluştur
                        $lineTotal = $i['liste'] * $i['miktar'];
                        $absDiscount = ($lineTotal * $discValue) / 100;
                        
                        $discountLine = [
                            'TYPE'          => 2,  // İskonto satırı
                            'DISCOUNT_RATE' => $discValue,  // İskonto yüzdesi
                            'DETAIL_LEVEL'  => 0,  // Satır iskontosu için 0 (1=Belge Altı)
                            'QUANTITY'      => 0,
                            'PRICE'         => 0,
                            'TOTAL'         => $absDiscount, // İskonto tutarı (ÖNEMLİ)
                            'VAT_RATE'      => 0,
                            'VAT_AMOUNT'    => 0,
                            'TOTAL_NET'     => 0,
                        ];
                        
                        error_log("DEBUG Discount Line #{$idx}: " . json_encode($discountLine, JSON_UNESCAPED_UNICODE), 3, $this->logDebugFile);
                        
                        $items[] = $discountLine;
                    }
                }
            } 
            // ─── TEK İSKONTO ───
            elseif (!empty($i['iskonto']) && (float)$i['iskonto'] > 0) {
                // Tek iskonto için de aynı yapıyı kullan
                $lineTotal = $i['liste'] * $i['miktar'];
                $absDiscount = ($lineTotal * (float)$i['iskonto']) / 100;

                $discountLine = [
                    'TYPE'          => 2,
                    'DISCOUNT_RATE' => (float) $i['iskonto'],
                    'DETAIL_LEVEL'  => 0,
                    'QUANTITY'      => 0,
                    'PRICE'         => 0,
                    'TOTAL'         => $absDiscount,
                    'VAT_RATE'      => 0,
                    'VAT_AMOUNT'    => 0,
                    'TOTAL_NET'     => 0,
                ];
                
                error_log("DEBUG Single Discount Line: " . json_encode($discountLine, JSON_UNESCAPED_UNICODE), 3, $this->logDebugFile);
                
                $items[] = $discountLine;
            }
        }

        return $items;
    }

    private function determineArpCode(array $order): string
    {
        if (is_numeric($order['musteriid'] ?? null)) {
            $c = $this->db->getCompanyInfoById((int) $order['musteriid']);
            if (!empty($c['s_arp_code'])) {
                return $c['s_arp_code'];
            }
        }
        return $order['sirket_arp_code'] ?? '';
    }

    private function buildPayload(array $order, array $items, string $arpCode): array
    {
        $currSel = $this->determineCurrencySelection($items);
        $headerCurr = $items[0]['EDT_CURR'] ?? '160';
        $totals = $this->calculateForeignTotals($items);
        
        // CURRSEL: 0=Yerel(TL), 1=Raporlama, 2=İşlem
        // Header dövizi TL olsa bile eğer satırlarda döviz varsa İşlem Dövizi (2) seçilmelidir (e-Fatura kuralı)
        $headerCurrInt = (int)$headerCurr;
        $hasForeignLine = false;
        foreach ($items as $it) {
            if (isset($it['EDT_CURR']) && $it['EDT_CURR'] !== '160') {
                $hasForeignLine = true;
                break;
            }
        }
        
        $currSelVal = ($headerCurrInt !== 160 || $hasForeignLine) ? 2 : 0; 
        
        return [
            'DATE' => date('c', strtotime($order['tekliftarihi'])),
            'TIME' => $this->getLogoTime(time()), // Şu anki saati integer formatında gönder
            'ARP_CODE' => $arpCode,
            'SOURCE_WH' => (int) $order['source_wh'],
            'SOURCE_COST_GRP' => (int) $order['source_cost_grp'],
            'DIVISION' => (int) $order['division'],
            'DEPARTMENT' => (int) $order['department'],
            'DOC_NUMBER' => $order['doc_number'] ?? '',
            'FACTORY' => (int) $order['factory'],
            'SALESMAN_CODE' => $order['salesman_code'],
            'SALESMANREF' => (int)$order['salesmanref'],
            'TRADING_GRP' => $order['trading_grp'],
            'PAYMENT_CODE' => $order['payment_code'],
            'PAYDEFREF' => (int)$order['paydefref'],
            'ORDER_STATUS' => (int)$order['order_status'],
            'VATEXCEPT_CODE' => $order['vatexcept_code'] ?? '',
            'VATEXCEPT_REASON' => $order['vatexcept_reason'] ?? '',
            'AUXIL_CODE' => $order['auxil_code'],
            'AUTH_CODE' => 'GMP',
            'NOTES1' => $order['notes1'],
            'NOTES2' => $order['notes2'] ?? '',
            'DOC_TRACK_NR' => $order['doc_track_nr'] ?? '',
            
            // YENİ: Döviz Bilgileri ve Taşıyıcı
            'CURRSEL_TOTAL'    => $currSelVal,
            'CURRSEL_DETAILS'  => $currSelVal,
            'SHPAGNCOD'        => $order['shipping_agent'] ?? 'GEMPA',
            'SHIPPING_AGENT'   => $order['shipping_agent'] ?? 'GEMPA', // Alternatif alan adı
            
            // Fatura Tipi: e-Fatura (1)
            'EINVOICE'         => 1,
            'EINVOICE_TYPE'    => 1, // Bazı versiyonlarda gerekebilir
            
            // Header seviyesinde işlem dövizi ve kuru
            'CURR_TRANSACTIN' => (int)$headerCurr, 
            'CURR_TYPE'       => $currSelVal, // 2 = İşlem Dövizi
            'TC_XRATE'        => $items[0]['TC_XRATE'] ?? 1, // İşlem Kuru (Veritabanı Adı)
            'TC_RATE'         => $items[0]['TC_XRATE'] ?? 1, // İşlem Kuru (API Adı Olasılığı)
            
            'RC_RATE' => 1,
            'RC_NET' => $totals['rcNet'],
            'ADD_DISCOUNTS' => 0, // Belge altı iskonto kullanılmıyor (Satır bazlı)
            'TOTAL_DISCOUNTS' => $totals['totalDiscountAmount'],
            'TOTAL_DISCOUNTED' => $totals['totalDiscounted'],
            'TOTAL_VAT' => $totals['totalVat'],
            'TOTAL_GROSS' => $totals['totalGross'],
            'TOTAL_NET' => $totals['totalNet'],
            'EXCHINFO_ADD_DISCOUNTS' => 0,
            'EXCHINFO_TOTAL_DISCOUNTED' => $totals['totalDiscounted'],
            'EXCHINFO_TOTAL_VAT' => $totals['totalVat'],
            'EXCHINFO_GROSS_TOTAL' => $totals['totalGross'],
            'TRANSACTIONS' => ['items' => $items],
        ];
    }

    private function executeApiCall(string $endpoint, array $payload): array
    {
        $this->logDebug("API Request to {$endpoint}", 0, $payload);
        $this->tokenManager = new TokenManager(config: $this->config);
        $this->restClient = new RestClient(tokenManager: $this->tokenManager, config: $this->configArray);
        try {
            $response = $this->restClient->post($endpoint, $payload);
            $this->logDebug("API Response from {$endpoint}", 0, $response);
            return $response;
        } catch (Exception $e) {
            $this->logError("API call failed for {$endpoint}: " . $e->getMessage(), 0);
            throw new Exception('API çağrısında hata: ' . $e->getMessage());
        }
    }

    private function mapCurrency(string $abbr): array
    {
        $abbr = strtoupper(trim($abbr));
        if (is_numeric($abbr)) {
            $code = (int) $abbr;
            if ($code === 20) {
                return ['edtCurr' => '20', 'currSel' => 2];
            }
            if ($code === 1) {
                return ['edtCurr' => '1', 'currSel' => 2];
            }
            if ($code === 160) {
                return ['edtCurr' => '160', 'currSel' => 1];
            }
        }

        if (strpos($abbr, 'EUR') !== false) {
            return ['edtCurr' => '20', 'currSel' => 2];
        }

        if (strpos($abbr, 'USD') !== false) {
            return ['edtCurr' => '1', 'currSel' => 2];
        }

        if (in_array($abbr, ['TL', 'TRY'], true)) {
            return ['edtCurr' => '160', 'currSel' => 1];
        }

        return ['edtCurr' => '160', 'currSel' => 1];
    }

    private function determineCurrencySelection(array $items): int
    {
        if (empty($items)) {
            return 1;
        }
        $curr = $items[0]['EDT_CURR'] ?? '160';
        return $curr === '160' ? 1 : 2;
    }

    private function calculateForeignTotals(array $items): array
    {
        $gross = 0.0;
        $vat   = 0.0;
        $totalDiscountAmount = 0.0;
        
        foreach ($items as $it) {
            $type = (int)($it['TYPE'] ?? 0);
            
            if ($type === 2) {
                // İskonto satırı
                $totalDiscountAmount += (float)($it['TOTAL'] ?? 0);
            } else {
                // Ürün satırı
                $qty   = (float) ($it['QUANTITY'] ?? 0);
                $price = (float) ($it['EDT_PRICE'] ?? ($it['PRICE'] ?? 0));
                $line  = $price * $qty;
                $gross += $line;
                
                // KDV iskontodan sonraki tutar üzerinden mi hesaplanıyor? 
                // Logo varsayılanı satır iskontosu düşülmüş matrah üzerindendir.
                // Ancak burada basitleştirmek için mevcut mantığı koruyup sadece toplamları düzeltiyoruz.
                $rate  = (float) ($it['VAT_RATE'] ?? 0);
                $vat   += $line * $rate / 100;
            }
        }
        
        $totalDiscounted = $gross - $totalDiscountAmount;
        $totalNet = $totalDiscounted + $vat;

        return [
            'gross' => round($gross, 2),
            'vat'   => round($vat, 2),
            'totalDiscountAmount' => round($totalDiscountAmount, 2),
            'totalDiscounted' => round($totalDiscounted, 2),
            'totalGross' => round($gross, 2),
            'totalVat'   => round($vat, 2),
            'totalNet'   => round($totalNet, 2),
            'rcNet'      => round($totalNet, 2),
        ];
    }

    private function compileErrorMessage(array $resp): string
    {
        $parts = [];
        foreach (['Message', 'error', 'error_description'] as $key) {
            if (!empty($resp[$key])) {
                $parts[] = $resp[$key];
            }
        }
        if (!empty($resp['ModelState']) && is_array($resp['ModelState'])) {
            foreach ($resp['ModelState'] as $errs) {
                $parts[] = implode(' ', $errs);
            }
        }
        return trim(implode(' ', $parts));
    }

    private function extractInternalReference(array $body): ?int
    {
        return $body['INTERNAL_REFERENCE']
            ?? $body['InternalReference']
            ?? $body['internal_reference']
            ?? null;
    }

    private function extractNumberFromHeaderResponse(array $body): ?string
    {
        return $body['NUMBER'] ?? null;
    }

    private function recordSuccess(int $orderId, ?int $internalRef, ?string $logoNumberVal): void
    {
        if ($internalRef !== null) {
            $this->db->setLogoSuccess($orderId, $internalRef, $logoNumberVal);
            $this->logDebug("Set internal_reference: {$internalRef}", $orderId);
        }
    }

    private function updateItemInternals(int $orderId, array $body): void
    {
        $items = $body['TRANSACTIONS']['items']
            ?? $body['Transactions']['Items']
            ?? [];

        $updates = [];
        foreach ($items as $it) {
            $code = $it['MASTER_CODE'] ?? $it['MasterCode'] ?? null;
            $internal_ref = $it['INTERNAL_REFERENCE']
                ?? $it['InternalReference']
                ?? $it['internal_reference']
                ?? null;

            $ordficheref = $it['ORDFICHEREF'] ?? $it['ordficheref'] ?? null;
            if ($code && $internal_ref) {
                $updates[] = ['kod' => $code, 'internal' => (int) $internal_ref, 'ordficheref' => (int) $ordficheref];
            }
        }
        if ($updates) {
            $this->db->updateProductsInternalRefs($orderId, $updates);
            $this->logDebug("Ürün iç referanslar güncellendi", $orderId);
        }
    }

    private function fail(int $orderId, string $message): array
    {
        $this->logError($message, $orderId);
        $this->db->markLogoError($orderId);
        return ['status' => false, 'message' => $message];
    }

    private function logError(string $message, int $orderId): void
    {
        error_log("LogoService error for OrderID {$orderId}: {$message}", 3, $this->logErrorFile);
    }

    /**
     * Genel amaçlı, unsafe query: önce doğrudan MSSQL'e,
     * hatalı nesne adı gelirse REST API'ye devret.
     */
    private function executeSqlQuery(string $sql): array
    {
        $oneLineSql = trim(preg_replace('/\s+/', ' ', $sql));
        // $this->logDebug("SQL Query", 0, $oneLineSql);

        global $gempa_logo_db;
        try {
            $stmt = $gempa_logo_db->prepare($oneLineSql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            if (
                stripos($e->getMessage(), 'Invalid object name') !== false
                || stripos($e->getMessage(), 'Geçersiz nesne adı') !== false
            ) {
                return $this->executeRestSql($oneLineSql);
            }
            $this->logError("Direct SQL failed: " . $e->getMessage(), 0);
            throw new Exception("SQL query failed: " . $e->getMessage());
        }
    }

    /**
     * Fallback olarak REST API üzerinden çalıştır.
     */
    private function executeRestSql(string $oneLineSql): array
    {
        // $this->logDebug("SQL Query", 0, $oneLineSql);
        $rawJson = json_encode($oneLineSql, JSON_UNESCAPED_UNICODE);
        try {
            $response = $this->restClient->postRaw('queries/unsafe?cmdTimeout=60', $rawJson);
        } catch (Exception $e) {
            throw new Exception("SQL query failed: " . $e->getMessage());
        }
        return $response['items'] ?? [];
    }

    /**
     * Cari hesap kartları için tanımlı "Özel Kod" listesini getirir
     */
    /**
     * Cari hesap kartları için tanımlı "Özel Kod" listesini getirir
     */
    public function getSpecodes(int $firmNr): array
    {
        return $this->getCachedResult("specodes_2_1_{$firmNr}", function() use ($firmNr) {
            $sql = "SELECT LOGICALREF, CODETYPE, SPECODETYPE, SPECODE, DEFINITION_, {$firmNr} AS FIRMNR
                FROM LG_{$firmNr}_SPECODES
                WHERE SPECODETYPE = 2 AND CODETYPE = 1";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Cari hesap kartlarında kullanılan yetki kodlarını listeler
     */

    /**
     * Cache helper: Returns cached data if valid, otherwise calls $fetcher and saves result.
     */
    private function getCachedResult(string $key, callable $fetcher, int $ttl = 86400): array
    {
        // Cache directory
        $cacheDir = __DIR__ . '/../cache';
        if (!is_dir($cacheDir)) {
            if (!@mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
                // Cannot create dir, fallback to direct fetch
                return $fetcher();
            }
        }

        $cacheFile = $cacheDir . '/logo_metadata_' . md5($key) . '.json';
        
        // Check if valid cache exists
        if (file_exists($cacheFile)) {
            $mtime = filemtime($cacheFile);
            if ($mtime && (time() - $mtime < $ttl)) {
                $json = file_get_contents($cacheFile);
                if ($json) {
                    $decoded = json_decode($json, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }
                }
            }
        }

        // Fetch fresh data
        $data = $fetcher();

        // Save to cache
        file_put_contents($cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $data;
    }

    /**
     * Clears cache files matching a pattern.
     */
    private function clearCache(string $pattern = '*'): void
    {
        $cacheDir = __DIR__ . '/../cache';
        if (!is_dir($cacheDir)) {
            return;
        }
        $files = glob($cacheDir . '/logo_metadata_' . $pattern . '.json');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    public function getAuthCodes(int $firmNr): array
    {
        return $this->getCachedResult("auth_codes_{$firmNr}", function() use ($firmNr) {
            $sql = "SELECT MIN(LOGICALREF) AS LOGICALREF, CYPHCODE AS CODE, '' AS DEFINITION_, {$firmNr} AS FIRMNR
                    FROM LG_{$firmNr}_CLCARD
                    WHERE CYPHCODE IS NOT NULL AND CYPHCODE <> ''
                    GROUP BY CYPHCODE";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Bölümler (L_CAPIDEPT)
     */
    public function getDepartments(int $firmNr): array
    {
        return $this->getCachedResult("departments_{$firmNr}", function() use ($firmNr) {
            $sql = "
                SELECT LOGICALREF, FIRMNR, NR, NAME 
                FROM L_CAPIDEPT 
                WHERE FIRMNR = {$firmNr}
            ";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Fabrikalar (L_CAPIFACTORY)
     */
    public function getFactories(int $firmNr): array
    {
        return $this->getCachedResult("factories_{$firmNr}", function() use ($firmNr) {
            $sql = "
                SELECT LOGICALREF, FIRMNR, NR, NAME 
                FROM L_CAPIFACTORY 
                WHERE FIRMNR = {$firmNr}
            ";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * İşyeri / Division (L_CAPIDIV)
     */
    public function getDivisions(int $firmNr): array
    {
        return $this->getCachedResult("divisions_{$firmNr}", function() use ($firmNr) {
            $sql = "
                SELECT LOGICALREF, FIRMNR, NR, NAME 
                FROM L_CAPIDIV 
                WHERE FIRMNR = {$firmNr}
            ";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Ambarlar / Warehouses (L_CAPIWHOUSE)
     */
    public function getWarehouses(int $firmNr): array
    {
        return $this->getCachedResult("warehouses_{$firmNr}", function() use ($firmNr) {
            $sql = "
                SELECT LOGICALREF, FIRMNR, NR, NAME, COSTGRP, DIVISNR 
                FROM L_CAPIWHOUSE 
                WHERE FIRMNR = {$firmNr}
            ";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Ticari İşlem Grupları (L_TRADGRP)
     */
    public function getTradeGroups(): array
    {
        return $this->getCachedResult("trade_groups", function() {
            $sql = "
                SELECT LOGICALREF, GCODE, GDEF 
                FROM L_TRADGRP
            ";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Satış Elemanları (LG_SLSMAN)
     */
    public function getSalesmen(int $firmNr): array
    {
        return $this->getCachedResult("salesmen_{$firmNr}", function() use ($firmNr) {
            $sql = "
                SELECT LOGICALREF, CODE, DEFINITION_, ACTIVE, USERID, FIRMNR, POSITION_ 
                FROM LG_SLSMAN 
                WHERE FIRMNR = {$firmNr}
            ";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Ödeme / Tahsilat Planları (LG_{FIRMNR}_PAYPLANS)
     */
    public function getPayPlans(int $firmNr): array
    {
        return $this->getCachedResult("pay_plans_{$firmNr}", function() use ($firmNr) {
            $sql = "
                SELECT
                    LOGICALREF,
                    CODE,
                    DEFINITION_,
                    {$firmNr} AS FIRMNR
                FROM LG_{$firmNr}_PAYPLANS
            ";
            return $this->executeSqlQuery($sql);
        });
    }


    /**
     * Birim Setleri (LG_{FIRMNR}_UNITSETF)
     */
    public function getUnitSets(int $firmNr): array
    {
        return $this->getCachedResult("unit_sets_{$firmNr}", function() use ($firmNr) {
            $sql = "
                SELECT
                    LOGICALREF,
                    CODE,
                    NAME,
                    CARDTYPE,
                    RECSTATUS,
                    GUID,
                    {$firmNr} AS FIRMNR
                FROM LG_{$firmNr}_UNITSETF
            ";
            return $this->executeSqlQuery($sql);
        });
    }

    /**
     * Vergi Daireleri (L_TAXOFFICE)
     */
    public function getTaxOffices(int $firmNr): array
    {
        return $this->getCachedResult("tax_offices_{$firmNr}", function() {
            $sql = "
                SELECT LOGICALREF, CODE, NAME
                FROM L_TAXOFFICE
            ";
            return $this->executeSqlQuery($sql);
        });
    }


    /**
     * Tüm referans tabloları Logo'dan çek ve yerelde güncelle.
     *
     * @param int $firmNr
     * @return array{success: string[], failed: array<string,string>}
     */
    public function syncReferenceData(int $firmNr): array
    {
        // 1) Önce cache'i temizle
        $this->clearCache('*');

        $mysqli = $this->db->getConnection();
        $results = ['success' => [], 'failed' => []];

        try {
            $mysqli->begin_transaction();

            $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
            $tables = [
                'departments',
                'factories',
                'divisions',
                'warehouses',
                'trade_groups',
                'salesmen',
                'pay_plans',
                'unit_sets',
                'specodes',
                'auth_codes',
            ];
            foreach ($tables as $tbl) {
                $mysqli->query("TRUNCATE TABLE `{$tbl}`");
            }
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

            // 5) Sadece INSERT adımına geç
            $resources = [
                [
                    'name' => 'departments',
                    'method' => 'getDepartments',
                    'insert' => "INSERT INTO departments (logicalref, firmnr, nr, name) VALUES (?, ?, ?, ?)",
                    'types' => 'iiis',
                    'fields' => ['LOGICALREF', 'FIRMNR', 'NR', 'NAME'],
                ],
                [
                    'name' => 'factories',
                    'method' => 'getFactories',
                    'insert' => "INSERT INTO factories  (logicalref, firmnr, nr, name) VALUES (?, ?, ?, ?)",
                    'types' => 'iiis',
                    'fields' => ['LOGICALREF', 'FIRMNR', 'NR', 'NAME'],
                ],
                [
                    'name' => 'divisions',
                    'method' => 'getDivisions',
                    'insert' => "INSERT INTO divisions  (logicalref, firmnr, nr, name) VALUES (?, ?, ?, ?)",
                    'types' => 'iiis',
                    'fields' => ['LOGICALREF', 'FIRMNR', 'NR', 'NAME'],
                ],
                [
                    'name' => 'warehouses',
                    'method' => 'getWarehouses',
                    'insert' => "INSERT INTO warehouses (logicalref, firmnr, nr, name, costgrp, divisnr) VALUES (?, ?, ?, ?, ?, ?)",
                    'types' => 'iiisii',
                    'fields' => ['LOGICALREF', 'FIRMNR', 'NR', 'NAME', 'COSTGRP', 'DIVISNR'],
                ],
                [
                    'name' => 'trade_groups',
                    'method' => 'getTradeGroups',
                    'insert' => "INSERT INTO trade_groups (logicalref, gcode, gdef) VALUES (?, ?, ?)",
                    'types' => 'iss',
                    'fields' => ['LOGICALREF', 'GCODE', 'GDEF'],
                ],
                [
                    'name' => 'salesmen',
                    'method' => 'getSalesmen',
                    'insert' => "INSERT INTO salesmen (logicalref, code, definition, active, userid, firmnr, position_) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    'types' => 'issiiis',
                    'fields' => ['LOGICALREF', 'CODE', 'DEFINITION_', 'ACTIVE', 'USERID', 'FIRMNR', 'POSITION_'],
                ],
                [
                    'name' => 'pay_plans',
                    'method' => 'getPayPlans',
                    'insert' => "INSERT INTO pay_plans (logicalref, code, definition, firmnr) VALUES (?, ?, ?, ?)",
                    'types' => 'issi',
                    'fields' => ['LOGICALREF', 'CODE', 'DEFINITION_', 'FIRMNR'],
                ],
                [
                    'name' => 'unit_sets',
                    'method' => 'getUnitSets',
                    'insert' => "INSERT INTO unit_sets (logicalref, code, name, cardtype, recstatus, guid, firmnr) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    'types' => 'issiiis',
                    'fields' => ['LOGICALREF', 'CODE', 'NAME', 'CARDTYPE', 'RECSTATUS', 'GUID', 'FIRMNR'],
                ],
                [
                    'name' => 'specodes',
                    'method' => 'getSpecodes',
                    'insert' => "INSERT INTO specodes (logicalref,codetype,specodetype,specode,definition_,firmnr)
                                VALUES (?, ?, ?, ?, ?, ?)",
                    'types' => 'iiissi',
                    'fields' => ['LOGICALREF', 'CODETYPE', 'SPECODETYPE', 'SPECODE', 'DEFINITION_', 'FIRMNR'],
                ],
                [
                    'name' => 'auth_codes',
                    'method' => 'getAuthCodes',
                    'insert' => "INSERT INTO auth_codes (logicalref, code, definition, firmnr) VALUES (?, ?, ?, ?)",
                    'types' => 'issi',
                    'fields' => ['LOGICALREF', 'CODE', 'DEFINITION_', 'FIRMNR'],
                ],
            ];

            foreach ($resources as $res) {
                // Logo'dan yeni veriyi çek
                $items = $this->{$res['method']}($firmNr);

                // INSERT hazırlayıp çalıştır
                $stmtIns = $mysqli->prepare($res['insert']);
                foreach ($items as $row) {
                    $values = array_map(fn($f) => $row[$f] ?? null, $res['fields']);
                    $stmtIns->bind_param($res['types'], ...$values);
                    $stmtIns->execute();
                }
                $stmtIns->close();

                $results['success'][] = $res['name'];
            }

            // 6) Commit
            $mysqli->commit();
        } catch (Exception $e) {
            $mysqli->rollback();
            $results['failed'][$res['name']] = $e->getMessage();
        }

        return $results;
    }


    /**
     * Hata günlüğüne SQL veya uygulama hatası yazar
     */
    private function logDebug(string $context, int $orderId, $data = null): void
    {
        $msg = "[DEBUG] {$context}"
            . ($orderId ? " (OrderID {$orderId})" : "")
            . ": " . (is_scalar($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE));
        error_log($msg . PHP_EOL, 3, $this->logDebugFile);
    }

    /**
     * Fetches risk data from period-based LG_{FIRM}_{PERIOD}_CLRNUMS table.
     * Default period is '01'.
     */
    public function getRiskInfo(int $firmNr, int $logicalRef, string $period = '01'): array
    {
        // Table name is dynamic based on period
        $table = "LG_{$firmNr}_{$period}_CLRNUMS";
        
        $sql = "
            SELECT 
                RISKLIMIT AS CREDIT_LIMIT,
                RISKTOTAL AS RISK_LIMIT, 
                RISKBALANCED,
                ORDRISKTOTAL,
                DESPRISKTOTAL
            FROM {$table}
            WHERE CLCARDREF = {$logicalRef}
        ";

        // This query might fail if table doesn't exist (wrong period), so we use try/catch in executeSqlQuery structure
        // But executeSqlQuery handles PDO exceptions.
        try {
            $rows = $this->executeSqlQuery($sql);
            return $rows[0] ?? [];
        } catch (Exception $e) {
            // Fallback or ignore if table not found
            $this->logError("Risk info fetch failed for ref {$logicalRef}: " . $e->getMessage(), 0);
            return [];
        }
    }
}
