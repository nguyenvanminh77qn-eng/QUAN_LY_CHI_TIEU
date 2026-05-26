<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", [
        "title" => "Reset password",
        "css" => ["pages/auth/reset"]
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
    <div class="logo">Ledger</div>
    <nav class="nav-links">
        <a href="#">Features</a>
        <a href="#">Security</a>
        <a href="#">Pricing</a>
    </nav>
    <div class="auth-buttons">
        <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view" class="sign-in-link">Sign In</a>
        <a href="<?= _WEB_ROOT ?>?template=auth&action=register.view" class="btn-get-started">Get Started</a>
    </div>
</header>

<main class="main-content reset-shell">
    <div class="bg-blobs" aria-hidden="true">
        <div class="blob blob-top-right"></div>
        <div class="blob blob-bottom-left"></div>
    </div>

    <div class="card-wrapper">
        <div class="card-outer">
            <div class="card-inner">
                <div class="card-header">
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

                    <div class="form-group">
                        <label for="password">New password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">lock</span>
                            <input id="password" name="password" type="password" placeholder="••••••••" autocomplete="new-password" />
                        </div>
                        <?= form_error($errors, 'password') ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">enhanced_encryption</span>
                            <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" autocomplete="new-password" />
                        </div>
                        <?= form_error($errors, 'confirm_password') ?>
                    </div>

                    <button type="submit" class="btn-submit" name="btn-reset">
                        Update password
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>

                <div class="back-to-login">
                    <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view">
                        <span class="material-symbols-outlined back-icon">arrow_back</span>
                        Back to login
                    </a>
                </div>
            </div>
        </div>

        <div class="security-badges">
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
    <div class="footer-logo">Ledger</div>
    <div class="footer-links">
        <a href="#">Support</a>
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Security</a>
    </div>
    <div class="footer-copyright">
        © <?= date('Y') ?> Ledger. All rights reserved.
    </div>
</footer>

<?php
    layout("footer", ["js" => ["pages/reset"]]);
?>
<?php endif; ?>
