-- Expense Manager database schema
-- Project: QUAN_LY_CHI_TIEU

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `reconciliation`;
DROP TABLE IF EXISTS `logintoken`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `transaction`;
DROP TABLE IF EXISTS `budget`;
DROP TABLE IF EXISTS `monthly_budget`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `user`;

-- 1. BẢNG NGƯỜI DÙNG (USER)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. BẢNG DANH MỤC THU CHI (CATEGORY)
CREATE TABLE `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `icon` varchar(10) DEFAULT '📦',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `category` (`id`, `name`, `icon`) VALUES
(1, 'Ăn uống', '🍔'),
(2, 'Giao thông', '🚗'),
(3, 'Giải trí', '🎮'),
(4, 'Mua sắm', '🛍️'),
(5, 'Sức khỏe', '💊'),
(6, 'Giáo dục', '📚'),
(7, 'Điện nước', '⚡'),
(8, 'Khác', '📝');

-- 3. BẢNG GIAO DỊCH (TRANSACTION)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BẢNG NGÂN SÁCH DANH MỤC (BUDGET)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. BẢNG TỔNG NGÂN SÁCH THÁNG (MONTHLY_BUDGET)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. BẢNG ĐỐI CHIẾU CHỐT SỔ (RECONCILIATION)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. BẢNG TOKEN ĐĂNG NHẬP (LOGINTOKEN)
CREATE TABLE `logintoken` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loginToken` varchar(100) NOT NULL,
  `user_id` int NOT NULL,
  `create_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loginToken_UNIQUE` (`loginToken`),
  KEY `logintoken_user_idx` (`user_id`),
  CONSTRAINT `logintoken_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. BẢNG THÔNG BÁO (NOTIFICATIONS)
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notif_created_by_idx` (`created_by`),
  CONSTRAINT `notif_admin_fk` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- DỮ LIỆU ĐỔ MẪU VÀ MIGRATION MẪU
-- ============================================================

-- Tạo tài khoản mẫu
INSERT INTO `user` (`username`, `email`, `phone`, `password`, `status`, `role`, `create_at`)
VALUES
('testuser', 'user@gmail.com', '0999999998', '$2y$10$oUGy8GbAXedzH.5MBzYPjOjKiTX.E903GkUQej93UPsJe8UgsjZ7m', 1, 'user', NOW()),
('testadmin', 'admintest@gmail.com', '0999999999', '$2y$10$FJauISB0B/l4KMLLA8vKoO9pUHKrbp0PbEqAJy3cUl9RG5nH.EHb2', 1, 'admin', NOW());

-- Khởi tạo biến ID cho tài khoản testuser
SET @uid := (SELECT id FROM user WHERE email = 'user@gmail.com');

-- Lấy danh mục ID
SET @cat_an_uong := (SELECT id FROM category WHERE name = 'Ăn uống');
SET @cat_giai_tri := (SELECT id FROM category WHERE name = 'Giải trí');
SET @cat_mua_sam := (SELECT id FROM category WHERE name = 'Mua sắm');
SET @cat_suc_khoe := (SELECT id FROM category WHERE name = 'Sức khỏe');
SET @cat_khac := (SELECT id FROM category WHERE name = 'Khác');

-- Ngân sách mẫu theo danh mục tháng 5/2026
INSERT INTO budget (user_id, category_id, month, year, amount, created_at) VALUES
(@uid, @cat_an_uong, 5, 2026, 3000000, NOW()),
(@uid, @cat_giai_tri, 5, 2026, 1500000, NOW()),
(@uid, @cat_mua_sam, 5, 2026, 2000000, NOW()),
(@uid, @cat_suc_khoe, 5, 2026, 500000, NOW());

-- Giao dịch mẫu tháng 3/2026
INSERT INTO transaction (user_id, category_id, price, type, description, transaction_date, create_at, is_archived, source_type) VALUES
(@uid, @cat_an_uong, 85000, 'expense', 'Ăn trưa văn phòng', '2026-03-02', NOW(), 1, 'manual'),
(@uid, @cat_an_uong, 120000, 'expense', 'Đi ăn tối cùng bạn', '2026-03-05', NOW(), 1, 'manual'),
(@uid, @cat_an_uong, 95000, 'expense', 'Ăn sáng + cà phê', '2026-03-10', NOW(), 1, 'manual'),
(@uid, @cat_giai_tri, 200000, 'expense', 'Xem phim rạp', '2026-03-12', NOW(), 1, 'manual'),
(@uid, @cat_mua_sam, 350000, 'expense', 'Mua áo thun mới', '2026-03-15', NOW(), 1, 'manual'),
(@uid, @cat_an_uong, 65000, 'expense', 'Ăn trưa', '2026-03-16', NOW(), 1, 'manual'),
(@uid, @cat_an_uong, 200000, 'expense', 'Đi ăn nhà hàng cuối tuần', '2026-03-20', NOW(), 1, 'manual'),
(@uid, @cat_khac, 100000, 'expense', 'Mua đồ dùng văn phòng', '2026-03-22', NOW(), 1, 'manual');

-- Giao dịch mẫu tháng 4/2026
INSERT INTO transaction (user_id, category_id, price, type, description, transaction_date, create_at, is_archived, source_type) VALUES
(@uid, @cat_an_uong, 75000, 'expense', 'Ăn sáng', '2026-04-02', NOW(), 0, 'manual'),
(@uid, @cat_khac, 1600000, 'expense', 'Tiền điện tháng 3', '2026-04-05', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 110000, 'expense', 'Ăn trưa với đồng nghiệp', '2026-04-06', NOW(), 0, 'manual'),
(@uid, @cat_khac, 55000, 'expense', 'Đổ xăng', '2026-04-07', NOW(), 0, 'manual'),
(@uid, @cat_suc_khoe, 200000, 'expense', 'Mua thuốc cảm', '2026-04-08', NOW(), 0, 'manual'),
(@uid, @cat_mua_sam, 550000, 'expense', 'Mua giày thể thao', '2026-04-10', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 85000, 'expense', 'Ăn trưa', '2026-04-11', NOW(), 0, 'manual'),
(@uid, @cat_khac, 750000, 'expense', 'Tiền nước tháng 3', '2026-04-12', NOW(), 0, 'manual'),
(@uid, @cat_giai_tri, 150000, 'expense', 'Xem phim + bỏng ngô', '2026-04-13', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 180000, 'expense', 'Ăn tối gia đình', '2026-04-15', NOW(), 0, 'manual'),
(@uid, @cat_khac, 40000, 'expense', 'Grab đi làm', '2026-04-16', NOW(), 0, 'manual'),
(@uid, @cat_khac, 250000, 'expense', 'Quà sinh nhật bạn', '2026-04-18', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 95000, 'expense', 'Ăn trưa', '2026-04-19', NOW(), 0, 'manual'),
(@uid, @cat_mua_sam, 420000, 'expense', 'Mua balo mới', '2026-04-21', NOW(), 0, 'manual'),
(@uid, @cat_khac, 350000, 'expense', 'Tiền internet tháng 4', '2026-04-22', NOW(), 0, 'manual'),
(@uid, @cat_giai_tri, 300000, 'expense', 'Mua game trên Steam', '2026-04-23', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 65000, 'expense', 'Ăn sáng', '2026-04-25', NOW(), 0, 'manual'),
(@uid, @cat_khac, 50000, 'expense', 'Đổ xăng', '2026-04-26', NOW(), 0, 'manual');

-- Thu nhập mẫu tháng 4/2026
INSERT INTO transaction (user_id, category_id, price, type, description, transaction_date, create_at, is_archived, source_type) VALUES
(@uid, @cat_khac, 2000000, 'income', 'Làm thêm cuối tuần', '2026-04-15', NOW(), 0, 'manual'),
(@uid, @cat_khac, 500000, 'income', 'Tiền lãi gửi tiết kiệm', '2026-04-20', NOW(), 0, 'manual'),
(@uid, @cat_khac, 12000000, 'income', 'Lương tháng 4/2026', '2026-04-01', NOW(), 0, 'manual');

-- Giao dịch mẫu tháng 5/2026
INSERT INTO transaction (user_id, category_id, price, type, description, transaction_date, create_at, is_archived, source_type) VALUES
(@uid, @cat_an_uong, 85000, 'expense', 'Ăn sáng phở', '2026-05-02', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 120000, 'expense', 'Ăn trưa với đối tác', '2026-05-04', NOW(), 0, 'manual'),
(@uid, @cat_mua_sam, 280000, 'expense', 'Mua sách', '2026-05-06', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 95000, 'expense', 'Ăn trưa', '2026-05-07', NOW(), 0, 'manual'),
(@uid, @cat_suc_khoe, 150000, 'expense', 'Khám răng định kỳ', '2026-05-08', NOW(), 0, 'manual'),
(@uid, @cat_giai_tri, 350000, 'expense', 'Đi xem ca nhạc', '2026-05-09', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 75000, 'expense', 'Ăn sáng', '2026-05-10', NOW(), 0, 'manual'),
(@uid, @cat_khac, 700000, 'expense', 'Tiền nước tháng 4', '2026-05-11', NOW(), 0, 'manual'),
(@uid, @cat_khac, 180000, 'expense', 'Phí gửi xe tháng 5', '2026-05-12', NOW(), 0, 'manual'),
(@uid, @cat_khac, 45000, 'expense', 'Xe bus', '2026-05-13', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 210000, 'expense', 'Ăn tối cùng bạn bè', '2026-05-14', NOW(), 0, 'manual'),
(@uid, @cat_mua_sam, 890000, 'expense', 'Mua tai nghe Bluetooth', '2026-05-15', NOW(), 0, 'manual'),
(@uid, @cat_an_uong, 65000, 'expense', 'Ăn trưa', '2026-05-16', NOW(), 0, 'manual'),
(@uid, @cat_khac, 12000000, 'income', 'Lương tháng 5/2026', '2026-05-01', NOW(), 0, 'manual'),
(@uid, @cat_khac, 300000, 'income', 'Bán đồ cũ', '2026-05-10', NOW(), 0, 'manual');

-- Giao dịch điều chỉnh chốt sổ mẫu
INSERT INTO transaction (user_id, category_id, price, type, description, transaction_date, create_at, is_archived, source_type) VALUES
(@uid, @cat_khac, 50000, 'expense', 'Adjustment', '2026-03-31', NOW(), 0, 'adjustment');

SET FOREIGN_KEY_CHECKS = 1;

