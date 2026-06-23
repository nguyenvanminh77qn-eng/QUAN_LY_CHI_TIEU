<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    if(isset($_POST['btn-forget'])){
        $filterAll = filter();
        $errors = [];
        $email = $filterAll['email'];
        if(empty($email)){
            $errors['email']['required'] = "Email không được để trống";
        }
        if(empty($errors)){
            $user = getOne(
                "SELECT id, email FROM user WHERE email = :email",
                ['email' => $email]
            );
            if($user){
                $forgotToken = bin2hex(random_bytes(16));
                $updateData = [
                    'forgotToken' => $forgotToken,
                    'forgot_expires' => date("Y-m-d H:i:s", strtotime('+1 hour')),
                    'update_at' => date("Y-m-d H:i:s")
                ];
                $updateUser = update("user", $updateData, "id = :id", ['id' => $user['id']]);
                if($updateUser){
                    $content = "Vui lòng click vào link này để reset mật khẩu <br>";
                    $content.=_WEB_ROOT."?template=auth&action=reset.view&reset=".$forgotToken;
                    $result = sendMail($user['email'], "Reset mật khẩu", $content);
                    if($result){
                        setMessage("Vui lòng kiểm tra email để reset mật khẩu", "success");
                    }else{
                        setMessage("Lỗi khi gửi email reset mật khẩu, vui lòng thử lại", "error");
                    }   
                }else{
                    setMessage("Lỗi khi reset mật khẩu, vui lòng thử lại", "error");
                }
            }else{
                setMessage("Email không tồn tại", "error");
            }
            redirect("?template=auth&action=forget.view");
        }else{
            setMessage("Vui lòng kiểm tra lại thông tin đã nhập", "error");
            setFlashData("errors", $errors);
            setFlashData("old", $filterAll);
            redirect("?template=auth&action=forget.view");
        }
    }
?>