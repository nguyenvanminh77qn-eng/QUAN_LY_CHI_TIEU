<?php
if (!CODE) die('Ban khong co quyen truy cap vao trang nay');

if (empty(getSession('loginToken'))) {
    redirect("?template=auth&action=login.view");
}

if (getSession('role') !== 'admin') {
    redirect("?template=user&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    cleanupNotifications();

    if (isset($_POST['broadcast_notification'])) {
        $filterAll = filter();
        $message    = trim($filterAll['message'] ?? '');
        $typeRaw    = $filterAll['type'] ?? '';
        $expiresAt  = $filterAll['expires_at'] ?? '';

        $validTypes = ['info', 'warning', 'success', 'error'];

        $expiresAtTimestamp = strtotime($expiresAt);

        if ($message === '') {
            setMessage("Noi dung thong bao khong duoc de trong", "error");
        } elseif ($expiresAtTimestamp === false) {
            setMessage("Thoi gian het han khong hop le.", "error");
        } elseif ($expiresAtTimestamp <= time()) {
            setMessage("Thoi gian het han phai lon hon thoi diem hien tai.", "error");
        } elseif (!in_array($typeRaw, $validTypes, true)) {
            setMessage("Loai thong bao khong hop le.", "error");
        } else {
            update('notifications', ['is_active' => 0], "is_active = 1", []);

            $inserted = insert('notifications', [
                'message'    => $message,
                'type'       => $typeRaw,
                'expires_at' => date('Y-m-d H:i:s', $expiresAtTimestamp),
                'is_active'  => 1,
            ]);

            if ($inserted) {
                setMessage("Phat thong bao thanh cong.", "success");
            } else {
                setMessage("Loi khi phat thong bao.", "error");
            }
        }
    } elseif (isset($_POST['disable_notification'])) {
        $id = (int)(filter()['disable_notification'] ?? 0);
        if ($id > 0) {
            update('notifications', ['is_active' => 0], "id = :id", ['id' => $id]);
            setMessage("Da tat thong bao.", "success");
        }
    }
}

redirect("?template=admin&action=dashboard");
?>
