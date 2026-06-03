<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Quên mật khẩu",
        "css" => ["pages/auth/forget", "pages/auth/auth-entrance"]
    ]);
    $message = getFlashData("message");
    $message_type = getFlashData("message_type");
    $errors = getFlashData("errors");
    $old = getFlashData("old");
?>

<main class="forget-page auth-page">
    <div class="forget-page__container">
        <section class="forget-page__intro auth-entrance-left auth-e-1">
            <div class="forget-page__intro-content">
                <div class="forget-page__icon-badge">
                    <span class="material-symbols-outlined">shield_lock</span>
                </div>
                <h1 class="forget-page__title">Khôi phục<br>tài khoản của bạn.</h1>
                <p class="forget-page__description">
                    Đừng lo lắng, việc quên mật khẩu là chuyện bình thường. Chúng tôi sẽ gửi hướng dẫn đặt lại mật khẩu qua email cho bạn.
                </p>
                <div class="forget-page__features">
                    <div class="forget-page__feature auth-entrance auth-e-3">
                        <span class="material-symbols-outlined">verified_user</span>
                        <span>Bảo mật tuyệt đối</span>
                    </div>
                    <div class="forget-page__feature auth-entrance auth-e-4">
                        <span class="material-symbols-outlined">bolt</span>
                        <span>Nhanh chóng & dễ dàng</span>
                    </div>
                    <div class="forget-page__feature auth-entrance auth-e-5">
                        <span class="material-symbols-outlined">support_agent</span>
                        <span>Hỗ trợ 24/7</span>
                    </div>
                </div>

                <div class="forget-visual">
                    <svg viewBox="0 0 360 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="fg1" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#0d9488"/>
                                <stop offset="100%" stop-color="#0f766e"/>
                            </linearGradient>
                            <linearGradient id="fg2" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#38bdf8"/>
                                <stop offset="100%" stop-color="#0ea5e9"/>
                            </linearGradient>
                        </defs>
                        <circle cx="320" cy="40" r="60" fill="#0d9488" opacity="0.04"/>
                        <!-- Shield -->
                        <g transform="translate(30, 20)">
                            <path d="M70 0 L140 30 L140 80 Q140 130 70 150 Q0 130 0 80 L0 30 Z"
                                fill="url(#fg1)" opacity="0.9"/>
                            <path d="M70 15 L125 38 L125 78 Q125 118 70 135 Q15 118 15 78 L15 38 Z"
                                fill="rgba(255,255,255,0.85)"/>
                            <!-- Lock icon inside shield -->
                            <rect x="55" y="58" width="30" height="22" rx="4" fill="url(#fg1)"/>
                            <path d="M60 62 L60 52 Q60 42 70 42 Q80 42 80 52 L80 62"
                                fill="none" stroke="url(#fg1)" stroke-width="3" stroke-linecap="round"/>
                            <circle cx="70" cy="72" r="4" fill="#fff">
                                <animate attributeName="r" values="3;5;3" dur="2s" repeatCount="indefinite"/>
                            </circle>
                            <path d="M70 74 L70 82" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                            <!-- Checkmark appearing -->
                            <path d="M55 55 L65 65 L85 45" fill="none" stroke="#10b981" stroke-width="4"
                                stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="50" stroke-dashoffset="50">
                                <animate attributeName="stroke-dashoffset" from="50" to="0" dur="1.5s" begin="0.5s" fill="freeze"/>
                            </path>
                        </g>
                        <!-- Key icon -->
                        <g transform="translate(200, 45)" style="animation: forgetKeyBob 3s ease-in-out infinite">
                            <circle cx="20" cy="20" r="16" fill="url(#fg2)" opacity="0.9"/>
                            <rect x="32" y="17" width="28" height="6" rx="3" fill="#64748b"/>
                            <rect x="50" y="12" width="6" height="16" rx="2" fill="#64748b"/>
                            <rect x="42" y="17" width="4" height="6" rx="1" fill="#64748b"/>
                            <!-- Glow -->
                            <circle cx="20" cy="20" r="20" fill="none" stroke="url(#fg2)" stroke-width="2" opacity="0.3">
                                <animate attributeName="r" values="18;24;18" dur="2s" repeatCount="indefinite"/>
                                <animate attributeName="opacity" values="0.3;0.1;0.3" dur="2s" repeatCount="indefinite"/>
                            </circle>
                        </g>
                        <!-- Email envelope -->
                        <g transform="translate(260, 50)">
                            <rect x="0" y="5" width="50" height="35" rx="5" fill="#e2e8f0"/>
                            <path d="M0 5 L25 28 L50 5" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="25" cy="22" r="3" fill="#0d9488">
                                <animate attributeName="opacity" values="0.3;1;0.3" dur="1.5s" repeatCount="indefinite"/>
                            </circle>
                        </g>
                        <!-- Decorative dots -->
                        <circle cx="35" cy="155" r="4" fill="#0d9488" opacity="0.2"
                            style="animation: forgetDotPulse 2s ease-in-out infinite"/>
                        <circle cx="160" cy="160" r="5" fill="#38bdf8" opacity="0.15"
                            style="animation: forgetDotPulse 2.5s ease-in-out infinite 0.3s"/>
                        <circle cx="300" cy="150" r="3" fill="#f59e0b" opacity="0.2"
                            style="animation: forgetDotPulse 1.8s ease-in-out infinite 0.6s"/>
                    </svg>
                </div>
            </div>
        </section>

        <section class="forget-page__form-section auth-entrance-right auth-e-2">
            <div class="forget-card">
                <header class="forget-card__header auth-entrance auth-e-3">
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
                    <div class="form-group auth-entrance auth-e-4">
                        <label class="form-group__label" for="email">Địa chỉ Email</label>
                        <div class="form-group__input-wrapper">
                            <span class="material-symbols-outlined form-group__icon">mail</span>
                            <input class="form-group__input" type="email" id="email" name="email" placeholder="name@gmail.com" value="<?= old($old, 'email') ?>">
                        </div>
                        <?= form_error($errors, 'email') ?>
                    </div>

                    <button type="submit" class="btn-submit auth-entrance auth-e-5" name="btn-forget">
                        <span>Gửi liên kết đặt lại</span>
                        <span class="material-symbols-outlined btn-submit__icon">arrow_forward</span>
                    </button>
                </form>

                <div class="forget-card__divider auth-entrance auth-e-6">
                    <span class="forget-card__divider-text">Hoặc</span>
                </div>

                <div class="forget-card__footer auth-entrance auth-e-7">
                    <a href="<?= _WEB_ROOT ?>?template=auth&action=login.view" class="forget-card__back-link">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <span>Quay lại đăng nhập</span>
                    </a>
                </div>

                <div class="forget-card__register auth-entrance auth-e-8">
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
