<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

$view = 'dashboard';

$loginToken = getSession('loginToken');
if (empty($loginToken)) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

// Budget render helper
function renderBudgetSectionHtml($userId, $budgetMonth, $budgetYear) {
    $mbRow = getOne(
        "SELECT amount FROM monthly_budget WHERE user_id = :uid AND month = :m AND year = :y",
        ['uid' => $userId, 'm' => $budgetMonth, 'y' => $budgetYear]
    );
    $mbAmount = $mbRow ? (float)$mbRow['amount'] : 0;
    $mbSpent = 0;
    $mbPct = 0;
    if ($mbAmount > 0) {
        $mbSpentRow = getOne(
            "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='expense' AND is_archived=0 AND MONTH(transaction_date)=:m AND YEAR(transaction_date)=:y",
            ['uid' => $userId, 'm' => $budgetMonth, 'y' => $budgetYear]
        );
        $mbSpent = $mbSpentRow ? (float)$mbSpentRow['total'] : 0;
        $mbPct = round(($mbSpent / $mbAmount) * 100);
    }

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
        ['uid' => $userId, 'm' => $budgetMonth, 'y' => $budgetYear]
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
    <?php if (!$hasAnyBudget): ?>
        <div style="padding:16px; text-align:center; color:#999; font-size:14px;">
            Chưa có ngân sách cho tháng này.
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
<?php
}

// ── AJAX: Cursor-based pagination (load more) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $userId = getSession('id');

    $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $limit = 5;
    $cursorWhere = '';
    $cursorParams = [];
    if ($lastId > 0) {
        $cursorWhere = "WHERE combined._sort_id < :last_id";
        $cursorParams = ['last_id' => $lastId];
    }
    $allParams = array_merge(['uid1' => $userId, 'uid2' => $userId], $cursorParams);
    $fetchLimit = $limit + 1;

    $transactionList = getAll(
        "SELECT * FROM (
            SELECT transaction.transaction_date, category.name, category.icon, transaction.description, transaction.price, transaction.type, transaction.source_type, wallet.name AS wallet_name, wallet.type AS wallet_type, transaction.id AS _sort_id, NULL AS batch_id
            FROM transaction
            JOIN category ON category.id = transaction.category_id
            LEFT JOIN wallet ON wallet.id = transaction.wallet_id
            WHERE transaction.user_id = :uid1 AND transaction.is_archived = 0 AND transaction.source_type != 'transfer' AND transaction.batch_id IS NULL
            UNION ALL
            SELECT MAX(transaction.transaction_date), category.name, category.icon, GROUP_CONCAT(DISTINCT transaction.description ORDER BY transaction.id SEPARATOR ', '), SUM(transaction.price), MAX(transaction.type), 'multi', 'Nhiều ví', NULL, MAX(transaction.id), transaction.batch_id
            FROM transaction
            JOIN category ON category.id = transaction.category_id
            WHERE transaction.user_id = :uid2 AND transaction.is_archived = 0 AND transaction.source_type != 'transfer' AND transaction.batch_id IS NOT NULL
            GROUP BY transaction.batch_id, category.id
        ) combined
        $cursorWhere
        ORDER BY combined._sort_id DESC
        LIMIT $fetchLimit",
        $allParams
    );

    $hasMore = count($transactionList) > $limit;
    if ($hasMore) {
        $transactionList = array_slice($transactionList, 0, $limit);
    }

    // Fetch batch detail wallets
    $batchIds = [];
    foreach ($transactionList as $t) {
        if (($t['source_type'] ?? '') === 'multi' && !empty($t['batch_id'])) {
            $batchIds[] = $t['batch_id'];
        }
    }
    $batchDetails = [];
    if (!empty($batchIds)) {
        $batchIds = array_unique($batchIds);
        $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
        $details = getAll(
            "SELECT t.batch_id, t.price, w.icon, w.name, w.type
             FROM transaction t
             LEFT JOIN wallet w ON w.id = t.wallet_id
             WHERE t.batch_id IN ($placeholders) AND t.user_id = ?
             ORDER BY t.id",
            array_merge($batchIds, [$userId])
        );
        foreach ($details as $d) {
            $batchDetails[$d['batch_id']][] = $d;
        }
    }

    $rowsHtml = '';
    if (empty($transactionList)) {
        $rowsHtml = '<tr><td colspan="5" class="table-text-center">Chưa có giao dịch nào.</td></tr>';
    } else {
        foreach ($transactionList as $transaction) {
            $isMulti = ($transaction['source_type'] ?? '') === 'multi';
            $source = sourceLabel($transaction['wallet_name'] ?? '', $transaction['source_type'] ?? 'manual', $transaction['wallet_type'] ?? '');
            $rowsHtml .= '<tr class="filter-tr' . ($isMulti ? ' has-batch' : '') . '">';
            $rowsHtml .= '<td>' . htmlspecialchars($transaction['transaction_date']) . '</td>';
            $rowsHtml .= '<td>' . htmlspecialchars($transaction['icon'] ?? '📦') . ' ' . htmlspecialchars($transaction['name']) . '</td>';
            $rowsHtml .= '<td>' . htmlspecialchars($transaction['description']) . '</td>';
            $rowsHtml .= '<td><span class="' . $source[1] . '">' . $source[0];
            if ($isMulti && !empty($batchDetails[$transaction['batch_id']])) {
                $rowsHtml .= '<div class="multi-wallet-detail">';
                foreach ($batchDetails[$transaction['batch_id']] as $bd) {
                    $rowsHtml .= '<div><span class="bwi-icon">' . htmlspecialchars($bd['icon'] ?? '💰') . '</span> ' . htmlspecialchars($bd['name']) . ': <strong>' . number_format($bd['price'], 0, ',', '.') . 'đ</strong></div>';
                }
                $rowsHtml .= '</div>';
            }
            $rowsHtml .= '</span></td>';
            $rowsHtml .= '<td class="' . ($transaction['type'] == 'income' ? 'text-income' : 'text-expense') . '">';
            $rowsHtml .= ($transaction['type'] == 'income' ? '+' : '-') . ' ' . number_format($transaction['price'], 0, ',', '.') . ' đ';
            $rowsHtml .= '</td>';
            $rowsHtml .= '</tr>';
        }
    }

    $nextLastId = 0;
    if (!empty($transactionList)) {
        $lastItem = end($transactionList);
        $nextLastId = $lastItem['_sort_id'];
    }

    jsonResponse(true, '', [
        'rows' => $rowsHtml,
        'has_more' => $hasMore,
        'next_last_id' => $nextLastId,
        'count' => count($transactionList),
    ]);
}

// AJAX budget handler
if (isset($_GET['ajax']) && $_GET['ajax'] === 'budget') {
    $userId = getSession('id');
    $budgetMonth = isset($_GET['budget_month']) ? max(1, min(12, (int)$_GET['budget_month'])) : (int)date('m');
    $budgetYear = isset($_GET['budget_year']) ? max(2000, (int)$_GET['budget_year']) : (int)date('Y');
    ob_start();
    renderBudgetSectionHtml($userId, $budgetMonth, $budgetYear);
    $html = ob_get_clean();
    jsonResponse(true, '', ['html' => $html]);
}

layout("header", [
    "title" => "Quản Lý Chi Tiêu",
    "css" => ["layout/sidebar", "pages/user/dashboard"]
]);


$username = getSession('username');
$user = getOne(
    "SELECT user.id FROM user JOIN logintoken ON user.id = logintoken.user_id WHERE loginToken = :loginToken",
    ["loginToken" => $loginToken]
);


$limit = 5;
$fetchLimit = $limit + 1;
$userId = $user['id'];
$transactionList = getAll(
    "SELECT * FROM (
        SELECT transaction.transaction_date, category.name, category.icon, transaction.description, transaction.price, transaction.type, transaction.source_type, wallet.name AS wallet_name, wallet.type AS wallet_type, transaction.id AS _sort_id, NULL AS batch_id
        FROM transaction
        JOIN category ON category.id = transaction.category_id
        LEFT JOIN wallet ON wallet.id = transaction.wallet_id
        WHERE transaction.user_id = :uid1 AND transaction.is_archived = 0 AND transaction.source_type != 'transfer' AND transaction.batch_id IS NULL
        UNION ALL
        SELECT MAX(transaction.transaction_date), category.name, category.icon, GROUP_CONCAT(DISTINCT transaction.description ORDER BY transaction.id SEPARATOR ', '), SUM(transaction.price), MAX(transaction.type), 'multi', 'Nhiều ví', NULL, MAX(transaction.id), transaction.batch_id
        FROM transaction
        JOIN category ON category.id = transaction.category_id
        WHERE transaction.user_id = :uid2 AND transaction.is_archived = 0 AND transaction.source_type != 'transfer' AND transaction.batch_id IS NOT NULL
        GROUP BY transaction.batch_id, category.id
    ) combined
    ORDER BY combined._sort_id DESC
    LIMIT $fetchLimit",
    ['uid1' => $user['id'], 'uid2' => $user['id']]
);

$dashboardHasMore = count($transactionList) > $limit;
if ($dashboardHasMore) {
    $transactionList = array_slice($transactionList, 0, $limit);
}

// Track cursor for JS
$dashboardLastId = 0;
if (!empty($transactionList)) {
    $lastItem = end($transactionList);
    $dashboardLastId = $lastItem['_sort_id'];
}

// ── Fetch batch detail wallets for display ──
$batchDetailMap = [];
$batchIdList = [];
foreach ($transactionList as $t) {
    if (($t['source_type'] ?? '') === 'multi' && !empty($t['batch_id'])) {
        $batchIdList[] = $t['batch_id'];
    }
}
if (!empty($batchIdList)) {
    $batchIdList = array_unique($batchIdList);
    $ph = implode(',', array_fill(0, count($batchIdList), '?'));
    $bd = getAll(
        "SELECT t.batch_id, t.price, w.icon, w.name, w.type
         FROM transaction t
         LEFT JOIN wallet w ON w.id = t.wallet_id
         WHERE t.batch_id IN ($ph) AND t.user_id = ?
         ORDER BY t.id",
        array_merge($batchIdList, [$user['id']])
    );
    foreach ($bd as $r) {
        $batchDetailMap[$r['batch_id']][] = $r;
    }
}

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
) ?: [];

$archiveNotice = getFlashData('archive_notice');
createDefaultWallets($user['id']);
$wallets = getWallets($user['id']);
$dailyWallets = array_filter($wallets, fn($w)=>$w['type']==='daily');
$ewalletWallets = array_filter($wallets, fn($w)=>$w['type']==='ewallet');
$targetWallets = array_filter($wallets, fn($w)=>$w['type']==='target');
$dailyWallets = array_filter($wallets, fn($w)=>$w['type']==='daily');
$ewalletWallets = array_filter($wallets, fn($w)=>$w['type']==='ewallet');
$targetWallets = array_filter($wallets, fn($w)=>$w['type']==='target');

// Total across all wallets
$totalWalletBalance = 0;
foreach ($wallets as $w) $totalWalletBalance += getWalletBalance($w['id'], $user['id']);
// Legacy transactions without wallet
$legacyBalance = getCurrentBalance($user['id']) - $totalWalletBalance;
$totalWalletBalance += max(0, $legacyBalance);

// Available balance = sum of daily wallets only (for reference)
$dailyBalance = 0;
foreach ($dailyWallets as $dw) $dailyBalance += getWalletBalance($dw['id'], $user['id']);
$dailyBalance += max(0, $legacyBalance);

// Check pending_delete transactions for notification
$pendingDeleteTxns = getAll(
    "SELECT id, wallet_id, price FROM transaction WHERE user_id = :uid AND status = 'pending_delete'",
    ['uid' => $user['id']]
);
$pendingReadyCount = 0;
$pendingTotalCount = count($pendingDeleteTxns);
if ($pendingTotalCount > 0) {
    foreach ($pendingDeleteTxns as $pt) {
        $bal = getWalletBalance($pt['wallet_id'], $user['id']);
        if ($bal >= $pt['price']) {
            $pendingReadyCount++;
        }
    }
}

// Check pending_edit transactions for notification
$pendingEditCount = (int)getOne(
    "SELECT COUNT(*) as cnt FROM transaction WHERE user_id = :uid AND sync_status = 'pending_edit'",
    ['uid' => $user['id']]
)['cnt'] ?? 0;

$totalIncome = getTotalSum($user['id'], 'income');
$totalExpense = getTotalSum($user['id'], 'expense');
$currentBalance = $totalIncome - $totalExpense;

// Extra stats
$monthStart = date('Y-m-01');
$monthEnd   = date('Y-m-t');
$txnThisMonth = countRows(
    "SELECT id FROM transaction WHERE user_id=:uid AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
    ['uid' => $user['id'], 's' => $monthStart, 'e' => $monthEnd]
);
$expenseThisMonth = getOne(
    "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='expense' AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
    ['uid' => $user['id'], 's' => $monthStart, 'e' => $monthEnd]
)['total'] ?? 0;
$daysPassed = max(1, (int)date('j'));
$avgDailyExpense = round($expenseThisMonth / $daysPassed);
$savingsRate = $totalIncome > 0 ? round(($currentBalance / $totalIncome) * 100) : 0;

$needsBalanceWarning = false;
$balanceWarningMessage = '';
if ($currentBalance < -1000000) {
    //  Vi phạm nghiêm trọng (Vượt quá hạn mức nợ 1 triệu)
    $needsBalanceWarning = true;
    $balanceWarningMessage = 'Tài khoản của bạn đã vượt quá hạn mức nợ cho phép (Tối đa nợ 1.000.000đ)!';

} elseif ($currentBalance < 0) {
    // Số dư bị âm nhưng vẫn trong tầm kiểm soát (nợ dưới 1 triệu)
    $needsBalanceWarning = true;
    $balanceWarningMessage = 'Số dư của bạn đang âm ' . number_format(abs($currentBalance), 0, ',', '.') . 'đ.';

} elseif ($currentBalance == 0) {
    // Vừa bằng 0
    $needsBalanceWarning = true;
    $balanceWarningMessage = 'Số dư của bạn đang bằng 0. Bạn có thể nợ tối đa 1.000.000đ.';
}

function sourceLabel($walletName, $sourceType, $walletType = '') {
    if (!empty($walletName)) {
        $typeClass = $walletType ? ' type-' . $walletType : '';
        return [$walletName, 'source-badge source-wallet' . $typeClass];
    }
    switch ($sourceType) {
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
            <?php if ($pendingEditCount > 0): ?>
            <div class="pending-notif edit">
                <div class="pending-notif-body">
                    <span class="pending-notif-icon">✏️</span>
                    <span class="pending-notif-text">
                        Có <strong><?= $pendingEditCount ?></strong> giao dịch đang chờ xử lý thay đổi.
                    </span>
                </div>
                <a href="?template=user&action=filter" class="pending-notif-btn">Xem ngay →</a>
            </div>
            <?php endif; ?>
            <?php if ($pendingReadyCount > 0): ?>
            <div class="pending-notif ready">
                <div class="pending-notif-body">
                    <span class="pending-notif-icon">✅</span>
                    <span class="pending-notif-text">
                        Có <strong><?= $pendingReadyCount ?></strong> giao dịch chờ xoá đã sẵn sàng!
                    </span>
                </div>
                <a href="?template=user&action=filter" class="pending-notif-btn">Xem ngay →</a>
            </div>
            <?php elseif ($pendingTotalCount > 0): ?>
            <div class="pending-notif waiting">
                <div class="pending-notif-body">
                    <span class="pending-notif-icon">⏳</span>
                    <span class="pending-notif-text">
                        Có <strong><?= $pendingTotalCount ?></strong> giao dịch đang chờ xoá.
                    </span>
                </div>
                <a href="?template=user&action=filter" class="pending-notif-btn">Xem ngay →</a>
            </div>
            <?php endif; ?>
            <section class="stats-grid">
                <div class="stat-card dark-green-card relative-overflow-visible">
                    <p class="card-title">TỔNG SỐ DƯ CÁC VÍ</p>
                    <h2><?= number_format($totalWalletBalance, 0, ',', '.') ?> đ</h2>
                    <p class="card-desc">Bao gồm tất cả ví &amp; quỹ</p>
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
                <div class="stat-card">
                    <p class="card-title">GIAO DỊCH THÁNG NÀY</p>
                    <h2><?= number_format($txnThisMonth) ?></h2>
                    <p class="card-desc">Từ <?= date('d/m/Y') ?></p>
                </div>
                <div class="stat-card">
                    <p class="card-title">CHI TIÊU BQ / NGÀY</p>
                    <h2><?= number_format($avgDailyExpense, 0, ',', '.') ?> đ</h2>
                    <p class="card-desc">Trung bình tháng <?= date('m/Y') ?></p>
                </div>
                <div class="stat-card">
                    <p class="card-title">TỈ LỆ TIẾT KIỆM</p>
                    <h2 style="color:<?= $savingsRate >= 0 ? '#2ecc71' : '#e74c3c' ?>"><?= $savingsRate ?>%</h2>
                    <p class="card-desc">Thu nhập – Chi tiêu</p>
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

            <!-- Wallet Summary -->
            <?php if (!empty($wallets)):
            $groupTotals = [];
            foreach (['daily','ewallet','target'] as $gtype) {
                $gw = array_filter($wallets, fn($w)=>$w['type']===$gtype);
                $total = 0;
                foreach ($gw as $w) $total += getWalletBalance($w['id'], $user['id']);
                $groupTotals[$gtype] = ['wallets' => $gw, 'total' => $total];
            }
            ?>
            <section class="card-box" style="margin-bottom:20px;">
                <div class="section-header" style="margin-bottom:16px;">
                    <h3><span class="material-symbols-outlined" style="font-size:20px;vertical-align:middle;margin-right:4px;">account_balance_wallet</span> Ví của bạn</h3>
                    <a href="?template=user&action=wallet" style="font-size:12px;color:#6366f1;text-decoration:none;font-weight:600;">Quản lý →</a>
                </div>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <?php foreach (['daily','ewallet','target'] as $gtype):
                        $gt = $groupTotals[$gtype];
                        if (empty($gt['wallets'])) continue;
                        $gColors = ['daily'=>['#059669','#d1fae5','#10b981'],'ewallet'=>['#2563eb','#dbeafe','#3b82f6'],'target'=>['#d97706','#fef3c7','#f59e0b']];
                        $gc = $gColors[$gtype];
                        $gtc = $gt['total'] >= 0 ? $gc[0] : '#dc2626';
                    ?>
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:<?= $gc[1] ?>;border-radius:8px;margin-bottom:6px;">
                            <span style="display:flex;align-items:center;gap:6px;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.03em;color:<?= $gc[0] ?>">
                                <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1;"><?= ['daily'=>'account_balance_wallet','ewallet'=>'phone_android','target'=>'savings'][$gtype] ?></span>
                                <?= ['daily'=>'Ví Chi Tiêu','ewallet'=>'Ví Điện Tử','target'=>'Quỹ / Mục Tiêu'][$gtype] ?>
                            </span>
                            <span style="font-weight:850;font-size:15px;color:<?= $gtc ?>"><?= number_format($gt['total'],0,',','.') ?>đ</span>
                        </div>
                        <?php foreach ($gt['wallets'] as $w):
                            $bal = getWalletBalance($w['id'], $user['id']);
                            $bc = $bal >= 0 ? '#059669' : '#dc2626';
                        ?>
                        <div style="display:flex;align-items:center;gap:8px;padding:5px 12px;">
                            <span style="font-size:15px;flex-shrink:0;"><?= htmlspecialchars($w['icon']??'💳') ?></span>
                            <span style="flex:1;font-weight:600;font-size:13px;color:var(--color-text-main,#0f172a);"><?= htmlspecialchars($w['name']) ?></span>
                            <span style="font-weight:700;font-size:13px;color:<?= $bc ?>"><?= number_format($bal,0,',','.') ?>đ</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php
            $budgetMonth = isset($_GET['budget_month']) ? (int)$_GET['budget_month'] : (int)date('m');
            $budgetYear = isset($_GET['budget_year']) ? (int)$_GET['budget_year'] : (int)date('Y');
            if ($budgetMonth < 1) $budgetMonth = 1;
            if ($budgetMonth > 12) $budgetMonth = 12;
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
                                    onchange="loadBudget()">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == $budgetMonth ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="budget_year"
                                    style="padding:5px 6px; border:1px solid #ccc; border-radius:4px; font-size:13px; width:86px; background:#fff;"
                                    onchange="loadBudget()">
                                <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                    <option value="<?= $y ?>" <?= $y == $budgetYear ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                </div>
                <div id="budgetContent"><?php renderBudgetSectionHtml($user['id'], $budgetMonth, $budgetYear); ?></div>
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
                    <div class="filter-table-wrapper">
                        <div class="filter-arrows" style="display:none;">
                            <button type="button" class="filter-arrow filter-arrow--prev" title="Trang trước" aria-label="Trang trước">&#x2039;</button>
                            <button type="button" class="filter-arrow filter-arrow--next" title="Trang sau" aria-label="Trang sau">&#x203A;</button>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>NGÀY</th>
                                    <th>DANH MỤC</th>
                                    <th>MÔ TẢ</th>
                                    <th>NGUỒN</th>
                                    <th>SỐ TIỀN</th>
                                </tr>
                            </thead>
                            <tbody class="filter-tbody" id="dashTbody">
                         <?php if (empty($transactionList)): ?>
                             <tr id="dashEmptyRow">
                                 <td colspan="5" class="table-text-center">Chưa có giao dịch nào.</td>
                             </tr>
                         <?php else: ?>
                             <?php foreach ($transactionList as $transaction):
                                 $isMulti = ($transaction['source_type'] ?? '') === 'multi';
                                 $source = sourceLabel($transaction['wallet_name'] ?? '', $transaction['source_type'] ?? 'manual', $transaction['wallet_type'] ?? '');
                             ?>
                                  <tr class="filter-tr<?= $isMulti ? ' has-batch' : '' ?>">
                                      <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                                      <td><?= htmlspecialchars($transaction['icon'] ?? '📦') ?> <?= htmlspecialchars($transaction['name']) ?></td>
                                      <td><?= htmlspecialchars($transaction['description']) ?></td>
                                      <td><span class="<?= $source[1] ?>"><?= $source[0] ?>
                                          <?php if ($isMulti && !empty($batchDetailMap[$transaction['batch_id']])): ?>
                                          <div class="multi-wallet-detail">
                                              <?php foreach ($batchDetailMap[$transaction['batch_id']] as $bd): ?>
                                              <div><span class="bwi-icon"><?= htmlspecialchars($bd['icon'] ?? '💰') ?></span> <?= htmlspecialchars($bd['name']) ?>: <strong><?= number_format($bd['price'], 0, ',', '.') ?>đ</strong></div>
                                              <?php endforeach; ?>
                                          </div>
                                          <?php endif; ?>
                                      </span></td>
                                      <td class="<?= $transaction['type'] == 'income' ? 'text-income' : 'text-expense' ?>">
                                          <?= $transaction['type'] == 'income' ? '+' : '-' ?>
                                          <?= number_format($transaction['price'], 0, ',', '.') ?> đ
                                      </td>
                                  </tr>
                             <?php endforeach; ?>
                             <?php endif; ?>
                        </tbody>
                    </table>
                    </div><!-- end filter-table-wrapper -->
                    <div class="load-more-container" id="dashLoadMoreContainer">
                        <button type="button" class="btn-load-more" id="dashBtnLoadMore">Xem thêm</button>
                        <div class="load-more-spinner" id="dashLoadMoreSpinner" style="display:none;">Đang tải...</div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script>
var dashLastId = <?= $dashboardLastId ?>;
var dashHasMore = <?= $dashboardHasMore ? 'true' : 'false' ?>;
</script>
<?php
layout("footer", ["js" => ["pages/sidebar", "pages/dashboard"]]);
