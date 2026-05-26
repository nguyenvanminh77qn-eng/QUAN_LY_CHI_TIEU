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
    "title" => "Admin Dashboard",
    "css" => ["layout/sidebar", "pages/user/dashboard", "pages/admin/dashboard"]
]);

$view = 'dashboard';
$username = getSession('username');

cleanupNotifications();

$totalUsers = countRows("SELECT id FROM user");
$totalTransactions = countRows("SELECT id FROM transaction");
$totalIncome = getOne("SELECT SUM(price) as total FROM transaction WHERE type = 'income'")['total'] ?? 0;
$totalExpense = getOne("SELECT SUM(price) as total FROM transaction WHERE type = 'expense'")['total'] ?? 0;

$catStats = getAll("
    SELECT
        c.name,
        COUNT(t.id) as usage_count,
        SUM(CASE WHEN t.type = 'income' THEN t.price ELSE 0 END) as income_val,
        SUM(CASE WHEN t.type = 'expense' THEN t.price ELSE 0 END) as expense_val
    FROM category c
    LEFT JOIN transaction t ON c.id = t.category_id
    GROUP BY c.id
    HAVING usage_count > 0
    ORDER BY usage_count DESC
    LIMIT 8
");

$labels = [];
$usageData = [];
$incomeData = [];
$expenseData = [];
foreach ($catStats as $stat) {
    $labels[] = $stat['name'];
    $usageData[] = (int)$stat['usage_count'];
    $incomeData[] = (float)$stat['income_val'];
    $expenseData[] = (float)$stat['expense_val'];
}

$message = getFlashData("message");
$message_type = getFlashData("message_type");
$notificationTypeMap = getNotificationTypeMap();
$activeNotification  = getActiveNotification();
$notificationHistory = getNotificationHistory();
$defaultExpiryValue  = date('Y-m-d\TH:i', strtotime('+1 hour'));
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="app-container">
    <?php layout("sidebar_admin", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">ADMINISTRATOR</span>
                    <h1>Hệ thống quản trị</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box" style="background: #e67e22;"><?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content">
            <?php if (!empty($message)) echo showMessage($message, $message_type); ?>

            <section class="notification-broadcast card-box" style="margin-bottom: 25px; padding: 22px; background: #fff; border-radius: 12px; border-left: 5px solid #e74c3c; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <h3 style="margin: 0 0 10px; color: #333;">Quản lý thông báo</h3>
                <p style="margin: 0 0 18px; color: #666; line-height: 1.6;">Chỉ có 1 thông báo được phát tại 1 thời điểm. Khi phát mới, thông báo đang active sẽ được đưa vào lịch sử.</p>

                <form action="?template=admin&action=dashboard" method="POST" style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: flex-end;">
                    <div style="flex: 1; min-width: 260px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:5px; text-transform:uppercase;">Nội dung</label>
                        <input type="text" name="message" placeholder="Nhập nội dung thông báo..." style="width:100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing:border-box;" required>
                    </div>
                    <div style="min-width: 180px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:5px; text-transform:uppercase;">Loại</label>
                        <select name="type" style="width:100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px;">
                            <option value="info">Thông tin</option>
                            <option value="warning">Cảnh báo</option>
                            <option value="success">Thành công</option>
                            <option value="error">Khẩn cấp</option>
                        </select>
                    </div>
                    <div style="width: 220px;">
                        <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:5px; text-transform:uppercase;">Hết hạn lực</label>
                        <input type="datetime-local" name="expires_at" value="<?= $defaultExpiryValue ?>" style="width:100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; box-sizing:border-box;" required>
                    </div>
                    <div>
                        <button type="submit" name="broadcast_notification" style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 700; font-size: 15px; height: 44px;">Phát thông báo</button>
                    </div>
                </form>

                <?php if ($activeNotification): ?>
                    <?php
                    $activeType    = normalizeNotificationType($activeNotification['type'] ?? 'info');
                    $activeStyle   = $notificationTypeMap[$activeType];
                    ?>
                    <div style="margin-bottom: 18px;">
                        <h4 style="margin: 0 0 10px; color: #444; font-size: 16px;">Thông báo đang phát</h4>
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px 18px; border-radius:10px; background: <?= $activeStyle['admin_bg'] ?>; border-left: 5px solid <?= $activeStyle['admin_border'] ?>; color: <?= $activeStyle['admin_text'] ?>;">
                            <div style="flex:1;">
                                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px;">
                                    <strong style="font-size:16px;"><?= htmlspecialchars($activeNotification['message']) ?></strong>
                                    <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#fff; border:1px solid rgba(0,0,0,0.08); font-size:11px; font-weight:700; text-transform:uppercase;"><?= htmlspecialchars($activeStyle['label']) ?></span>
                                    <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#2ecc71; color:#fff; font-size:11px; font-weight:700; text-transform:uppercase;">Đang phát</span>
                                </div>
                                <div style="font-size:13px; opacity:0.88; line-height:1.6;">
                                    Hết hạn: <?= date('d/m/Y H:i', strtotime($activeNotification['expires_at'])) ?> |
                                    Bởi: <strong><?= htmlspecialchars($activeNotification['created_by_name'] ?? 'Không rõ') ?></strong>
                                </div>
                            </div>
                            <form action="?template=admin&action=dashboard" method="POST" style="margin:0;">
                                <input type="hidden" name="disable_notification" value="<?= (int)$activeNotification['id'] ?>">
                                <button type="submit" style="background:#e74c3c; color:#fff; padding:8px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:700;">Tat ngay</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <h4 style="margin: 0 0 10px; color: #444; font-size: 16px;">Lịch sử thông báo</h4>
                    <p style="margin: 0 0 12px; font-size: 13px; color: #999; font-style: italic;">Những thông báo quá 7 ngày kể từ ngày hết thời hạn sẽ bị xoá.</p>
                    <?php if (!empty($notificationHistory)): ?>
                        <div style="display:flex; flex-direction:column; gap:10px; height:290px; overflow-y:auto; padding-right:4px;">
                            <?php foreach ($notificationHistory as $notif): ?>
                                <?php
                                $notifType  = normalizeNotificationType($notif['type'] ?? 'info');
                                $notifStyle = $notificationTypeMap[$notifType];
                                ?>
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 16px; border-radius:10px; background: <?= $notifStyle['admin_bg'] ?>; border-left: 5px solid <?= $notifStyle['admin_border'] ?>; color: <?= $notifStyle['admin_text'] ?>;">
                                    <div style="flex:1;">
                                        <div style="font-weight:700; font-size:15px; margin-bottom:5px;"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div style="font-size:12px; opacity:0.86; line-height:1.6;">
                                            Tạo lúc: <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?> |
                                            Hết hạn: <?= date('d/m/Y H:i', strtotime($notif['expires_at'])) ?> |
                                            Bởi: <strong><?= htmlspecialchars($notif['created_by_name'] ?? 'Không rõ') ?></strong>
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                                        <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#fff; border:1px solid rgba(0,0,0,0.08); font-size:11px; font-weight:700; text-transform:uppercase;"><?= htmlspecialchars($notifStyle['label']) ?></span>
                                        <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#95a5a6; color:#fff; font-size:11px; font-weight:700; text-transform:uppercase;">Lưu lịch sử</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 14px 16px; border: 1px dashed #d0d7de; border-radius: 10px; color: #6b7280; background: #fafafa;">Chưa có thông báo nào trong lịch sử.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="stats-grid">
                <div class="stat-card" style="border-left: 5px solid #3498db;">
                    <p class="card-title">TỔNG THÀNH VIÊN</p>
                    <h2><?= number_format($totalUsers) ?></h2>
                </div>
                <div class="stat-card" style="border-left: 5px solid #e67e22;">
                    <p class="card-title">TỔNG GIAO DỊCH</p>
                    <h2><?= number_format($totalTransactions) ?></h2>
                </div>
                <div class="stat-card dark-green-card">
                    <p class="card-title">SỐ DƯ HỆ THỐNG</p>
                    <h2><?= number_format($totalIncome - $totalExpense, 0, ',', '.') ?> đ</h2>
                </div>
            </section>

            <section class="chart-section">
                <div class="chart-container-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Phân tích hạng mục</h3>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="dot-usage">●</span> Lượt dùng</span>
                            <span class="legend-item"><span class="dot-income">●</span> Tổng thu</span>
                            <span class="legend-item"><span class="dot-expense">●</span> Tong chi</span>
                        </div>
                    </div>

                    <div class="canvas-wrapper">
                        <canvas
                            id="groupedBarChart"
                            data-labels='<?= json_encode($labels) ?>'
                            data-usage='<?= json_encode($usageData) ?>'
                            data-income='<?= json_encode($incomeData) ?>'
                            data-expense='<?= json_encode($expenseData) ?>'>
                        </canvas>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<?php
layout("footer", ["js" => ["pages/sidebar", "pages/admin/dashboard"]]);
?>
