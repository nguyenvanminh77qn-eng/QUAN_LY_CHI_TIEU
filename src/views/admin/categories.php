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

function getCategoryPage($keyword, $lastId, $lastUsageCount, $lastName, $limit = 5) {
    $where = "";
    $params = [];
    if (!empty($keyword)) {
        $where = "WHERE c.name LIKE :keyword";
        $params['keyword'] = "%$keyword%";
    }
    $cursorHaving = '';
    $cursorParams = [];
    if ($lastId > 0 && $lastUsageCount >= 0 && $lastName !== '') {
        $cursorHaving = "HAVING usage_count < :last_uc OR (usage_count = :last_uc2 AND c.name > :last_n) OR (usage_count = :last_uc3 AND c.name = :last_n2 AND c.id > :last_id)";
        $cursorParams = ['last_uc' => $lastUsageCount, 'last_uc2' => $lastUsageCount, 'last_n' => $lastName, 'last_uc3' => $lastUsageCount, 'last_n2' => $lastName, 'last_id' => $lastId];
    }
    $fetchLimit = $limit + 1;
    $list = getAll("SELECT c.*, COUNT(t.id) as usage_count FROM category c LEFT JOIN transaction t ON c.id = t.category_id $where GROUP BY c.id $cursorHaving ORDER BY usage_count DESC, c.name ASC LIMIT $fetchLimit", array_merge($params, $cursorParams));
    $hasMore = count($list) > $limit;
    if ($hasMore) $list = array_slice($list, 0, $limit);
    $next = ['id' => 0, 'usage_count' => -1, 'name' => ''];
    if (!empty($list)) {
        $lastItem = end($list);
        $next = ['id' => $lastItem['id'], 'usage_count' => $lastItem['usage_count'], 'name' => $lastItem['name']];
    }
    return ['list' => $list, 'hasMore' => $hasMore, 'nextId' => $next['id'], 'nextUsageCount' => $next['usage_count'], 'nextName' => $next['name']];
}

function renderCategoryRow($cat) {
    return '<tr class="filter-tr" id="row-' . $cat['id'] . '">'
        . '<td class="filter-td" style="text-align:center;font-size:24px;">' . htmlspecialchars($cat['icon'] ?? '📦') . '</td>'
        . '<td class="filter-td">' . htmlspecialchars($cat['name']) . '</td>'
        . '<td class="filter-td text-center"><span class="status-badge" style="background:#e1f5fe;color:#0288d1;padding:5px 15px;border-radius:20px;font-weight:bold;">' . number_format($cat['usage_count']) . ' lần</span></td>'
        . '<td class="filter-td text-center"><button type="button" onclick="openEditRow(' . $cat['id'] . ',\'' . htmlspecialchars(addslashes($cat['name'])) . '\',\'' . htmlspecialchars(addslashes($cat['icon'] ?? '📦')) . '\')" style="background:#3498db;color:#fff;border:none;border-radius:6px;padding:5px 12px;cursor:pointer;font-size:13px;">✏️ Sửa</button></td>'
        . '</tr>';
}

// ── AJAX: Cursor-based pagination (load more) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $page = getCategoryPage(
        isset($_GET['keyword']) ? trim($_GET['keyword']) : '',
        isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0,
        isset($_GET['last_usage_count']) ? (int)$_GET['last_usage_count'] : -1,
        isset($_GET['last_name']) ? $_GET['last_name'] : ''
    );
    $rowsHtml = '';
    foreach ($page['list'] as $cat) $rowsHtml .= renderCategoryRow($cat);
    jsonResponse(true, '', [
        'rows' => $rowsHtml, 'has_more' => $page['hasMore'],
        'next_last_id' => $page['nextId'], 'next_last_usage_count' => $page['nextUsageCount'], 'next_last_name' => $page['nextName'],
        'count' => count($page['list']),
    ]);
}

layout("header", [
    "title" => "Quản lý danh mục",
    "css" => ["layout/sidebar", "pages/user/filter", "pages/admin/theme"] 
]);
$view = 'categories';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : "";
$catPage = getCategoryPage($keyword, 0, -1, '');
$categoryList = $catPage['list'];
$hasMoreCategories = $catPage['hasMore'];
$categoriesLastId = $catPage['nextId'];
$categoriesLastUsageCount = $catPage['nextUsageCount'];
$categoriesLastName = $catPage['nextName'];

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
                            <input type="text" name="icon" placeholder="🍕" class="filter-input category-icon-input" maxlength="5">
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
                <div class="filter-toolbar">
                    <div class="filter-toolbar-left">
                        <h3>Danh sách danh mục</h3>
                    </div>
                </div>
                <div class="filter-table-wrapper">
                    <div class="filter-arrows" style="display:none;">
                        <button type="button" class="filter-arrow filter-arrow--prev" title="Trang trước">&#x2039;</button>
                        <button type="button" class="filter-arrow filter-arrow--next" title="Trang sau">&#x203A;</button>
                    </div>
                    <table class="filter-data-table">
                        <thead class="filter-thead">
                        <tr>
                            <th class="filter-th" style="text-align:center;">Icon</th>
                            <th class="filter-th">Tên danh mục</th>
                            <th class="filter-th text-center">Số lần sử dụng</th>
                            <th class="filter-th text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="filter-tbody" id="catTbody">
                        <?php foreach ($categoryList as $cat): ?>
                            <?= renderCategoryRow($cat) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div><!-- end filter-table-wrapper -->
            </div>
            <div class="load-more-container" id="catLoadMoreContainer">
                <button type="button" class="btn-load-more" id="catBtnLoadMore">Xem thêm</button>
                <div class="load-more-spinner" id="catLoadMoreSpinner" style="display:none;">Đang tải...</div>
            </div>
        </div>
    </main>
</div>

<script>
var categoriesLastId = <?= $categoriesLastId ?>;
var categoriesLastUsageCount = <?= $categoriesLastUsageCount ?>;
var categoriesLastName = <?= json_encode($categoriesLastName) ?>;
var categoriesHasMore = <?= $hasMoreCategories ? 'true' : 'false' ?>;
</script>
<?php layout("footer", ["js" => ["pages/sidebar", "pages/admin-search"]]); ?>

<!-- Modal sửa danh mục -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:28px 32px; width:360px; box-shadow:0 8px 32px rgba(0,0,0,0.18);">
        <h3 style="margin:0 0 18px; color:#333;">Sửa danh mục</h3>
        <form action="?template=admin&action=categories" method="POST">
            <input type="hidden" name="edit_id" id="edit_id">
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:5px; text-transform:uppercase;">Icon</label>
                <input type="text" name="edit_icon" id="edit_icon" maxlength="5"
                       style="width:60px; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:22px; text-align:center; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#666; margin-bottom:5px; text-transform:uppercase;">Tên danh mục</label>
                <input type="text" name="edit_name" id="edit_name" required
                       style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; font-size:15px; box-sizing:border-box;">
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="closeEditModal()"
                        style="padding:8px 18px; border:1px solid #ddd; border-radius:6px; background:#f5f5f5; cursor:pointer;">Hủy</button>
                <button type="submit" name="edit_category"
                        style="padding:8px 18px; border:none; border-radius:6px; background:#3498db; color:#fff; font-weight:700; cursor:pointer;">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditRow(id, name, icon) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_icon').value = icon;
    var modal = document.getElementById('editModal');
    modal.style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
