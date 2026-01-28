-- Özel Fiyat Çalışmaları Veritabanı Tabloları

-- Tablo 1: Ana çalışma bilgileri
CREATE TABLE IF NOT EXISTS `ozel_fiyat_calismalari` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sirket_id` INT NOT NULL,
  `cari_kod` VARCHAR(50) NOT NULL,
  `baslik` VARCHAR(255) NOT NULL,
  `aciklama` TEXT,
  `aktif` TINYINT(1) DEFAULT 1,
  `olusturan_yonetici_id` INT,
  `olusturma_tarihi` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_sirket` (`sirket_id`),
  INDEX `idx_cari` (`cari_kod`),
  INDEX `idx_aktif` (`aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablo 2: Çalışmalara ait ürün fiyatları
CREATE TABLE IF NOT EXISTS `ozel_fiyat_urunler` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `calisma_id` INT NOT NULL,
  `stok_kodu` VARCHAR(100) NOT NULL,
  `urun_adi` VARCHAR(255),
  `birim` VARCHAR(20),
  `liste_fiyati` DECIMAL(18,4),
  `ozel_fiyat` DECIMAL(18,4) NOT NULL,
  `doviz` VARCHAR(10) DEFAULT 'EUR',
  `iskonto_orani` DECIMAL(5,2),
  `notlar` TEXT,
  `olusturma_tarihi` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`calisma_id`) REFERENCES `ozel_fiyat_calismalari`(`id`) ON DELETE CASCADE,
  INDEX `idx_calisma` (`calisma_id`),
  INDEX `idx_stok` (`stok_kodu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
