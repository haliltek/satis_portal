-- Manuel Kampanya Sistemi - Veritabanı Tabloları
-- Tarih: 2026-01-14

-- 1. Ana Kampanya Tablosu
CREATE TABLE IF NOT EXISTS `custom_campaigns` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Kampanya adı',
    `description` TEXT COMMENT 'Açıklama',
    `customer_type` ENUM('ana_bayi', 'bayi', 'tum', 'specific') DEFAULT 'tum' COMMENT 'Hedef müşteri tipi',
    `customer_code` VARCHAR(50) NULL COMMENT 'Belirli bir cari kodu (örn: 120.01.E04)',
    `min_quantity` INT DEFAULT 0 COMMENT 'Minimum toplam adet',
    `min_total_amount` DECIMAL(15,2) DEFAULT 0 COMMENT 'Minimum fiş tutarı (€)',
    `fallback_discount` DECIMAL(5,2) DEFAULT 0 COMMENT 'Koşul sağlanmazsa uygulanacak iskonto (%)',
    `active` TINYINT(1) DEFAULT 1 COMMENT '1=Aktif, 0=Pasif',
    `priority` INT DEFAULT 0 COMMENT 'Öncelik (yüksek önce uygulanır)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (`active`),
    INDEX idx_customer (`customer_type`, `customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Kampanya Ürünleri Tablosu
CREATE TABLE IF NOT EXISTS `custom_campaign_products` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `campaign_id` INT NOT NULL,
    `product_code` VARCHAR(50) NOT NULL COMMENT 'Stok kodu',
    `product_name` VARCHAR(255) NULL COMMENT 'Ürün adı (opsiyonel)',
    `campaign_price` DECIMAL(15,2) NULL COMMENT 'Kampanya fiyatı (€)',
    `discount_rate` DECIMAL(5,2) NOT NULL COMMENT 'Ürüne özel iskonto oranı (%)',
    FOREIGN KEY (`campaign_id`) REFERENCES `custom_campaigns`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_campaign_product` (`campaign_id`, `product_code`),
    INDEX idx_product_code (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Kademeli İskonto Kuralları Tablosu
CREATE TABLE IF NOT EXISTS `custom_campaign_rules` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `campaign_id` INT NOT NULL,
    `rule_type` ENUM('amount_based', 'payment_based', 'quantity_based') NOT NULL COMMENT 'Kural tipi',
    `rule_name` VARCHAR(100) NOT NULL COMMENT 'Kural adı',
    `condition_value` DECIMAL(15,2) NOT NULL COMMENT 'Koşul değeri (örn: 10000€ veya 10 adet)',
    `discount_rate` DECIMAL(5,2) NOT NULL COMMENT 'Ek iskonto oranı (%)',
    `priority` INT DEFAULT 0 COMMENT 'Kural önceliği',
    FOREIGN KEY (`campaign_id`) REFERENCES `custom_campaigns`(`id`) ON DELETE CASCADE,
    INDEX idx_campaign (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo veri ekle (Ertek Ana Bayi Kampanyası)
INSERT INTO `custom_campaigns` 
    (`name`, `description`, `customer_type`, `customer_code`, `min_quantity`, `min_total_amount`, `fallback_discount`, `active`, `priority`) 
VALUES 
    ('Ertek Ana Bayi Kampanyası', 'Ana bayi için özel ürün kampanyası - 10+ adet alımda kademeli iskonto', 'specific', '120.01.E04', 10, 10000.00, 45.00, 1, 100);

SET @campaign_id = LAST_INSERT_ID();

-- Kampanya ürünlerini ekle (resimdekiler)
INSERT INTO `custom_campaign_products` (`campaign_id`, `product_code`, `discount_rate`) VALUES
(@campaign_id, '0211211S', 52.9412),
(@campaign_id, '0211212S', 53.0612),
(@campaign_id, '021113T', 55.0201),
(@campaign_id, '021113Z', 55.1839),
(@campaign_id, '021113', 56.1404),
(@campaign_id, '021911T', 51.6750),
(@campaign_id, '021912', 52.0702),
(@campaign_id, '021913', 51.8657),
(@campaign_id, '021921', 51.6248),
(@campaign_id, '021923', 52.0661),
(@campaign_id, '021813', 51.7241),
(@campaign_id, '021814', 51.9481),
(@campaign_id, '021815', 51.8519);

-- Kademeli iskonto kurallarını ekle
INSERT INTO `custom_campaign_rules` (`campaign_id`, `rule_type`, `rule_name`, `condition_value`, `discount_rate`, `priority`) VALUES
(@campaign_id, 'amount_based', 'Fiş Toplamı ≥ 10.000€', 10000.00, 5.00, 1),
(@campaign_id, 'payment_based', 'Peşin Ödeme', 0.00, 10.00, 2);
