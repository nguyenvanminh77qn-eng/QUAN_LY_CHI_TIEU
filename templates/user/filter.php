<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

$view = 'filter';

$loginToken = getSession('loginToken');
if(empty($loginToken)){
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

layout("header", [
    "title" => "Lọc Dữ Liệu",
    "css" => ["layout/sidebar", "pages/user/filter"]
]);


$username = getSession('username');
$id = getSession('id');

// Lấy dữ liệu lọc từ Session
$where = getSession("filter_where") ?? "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0";
$params = getSession("filter_params") ?? ['user_id' => $id];
$oldInputs = getSession("filter_oldInputs") ?? [];
$sqlCount = "SELECT COUNT(*) as cnt FROM transaction JOIN category ON category.id = transaction.category_id $where";
$countResult = getOne($sqlCount, $params);
$totalResults = $countResult ? (int)$countResult['cnt'] : 0;

// Pagination Logic
$limit = 5;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pagination = getPagination($totalResults, $limit, $currentPage);
$offset = (int)$pagination['offset'];
$filterTransaction = [];
if($totalResults > 0){
    $sql = "SELECT transaction.id, transaction_date, category.name as category_name, category.icon as category_icon, description, price, type
            FROM transaction 
            JOIN category ON category.id = transaction.category_id 
            $where 
            ORDER BY transaction_date DESC, transaction.id DESC
            LIMIT $limit OFFSET $offset";
    
    $filterTransaction = getAll($sql, $params);
}

$categoryList = getAll("SELECT * FROM category ORDER BY name ASC");

$message = getFlashData("message");
$message_type = getFlashData("message_type");




?>

<div class="app-container filter-page">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">DIGITAL CURATOR</span>
                    <h1>Quản lý Chi Tiêu</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content">
            <div class="card filter-form-card">
                <form action="?template=user&action=filter" method="POST" class="filter-form-inline">
                    <input type="date" name="transaction_date" class="filter-input" 
                           value="<?= $oldInputs['transaction_date'] ?? '' ?>">

                    <select name="type" class="filter-input">
                        <option value="" selected >-- Tất cả loại --</option>
                        <option value="income" <?= (!empty($oldInputs['type']) && $oldInputs['type'] == 'income' ? 'selected' : '') ?>>Thu nhập (+)</option>
                        <option value="expense" <?= (!empty($oldInputs['type']) && $oldInputs['type'] == 'expense' ? 'selected' : '') ?>>Chi tiêu (-)</option>
                    </select>

                    <select name="category_id" class="filter-input">
                        <option value="" selected>-- Tất cả danh mục --</option>
                        <?php if($categoryList): foreach($categoryList as $dm): ?>
                            <option value="<?= $dm['id'] ?>" <?= (!empty($oldInputs['category_id']) && $oldInputs['category_id'] == $dm['id'] ? 'selected' : '') ?>><?= $dm['name'] ?></option>
                        <?php endforeach; endif; ?>
                    </select>

                    <input type="text" name="description" placeholder="Mô tả..." class="filter-input"
                           value="<?= $oldInputs['description'] ?? '' ?>">

                    <input type="number" name="price_min" placeholder="Số tiền từ..." class="filter-input" min="0" step="1000"
                           value="<?= $oldInputs['price_min'] ?? '' ?>">

                    <input type="number" name="price_max" placeholder="Số tiền đến..." class="filter-input" min="0" step="1000"
                           value="<?= $oldInputs['price_max'] ?? '' ?>">

                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div style="display:flex; gap:8px; align-items:center;">
                        <button type="submit" class="filter-btn-submit" name="filter-btn">Lọc</button>
                        <button type="submit" class="filter-btn-reset" name="filter-reset-btn">Xóa lọc</button>
                    </div>
                </form>
            </div>

            <?php if(!empty($message)) echo showMessage($message, $message_type); ?>

            <form action="?template=user&action=delete&page=<?= $currentPage ?>" method="POST" id="formDelete" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                <div class="card filter-result-card">
                    <div class="filter-toolbar">
                        <h3>Kết quả (<?= count($filterTransaction) ?>)</h3>
                        <button type="submit" class="filter-btn-delete" name="btn-delete">
                            🗑️ Xóa các mục đã chọn
                        </button>
                    </div>

                    <table class="filter-data-table">
                        <thead class="filter-thead">
                            <tr>
                                <th class="filter-th">Ngày</th>
                                <th class="filter-th">Danh mục</th>
                                <th class="filter-th">Mô tả</th>
                                <th class="filter-th text-right">Số tiền</th>
                                <th class="filter-th text-center action-col">Hành động</th>
                                <th class="filter-th text-center checkbox-col">
                                    <input type="checkbox" id="checkAll">
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filterTransaction)): ?>
                                <tr>
                                    <td colspan="5" class="filter-td filter-empty-state">Không có giao dịch nào phù hợp.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filterTransaction as $item): ?>
                                    <tr class="filter-tr">
                                        <td class="filter-td"><?= $item['transaction_date'] ?></td>
                                        <td class="filter-td category">
                                            <span class="category-badge">
                                                <span class="category-badge__icon"><?= $item['category_icon'] ?? '📦' ?></span>
                                                <span class="category-badge__name"><?= htmlspecialchars($item['category_name']) ?></span>
                                            </span>
                                        </td>
                                        <td class="filter-td desc"><?= $item['description'] ?></td>
                                        <td class="filter-td <?= $item['type'] == 'income' ? 'income' : 'expense' ?>">
                                            <?= $item['type'] == 'income' ? '+' : '-' ?> <?= number_format($item['price'], 0, ',', '.') ?> đ
                                        </td>
                                        <td class="filter-td text-center">
                                            <a href="?template=user&action=edit&id=<?= $item['id'] ?>" class="btn-action-edit">Sửa</a>
                                        </td>
                                        <td class="filter-td text-center">
                                            <input type="checkbox" class="checkItem" name="ids[]" value="<?= $item['id'] ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?= renderPagination($pagination['totalPages'], $pagination['currentPage'], "?template=user&action=filter") ?>
            </form>
        </div>
    </main>
</div>

<?php
layout("footer", ["js" => ["pages/sidebar", "pages/user/filter"]]);
?>