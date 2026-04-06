<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    layout("header", ["title" => "Đăng ký",
        "css" => ["pages/register"]
    ]);
?>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<main class="register-page">
    <div class="register-page__container">
        <section class="register-page__intro">
            <h1 class="register-page__title">Kiến tạo tương lai tài chính của bạn.</h1>
            <p class="register-page__description">
                Trải nghiệm thánh đường kỹ thuật số cho sự minh bạch tài chính. Công cụ chính xác được thiết kế để quản lý chi tiêu hiện đại.
            </p>
        </section>

        <section class="register-page__form-section">
            <div class="signup-card">
                <header class="signup-card__header">
                    <h2 class="signup-card__title">Tạo tài khoản</h2>
                    <p class="signup-card__subtitle">Nhập thông tin chi tiết để bắt đầu hành trình.</p>
                </header>

                <form id="signupForm" class="signup-card__form">
                    <div class="form-group">
                        <label class="form-group__label" for="name">Họ và tên</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">person</span>
                            <input class="form-group__input" type="text" id="name" placeholder="Name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-group__label" for="email">Địa chỉ Email</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">mail</span>
                            <input class="form-group__input" type="email" id="email" placeholder="Name@gmail.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-group__label" for="phone">Số điện thoại</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">phone</span>
                            <input class="form-group__input" type="tel" id="phone" placeholder="0123456789" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-group__label" for="password">Mật khẩu</label>
                            <div class="form-group__input-wrapper">
                                <span class="material-symbols-outlined form-group__icon">lock</span>
                                <input class="form-group__input" type="password" id="password" placeholder="••••••••" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-group__label" for="confirm_password">Xác nhận</label>
                            <div class="form-group__input-wrapper">
                                <span class="material-symbols-outlined form-group__icon">enhanced_encryption</span>
                                <input class="form-group__input" type="password" id="confirm_password" placeholder="••••••••" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-checkbox">
                        <input class="form-checkbox__input" type="checkbox" id="terms" required>
                        <label class="form-checkbox__label" for="terms">
                            Tôi đồng ý với <a href="#" class="form-checkbox__link">Điều khoản</a> và <a href="#" class="form-checkbox__link">Chính sách bảo mật</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">Tạo tài khoản</button>
                </form>

                <div class="signup-card__footer">
                    <span>Đã có tài khoản?</span>
                    <a href="#" class="signup-card__link">Đăng nhập</a>
                </div>
            </div>
        </section>
    </div>
</main>
<?php
layout("footer", ["js" => ["pages/register"]]);

?>

