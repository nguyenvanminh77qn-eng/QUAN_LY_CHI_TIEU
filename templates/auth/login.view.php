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


 <main class="login-container">
        <header class="login-header">
            <h1 class="login-header__title">Login</h1>
        </header>
        <?php if(!empty($message)): ?>
            <?php echo showMessage($message,$message_type); ?>
        <?php endif; ?>
        <div class="login-card">
            <form class="login-form" id="loginForm" method="POST" action="<?= _WEB_ROOT ?>?template=auth&action=login">
                <div class="form-group">
                    <label for="email" class="form-group__label">Email</label>
                    <input type="email" id="email" class="form-group__input" placeholder="Email của bạn" name="email" value="<?= old($oldData, 'email') ?>">
                    <?= form_error($errors, 'email') ?>
                </div>

                <div class="form-group">
                    <div class="form-group__header">
                        <label for="password" class="form-group__label">Password</label>
                        <a href="?template=auth&action=forget.view" class="form-group__link">Quên mật khẩu?</a>
                    </div>
                    <input type="password" id="password" class="form-group__input" placeholder="••••••••" name="password" >
                    <?= form_error($errors, 'password') ?>
                </div>

                <button type="submit" class="btn btn--primary" name="btn-login">Đăng nhập</button>
            </form>

            <div class="divider">
                <span class="divider__text">Hoặc</span>
            </div>

            <div class="social-group">
                <button class="btn btn--outline">
                    <img src="https://www.google.com/favicon.ico" alt="Google" class="btn__icon">
                    Google
                </button>
                <button class="btn btn--outline">
                    <img src="https://www.apple.com/favicon.ico" alt="Apple" class="btn__icon">
                    Apple
                </button>
            </div>
        </div>

        <p class="login-container__footer-text">
            Chưa có tài khoản? <a href="?template=auth&action=register.view" class="login-container__link">Đăng ký ngay</a>
        </p>
    </main>

<?php
    layout("footer", [  "js" => ["pages/login"]
    ]);

?>
