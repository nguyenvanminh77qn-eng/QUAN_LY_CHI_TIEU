<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if (empty(getSession('loginToken'))) {
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'admin') {
    redirect("?template=user&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['broadcast_notification'])) {
        $filterAll = filter();
        $message = $filterAll['message'] ?? '';
        $type = $filterAll['type'] ?? 'info';
        
        if (!empty($message)) {
            // Tắt các thông báo cũ
            update('notifications', ['is_active' => 0], "is_active = 1", []);
            
            // Thêm thông báo mới
            $dataInsert = [
                'message' => $message,
                'type' => $type,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if (insert('notifications', $dataInsert)) {
                setMessage("Phát thông báo thành công!", "success");
            } else {
                setMessage("Lỗi khi phát thông báo.", "error");
            }
        }
    } elseif (isset($_POST['disable_notification'])) {
        $id = filter()['disable_notification'] ?? 0;
        if ($id > 0) {
            update('notifications', ['is_active' => 0], "id = :id", ['id' => $id]);
            setMessage("Đã tắt thông báo.", "success");
        }
    }
}

redirect("?template=admin&action=dashboard");
?>
