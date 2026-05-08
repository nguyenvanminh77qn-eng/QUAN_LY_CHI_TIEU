<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');
layout("header", [
    "title" => "Quản Lý Chi Tiêu",
    "css" => ["layout/sidebar","pages/user/dashboard"]
]);
$view = 'dashboard';

$loginToken = getSession('loginToken');
if(empty($loginToken)){
    setMessage("Bạn phải đăng nhập","error");
    redirect("?template=auth&action=login.view");
}
$username = getSession('username');
$user = getOne("SELECT user.id FROM user JOIN logintoken ON user.id = logintoken.user_id WHERE loginToken = :loginToken",["loginToken"=>$loginToken]);
setSession("id",$user['id']);

// Pagination Logic
$limit = 5;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$totalTransactions = countRows(
    "SELECT id FROM transaction WHERE user_id = :id ORDER BY transaction_date DESC, id DESC",
    ["id" => $user['id']]
);

$pagination = getPagination($totalTransactions, $limit, $currentPage);


$offset = (int)$pagination['offset'];

$transactionList = getAll("
    SELECT transaction_date, category.name, category.icon, description, price, type
    FROM transaction 
    JOIN category ON category.id = transaction.category_id 
    WHERE transaction.user_id = :id
    ORDER BY transaction_date DESC, transaction.id DESC
    LIMIT $limit OFFSET $offset", [
    "id" => $user['id']
]);

$totalInCome = getTotalSum($user['id'],'income');
$totalExpense = getTotalSum($user['id'],'expense');

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
                <div class="user-box">👤 <?=htmlspecialchars($username)?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            

            <section class="stats-grid">
                <div class="stat-card dark-green-card">
                    <p class="card-title">SỐ DƯ KHẢ DỤNG</p>
                    <h2><?= number_format($totalInCome - $totalExpense, 0, ',', '.') ?> đ</h2>
                </div>
                <div class="stat-card">
                    <p class="card-amount positive">+ <?= number_format($totalInCome, 0, ',', '.') ?> đ</p>
                    <h3>Tổng thu</h3>
                    <p class="card-desc">Lương & Các khoản thu nhập phụ</p>
                </div>
                <div class="stat-card">
                    <p class="card-amount negative">- <?= number_format($totalExpense, 0, ',', '.') ?> đ</p>
                    <h3>Tổng chi</h3>
                    <p class="card-desc">Chi phí sinh hoạt & Mua sắm</p>
                </div>
            </section>


            <?php 
            $message = getFlashData("message");
            $message_type = getFlashData("message_type");
            if (!empty($message)) echo showMessage($message, $message_type); 
            ?>
            <section class="quick-add-section card-box" style="margin-bottom: 20px; padding: 15px; border-radius: 12px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid #00c6ff;">
                <form action="?template=user&action=quick_add" method="POST" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="quick_text" placeholder="Thêm nhanh (VD: Đổ xăng 50k, Trả tiền phòng 3tr, Lương tháng 15000000...)" class="filter-input" style="flex: 1; height: 45px; font-size: 16px; border-radius: 8px; border: 1px solid #ddd; padding: 0 15px;" required autocomplete="off">
                    <button type="submit" name="quick_add_btn" style="height: 45px; padding: 0 25px; border-radius: 8px; border: none; background: linear-gradient(135deg, #00c6ff, #0072ff); color: #fff; font-weight: bold; cursor: pointer; transition: 0.3s;">Tự động phân tích 🚀</button>
                </form>
            </section>


            <section class="data-grid">

                <div class="transactions-section card-box">
                    <div class="section-header">
                        <h3>Giao dịch gần đây</h3>
                    </div>
                    <table class="data-table">
                        <tr>
                            <th>NGÀY</th>
                            <th>DANH MỤC</th>
                            <th>MÔ TẢ</th>
                            <th>SỐ TIỀN</th>
                        </tr>
                        <?php if(empty($transactionList)) : ?>
                            <tr>
                                    <td colspan="5" style="text-align:center;">Chưa có giao dịch nào.</td>
                            </tr>
                        <?php else : ?>
                        <?php foreach($transactionList as $transaction): ?>
                        <tr>
                            <td><?=$transaction['transaction_date']?></td>
                            <td><?= htmlspecialchars($transaction['icon'] ?? '📦') ?> <?= htmlspecialchars($transaction['name']) ?></td>
                            <td><?=$transaction['description']?></td>
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
layout("footer", ["js" => ["pages/sidebar"]]);
