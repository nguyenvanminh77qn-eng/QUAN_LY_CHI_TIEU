-- =============================================
-- QUAN LY CHI TIEU - Complete Database Schema
-- Merged from: quan_ly_chi_tieu.sql + all migrations
-- (wallet, pending_delete, pending_edit,
--  pending_edit_v2, category_limit, feedback,
--  chat_realtime)
-- Synced with actual DB dump 2026-06-16
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `wallet_transfer`;
DROP TABLE IF EXISTS `category_limit`;
DROP TABLE IF EXISTS `feedbacks`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `conversations`;
DROP TABLE IF EXISTS `reconciliation`;
DROP TABLE IF EXISTS `logintoken`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `transaction`;
DROP TABLE IF EXISTS `wallet`;
DROP TABLE IF EXISTS `budget`;
DROP TABLE IF EXISTS `monthly_budget`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `user`;

-- =============================================
-- 1. USER
-- =============================================
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `password` varchar(100) NOT NULL,
  `activeToken` varchar(100) DEFAULT NULL,
  `active_expires` datetime DEFAULT NULL,
  `forgotToken` varchar(100) DEFAULT NULL,
  `forgot_expires` datetime DEFAULT NULL,
  `status` int DEFAULT '0',
  `savings_balance` decimal(15,0) DEFAULT '0',
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `failed_attempts` int DEFAULT '0',
  `lockout_until` datetime DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `phone_UNIQUE` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================
-- 2. WALLET
-- =============================================
CREATE TABLE `wallet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(10) DEFAULT '💰',
  `type` enum('daily','ewallet','target') NOT NULL DEFAULT 'daily',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallet_user_idx` (`user_id`),
  CONSTRAINT `wallet_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================
-- 2b. WALLET_TRANSFER
-- =============================================
CREATE TABLE IF NOT EXISTS `wallet_transfer` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `from_wallet_id` INT NOT NULL,
  `to_wallet_id` INT NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `description` VARCHAR(200) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_wt_user` (`user_id`),
  INDEX `idx_wt_from` (`from_wallet_id`),
  INDEX `idx_wt_to` (`to_wallet_id`),
  CONSTRAINT `fk_wt_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wt_from` FOREIGN KEY (`from_wallet_id`) REFERENCES `wallet`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wt_to` FOREIGN KEY (`to_wallet_id`) REFERENCES `wallet`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. CATEGORY
-- =============================================
CREATE TABLE `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `icon` varchar(10) DEFAULT '📦',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `category` (`id`, `name`, `icon`) VALUES
(1, 'Ăn uống', '🍔'),
(2, 'Giải trí', '🎮'),
(3, 'Du lịch', '✈️'),
(4, 'Di chuyển', '🚗'),
(5, 'Mua sắm', '🛍️'),
(6, 'Hoc hanh', '📚'),
(7, 'đánh bài', '🃏'),
(8, 'sức khỏe', '💊'),
(9, 'Khác', '📝');

-- =============================================
-- 4. TRANSACTION
--     Includes: wallet_id, status (pending_delete),
--     sync_status, pending_amount, pending_type,
--     pending_wallets_json (pending_edit), batch_id
-- =============================================
CREATE TABLE `transaction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `wallet_id` int DEFAULT NULL,
  `category_id` int NOT NULL,
  `price` decimal(15,2) NOT NULL COMMENT 'Số tiền chính xác tuyệt đối',
  `type` enum('income','expense') NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `create_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Trang thai luu tru (0=active, 1=archived)',
  `evidence_image` varchar(255) DEFAULT NULL,
  `evidence_reference` varchar(100) DEFAULT NULL,
  `evidence_text` text,
  `source_type` varchar(30) NOT NULL DEFAULT 'manual',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active: binh thuong, pending_delete: dang cho xoa do khong du so du',
  `sync_status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active: bình thường, pending_edit: đang chờ áp dụng do thiếu số dư',
  `pending_amount` decimal(12,0) DEFAULT NULL COMMENT 'Số tiền mới chờ áp dụng',
  `pending_type` varchar(10) DEFAULT NULL COMMENT 'Loại mới (income/expense) chờ áp dụng',
  `pending_wallets_json` text COMMENT 'Cau truc vi moi dang JSON',
  `batch_id` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `CategoryForeignKey_idx` (`category_id`),
  KEY `transaction_wallet_idx` (`wallet_id`),
  KEY `idx_user_date_archived` (`user_id`,`transaction_date`,`is_archived`),
  KEY `idx_user_type_archived` (`user_id`,`type`,`is_archived`),
  KEY `transaction_user_type_archived_idx` (`user_id`,`type`,`is_archived`),
  KEY `transaction_user_archived_date_idx` (`user_id`,`is_archived`,`transaction_date`),
  KEY `transaction_user_cat_date_idx` (`user_id`,`category_id`,`type`,`is_archived`,`transaction_date`),
  KEY `transaction_created_at_idx` (`create_at`),
  CONSTRAINT `transaction_wallet_fk` FOREIGN KEY (`wallet_id`) REFERENCES `wallet` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `CategoryForeignKey` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `UserIdForeignKey` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================
-- 5. BUDGET (category budget)
-- =============================================
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

-- =============================================
-- 6. MONTHLY_BUDGET (overall monthly budget)
-- =============================================
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

-- =============================================
-- 7. RECONCILIATION
-- =============================================
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

-- =============================================
-- 8. LOGINTOKEN
-- =============================================
CREATE TABLE `logintoken` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loginToken` varchar(100) NOT NULL,
  `user_id` int NOT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loginToken_UNIQUE` (`loginToken`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================
-- 9. NOTIFICATIONS
-- =============================================
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_created_at` (`created_at`),
  KEY `notif_created_by_idx` (`created_by`),
  CONSTRAINT `notif_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =============================================
-- 10. CATEGORY_LIMIT (migration_category_limit)
-- =============================================
CREATE TABLE IF NOT EXISTS `category_limit` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `max_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_category` (`user_id`, `category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;




-- ============================================================
-- DROP TABLE IF EXISTS `feedbacks`; -- removed chat feature
-- ============================================================
-- SAMPLE ACCOUNTS
-- ============================================================

-- Password for testuser: User@123
-- Password for testadmin: Admin@123
INSERT INTO `user` (`username`, `email`, `phone`, `password`, `status`, `role`, `create_at`)
VALUES
('testuser', 'user@gmail.com', '0999999998', '$2y$10$oUGy8GbAXedzH.5MBzYPjOjKiTX.E903GkUQej93UPsJe8UgsjZ7m', 1, 'user', NOW()),
('testadmin', 'admintest@gmail.com', '0999999999', '$2y$10$FJauISB0B/l4KMLLA8vKoO9pUHKrbp0PbEqAJy3cUl9RG5nH.EHb2', 1, 'admin', NOW());

SET FOREIGN_KEY_CHECKS = 1;
