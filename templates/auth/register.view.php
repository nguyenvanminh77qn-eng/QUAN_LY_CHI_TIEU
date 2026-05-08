<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    layout("header", ["title" => "Đăng ký",
        "css" => ["pages/auth/register"]
    ]);

    $errors = getFlashData("errors");
    $oldData = getFlashData("old");
    $message = getFlashData("message");
    $message_type = getFlashData("message_type");
    
?>
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
                <?php
                    if(!empty($message)){
                        echo showMessage($message, $message_type);
                    }
                ?>
                <form method="POST" action="<?= _WEB_ROOT ?>?template=auth&action=register" id="signupForm" class="signup-card__form">
                    <div class="form-group">
                        <label class="form-group__label" for="name">Họ và tên</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">person</span>
                            <input class="form-group__input" type="text" id="name" name="username" placeholder="Name" value="<?= old($oldData, 'username') ?>">
                        </div>
                        <?= form_error($errors, 'username') ?>
                    </div>

                    <div class="form-group">
                        <label class="form-group__label" for="email">Địa chỉ Email</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">mail</span>
                            <input class="form-group__input" type="email" id="email" name="email" placeholder="Name@gmail.com"  value="<?= old($oldData, 'email') ?>">
                        </div>
                        <?= form_error($errors, 'email') ?>
                    </div>

                    <div class="form-group">
                        <label class="form-group__label" for="phone">Số điện thoại</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">phone</span>
                            <input class="form-group__input" type="tel" id="phone" name="phone" placeholder="0123456789" value="<?= old($oldData, 'phone') ?>">
                        </div>
                        <?= form_error($errors, 'phone') ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-group__label" for="password">Mật khẩu</label>
                            <div class="form-group__input-wrapper">
                                <span class="material-symbols-outlined form-group__icon">lock</span>
                                <input class="form-group__input" type="password" id="password" name="password" placeholder="••••••••" >
                            </div>
                            <?= form_error($errors, 'password') ?>
                        </div>
                        <div class="form-group">
                            <label class="form-group__label" for="confirm_password">Xác nhận</label>
                            <div class="form-group__input-wrapper">
                                <span class="material-symbols-outlined form-group__icon">enhanced_encryption</span>
                                <input class="form-group__input" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" >
                            </div>
                            <?= form_error($errors, 'confirm_password') ?>
                        </div>
                    </div>

                    <div class="form-checkbox">
                        <input class="form-checkbox__input" type="checkbox" id="terms" >
                        <label class="form-checkbox__label" for="terms">
                            Tôi đồng ý với <a href="#" class="form-checkbox__link">Điều khoản</a> và <a href="#" class="form-checkbox__link">Chính sách bảo mật</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit" name="btn-register">Tạo tài khoản</button>
                </form>

                <div class="signup-card__footer">
                    <span>Đã có tài khoản?</span>
                    <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view" class="signup-card__link">Đăng nhập</a>
                </div>
            </div>
        </section>
    </div>
</main>
<?php
layout("footer", ["js" => ["pages/register"]]);

?>

