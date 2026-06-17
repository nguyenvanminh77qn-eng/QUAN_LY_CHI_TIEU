<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Đăng nhập",
        "css" => ["pages/auth/login"]
    ]);
    $loginToken = getSession("loginToken");
    if(!empty($loginToken)){
        $role = getSession('role');
        if($role == 'admin'){
            redirect("?template=admin&action=dashboard");
        }else{
            redirect("?template=user&action=dashboard");
        }
    }
    $message = getFlashData("message");
    $message_type = getFlashData("message_type");
    $errors = getFlashData("errors");
    $oldData = getFlashData("old");
?>

<main class="login-page">
  <div class="login-page__particles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>

  <div class="login-grid">
    <!-- Left: Hero Image -->
    <div class="login-hero">
      <div class="login-hero__bg">
        <img src="https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=800&q=80" alt="" aria-hidden="true"/>
      </div>
      <div class="login-hero__overlay"></div>
      <div class="login-hero__content">
        <div class="login-hero__brand">
          <svg width="32" height="32" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#0d9488"/><path d="M8 18V12h3v6H8Zm4.5 0V8h3v10h-3Zm4.5 0v-4h3v4h-3Z" fill="#fff" opacity="0.9"/></svg>
          MoneyMaster
        </div>
        <h1>Chào mừng trở lại</h1>
        <p>Theo dõi thu chi, lập ngân sách và đạt được mục tiêu tài chính cá nhân một cách dễ dàng.</p>
        <div class="login-hero__badges">
          <div class="login-hero__badge">
            <span class="login-hero__badge-icon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </span>
            <div>
              <strong>Tiết kiệm</strong>
              <span>Tăng 32% tháng này</span>
            </div>
          </div>
          <div class="login-hero__badge">
            <span class="login-hero__badge-icon" style="background:#f59e0b">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </span>
            <div>
              <strong>1,200+</strong>
              <span>Giao dịch hôm nay</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="login-form-col">
      <div class="login-form-card">
        <div class="login-form-header">
          <div class="login-form-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <h1 class="login-form-title">Đăng nhập</h1>
          <p class="login-form-subtitle">Chào mừng bạn trở lại!</p>
        </div>

        <?php if(!empty($message)): ?>
          <div class="login-form-message">
            <?php echo showMessage($message, $message_type); ?>
          </div>
        <?php endif; ?>

        <form class="login-form" id="loginForm" method="POST" action="<?= _WEB_ROOT ?>?template=auth&action=login">
          <div class="login-form__group">
            <label for="email" class="login-form__label">Email</label>
            <div class="login-form__input-wrap">
              <svg class="login-form__input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <input type="email" id="email" class="login-form__input" placeholder="name@email.com" name="email" value="<?= old($oldData, 'email') ?>">
            </div>
            <?= form_error($errors, 'email') ?>
          </div>

          <div class="login-form__group">
            <div class="login-form__group-head">
              <label for="password" class="login-form__label">Mật khẩu</label>
              <a href="?template=auth&action=forget.view" class="login-form__link">Quên mật khẩu?</a>
            </div>
            <div class="login-form__input-wrap">
              <svg class="login-form__input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <input type="password" id="password" class="login-form__input" placeholder="••••••••" name="password">
            </div>
            <?= form_error($errors, 'password') ?>
          </div>

          <button type="submit" class="login-form__btn" name="btn-login">
            <span>Đăng nhập</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>

          <div class="login-form__divider">
            <span>Hoặc tiếp tục với</span>
          </div>

          <div class="login-form__social">
            <button type="button" class="login-form__social-btn">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
              Google
            </button>
            <button type="button" class="login-form__social-btn">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z" fill="#333"/></svg>
              Apple
            </button>
          </div>
        </form>
      </div>

      <p class="login-form-footer">
        Chưa có tài khoản? <a href="?template=auth&action=register.view">Đăng ký ngay</a>
      </p>
    </div>
  </div>
</main>

<?php
    layout("footer", ["js" => ["pages/login"]]);
?>
