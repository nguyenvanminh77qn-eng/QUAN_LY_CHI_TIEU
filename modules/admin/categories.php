<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=user&action=dashboard");
}



// Sửa danh mục
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_category'])) {
    $filterAll = filter();
    $id   = (int)($filterAll['edit_id'] ?? 0);
    $name = trim($filterAll['edit_name'] ?? '');
    $icon = !empty($filterAll['edit_icon']) ? trim($filterAll['edit_icon']) : '🧾';

    if ($id <= 0) {
        setMessage("Danh mục không hợp lệ", "error");
        redirect("?template=admin&action=categories");
    }

    if ($name === '') {
        setMessage("Tên danh mục không được để trống", "error");
        redirect("?template=admin&action=categories");
    }

    // Kiểm tra trùng tên với danh mục khác
    $exists = getOne("SELECT id FROM category WHERE name = :name AND id != :id", ['name' => $name, 'id' => $id]);
    if ($exists) {
        setMessage("Tên danh mục đã tồn tại", "error");
        redirect("?template=admin&action=categories");
    }

    $updated = update('category', ['name' => $name, 'icon' => $icon], "id = :id", ['id' => $id]);
    if ($updated !== false) {
        setMessage("Cập nhật danh mục thành công", "success");
    } else {
        setMessage("Cập nhật thất bại", "error");
    }

    redirect("?template=admin&action=categories");
}
