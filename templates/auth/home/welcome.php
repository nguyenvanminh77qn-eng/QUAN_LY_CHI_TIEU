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
