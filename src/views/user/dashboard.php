<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

$view = 'dashboard';
$loginToken = getSession('loginToken');
if (empty($loginToken)) { setMessage("Bạn phải đăng nhập","error"); redirect("?template=auth&action=login.view"); }
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền truy cập trang này","error"); redirect("?template=admin&action=dashboard"); }

// ── AJAX: Transaction pagination ──
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $userId = getSession('id');
    $page = getDashboardTransactions($userId, isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0);
    $batchDetailMap = getBatchDetailMap($page['list'], $userId);
    $rowsHtml = '';
    if (empty($page['list'])) {
        $rowsHtml = '<tr><td colspan="5" class="table-text-center">Chưa có giao dịch nào.</td></tr>';
    } else {
        foreach ($page['list'] as $transaction) $rowsHtml .= renderTransactionRow($transaction, $batchDetailMap);
    }
    jsonResponse(true, '', ['rows' => $rowsHtml, 'has_more' => $page['hasMore'], 'next_last_id' => $page['nextId'], 'count' => count($page['list'])]);
}

// ── AJAX: Budget ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'budget') {
    $userId = getSession('id');
    $budgetMonth = isset($_GET['budget_month']) ? max(1, min(12, (int)$_GET['budget_month'])) : (int)date('m');
    $budgetYear = isset($_GET['budget_year']) ? max(2000, (int)$_GET['budget_year']) : (int)date('Y');
    ob_start(); renderBudgetSectionHtml($userId, $budgetMonth, $budgetYear); $html = ob_get_clean();
    jsonResponse(true, '', ['html' => $html]);
}

layout("header", ["title" => "Quản Lý Chi Tiêu", "css" => ["layout/sidebar", "pages/user/dashboard"]]);

// ── Load dashboard data ──
$username = getSession('username');
$user = getOne("SELECT user.id FROM user JOIN logintoken ON user.id = logintoken.user_id WHERE loginToken = :loginToken", ["loginToken" => $loginToken]);
$userId = $user['id'];

$dashboardPage = getDashboardTransactions($userId);
$transactionList = $dashboardPage['list'];
$dashboardHasMore = $dashboardPage['hasMore'];
$dashboardLastId = $dashboardPage['nextId'];
$batchDetailMap = getBatchDetailMap($transactionList, $userId);

[$chartMonths, $chartIncome, $chartExpense, $catChartData] = getDashboardChartData($userId);
[$wallets, $dailyWallets, $ewalletWallets, $targetWallets] = getDashboardWallets($userId);
[$totalWalletBalance, $legacyBalance, $dailyBalance] = getDashboardBalanceData($userId, $wallets);
[$pendingReadyCount, $pendingTotalCount, $pendingEditCount] = getDashboardPendingInfo($userId);
[$totalIncome, $totalExpense, $currentBalance, $txnThisMonth, $expenseThisMonth, $daysPassed, $avgDailyExpense, $savingsRate] = getDashboardFinancialStats($userId);
[$needsBalanceWarning, $balanceWarningMessage] = getDashboardBalanceWarning($currentBalance);

$archiveNotice = getFlashData('archive_notice');
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
            if (!empty($message)) echo showMessage($message, $messageType);
            if (!empty($archiveNotice)) echo showMessage($archiveNotice, 'info');
            ?>

            <!-- Wallet Summary -->
            <?php if (!empty($wallets)):
            $groupTotals = [];
            foreach (['daily','ewallet','target'] as $gtype) {
                $gw = array_filter($wallets, fn($w)=>$w['type']===$gtype);
                $total = 0;
                foreach ($gw as $w) $total += getWalletBalance($w['id'], $userId);
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
                            $bal = getWalletBalance($w['id'], $userId);
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
                <div id="budgetContent"><?php renderBudgetSectionHtml($userId, $budgetMonth, $budgetYear); ?></div>
            </section>

            <section class="data-grid">
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
                              <?php foreach ($transactionList as $transaction) echo renderTransactionRow($transaction, $batchDetailMap); ?>
                          <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
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
