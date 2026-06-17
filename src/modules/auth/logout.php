<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('?template=auth&action=login.view');
}

$token = getSession('loginToken');

if (!empty($token)) {
    $sql = "DELETE FROM logintoken WHERE loginToken = :loginToken";
    query($sql, ["loginToken" => $token]);
}

deleteSession('loginToken');
deleteSession('username');
deleteSession('id');

setMessage("Đăng xuất thành công", "success");
redirect("?template=auth&action=login.view");