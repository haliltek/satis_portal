<?php
// services/MaterialService.php

use Proje\TokenManager;
use Proje\RestClient;

class MaterialService
{
    private $config;
    private $logger;

    public function __construct(array $config, LoggerService $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function createMaterialCard(array $postData): array
    {
        $this->logger->log("[createMaterialCard] Start processing form data.");
        // Form verilerini toplama ve basit doğrulama (xss, addslashes gibi işlemleri sanitize() metoduyla yapabilirsiniz)
        $formData = [
            'stokadi'      => isset($postData["stokadi"]) ? $this->sanitize($postData["stokadi"]) : '',
            'kod'          => isset($postData["kod"]) ? $this->sanitize($postData["kod"]) : '',
            'card_type'    => isset($postData["card_type"]) ? (int)$postData["card_type"] : 10,
            'auxil_code'   => isset($postData["auxil_code"]) ? $this->sanitize($postData["auxil_code"]) : '',
            'auth_code'    => isset($postData["auth_code"]) ? $this->sanitize($postData["auth_code"]) : '',
            'group_code'   => isset($postData["group_code"]) ? $this->sanitize($postData["group_code"]) : '',
            'unitset_code' => isset($postData["unitset_code"]) ? $this->sanitize($postData["unitset_code"]) : '',
            'vat'          => isset($postData["vat"]) ? floatval($postData["vat"]) : 20,
            'selvat'       => isset($postData["selvat"]) ? floatval($postData["selvat"]) : 20,
            'returnvat'    => isset($postData["returnvat"]) ? floatval($postData["returnvat"]) : 20,
            'selprvat'     => isset($postData["selprvat"]) ? floatval($postData["selprvat"]) : 20,
            'returnprvat'  => isset($postData["returnprvat"]) ? floatval($postData["returnprvat"]) : 20,
            'auxil_code5'  => isset($postData["auxil_code5"]) ? intval($postData["auxil_code5"]) : 5,
        ];

        if (empty($formData['kod'])) {
            $msg = 'Stok kodu boş olamaz.';
            $this->logger->log("[createMaterialCard] Validation error: $msg", "ERROR");
            return ['success' => false, 'message' => $msg];
        }
        if (empty($formData['stokadi'])) {
            $msg = 'Stok adı boş olamaz.';
            $this->logger->log("[createMaterialCard] Validation error: $msg", "ERROR");
            return ['success' => false, 'message' => $msg];
        }

        // EXT_ACC_FLAGS hesaplaması
        $extAccFlags = 0;
        if (isset($postData['ext_acc_eis'])) {
            $extAccFlags += 1;
        }
        if (isset($postData['ext_acc_satis'])) {
            $extAccFlags += 2;
        }
        // MULTI_ADD_TAX hesaplaması
        $multiAddTax = isset($postData['multi_add_tax']) ? 1 : 0;

        // Payload oluşturulması
        $payload = [
            "CARD_TYPE"       => $formData['card_type'],
            "CODE"            => $formData['kod'],
            "NAME"            => $formData['stokadi'],
            "AUXIL_CODE"      => $formData['auxil_code'],
            "AUTH_CODE"       => $formData['auth_code'],
            "GROUP_CODE"      => $formData['group_code'],
            "USEF_PURCHASING" => 1,
            "USEF_SALES"      => 1,
            "USEF_MM"         => 1,
            "VAT"             => $formData['vat'],
            "UNITSET_CODE"    => $formData['unitset_code'],
            "EXT_ACC_FLAGS"   => $extAccFlags,
            "MULTI_ADD_TAX"   => $multiAddTax,
            "PACKET"          => 0,
            "SELVAT"          => $formData['selvat'],
            "RETURNVAT"       => $formData['returnvat'],
            "SELPRVAT"        => $formData['selprvat'],
            "RETURNPRVAT"     => $formData['returnprvat'],
            "AUXIL_CODE5"     => $formData['auxil_code5']
        ];

        $this->logger->log("[createMaterialCard] Payload created: " . json_encode($payload));

        try {
            $tokenManager = new TokenManager($this->config);
            $restClient   = new RestClient($tokenManager, $this->config);
            $response = $restClient->post('/api/v1/Items', $payload);
            $this->logger->log("[createMaterialCard] API Response: " . json_encode($response));

            if (isset($response['INTERNAL_REFERENCE'])) {
                $msg = 'Malzeme fişi gönderildi. Logical reference: ' . $response['INTERNAL_REFERENCE'];
                return ['success' => true, 'message' => $msg];
            } elseif (isset($response['error'])) {
                $msg = 'Hata oluştu: ' . $response['error'] . ' - ' . $response['error_description'];
                return ['success' => false, 'message' => $msg];
            } else {
                $msg = 'Beklenmeyen bir yanıt alındı.';
                return ['success' => false, 'message' => $msg];
            }
        } catch (Exception $e) {
            $msg = 'İstek sırasında istisna oluştu: ' . $e->getMessage();
            $this->logger->log("[createMaterialCard] Exception: $msg", "ERROR");
            return ['success' => false, 'message' => $msg];
        }
    }

    private function sanitize($value): string
    {
        return htmlspecialchars(addslashes($value), ENT_QUOTES, 'UTF-8');
    }
}
