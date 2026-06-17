# Quản Lý Chi Tiêu

Ứng dụng quản lý chi tiêu cá nhân với hai vai trò: **Người dùng** (user) và **Quản trị viên** (admin).

## Yêu Cầu Hệ Thống

- **PHP**: 7.4 trở lên (khuyên dùng 8.x)
- **MySQL**: 5.7+ (khuyên dùng 8.0+)
- **Web Server**: Apache (mod_rewrite bật)
- **Trình duyệt**: Chrome, Firefox, Edge (phiên bản mới)

## Cài Đặt Nhanh

### 1. Clone dự án

```bash
git clone https://github.com/username/quan_ly_chi_tieu.git
cd quan_ly_chi_tieu
```

### 2. Cấu hình database

Copy file `.env.example` thành `.env` và sửa thông tin:

```bash
cp .env.example .env
```

Mở file `.env`, sửa các dòng:

```env
DB_HOST=localhost
DB_USER=root              # MySQL user
DB_PASS=your_password     # MySQL password
DB_NAME=quan_ly_chi_tieu  # Tên database
DB_PORT=3306
```

### 3. Import database

Tạo database `quan_ly_chi_tieu` trong MySQL, sau đó import:

```
mysql -h localhost -u root -p quan_ly_chi_tieu < database/database_complete.sql
```

Hoặc dùng phpMyAdmin: tạo database → chọn tab Import → chọn file `database/database_complete.sql`.

### 4. Chạy ứng dụng

Đặt thư mục dự án vào `htdocs` (XAMPP) hoặc DocumentRoot của Apache.

Truy cập: **http://localhost/QUAN_LY_CHI_TIEU/**

## Tài Khoản Mẫu

Sau khi import database, có sẵn 2 tài khoản test:

| Vai trò | Email | Mật khẩu |
|---------|-------|----------|
| Người dùng | `user@gmail.com` | `User@123` |
| Quản trị viên | `admintest@gmail.com` | `Admin@123` |

Hai tài khoản này được hardcode bỏ qua OTP → đăng nhập thẳng vào dashboard.

## Cấu Hình Email (SMTP)

Tính năng OTP, quên mật khẩu, kích hoạt tài khoản cần SMTP để gửi email.

### Gmail SMTP

Sửa trong file `.env`:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=465
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
```

**Tạo App Password cho Gmail:**
1. Vào https://myaccount.google.com/security → bật **Xác thực 2 bước**
2. Vào https://myaccount.google.com/apppasswords
3. Chọn "Mail" → "Windows Computer" → Tạo
4. Copy 16 ký tự password dán vào `SMTP_PASS`

### Nếu không cấu hình SMTP

Ứng dụng vẫn chạy bình thường. Email sẽ được ghi log ra file `mail_debug.log` để kiểm tra.

## Cấu Trúc Thư Mục

```
QUAN_LY_CHI_TIEU/
├── index.php              # Entry point (require src/bootstrap)
├── .env                   # Cấu hình database + SMTP (KHÔNG commit)
├── .env.example           # Mẫu .env (commit được)
│
├── src/
│   ├── bootstrap/
│   │   └── index.php      # Bootloader: load config, core, route
│   ├── config/
│   │   └── app.php        # Config constants, loadEnv()
│   ├── app/
│   │   ├── Core/          # connect.php, database.php, functions.php, session.php
│   │   ├── Helpers/       # transaction_helpers, wallet_helper, feedback_helper, monthly_report
│   │   └── Http/Api/      # AJAX endpoints (chat_poll, feedback)
│   ├── modules/           # POST handlers (Controllers)
│   │   ├── auth/          # login, register, logout, OTP, active, forget, reset
│   │   ├── user/          # add, edit, delete, budget, filter, export, profile, wallet, reconcile
│   │   └── admin/         # dashboard, categories, users, notifications, profile, reports
│   └── views/             # GET templates (Views)
│       ├── layout/        # header, footer, sidebar
│       ├── auth/          # login, register, OTP, active, forget, reset
│       ├── user/          # dashboard, add, edit, budget, filter, export, profile, wallet
│       ├── admin/         # dashboard, categories, users, notifications, reports
│       └── home/          # welcome page
│
├── public/
│   └── assets/
│       ├── css/           # base/, layout/, pages/, themes.css, main.css
│       ├── js/pages/      # JS theo từng page
│       └── images/        # (trống)
│
├── vendor/                # Thư viện (PHPMailer, FPDF)
│   ├── phpmailer/
│   └── fpdf/
│
├── database/
│   └── database_complete.sql  # Schema + dữ liệu mẫu
│
└── storage/               # Uploads, temp files
```

## Khắc Phục Sự Cố

### Email không gửi được

1. Kiểm tra `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` trong `.env`
2. Với Gmail: phải dùng **App Password**, không phải mật khẩu thường
3. Port 465 dùng SSL, port 587 dùng TLS — đảm bảo cổng không bị firewall chặn
4. Xem log tại `mail_debug.log` để biết chi tiết lỗi
5. Nếu SMTP trống, email sẽ được log ra file mà không gửi thật

### Lỗi database

1. Đảm bảo database `quan_ly_chi_tieu` đã được tạo
2. Import file `database/database_complete.sql` đầy đủ
3. Kiểm tra thông tin trong `.env` khớp với MySQL của bạn
4. Nếu dùng XAMPP, mặc định `DB_PASS` để trống

### Lỗi 500 / White screen

1. Bật PHP error display trong `src/config/app.php`
2. Kiểm tra Apache error log
3. Đảm bảo thư mục `vendor/` tồn tại (chứa PHPMailer + FPDF)
