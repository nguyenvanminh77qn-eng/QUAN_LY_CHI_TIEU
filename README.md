# Quản Lý Chi Tiêu - Expense Tracking Application

Ứng dụng quản lý chi tiêu cá nhân với hai vai trò: **Người dùng** và **Quản trị viên**.

## 📋 Tính Năng Chính

### Dành cho Người Dùng

- ✅ Thêm, sửa, xóa giao dịch chi tiêu
- ✅ Tìm kiếm và lọc giao dịch theo tiêu chí
- ✅ Thêm nhanh giao dịch bằng AI suggestion
- ✅ Xuất dữ liệu giao dịch ra file (CSV/Excel)
- ✅ Quản lý tài khoản (cập nhật profile, đổi mật khẩu)

### Dành cho Quản Trị Viên

- ✅ Quản lý danh sách người dùng (mở ,khóa tài khoản người dùng)
- ✅ Xem và quản lý tất cả giao dịch trong hệ thống
- ✅ Gửi thông báo/notification cho người dùng
- ✅ Xem báo cáo thống kê tổng hợp

### Bảo Mật

- ✅ Xác thực người dùng (Đăng ký, Đăng nhập, Kích hoạt tài khoản qua email)
- ✅ Quên mật khẩu & Đặt lại mật khẩu qua email
- ✅ Login token để đảm bảo phiên làm việc an toàn
- ✅ Kiểm soát quyền truy cập theo vai trò (Role-based Access Control)

---

## 🛠 Yêu Cầu Hệ Thống

- **PHP**: 7.2 trở lên
- **MySQL**: 5.7 trở lên (khuyên dùng 8.0+)
- **Web Server**: Apache (hoặc tương đương)
- **Trình duyệt**: Chrome, Firefox, Safari, Edge (phiên bản mới)

---

## 📥 Cài Đặt

### 1. Clone hoặc tải dự án

```bash
git clone https://github.com/username/quan_ly_chi_tieu.git
cd quan_ly_chi_tieu
```

### 2. Cấu hình cơ sở dữ liệu

**a) Tạo file `.env` từ `.env.example`:**

```bash
cp .env.example .env
```

**b) Chỉnh sửa file `.env` với thông tin của bạn:**

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=quan_ly_chi_tieu
DB_PORT=3306
```

### 3. Nhập cơ sở dữ liệu

**Cách 1: Sử dụng phpMyAdmin**

- Mở phpMyAdmin: `http://localhost/phpmyadmin`
- Tạo database mới có tên: `quan_ly_chi_tieu`
- Chọn database → Tab "Import" → Chọn file `database/quan_ly_chi_tieu.sql`
- Nhấn "Import"

**Cách 2: Sử dụng command line**

```bash
mysql -h localhost -u root -p quan_ly_chi_tieu < database/quan_ly_chi_tieu.sql
```

### 4. Cấu Hình Email (Tùy Chọn)

Để tính năng gửi email (quên mật khẩu, thông báo) hoạt động, bạn cần cấu hình SMTP:

**a) Cập nhật file `.env`:**

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
APP_NAME=Quản Lý Chi Tiêu
```

**b) Hướng dẫn tạo Gmail App Password:**

1. Bật 2-Factor Authentication tại: https://support.google.com/accounts/answer/185833
2. Truy cập: https://myaccount.google.com/apppasswords
3. Chọn "Mail" → "Windows Computer"
4. Sao chép 16 ký tự password, dán vào `SMTP_PASS` trong `.env`

**c) Nếu không cấu hình SMTP:**

- Chức năng gửi email sẽ bị vô hiệu hóa
- Ứng dụng vẫn hoạt động bình thường

### 5. Chạy ứng dụng

- Đặt dự án vào thư mục web server của bạn (thường là `htdocs` với XAMPP)
- Truy cập: `http://localhost/QUAN_LY_CHI_TIEU/`

---

## 📁 Cấu Trúc Thư Mục

```
QUAN_LY_CHI_TIEU/
├── index.php                 # File chính của ứng dụng
├── config.php               # Cấu hình ứng dụng
├── .env.example             # Template file .env
├── .gitignore               # Git ignore rules
├── assets/                  # Tài nguyên (CSS, JS, hình ảnh)
│   ├── css/                 # Các file CSS theo trang
│   ├── js/                  # Các file JavaScript
│   └── images/              # Hình ảnh, icon
├── database/                # Database
│   └── quan_ly_chi_tieu.sql # File dump database
├── includes/                # File include chung
│   ├── connect.php          # Kết nối database
│   ├── database.php         # Hàm database
│   ├── functions.php        # Hàm tiện ích
│   ├── session.php          # Quản lý session
│   ├── validator.php        # Xác thực dữ liệu
│   ├── env-loader.php       # Load biến environment
│   └── PHPMailer/           # Thư viện gửi email
├── modules/                 # Xử lý logic (Controllers)
│   ├── admin/               # Module admin
│   ├── auth/                # Module xác thực
│   ├── home/                # Module trang chủ
│   └── user/                # Module người dùng
└── templates/               # View (HTML templates)
    ├── admin/               # Trang admin
    ├── auth/                # Trang xác thực
    ├── home/                # Trang chủ
    ├── user/                # Trang người dùng
    ├── layout/              # Layout chung (header, footer, sidebar)
    └── error/               # Trang lỗi
```

---

### Các Biện Pháp Bảo Mật Đã Áp Dụng

- ✅ Kiểm tra session và login token
- ✅ Sanitize input (lọc dữ liệu từ GET/POST)
- ✅ Prepared statements (PDO) để tránh SQL injection
- ✅ Role-based access control (RBAC)
- ✅ Password hashing
- ✅ Email verification

### Lưu Ý Quan Trọng

- **Không** commit file `.env` lên git (chỉ commit `.env.example`)
- **Luôn** kiểm tra dữ liệu đầu vào trước khi xử lý
- **Cập nhật** định kỳ để vá các lỗ hổng bảo mật

---
