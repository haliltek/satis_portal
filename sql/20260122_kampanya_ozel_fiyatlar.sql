-- Kampanya Özel Fiyatlar Tablosu
-- Tarih: 2026-01-22

CREATE TABLE IF NOT EXISTS `kampanya_ozel_fiyatlar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `stok_kodu` VARCHAR(50) UNIQUE NOT NULL,
  `stok_adi` VARCHAR(255),
  `yurtici_fiyat` DECIMAL(10,2) DEFAULT 0.00,
  `ihracat_fiyat` DECIMAL(10,2) DEFAULT 0.00,
  `ozel_fiyat` DECIMAL(10,2) NOT NULL,
  `kategori` VARCHAR(100),
  `logicalref` INT DEFAULT NULL,
  `gempa_logicalref` INT DEFAULT NULL,
  `gemas_logicalref` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_stok_kodu` (`stok_kodu`),
  INDEX `idx_kategori` (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
