-- HAVUZIÇI EKIPMAN kampanyasını kontrol et

-- 1. Kampanya var mı?
SELECT * FROM custom_campaigns 
WHERE category_name LIKE '%HAVUZ%' 
   OR name LIKE '%HAVUZ%';

-- 2. Tüm kampanyaları listele
SELECT id, category_name, name, is_active 
FROM custom_campaigns 
ORDER BY id;

-- 3. HAVUZIÇI EKIPMAN kampanyası yoksa oluştur
INSERT INTO custom_campaigns 
(category_name, name, start_date, end_date, min_quantity, min_total_amount, currency, priority, is_active)
VALUES 
('HAVUZIÇI EKIPMAN', 'HAVUZIÇI EKIPMAN - Ana Bayi Özel Fiyat', '2026-01-01', '2026-12-31', 0, 1500, 'EUR', 5, 1);

-- 4. Kampanya ID'sini al (yukarıdaki INSERT'ten sonra)
SELECT LAST_INSERT_ID() as campaign_id;

-- 5. Kampanya kurallarını ekle
-- Önce campaign_id'yi yukarıdan alın, sonra bu sorguyu çalıştırın:
-- REPLACE @campaign_id with actual ID from step 4

INSERT INTO custom_campaign_rules 
(campaign_id, rule_name, rule_type, condition_value, discount_rate, priority)
VALUES 
(@campaign_id, 'Fiş Toplamı ≥ 1.500€', 'amount_based', 1500, 0, 1),
(@campaign_id, 'Ana Bayi Ek İskonto', 'amount_based', 5000, 5, 2);

-- 6. Kampanya ürünlerini ekle (HAVUZIÇI EKIPMAN kategorisindeki tüm ürünler)
INSERT INTO custom_campaign_products (campaign_id, product_code, discount_rate)
SELECT @campaign_id, stok_kodu, 0
FROM kampanya_ozel_fiyatlar
WHERE kategori = 'HAVUZIÇI EKIPMAN';
