CREATE TABLE `auth_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `logicalref` int NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `definition` varchar(255) DEFAULT NULL,
  `firmnr` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

