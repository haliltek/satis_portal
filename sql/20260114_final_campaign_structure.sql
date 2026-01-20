-- Kampanyaları Yeniden Yapılandır - Doğru Koşullarla

-- Kampanya 1: Filtreler (Min 10 adet, Ana bayi ek %5: ≥10K€)
UPDATE custom_campaigns 
SET name = 'Ertek - Filtreler', 
    description = 'Min 10 adet, Ana bayi +5% için ≥10K€',
    min_quantity = 10,
    min_total_amount = 0
WHERE id = 1;

-- Kampanya 4: Çok Yollu Vana (Min 100 adet, Ana bayi ek %5: ≥10K€)
UPDATE custom_campaigns 
SET name = 'Ertek - Çok Yollu Vana', 
    description = 'Min 100 adet, Ana bayi +5% için ≥10K€',
    min_quantity = 100,
    min_total_amount = 0
WHERE id = 4;

-- Kampanya 8: Kuvars & Cam Medya (Min 5000 kg, Ana bayi ek %5: ≥20K kg)
UPDATE custom_campaigns 
SET name = 'Ertek - Kuvars & Cam Medya', 
    description = 'Min 5000 kg, Ana bayi +5% için ≥20K kg',
    min_quantity = 5000,
    min_total_amount = 0
WHERE id = 8;

-- Kuralları güncelle
-- Kampanya 1 ve 4: Ana bayi ek %5 için ≥10K€ (tutar bazlı)
DELETE FROM custom_campaign_rules WHERE campaign_id IN (1, 4);
INSERT INTO custom_campaign_rules (campaign_id, rule_type, rule_name, condition_value, discount_rate, priority) VALUES
(1, 'amount_based', 'Ana Bayi Ek İskonto (≥10.000€)', 10000.00, 5.00, 1),
(1, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2),
(4, 'amount_based', 'Ana Bayi Ek İskonto (≥10.000€)', 10000.00, 5.00, 1),
(4, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- Kampanya 8: Ana bayi ek %5 için ≥20K kg (miktar bazlı)
DELETE FROM custom_campaign_rules WHERE campaign_id = 8;
INSERT INTO custom_campaign_rules (campaign_id, rule_type, rule_name, condition_value, discount_rate, priority) VALUES
(8, 'quantity_based', 'Ana Bayi Ek İskonto (≥20.000 kg)', 20000.00, 5.00, 1),
(8, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- Filtre ürünlerini Kampanya 1'e taşı (021xxx)
UPDATE custom_campaign_products 
SET campaign_id = 1 
WHERE product_code LIKE '021%';

-- Çok yollu vana Kampanya 4'te kalıyor (02400, 02213027)
-- Zaten orada olmalı

-- Kuvars & Cam Medya Kampanya 8'de kalıyor (023xxx)
-- Zaten orada olmalı

-- Kontrol
SELECT 
    c.id,
    c.name,
    c.min_quantity,
    c.min_total_amount,
    COUNT(cp.id) as urun_sayisi
FROM custom_campaigns c
LEFT JOIN custom_campaign_products cp ON c.id = cp.campaign_id
WHERE c.customer_code = '120.01.E04'
GROUP BY c.id
ORDER BY c.priority DESC;
