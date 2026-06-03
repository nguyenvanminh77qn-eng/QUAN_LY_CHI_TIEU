<?php
if (!CODE) die('Ban khong co quyen truy cap vao trang nay');

if (empty(getSession('loginToken'))) {
    setMessage("Ban phai dang nhap", "error");
    redirect("?template=auth&action=login.view");
}

if (getSession('role') !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=user&action=dashboard");
}

layout("header", [
    "title" => "Quản lý thông báo",
    "css" => ["layout/sidebar", "pages/admin/theme"]
]);

$view = 'notifications';
$username = getSession('username');

cleanupNotifications();

$notificationTypeMap = getNotificationTypeMap();
$activeNotification  = getActiveNotification();
$notificationHistory = getNotificationHistory();
$defaultExpiryValue  = date('Y-m-d\TH:i', strtotime('+1 hour'));

$message = getFlashData("message");
$message_type = getFlashData("message_type");
?>

<div class="app-container">
    <?php layout("sidebar_admin", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">ADMINISTRATOR</span>
                    <h1>Quản lý thông báo</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box"><?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content">
            <?php if (!empty($message)) echo showMessage($message, $message_type); ?>

            <div class="card-box" style="margin-bottom:25px; padding:24px;">
                <h3 style="margin:0 0 6px; font-size:18px; color:rgba(245,240,235,0.85);">📢 Phát thông báo mới</h3>
                <p style="margin:0 0 20px; font-size:13px; color:rgba(245,240,235,0.5); line-height:1.6;">Chỉ có 1 thông báo được phát tại 1 thời điểm. Khi phát mới, thông báo đang active sẽ được đưa vào lịch sử.</p>

                <form action="?template=admin&action=notifications" method="POST" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; align-items:flex-end;">
                    <div style="flex:1; min-width:260px;">
                        <label style="display:block; font-size:11px; font-weight:600; color:rgba(245,240,235,0.4); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Nội dung</label>
                        <input type="text" name="message" placeholder="Nhập nội dung thông báo..." style="width:100%; padding:10px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:14px; background:rgba(13,13,13,0.5); color:#f5f0eb; box-sizing:border-box; outline:none;" required>
                    </div>
                    <div style="min-width:170px;">
                        <label style="display:block; font-size:11px; font-weight:600; color:rgba(245,240,235,0.4); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Loại</label>
                        <select name="type" style="width:100%; padding:10px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:14px; background:rgba(13,13,13,0.5); color:#f5f0eb; outline:none;">
                            <option value="info" style="color:#1a1512; background:#fff;">Thông tin</option>
                            <option value="warning" style="color:#1a1512; background:#fff;">Cảnh báo</option>
                            <option value="success" style="color:#1a1512; background:#fff;">Thành công</option>
                            <option value="error" style="color:#1a1512; background:#fff;">Khẩn cấp</option>
                        </select>
                    </div>
                    <div style="width:210px;">
                        <label style="display:block; font-size:11px; font-weight:600; color:rgba(245,240,235,0.4); margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;">Hết hạn lúc</label>
                        <input type="datetime-local" name="expires_at" value="<?= $defaultExpiryValue ?>" style="width:100%; padding:10px 14px; border:1px solid var(--border-color); border-radius:8px; font-size:14px; background:rgba(13,13,13,0.5); color:#f5f0eb; box-sizing:border-box; outline:none;" required>
                    </div>
                    <div>
                        <button type="submit" name="broadcast_notification" style="background:linear-gradient(135deg, #b8922e, #d4a843); color:#0d0d0d; padding:10px 24px; border:none; border-radius:8px; cursor:pointer; font-weight:700; font-size:14px; height:44px; box-shadow:0 4px 14px rgba(212,168,67,0.2); transition:all 0.2s;">
                            📢 Phát thông báo
                        </button>
                    </div>
                </form>

                <?php if ($activeNotification): ?>
                    <?php
                    $activeType  = normalizeNotificationType($activeNotification['type'] ?? 'info');
                    $activeStyle = $notificationTypeMap[$activeType];
                    ?>
                    <div style="margin-bottom:18px;">
                        <h4 style="margin:0 0 10px; font-size:15px; color:rgba(245,240,235,0.7); display:flex; align-items:center; gap:8px;">
                            📡 Thông báo đang phát
                        </h4>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 18px; border-radius:10px; background:<?= $activeStyle['admin_bg'] ?>; border-left:5px solid <?= $activeStyle['admin_border'] ?>; color:<?= $activeStyle['admin_text'] ?>;">
                            <div style="flex:1;">
                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px;">
                                    <strong style="font-size:16px;"><?= htmlspecialchars($activeNotification['message']) ?></strong>
                                    <span style="display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; background:rgba(0,0,0,0.15); font-size:11px; font-weight:700; text-transform:uppercase;"><?= htmlspecialchars($activeStyle['label']) ?></span>
                                    <span style="display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; background:#2ecc71; color:#fff; font-size:11px; font-weight:700; text-transform:uppercase;">● Đang phát</span>
                                </div>
                                <div style="font-size:13px; opacity:0.88; line-height:1.6;">
                                    Hết hạn: <?= date('d/m/Y H:i', strtotime($activeNotification['expires_at'])) ?> |
                                    Bởi: <strong><?= htmlspecialchars($activeNotification['created_by_name'] ?? 'Không rõ') ?></strong>
                                </div>
                            </div>
                            <form action="?template=admin&action=notifications" method="POST" style="margin:0;">
                                <input type="hidden" name="disable_notification" value="<?= (int)$activeNotification['id'] ?>">
                                <button type="submit" style="background:#e74c3c; color:#fff; padding:8px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:700; transition:all 0.2s;">Tắt ngay</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <h4 style="margin:0 0 6px; font-size:15px; color:rgba(245,240,235,0.7); display:flex; align-items:center; gap:8px;">
                        📋 Lịch sử thông báo
                    </h4>
                    <p style="margin:0 0 14px; font-size:12px; color:rgba(245,240,235,0.3); font-style:italic;">Những thông báo quá 7 ngày kể từ ngày hết hạn sẽ bị xoá.</p>
                    <?php if (!empty($notificationHistory)): ?>
                        <div style="display:flex; flex-direction:column; gap:10px; max-height:400px; overflow-y:auto; padding-right:4px;">
                            <?php foreach ($notificationHistory as $notif): ?>
                                <?php
                                $notifType  = normalizeNotificationType($notif['type'] ?? 'info');
                                $notifStyle = $notificationTypeMap[$notifType];
                                ?>
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 16px; border-radius:10px; background:<?= $notifStyle['admin_bg'] ?>; border-left:5px solid <?= $notifStyle['admin_border'] ?>; color:<?= $notifStyle['admin_text'] ?>;">
                                    <div style="flex:1;">
                                        <div style="font-weight:700; font-size:15px; margin-bottom:5px;"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div style="font-size:12px; opacity:0.86; line-height:1.6;">
                                            Tạo lúc: <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?> |
                                            Hết hạn: <?= date('d/m/Y H:i', strtotime($notif['expires_at'])) ?> |
                                            Bởi: <strong><?= htmlspecialchars($notif['created_by_name'] ?? 'Không rõ') ?></strong>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                        <span style="display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; background:rgba(0,0,0,0.15); font-size:11px; font-weight:700; text-transform:uppercase;"><?= htmlspecialchars($notifStyle['label']) ?></span>
                                        <span style="display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; background:rgba(255,255,255,0.08); color:rgba(245,240,235,0.5); font-size:11px; font-weight:700; text-transform:uppercase;">Lưu lịch sử</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding:16px; border:1px dashed rgba(58,50,42,0.6); border-radius:10px; color:rgba(245,240,235,0.3); background:transparent; text-align:center; font-size:14px;">Chưa có thông báo nào trong lịch sử.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
layout("footer", ["js" => ["pages/sidebar"]]);
?>
