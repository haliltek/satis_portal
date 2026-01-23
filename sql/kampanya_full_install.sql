-- ============================================
-- KAMPANYA SİSTEMİ TAM YENİDEN KURULUM
-- Tarih: 2026-01-21 (Düzeltilmiş)
-- ============================================

-- ADIM 1: Foreign Key Kontrollerini Kapat
SET FOREIGN_KEY_CHECKS = 0;

-- ADIM 2: Yedekleme (Varsa)
DROP TABLE IF EXISTS `backup_custom_campaigns_20260121`;
DROP TABLE IF EXISTS `backup_custom_campaign_products_20260121`;
DROP TABLE IF EXISTS `backup_custom_campaign_rules_20260121`;

CREATE TABLE IF NOT EXISTS `backup_custom_campaigns_20260121` SELECT * FROM `custom_campaigns`;
CREATE TABLE IF NOT EXISTS `backup_custom_campaign_products_20260121` SELECT * FROM `custom_campaign_products`;
CREATE TABLE IF NOT EXISTS `backup_custom_campaign_rules_20260121` SELECT * FROM `custom_campaign_rules`;

-- ADIM 3: Mevcut Tabloları Temizle
DROP TABLE IF EXISTS `custom_campaign_rules`;
DROP TABLE IF EXISTS `custom_campaign_products`;
DROP TABLE IF EXISTS `custom_campaigns`;
DROP TABLE IF EXISTS `custom_campaign_customers`;

-- ADIM 4: Tabloları Yeniden Oluştur

-- 4.1 Ana Kampanya Tablosu
CREATE TABLE `custom_campaigns` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Kampanya adı',
    `category_name` VARCHAR(100) NOT NULL COMMENT 'Kategori adı (POMPALAR, FİLTRELER, vb)',
    `customer_type` ENUM('ana_bayi', 'bayi', 'tum') DEFAULT 'ana_bayi',
    `min_quantity` INT DEFAULT 10 COMMENT 'Minimum toplam adet',
    `min_total_amount` DECIMAL(15,2) DEFAULT 10000.00 COMMENT 'Kademeli iskonto için min tutar (€)',
    `fallback_discount_cash` DECIMAL(5,2) DEFAULT 50.5 COMMENT 'Koşul sağlanmazsa peşin iskonto (%)',
    `fallback_discount_credit` DECIMAL(5,2) DEFAULT 45.0 COMMENT 'Koşul sağlanmazsa vadeli iskonto (%)',
    `active` TINYINT(1) DEFAULT 1,
    `priority` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (`category_name`),
    INDEX idx_active (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.2 Kampanya Ürünleri
CREATE TABLE `custom_campaign_products` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `campaign_id` INT NOT NULL,
    `product_code` VARCHAR(50) NOT NULL COMMENT 'Stok kodu',
    `product_name` VARCHAR(255) NULL COMMENT 'Ürün adı (opsiyonel)',
    `discount_rate` DECIMAL(8,5) NOT NULL COMMENT 'İskonto oranı (örn: 52.31788)',
    FOREIGN KEY (`campaign_id`) REFERENCES `custom_campaigns`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_campaign_product` (`campaign_id`, `product_code`),
    INDEX idx_product_code (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.3 Kademeli İskonto Kuralları
CREATE TABLE `custom_campaign_rules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `campaign_id` INT NOT NULL,
    `rule_type` ENUM('amount_based', 'payment_based', 'quantity_based') NOT NULL,
    `rule_name` VARCHAR(100) NOT NULL,
    `condition_value` DECIMAL(15,2) DEFAULT 0 COMMENT 'Koşul değeri (tutar veya miktar)',
    `discount_rate` DECIMAL(5,2) NOT NULL COMMENT 'Ek iskonto oranı (%)',
    `priority` INT DEFAULT 0,
    FOREIGN KEY (`campaign_id`) REFERENCES `custom_campaigns`(`id`) ON DELETE CASCADE,
    INDEX idx_campaign (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4.4 Ana Bayi Listesi
CREATE TABLE `custom_campaign_customers` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `customer_code` VARCHAR(50) NOT NULL COMMENT 'Cari Kodu (örn: 120.01.E04)',
    `customer_name` VARCHAR(255) NOT NULL COMMENT 'Müşteri Adı (örn: ERTEK)',
    `customer_type` ENUM('ana_bayi', 'bayi') DEFAULT 'ana_bayi',
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_customer_code` (`customer_code`),
    INDEX idx_type_active (`customer_type`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ADIM 5: Ana Bayileri Ekle
INSERT INTO `custom_campaign_customers` (`customer_code`, `customer_name`, `customer_type`, `active`) VALUES 
('120.01.E04', 'ERTEK', 'ana_bayi', 1);

-- ADIM 6: Kampanya Verilerini Ekle

-- 6.1 POMPALAR
INSERT INTO `custom_campaigns` (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) VALUES 
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

-- 6.2 FİLTRELER
INSERT INTO `custom_campaigns` (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) VALUES 
('FİLTRELER - Ana Bayi Özel Fiyat', 'FİLTRELER', 'ana_bayi', 10, 10000.00, 50.5, 45.0, 1, 90);
SET @campaign_filtreler = LAST_INSERT_ID();

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
(@campaign_filtreler, '021712', 49.8282),
(@campaign_filtreler, '021712S', 50.0000),
(@campaign_filtreler, '021713', 50.0000),
(@campaign_filtreler, '021713S', 50.0000),
(@campaign_filtreler, '021714', 50.0000),
(@campaign_filtreler, '021715S', 50.0000),
(@campaign_filtreler, '021715', 50.0000),
(@campaign_filtreler, '021311B', 49.7872),
(@campaign_filtreler, '021312B', 49.8039),
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

-- 6.3 ÇOK YOLLU VANA
INSERT INTO `custom_campaigns` (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) VALUES 
('ÇOK YOLLU VANA - Ana Bayi Özel Fiyat', 'ÇOK YOLLU VANA', 'ana_bayi', 100, 10000.00, 50.5, 45.0, 1, 80);
SET @campaign_vana = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_vana, '02400', 50.0000),
(@campaign_vana, '02213027', 54.2857);

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_vana, 'amount_based', 'Fiş Toplamı ≥ 10.000€', 10000.00, 5.00, 1),
(@campaign_vana, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- 6.4 FİLTRE MEDYA
INSERT INTO `custom_campaigns` (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) VALUES 
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

-- 6.5 MERDİVEN
INSERT INTO `custom_campaigns` (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) VALUES 
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

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_merdiven, 'quantity_based', 'Toplam Miktar ≥ 50 Adet', 50.00, 5.00, 1),
(@campaign_merdiven, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);

-- ADIM 7: Foreign Key Kontrollerini Tekrar Aç
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- KURULUM TAMAMLANDI
-- 5 Kampanya, 78 Ürün, 10 Kural Eklendi
-- ============================================
