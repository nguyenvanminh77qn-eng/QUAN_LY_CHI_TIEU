<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Kích hoạt tài khoản"]);
    $filterAll = filter();
    $redirectToLogin = false;
    $delaySeconds = 2;
    $message = "";
    $message_type = "";
    if(empty($filterAll['active'])){
        $message = "Link đã hết hạn hoặc không tồn tại";
        $message_type = "error";
    }else{
        $query = getOne("SELECT id FROM user WHERE activeToken = :activeToken",["activeToken" => $filterAll['active']]);
        if($query){
            $updateData = [
                "activeToken" => null,
                "status" => 1
            ];
            $updateStatus = update("user", $updateData, "id=:id", ['id' => $query['id']]);
            if($updateStatus){
                $message = "Kích hoạt tài khoản thành công.Bạn có thể đăng nhập";
                $message_type = "success";
                setMessage($message, $message_type);
                $redirectToLogin = true;
            }else{
                $message = "Lỗi hệ thống, thử lại sau";
                $message_type = "error";
            }
        }else{
            $message = "Link đã hết hạn hoặc không tồn tại";
            $message_type = "error";
        }
    }

?>
<?php echo showMessage($message,$message_type); ?>
<?php if($redirectToLogin): ?>
<script>
    setTimeout(function () {
        var target = "?template=auth&action=login.view";
        window.location.href = target;
    }, <?= $delaySeconds * 1000 ?>);
</script>
<?php endif; ?>