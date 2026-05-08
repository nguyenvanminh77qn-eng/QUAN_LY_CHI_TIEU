<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=user&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $filterAll = filter();
    $name = $filterAll['name'] ?? '';
    $icon = !empty($filterAll['icon']) ? trim($filterAll['icon']) : '🧾';

    if (!empty($name)) {
            $insert = insert('category', ['name' => $name, 'icon' => $icon]);
            if ($insert) {
                setMessage("Thêm danh mục thành công", "success");
            } else {
                setMessage("Thêm danh mục thất bại", "error");
            }
    }else{
        setMessage("Vui lòng nhập tên danh mục", "error");
    }
    
    redirect("?template=admin&action=categories");
}



    
