-- POMPALAR (17 ürün) - Kampanya 1'e ekle
-- Min: 10 adet, Ek %5 için: 50+ adet (miktar bazlı)

INSERT INTO custom_campaign_products (campaign_id, product_code, discount_rate) VALUES
(1, '0111STRM50M', 52.3178),
(1, '0111STRM80M', 52.5316),
(1, '0111STRM100M', 52.5167),
(1, '0111STRM150M', 48.9675),
(1, '0111STRN50M', 53.0303),
(1, '0111STRN75M', 52.9577),
(1, '0111STRN75T', 53.1055),
(1, '0111STRN100M', 52.8926),
(1, '0111STRN100T', 53.0259),
(1, '0111STRN150M', 53.0805),
(1, '0111STRN150T', 53.1172),
(1, '0111STRN200M', 52.9816),
(1, '0111STRN200T', 53.1100),
(1, '0111STRN300M', 52.9166),
(1, '0111STRN300T', 52.9017),
(1, '0111STRN350M', 53.0158),
(1, '0111STRN350T', 52.9411);

-- Yeni kural ekle: 50+ adet için %5 ek iskonto (sadece pompalar için)
-- NOT: Bu kural quantity_based olacak
INSERT INTO custom_campaign_rules (campaign_id, rule_type, rule_name, condition_value, discount_rate, priority) 
VALUES 
(1, 'quantity_based', 'Pompa Miktarı ≥ 50 Adet', 50.00, 5.00, 3);
