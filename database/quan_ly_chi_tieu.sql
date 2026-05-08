-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: quan_ly_chi_tieu
-- Database Name: quan_ly_chi_tieu (Expense Tracking Application)
-- Project: Quản Lý Chi Tiêu - Personal Expense Manager
-- Description: Database schema for expense tracking with user roles and notifications
-- Created: 2026-05-08
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table: `category` - Danh mục giao dịch (Expense categories)
-- Contains predefined categories for transactions (e.g., Food, Transport, Entertainment)
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'Tên danh mục / Category name',
  `icon` varchar(10) DEFAULT 0xF09F93A6 COMMENT 'Icon emoji / Category emoji icon',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Danh mục giao dịch - Transaction categories';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES 
(1,'Ăn uống','🍔'),
(2,'Giao thông','🚗'),
(3,'Giải trí','🎮'),
(4,'Mua sắm','🛍️'),
(5,'Sức khỏe','💊'),
(6,'Giáo dục','📚'),
(7,'Điện nước','⚡'),
(8,'Khác','📝');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table: `logintoken` - Token đăng nhập (Login sessions)
-- Stores login tokens for user session management and security
--

DROP TABLE IF EXISTS `logintoken`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logintoken` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loginToken` varchar(100) NOT NULL COMMENT 'Token đăng nhập duy nhất / Unique login token',
  `user_id` int NOT NULL COMMENT 'ID người dùng / User ID',
  `create_at` datetime DEFAULT NULL COMMENT 'Thời gian tạo / Creation timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `loginToken_UNIQUE` (`loginToken`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Token đăng nhập - Login tokens for sessions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table: `notifications` - Thông báo (System notifications)
-- Stores notification messages sent from admin to users or system notifications
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL COMMENT 'Nội dung thông báo / Notification message',
  `type` varchar(50) NOT NULL DEFAULT 'info' COMMENT 'Loại thông báo (info/warning/error) / Notification type',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trạng thái hoạt động / Active status',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo / Creation timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Thông báo hệ thống - System notifications';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table: `transaction` - Giao dịch (Transactions)
-- Records all income and expense transactions made by users
-- Relationships: user_id -> user.id, category_id -> category.id
--

DROP TABLE IF EXISTS `transaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'ID người dùng / User ID',
  `category_id` int NOT NULL COMMENT 'ID danh mục / Category ID',
  `price` float NOT NULL COMMENT 'Số tiền / Amount',
  `type` enum('income','expense') NOT NULL COMMENT 'Loại giao dịch (thu nhập/chi tiêu) / Transaction type',
  `description` varchar(100) DEFAULT NULL COMMENT 'Mô tả giao dịch / Transaction description',
  `transaction_date` date NOT NULL COMMENT 'Ngày giao dịch / Transaction date',
  `create_at` datetime DEFAULT NULL COMMENT 'Thời gian tạo / Creation timestamp',
  `update_at` datetime DEFAULT NULL COMMENT 'Thời gian cập nhật / Last update timestamp',
  PRIMARY KEY (`id`),
  KEY `user_id_idx` (`user_id`),
  KEY `CategoryForeignKey_idx` (`category_id`),
  CONSTRAINT `CategoryForeignKey` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`),
  CONSTRAINT `UserIdForeignKey` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Giao dịch - User transactions (income/expense)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table: `user` - Người dùng (Users)
-- Stores user account information, authentication data, and role information
-- Roles: 'user' (regular user), 'admin' (administrator)
-- Status: 0=inactive, 1=active
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT 'Tên đăng nhập / Username',
  `email` varchar(50) NOT NULL COMMENT 'Email địa chỉ / Email address',
  `phone` varchar(10) NOT NULL COMMENT 'Số điện thoại / Phone number',
  `password` varchar(100) NOT NULL COMMENT 'Mật khẩu (hash) / Password hash',
  `activeToken` varchar(100) DEFAULT NULL COMMENT 'Token xác thực email / Email verification token',
  `forgotToken` varchar(100) DEFAULT NULL COMMENT 'Token đặt lại mật khẩu / Password reset token',
  `status` int DEFAULT '0' COMMENT 'Trạng thái (0=inactive, 1=active) / Account status',
  `create_at` datetime DEFAULT NULL COMMENT 'Thời gian tạo / Creation timestamp',
  `update_at` datetime DEFAULT NULL COMMENT 'Thời gian cập nhật / Last update timestamp',
  `role` enum('user','admin') DEFAULT 'user' COMMENT 'Vai trò (user/admin) / User role',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `phone_UNIQUE` (`phone`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Người dùng - User accounts';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-08 12:00:00
