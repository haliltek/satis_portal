CREATE TABLE `order_processes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teklif_id` int(11) NOT NULL,
  `s_arp_code` varchar(50) NOT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `offer_idx` (`teklif_id`),
  KEY `user_idx` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
