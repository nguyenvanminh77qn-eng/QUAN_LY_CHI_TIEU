<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if (isset($_POST['btn-delete'])) {
    $filterAll = filter();
    $userId = getSession('id');

    if (empty($filterAll['ids[]'])) {
        setMessage("Vui lòng chọn giao dịch để xóa", "error");
        redirect("?template=user&action=filter");
    }

    $ids = $filterAll['ids[]'];
    if (!is_array($ids)) {
        $ids = [$ids];
    }

    // Lọc chỉ giữ id là số nguyên dương
    $ids = array_filter(array_map('intval', $ids), fn($v) => $v > 0);

    if (empty($ids)) {
        setMessage("Dữ liệu không hợp lệ", "error");
        redirect("?template=user&action=filter");
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$userId], array_values($ids));

    // Đảm bảo user chỉ xóa giao dịch của chính mình
    $result = query(
        "DELETE FROM transaction WHERE user_id = ? AND id IN ($placeholders)",
        $params
    );

    if ($result !== false) {
        setMessage("Xóa thành công", "success");
    } else {
        setMessage("Lỗi hệ thống, vui lòng thử lại sau", "error");
    }

    redirect("?template=user&action=filter");
}
