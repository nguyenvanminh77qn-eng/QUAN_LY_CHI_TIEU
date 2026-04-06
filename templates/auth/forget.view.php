<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Quên mật khẩu",
        "css" => ["pages/forget"]
    ]);
?>


<!DOCTYPE html>
< lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reset Password | Ledger</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
</head>
<body>
    <header class="header">
        <div class="logo">Ledger</div>
        <nav class="nav-links">
            <a href="#">Features</a>
            <a href="#">Security</a>
            <a href="#">Pricing</a>
        </nav>
        <div class="auth-buttons">
            <a href="#" class="sign-in-link">Sign In</a>
            <button class="btn-get-started">Get Started</button>
        </div>
    </header>

    <main class="main-content">
        <div class="bg-blobs">
            <div class="blob blob-top-right"></div>
            <div class="blob blob-bottom-left"></div>
        </div>
        
        <div class="card-wrapper">
            <div class="card-outer">
                <div class="card-inner">
                    <div class="card-header">
                        <div class="icon-circle">
                            <span class="material-symbols-outlined">lock_reset</span>
                        </div>
                        <h1>Forgot password?</h1>
                        <p>Enter your email to reset your password</p>
                    </div>
                    
                    <form class="reset-form">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <span class="material-symbols-outlined input-icon">mail</span>
                                <input id="email" name="email" type="email" placeholder="name@company.com" required />
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">
                            Send Reset Link
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>
                    
                    <div class="back-to-login">
                        <a href="#">
                            <span class="material-symbols-outlined back-icon">arrow_back</span>
                            Back to Login
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="security-badges">
                <span>
                    <span class="material-symbols-outlined">verified_user</span>
                    Secure Encryption
                </span>
                <span>
                    <span class="material-symbols-outlined">history</span>
                    24/7 Support
                </span>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-logo">Ledger</div>
        <div class="footer-links">
            <a href="#">Support</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Security</a>
        </div>
        <div class="footer-copyright">
            © 2024 Architectural Ledger. All rights reserved.
        </div>
    </footer>
</body>
<?php
    layout("footer", ["js" => ["pages/forget"]] );
?>