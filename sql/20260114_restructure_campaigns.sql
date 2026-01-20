-- Kampanya Yeniden Yapılandırma
-- Tüm ürünler Kampanya 1'e eklenmişti, farklı koşullara göre ayırıyoruz

-- ADIM 1: Yeni kampanyalar oluştur
INSERT INTO custom_campaigns 
    (name, description, customer_type, customer_code, min_quantity, min_total_amount, fallback_discount, active, priority) 
VALUES 
    -- Kampanya 3: Min 1500€ (LED, Temizlik, Havuzici)
    ('Ertek - LED & Ekipman', '1500€ minimum', 'specific', '120.01.E04', 0, 1500.00, 45.00, 1, 80),
    
    -- Kampanya 4: Min 2000€ (Boru)
    ('Ertek - Boru & PVC', '2000€ minimum', 'specific', '120.01.E04', 0, 2000.00, 45.00, 1, 70),
    
    -- Kampanya 5: Min 500 metre (Izgara)
    ('Ertek - Kenar Ekipman', '500 metre minimum', 'specific', '120.01.E04', 500, 0, 45.00, 1, 60);

-- Kampanya ID'lerini al
SET @camp3 = LAST_INSERT_ID();
SET @camp4 = @camp3 + 1;
SET @camp5 = @camp3 + 2;

-- ADIM 2: Kuralları ekle (hepsinde aynı: +%5 tutar, +%10 peşin)
INSERT INTO custom_campaign_rules (campaign_id, rule_type, rule_name, condition_value, discount_rate, priority) VALUES
-- Kampanya 3
(@camp3, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@camp3, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2),

-- Kampanya 4
(@camp4, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@camp4, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2),

-- Kampanya 5
(@camp5, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@camp5, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ADIM 3: Ürünleri taşı
-- LED (051xxx, 052xxx) -> Kampanya 3
UPDATE custom_campaign_products 
SET campaign_id = @camp3 
WHERE product_code LIKE '051%' OR product_code LIKE '052%';

-- Temizlik (08xxx, 09xxx, 10xxx) -> Kampanya 3
UPDATE custom_campaign_products 
SET campaign_id = @camp3 
WHERE product_code LIKE '08%' OR product_code LIKE '09%' OR product_code LIKE '10%';

-- Havuzici (041xx, 042xx) -> Kampanya 3
UPDATE custom_campaign_products 
SET campaign_id = @camp3 
WHERE product_code LIKE '041%' OR product_code LIKE '042%';

-- Boru (139xxxx) -> Kampanya 4
UPDATE custom_campaign_products 
SET campaign_id = @camp4 
WHERE product_code LIKE '139%';

-- Izgara (037xxx) -> Kampanya 5
UPDATE custom_campaign_products 
SET campaign_id = @camp5 
WHERE product_code LIKE '037%';

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
