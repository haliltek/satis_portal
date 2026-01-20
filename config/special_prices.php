<?php
/**
 * Özel Fiyatlandırma Konfigürasyonu
 * Manuel Kampanya Sistemi - ERTEK (120.01.E04) için özel fiyatlar
 * 
 * Bu fiyatlar minimum 10 adet alımda geçerlidir.
 * Peşin ödeme durumunda ek %10 iskonto uygulanır.
 * Ana bayi ve 50+ adet alımda ek %5 iskonto uygulanır.
 */

return [
    // Cari bazlı özel fiyatlar
    'customer_special_prices' => [
        '120.01.E04' => [ // ERTEK
            'is_main_dealer' => true,
            'min_quantity' => 10,
            'main_dealer_min_quantity' => 50,
            'main_dealer_discount' => 5, // %5 ekstra
            'cash_discount' => 10, // %10 ekstra (peşin)
            
            // Ürün özel fiyatları
            'products' => [
                // Merdiven -304 Serisi
                '0311111' => ['price' => 76, 'currency' => 'EUR'],
                '0311112' => ['price' => 87, 'currency' => 'EUR'],
                '0311113' => ['price' => 101, 'currency' => 'EUR'],
                '0311114' => ['price' => 115, 'currency' => 'EUR'],
                '0311211' => ['price' => 73, 'currency' => 'EUR'],
                '0311212' => ['price' => 83, 'currency' => 'EUR'],
                '0311213' => ['price' => 98, 'currency' => 'EUR'],
                '0312214' => ['price' => 104, 'currency' => 'EUR'],
                
                // Merdiven -316 Serisi
                '0312111' => ['price' => 103, 'currency' => 'EUR'],
                '0312112' => ['price' => 116, 'currency' => 'EUR'],
                '0312113' => ['price' => 132, 'currency' => 'EUR'],
                '0312114' => ['price' => 148, 'currency' => 'EUR'],
                '0312221' => ['price' => 99, 'currency' => 'EUR'],
                '0312222' => ['price' => 110, 'currency' => 'EUR'],
                '0312223' => ['price' => 129, 'currency' => 'EUR'],
                '0312224' => ['price' => 143, 'currency' => 'EUR'],
            ]
        ]
    ],
    
    /**
     * Özel fiyatı olan ürünü kontrol eder
     */
    'hasSpecialPrice' => function($customerCode, $productCode) {
        $config = require __FILE__;
        $prices = $config['customer_special_prices'][$customerCode]['products'] ?? [];
        return isset($prices[$productCode]);
    },
    
    /**
     * Ürünün özel fiyatını döndürür
     */
    'getSpecialPrice' => function($customerCode, $productCode) {
        $config = require __FILE__;
        $prices = $config['customer_special_prices'][$customerCode]['products'] ?? [];
        return $prices[$productCode] ?? null;
    },
    
    /**
     * Müşterinin konfigürasyonunu döndürür
     */
    'getCustomerConfig' => function($customerCode) {
        $config = require __FILE__;
        return $config['customer_special_prices'][$customerCode] ?? null;
    }
];
