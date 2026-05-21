-- Migration: Thêm index cho hiệu năng truy vấn
-- Chạy file này nếu database đã tồn tại trước khi có các index mới

-- transaction: composite index cho truy vấn user + type + archived
CREATE INDEX transaction_user_type_archived_idx ON `transaction` (user_id, type, is_archived);
CREATE INDEX transaction_user_archived_date_idx ON `transaction` (user_id, is_archived, transaction_date);
CREATE INDEX transaction_user_cat_date_idx ON `transaction` (user_id, category_id, type, is_archived, transaction_date);
CREATE INDEX transaction_created_at_idx ON `transaction` (create_at);
