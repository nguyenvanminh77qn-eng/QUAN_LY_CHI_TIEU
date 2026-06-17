<?php
if (!CODE) die('Ban khong co quyen truy cap vao trang nay');

if (empty(getSession('loginToken'))) {
    redirect("?template=auth&action=login.view");
}

if (getSession('role') !== 'admin') {
    redirect("?template=user&action=dashboard");
}

redirect("?template=admin&action=dashboard");
?>
