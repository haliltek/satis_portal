-- Consolidated Database Updates with Safety Checks (Robust Version)
-- --------------------------------------------------------

-- Helper Procedure to Add Columns Safely
DELIMITER //
DROP PROCEDURE IF EXISTS `AddColumnIfNotExists` //
CREATE PROCEDURE `AddColumnIfNotExists` (
    IN tableName VARCHAR(255),
    IN colName VARCHAR(255),
    IN colDef TEXT
)
BEGIN
    DECLARE col_count INT;
    SELECT COUNT(*) INTO col_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = tableName
    AND column_name = colName;

    IF col_count = 0 THEN
        SET @s = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', colName, '` ', colDef);
        PREPARE stmt FROM @s;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- 1. Create Campaigns Table
CREATE TABLE IF NOT EXISTS `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  `discount_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_idx` (`product_id`),
  KEY `group_idx` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Create B2B Users Table
CREATE TABLE IF NOT EXISTS `b2b_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `cari_code` varchar(50) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `role` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create Order Processes Table
CREATE TABLE IF NOT EXISTS `order_processes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teklif_id` int(11) NOT NULL,
  `s_arp_code` varchar(50) NOT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `offer_idx` (`teklif_id`),
  KEY `user_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Create Auth Codes Table
CREATE TABLE IF NOT EXISTS `auth_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `logicalref` int NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `definition` varchar(255) DEFAULT NULL,
  `firmnr` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Extend Sirket Table
-- REMOVED 'AFTER' CLAUSES TO PREVENT ERRORS IF PREVIOUS COLUMNS ARE MISSING
CALL AddColumnIfNotExists('sirket', 's_adresi2', 'text NULL');
CALL AddColumnIfNotExists('sirket', 's_country_code', 'varchar(5) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_postal_code', 'varchar(20) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_fax', 'varchar(50) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_web', 'varchar(255) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'yetkili2', 'text NULL');
CALL AddColumnIfNotExists('sirket', 'yetkili3', 'text NULL');
CALL AddColumnIfNotExists('sirket', 'mail2', 'text NULL');
CALL AddColumnIfNotExists('sirket', 'mail3', 'text NULL');
CALL AddColumnIfNotExists('sirket', 'currency', 'int DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'credit_limit', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'risk_limit', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'blocked', 'tinyint(1) DEFAULT 0');
CALL AddColumnIfNotExists('sirket', 'record_status', 'tinyint(1) DEFAULT 0');
CALL AddColumnIfNotExists('sirket', 's_auxil_code', 'varchar(100) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_auth_code', 'varchar(100) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_country', 'varchar(50) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_tax_office_code', 'varchar(20) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_corresp_lang', 'varchar(10) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_gl_code', 'varchar(100) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 's_subscriber_ext', 'varchar(100) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'cl_ord_freq', 'int DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'logoid', 'varchar(50) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'invoice_prnt_cnt', 'int DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'accept_einv', 'tinyint(1) DEFAULT 0');
CALL AddColumnIfNotExists('sirket', 'profile_id', 'int DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'post_label', 'varchar(255) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'sender_label', 'varchar(255) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'factory_div_nr', 'int DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'create_wh_fiche', 'tinyint(1) DEFAULT 0');
CALL AddColumnIfNotExists('sirket', 'disp_print_cnt', 'int DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'ord_print_cnt', 'int DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'guid', 'varchar(36) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'riskfact_chq', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'riskfact_promnt', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('sirket', 'low_level_codes1', 'varchar(100) DEFAULT NULL');

-- 6. Add 'ana_bayi' to Sirket
CALL AddColumnIfNotExists('sirket', 'ana_bayi', 'TINYINT(1) DEFAULT 0 COMMENT "Ana bayi ise 1, değilse 0"');
UPDATE sirket SET ana_bayi = 1 WHERE s_arp_code = '120.01.E04';

-- 7. Add 'sozlesme_id' to ogteklif2
CALL AddColumnIfNotExists('ogteklif2', 'sozlesme_id', 'INT NOT NULL DEFAULT 5');

-- 8. Add 'doviz_goster' to ogteklif2
CALL AddColumnIfNotExists('ogteklif2', 'doviz_goster', 'VARCHAR(10) DEFAULT "TUMU"');
UPDATE `ogteklif2` SET `doviz_goster` = 'TUMU' WHERE `doviz_goster` IS NULL;

-- 9. Add Special Offer Fields to ogteklif2
CALL AddColumnIfNotExists('ogteklif2', 'is_special_offer', 'TINYINT(1) DEFAULT 0 COMMENT "0: Standard, 1: Special Offer"');
CALL AddColumnIfNotExists('ogteklif2', 'approval_status', 'ENUM("none", "pending", "approved", "rejected") DEFAULT "none" COMMENT "Status of special offer approval"');
CALL AddColumnIfNotExists('ogteklif2', 'approved_by', 'INT(11) DEFAULT NULL COMMENT "Admin ID who approved/rejected"');
CALL AddColumnIfNotExists('ogteklif2', 'approved_at', 'DATETIME DEFAULT NULL COMMENT "Timestamp of approval action"');

-- 10. Add Header Preferences to Yonetici
CALL AddColumnIfNotExists('yonetici', 'pref_auxil_code', 'text DEFAULT NULL');
CALL AddColumnIfNotExists('yonetici', 'pref_division', 'int(11) DEFAULT NULL');
CALL AddColumnIfNotExists('yonetici', 'pref_department', 'int(11) DEFAULT NULL');
CALL AddColumnIfNotExists('yonetici', 'pref_source_wh', 'int(11) DEFAULT NULL');
CALL AddColumnIfNotExists('yonetici', 'pref_factory', 'text DEFAULT NULL');
CALL AddColumnIfNotExists('yonetici', 'pref_salesmanref', 'int(11) DEFAULT NULL');

-- 11. Add Satis Tipi to Yonetici
CALL AddColumnIfNotExists('yonetici', 'satis_tipi', 'ENUM("Yurt İçi","Yurt Dışı") NOT NULL DEFAULT "Yurt İçi"');

-- 12. Add 'stokadi_en' to Urunler
CALL AddColumnIfNotExists('urunler', 'stokadi_en', 'text CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL');
-- Add other missing columns detected during debugging
CALL AddColumnIfNotExists('urunler', 'logo_active', 'TINYINT(1) DEFAULT 0 COMMENT "0: Active, 1: Passive"');
CALL AddColumnIfNotExists('urunler', 'gemas2026logical', 'INT DEFAULT 0');
CALL AddColumnIfNotExists('urunler', 'gempa2026logical', 'INT DEFAULT 0');
CALL AddColumnIfNotExists('urunler', 'mysql_guncelleme', 'DATETIME DEFAULT NULL');
CALL AddColumnIfNotExists('urunler', 'export_mysql_guncelleme', 'DATETIME DEFAULT NULL');
CALL AddColumnIfNotExists('urunler', 'logicalref', 'INT DEFAULT 0');

-- 13. Add Sales Order Currency fields
CALL AddColumnIfNotExists('ogteklif2', 'curr_transactin', 'decimal(10,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'rc_rate', 'decimal(10,4) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'rc_net', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'total_discounted', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'total_vat', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'total_gross', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'total_net_header', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'exchinfo_total_discounted', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'exchinfo_total_vat', 'decimal(12,2) DEFAULT NULL');
CALL AddColumnIfNotExists('ogteklif2', 'exchinfo_gross_total', 'decimal(12,2) DEFAULT NULL');

-- 14. Teklif Decisions
CREATE TABLE IF NOT EXISTS teklif_decisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) UNIQUE,
    teklif_id VARCHAR(100),
    manager_phone VARCHAR(20),
    decision_type ENUM('ONAY', 'RED') NOT NULL,
    decision_status VARCHAR(50) DEFAULT 'PROCESSED',
    decision_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_manager_phone (manager_phone),
    INDEX idx_teklif_id (teklif_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 15. Urun Fiyat Log Table (Missing)
CREATE TABLE IF NOT EXISTS `urun_fiyat_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `stokkodu` varchar(50) DEFAULT NULL,
  `stokadi` varchar(255) DEFAULT NULL,
  `guncelleyen` int(11) DEFAULT NULL,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `onceki_fiyat` decimal(15,2) DEFAULT NULL,
  `yeni_fiyat` decimal(15,2) DEFAULT NULL,
  `fiyat_tipi` varchar(20) DEFAULT 'domestic',
  `reverted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`log_id`),
  KEY `idx_stokkodu` (`stokkodu`),
  KEY `idx_guncelleyen` (`guncelleyen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Clean up
DROP PROCEDURE `AddColumnIfNotExists`;
