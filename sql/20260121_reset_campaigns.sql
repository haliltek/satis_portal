-- ============================================
-- KAMPANYA SİSTEMİ YENİDEN YAPILANDIRMA
-- Tarih: 2026-01-21
-- Amaç: Kategori bazlı kampanya sistemi
-- ============================================

-- ADIM 1: YEDEKLEME (Varsa mevcut verileri yedekle)
-- --------------------------------------------
DROP TABLE IF EXISTS `backup_custom_campaigns_20260121`;
DROP TABLE IF EXISTS `backup_custom_campaign_products_20260121`;
DROP TABLE IF EXISTS `backup_custom_campaign_rules_20260121`;

CREATE TABLE IF NOT EXISTS `backup_custom_campaigns_20260121` LIKE `custom_campaigns`;
INSERT INTO `backup_custom_campaigns_20260121` SELECT * FROM `custom_campaigns`;

CREATE TABLE IF NOT EXISTS `backup_custom_campaign_products_20260121` LIKE `custom_campaign_products`;
INSERT INTO `backup_custom_campaign_products_20260121` SELECT * FROM `custom_campaign_products`;

CREATE TABLE IF NOT EXISTS `backup_custom_campaign_rules_20260121` LIKE `custom_campaign_rules`;
INSERT INTO `backup_custom_campaign_rules_20260121` SELECT * FROM `custom_campaign_rules`;

-- ADIM 2: MEVCUT TABLOLARI TEMİZLE
-- --------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `custom_campaign_rules`;
TRUNCATE TABLE `custom_campaign_products`;
TRUNCATE TABLE `custom_campaigns`;
SET FOREIGN_KEY_CHECKS = 1;

-- ADIM 3: TABLO YAPILARINI GÜNCELLE (Gerekirse)
-- --------------------------------------------

-- custom_campaigns tablosunu düzenle (fallback kolonları ekle)
ALTER TABLE `custom_campaigns`
    MODIFY COLUMN `customer_type` ENUM('ana_bayi', 'bayi', 'tum', 'specific') DEFAULT 'ana_bayi',
    ADD COLUMN IF NOT EXISTS `category_name` VARCHAR(100) NOT NULL DEFAULT 'GENEL' COMMENT 'Kategori adı' AFTER `name`,
    ADD COLUMN IF NOT EXISTS `fallback_discount_cash` DECIMAL(5,2) DEFAULT 50.5 COMMENT 'Koşul sağlanmazsa peşin iskonto' AFTER `min_total_amount`,
    ADD COLUMN IF NOT EXISTS `fallback_discount_credit` DECIMAL(5,2) DEFAULT 45.0 COMMENT 'Koşul sağlanmazsa vadeli iskonto' AFTER `fallback_discount_cash`,
    DROP COLUMN IF EXISTS `fallback_discount`,
    DROP COLUMN IF EXISTS `customer_code`;

-- custom_campaign_products tablosunda discount_rate hassasiyetini artır
ALTER TABLE `custom_campaign_products`
    MODIFY COLUMN `discount_rate` DECIMAL(8,5) NOT NULL COMMENT 'İskonto oranı (örn: 52.31788)';

-- ADIM 4: ANA BAYİ LİSTESİ TABLOSU OLUŞTUR
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS `custom_campaign_customers` (
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

-- ADIM 5: ANA BAYİLERİ EKLE
-- --------------------------------------------
INSERT INTO `custom_campaign_customers` (`customer_code`, `customer_name`, `customer_type`, `active`) 
VALUES 
    ('120.01.E04', 'ERTEK', 'ana_bayi', 1)
ON DUPLICATE KEY UPDATE 
    `customer_name` = VALUES(`customer_name`),
    `active` = VALUES(`active`);

-- ============================================
-- KAMPANYA VERİLERİ BURAYA EKLENECEk
-- (Kullanıcıdan kategori bilgileri alındıktan sonra)
-- ============================================

-- Örnek: POMPALAR Kategorisi
/*
INSERT INTO `custom_campaigns` 
    (`name`, `category_name`, `customer_type`, `min_quantity`, `min_total_amount`, `fallback_discount_cash`, `fallback_discount_credit`, `active`, `priority`) 
VALUES 
    ('POMPALAR - Ana Bayi Özel Fiyat', 'POMPALAR', 'ana_bayi', 10, 10000.00, 50.5, 45.0, 1, 100);

SET @campaign_id = LAST_INSERT_ID();

INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_id, '0111STRM50M', 52.31788),
(@campaign_id, '0111STRM80M', 52.53165);
-- ... diğer ürünler

INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_id, 'amount_based', 'Fiş Toplamı ≥ 10.000€', 10000.00, 5.00, 1),
(@campaign_id, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);
*/
