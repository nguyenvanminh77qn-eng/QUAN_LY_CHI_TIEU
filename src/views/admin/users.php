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

function getUserPage($keyword, $lastId, $lastCreateAt, $limit = 10) {
    $where = "";
    $params = [];
    if (!empty($keyword)) {
        $where = "WHERE (username LIKE :keyword OR email LIKE :keyword)";
        $params['keyword'] = "%$keyword%";
    }
    $cursorWhere = '';
    $cursorParams = [];
    if ($lastId > 0 && $lastCreateAt !== '') {
        $cursorWhere = "AND (create_at < :last_ca OR (create_at = :last_ca AND id < :last_id))";
        $cursorParams = ['last_ca' => $lastCreateAt, 'last_id' => $lastId];
    }
    $fetchLimit = $limit + 1;
    $list = getAll("SELECT * FROM user $where $cursorWhere ORDER BY create_at DESC, id DESC LIMIT $fetchLimit", array_merge($params, $cursorParams));
    $hasMore = count($list) > $limit;
    if ($hasMore) $list = array_slice($list, 0, $limit);
    $next = ['id' => 0, 'create_at' => ''];
    if (!empty($list)) {
        $lastItem = end($list);
        $next = ['id' => $lastItem['id'], 'create_at' => $lastItem['create_at']];
    }
    return ['list' => $list, 'hasMore' => $hasMore, 'nextId' => $next['id'], 'nextCreateAt' => $next['create_at']];
}

function renderUserRow($user) {
    $onlineCheck = countRows("SELECT id FROM logintoken WHERE user_id = :uid", ["uid" => $user['id']]);
    $roleBg = $user['role'] === 'admin' ? '#d4a843' : '#3498db';
    $html = '<tr class="filter-tr">';
    $html .= '<td class="filter-td">' . htmlspecialchars($user['username']) . '</td>';
    $html .= '<td class="filter-td">' . htmlspecialchars($user['email']) . '</td>';
    $html .= '<td class="filter-td"><span style="padding:2px 8px;border-radius:10px;font-size:0.8em;background:' . $roleBg . ';color:white;">' . strtoupper($user['role']) . '</span></td>';
    $html .= '<td class="filter-td">' . ($user['status'] != 0 ? '<span style="color:green;">✔ Đã kích hoạt</span>' : '<span style="color:red;">✘ Chưa kích hoạt</span>') . '</td>';
    $html .= '<td class="filter-td">';
    if ($user['status'] == 1) {
        $html .= $onlineCheck ? '<span style="color:green;">✔ Đang hoạt động</span>' : '<span style="color:#666;">○ Đang ngoại tuyến</span>';
    } elseif ($user['status'] == 2) {
        $html .= '<span style="color:red;">🔒 Đã bị khóa</span>';
    } else {
        $html .= '<span style="color:orange;">⏳ Chờ kích hoạt</span>';
    }
    $html .= '</td>';
    $html .= '<td class="filter-td text-center">';
    if ($user['role'] !== 'admin') {
        $labels = [0 => '✅ Kích hoạt', 1 => '🔒 Khóa', 2 => '🔓 Mở'];
        $titles = [0 => 'Kích hoạt hộ', 1 => 'Khóa tài khoản', 2 => 'Mở khóa'];
        $html .= '<form action="?template=admin&action=users" method="POST" style="display:inline;">';
        $html .= '<input type="hidden" name="id" value="' . $user['id'] . '">';
        $html .= '<input type="hidden" name="current_status" value="' . $user['status'] . '">';
        $html .= '<button type="submit" name="toggle_status" class="action-btn status" title="' . ($titles[$user['status']] ?? '') . '">' . ($labels[$user['status']] ?? '') . '</button>';
        $html .= '</form>';
    } else {
        $html .= '<span style="color:#999;font-style:italic;font-size:0.9em;">Không được phép</span>';
    }
    $html .= '</td></tr>';
    return $html;
}

// ── AJAX: Cursor-based pagination (load more) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $page = getUserPage(
        isset($_GET['keyword']) ? trim($_GET['keyword']) : '',
        isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0,
        isset($_GET['last_create_at']) ? $_GET['last_create_at'] : ''
    );
    $rowsHtml = '';
    foreach ($page['list'] as $user) $rowsHtml .= renderUserRow($user);
    jsonResponse(true, '', [
        'rows' => $rowsHtml, 'has_more' => $page['hasMore'],
        'next_last_id' => $page['nextId'], 'next_last_create_at' => $page['nextCreateAt'],
        'count' => count($page['list']),
    ]);
}

layout("header", [
    "title" => "Quản lý thành viên",
    "css" => ["layout/sidebar", "pages/user/filter", "pages/admin/theme"] 
]);
$view = 'users';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$userPage = getUserPage($keyword, 0, '');
$userList = $userPage['list'];
$hasMoreUsers = $userPage['hasMore'];
$usersLastId = $userPage['nextId'];
$usersLastCreateAt = $userPage['nextCreateAt'];

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
                    <h1>Quản Lý Thành Viên</h1>
                </div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <?php if(!empty($message)) echo showMessage($message, $message_type); ?>

            <div class="card filter-result-card">
                <div class="filter-toolbar">
                    <div class="filter-toolbar-left">
                        <h3>Danh sách người dùng</h3>
                    </div>
                    <form action="" method="GET" style="display: flex; gap: 10px;">
                        <input type="hidden" name="template" value="admin">
                        <input type="hidden" name="action" value="users">
                        <input type="text" name="keyword" placeholder="Tìm tên hoặc email..." class="filter-input" value="<?= htmlspecialchars($keyword) ?>">
                        <button type="submit" class="filter-btn-submit">Tìm kiếm</button>
                    </form>
                </div>

                <div class="filter-table-wrapper">
                    <div class="filter-arrows" style="display:none;">
                        <button type="button" class="filter-arrow filter-arrow--prev" title="Trang trước">&#x2039;</button>
                        <button type="button" class="filter-arrow filter-arrow--next" title="Trang sau">&#x203A;</button>
                    </div>
                    <table class="filter-data-table">
                        <thead class="filter-thead">
                        <tr>
                            <th class="filter-th">Username</th>
                            <th class="filter-th">Email</th>
                            <th class="filter-th">Vai trò</th>
                            <th class="filter-th">Tài khoản</th>
                            <th class="filter-th">Trạng thái</th>
                            <th class="filter-th text-center">Hành động</th>
                        </tr>
                        </thead>
                    <tbody class="filter-tbody" id="userTbody">
                        <?php foreach ($userList as $user): ?>
                            <?= renderUserRow($user) ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div><!-- end filter-table-wrapper -->
            </div>
            <div class="load-more-container" id="userLoadMoreContainer">
                <button type="button" class="btn-load-more" id="userBtnLoadMore">Xem thêm</button>
                <div class="load-more-spinner" id="userLoadMoreSpinner" style="display:none;">Đang tải...</div>
            </div>
        </div>
    </main>
</div>

<script>
var usersLastId = <?= $usersLastId ?>;
var usersLastCreateAt = <?= json_encode($usersLastCreateAt) ?>;
var usersHasMore = <?= $hasMoreUsers ? 'true' : 'false' ?>;
</script>
<?php layout("footer", ["js" => ["pages/sidebar", "pages/admin-search"]]); ?>
