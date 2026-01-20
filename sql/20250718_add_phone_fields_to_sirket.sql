ALTER TABLE `sirket`
  ADD COLUMN `s_tel1_code` varchar(10) DEFAULT NULL AFTER `s_telefonu`,
  ADD COLUMN `s_telefonu2` varchar(50) DEFAULT NULL AFTER `s_tel1_code`,
  ADD COLUMN `s_tel2_code` varchar(10) DEFAULT NULL AFTER `s_telefonu2`;
