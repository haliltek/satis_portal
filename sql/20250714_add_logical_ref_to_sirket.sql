-- Deprecated: use internal_reference column instead
ALTER TABLE `sirket`
  ADD COLUMN `logical_ref` int DEFAULT NULL AFTER `s_arp_code`;
