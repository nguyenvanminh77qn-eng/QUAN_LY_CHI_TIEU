<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$role = getSession('role');
if ($role !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=auth&action=login.view");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect("?template=admin&action=inbox");
}
