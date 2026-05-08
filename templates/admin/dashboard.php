<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

// Access Control
if(empty(getSession('loginToken'))) {
    setMessage("Bạn phải đăng nhập", "error");
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

// System Stats
$totalUsers = countRows("SELECT id FROM user");
$totalTransactions = countRows("SELECT id FROM transaction");
$totalIncome = getOne("SELECT SUM(price) as total FROM transaction WHERE type = 'income'")['total'] ?? 0;
$totalExpense = getOne("SELECT SUM(price) as total FROM transaction WHERE type = 'expense'")['total'] ?? 0;

// Unified Category Stats for Chart (Usage, Income, Expense)
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

// Prepare simplified data for Data Attributes
$labels = []; $usageData = []; $incomeData = []; $expenseData = [];
foreach($catStats as $stat) {
    $labels[] = $stat['name'];
    $usageData[] = (int)$stat['usage_count'];
    $incomeData[] = (float)$stat['income_val'];
    $expenseData[] = (float)$stat['expense_val'];
}
$activeNotification = getOne("SELECT * FROM notifications WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$message = getFlashData("message");
$message_type = getFlashData("message_type");
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
                    <h1>Hệ Thống Quản Trị</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box" style="background: #e67e22;">👑 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content">
            <?php if(!empty($message)) echo showMessage($message, $message_type); ?>

            <!-- Notification Broadcast Section -->
            <section class="notification-broadcast card-box" style="margin-bottom: 25px; padding: 20px; background: #fff; border-radius: 12px; border-left: 5px solid #e74c3c; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 15px; color: #333;">📢 Phát loa thông báo toàn cầu</h3>
                
                <?php if ($activeNotification): 
                    $previewColors = [
                        'info' => ['bg' => '#e3f2fd', 'border' => '#90caf9', 'text' => '#1976d2'],
                        'warning' => ['bg' => '#fff8e1', 'border' => '#ffe082', 'text' => '#f57c00'],
                        'success' => ['bg' => '#e8f5e9', 'border' => '#a5d6a7', 'text' => '#2e7d32'],
                        'error' => ['bg' => '#ffebee', 'border' => '#ef9a9a', 'text' => '#c62828']
                    ];
                    $style = $previewColors[$activeNotification['type']] ?? $previewColors['info'];
                ?>
                    <div style="padding: 15px; background: <?= $style['bg'] ?>; border: 1px solid <?= $style['border'] ?>; border-radius: 8px; margin-bottom: 15px; color: <?= $style['text'] ?>;">
                        <strong>Trạng thái:</strong> <span style="font-weight: bold;">Đang phát</span><br>
                        <strong>Nội dung:</strong> <?= htmlspecialchars($activeNotification['message']) ?><br>
                        <strong>Loại:</strong> <?= htmlspecialchars($activeNotification['type']) ?>
                    </div>
                    <form action="?template=admin&action=dashboard" method="POST">
                        <input type="hidden" name="disable_notification" value="<?= $activeNotification['id'] ?>">
                        <button type="submit" class="btn btn-danger" style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer;">Tắt thông báo này</button>
                    </form>
                <?php else: ?>
                    <form action="?template=admin&action=dashboard" method="POST" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <input type="text" name="message" placeholder="Nhập nội dung thông báo..." style="flex: 1; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px;" required>
                        <select name="type" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px;">
                            <option value="info">Thông tin (Xanh lam)</option>
                            <option value="warning">Cảnh báo (Vàng)</option>
                            <option value="success">Thành công (Xanh lá)</option>
                            <option value="error">Lỗi/Khẩn cấp (Đỏ)</option>
                        </select>
                        <button type="submit" name="broadcast_notification" style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Phát thông báo 🚀</button>
                    </form>
                <?php endif; ?>
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

            <!-- Chart Section -->
            <section class="chart-section">
                <div class="chart-container-card">
                    <div class="chart-header">
                        <h3 class="chart-title">📊 Biểu đồ Phân tích Hạng mục</h3>
                        <div class="chart-legend">
                            <span class="legend-item"><span class="dot-usage">●</span> Lượt dùng</span>
                            <span class="legend-item"><span class="dot-income">●</span> Tổng thu</span>
                            <span class="legend-item"><span class="dot-expense">●</span> Tổng chi</span>
                        </div>
                    </div>
                    
                    <div class="canvas-wrapper">
                        <canvas id="groupedBarChart" 
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
