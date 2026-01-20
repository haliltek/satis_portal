-- Create guncellemeler (updates/changes) table
-- This table is used to track development updates and announcements for customers and staff

CREATE TABLE IF NOT EXISTS `guncellemeler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adi` varchar(255) NOT NULL COMMENT 'Update name/title',
  `aciklama` text NOT NULL COMMENT 'Update description/details',
  `tarih` varchar(50) NOT NULL COMMENT 'Update date',
  `durumu` varchar(50) NOT NULL COMMENT 'Target audience: Müşteri (Customer) or Personel (Staff)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
