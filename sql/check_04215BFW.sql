-- 04215BFW ürününü kampanya_ozel_fiyatlar tablosunda kontrol et

-- 1. Ürün var mı?
SELECT * FROM kampanya_ozel_fiyatlar 
WHERE stok_kodu = '04215BFW';

-- 2. Benzer kodlar var mı? (boşluk, küçük harf vb.)
SELECT * FROM kampanya_ozel_fiyatlar 
WHERE stok_kodu LIKE '%04215BFW%' 
   OR stok_kodu LIKE '%04215bfw%'
   OR REPLACE(stok_kodu, ' ', '') = '04215BFW';

-- 3. Tüm 04215 ile başlayan kodlar
SELECT * FROM kampanya_ozel_fiyatlar 
WHERE stok_kodu LIKE '04215%';

-- 4. Eğer yoksa, urunler tablosunda var mı?
SELECT stokkodu, stokadi, fiyat 
FROM urunler 
WHERE stokkodu = '04215BFW';
