<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$role = getSession('role');
if ($role !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=auth&action=login.view");
}

// Any POST form handling for inbox page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // handled by AJAX usually
    redirect("?template=user&action=inbox");
}
