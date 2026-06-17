<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    if (empty(getSession('loginToken'))) {
        setMessage("Bạn phải đăng nhập", "error");
        redirect("?template=auth&action=login.view");
    }

    if (getSession('role') !== 'admin') {
        setMessage("Bạn không có quyền truy cập trang này", "error");
        redirect("?template=user&action=dashboard");
    }

    layout("header", [
        "title" => "Admin Dashboard",
        "css" => ["layout/sidebar", "pages/user/dashboard", "pages/admin/theme", "pages/admin/dashboard"]
    ]);

    $view = 'dashboard';
    $username = getSession('username');

    cleanupNotifications();

    // ── Stats ──
    $totalUsers     = countRows("SELECT id FROM user");
    $totalTransactions = countRows("SELECT id FROM transaction");
    $totalIncome    = getOne("SELECT SUM(price) as total FROM transaction WHERE type = 'income'")['total'] ?? 0;
    $totalExpense   = getOne("SELECT SUM(price) as total FROM transaction WHERE type = 'expense'")['total'] ?? 0;
    $balance        = $totalIncome - $totalExpense;

    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');
    $newUsersThisMonth = countRows(
        "SELECT id FROM user WHERE create_at BETWEEN :s AND :e",
        ['s' => $monthStart, 'e' => $monthEnd]
    );

    // ── Monthly trend (6 months) ──
    $trendLabels = [];
    $trendIncome = [];
    $trendExpense = [];
    for ($i = 5; $i >= 0; $i--) {
        $ms = date('Y-m-01', strtotime("-$i months"));
        $me = date('Y-m-t', strtotime("-$i months"));
        $trendLabels[] = date('m/Y', strtotime("-$i months"));

        $inc = getOne(
            "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE type='income' AND transaction_date BETWEEN :s AND :e",
            ['s' => $ms, 'e' => $me]
        );
        $exp = getOne(
            "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE type='expense' AND transaction_date BETWEEN :s AND :e",
            ['s' => $ms, 'e' => $me]
        );
        $trendIncome[]  = (float)($inc['total'] ?? 0);
        $trendExpense[] = (float)($exp['total'] ?? 0);
    }

    // ── Recent transactions (latest 8) ──
    $recentTxns = getAll("
        SELECT t.transaction_date, t.description, t.price, t.type,
               u.username, c.name as cat_name, c.icon
        FROM transaction t
        JOIN user u ON u.id = t.user_id
        JOIN category c ON c.id = t.category_id
        WHERE t.is_archived = 0
        ORDER BY t.create_at DESC
        LIMIT 8
    ");

    // ── Recent users (latest 6) ──
    $recentUsers = getAll("
        SELECT username, email, create_at, status
        FROM user
        ORDER BY id DESC
        LIMIT 6
    ");

    // ── Category analysis ──
    $catStats = getAll("
        SELECT c.name,
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
        $labels[]     = $stat['name'];
        $usageData[]  = (int)$stat['usage_count'];
        $incomeData[] = (float)$stat['income_val'];
        $expenseData[] = (float)$stat['expense_val'];
    }

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
                    <h1>Hệ thống quản trị</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box"><?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content">
            <?php if (!empty($message)) echo showMessage($message, $message_type); ?>

            <!-- Stats -->
            <section class="stats-grid">
                <div class="stat-card">
                    <p class="card-title">TỔNG THÀNH VIÊN</p>
                    <h2><?= number_format($totalUsers) ?></h2>
                    <p class="card-desc">Toàn bộ người dùng</p>
                </div>
                <div class="stat-card">
                    <p class="card-title">TỔNG GIAO DỊCH</p>
                    <h2><?= number_format($totalTransactions) ?></h2>
                    <p class="card-desc">Tổng số giao dịch</p>
                </div>
                <div class="stat-card dark-green-card">
                    <p class="card-title">SỐ DƯ HỆ THỐNG</p>
                    <h2><?= number_format($balance, 0, ',', '.') ?> đ</h2>
                    <p class="card-desc">Thu nhập – Chi tiêu</p>
                </div>
                <div class="stat-card">
                    <p class="card-title">NGƯỜI DÙNG MỚI</p>
                    <h2>+<?= number_format($newUsersThisMonth) ?></h2>
                    <p class="card-desc">Tháng này</p>
                </div>
                <div class="stat-card">
                    <p class="card-title">TỔNG THU</p>
                    <h2 style="color:#2ecc71;">+<?= number_format($totalIncome, 0, ',', '.') ?> đ</h2>
                    <p class="card-desc">Toàn hệ thống</p>
                </div>
                <div class="stat-card">
                    <p class="card-title">TỔNG CHI</p>
                    <h2 style="color:#e74c3c;">-<?= number_format($totalExpense, 0, ',', '.') ?> đ</h2>
                    <p class="card-desc">Toàn hệ thống</p>
                </div>
            </section>

            <!-- Charts -->
            <section class="chart-section">
                <div class="chart-grid">
                    <div class="chart-container-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Phân tích hạng mục</h3>
                        </div>
                        <div class="dual-chart">
                            <div class="sub-chart-block">
                                <div class="sub-chart-label">
                                    <span class="dot-usage">●</span> Số lượt sử dụng
                                </div>
                                <div class="sub-canvas-wrapper">
                                    <canvas id="usageChart"
                                        data-labels='<?= json_encode($labels) ?>'
                                        data-values='<?= json_encode($usageData) ?>'>
                                    </canvas>
                                </div>
                            </div>
                            <div class="sub-chart-block">
                                <div class="sub-chart-label">
                                    <span class="dot-income">●</span> Thu nhập
                                    <span class="dot-expense" style="margin-left:20px;">●</span> Chi tiêu
                                </div>
                                <div class="sub-canvas-wrapper">
                                    <canvas id="moneyChart"
                                        data-labels='<?= json_encode($labels) ?>'
                                        data-income='<?= json_encode($incomeData) ?>'
                                        data-expense='<?= json_encode($expenseData) ?>'>
                                    </canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="chart-container-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Thu / Chi 6 tháng</h3>
                            <div class="chart-legend">
                                <span class="legend-item"><span class="dot-income">●</span> Thu nhập</span>
                                <span class="legend-item"><span class="dot-expense">●</span> Chi tiêu</span>
                            </div>
                        </div>
                        <div class="canvas-wrapper" style="height:380px;">
                            <canvas id="trendChart"
                                data-labels='<?= json_encode($trendLabels) ?>'
                                data-income='<?= json_encode($trendIncome) ?>'
                                data-expense='<?= json_encode($trendExpense) ?>'>
                            </canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tables -->
            <section class="data-grid">
                <div class="card-box">
                    <div class="section-header">
                        <h3>Giao dịch gần đây</h3>
                    </div>
                    <?php if (empty($recentTxns)): ?>
                        <p style="padding:20px;text-align:center;color:rgba(245,240,235,0.3);">Chưa có giao dịch nào.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>NGÀY</th>
                                    <th>NGƯỜI DÙNG</th>
                                    <th>DANH MỤC</th>
                                    <th>MÔ TẢ</th>
                                    <th>SỐ TIỀN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTxns as $tx): ?>
                                    <tr class="filter-tr">
                                        <td><?= htmlspecialchars($tx['transaction_date']) ?></td>
                                        <td><?= htmlspecialchars($tx['username']) ?></td>
                                        <td><?= htmlspecialchars($tx['icon'] ?? '📦') ?> <?= htmlspecialchars($tx['cat_name']) ?></td>
                                        <td class="desc" title="<?= htmlspecialchars($tx['description']) ?>"><?= htmlspecialchars($tx['description']) ?></td>
                                        <td class="<?= $tx['type'] == 'income' ? 'text-income' : 'text-expense' ?>">
                                            <?= $tx['type'] == 'income' ? '+' : '-' ?>
                                            <?= number_format($tx['price'], 0, ',', '.') ?> đ
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="card-box">
                    <div class="section-header">
                        <h3>Người dùng mới</h3>
                    </div>
                    <?php if (empty($recentUsers)): ?>
                        <p style="padding:20px;text-align:center;color:rgba(245,240,235,0.3);">Chưa có người dùng.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>TÊN ĐĂNG NHẬP</th>
                                    <th>EMAIL</th>
                                    <th>NGÀY TẠO</th>
                                    <th>TRẠNG THÁI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $u): ?>
                                    <tr class="filter-tr">
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($u['create_at'])) ?></td>
                                        <td style="text-align:center;">
                                            <span style="display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap;
                                                <?= $u['status'] == 1 ? 'background:rgba(46,204,113,0.15);color:#2ecc71;' : 'background:rgba(149,165,166,0.15);color:#95a5a6;' ?>
                                            ">
                                                <span style="width:6px;height:6px;border-radius:50%;background:currentColor;"></span>
                                                <?= $u['status'] == 1 ? 'Hoạt động' : ($u['status'] == 2 ? 'Bị khóa' : 'Chưa kích hoạt') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</div>

<?php
    layout("footer", ["js" => ["pages/sidebar", "pages/admin/dashboard"]]);
?>
