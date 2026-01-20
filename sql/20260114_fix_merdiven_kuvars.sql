-- Merdiven ve Kuvars Kumu ürünlerini yeni kampanyaya taşı
-- Bu ürünler farklı koşullara sahip (KG bazlı olabilir)

-- Yeni kampanya oluştur: Merdiven & Kuvars
INSERT INTO custom_campaigns 
    (name, description, customer_type, customer_code, min_quantity, min_total_amount, fallback_discount, active, priority) 
VALUES 
    ('Ertek - Merdiven & Kuvars', 'Kimyasal ürünler - 5000kg min', 'specific', '120.01.E04', 5000, 0, 45.00, 1, 50);

SET @campMerdiven = LAST_INSERT_ID();

-- Kuralları ekle
INSERT INTO custom_campaign_rules (campaign_id, rule_type, rule_name, condition_value, discount_rate, priority) VALUES
(@campMerdiven, 'quantity_based', 'Ana Bayi Ek İskonto (≥20000 kg)', 20000.00, 5.00, 1),
(@campMerdiven, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- Merdiven ve Kuvars ürünlerini taşı
UPDATE custom_campaign_products 
SET campaign_id = @campMerdiven 
WHERE product_code LIKE '023%' OR product_code LIKE '031%';

-- Kontrol
SELECT 
    c.name,
    COUNT(cp.id) as urun_sayisi,
    GROUP_CONCAT(cp.product_code SEPARATOR ', ') as urunler
FROM custom_campaign_products cp
JOIN custom_campaigns c ON cp.campaign_id = c.id
WHERE cp.product_code LIKE '023%' OR cp.product_code LIKE '031%'
GROUP BY c.id;
