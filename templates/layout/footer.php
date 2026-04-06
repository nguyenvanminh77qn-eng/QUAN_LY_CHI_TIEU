<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    if(!empty($js)){
        foreach($js as $item){
         echo '<script src="'. _JS .$item.'.js"></script>';
        }
    }
?>


