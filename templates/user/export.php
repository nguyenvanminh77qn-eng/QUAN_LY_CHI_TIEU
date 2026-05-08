<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

layout("header", [
    "title" => "Xuất Dữ Liệu",
    "css" => ["layout/sidebar", "pages/user/export"]
]);

$view = 'export';

$loginToken = getSession('loginToken');
if(empty($loginToken)){
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}

$username = getSession('username');
$id = getSession('id');

$categoryList = getAll("SELECT * FROM category ORDER BY name ASC");

$message = getFlashData("message");
$message_type = getFlashData("message_type");
?>

<div class="app-container export-page">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">DIGITAL CURATOR</span>
                    <h1>Xuất Dữ Liệu CSV</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <div class="page-container">
                <div class="card-box export-card">
                    <div class="export-header">
                        <h3 class="section-title">Tải xuống giao dịch của bạn</h3>
                        <p class="export-desc">Chọn khoảng thời gian và danh mục cần xuất thành file CSV. Để trống nếu muốn xuất tất cả.</p>
                    </div>
                    
                    <?php if(!empty($message)) echo showMessage($message, $message_type); ?>

                    <form action="?template=user&action=export" method="POST" class="export-form">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">TỪ NGÀY</label>
                                <input type="date" name="date_from" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">ĐẾN NGÀY</label>
                                <input type="date" name="date_to" class="form-input">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">LOẠI GIAO DỊCH</label>
                                <select name="type" class="form-input">
                                    <option value="">-- Tất cả loại --</option>
                                    <option value="income">Thu nhập (+)</option>
                                    <option value="expense">Chi tiêu (-)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">DANH MỤC</label>
                                <select name="category_id" class="form-input">
                                    <option value="">-- Tất cả danh mục --</option>
                                    <?php if($categoryList): foreach($categoryList as $dm): ?>
                                        <option value="<?= $dm['id'] ?>"><?= $dm['name'] ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="export-actions" style="margin-top: 30px;">
                            <button type="submit" name="btn-export-csv" class="btn-primary export-btn">
                                📥 XUẤT FILE CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php
layout("footer", ["js" => ["pages/sidebar"]]);
?>
