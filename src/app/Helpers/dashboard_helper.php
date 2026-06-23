<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

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

function getDashboardTransactions($userId, $lastId = 0, $limit = 5) {
    $cursorWhere = '';
    $cursorParams = [];
    if ($lastId > 0) {
        $cursorWhere = "WHERE combined._sort_id < :last_id";
        $cursorParams = ['last_id' => $lastId];
    }
    $fetchLimit = $limit + 1;
    $list = getAll(
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
        array_merge(['uid1' => $userId, 'uid2' => $userId], $cursorParams)
    );
    $hasMore = count($list) > $limit;
    if ($hasMore) $list = array_slice($list, 0, $limit);
    $nextId = 0;
    if (!empty($list)) {
        $lastItem = end($list);
        $nextId = $lastItem['_sort_id'];
    }
    return ['list' => $list, 'hasMore' => $hasMore, 'nextId' => $nextId];
}

function getBatchDetailMap($transactionList, $userId) {
    $batchIds = [];
    foreach ($transactionList as $t) {
        if (($t['source_type'] ?? '') === 'multi' && !empty($t['batch_id'])) {
            $batchIds[] = $t['batch_id'];
        }
    }
    $map = [];
    if (!empty($batchIds)) {
        $batchIds = array_unique($batchIds);
        $ph = implode(',', array_fill(0, count($batchIds), '?'));
        $details = getAll(
            "SELECT t.batch_id, t.price, w.icon, w.name, w.type
             FROM transaction t
             LEFT JOIN wallet w ON w.id = t.wallet_id
             WHERE t.batch_id IN ($ph) AND t.user_id = ?
             ORDER BY t.id",
            array_merge($batchIds, [$userId])
        );
        foreach ($details as $d) {
            $map[$d['batch_id']][] = $d;
        }
    }
    return $map;
}

function renderTransactionRow($transaction, $batchDetailMap) {
    $isMulti = ($transaction['source_type'] ?? '') === 'multi';
    $source = sourceLabel($transaction['wallet_name'] ?? '', $transaction['source_type'] ?? 'manual', $transaction['wallet_type'] ?? '');
    $html = '<tr class="filter-tr' . ($isMulti ? ' has-batch' : '') . '">';
    $html .= '<td>' . htmlspecialchars($transaction['transaction_date']) . '</td>';
    $html .= '<td>' . htmlspecialchars($transaction['icon'] ?? '📦') . ' ' . htmlspecialchars($transaction['name']) . '</td>';
    $html .= '<td>' . htmlspecialchars($transaction['description']) . '</td>';
    $html .= '<td><span class="' . $source[1] . '">' . $source[0];
    if ($isMulti && !empty($batchDetailMap[$transaction['batch_id']])) {
        $html .= '<div class="multi-wallet-detail">';
        foreach ($batchDetailMap[$transaction['batch_id']] as $bd) {
            $html .= '<div><span class="bwi-icon">' . htmlspecialchars($bd['icon'] ?? '💰') . '</span> ' . htmlspecialchars($bd['name']) . ': <strong>' . number_format($bd['price'], 0, ',', '.') . 'đ</strong></div>';
        }
        $html .= '</div>';
    }
    $html .= '</span></td>';
    $html .= '<td class="' . ($transaction['type'] == 'income' ? 'text-income' : 'text-expense') . '">';
    $html .= ($transaction['type'] == 'income' ? '+' : '-') . ' ' . number_format($transaction['price'], 0, ',', '.') . ' đ';
    $html .= '</td></tr>';
    return $html;
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

function getDashboardChartData($userId) {
    $chartMonths = [];
    $chartIncome = [];
    $chartExpense = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthStart = date('Y-m-01', strtotime("-$i months"));
        $monthEnd   = date('Y-m-t', strtotime("-$i months"));
        $chartMonths[] = date('m/Y', strtotime("-$i months"));
        $inc = getOne(
            "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='income' AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
            ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
        );
        $exp = getOne(
            "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='expense' AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
            ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
        );
        $chartIncome[]  = (float)($inc['total'] ?? 0);
        $chartExpense[] = (float)($exp['total'] ?? 0);
    }
    $catChartData = getAll(
        "SELECT category.name, category.icon, SUM(transaction.price) as total
         FROM transaction
         JOIN category ON category.id = transaction.category_id
         WHERE transaction.user_id = :uid AND transaction.type = 'expense' AND transaction.is_archived = 0
         GROUP BY category.id
         ORDER BY total DESC
         LIMIT 6",
        ['uid' => $userId]
    ) ?: [];
    return [$chartMonths, $chartIncome, $chartExpense, $catChartData];
}

function getDashboardWallets($userId) {
    createDefaultWallets($userId);
    $wallets = getWallets($userId);
    $dailyWallets = array_filter($wallets, fn($w)=>$w['type']==='daily');
    $ewalletWallets = array_filter($wallets, fn($w)=>$w['type']==='ewallet');
    $targetWallets = array_filter($wallets, fn($w)=>$w['type']==='target');
    return [$wallets, $dailyWallets, $ewalletWallets, $targetWallets];
}

function getDashboardBalanceData($userId, $wallets) {
    $totalWalletBalance = 0;
    foreach ($wallets as $w) $totalWalletBalance += getWalletBalance($w['id'], $userId);
    $legacyBalance = getCurrentBalance($userId) - $totalWalletBalance;
    $totalWalletBalance += max(0, $legacyBalance);
    $dailyWallets = array_filter($wallets, fn($w)=>$w['type']==='daily');
    $dailyBalance = 0;
    foreach ($dailyWallets as $dw) $dailyBalance += getWalletBalance($dw['id'], $userId);
    $dailyBalance += max(0, $legacyBalance);
    return [$totalWalletBalance, $legacyBalance, $dailyBalance];
}

function getDashboardPendingInfo($userId) {
    $pendingDeleteTxns = getAll(
        "SELECT id, wallet_id, price FROM transaction WHERE user_id = :uid AND status = 'pending_delete'",
        ['uid' => $userId]
    );
    $pendingReadyCount = 0;
    $pendingTotalCount = count($pendingDeleteTxns);
    if ($pendingTotalCount > 0) {
        foreach ($pendingDeleteTxns as $pt) {
            if (getWalletBalance($pt['wallet_id'], $userId) >= $pt['price']) $pendingReadyCount++;
        }
    }
    $pendingEditCount = (int)(getOne(
        "SELECT COUNT(*) as cnt FROM transaction WHERE user_id = :uid AND sync_status = 'pending_edit'",
        ['uid' => $userId]
    )['cnt'] ?? 0);
    return [$pendingReadyCount, $pendingTotalCount, $pendingEditCount];
}

function getDashboardFinancialStats($userId) {
    $totalIncome = getTotalSum($userId, 'income');
    $totalExpense = getTotalSum($userId, 'expense');
    $currentBalance = $totalIncome - $totalExpense;

    $monthStart = date('Y-m-01');
    $monthEnd   = date('Y-m-t');
    $txnThisMonth = countRows(
        "SELECT id FROM transaction WHERE user_id=:uid AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
        ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
    );
    $expenseThisMonth = (float)(getOne(
        "SELECT COALESCE(SUM(price),0) as total FROM transaction WHERE user_id=:uid AND type='expense' AND is_archived=0 AND transaction_date BETWEEN :s AND :e",
        ['uid' => $userId, 's' => $monthStart, 'e' => $monthEnd]
    )['total'] ?? 0);
    $daysPassed = max(1, (int)date('j'));
    $avgDailyExpense = round($expenseThisMonth / $daysPassed);
    $savingsRate = $totalIncome > 0 ? round(($currentBalance / $totalIncome) * 100) : 0;

    return [$totalIncome, $totalExpense, $currentBalance, $txnThisMonth, $expenseThisMonth, $daysPassed, $avgDailyExpense, $savingsRate];
}

function getDashboardBalanceWarning($currentBalance) {
    $needsBalanceWarning = false;
    $balanceWarningMessage = '';
    if ($currentBalance < -1000000) {
        $needsBalanceWarning = true;
        $balanceWarningMessage = 'Tài khoản của bạn đã vượt quá hạn mức nợ cho phép (Tối đa nợ 1.000.000đ)!';
    } elseif ($currentBalance < 0) {
        $needsBalanceWarning = true;
        $balanceWarningMessage = 'Số dư của bạn đang âm ' . number_format(abs($currentBalance), 0, ',', '.') . 'đ.';
    } elseif ($currentBalance == 0) {
        $needsBalanceWarning = true;
        $balanceWarningMessage = 'Số dư của bạn đang bằng 0. Bạn có thể nợ tối đa 1.000.000đ.';
    }
    return [$needsBalanceWarning, $balanceWarningMessage];
}
