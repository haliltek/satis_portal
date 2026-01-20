-- Sirket tablosuna ana_bayi kolonu ekle
ALTER TABLE sirket 
ADD COLUMN IF NOT EXISTS ana_bayi TINYINT(1) DEFAULT 0 COMMENT 'Ana bayi ise 1, değilse 0';

-- ERTEK'i ana bayi olarak işaretle
UPDATE sirket 
SET ana_bayi = 1 
WHERE s_arp_code = '120.01.E04';

-- Kontrol sorgusu
SELECT sirket_id, s_arp_code, s_unvan, ana_bayi 
FROM sirket 
WHERE s_arp_code = '120.01.E04';
