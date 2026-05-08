<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if(empty(getSession('loginToken'))) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=user&action=dashboard");
}

layout("header", [
    "title" => "Quản lý danh mục",
    "css" => ["layout/sidebar", "pages/user/filter"] 
]);
$view = 'categories';

// Search and Pagination Logic

$limit = 5;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : "";
$where = "";
$params = [];
if (!empty($keyword)) {
    $where = "WHERE c.name LIKE :keyword";
    $params['keyword'] = "%$keyword%";
}

$totalCategoriesCount = countRows("SELECT id FROM category c $where", $params);
$pagination = getPagination($totalCategoriesCount, $limit, $currentPage);
$offset = $pagination['offset'];

$categoryList = getAll("
    SELECT c.*, COUNT(t.id) as usage_count 
    FROM category c
    LEFT JOIN transaction t ON c.id = t.category_id 
    $where
    GROUP BY c.id 
    ORDER BY usage_count DESC, c.name ASC
    LIMIT $limit OFFSET $offset
", $params);

$message = getFlashData("message");
$message_type = getFlashData("message_type");
?>

<div class="app-container">
    <?php layout("sidebar_admin", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">ADMINISTRATION</span>
                    <h1>Thống Kê Danh Mục</h1>
                </div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <?php if(!empty($message)) echo showMessage($message, $message_type); ?>

            <div class="card" style="margin-bottom: 20px; padding: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Phần Thêm mới -->
                    <div class="add-section">
                        <h3 style="margin-bottom: 15px;">Thêm danh mục mới</h3>
                        <form action="?template=admin&action=categories" method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <input type="text" name="icon" placeholder="😀" class="filter-input" style="width: 50px; height: 40px; padding: 5px; text-align:center; font-size:20px; box-sizing: border-box;" title="Nhập một icon (emoji)" maxlength="5">
                            <input type="text" name="name" placeholder="Tên danh mục..." class="filter-input" style="flex: 1; height: 40px; box-sizing: border-box;" required>
                            <button type="submit" name="add_category" class="filter-btn-submit" style="height: 40px; min-width: 100px;">Thêm</button>
                        </form>
                    </div>

                    <!-- Phần Tìm kiếm -->
                    <div class="search-section">
                        <h3 style="margin-bottom: 15px;">Tìm kiếm danh mục</h3>
                        <form action="" method="GET" style="display: flex; gap: 10px;">
                            <input type="hidden" name="template" value="admin">
                            <input type="hidden" name="action" value="categories">
                            <input type="text" name="keyword" placeholder="Nhập tên danh mục..." class="filter-input" style="flex: 1; height: 40px; box-sizing: border-box;" value="<?= htmlspecialchars($keyword) ?>">
                            <button type="submit" class="filter-btn-submit" style="height: 40px; min-width: 100px;">Tìm kiếm</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card filter-result-card">
                <table class="filter-data-table">
                    <thead class="filter-thead">
                        <tr>
                            <th class="filter-th">STT</th>
                            <th class="filter-th" style="text-align:center;">Icon</th>
                            <th class="filter-th">Tên danh mục</th>
                            <th class="filter-th text-center">Số lần sử dụng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = $offset; 
                        foreach ($categoryList as $cat): 
                            $count++;
                        ?>
                            <tr class="filter-tr">
                                <td class="filter-td"><?= $count ?></td>
                                <td class="filter-td" style="text-align:center; font-size:24px;"><?= $cat['icon'] ?? '📦' ?></td>
                                <td class="filter-td"><?= htmlspecialchars($cat['name']) ?></td>
                                <td class="filter-td text-center">
                                    <span class="status-badge" style="background: #e1f5fe; color: #0288d1; padding: 5px 15px; border-radius: 20px; font-weight: bold;">
                                        <?= number_format($cat['usage_count']) ?> lần
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= renderPagination($pagination['totalPages'], $pagination['currentPage'], "?template=admin&action=categories&keyword=".urlencode($keyword)) ?>
        </div>
    </main>
</div>

<?php layout("footer", ["js" => ["pages/sidebar"]]); ?>
