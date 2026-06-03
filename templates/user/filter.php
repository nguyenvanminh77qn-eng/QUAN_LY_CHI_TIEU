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

// AJAX pagination handler
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');

    $userId = getSession('id');
    $where = getSession("filter_where") ?? "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0";
    $where = preg_replace('/(?<![.\w])type\s*=/', 'transaction.type =', $where);
    $params = getSession("filter_params") ?? ['user_id' => $userId];

    $sqlCount = "SELECT COUNT(*) as cnt FROM transaction JOIN category ON category.id = transaction.category_id $where";
    $countResult = getOne($sqlCount, $params);
    $totalResults = $countResult ? (int)$countResult['cnt'] : 0;

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 5;
    $pagination = getPagination($totalResults, $limit, $page);
    $offset = (int)$pagination['offset'];

    $rowsHtml = '';
    if ($totalResults > 0) {
        $sql = "SELECT transaction.id, transaction_date, category.name as category_name, category.icon as category_icon, description, price, transaction.type
                FROM transaction 
                JOIN category ON category.id = transaction.category_id 
                $where 
                ORDER BY transaction_date DESC, transaction.id DESC
                LIMIT $limit OFFSET $offset";
        $items = getAll($sql, $params);
        foreach ($items as $item) {
            $priceFormatted = number_format($item['price'], 0, ',', '.');
            $typeClass = $item['type'] === 'income' ? 'income' : 'expense';
            $sign = $item['type'] === 'income' ? '+' : '-';
            $rowsHtml .= '<tr class="filter-tr">';
            $rowsHtml .= '<td class="filter-td">' . htmlspecialchars($item['transaction_date']) . '</td>';
            $rowsHtml .= '<td class="filter-td category"><span class="category-badge"><span class="category-badge__icon">' . htmlspecialchars($item['category_icon'] ?? '📦') . '</span><span class="category-badge__name">' . htmlspecialchars($item['category_name']) . '</span></span></td>';
            $rowsHtml .= '<td class="filter-td desc">' . htmlspecialchars($item['description']) . '</td>';
            $rowsHtml .= '<td class="filter-td ' . $typeClass . '">' . $sign . ' ' . $priceFormatted . ' đ</td>';
            $rowsHtml .= '<td class="filter-td text-center"><a href="?template=user&action=edit&id=' . $item['id'] . '" class="btn-action-edit">Sửa</a></td>';
            $rowsHtml .= '<td class="filter-td text-center"><input type="checkbox" class="checkItem" name="ids[]" value="' . $item['id'] . '"></td>';
            $rowsHtml .= '</tr>';
        }
    } else {
        $rowsHtml .= '<tr><td colspan="6" class="filter-td filter-empty-state">Không có giao dịch nào phù hợp.</td></tr>';
    }

    echo json_encode([
        'rows' => $rowsHtml,
        'pagination' => renderPagination($pagination['totalPages'], $pagination['currentPage'], "?template=user&action=filter"),
        'count' => count($items ?? []),
        'totalPages' => $pagination['totalPages'],
        'currentPage' => $pagination['currentPage']
    ]);
    exit;
}

layout("header", [
    "title" => "Lọc Dữ Liệu",
    "css" => ["layout/sidebar", "pages/user/filter"]
]);


$username = getSession('username');
$id = getSession('id');

// Lấy dữ liệu lọc từ Session
$where = getSession("filter_where") ?? "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0";
// Sanitize: ensure standalone 'type' is always prefixed with 'transaction.' (old session data may lack it)
$where = preg_replace('/(?<![.\w])type\s*=/', 'transaction.type =', $where);
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
    $sql = "SELECT transaction.id, transaction_date, category.name as category_name, category.icon as category_icon, description, price, transaction.type, wallet.name as wallet_name, wallet.type as wallet_type
            FROM transaction 
            JOIN category ON category.id = transaction.category_id 
            LEFT JOIN wallet ON wallet.id = transaction.wallet_id
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
                        <div class="filter-toolbar-left">
                            <h3>Kết quả (<span class="result-count"><?= count($filterTransaction) ?></span>)</h3>
                        </div>
                        <button type="submit" class="filter-btn-delete" name="btn-delete">
                            🗑️ Xóa các mục đã chọn
                        </button>
                    </div>

                    <div class="filter-table-wrapper">
                        <div class="filter-arrows">
                            <button type="button" class="filter-arrow filter-arrow--prev" title="Trang trước" aria-label="Trang trước">&#x2039;</button>
                            <button type="button" class="filter-arrow filter-arrow--next" title="Trang sau" aria-label="Trang sau">&#x203A;</button>
                        </div>
                        <table class="filter-data-table">
                            <thead class="filter-thead">
                                <tr>
                                    <th class="filter-th">Ngày</th>
                                    <th class="filter-th">Danh mục</th>
                                    <th class="filter-th">Mô tả</th>
                                    <th class="filter-th">Nguồn</th>
                                    <th class="filter-th text-right">Số tiền</th>
                                    <th class="filter-th text-center action-col">Hành động</th>
                                    <th class="filter-th text-center checkbox-col">
                                        <input type="checkbox" id="checkAll">
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="filter-tbody">
                            <?php if (empty($filterTransaction)): ?>
                                <tr>
                                    <td colspan="6" class="filter-td filter-empty-state">Không có giao dịch nào phù hợp.</td>
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
                                        <td class="filter-td"><?= !empty($item['wallet_name']) ? '<span class="source-badge source-wallet type-' . htmlspecialchars($item['wallet_type'] ?? 'daily') . '">' . htmlspecialchars($item['wallet_name']) . '</span>' : '<span class="source-badge source-manual">Nhập tay</span>' ?></td>
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
                </div><!-- end filter-table-wrapper -->
                </div><!-- end filter-result-card -->
                <div class="pagination-ajax" data-total-pages="<?= $pagination['totalPages'] ?>" data-current-page="<?= $pagination['currentPage'] ?>">
                    <?= renderPagination($pagination['totalPages'], $pagination['currentPage'], "?template=user&action=filter") ?>
                </div>
            </form>
        </div>
    </main>
</div>

<?php
layout("footer", ["js" => ["pages/sidebar", "pages/user/filter"]]);
?>