ALTER TABLE `yonetici`
  ADD COLUMN `satis_tipi` ENUM('Yurt İçi','Yurt Dışı') NOT NULL DEFAULT 'Yurt İçi' AFTER `tur`;
