<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", [
        "title" => "Reset password",
        "css" => ["pages/auth/reset", "pages/auth/auth-entrance"]
    ]);
    
    $tokenValid = false;
    $filterAll = filter();
    $token = $filterAll['reset'] ?? '';
    
    if(empty($token)){
        setMessage("Bạn chưa có link để sửa mật khẩu.", "error");
    }else{
        $userByToken = getOne(
            "SELECT id, forgot_expires FROM user WHERE forgotToken = :forgotToken",
            ['forgotToken' => $token]
        );
        if(!$userByToken){
            setMessage("Link này không tồn tại hoặc hết hạn.", "error");
        }elseif (!empty($userByToken['forgot_expires']) && strtotime($userByToken['forgot_expires']) <= time()) {
            setMessage("Link reset mật khẩu đã hết hạn. Vui lòng yêu cầu lại.", "error");
        }else{
            $tokenValid = true;
        }
    }
    
    $message = getFlashData("message");
    $message_type = getFlashData("message_type");
    $errors = getFlashData("errors");
    
?>

<?php if(!$tokenValid): ?>
<?php echo showMessage($message, $message_type); ?>
<?php else: ?>

<header class="reset-shell__header header">
    <div class="logo auth-entrance auth-e-1">Ledger</div>
    <nav class="nav-links auth-entrance auth-e-1">
        <a href="#">Features</a>
        <a href="#">Security</a>
        <a href="#">Pricing</a>
    </nav>
    <div class="auth-buttons auth-entrance auth-e-1">
        <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view" class="sign-in-link">Sign In</a>
        <a href="<?= _WEB_ROOT ?>?template=auth&action=register.view" class="btn-get-started">Get Started</a>
    </div>
</header>

<main class="main-content reset-shell auth-page">
    <div class="bg-blobs" aria-hidden="true">
        <div class="blob blob-top-right"></div>
        <div class="blob blob-bottom-left"></div>
    </div>

    <div class="reset-visual" aria-hidden="true">
        <svg viewBox="0 0 400 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="rsg1" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#0d9488"/>
                    <stop offset="100%" stop-color="#0f766e"/>
                </linearGradient>
                <linearGradient id="rsg2" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#38bdf8"/>
                    <stop offset="100%" stop-color="#0ea5e9"/>
                </linearGradient>
            </defs>
            <circle cx="360" cy="20" r="50" fill="#0d9488" opacity="0.04"/>
            <g transform="translate(60, 10)" style="animation: resetLockBob 4s ease-in-out infinite">
                <rect x="0" y="18" width="40" height="32" rx="6" fill="url(#rsg1)" opacity="0.9"/>
                <path d="M6 22 L6 12 Q6 0 20 0 Q34 0 34 12 L34 22" fill="none" stroke="url(#rsg1)" stroke-width="3.5" stroke-linecap="round"/>
                <circle cx="20" cy="34" r="5" fill="#fff">
                    <animate attributeName="r" values="4;6;4" dur="2s" repeatCount="indefinite"/>
                </circle>
                <line x1="20" y1="36" x2="20" y2="44" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/>
                <!-- Key turning -->
                <g transform="translate(48, 22)">
                    <circle cx="8" cy="8" r="7" fill="url(#rsg2)"/>
                    <rect x="13" y="6" width="18" height="4" rx="2" fill="#64748b"/>
                    <rect x="25" y="3" width="4" height="10" rx="1.5" fill="#64748b"/>
                </g>
            </g>
            <g transform="translate(180, 15)" style="animation: resetShieldBob 5s ease-in-out infinite 0.5s">
                <path d="M50 0 L100 25 L100 75 Q100 120 50 140 Q0 120 0 75 L0 25 Z"
                    fill="url(#rsg1)" opacity="0.15"/>
                <path d="M50 8 L90 28 L90 70 Q90 105 50 125 Q10 105 10 70 L10 28 Z"
                    fill="none" stroke="url(#rsg1)" stroke-width="2"/>
                <text x="50" y="72" text-anchor="middle" font-size="12" font-weight="700" fill="#0d9488">OTP</text>
            </g>
            <g transform="translate(300, 25)" style="animation: resetCheckPulse 2.5s ease-in-out infinite">
                <circle cx="20" cy="20" r="18" fill="#10b981" opacity="0.1"/>
                <circle cx="20" cy="20" r="12" fill="#10b981" opacity="0.2"/>
                <path d="M12 20 L17 25 L28 14" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
        </svg>
    </div>

    <div class="card-wrapper">
        <div class="card-outer auth-entrance-scale auth-e-2">
            <div class="card-inner">
                <div class="card-header auth-entrance auth-e-3">
                    <div class="icon-circle">
                        <span class="material-symbols-outlined">vpn_key</span>
                    </div>
                    <h1>Reset your password</h1>
                    <p>Choose a new password — at least 6 characters to keep your account secure.</p>
                </div>

                <?php if(!empty($message)): ?>
                    <?php echo showMessage($message, $message_type); ?>
                <?php endif; ?>

                <form class="reset-form" method="POST" action="<?= _WEB_ROOT ?>?template=auth&action=reset" id="resetPasswordForm">
                    <input type="hidden" name="reset" value="<?= $token ?>">

                    <div class="form-group auth-entrance auth-e-4">
                        <label for="password">New password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">lock</span>
                            <input id="password" name="password" type="password" placeholder="••••••••" autocomplete="new-password" />
                        </div>
                        <?= form_error($errors, 'password') ?>
                    </div>

                    <div class="form-group auth-entrance auth-e-5">
                        <label for="confirm_password">Confirm password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">enhanced_encryption</span>
                            <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" autocomplete="new-password" />
                        </div>
                        <?= form_error($errors, 'confirm_password') ?>
                    </div>

                    <button type="submit" class="btn-submit auth-entrance auth-e-6" name="btn-reset">
                        Update password
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>

                <div class="back-to-login auth-entrance auth-e-7">
                    <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view">
                        <span class="material-symbols-outlined back-icon">arrow_back</span>
                        Back to login
                    </a>
                </div>
            </div>
        </div>

        <div class="security-badges auth-entrance auth-e-7">
            <span>
                <span class="material-symbols-outlined">verified_user</span>
                Secure encryption
            </span>
            <span>
                <span class="material-symbols-outlined">shield_lock</span>
                Account protection
            </span>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="footer-logo auth-entrance auth-e-7">Ledger</div>
    <div class="footer-links auth-entrance auth-e-7">
        <a href="#">Support</a>
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Security</a>
    </div>
    <div class="footer-copyright auth-entrance auth-e-8">
        &copy; <?= date('Y') ?> Ledger. All rights reserved.
    </div>
</footer>

<?php
    layout("footer", ["js" => ["pages/reset"]]);
?>
<?php endif; ?>
