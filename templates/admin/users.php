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
    "title" => "Quản lý thành viên",
    "css" => ["layout/sidebar", "pages/user/filter"] 
]);
$view = 'users';

// Search and Pagination Logic
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$limit = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$where = "";
$params = [];
if (!empty($keyword)) {
    $where = "WHERE username LIKE :keyword OR email LIKE :keyword";
    $params['keyword'] = "%$keyword%";
}

$totalUsersCount = countRows("SELECT id FROM user $where", $params);
$pagination = getPagination($totalUsersCount, $limit, $currentPage);
$offset = $pagination['offset'];

$userList = getAll("SELECT * FROM user $where ORDER BY create_at DESC LIMIT $limit OFFSET $offset", $params);

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
                <div class="filter-toolbar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <h3>Danh sách người dùng (<?= $totalUsersCount ?>)</h3>
                    <form action="" method="GET" style="display: flex; gap: 10px;">
                        <input type="hidden" name="template" value="admin">
                        <input type="hidden" name="action" value="users">
                        <input type="text" name="keyword" placeholder="Tìm tên hoặc email..." class="filter-input" value="<?= htmlspecialchars($keyword) ?>">
                        <button type="submit" class="filter-btn-submit">Tìm kiếm</button>
                    </form>
                </div>

                <table class="filter-data-table">
                    <thead class="filter-thead">
                        <tr>
                            <th class="filter-th">STT</th>
                            <th class="filter-th">Username</th>
                            <th class="filter-th">Email</th>
                            <th class="filter-th">Vai trò</th>
                            <th class="filter-th">Tài khoản</th>
                            <th class="filter-th">Trạng thái</th>
                            <th class="filter-th text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = $offset; // Khởi tạo biến đếm dựa trên offset
                        foreach ($userList as $user): 
                            $count++; // Tăng biến đếm
                        ?>
                            <tr class="filter-tr">
                                <td class="filter-td"><?= $count ?></td>
                                <td class="filter-td"><?= htmlspecialchars($user['username']) ?></td>
                                <td class="filter-td"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="filter-td">
                                    <span style="padding: 2px 8px; border-radius: 10px; font-size: 0.8em; background: <?= $user['role'] == 'admin' ? '#e67e22' : '#3498db' ?>; color: white;">
                                        <?= strtoupper($user['role']) ?>
                                    </span>
                                </td>
                                <td class="filter-td">
                                    <?= $user['status'] != 0 ? '<span style="color: green;">✔ Đã kích hoạt</span>' : '<span style="color: red;">✘ Chưa kích hoạt</span>' ?>
                                </td>
                                <td class="filter-td">
                                    <?php if($user['status'] == 1) : ?>
                                        <?php if(countRows("SELECT id FROM logintoken WHERE user_id = :user_id", ["user_id" => $user['id']])) : ?>
                                            <span style="color: green;">✔ Đang hoạt động</span>
                                        <?php else : ?>
                                            <span style="color: #666;">○ Đang ngoại tuyến</span>
                                        <?php endif; ?>
                                    <?php elseif($user['status'] == 2) : ?>
                                        <span style="color: red;">🔒 Đã bị khóa</span>
                                    <?php else : ?>
                                        <span style="color: orange;">⏳ Chờ kích hoạt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="filter-td text-center">
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <form action="?template=admin&action=users" method="POST" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $user['status'] ?>">
                                            <button type="submit" name="toggle_status" class="action-btn status" 
                                                    title="<?= $user['status'] == 1 ? 'Khóa tài khoản' : ($user['status'] == 0 ? 'Kích hoạt hộ' : 'Mở khóa') ?>">
                                                <?php 
                                                    if($user['status'] == 1) echo '🔒 Khóa';
                                                    elseif($user['status'] == 2) echo '🔓 Mở';
                                                    else echo '✅ Kích hoạt';
                                                ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic; font-size: 0.9em;">Không được phép</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= renderPagination($pagination['totalPages'], $pagination['currentPage'], "?template=admin&action=users&keyword=".urlencode($keyword)) ?>
        </div>
    </main>
</div>

<?php layout("footer", ["js" => ["pages/sidebar"]]); ?>
