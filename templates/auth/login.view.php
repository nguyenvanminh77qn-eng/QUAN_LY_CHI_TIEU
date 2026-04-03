<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Đăng nhập",
        "css" => ["pages/login"]
    ]);
?>
 <main class="login-container">
        <header class="login-header">
            <h1 class="login-header__title">Login</h1>
        </header>

        <div class="login-card">
            <form class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="email" class="form-group__label">Email</label>
                    <input type="email" id="email" class="form-group__input" placeholder="Email của bạn" required>
                </div>

                <div class="form-group">
                    <div class="form-group__header">
                        <label for="password" class="form-group__label">Password</label>
                        <a href="#" class="form-group__link">Quên mật khẩu?</a>
                    </div>
                    <input type="password" id="password" class="form-group__input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn--primary">Đăng nhập</button>
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
            Chưa có tài khoản? <a href="#" class="login-container__link">Đăng ký ngay</a>
        </p>
    </main>

<?php
    layout("footer", [  "js" => ["pages/login"]
    ]);