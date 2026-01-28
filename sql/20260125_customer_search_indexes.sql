-- Müşteri Arama Performans İyileştirme
-- Tarih: 2026-01-25
-- Amaç: teklif-olustur.php sayfasında müşteri aramasını hızlandırmak

-- 1. Firma adı araması için index (LIKE '%keyword%' için FULLTEXT daha iyi)
CREATE INDEX idx_sirket_adi ON sirket(s_adi(100));

-- 2. Cari kodu için index
CREATE INDEX idx_sirket_arp_code ON sirket(s_arp_code);

-- 3. is_export filtresi için index (zaten var mı kontrol et)
CREATE INDEX idx_sirket_is_export ON sirket(is_export);

-- 4. Composite index - en çok kullanılan sorgu için
-- (is_export + s_adi birlikte aranıyor)
CREATE INDEX idx_sirket_export_adi ON sirket(is_export, s_adi(100));

-- FULLTEXT index ekle (çok daha hızlı arama için)
-- Not: FULLTEXT sadece MyISAM veya InnoDB'de çalışır
ALTER TABLE sirket ADD FULLTEXT INDEX ft_sirket_adi (s_adi);

-- Mevcut indexleri görmek için:
-- SHOW INDEX FROM sirket;
