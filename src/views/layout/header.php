<?php
if (!CODE) die('Ban khong co quyen truy cap vao trang nay');

$loginToken = getSession('loginToken');
$userId = (int)(getSession('id') ?? 0);
$globalNotification = null;
$notificationMeta = [];
$notificationDuration = 0;
$userRole = getSession('role');

if (!empty($loginToken)) {
    cleanupNotifications();

    if ($userRole !== 'admin') {
        $globalNotification = getActiveNotification();

        if ($globalNotification) {
            $notificationTypeMap = getNotificationTypeMap();
            $notificationType    = normalizeNotificationType($globalNotification['type'] ?? 'info');
            $notificationMeta    = $notificationTypeMap[$notificationType];
            $notificationDuration = TOAST_DISPLAY_SECONDS;
        }
    }

    archiveExpiredTransactions($userId);
    purgeArchivedTransactions($userId);
    archiveOldFeedbacks();
    purgeOldArchivedFeedbacks();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <script>(function(){var t=localStorage.getItem('theme');var tmpl='<?= $_GET['template'] ?? '' ?>';if(tmpl==='auth'||tmpl==='user')t='light';if(tmpl==='admin')t='dark';var html=document.documentElement;if(t==='dark'||t==='light'){html.setAttribute('data-theme',t);}html.style.backgroundColor=t==='dark'?'#0c0a09':'#f0fdfa';document.addEventListener('DOMContentLoaded',function(){var app=document.querySelector('.app-container');if(app&&t){app.setAttribute('data-theme',t);}});})();</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Quan ly chi tieu' ?></title>
    <link rel="stylesheet" href="<?= _CSS ?>main.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <?php
    if (!empty($css)) {
        foreach ($css as $item) {
            echo '<link rel="stylesheet" href="' . _CSS . $item . '.css?v=' . time() . '">' . "\n";
        }
    }
    ?>
    <link rel="stylesheet" href="<?= _CSS ?>themes.css?v=<?= time() ?>">
    <style>
        @keyframes slideInRight {
            from { transform: translate3d(120%, 0, 0); opacity: 0; }
            to { transform: translate3d(0, 0, 0); opacity: 1; }
        }

        @keyframes slideOutRight {
            from { transform: translate3d(0, 0, 0); opacity: 1; }
            to { transform: translate3d(120%, 0, 0); opacity: 0; }
        }

        @keyframes toastProgress {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        #global-toast {
            display: none;
            position: fixed;
            right: 28px;
            top: 110px;
            width: min(420px, calc(100vw - 32px));
            color: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.24);
            z-index: 10000;
        }

        #global-toast.toast-visible {
            display: block;
            animation: slideInRight 0.38s ease forwards;
        }

        #global-toast.toast-hiding {
            animation: slideOutRight 0.3s ease forwards;
        }

        .global-toast-shell {
            display: flex;
            gap: 14px;
            align-items: flex-start;
            padding: 18px 18px 16px;
        }

        .global-toast-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.18);
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .global-toast-content {
            flex: 1;
            min-width: 0;
        }

        .global-toast-label {
            margin: 0 0 6px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.92;
        }

        .global-toast-message {
            margin: 0;
            font-size: 15px;
            line-height: 1.55;
            word-break: break-word;
        }

        .global-toast-meta {
            margin-top: 8px;
            font-size: 12px;
            opacity: 0.84;
        }

        .global-toast-close {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 20px;
            line-height: 1;
        }

        .global-toast-progress {
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
        }

        .global-toast-progress > span {
            display: block;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            transform-origin: left center;
        }

        .global-toast-progress > span.is-running {
            animation-name: toastProgress;
            animation-timing-function: linear;
            animation-fill-mode: forwards;
        }

        @media (max-width: 640px) {
            #global-toast {
                left: 16px;
                right: 16px;
                top: 90px;
                width: auto;
            }
        }
    </style>
</head>
<body
    data-user-hash="<?= $userId > 0 ? substr(hash('sha256', 'user_' . $userId . '_' . USER_HASH_SALT), 0, 16) : '0' ?>"
    data-role="<?= htmlspecialchars($userRole ?? '') ?>"
>
<?php if (!empty($loginToken)): ?>
<link rel="stylesheet" href="<?= _CSS ?>pages/user/feedback.css?v=<?= time() ?>">
<?php endif; ?>

<?php if ($globalNotification): ?>
    <div
        id="global-toast"
        data-notification-id="<?= (int)$globalNotification['id'] ?>"
        data-user-hash="<?= $userId > 0 ? substr(hash('sha256', 'user_' . $userId . '_' . USER_HASH_SALT), 0, 16) : '0' ?>"
        data-duration="<?= $notificationDuration ?>"
        data-seen-key="notif_seen_user_<?= $userId > 0 ? substr(hash('sha256', 'user_' . $userId . '_' . USER_HASH_SALT), 0, 16) : '0' ?>_<?= (int)$globalNotification['id'] ?>"
        style="background: <?= htmlspecialchars($notificationMeta['toast_gradient'], ENT_QUOTES) ?>; box-shadow: 0 18px 48px <?= htmlspecialchars($notificationMeta['toast_shadow'], ENT_QUOTES) ?>;"
    >
        <div class="global-toast-shell">
            <div class="global-toast-icon"><?= htmlspecialchars($notificationMeta['toast_icon']) ?></div>
            <div class="global-toast-content">
                <p class="global-toast-label">Thông báo hệ thống</p>
                <p class="global-toast-message"><?= htmlspecialchars($globalNotification['message']) ?></p>
            </div>
            <button id="global-toast-close" class="global-toast-close" type="button" aria-label="Đóng thông báo">&times;</button>
        </div>
        <div class="global-toast-progress">
            <span id="global-toast-progress-bar"></span>
        </div>
    </div>

    <script>
        (function () {
            var toast = document.getElementById('global-toast');
            var closeBtn = document.getElementById('global-toast-close');
            var progressBar = document.getElementById('global-toast-progress-bar');

            if (!toast) {
                return;
            }

            var duration = Math.max(parseInt(toast.getAttribute('data-duration') || '8', 10), 3) * 1000;
            var seenKey = toast.getAttribute('data-seen-key');
            var hideTimer = null;
            var hideDelayTimer = null;

            function finalizeHide() {
                toast.classList.remove('toast-visible', 'toast-hiding');
                toast.style.display = 'none';
                if (progressBar) {
                    progressBar.classList.remove('is-running');
                    progressBar.style.animationDuration = '';
                }
            }

            function hideToast() {
                if (hideTimer) {
                    clearTimeout(hideTimer);
                }
                if (hideDelayTimer) {
                    clearTimeout(hideDelayTimer);
                }

                toast.classList.remove('toast-visible');
                toast.classList.add('toast-hiding');
                hideDelayTimer = setTimeout(finalizeHide, 320);
            }

            if (!localStorage.getItem(seenKey)) {
                localStorage.setItem(seenKey, '1');
                toast.classList.add('toast-visible');

                if (progressBar) {
                    progressBar.style.animationDuration = duration + 'ms';
                    progressBar.classList.add('is-running');
                }

                hideTimer = setTimeout(hideToast, duration);
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', hideToast);
            }
        })();
    </script>
<?php endif; ?>
