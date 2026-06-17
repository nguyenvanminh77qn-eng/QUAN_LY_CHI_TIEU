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
  <div class="register-page__particles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>

  <div class="register-grid">
    <!-- Left: Hero Image -->
    <div class="register-hero">
      <div class="register-hero__bg">
        <img src="https://images.unsplash.com/photo-1579621970795-87facc2f976d?w=800&q=80" alt="" aria-hidden="true"/>
      </div>
      <div class="register-hero__overlay"></div>
      <div class="register-hero__content">
        <div class="register-hero__brand">
          <svg width="32" height="32" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#0d9488"/><path d="M8 18V12h3v6H8Zm4.5 0V8h3v10h-3Zm4.5 0v-4h3v4h-3Z" fill="#fff" opacity="0.9"/></svg>
          MoneyMaster
        </div>
        <h1>Kiến tạo tương lai tài chính</h1>
        <p>Bắt đầu hành trình quản lý chi tiêu thông minh với những công cụ mạnh mẽ và trực quan.</p>
        <div class="register-hero__badges">
          <div class="register-hero__badge">
            <span class="register-hero__badge-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
            </span>
            <div>
              <strong>Miễn phí 30 ngày</strong>
              <span>Không ràng buộc</span>
            </div>
          </div>
          <div class="register-hero__badge">
            <span class="register-hero__badge-icon" style="background:#6366f1">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <div>
              <strong>Bảo mật</strong>
              <span>Mã hóa đầu cuối</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="register-form-col">
      <div class="register-form-card">
        <div class="register-form-header">
          <div class="register-form-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
          </div>
          <h1 class="register-form-title">Tạo tài khoản</h1>
          <p class="register-form-subtitle">Nhập thông tin để bắt đầu hành trình.</p>
        </div>

        <?php if(!empty($message)): ?>
          <div class="register-form-message">
            <?php echo showMessage($message, $message_type); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= _WEB_ROOT ?>?template=auth&action=register" id="registerForm" class="register-form">
          <div class="register-form__group">
            <label class="register-form__label" for="name">Họ và tên</label>
            <div class="register-form__input-wrap">
              <svg class="register-form__input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input class="register-form__input" type="text" id="name" name="username" placeholder="Nguyễn Văn A" value="<?= old($oldData, 'username') ?>">
            </div>
            <?= form_error($errors, 'username') ?>
          </div>

          <div class="register-form__group">
            <label class="register-form__label" for="email">Địa chỉ Email</label>
            <div class="register-form__input-wrap">
              <svg class="register-form__input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <input class="register-form__input" type="email" id="email" name="email" placeholder="name@email.com" value="<?= old($oldData, 'email') ?>">
            </div>
            <?= form_error($errors, 'email') ?>
          </div>

          <div class="register-form__group">
            <label class="register-form__label" for="phone">Số điện thoại</label>
            <div class="register-form__input-wrap">
              <svg class="register-form__input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <input class="register-form__input" type="tel" id="phone" name="phone" placeholder="0123456789" value="<?= old($oldData, 'phone') ?>">
            </div>
            <?= form_error($errors, 'phone') ?>
          </div>

          <div class="register-form__row">
            <div class="register-form__group">
              <label class="register-form__label" for="password">Mật khẩu</label>
              <div class="register-form__input-wrap">
                <svg class="register-form__input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input class="register-form__input" type="password" id="password" name="password" placeholder="••••••••">
              </div>
              <?= form_error($errors, 'password') ?>
            </div>
            <div class="register-form__group">
              <label class="register-form__label" for="confirm_password">Xác nhận mật khẩu</label>
              <div class="register-form__input-wrap">
                <svg class="register-form__input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input class="register-form__input" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••">
              </div>
              <?= form_error($errors, 'confirm_password') ?>
            </div>
          </div>

          <div class="register-form__checkbox">
            <input type="checkbox" id="terms" class="register-form__checkbox-input">
            <label for="terms" class="register-form__checkbox-label">
              Tôi đồng ý với <a href="#">Điều khoản</a> và <a href="#">Chính sách bảo mật</a>
            </label>
          </div>

          <button type="submit" class="register-form__btn" name="btn-register">
            <span>Tạo tài khoản</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </form>

        <div class="register-form-footer">
          <span>Đã có tài khoản?</span>
          <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view">Đăng nhập</a>
        </div>
      </div>
    </div>
  </div>
</main>
<?php
layout("footer", ["js" => ["pages/register"]]);
?>
