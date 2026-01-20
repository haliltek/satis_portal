-- Local Teklifleri Temizleme Scripti
-- GÜNCELLENDİ: TRUNCATE yerine DELETE kullanılarak FK hataları önlendi.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabloları Temizle
DELETE FROM `ogteklifurun2`;
DELETE FROM `durum_gecisleri`;
DELETE FROM `order_processes`;
DELETE FROM `teklif_decisions`;
DELETE FROM `ogteklif2`;

-- 2. Sayaçları (ID) Sıfırla
ALTER TABLE `ogteklifurun2` AUTO_INCREMENT = 1;
ALTER TABLE `durum_gecisleri` AUTO_INCREMENT = 1;
ALTER TABLE `order_processes` AUTO_INCREMENT = 1;
ALTER TABLE `teklif_decisions` AUTO_INCREMENT = 1;
ALTER TABLE `ogteklif2` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
