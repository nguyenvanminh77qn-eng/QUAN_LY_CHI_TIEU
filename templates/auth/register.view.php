<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    layout("header", ["title" => "Đăng ký",
        "css" => ["pages/register"]
    ]);
?>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<main class="main-content">
        <div class="grid-container">
            <section class="value-prop">
                <div class="hero-text">
                    <h1>Kiến tạo tương lai tài chính của bạn.</h1>
                    <p>Trải nghiệm thánh đường kỹ thuật số cho sự minh bạch tài chính. Công cụ chính xác được thiết kế để quản lý chi tiêu hiện đại.</p>
            </section>

            <section class="form-wrapper">
                <div class="form-container">
                    <div class="form-header">
                        <h2>Tạo tài khoản</h2>
                        <p>Nhập thông tin chi tiết để bắt đầu hành trình.</p>
                    </div>

                    <form id="signupForm" class="signup-form">
                        <div class="input-group">
                            <label for="name">Họ và tên</label>
                            <div class="input-relative">
                                <span class="material-symbols-outlined input-icon">person</span>
                                <input type="text" id="name" placeholder="Name" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="email">Địa chỉ Email</label>
                            <div class="input-relative">
                                <span class="material-symbols-outlined input-icon">mail</span>
                                <input type="email" id="email" placeholder="Name@gmail.com" required>
                            </div>
                        </div>

                        <div class="input-group" bis_skin_checked="1">
                            <label for="email">Só điện thoại</label>
                            <div class="input-relative" bis_skin_checked="1">
                                <span class="material-symbols-outlined input-icon">phone</span>
                                <input type="tel" id="email" placeholder="0123456789" required="">
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="input-group">
                                <label for="password">Mật khẩu</label>
                                <div class="input-relative">
                                    <span class="material-symbols-outlined input-icon">lock</span>
                                    <input type="password" id="password" placeholder="••••••••" required>
                                </div>
                            </div>
                            <div class="input-group">
                                <label for="confirm_password">Xác nhận</label>
                                <div class="input-relative">
                                    <span class="material-symbols-outlined input-icon">enhanced_encryption</span>
                                    <input type="password" id="confirm_password" placeholder="••••••••" required>
                                </div>
                            </div>
                        </div>

                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" required>
                            <label for="terms">
                                Tôi đồng ý với <a href="#">Điều khoản</a> và <a href="#">Chính sách bảo mật</a>
                            </label>
                        </div>

                        <button type="submit" class="btn-submit">Tạo tài khoản</button>
                    </form>

                    <div class="mobile-footer lg-hidden">
                        <span>Đã có tài khoản?</span>
                        <a href="#">Đăng nhập</a>
                    </div>
                </div>
            </section>
        </div>
    </main>
<?php
layout("footer", ["js" => ["pages/register"]]);

?>

