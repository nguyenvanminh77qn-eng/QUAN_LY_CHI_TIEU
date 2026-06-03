-- Migration: Add multi-wallet support
-- Run this on existing databases to upgrade

-- 1. Create wallet table
CREATE TABLE IF NOT EXISTS `wallet` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `icon` varchar(10) DEFAULT '💰',
  `type` enum('daily','ewallet','target') NOT NULL DEFAULT 'daily',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallet_user_idx` (`user_id`),
  CONSTRAINT `wallet_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 2. Add wallet_id to transaction
ALTER TABLE `transaction`
  ADD COLUMN `wallet_id` int DEFAULT NULL AFTER `user_id`,
  ADD KEY `transaction_wallet_idx` (`wallet_id`),
  ADD CONSTRAINT `transaction_wallet_fk` FOREIGN KEY (`wallet_id`) REFERENCES `wallet` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
