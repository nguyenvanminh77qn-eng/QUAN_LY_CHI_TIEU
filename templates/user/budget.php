<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

$view = 'budget';

$loginToken = getSession('loginToken');
if (empty($loginToken)) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

layout("header", [
    "title" => "Ngân sách danh mục",
    "css" => ["layout/sidebar", "pages/user/filter"]
]);

$userId = getSession('id');
$username = getSession('username');

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($month < 1) $month = 1;
if ($month > 12) $month = 12;

$categories = getAll("SELECT * FROM category ORDER BY name ASC");

$budgets = getAll(
    "SELECT category_id, amount FROM budget WHERE user_id = :uid AND month = :m AND year = :y",
    ['uid' => $userId, 'm' => $month, 'y' => $year]
);
$budgetMap = [];
foreach ($budgets as $b) {
    $budgetMap[$b['category_id']] = (float)$b['amount'];
}

// Chi tiêu thực tế trong tháng theo danh mục (chỉ expense)
$spentData = getAll(
    "SELECT category_id, SUM(price) as total
     FROM transaction
     WHERE user_id = :uid AND type = 'expense' AND is_archived = 0
       AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y
     GROUP BY category_id",
    ['uid' => $userId, 'm' => $month, 'y' => $year]
);
$spentMap = [];
foreach ($spentData as $s) {
    $spentMap[$s['category_id']] = (float)$s['total'];
}

// Monthly budget
$monthlyBudgetRow = getOne(
    "SELECT amount FROM monthly_budget WHERE user_id = :uid AND month = :m AND year = :y",
    ['uid' => $userId, 'm' => $month, 'y' => $year]
);
$monthlyBudgetAmount = $monthlyBudgetRow ? (float)$monthlyBudgetRow['amount'] : 0;

// Tổng chi trong tháng
$totalSpentRow = getOne(
    "SELECT COALESCE(SUM(price), 0) as total
     FROM transaction
     WHERE user_id = :uid AND type = 'expense' AND is_archived = 0
       AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y",
    ['uid' => $userId, 'm' => $month, 'y' => $year]
);
$totalSpent = $totalSpentRow ? (float)$totalSpentRow['total'] : 0;

$message = getFlashData("message");
$message_type = getFlashData("message_type");
?>
<div class="app-container">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">BUDGET PLANNER</span>
                    <h1>Ngân sách</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <?php if (!empty($message)) echo showMessage($message, $message_type); ?>

            <div class="card" style="margin-bottom: 20px; padding: 20px;">
                <form action="" method="GET" style="display: flex; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="template" value="user">
                    <input type="hidden" name="action" value="budget">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:4px;">Tháng</label>
                        <select name="month" style="padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:14px; width:100px; background:#fff;" onchange="this.form.submit()">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $month ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:4px;">Năm</label>
                        <select name="year" style="padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:14px; width:100px; background:#fff;" onchange="this.form.submit()">
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Monthly Budget -->
            <div class="card" style="margin-bottom: 20px; padding: 20px;">
                <form action="?template=user&action=budget" method="POST">
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    <h3 style="margin:0 0 12px;">Ngân sách tháng <?= sprintf('%02d', $month) ?>/<?= $year ?></h3>
                    <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                        <div style="flex:1; min-width:200px;">
                            <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:4px;">Tổng ngân sách tháng</label>
                            <div style="display:flex; gap:8px; align-items:center;">
                                <input type="text" name="monthly_amount"
                                       value="<?= $monthlyBudgetAmount > 0 ? $monthlyBudgetAmount : '' ?>"
                                       placeholder="0 (vd: 5000000)"
                                       style="flex:1; padding:8px 12px; border:1px solid #ddd; border-radius:4px; font-size:15px; text-align:right;">
                                <button type="submit" name="save_monthly_budget" class="btn-submit-quick" style="padding:8px 20px; white-space:nowrap;">Lưu</button>
                            </div>
                        </div>
                        <?php if ($monthlyBudgetAmount > 0):
                            $mbPct = round(($totalSpent / $monthlyBudgetAmount) * 100);
                            $mbColor = '#2ecc71';
                            $mbMax = $monthlyBudgetAmount * 1.1;
                            if ($mbPct > 100) $mbColor = '#e74c3c';
                            elseif ($mbPct >= 80) $mbColor = '#f39c12';
                        ?>
                        <div style="min-width:200px; flex:1;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>Đã chi: <strong><?= number_format($totalSpent, 0, ',', '.') ?>đ</strong></span>
                                <span>Ngân sách: <strong><?= number_format($monthlyBudgetAmount, 0, ',', '.') ?>đ</strong></span>
                            </div>
                            <div style="height:10px; background:#ecf0f1; border-radius:5px; overflow:hidden;">
                                <div style="height:100%; width:<?= min($mbPct, 100) ?>%; background:<?= $mbColor ?>; border-radius:5px;"></div>
                            </div>
                            <div style="text-align:right; font-size:12px; font-weight:600; color:<?= $mbColor ?>; margin-top:3px;">
                                <?= $mbPct ?>% (tối đa <?= number_format($mbMax, 0, ',', '.') ?>đ)
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Category Budgets -->
            <div class="card filter-result-card">
                <form action="?template=user&action=budget" method="POST">
                    <input type="hidden" name="month" value="<?= $month ?>">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    <div class="filter-toolbar">
                        <h3>Ngân sách theo danh mục</h3>
                        <button type="submit" name="save_budget" class="btn-submit-quick" style="padding: 8px 20px;">Lưu danh mục</button>
                    </div>

                    <table class="filter-data-table">
                        <thead class="filter-thead">
                            <tr>
                                <th class="filter-th" style="text-align:center;">Danh mục</th>
                                <th class="filter-th text-right">Ngân sách (đ)</th>
                                <th class="filter-th text-right">Đã chi (đ)</th>
                                <th class="filter-th text-center">Tiến độ</th>
                                <th class="filter-th text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="5" class="filter-td filter-empty-state">Chưa có danh mục nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat):
                                    $budgetAmount = $budgetMap[$cat['id']] ?? 0;
                                    $spent = $spentMap[$cat['id']] ?? 0;
                                    $percent = $budgetAmount > 0 ? round(($spent / $budgetAmount) * 100) : 0;
                                    $barColor = '#2ecc71';
                                    $statusText = 'OK';
                                    $statusColor = '#2ecc71';
                                    if ($percent > 100) {
                                        $barColor = '#e74c3c';
                                        $statusText = 'Vượt ngân sách!';
                                        $statusColor = '#e74c3c';
                                    } elseif ($percent >= 80) {
                                        $barColor = '#f39c12';
                                        $statusText = 'Sắp vượt';
                                        $statusColor = '#f39c12';
                                    }
                                ?>
                                <tr class="filter-tr">
                                    <td class="filter-td">
                                        <span class="category-badge">
                                            <span class="category-badge__icon"><?= htmlspecialchars($cat['icon'] ?? '📦') ?></span>
                                            <span class="category-badge__name"><?= htmlspecialchars($cat['name']) ?></span>
                                        </span>
                                    </td>
                                    <td class="filter-td text-right">
                                        <input type="text" name="amounts[<?= $cat['id'] ?>]"
                                               value="<?= $budgetAmount > 0 ? $budgetAmount : '' ?>"
                                               class="filter-input budget-input"
                                               placeholder="0 (vd: 5000000)"
                                               style="width:160px; text-align:right;">
                                    </td>
                                    <td class="filter-td text-right <?= $spent > $budgetAmount && $budgetAmount > 0 ? 'text-expense' : '' ?>">
                                        <strong><?= number_format($spent, 0, ',', '.') ?> đ</strong>
                                    </td>
                                    <td class="filter-td" style="min-width:180px;">
                                        <?php if ($budgetAmount > 0): ?>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="flex:1; height:8px; background:#ecf0f1; border-radius:4px; overflow:hidden;">
                                                    <div style="height:100%; width:<?= min($percent, 100) ?>%; background:<?= $barColor ?>; border-radius:4px; transition:width 0.3s;"></div>
                                                </div>
                                                <span style="font-size:12px; font-weight:600; min-width:35px; text-align:right; color:<?= $barColor ?>;"><?= $percent ?>%</span>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#999; font-size:13px;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="filter-td text-center">
                                        <?php if ($budgetAmount > 0): ?>
                                            <span style="display:inline-block; padding:3px 12px; border-radius:12px; font-size:12px; font-weight:600; background:<?= $statusColor ?>20; color:<?= $statusColor ?>;">
                                                <?= $statusText ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color:#bbb;">Chưa đặt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </main>
</div>

<?php layout("footer", ["js" => ["pages/sidebar"]]); ?>
