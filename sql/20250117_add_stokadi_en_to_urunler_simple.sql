-- İngilizce ürün adı için stokadi_en alanını ekle
-- Backtick olmadan basit versiyon
ALTER TABLE urunler 
ADD COLUMN stokadi_en text DEFAULT NULL AFTER stokadi;

