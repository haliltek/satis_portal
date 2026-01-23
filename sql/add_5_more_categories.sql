-- ============================================
-- KAMPANYA SİSTEMİ - EK 5 KATEGORİ
-- Tarih: 2026-01-21
-- Toplam: 114 Yeni Ürün
-- ============================================

-- ====================
-- 6. KENAR EKİPMAN - IZGARA
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('KENAR EKİPMAN - IZGARA - Ana Bayi Özel Fiyat', 'KENAR EKİPMAN', 'ana_bayi', 500, 5000.00, 50.5, 45.0, 1, 50);
SET @campaign_kenar = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_kenar, '0372211', 51.8349),
(@campaign_kenar, '0372212', 57.3598),
(@campaign_kenar, '0372312', 59.4173),
(@campaign_kenar, '0372221', 64.0000),
(@campaign_kenar, '0372321', 60.0000),
(@campaign_kenar, '0372421', 52.6667),
(@campaign_kenar, '0372212K', 54.9839),
(@campaign_kenar, '0372213K', 62.8571),
(@campaign_kenar, '03750', 50.0000);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_kenar, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@campaign_kenar, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 7. HAVUZİÇİ EKİPMAN
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('HAVUZİÇİ EKİPMAN - Ana Bayi Özel Fiyat', 'HAVUZİÇİ EKİPMAN', 'ana_bayi', 0, 1500.00, 50.5, 45.0, 1, 40);
SET @campaign_havuzici = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_havuzici, '04143R1', 55.0000),
(@campaign_havuzici, '04131S', 47.5000),
(@campaign_havuzici, '04133S', 55.0000),
(@campaign_havuzici, '04134S', 50.1250),
(@campaign_havuzici, '04131S1', 48.5000),
(@campaign_havuzici, '04133S1', 54.5000),
(@campaign_havuzici, '04134S1', 54.0000),
(@campaign_havuzici, '041331S', 61.8750),
(@campaign_havuzici, '041321S', 75.0000),
(@campaign_havuzici, '04132S4', 51.2500),
(@campaign_havuzici, '04231BT', 61.5385),
(@campaign_havuzici, '04234TB', 58.3333),
(@campaign_havuzici, '04231BTN', 65.6250),
(@campaign_havuzici, '04241T1B', 59.5833),
(@campaign_havuzici, '04241T2B', 59.5833),
(@campaign_havuzici, '04211BFW', 52.1739),
(@campaign_havuzici, '04212BFW', 59.8039),
(@campaign_havuzici, '04215BFW', 60.1695),
(@campaign_havuzici, '04115FW', 50.0000),
(@campaign_havuzici, '04111FW', 46.9697),
(@campaign_havuzici, '04112FW', 63.3333);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_havuzici, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@campaign_havuzici, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 8. LEDLER
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('LEDLER - Ana Bayi Özel Fiyat', 'LEDLER', 'ana_bayi', 0, 1500.00, 50.5, 45.0, 1, 30);
SET @campaign_ledler = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_ledler, '051330EO', 65.0000),
(@campaign_ledler, '051331EO', 65.0000),
(@campaign_ledler, '051333EO', 65.9091),
(@campaign_ledler, '051150EO', 61.3333),
(@campaign_ledler, '051152EO', 58.6957),
(@campaign_ledler, '051120EO', 63.8015),
(@campaign_ledler, '051121EO', 63.8015),
(@campaign_ledler, '051123EO', 68.1818),
(@campaign_ledler, '051160E', 60.0000),
(@campaign_ledler, '051161E', 60.0000),
(@campaign_ledler, '051162E', 63.1579),
(@campaign_ledler, '051163E', 60.5263),
(@campaign_ledler, '051164E', 60.5263),
(@campaign_ledler, '051165E', 63.1579),
(@campaign_ledler, '051170E', 69.1176),
(@campaign_ledler, '051171E', 67.4419),
(@campaign_ledler, '052160E', 60.0000),
(@campaign_ledler, '052161E', 60.0000),
(@campaign_ledler, '052162E', 57.7778),
(@campaign_ledler, '052163E', 57.7778),
(@campaign_ledler, '052164E', 57.7778),
(@campaign_ledler, '052165E', 57.7778),
(@campaign_ledler, '052170E', 66.2338),
(@campaign_ledler, '052171E', 63.2653),
(@campaign_ledler, '051180', 61.7647),
(@campaign_ledler, '051181', 61.7647),
(@campaign_ledler, '051182', 63.1579),
(@campaign_ledler, '051183', 63.1579),
(@campaign_ledler, '051184', 65.7895),
(@campaign_ledler, '051185', 63.1579),
(@campaign_ledler, '051190', 65.6250),
(@campaign_ledler, '051191', 78.5714),
(@campaign_ledler, '052180', 66.6667),
(@campaign_ledler, '052181', 66.6667),
(@campaign_ledler, '052182', 68.9655),
(@campaign_ledler, '052183', 68.9655),
(@campaign_ledler, '052184', 68.9655),
(@campaign_ledler, '052185', 68.9655),
(@campaign_ledler, '052190', 70.1149),
(@campaign_ledler, '052191', 76.2712),
(@campaign_ledler, '05074P', 45.8333);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_ledler, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@campaign_ledler, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 9. TEMİZLİK EKİPMANLARI
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('TEMİZLİK EKİPMANLARI - Ana Bayi Özel Fiyat', 'TEMİZLİK EKİPMANLARI', 'ana_bayi', 0, 1500.00, 50.5, 45.0, 1, 20);
SET @campaign_temizlik = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_temizlik, '08211', 69.8182),
(@campaign_temizlik, '082111', 54.5455),
(@campaign_temizlik, '082112', 54.5455),
(@campaign_temizlik, '092111', 48.3871),
(@campaign_temizlik, '092112', 48.7179),
(@campaign_temizlik, '0921121', 48.7179),
(@campaign_temizlik, '0921122', 48.6486),
(@campaign_temizlik, '092121', 46.4286),
(@campaign_temizlik, '092122', 47.2222),
(@campaign_temizlik, '09219', 66.6667),
(@campaign_temizlik, '09224', 59.5238),
(@campaign_temizlik, '09225', 58.6957),
(@campaign_temizlik, '09430', 50.0000),
(@campaign_temizlik, '09440', 52.5641),
(@campaign_temizlik, '09460', 61.4035),
(@campaign_temizlik, '09470', 52.2388),
(@campaign_temizlik, '09490', 51.5152),
(@campaign_temizlik, '09511', 60.0000),
(@campaign_temizlik, '09511E', 68.1021),
(@campaign_temizlik, '09512', 60.0000),
(@campaign_temizlik, '09512E', 68.1021),
(@campaign_temizlik, '09620', 52.5000),
(@campaign_temizlik, '09626', 67.7207),
(@campaign_temizlik, '09710', 73.4375),
(@campaign_temizlik, '09730', 68.7500),
(@campaign_temizlik, '09811', 59.1429),
(@campaign_temizlik, '09812', 63.3333),
(@campaign_temizlik, '09814', 63.3333),
(@campaign_temizlik, '10431', 59.2593);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_temizlik, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@campaign_temizlik, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 10. BORU
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('BORU - Ana Bayi Özel Fiyat', 'BORU', 'ana_bayi', 0, 2000.00, 50.5, 45.0, 1, 10);
SET @campaign_boru = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_boru, '1391001', 61.0991),
(@campaign_boru, '1391002', 58.5415),
(@campaign_boru, '1391003', 57.7580),
(@campaign_boru, '1391004', 56.1723),
(@campaign_boru, '1391005', 55.5140),
(@campaign_boru, '1391006', 55.1052),
(@campaign_boru, '1391007', 54.9453),
(@campaign_boru, '1391008', 54.9319),
(@campaign_boru, '1391009', 56.6261),
(@campaign_boru, '1391010', 53.6533),
(@campaign_boru, '1391011', 56.0778),
(@campaign_boru, '1391012', 54.6902),
(@campaign_boru, '1391013', 54.6735);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_boru, 'amount_based', 'Fiş Toplamı ≥ 5.000€', 5000.00, 5.00, 1),
(@campaign_boru, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ============================================
-- ÖZET: 5 YENİ KATEGORİ EKLENDI
-- ============================================
-- 6. KENAR EKİPMAN: 9 ürün, min 500 adet
-- 7. HAVUZİÇİ EKİPMAN: 21 ürün, min 1500€
-- 8. LEDLER: 41 ürün, min 1500€
-- 9. TEMİZLİK EKİPMANLARI: 29 ürün, min 1500€
-- 10. BORU: 13 ürün, min 2000€
-- 
-- Toplam: 113 yeni ürün
-- Genel Toplam: 10 kampanya, 191 ürün
-- ============================================
