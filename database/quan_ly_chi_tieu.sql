-- Expense Manager database schema
-- Project: QUAN_LY_CHI_TIEU

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `reconciliation`;
DROP TABLE IF EXISTS `logintoken`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `transaction`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `password` varchar(100) NOT NULL,
  `activeToken` varchar(100) DEFAULT NULL,
  `forgotToken` varchar(100) DEFAULT NULL,
  `status` int DEFAULT 0,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `failed_attempts` int NOT NULL DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `phone_UNIQUE` (`phone`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `icon` varchar(10) DEFAULT '📦',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `category` (`id`, `name`, `icon`) VALUES
(1, 'Ăn uống', '🍔'),
(2, 'Giao thông', '🚗'),
(3, 'Giải trí', '🎮'),
(4, 'Mua sắm', '🛍️'),
(5, 'Sức khỏe', '💊'),
(6, 'Giáo dục', '📚'),
(7, 'Điện nước', '⚡'),
(8, 'Khác', '📝');

CREATE TABLE `transaction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `price` float NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `evidence_image` varchar(255) DEFAULT NULL,
  `evidence_reference` varchar(100) DEFAULT NULL,
  `evidence_text` text,
  `source_type` varchar(30) NOT NULL DEFAULT 'manual',
  PRIMARY KEY (`id`),
  KEY `transaction_user_idx` (`user_id`),
  KEY `transaction_category_idx` (`category_id`),
  KEY `transaction_user_type_archived_idx` (`user_id`, `type`, `is_archived`),
  KEY `transaction_user_archived_date_idx` (`user_id`, `is_archived`, `transaction_date`),
  KEY `transaction_user_cat_date_idx` (`user_id`, `category_id`, `type`, `is_archived`, `transaction_date`),
  KEY `transaction_created_at_idx` (`create_at`),
  CONSTRAINT `transaction_category_fk` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `transaction_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `budget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `amount` float NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_unique` (`user_id`, `category_id`, `month`, `year`),
  KEY `budget_user_idx` (`user_id`),
  KEY `budget_category_idx` (`category_id`),
  CONSTRAINT `budget_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `budget_category_fk` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `monthly_budget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `amount` float NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mb_unique` (`user_id`, `month`, `year`),
  KEY `mb_user_idx` (`user_id`),
  CONSTRAINT `mb_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `reconciliation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reconciliation_date` date NOT NULL,
  `actual_balance` float NOT NULL,
  `system_balance` float NOT NULL,
  `difference_amount` float NOT NULL DEFAULT 0,
  `adjustment_transaction_id` int DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reconciliation_user_idx` (`user_id`),
  KEY `reconciliation_adjustment_idx` (`adjustment_transaction_id`),
  CONSTRAINT `reconciliation_adjustment_fk` FOREIGN KEY (`adjustment_transaction_id`) REFERENCES `transaction` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `reconciliation_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `logintoken` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loginToken` varchar(100) NOT NULL,
  `user_id` int NOT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loginToken_UNIQUE` (`loginToken`),
  KEY `logintoken_user_idx` (`user_id`),
  CONSTRAINT `logintoken_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `monthly_budget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `amount` float NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mb_unique` (`user_id`, `month`, `year`),
  KEY `mb_user_idx` (`user_id`),
  CONSTRAINT `mb_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `budget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `amount` float NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `budget_unique` (`user_id`, `category_id`, `month`, `year`),
  KEY `budget_user_idx` (`user_id`),
  KEY `budget_category_idx` (`category_id`),
  CONSTRAINT `budget_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `budget_category_fk` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
