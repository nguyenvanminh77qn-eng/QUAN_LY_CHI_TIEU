<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

layout("header", [
    "title" => "Quản Lý Chi Tiêu",
    "css" => ["layout/sidebar", "pages/user/dashboard"]
]);
$view = 'dashboard';

$loginToken = getSession('loginToken');
if (empty($loginToken)) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}

$username = getSession('username');
$user = getOne(
    "SELECT user.id FROM user JOIN logintoken ON user.id = logintoken.user_id WHERE loginToken = :loginToken",
    ["loginToken" => $loginToken]
);
setSession("id", $user['id']);

$archivedCount = archiveExpiredTransactions($user['id']);
cleanupNotifications();
if ($archivedCount > 0) {
    setFlashData('archive_notice', "Đã lưu trữ $archivedCount giao dịch quá hạn.");
}

$limit = 5;
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$totalTransactions = countRows(
    "SELECT id FROM transaction WHERE user_id = :id AND is_archived = 0 ORDER BY transaction_date DESC, id DESC",
    ["id" => $user['id']]
);
$pagination = getPagination($totalTransactions, $limit, $currentPage);
$offset = (int) $pagination['offset'];

$transactionList = getAll(
    "SELECT transaction.transaction_date, category.name, category.icon, transaction.description, transaction.price, transaction.type, transaction.source_type
    FROM transaction
    JOIN category ON category.id = transaction.category_id
    WHERE transaction.user_id = :id AND transaction.is_archived = 0
    ORDER BY transaction.transaction_date DESC, transaction.id DESC
    LIMIT $limit OFFSET $offset",
    ["id" => $user['id']]
);

$latestReconciliation = getOne(
    "SELECT reconciliation_date, actual_balance, system_balance, difference_amount, created_at
    FROM reconciliation
    WHERE user_id = :user_id
    ORDER BY reconciliation_date DESC, id DESC
    LIMIT 1",
    ['user_id' => $user['id']]
);

// Dữ liệu biểu đồ: thu/chi theo 6 tháng gần nhất
$chartMonths = [];
$chartIncome = [];
$chartExpense = [];
for ($i = 5; $i >= 0; $i--) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd   = date('Y-m-t', strtotime("-$i months"));
    $label      = date('m/Y', strtotime("-$i months"));
    $chartMonths[] = $label;

    $inc = getOne(
        "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='income' AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
        ['uid' => $user['id'], 's' => $monthStart, 'e' => $monthEnd]
    );
    $exp = getOne(
        "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='expense' AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
        ['uid' => $user['id'], 's' => $monthStart, 'e' => $monthEnd]
    );
    $chartIncome[]  = (float)($inc['total'] ?? 0);
    $chartExpense[] = (float)($exp['total'] ?? 0);
}

// Dữ liệu biểu đồ tròn: chi tiêu theo danh mục
$catChartData = getAll(
    "SELECT category.name, category.icon, SUM(transaction.price) as total
     FROM transaction
     JOIN category ON category.id = transaction.category_id
     WHERE transaction.user_id = :uid AND transaction.type = 'expense' AND transaction.is_archived = 0
     GROUP BY category.id
     ORDER BY total DESC
     LIMIT 6",
    ['uid' => $user['id']]
);

$archiveNotice = getFlashData('archive_notice');
$totalIncome = getTotalSum($user['id'], 'income');
$totalExpense = getTotalSum($user['id'], 'expense');
$currentBalance = $totalIncome - $totalExpense;

$needsBalanceWarning = false;
$balanceWarningMessage = '';
if ($currentBalance <= 0) {
    $needsBalanceWarning = true;
    if ($currentBalance == 0) {
        $balanceWarningMessage = 'Số dư của bạn đang bằng 0. Bạn có thể nợ tối đa 1.000.000đ.';
    } else {
        $balanceWarningMessage = 'Số dư của bạn đang âm ' . number_format(abs($currentBalance), 0, ',', '.') . 'đ.';
    }
}
if ($currentBalance < -1000000) {
    $needsBalanceWarning = true;
    $balanceWarningMessage = 'Số dư đã vượt quá giới hạn âm cho phép.';
}

function sourceLabel($sourceType) {
    switch ($sourceType) {
        case 'bank_message':
        case 'pasted_text':
            return ['Văn bản dán', 'source-badge source-bank'];
        case 'adjustment':
            return ['Chốt sổ', 'source-badge source-adjustment'];
        default:
            return ['Nhập tay', 'source-badge source-manual'];
    }
}
?>
<div class="app-container">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">DIGITAL CURATOR</span>
                    <h1>Quản Lý Chi Tiêu</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content user-dashboard-content">
            <section class="stats-grid">
                <div class="stat-card dark-green-card relative-overflow-visible">
                    <p class="card-title">SỐ DƯ KHẢ DỤNG</p>
                    <h2><?= number_format($currentBalance, 0, ',', '.') ?> đ</h2>

                    <?php if ($needsBalanceWarning): ?>
                        <div class="inline-balance-bubble">
                            <div class="bubble-arrow-outer"></div>
                            <div class="bubble-arrow-inner"></div>
                            <p><?= htmlspecialchars($balanceWarningMessage) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="stat-card">
                    <p class="card-amount positive">+ <?= number_format($totalIncome, 0, ',', '.') ?> đ</p>
                    <h3>Tổng thu</h3>
                    <p class="card-desc">Lương và các khoản thu nhập</p>
                </div>
                <div class="stat-card">
                    <p class="card-amount negative">- <?= number_format($totalExpense, 0, ',', '.') ?> đ</p>
                    <h3>Tổng chi</h3>
                    <p class="card-desc">Chi phí sinh hoạt và mua sắm</p>
                </div>
            </section>

            <?php
            $message = getFlashData("message");
            $messageType = getFlashData("message_type");
            if (!empty($message)) {
                echo showMessage($message, $messageType);
            }
            if (!empty($archiveNotice)) {
                echo showMessage($archiveNotice, 'info');
            }
            ?>

            <section class="card-box reconciliation-section">
                <div class="section-header">
                    <h3>Chốt sổ thực tế</h3>
                </div>
                <p class="section-subtext">Cuối tuần hoặc cuối tháng, nhập số dư thực tế để đối chiếu. Nếu lệch, hệ thống sẽ tạo giao dịch điều chỉnh để đưa số dư trên app về đúng thực tế.</p>
                <form action="?template=user&action=reconcile" method="POST" class="reconcile-form">
                    <input type="date" name="reconciliation_date" class="filter-input" value="<?= date('Y-m-d') ?>" required>
                    <input type="number" name="actual_balance" class="filter-input" step="1000" placeholder="Số dư thực tế hiện có" required>
                    <input type="text" name="note" class="filter-input" placeholder="Ghi chú ngắn (nếu cần)">
                    <button type="submit" name="reconcile_btn" class="btn-submit-quick">Chốt sổ và đồng bộ</button>
                </form>

                <?php if (!empty($latestReconciliation)): ?>
                    <div class="reconciliation-summary">
                        <div class="summary-item">
                            <span>Lần chốt sổ gần nhất</span>
                            <strong><?= htmlspecialchars($latestReconciliation['reconciliation_date']) ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Số dư thực tế</span>
                            <strong><?= number_format($latestReconciliation['actual_balance'], 0, ',', '.') ?> đ</strong>
                        </div>
                        <div class="summary-item">
                            <span>Lệch so với hệ thống</span>
                            <strong><?= number_format($latestReconciliation['difference_amount'], 0, ',', '.') ?> đ</strong>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <?php
            // Budget summary — monthly budget + category budgets
            $budgetMonth = isset($_GET['budget_month']) ? (int)$_GET['budget_month'] : (int)date('m');
            $budgetYear = isset($_GET['budget_year']) ? (int)$_GET['budget_year'] : (int)date('Y');
            if ($budgetMonth < 1) $budgetMonth = 1;
            if ($budgetMonth > 12) $budgetMonth = 12;

            // Monthly budget
            $mbRow = getOne(
                "SELECT amount FROM monthly_budget WHERE user_id = :uid AND month = :m AND year = :y",
                ['uid' => $user['id'], 'm' => $budgetMonth, 'y' => $budgetYear]
            );
            $mbAmount = $mbRow ? (float)$mbRow['amount'] : 0;
            $mbSpent = 0;
            $mbPct = 0;
            if ($mbAmount > 0) {
                $mbSpentRow = getOne(
                    "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='expense' AND is_archived=0 AND MONTH(transaction_date)=:m AND YEAR(transaction_date)=:y",
                    ['uid' => $user['id'], 'm' => $budgetMonth, 'y' => $budgetYear]
                );
                $mbSpent = $mbSpentRow ? (float)$mbSpentRow['total'] : 0;
                $mbPct = round(($mbSpent / $mbAmount) * 100);
            }

            // Category budgets
            $budgetList = getAll(
                "SELECT b.amount, c.id as cat_id, c.name, c.icon,
                        COALESCE(SUM(t.price), 0) as spent
                 FROM budget b
                 JOIN category c ON c.id = b.category_id
                 LEFT JOIN transaction t ON t.category_id = c.id
                     AND t.user_id = b.user_id
                     AND t.type = 'expense' AND t.is_archived = 0
                     AND MONTH(t.transaction_date) = b.month
                     AND YEAR(t.transaction_date) = b.year
                 WHERE b.user_id = :uid AND b.month = :m AND b.year = :y
                 GROUP BY b.id, c.id, c.name, c.icon, b.amount
                 ORDER BY c.name ASC",
                ['uid' => $user['id'], 'm' => $budgetMonth, 'y' => $budgetYear]
            );
            $overBudgetCount = 0;
            $nearLimitCount = 0;
            foreach ($budgetList as $bl) {
                $pct = $bl['amount'] > 0 ? ($bl['spent'] / $bl['amount']) * 100 : 0;
                if ($pct > 100) $overBudgetCount++;
                elseif ($pct >= 80) $nearLimitCount++;
            }

            $hasAnyBudget = $mbAmount > 0 || !empty($budgetList);
            ?>
            <section class="card-box" style="margin-bottom:20px;">
                <div class="section-header" style="margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <h3>Ngân sách</h3>
                    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                        <form action="" method="GET" id="budgetFilterForm" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="template" value="user">
                            <input type="hidden" name="action" value="dashboard">
                            <select name="budget_month"
                                    style="padding:5px 6px; border:1px solid #ccc; border-radius:4px; font-size:13px; width:76px; background:#fff;"
                                    onchange="this.form.submit()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == $budgetMonth ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="budget_year"
                                    style="padding:5px 6px; border:1px solid #ccc; border-radius:4px; font-size:13px; width:86px; background:#fff;"
                                    onchange="this.form.submit()">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $budgetYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                        <a href="?template=user&action=budget" style="font-size:13px; color:#3498db; text-decoration:none; white-space:nowrap;">Quản lý →</a>
                    </div>
                </div>

                <?php if (!$hasAnyBudget): ?>
                    <div style="padding:16px; text-align:center; color:#999; font-size:14px;">
                        Chưa có ngân sách cho tháng này. <a href="?template=user&action=budget" style="color:#3498db;">Thiết lập ngân sách →</a>
                    </div>
                <?php else: ?>
                    <?php if ($overBudgetCount > 0): ?>
                        <div style="background:#fdeeee; border:1px solid #e74c3c; border-radius:8px; padding:8px 14px; margin-bottom:12px; font-size:13px; color:#c0392b;">
                            ⚠️ Có <strong><?= $overBudgetCount ?></strong> danh mục đã vượt ngân sách!
                        </div>
                    <?php elseif ($nearLimitCount > 0): ?>
                        <div style="background:#fff7e6; border:1px solid #f39c12; border-radius:8px; padding:8px 14px; margin-bottom:12px; font-size:13px; color:#b56d07;">
                            ⚠️ Có <strong><?= $nearLimitCount ?></strong> danh mục sắp vượt ngân sách.
                        </div>
                    <?php endif; ?>

                    <?php if ($mbAmount > 0):
                        $mbColor = '#2ecc71';
                        $mbMax = $mbAmount * 1.1;
                        if ($mbPct > 100) $mbColor = '#e74c3c';
                        elseif ($mbPct >= 80) $mbColor = '#f39c12';
                    ?>
                    <div style="background:#f9f9f9; border-radius:8px; padding:12px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <span style="font-weight:600; font-size:14px;">📅 Tổng ngân sách tháng</span>
                            <span style="font-size:12px; color:#666;">
                                <?= number_format($mbSpent, 0, ',', '.') ?>đ / <?= number_format($mbAmount, 0, ',', '.') ?>đ
                            </span>
                        </div>
                        <div style="height:8px; background:#ecf0f1; border-radius:4px; overflow:hidden;">
                            <div style="height:100%; width:<?= min($mbPct, 100) ?>%; background:<?= $mbColor ?>; border-radius:4px;"></div>
                        </div>
                        <div style="text-align:right; font-size:11px; color:#999; margin-top:3px;"><?= $mbPct ?>% (tối đa <?= number_format($mbMax, 0, ',', '.') ?>đ)</div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($budgetList)): ?>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px;">
                        <?php foreach ($budgetList as $bl):
                            $spent = (float)$bl['spent'];
                            $budgetAmt = (float)$bl['amount'];
                            $pct = $budgetAmt > 0 ? round(($spent / $budgetAmt) * 100) : 0;
                            $barColor = '#2ecc71';
                            if ($pct > 100) $barColor = '#e74c3c';
                            elseif ($pct >= 80) $barColor = '#f39c12';
                        ?>
                        <div style="background:#f9f9f9; border-radius:8px; padding:12px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <span style="font-weight:600; font-size:14px;">
                                    <?= htmlspecialchars($bl['icon'] ?? '📦') ?> <?= htmlspecialchars($bl['name']) ?>
                                </span>
                                <span style="font-size:12px; color:#666;">
                                    <?= number_format($spent, 0, ',', '.') ?>đ / <?= number_format($budgetAmt, 0, ',', '.') ?>đ
                                </span>
                            </div>
                            <div style="height:6px; background:#ecf0f1; border-radius:3px; overflow:hidden;">
                                <div style="height:100%; width:<?= min($pct, 100) ?>%; background:<?= $barColor ?>; border-radius:3px;"></div>
                            </div>
                            <div style="text-align:right; font-size:11px; color:#999; margin-top:3px;"><?= $pct ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="data-grid">
                <!-- Biểu đồ thu/chi 6 tháng -->
                <div class="card-box" style="margin-bottom:20px;">
                    <div class="section-header" style="margin-bottom:16px;">
                        <h3>Thu / Chi 6 tháng gần nhất</h3>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:center;">
                        <div style="position:relative; height:240px; width:100%;">
                            <canvas id="userBarChart"
                                data-months='<?= json_encode($chartMonths) ?>'
                                data-income='<?= json_encode($chartIncome) ?>'
                                data-expense='<?= json_encode($chartExpense) ?>'>
                            </canvas>
                        </div>
                        <div style="position:relative; height:240px; width:100%; display:flex; align-items:center; justify-content:center;">
                            <canvas id="userPieChart"
                                data-labels='<?= json_encode(array_column($catChartData, 'name')) ?>'
                                data-values='<?= json_encode(array_column($catChartData, 'total')) ?>'>
                            </canvas>
                        </div>
                    </div>
                </div>

                <div class="transactions-section card-box">
                    <div class="section-header">
                        <h3>Giao dịch gần đây</h3>
                    </div>
                    <table class="data-table">
                        <tr>
                            <th>NGÀY</th>
                            <th>DANH MỤC</th>
                            <th>MÔ TẢ</th>
                            <th>NGUỒN</th>
                            <th>SỐ TIỀN</th>
                        </tr>
                        <?php if (empty($transactionList)): ?>
                            <tr>
                                <td colspan="5" class="table-text-center">Chưa có giao dịch nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactionList as $transaction): ?>
                                <?php $source = sourceLabel($transaction['source_type'] ?? 'manual'); ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                                    <td><?= htmlspecialchars($transaction['icon'] ?? '📦') ?> <?= htmlspecialchars($transaction['name']) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><span class="<?= $source[1] ?>"><?= $source[0] ?></span></td>
                                    <td class="<?= $transaction['type'] == 'income' ? 'text-income' : 'text-expense' ?>">
                                        <?= $transaction['type'] == 'income' ? '+' : '-' ?>
                                        <?= number_format($transaction['price'], 0, ',', '.') ?> đ
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </table>
                    <?= renderPagination($pagination['totalPages'], $pagination['currentPage'], "?template=user&action=dashboard") ?>
                </div>
            </section>
        </div>
    </main>
</div>

<?php
layout("footer", ["js" => ["pages/sidebar", "pages/dashboard"]]);
