-- Urunler tablosuna web/app fiyatı ve maliyet sütunlarını ekle
-- Tarih: 2026-01-22

ALTER TABLE `urunler` 
ADD COLUMN IF NOT EXISTS `web_fiyat` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Web/App Fiyatı',
ADD COLUMN IF NOT EXISTS `maliyet` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Maliyet Fiyatı';
