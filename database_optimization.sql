-- Database Performance Optimization
-- Indexes for 20+ concurrent users

-- ============================================
-- URUNLER (Products) Table Indexes
-- ============================================

-- Stok kodu ile sık arama yapılıyor
CREATE INDEX IF NOT EXISTS idx_urunler_stokkodu ON urunler(stokkodu);

-- Aktif ürün filtreleme
CREATE INDEX IF NOT EXISTS idx_urunler_logo_active ON urunler(logo_active);

-- Logical reference ile arama
CREATE INDEX IF NOT EXISTS idx_urunler_logicalref ON urunler(logicalref);

-- Marka bazlı filtreleme (eğer kullanılıyorsa)
-- CREATE INDEX IF NOT EXISTS idx_urunler_marka ON urunler(marka);

-- Composite index: Aktif ürünler + stok kodu
CREATE INDEX IF NOT EXISTS idx_urunler_active_kod ON urunler(logo_active, stokkodu);

-- ============================================
-- OGTEKLIF2 (Quotes) Table Indexes
-- ============================================

-- Müşteri bazlı teklif sorgulama
CREATE INDEX IF NOT EXISTS idx_ogteklif2_musteriid ON ogteklif2(musteriid);

-- Teklif tarihi ile sıralama/filtreleme
CREATE INDEX IF NOT EXISTS idx_ogteklif2_tarih ON ogteklif2(tekliftarihi);

-- Teklif durumu (aktif/pasif)
CREATE INDEX IF NOT EXISTS idx_ogteklif2_durum ON ogteklif2(durum);

-- Logo aktarım durumu
CREATE INDEX IF NOT EXISTS idx_ogteklif2_logo_status ON ogteklif2(logo_aktarildi);

-- Composite: Müşteri + tarih
CREATE INDEX IF NOT EXISTS idx_ogteklif2_musteri_tarih ON ogteklif2(musteriid, tekliftarihi);

-- ============================================
-- OGTEKLIFURUN2 (Quote Items) Table Indexes
-- ============================================

-- Teklif ID ile ürünleri getirme (en sık kullanılan)
CREATE INDEX IF NOT EXISTS idx_ogteklifurun2_teklifid ON ogteklifurun2(teklifid);

-- Ürün kodu ile arama
CREATE INDEX IF NOT EXISTS idx_ogteklifurun2_stokkodu ON ogteklifurun2(stokkodu);

-- Logo internal reference
CREATE INDEX IF NOT EXISTS idx_ogteklifurun2_internal_ref ON ogteklifurun2(internal_reference);

-- ============================================
-- B2B_USERS (Users) Table Indexes
-- ============================================

-- Username ile login
CREATE INDEX IF NOT EXISTS idx_b2b_users_username ON b2b_users(username);

-- Email ile arama
CREATE INDEX IF NOT EXISTS idx_b2b_users_email ON b2b_users(email);

-- Aktif kullanıcı filtreleme
CREATE INDEX IF NOT EXISTS idx_b2b_users_active ON b2b_users(active);

-- ============================================
-- SIRKETLER (Companies) Table Indexes
-- ============================================

-- ARP code ile arama (Logo entegrasyonu)
CREATE INDEX IF NOT EXISTS idx_sirketler_arp_code ON sirketler(s_arp_code);

-- Internal reference
CREATE INDEX IF NOT EXISTS idx_sirketler_internal_ref ON sirketler(internal_reference);

-- Şirket adı ile arama
CREATE INDEX IF NOT EXISTS idx_sirketler_unvan ON sirketler(s_unvan);

-- ============================================
-- KAMPANYALAR (Campaigns) Table Indexes
-- ============================================

-- Kampanya tarihleri ile filtreleme
CREATE INDEX IF NOT EXISTS idx_kampanyalar_baslangic ON kampanyalar(baslangic_tarihi);
CREATE INDEX IF NOT EXISTS idx_kampanyalar_bitis ON kampanyalar(bitis_tarihi);

-- Aktif kampanya kontrolü
CREATE INDEX IF NOT EXISTS idx_kampanyalar_aktif ON kampanyalar(aktif);

-- Composite: Aktif + tarih aralığı
CREATE INDEX IF NOT EXISTS idx_kampanyalar_aktif_tarih ON kampanyalar(aktif, baslangic_tarihi, bitis_tarihi);

-- ============================================
-- YONETICI (Admin Users) Table Indexes
-- ============================================

-- Yönetici ID ile arama
CREATE INDEX IF NOT EXISTS idx_yonetici_id ON yonetici(yonetici_id);

-- Kullanıcı adı ile login
CREATE INDEX IF NOT EXISTS idx_yonetici_kadi ON yonetici(yonetici_kadi);

-- Tip bazlı filtreleme
CREATE INDEX IF NOT EXISTS idx_yonetici_tur ON yonetici(tur);

-- ============================================
-- Query Optimization Settings
-- ============================================

-- InnoDB buffer pool size artırımı (my.cnf veya docker-compose.yml'de)
-- innodb_buffer_pool_size = 512M  (toplam RAM'in %50-70'i)

-- Query cache (MySQL 5.7 ve öncesi için)
-- query_cache_type = 1
-- query_cache_size = 64M

-- Connection pool settings
-- max_connections = 200
-- wait_timeout = 28800

-- ============================================
-- Analyze Tables (İstatistikleri güncelle)
-- ============================================

ANALYZE TABLE urunler;
ANALYZE TABLE ogteklif2;
ANALYZE TABLE ogteklifurun2;
ANALYZE TABLE b2b_users;
ANALYZE TABLE sirketler;
ANALYZE TABLE kampanyalar;
ANALYZE TABLE yonetici;

-- ============================================
-- Optimize Tables (Defragmentation)
-- ============================================

OPTIMIZE TABLE urunler;
OPTIMIZE TABLE ogteklif2;
OPTIMIZE TABLE ogteklifurun2;

-- ============================================
-- Show Index Usage
-- ============================================

-- Index kullanımını kontrol et
-- SHOW INDEX FROM urunler;
-- SHOW INDEX FROM ogteklif2;
-- SHOW INDEX FROM ogteklifurun2;
