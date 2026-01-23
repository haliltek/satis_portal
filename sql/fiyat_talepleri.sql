-- Fiyat Talepleri Tablosu
CREATE TABLE IF NOT EXISTS fiyat_talepleri (
    talep_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    urun_id INT(11) NOT NULL,
    stokkodu VARCHAR(100),
    stokadi VARCHAR(255),
    talep_eden_id INT(11) NOT NULL,
    talep_eden_adi VARCHAR(100),
    talep_notu TEXT,
    talep_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP,
    durum ENUM('beklemede', 'onaylandi', 'reddedildi') DEFAULT 'beklemede',
    yonetici_notu TEXT,
    cevaplayan_id INT(11),
    cevap_tarihi DATETIME,
    onerilen_fiyat DECIMAL(15,2),
    onerilen_doviz VARCHAR(10),
    INDEX idx_urun (urun_id),
    INDEX idx_talep_eden (talep_eden_id),
    INDEX idx_durum (durum),
    INDEX idx_tarih (talep_tarihi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
