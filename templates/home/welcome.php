<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

// Nếu đã đăng nhập thì chuyển hướng vào dashboard tương ứng
if(!empty(getSession('loginToken'))){
    $role = getSession('role');
    if($role == 'admin'){
        redirect('?template=admin&action=dashboard');
    }else{
        redirect('?template=user&action=dashboard');
    }
}

layout("header", [
    "title" => "MoneyMaster - Quản Lý Chi Tiêu",
    "css" => ["pages/home/welcome"]
]);
?>

<main class="welcome-container">
    <div class="welcome-content animate-slide-up">
        <div class="brand-logo">MoneyMaster</div>
        <h1>Quản Lý Chi Tiêu</h1>
        <p>Theo dõi thu chi, lập ngân sách và kiểm soát tài chính cá nhân một cách dễ dàng.</p>
        <div class="action-buttons">
            <a href="?template=auth&action=login.view" class="btn btn-solid">Đăng nhập</a>
            <a href="?template=auth&action=register.view" class="btn btn-outline">Đăng ký</a>
        </div>
    </div>
    <div class="welcome-footer animate-fade-in-delayed">
        &copy; 2026 MoneyMaster. All rights reserved.
    </div>
</main>

<?php
layout("footer", ["js" => []]);
?>
