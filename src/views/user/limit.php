<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

$view = 'limit';

$loginToken = getSession('loginToken');
if (empty($loginToken)) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

$userId = getSession('id');
$username = getSession('username');

$categories = getCachedCategories('name');

$limits = getAll(
    "SELECT category_id, max_amount FROM category_limit WHERE user_id = :uid",
    ['uid' => $userId]
);
$limitMap = [];
foreach ($limits as $l) {
    $limitMap[$l['category_id']] = (float)$l['max_amount'];
}

layout("header", [
    "title" => "Hạn mức giao dịch",
    "css" => ["layout/sidebar", "pages/user/limit"]
]);

$message = getFlashData("message");
$message_type = getFlashData("message_type");
?>
<div class="app-container">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">TRANSACTION LIMITS</span>
                    <h1>Hạn mức giao dịch</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <?php if (!empty($message)) echo showMessage($message, $message_type); ?>

            <form id="limitForm" action="?template=user&action=limit" method="POST">
            <div class="limit-card">
                <div class="limit-card__header">
                    <div class="limit-card__header-left">
                        <h2 class="limit-card__title">Giới hạn theo danh mục</h2>
                        <p class="limit-card__desc">Đặt số tiền tối đa cho mỗi giao dịch theo từng danh mục</p>
                    </div>
                    <button type="submit" name="save_limit" class="limit-btn-save">Lưu hạn mức</button>
                </div>

                <div class="limit-table-wrap">
                    <table class="limit-table">
                        <thead>
                            <tr>
                                <th class="limit-th limit-th--cat">Danh mục</th>
                                <th class="limit-th limit-th--current">Hạn mức hiện tại</th>
                                <th class="limit-th limit-th--new">Hạn mức mới</th>
                                <th class="limit-th limit-th--status">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="4" class="limit-td limit-empty">Chưa có danh mục nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat):
                                    $currentLimit = $limitMap[$cat['id']] ?? 0;
                                    $hasLimit = $currentLimit > 0;
                                ?>
                                <tr class="limit-row">
                                    <td class="limit-td limit-td--cat">
                                        <span class="limit-cat-badge">
                                            <span class="limit-cat-icon"><?= htmlspecialchars($cat['icon'] ?? '📦') ?></span>
                                            <span class="limit-cat-name"><?= htmlspecialchars($cat['name']) ?></span>
                                        </span>
                                    </td>
                                    <td class="limit-td limit-td--current">
                                        <?php if ($hasLimit): ?>
                                            <span class="limit-current-value"><?= number_format($currentLimit, 0, ',', '.') ?> ₫</span>
                                        <?php else: ?>
                                            <span class="limit-current-none">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="limit-td limit-td--new">
                                        <input type="text"
                                               name="amounts[<?= $cat['id'] ?>]"
                                               value="<?= $hasLimit ? number_format($currentLimit, 0, ',', '.') : '' ?>"
                                               class="limit-input"
                                               placeholder="0"
                                               inputmode="numeric">
                                    </td>
                                    <td class="limit-td limit-td--status">
                                        <?php if ($hasLimit): ?>
                                            <span class="limit-pill limit-pill--active">Đã đặt</span>
                                        <?php else: ?>
                                            <span class="limit-pill limit-pill--inactive">Chưa đặt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </form>
        </div>
    </main>
</div>

<?php layout("footer", ["js" => ["pages/sidebar", "pages/user/limit"]]); ?>
