ALTER TABLE `yonetici`
  ADD COLUMN `pref_auxil_code` text DEFAULT NULL,
  ADD COLUMN `pref_division` int(11) DEFAULT NULL,
  ADD COLUMN `pref_department` int(11) DEFAULT NULL,
  ADD COLUMN `pref_source_wh` int(11) DEFAULT NULL,
  ADD COLUMN `pref_factory` text DEFAULT NULL,
  ADD COLUMN `pref_salesmanref` int(11) DEFAULT NULL;
