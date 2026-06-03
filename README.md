# Quản Lý Chi Tiêu - Expense Tracking Application

Ứng dụng quản lý chi tiêu cá nhân với hai vai trò: **Người dùng** và **Quản trị viên**.

## Tính Năng Chính

### Dành cho Người Dùng

- Thêm, sửa, xóa giao dịch chi tiêu
- Tìm kiếm và lọc giao dịch theo tiêu chí
- Đặt ngân sách chi tiêu cho từng danh mục, từng tháng
- Xuất dữ liệu giao dịch ra file CSV theo thời gian, loại, danh mục
- Quản lý tài khoản (đổi mật khẩu)
- Đối soát tài khoản (reconciliation) — kiểm tra số dư thực tế vs hệ thống

### Dành cho Quản Trị Viên

- Quản lý danh sách người dùng (mở/khóa tài khoản)
- Xem và quản lý danh mục
- Gửi thông báo (notification) cho người dùng — mỗi thông báo ghi rõ admin tạo
- Xem báo cáo thống kê tổng hợp (chart, tổng thu/chi, top danh mục)
- Quản lý hồ sơ cá nhân, đổi mật khẩu

### Bảo Mật

- Xác thực 2 yếu tố (OTP) qua email khi đăng nhập
- OTP có hiệu lực 60 giây, gửi lại sau 60 giây
- Xác thực OTP bằng AJAX — không reload trang, countdown chạy liên tục
- Giới hạn số lần xác thực OTP sai (5 lần → block 1 phút, tăng dần)
- Giới hạn số lần gửi lại OTP (5 lần → block)
- Mã OTP được tạo bằng `random_int()` đảm bảo tính ngẫu nhiên
- Login token lưu trong database, kiểm tra mỗi request
- Khi admin khóa user, toàn bộ token bị xóa → user bị đăng xuất ngay lập tức
- Đăng xuất qua POST (không lộ token trên URL)
- Session với httponly + SameSite=Strict
- Prepared statements (PDO) chống SQL injection
- Role-based access control (RBAC) — user không vào được admin và ngược lại
- Kiểm tra role cả ở template (GET) lẫn module handler (POST)
- Password hashing với `password_hash()`
- Link kích hoạt tài khoản hết hạn sau 24 giờ
- Link đặt lại mật khẩu hết hạn sau 24 giờ

### Giao Dịch

- Phát hiện giao dịch bất thường (suspicious) — popup cảnh báo trước khi thêm/sửa
- Giới hạn số giao dịch tối đa mỗi ngày (theo cấu hình)
- Đối soát số dư — chỉ tính giao dịch đến ngày đối soát
- Lưu trữ (archive) giao dịch

### Thông Báo (Notification)

- Admin phát thông báo toast đến tất cả user đang online
- Mỗi thông báo có thời gian hết hạn
- Tự động dọn dẹp thông báo quá hạn (cleanupNotifications)
- Chỉ 1 thông báo active tại một thời điểm
- Lịch sử thông báo hiển thị admin tạo, scroll 290px
- User chỉ thấy thông báo nếu chưa xem (localStorage)

### Database

- Unique index trên `logintoken.loginToken`
- Unique index trên `user.email`, `user.username`
- Khóa ngoại (foreign key) đảm bảo toàn vẹn dữ liệu


---

## Yêu Cầu Hệ Thống

- **PHP**: 7.2 trở lên
- **MySQL**: 5.7 trở lên (khuyên dùng 8.0+)
- **Web Server**: Apache (hoặc tương đương)
- **Trình duyệt**: Chrome, Firefox, Safari, Edge (phiên bản mới)

---

## Cài Đặt

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

### 4. Cấu Hình Email

Để tính năng gửi email (OTP, quên mật khẩu, kích hoạt tài khoản) hoạt động, bạn cần cấu hình SMTP:

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
- Tài khoản test (xem bên dưới) không cần email

### 5. Chạy ứng dụng

- Đặt dự án vào thư mục web server của bạn (thường là `htdocs` với XAMPP)
- Truy cập: `http://localhost/QUAN_LY_CHI_TIEU/`

### 6. Tài Khoản Test (Tùy Chọn)

Nếu muốn dùng tài khoản test (bỏ qua OTP), import thêm file:

```bash
mysql -h localhost -u root -p quan_ly_chi_tieu < database/migration_test_accounts.sql
```

- **User**: `user@gmail.com` / `userpassword`
- **Admin**: `admintest@gmail.com` / `adminpassword`

Có thể import dữ liệu mẫu giao dịch:

```bash
mysql -h localhost -u root -p quan_ly_chi_tieu < database/migration_sample_data.sql
```

---

## Cấu Trúc Thư Mục

```
QUAN_LY_CHI_TIEU/
├── index.php                     # File chính (router)
├── config.php                    # Cấu hình ứng dụng
├── .env.example                  # Template file .env
├── .gitignore
├── assets/
│   ├── css/
│   │   ├── main.css
│   │   ├── layout/
│   │   │   └── sidebar.css
│   │   └── pages/
│   │       ├── user/
│   │       │   ├── add.css
│   │       │   ├── dashboard.css
│   │       │   ├── edit.css
│   │       │   ├── export.css
│   │       │   ├── filter.css
│   │       │   └── profile.css
│   │       ├── admin/
│   │       │   └── dashboard.css
│   │       └── auth/
│   │           ├── login.css
│   │           └── register.css
│   └── js/pages/
│       ├── login.js
│       ├── register.js
│       ├── dashboard.js
│       ├── reset.js
│       ├── forget.js
│       ├── sidebar.js
│       ├── user/filter.js
│       └── admin/dashboard.js
├── database/
│   ├── quan_ly_chi_tieu.sql      # Schema chính
├── includes/
│   ├── connect.php               # Kết nối database
│   ├── database.php              # Hàm database (query, getOne, getAll, insert, update, delete)
│   ├── functions.php             # Hàm tiện ích (redirect, setMessage, OTP helpers, notification helpers)
│   ├── session.php               # Quản lý session
│   ├── validator.php             # Xác thực dữ liệu
│   ├── env-loader.php            # Load biến environment
│   ├── transaction_policy.php    # Chính sách giao dịch (suspicious detection, max per day, balance check)
│   ├── transaction_helpers.php   # Helper xử lý giao dịch
│   └── PHPMailer/                # Thư viện gửi email
├── modules/                      # Xử lý logic (Controllers)
│   ├── admin/
│   │   ├── dashboard.php         # Dashboard admin + broadcast notification
│   │   ├── categories.php        # Quản lý danh mục
│   │   ├── users.php             # Quản lý thành viên (lock/unlock)
│   │   └── profile.php           # Hồ sơ admin, đổi mật khẩu
│   ├── auth/
│   │   ├── login.php             # Xác thực + tạo OTP
│   │   ├── verify_otp.php        # Xác thực OTP (AJAX) + rate limiting
│   │   ├── logout.php            # Đăng xuất (POST-only)
│   │   ├── register.php          # Đăng ký + gửi email kích hoạt
│   │   ├── active.php            # Kích hoạt tài khoản (kiểm tra hạn 24h)
│   │   ├── forget.php            # Quên mật khẩu (hạn 24h)
│   │   └── reset.php             # Đặt lại mật khẩu
│   ├── home/
│   │   └── welcome.php
│   └── user/
│       ├── add.php               # Thêm giao dịch (suspicious check)
│       ├── edit.php              # Sửa giao dịch
│       ├── delete.php            # Xóa hàng loạt
│       ├── reconcile.php         # Đối soát tài khoản
│       ├── budget.php            # Quản lý ngân sách
│       ├── filter.php            # Lọc giao dịch
│       ├── export.php            # Xuất CSV
│       └── profile.php           # Đổi mật khẩu
└── templates/                    # View (HTML templates)
    ├── admin/
    │   ├── dashboard.php
    │   ├── categories.php
    │   ├── users.php
    │   └── profile.php
    ├── auth/
    │   ├── login.view.php
    │   ├── verify_otp.view.php   # Form OTP + AJAX countdown
    │   ├── register.view.php
    │   ├── active.view.php       # Kích hoạt (kiểm tra hạn)
    │   ├── forget.view.php
    │   └── reset.view.php        # Reset mật khẩu (kiểm tra hạn)
    ├── home/
    │   └── welcome.php
    ├── user/
    │   ├── add.php               # Form thêm + suspicious modal
    │   ├── edit.php              # Form sửa + suspicious modal
    │   ├── dashboard.php         # Dashboard user
    │   ├── budget.php            # Quản lý ngân sách
    │   ├── filter.php            # Bộ lọc + kết quả
    │   ├── export.php            # Form xuất CSV
    │   └── profile.php           # Hồ sơ + đổi mật khẩu
    ├── layout/
    │   ├── header.php            # Header + notification toast
    │   ├── footer.php
    │   ├── sidebar.php           # Sidebar user
    │   └── sidebar_admin.php     # Sidebar admin
    └── error/
        └── 404.php
```

---

## Luồng Xác Thực OTP

1. User nhập email/password → `login.php` kiểm tra thông tin
2. Nếu đúng → tạo mã OTP 6 số (60s), gửi email, lưu `otp_sent_at` vào session
3. Chuyển đến form OTP (`verify_otp.view.php`)
4. Form gửi AJAX `fetch()` đến `verify_otp.php` — không reload trang
5. Đúng → redirect, Sai → hiện lỗi + countdown vẫn chạy
6. Hết giờ → bấm "Gửi lại" (AJAX) → OTP mới + reset countdown
7. Sai 5 lần → block 1 phút (tăng dần mỗi lần)

### Tài khoản test

Hai tài khoản `user@gmail.com` và `admin@gmail.com` được hardcode bỏ qua OTP — đăng nhập thẳng vào dashboard.

---

## Biện Pháp Bảo Mật Đã Áp Dụng

- OTP 6 số dùng `random_int()`, hiệu lực 60s
- Rate limit OTP (verify + resend) — session-based, block tăng dần
- Session: httponly, SameSite=Strict
- Login token kiểm tra mỗi request, xóa khi khóa user
- Đăng xuất: POST-only, token đọc từ session (không trên URL)
- Prepared statements cho mọi truy vấn
- Input sanitize (`filter()`)
- Role-based access (template + module)
- Password hash bằng `password_hash(PASSWORD_DEFAULT)`
- Link kích hoạt/reset hết hạn sau 24h
- Chống tấn công CSRF (SameSite=Strict)

## Lưu Ý Quan Trọng

- **Không** commit file `.env` lên git (chỉ commit `.env.example`)
- **Luôn** kiểm tra dữ liệu đầu vào trước khi xử lý
- **Cập nhật** định kỳ để vá các lỗ hổng bảo mật
