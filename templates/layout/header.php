<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Quản lý chi tiêu' ?></title>
    <link rel="stylesheet" href="<?= _CSS ?>main.css">
    <?php
        if(!empty($css)){
            foreach($css as $item){
             echo '<link rel="stylesheet" href="'. _CSS .$item.'.css">';
            }
}
?>


</head>
<body>

</body>
</html> 