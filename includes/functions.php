
<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    function layout($view, $data = []) {
        extract($data);
        require_once WEB_PATH_TEMPLATE."layout/".$view.".php";
    }