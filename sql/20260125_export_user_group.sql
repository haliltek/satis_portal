-- Export Kullanıcı Grubu Oluşturma
-- Tarih: 2026-01-25
-- Amaç: Yurtdışı satış ekibi için özel kullanıcı grubu

-- 1. Yönetici tablosuna user_type için 'Export' değeri ekle
-- Not: user_type zaten VARCHAR olduğu için yeni değer ekleyebiliriz

-- 2. Örnek Export kullanıcı oluşturma (isteğe bağlı)
-- INSERT INTO yonetici (kullanici_adi, sifre, user_type, iskonto_max, satis_tipi) 
-- VALUES ('export_user', MD5('password'), 'Export', 60, 'Yurtdışı');

-- 3. Export kullanıcılarının varsayılan pazar tipi 'yurtdisi' olacak
-- Bu kod tarafında (fonk.php veya teklif-olustur.php) yapılacak

-- 4. Export kullanıcıları için özel yetkiler:
-- - Personel ile aynı iskonto limiti (60%)
-- - Sadece is_export = 1 olan müşterileri görebilir
-- - Varsayılan pazar tipi: Yurtdışı

-- Mevcut kullanıcı tiplerini görmek için:
-- SELECT DISTINCT user_type FROM yonetici;
