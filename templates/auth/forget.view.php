<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Quên mật khẩu",
        "css" => ["pages/forge"]
    ]);
?>


<h1>Quên mật khẩu</h1>
<?php
    layout("footer", ["js" => ["pages/forget"]] );
?>