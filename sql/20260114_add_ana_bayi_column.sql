-- Ana Bayi kolonu ekle
ALTER TABLE `ogteklif2` 
ADD COLUMN `ana_bayi` TINYINT(1) DEFAULT 0 COMMENT '1=Ana Bayi, 0=Normal' AFTER `cari_kodu`;

-- Ertek'i ana bayi olarak işaretle
UPDATE `ogteklif2` SET `ana_bayi` = 1 WHERE `cari_kodu` = '120.01.E04';
