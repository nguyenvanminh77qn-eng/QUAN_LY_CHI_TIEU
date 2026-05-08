<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

$filterAll = filter();
$token = $filterAll['token'];

if (!empty($token)) {

    $sql = "DELETE FROM logintoken WHERE loginToken = :loginToken";
    query($sql, ["loginToken" => $token]);
}


deleteSession('loginToken');
deleteSession('username');
deleteSession('id'); 


setMessage("Đăng xuất thành công", "success");
redirect("?template=auth&action=login.view");