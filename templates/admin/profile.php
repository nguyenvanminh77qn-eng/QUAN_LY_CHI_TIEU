<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');
$view = 'profile';

$loginToken = getSession('loginToken');
if(empty($loginToken)){
    setMessage("Bạn phải đăng nhập","error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=user&action=dashboard");
}

layout("header", [
    "title" => "Hồ Sơ Quản Trị Viên",
    "css" => ["layout/sidebar","pages/user/profile"]
]);
    
$username = getSession('username');
$id = getSession('id');
$user = getOne("SELECT * FROM user WHERE id = :id",["id"=>$id]);

$message = getFlashData("message");
$message_type = getFlashData("message_type");
$errors = getFlashData("errors");
?>
<div class="app-container">
    <?php layout('sidebar_admin', ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">ADMIN PANEL</span>
                    <h1>Hồ Sơ Quản Trị Viên</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤<?= htmlspecialchars($username)?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <div class="page-container">
                <?php if(!empty($message)) echo showMessage($message, $message_type); ?>
                <div class="profile-layout">
                    <div class="profile-header card-box">
                        <div class="profile-avatar">
                            <span class="avatar-text"><?= strtoupper($user['username'][0]) ?></span>
                        </div>
                        <div class="profile-info">
                            <h2 class="profile-name"><?= $user['username'] ?></h2>
                            <p class="profile-email"><?= $user['email'] ?></p>
                            <span class="profile-role">Quản trị viên</span>
                        </div>
                    </div>
            
                    <div class="card-box profile-form-card">
                        <h3 class="section-title">Thông tin cá nhân</h3>
                        <form id="profileForm" class="profile-form">
                            <div class="form-group">
                                <label class="form-label">HỌ VÀ TÊN</label>
                                <input type="text" class="form-input" value="<?= $user['username'] ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">EMAIL</label>
                                <input type="email" class="form-input" value="<?= $user['email']?>" disabled>
                                <small class="form-hint">Email dùng để đăng nhập không thể thay đổi.</small>
                            </div>
            
                            <div class="form-group">
                                <label class="form-label">SỐ ĐIỆN THOẠI</label>
                                <input type="tel" class="form-input" placeholder="Nhập số điện thoại của bạn..." value="<?= $user['phone']?>" disabled>
                            </div>
                            
                        </form>
                    </div>

                    <div class="card-box profile-form-card" style="margin-top: 20px;">
                        <h3 class="section-title">Đổi mật khẩu</h3>
                        <form action="?template=admin&action=profile" method="POST" id="changePasswordForm" class="profile-form">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <div class="form-group">
                                <label class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" name="old_password" class="form-input" placeholder="Nhập mật khẩu hiện tại..." required>
                                <?= form_error($errors, 'old_password') ?>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" name="new_password" class="form-input" placeholder="Nhập mật khẩu mới..." required>
                                <?= form_error($errors, 'new_password') ?>
                            </div>
            
                            <div class="form-group">
                                <label class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_password" class="form-input" placeholder="Nhập lại mật khẩu mới..." required>
                                <?= form_error($errors, 'confirm_password') ?>
                            </div>
            
                            <div class="form-actions" style="margin-top: 20px;">
                                <button type="submit" name="btn-change-password" class="btn-primary" style="padding: 10px 20px; border: none; border-radius: 6px; background-color: var(--primary-color, #4CAF50); color: white; cursor: pointer; font-weight: bold;">Cập nhật mật khẩu</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php
layout("footer", ["js" => ["pages/sidebar"]]);
