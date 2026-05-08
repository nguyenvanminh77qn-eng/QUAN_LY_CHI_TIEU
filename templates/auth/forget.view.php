<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Quên mật khẩu",
        "css" => ["pages/auth/forget"]
    ]);
    $message = getFlashData("message");
    $message_type = getFlashData("message_type");
    $errors = getFlashData("errors");
    $old = getFlashData("old");
?>

<main class="forget-page">
    <div class="forget-page__container">
        <!-- Left: Intro Section -->
        <section class="forget-page__intro">
            <div class="forget-page__intro-content">
                <div class="forget-page__icon-badge">
                    <span class="material-symbols-outlined">shield_lock</span>
                </div>
                <h1 class="forget-page__title">Khôi phục<br>tài khoản của bạn.</h1>
                <p class="forget-page__description">
                    Đừng lo lắng, việc quên mật khẩu là chuyện bình thường. Chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu qua email cho bạn.
                </p>
                <div class="forget-page__features">
                    <div class="forget-page__feature">
                        <span class="material-symbols-outlined">verified_user</span>
                        <span>Bảo mật tuyệt đối</span>
                    </div>
                    <div class="forget-page__feature">
                        <span class="material-symbols-outlined">bolt</span>
                        <span>Nhanh chóng & dễ dàng</span>
                    </div>
                    <div class="forget-page__feature">
                        <span class="material-symbols-outlined">support_agent</span>
                        <span>Hỗ trợ 24/7</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right: Form Section -->
        <section class="forget-page__form-section">
            <div class="forget-card">
                <header class="forget-card__header">
                    <div class="forget-card__icon-circle">
                        <span class="material-symbols-outlined">lock_reset</span>
                    </div>
                    <h2 class="forget-card__title">Quên mật khẩu?</h2>
                    <p class="forget-card__subtitle">Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.</p>
                </header>

                <?php if(!empty($message)): ?>
                    <?php echo showMessage($message, $message_type); ?>
                <?php endif; ?>

                <form method="POST" action="<?= _WEB_ROOT ?>?template=auth&action=forget" id="forgetForm" class="forget-card__form">
                    <div class="form-group">
                        <label class="form-group__label" for="email">Địa chỉ Email</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">mail</span>
                            <input class="form-group__input" type="email" id="email" name="email" placeholder="name@gmail.com" value="<?= old($old, 'email') ?>">
                        </div>
                        <?= form_error($errors, 'email') ?>
                    </div>

                    <button type="submit" class="btn-submit" name="btn-forget">
                        <span>Gửi liên kết đặt lại</span>
                        <span class="material-symbols-outlined btn-submit__icon">arrow_forward</span>
                    </button>
                </form>

                <div class="forget-card__divider">
                    <span class="forget-card__divider-text">Hoặc</span>
                </div>

                <div class="forget-card__footer">
                    <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view" class="forget-card__back-link">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <span>Quay lại đăng nhập</span>
                    </a>
                </div>

                <div class="forget-card__register">
                    <span>Chưa có tài khoản?</span>
                    <a href="<?= _WEB_ROOT ?>?template=auth&action=register.view" class="forget-card__link">Đăng ký ngay</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
    layout("footer", ["js" => ["pages/forget"]]);
?>