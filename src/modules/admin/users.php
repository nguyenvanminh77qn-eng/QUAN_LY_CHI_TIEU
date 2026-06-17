<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'admin') {
    redirect("?template=user&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $filterAll = filter();
    $id = (int)$filterAll['id'];
    $currentStatus = (int)$filterAll['current_status'];
    
    // Kiểm tra quyền: Admin không được thao tác trên Admin khác
    $targetUser = getOne("SELECT role FROM user WHERE id = :id", ['id' => $id]);
    if ($targetUser && $targetUser['role'] === 'admin') {
        setMessage("Bạn không có quyền thực hiện hành động này đối với tài khoản quản trị viên khác.", "error");
        redirect("?template=admin&action=users");
    }
    
    // Logic: 
    // Nếu đang là 1 (Hoạt động) -> 2 (Khóa)
    // Nếu đang là 2 (Khóa) -> 1 (Hoạt động)
    // Nếu đang là 0 (Chưa kích hoạt) -> 1 (Kích hoạt luôn)
    
    $newStatus = $currentStatus;
    if ($currentStatus == 1) {
        $newStatus = 2; // Khóa
        // Xóa token để user bị out ngay lập tức
        delete('logintoken', "user_id = :user_id", ['user_id' => $id]);
    } else if ($currentStatus == 2 || $currentStatus == 0) {
        $newStatus = 1; // Mở khóa hoặc Kích hoạt hộ
    }
    
    $update = update('user', ['status' => $newStatus], "id = :id", ['id' => $id]);
    if ($update) {
        if ($newStatus == 1) {
            $msg = ($currentStatus == 0) ? "Kích hoạt tài khoản thành công" : "Mở khóa tài khoản thành công";
            setMessage($msg, "success");
        } else {
            setMessage("Khóa tài khoản thành công", "success");
        }
    } else {
        setMessage("Thao tác thất bại, thử lại sau", "error");
    }
    
    redirect("?template=admin&action=users");
}
