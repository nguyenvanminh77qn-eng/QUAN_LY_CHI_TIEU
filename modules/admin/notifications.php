<?php
if (!CODE) die('Ban khong co quyen truy cap vao trang nay');

if (empty(getSession('loginToken'))) {
    redirect("?template=auth&action=login.view");
}

if (getSession('role') !== 'admin') {
    redirect("?template=user&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['broadcast_notification'])) {
        $filterAll = filter();
        $message    = trim($filterAll['message'] ?? '');
        $typeRaw    = $filterAll['type'] ?? '';
        $expiresAt  = $filterAll['expires_at'] ?? '';

        $validTypes = ['info', 'warning', 'success', 'error'];

        $expiresAtTimestamp = strtotime($expiresAt);

        if ($message === '') {
            setMessage("Nội dung thông báo không được để trống", "error");
        } elseif ($expiresAtTimestamp === false) {
            setMessage("Thời gian hết hạn không hợp lệ.", "error");
        } elseif ($expiresAtTimestamp <= time()) {
            setMessage("Thời gian hết hạn phải lớn hơn thời điểm hiện tại.", "error");
        } elseif (!in_array($typeRaw, $validTypes, true)) {
            setMessage("Loại thông báo không hợp lệ.", "error");
        } else {
            update('notifications', ['is_active' => 0], "is_active = 1", []);

            $inserted = insert('notifications', [
                'message'    => $message,
                'type'       => $typeRaw,
                'expires_at' => date('Y-m-d H:i:s', $expiresAtTimestamp),
                'is_active'  => 1,
                'created_by' => (int)getSession('id'),
            ]);

            if ($inserted) {
                setMessage("Phát thông báo thành công.", "success");
            } else {
                setMessage("Lỗi khi phát thông báo.", "error");
            }
        }
    } elseif (isset($_POST['disable_notification'])) {
        $id = (int)(filter()['disable_notification'] ?? 0);
        if ($id > 0) {
            update('notifications', ['is_active' => 0], "id = :id", ['id' => $id]);
            setMessage("Đã tắt thông báo.", "success");
        }
    }
}

redirect("?template=admin&action=notifications");
?>
