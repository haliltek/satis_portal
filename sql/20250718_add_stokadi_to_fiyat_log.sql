ALTER TABLE urun_fiyat_log ADD COLUMN stokadi VARCHAR(255) AFTER stokkodu;
UPDATE urun_fiyat_log l
  JOIN urunler u ON u.stokkodu = l.stokkodu
  SET l.stokadi = u.stokadi
  WHERE l.stokadi IS NULL OR l.stokadi = '';
