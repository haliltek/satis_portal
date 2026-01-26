-- Kampanya İsimlerindeki Karakter Bozukluklarını Düzelt
-- Tarih: 2026-01-25
-- Amaç: Veritabanındaki bozuk Türkçe karakterleri düzeltmek

USE b2bgemascom_teklif;

-- 1. ŞOK YOLLU VANA kampanyası
UPDATE custom_campaigns 
SET category_name = 'ŞOK YOLLU VANA - Ana Bayi Özel Fiyat'
WHERE category_name LIKE '%OK YOLLU VANA%' 
   OR category_name LIKE '%�OK YOLLU%';

-- 2. Diğer olası bozuk karakterler (� → Ş)
UPDATE custom_campaigns 
SET category_name = REPLACE(category_name, '�', 'Ş')
WHERE category_name LIKE '%�%';

-- 3. Tüm kampanya isimlerini kontrol et
SELECT id, category_name 
FROM custom_campaigns 
ORDER BY id;
