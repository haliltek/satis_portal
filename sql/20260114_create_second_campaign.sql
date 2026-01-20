-- Yeni kampanya: Yüksek Miktar Ürünleri (Min 100 adet)
INSERT INTO custom_campaigns 
    (name, description, customer_type, customer_code, min_quantity, min_total_amount, fallback_discount, active, priority) 
VALUES 
    ('Ertek Ana Bayi - Yüksek Miktar', 
     'Ana bayi için yüksek hacimli ürünler - 100+ adet alımda kademeli iskonto', 
     'specific', 
     '120.01.E04', 
     100,      -- Min 100 adet
     10000.00, -- Min 10K€ (aynı)
     45.00,    -- Fallback: %45
     1, 
     90);      -- Priority 90 (ilk kampanyadan düşük, önce 10 adetlik kontrol edilsin)

SET @campaign_id = LAST_INSERT_ID();

-- Yeni ürünleri ekle (resimdekiler)
INSERT INTO custom_campaign_products (campaign_id, product_code, discount_rate) VALUES
(@campaign_id, '02400', 50.0000),
(@campaign_id, '02213027', 54.2857);

-- Aynı kuralları kopyala (tutar bazlı %5, peşin %10)
INSERT INTO custom_campaign_rules (campaign_id, rule_type, rule_name, condition_value, discount_rate, priority) VALUES
(@campaign_id, 'amount_based', 'Fiş Toplamı ≥ 10.000€', 10000.00, 5.00, 1),
(@campaign_id, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- Kontrol
SELECT 
    c.id,
    c.name,
    c.min_quantity,
    COUNT(cp.id) as urun_sayisi
FROM custom_campaigns c
LEFT JOIN custom_campaign_products cp ON c.id = cp.campaign_id
WHERE c.customer_code = '120.01.E04'
GROUP BY c.id;
