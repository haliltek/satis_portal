ALTER TABLE ogteklif2
  ADD COLUMN curr_transactin decimal(10,2) DEFAULT NULL AFTER salesmanref,
  ADD COLUMN rc_rate decimal(10,4) DEFAULT NULL AFTER trading_grp,
  ADD COLUMN rc_net decimal(12,2) DEFAULT NULL AFTER rc_rate,
  ADD COLUMN total_discounted decimal(12,2) DEFAULT NULL AFTER rc_net,
  ADD COLUMN total_vat decimal(12,2) DEFAULT NULL AFTER total_discounted,
  ADD COLUMN total_gross decimal(12,2) DEFAULT NULL AFTER total_vat,
  ADD COLUMN total_net_header decimal(12,2) DEFAULT NULL AFTER total_gross,
  ADD COLUMN exchinfo_total_discounted decimal(12,2) DEFAULT NULL AFTER total_net_header,
  ADD COLUMN exchinfo_total_vat decimal(12,2) DEFAULT NULL AFTER exchinfo_total_discounted,
  ADD COLUMN exchinfo_gross_total decimal(12,2) DEFAULT NULL AFTER exchinfo_total_vat;
