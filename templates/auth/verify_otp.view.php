<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

layout("header", [
    "title" => "Xác thực OTP",
    "css" => ["pages/auth/verify-otp", "pages/auth/auth-entrance"]
]);

$message = getFlashData("message");
$message_type = getFlashData("message_type");
?>

<main class="otp-page auth-page">
    <div class="otp-deco" aria-hidden="true">
        <svg viewBox="0 0 420 420" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="370" cy="50" r="80" fill="#0d9488" opacity="0.05"/>
            <circle cx="50" cy="370" r="60" fill="#38bdf8" opacity="0.04"/>
            <g transform="translate(340, 80)" style="animation: otpDecoBob 4s ease-in-out infinite">
                <path d="M0 0 L5 -14 L10 0 L24 5 L10 10 L5 24 L0 10 L-14 5 Z" fill="#0d9488" opacity="0.2"/>
            </g>
            <g transform="translate(30, 60)" style="animation: otpDecoBob 4.5s ease-in-out infinite 0.5s">
                <path d="M0 0 L4 -10 L8 0 L18 4 L8 8 L4 18 L0 8 L-10 4 Z" fill="#f59e0b" opacity="0.15"/>
            </g>
            <g transform="translate(380, 360)" style="animation: otpDecoBob 5s ease-in-out infinite 1s">
                <path d="M0 0 L3 -8 L6 0 L14 3 L6 6 L3 14 L0 6 L-8 3 Z" fill="#38bdf8" opacity="0.15"/>
            </g>
            <g transform="translate(60, 300)" style="animation: otpDecoBob 3.5s ease-in-out infinite 0.8s">
                <path d="M0 0 L3 -8 L6 0 L14 3 L6 6 L3 14 L0 6 L-8 3 Z" fill="#10b981" opacity="0.12"/>
            </g>
            <g transform="translate(260, 20)">
                <circle cx="0" cy="0" r="3" fill="#0d9488" opacity="0.25"
                    style="animation: otpDotPulse 2s ease-in-out infinite"/>
            </g>
            <g transform="translate(380, 180)">
                <circle cx="0" cy="0" r="4" fill="#38bdf8" opacity="0.2"
                    style="animation: otpDotPulse 2.5s ease-in-out infinite 0.3s"/>
            </g>
            <g transform="translate(20, 180)">
                <circle cx="0" cy="0" r="3" fill="#f59e0b" opacity="0.2"
                    style="animation: otpDotPulse 1.8s ease-in-out infinite 0.6s"/>
            </g>
        </svg>
    </div>
    <div class="otp-container">
        <div class="otp-card auth-entrance-scale auth-e-1">
            <div class="otp-header auth-entrance auth-e-2">
                <div class="otp-icon">
                    <span class="material-symbols-outlined">verified_user</span>
                </div>
                <h1>Xác thực OTP</h1>
                <p>Một mã OTP gồm 6 chữ số đã được gửi tới email của bạn.<br>Vui lòng nhập mã để hoàn tất đăng nhập.</p>
            </div>

            <div id="alert-area" class="auth-entrance auth-e-3">
                <?php if(!empty($message)): ?>
                    <?php echo showMessage($message, $message_type); ?>
                <?php endif; ?>
            </div>

            <form class="otp-form" id="verify-form">
                <div class="otp-input-wrapper auth-entrance auth-e-4">
                    <input type="text" id="otp" placeholder="000000" name="otp" required maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code">
                </div>

                <button type="submit" class="btn-otp auth-entrance auth-e-5" id="btn-verify">
                    <span>Xác nhận</span>
                    <span class="material-symbols-outlined" style="font-size:18px">arrow_forward</span>
                </button>
            </form>

            <div class="otp-divider auth-entrance auth-e-6">
                <span>Không nhận được mã?</span>
            </div>

            <button type="button" id="btn-resend" class="btn-resend auth-entrance auth-e-7" disabled>
                <span class="material-symbols-outlined" style="font-size:18px">refresh</span>
                Gửi lại OTP (<span id="resend-countdown">60</span>s)
            </button>

            <div class="otp-footer auth-entrance auth-e-8">
                <a href="?template=auth&action=login.view">
                    <span class="material-symbols-outlined" style="font-size:16px">arrow_back</span>
                    Quay lại Đăng nhập
                </a>
            </div>
        </div>
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
            resendBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">refresh</span> Gửi lại OTP (<span id="resend-countdown">' + countdownValue + '</span>s)';

            if (countdownValue <= 0) {
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">refresh</span> Gửi lại OTP';
                countdownTimer = null;
                return;
            }
            countdownValue--;
            countdownTimer = setTimeout(tick, 1000);
        }
        tick();
    }

    resendBtn.addEventListener('click', function() {
        resendBtn.disabled = true;
        resendBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">refresh</span> Đang gửi...';

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
                var s = countdownValue;
                if (s <= 0) {
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px">refresh</span> Gửi lại OTP';
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

    document.getElementById('verify-form').addEventListener('submit', function(e) {
        e.preventDefault();
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<span>Đang xác thực...</span>';

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
            verifyBtn.innerHTML = '<span>Xác nhận</span><span class="material-symbols-outlined" style="font-size:18px">arrow_forward</span>';

            if (data.success) {
                showAlert(data.message, 'success');
                window.location.href = data.redirect;
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(function() {
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<span>Xác nhận</span><span class="material-symbols-outlined" style="font-size:18px">arrow_forward</span>';
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
