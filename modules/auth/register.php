<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    if(isset($_POST['btn-register'])){
        $filterAll = filter();
        $errors = [];
        $username = $filterAll['username'];
        $email = $filterAll['email'];
        $phone = $filterAll['phone'];
        $password = $filterAll['password'];
        $confirm_password = $filterAll['confirm_password'];


        if(empty($username)){
            $errors['username']['required'] = "Tên người dùng không được để trống";
        }else if(strlen($username) < 5){
            $errors['username']['min'] = "Tên người dùng phải có ít nhất 5 ký tự";
        }else{
            $user = getOne("SELECT id FROM user WHERE username = :username", ['username' => $username]);
            if($user){
                $errors['username']['exists'] = "Tên người dùng đã được sử dụng";
            }
        }

        if(empty($email)){
            $errors['email']['required'] = "Email không được để trống";
        }else{
            $user = getOne(
                "SELECT id FROM user WHERE email = :email",
                ['email' => $email]
            );
            if($user){
                $errors['email']['exists'] = "Email đã được sử dụng";
            }
        }

        if(empty($phone)){
            $errors['phone']['required'] = "Số điện thoại không được để trống";
        }else{
            if(!isPhone($phone)){
                $errors['phone']['invalid'] = "Số điện thoại không hợp lệ";
            }else{
                $userPhone = getOne("SELECT id FROM user WHERE phone = :phone", ['phone' => $phone]);
                if($userPhone){
                    $errors['phone']['exists'] = "Số điện thoại đã được sử dụng";
                }
            }
        }

        if(empty($password)){
            $errors['password']['required'] = "Mật khẩu không được để trống";
        }else if(strlen($password) < 6){
            $errors['password']['min'] = "Mật khẩu phải có ít nhất 6 ký tự";
        }

        if(empty($confirm_password)){
            $errors['confirm_password']['required'] = "Xác nhận mật khẩu không được trống";
        }else if($confirm_password !== $password){
            $errors['confirm_password']['match'] = "Xác nhận mật khẩu không khớp";
        }

        if(empty($errors)){
            $activeToken = bin2hex(random_bytes(16));
            $now = date("Y-m-d H:i:s");
            $dataInsert = [
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'activeToken'=>$activeToken,
                'create_at'=>$now,
                'update_at'=>$now
            ];
            if(insert('user', $dataInsert)){
                $content = "Vui lòng click vào link này để kích hoạt tài khoản <br>";
                $content.=_WEB_HOST."?template=auth&action=active.view&active=".$activeToken;
                $result =  sendMail($email,"Kích hoạt tài khoản",$content);
                if($result){
                    setMessage("Đăng ký thành công, vui lòng vào email để kích hoat tài khoản");
                }else{
                    setMessage("Đăng ký thành công, nhưng lỗi không gửi email được.Vui lòng kiểm tra email","error");
                }
            }else{
               setMessage("Đăng ký thất bại, vui lòng thử lại", "error");
            }
            redirect("?template=auth&action=register.view");
        }else{
            setFlashData("errors", $errors);
            setFlashData("old", $filterAll);
            setMessage("Vui lòng kiểm tra lại thông tin đã nhập", "error");
            redirect("?template=auth&action=register.view");
        }
    }
?>