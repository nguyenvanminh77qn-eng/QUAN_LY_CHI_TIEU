<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

layout("header", [
    "title" => "Xác thực OTP",
    "css" => ["pages/auth/login"]
]);

$message = getFlashData("message");
$message_type = getFlashData("message_type");
?>

<main class="login-container">
    <header class="login-header">
        <h1 class="login-header__title">Xác thực OTP</h1>
    </header>

    <div id="alert-area">
        <?php if(!empty($message)): ?>
            <?php echo showMessage($message, $message_type); ?>
        <?php endif; ?>
    </div>

    <div class="login-card">
        <p style="text-align: center; margin-bottom: 20px; color: #555;">
            Một mã OTP gồm 6 chữ số đã được gửi tới email của bạn. Vui lòng nhập mã để hoàn tất đăng nhập.
        </p>

        <form class="login-form" id="verify-form">
            <div class="form-group">
                <label for="otp" class="form-group__label">Mã OTP</label>
                <input type="text" id="otp" class="form-group__input" placeholder="123456" name="otp" required maxlength="6" pattern="\d{6}" style="text-align: center; font-size: 24px; letter-spacing: 5px;" inputmode="numeric" autocomplete="one-time-code">
            </div>

            <button type="submit" class="btn btn--primary" id="btn-verify">Xác nhận</button>
        </form>

        <div class="divider" style="margin-top: 20px;"></div>

        <div style="text-align: center; margin-top: 16px;">
            <button type="button" id="btn-resend" class="btn btn--primary" style="background: #95a5a6; padding: 10px 24px; border: none; border-radius: 6px; color: #fff; font-weight: 600; cursor: pointer; font-size: 14px;" disabled>
                Gửi lại OTP (<span id="resend-countdown">60</span>s)
            </button>
        </div>

        <div class="divider" style="margin-top: 20px;"></div>

        <p style="text-align: center; font-size: 14px;">
            <a href="?template=auth&action=login.view" class="form-group__link">Quay lại Đăng nhập</a>
        </p>
    </div>
</main>

<script>
(function() {
    var COOLDOWN = 60;
    var resendBtn = document.getElementById('btn-resend');
    var verifyBtn = document.getElementById('btn-verify');
    var otpInput = document.getElementById('otp');
    var alertArea = document.getElementById('alert-area');
    var countdownValue = COOLDOWN;
    var countdownTimer = null;

    function showAlert(message, type) {
        alertArea.innerHTML = '<div class="alert alert-' + type + '">' + message + '</div>';
    }

    function startCountdown(seconds) {
        if (countdownTimer) clearTimeout(countdownTimer);
        countdownValue = seconds;
        resendBtn.disabled = true;

        function tick() {
            resendBtn.innerHTML = 'Gửi lại OTP (<span id="resend-countdown">' + countdownValue + '</span>s)';

            if (countdownValue <= 0) {
                resendBtn.disabled = false;
                resendBtn.innerHTML = 'Gửi lại OTP';
                countdownTimer = null;
                return;
            }
            countdownValue--;
            countdownTimer = setTimeout(tick, 1000);
        }
        tick();
    }

    // Gửi lại OTP (AJAX)
    resendBtn.addEventListener('click', function() {
        resendBtn.disabled = true;
        resendBtn.innerHTML = 'Đang gửi...';

        var formData = new FormData();
        formData.append('btn-resend-otp', '1');
        formData.append('ajax', '1');

        fetch('?template=auth&action=verify_otp', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                startCountdown(data.cooldown || COOLDOWN);
                showAlert(data.message, 'success');
            } else {
                showAlert(data.message, 'error');
                // Khôi phục nút nếu lỗi
                var s = countdownValue;
                if (s <= 0) {
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = 'Gửi lại OTP';
                } else {
                    startCountdown(s);
                }
            }
        })
        .catch(function() {
            showAlert('Lỗi kết nối. Vui lòng thử lại.', 'error');
            startCountdown(5);
        });
    });

    // Xác thực OTP (AJAX)
    document.getElementById('verify-form').addEventListener('submit', function(e) {
        e.preventDefault();
        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Đang xác thực...';

        var formData = new FormData();
        formData.append('btn-verify-otp', '1');
        formData.append('ajax', '1');
        formData.append('otp', otpInput.value);

        fetch('?template=auth&action=verify_otp', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Xác nhận';

            if (data.success) {
                showAlert(data.message, 'success');
                window.location.href = data.redirect;
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(function() {
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Xác nhận';
            showAlert('Lỗi kết nối. Vui lòng thử lại.', 'error');
        });
    });

    var initialSeconds = COOLDOWN;
    var sentAt = <?= (int)(getSession('otp_sent_at') ?: 0) ?>;
    if (sentAt > 0) {
        var elapsed = Math.floor(Date.now() / 1000) - sentAt;
        initialSeconds = Math.max(0, COOLDOWN - elapsed);
    }
    startCountdown(initialSeconds);
})();
</script>

<?php
layout("footer", ["js" => []]);
?>
