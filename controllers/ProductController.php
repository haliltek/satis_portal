<?php
// controllers/ProductController.php

class ProductController
{
    private $authService;
    private $priceUpdater;
    public function __construct($authService, $priceUpdater)
    {
        $this->authService = $authService;
        $this->priceUpdater = $priceUpdater;
    }

    public function handleUpdatePrice()
    {
        $stokKodu  = isset($_POST['stok_kodu']) ? $_POST['stok_kodu'] : '';
        $logicalref = isset($_POST['logicalref']) ? intval($_POST['logicalref']) : 0;
        $yeniFiyat = isset($_POST['yeni_fiyat']) ? floatval($_POST['yeni_fiyat']) : 0.0;

        if (empty($stokKodu) || $logicalref <= 0 || $yeniFiyat <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Geçersiz veri.']);
            exit();
        }

        $result = $this->priceUpdater->updatePrice($stokKodu, $logicalref, $yeniFiyat);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    }
}
