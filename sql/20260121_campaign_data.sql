-- ============================================
-- KAMPANYA VERİLERİ - 5 KATEGORİ
-- Tarih: 2026-01-21
-- ============================================

-- ====================
-- 1. POMPALAR
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('POMPALAR - Ana Bayi Özel Fiyat', 'POMPALAR', 'ana_bayi', 10, 10000.00, 50.5, 45.0, 1, 100);

SET @campaign_pompalar = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_pompalar, '0111STRM50M', 52.31788),
(@campaign_pompalar, '0111STRM80M', 52.53165),
(@campaign_pompalar, '0111STRM100M', 52.51572),
(@campaign_pompalar, '0111STRM150M', 48.96755),
(@campaign_pompalar, '0111STRN50M', 53.03030),
(@campaign_pompalar, '0111STRN75M', 52.95775),
(@campaign_pompalar, '0111STRN75T', 53.10559),
(@campaign_pompalar, '0111STRN100M', 52.89256),
(@campaign_pompalar, '0111STRN100T', 53.02594),
(@campaign_pompalar, '0111STRN150M', 53.08057),
(@campaign_pompalar, '0111STRN150T', 53.11721),
(@campaign_pompalar, '0111STRN200M', 52.98165),
(@campaign_pompalar, '0111STRN200T', 53.11005),
(@campaign_pompalar, '0111STRN300M', 52.91667),
(@campaign_pompalar, '0111STRN300T', 52.90179),
(@campaign_pompalar, '0111STRN350M', 53.01587),
(@campaign_pompalar, '0111STRN350T', 52.94118);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_pompalar, 'amount_based', 'Fiş Toplamı ≥ 10.000€', 10000.00, 5.00, 1),
(@campaign_pompalar, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 2. FİLTRELER
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('FİLTRELER - Ana Bayi Özel Fiyat', 'FİLTRELER', 'ana_bayi', 10, 10000.00, 50.5, 45.0, 1, 90);

SET @campaign_filtreler = LAST_INSERT_ID();

-- PLASTİK FİLTRELER
INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_filtreler, '0211211S', 52.9412),
(@campaign_filtreler, '0211212S', 53.0612),
(@campaign_filtreler, '021131', 55.0201),
(@campaign_filtreler, '021132', 55.1839),
(@campaign_filtreler, '021133', 56.1404),
(@campaign_filtreler, '021911', 51.8750),
(@campaign_filtreler, '021912', 52.0202),
(@campaign_filtreler, '021913', 51.8657),
(@campaign_filtreler, '021921', 51.8248),
(@campaign_filtreler, '021923', 52.0661),
(@campaign_filtreler, '021813', 51.7241),
(@campaign_filtreler, '021814', 51.9481),
(@campaign_filtreler, '021815', 51.8519),
-- BOBİN SARGI FİLTRELER
(@campaign_filtreler, '021712', 49.8282),
(@campaign_filtreler, '021712S', 50.0000),
(@campaign_filtreler, '021713', 50.0000),
(@campaign_filtreler, '021713S', 50.0000),
(@campaign_filtreler, '021714', 50.0000),
(@campaign_filtreler, '021715S', 50.0000),
(@campaign_filtreler, '021715', 50.0000),
(@campaign_filtreler, '021311B', 49.7872),
(@campaign_filtreler, '021312B', 49.8039),
-- YENİ FİLTRELER
(@campaign_filtreler, '021610', 49.8127),
(@campaign_filtreler, '021611', 50.0000),
(@campaign_filtreler, '021612', 49.8728),
(@campaign_filtreler, '021610T', 49.7925),
(@campaign_filtreler, '021611T', 50.0000),
(@campaign_filtreler, '021612T', 49.8638),
(@campaign_filtreler, '021131A', 52.8571),
(@campaign_filtreler, '021132A', 52.8875),
(@campaign_filtreler, '021133A', 53.0120),
(@campaign_filtreler, '021131TA', 53.0364),
(@campaign_filtreler, '021132TA', 53.1034),
(@campaign_filtreler, '021133TA', 52.8767);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_filtreler, 'amount_based', 'Fiş Toplamı ≥ 10.000€', 10000.00, 5.00, 1),
(@campaign_filtreler, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 3. ÇOK YOLLU VANA
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('ÇOK YOLLU VANA - Ana Bayi Özel Fiyat', 'ÇOK YOLLU VANA', 'ana_bayi', 100, 10000.00, 50.5, 45.0, 1, 80);

SET @campaign_vana = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_vana, '02400', 50.0000),
(@campaign_vana, '02213027', 54.2857);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_vana, 'amount_based', 'Fiş Toplamı ≥ 10.000€', 10000.00, 5.00, 1),
(@campaign_vana, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 4. FİLTRE MEDYA
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('FİLTRE MEDYA - Ana Bayi Özel Fiyat', 'FİLTRE MEDYA', 'ana_bayi', 5000, 20000.00, 50.5, 45.0, 1, 70);

SET @campaign_medya = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_medya, '02312', 51.6129),
(@campaign_medya, '02313', 51.6129),
(@campaign_medya, '02314', 51.6129),
(@campaign_medya, '023411', 49.9316),
(@campaign_medya, '023412', 49.9316),
(@campaign_medya, '023413', 49.9316),
(@campaign_medya, '023411K', 53.9474),
(@campaign_medya, '023412K', 53.9474),
(@campaign_medya, '023413K', 53.9474);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_medya, 'amount_based', 'Fiş Toplamı ≥ 20.000€', 20000.00, 5.00, 1),
(@campaign_medya, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ====================
-- 5. MERDİVEN
-- ====================
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('MERDİVEN - Ana Bayi Özel Fiyat', 'MERDİVEN', 'ana_bayi', 10, 5000.00, 50.5, 45.0, 1, 60);

SET @campaign_merdiven = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_merdiven, '0311111', 47.7500),
(@campaign_merdiven, '0311112', 47.7500),
(@campaign_merdiven, '0311113', 47.7500),
(@campaign_merdiven, '0311114', 47.7500),
(@campaign_merdiven, '0311211', 47.7500),
(@campaign_merdiven, '0311212', 47.7500),
(@campaign_merdiven, '0311213', 47.7500),
(@campaign_merdiven, '0311214', 50.4545),
(@campaign_merdiven, '0312111', 47.7500),
(@campaign_merdiven, '0312112', 47.7500),
(@campaign_merdiven, '0312113', 47.7500),
(@campaign_merdiven, '0312114', 47.7500),
(@campaign_merdiven, '0312221', 47.7500),
(@campaign_merdiven, '0312222', 47.7500),
(@campaign_merdiven, '0312223', 47.7500),
(@campaign_merdiven, '0312224', 47.7500);

-- NOT: Kullanıcı "min 50 adet için +5%" dedi, ama genel kural 10.000€
-- ÖNCELİKLE bu kuralla başlayalım, gerekirse düzeltiriz
INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_merdiven, 'quantity_based', 'Toplam Miktar ≥ 50 Adet', 50.00, 5.00, 1),
(@campaign_merdiven, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ============================================
-- ÖZET
-- ============================================
-- 5 Kampanya Kategorisi Eklendi:
-- 1. POMPALAR: 17 ürün, min 10 adet
-- 2. FİLTRELER: 34 ürün, min 10 adet
-- 3. ÇOK YOLLU VANA: 2 ürün, min 100 adet
-- 4. FİLTRE MEDYA: 9 ürün, min 5000 adet
-- 5. MERDİVEN: 16 ürün, min 10 adet, min 50 adet için +5%
-- ============================================
