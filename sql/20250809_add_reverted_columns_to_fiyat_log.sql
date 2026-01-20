ALTER TABLE urun_fiyat_log
  ADD COLUMN reverted TINYINT(1) NOT NULL DEFAULT 0 AFTER guncelleme_tarihi,
  ADD COLUMN reverted_by INT NULL AFTER reverted,
  ADD COLUMN reverted_at DATETIME NULL AFTER reverted_by;
