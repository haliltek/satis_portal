-- Döviz gösterimi seçeneği ekle
ALTER TABLE `ogteklif2` ADD COLUMN `doviz_goster` VARCHAR(10) DEFAULT 'TUMU' AFTER `sozlesme_id`;

-- Mevcut kayıtlar için varsayılan değer
UPDATE `ogteklif2` SET `doviz_goster` = 'TUMU' WHERE `doviz_goster` IS NULL;

