<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');
layout("header", [
    "title" => "Chào mừng tới MoneyMaster",
    "css" => ["pages/home/welcome"]
]);
?>

<div class="welcome-container">
    <div class="welcome-content">
        <div class="brand-logo animate-fade-in">MoneyMaster</div>
        <h1 class="animate-slide-up">Làm chủ dòng tiền của bạn</h1>
        <p class="animate-slide-up-delay">Giải pháp quản lý chi tiêu cá nhân thông minh, đơn giản và hiệu quả nhất.</p>
        
        <div class="action-buttons animate-fade-in-delayed">
            <a href="?template=auth&action=login.view" class="btn btn-outline">Đăng nhập</a>
            <a href="?template=auth&action=register.view" class="btn btn-solid">Đăng ký ngay</a>
        </div>
    </div>
    
    <footer class="welcome-footer animate-fade-in-delayed">
        &copy; <?= date('Y') ?> MoneyMaster • Ổn định tài chính cho mọi nhà.
    </footer>
</div>

<?php
layout("footer");
?>