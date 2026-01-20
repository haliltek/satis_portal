-- İngilizce ürün adı için stokadi_en alanını ekle
ALTER TABLE `urunler` 
ADD COLUMN `stokadi_en` text CHARACTER SET utf8mb3 COLLATE utf8mb3_turkish_ci DEFAULT NULL AFTER `stokadi`;

