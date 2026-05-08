<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    if(isset($_POST['btn-login'])){
        $filterAll = filter();
        $errors = [];
        $email = $filterAll['email'];
        $password = $filterAll['password'];
        $user = getOne("SELECT * FROM user WHERE email = :email", ['email' => $email]);
        
        if(empty($email)){
            $errors['email']['required'] = "Email không được để trống";
        }
        if(empty($password)){
            $errors['password']['required'] = "Mật khẩu không được để trống";
        }else{
            if(strlen($password) < 6){
                $errors['password']['min'] = "Mật khẩu phải có ít nhất 6 ký tự";
            }
        }

        if(empty($errors)){
            if($user){
                if(password_verify($password, $user['password'])){

                    if($user['status'] == 0){
                        setMessage("Tài khoản chưa được kích hoạt, vui lòng kiểm tra email","error");
                        redirect("?template=auth&action=login.view");
                    }
                    if($user['status'] == 2){
                        setMessage("Tài khoản của bạn đã bị khóa bởi quản trị viên","error");
                        redirect("?template=auth&action=login.view");
                    }
                    $isLogin = countRows("SELECT * FROM logintoken WHERE user_id = :id",["id"=>$user['id']]);
                    if($isLogin > 0){
                        setMessage("Có người đang đăng nhập tài khoản này","error");
                        redirect("?template=auth&action=login.view");
                    }
                    $loginToken = bin2hex(random_bytes(16));
                    $insertData = [
                        'user_id' => $user['id'],
                        'loginToken' => $loginToken,
                        'create_at' => date('Y-m-d H:i:s')
                    ];
                    $insertQuery = insert('logintoken', $insertData);
                    if($insertQuery){
                        setSession("loginToken",$loginToken);
                        setSession('username',$user['username']);
                        setSession('id',$user['id']);
                        setSession('role',$user['role']);
                        
                        if($user['role'] == 'admin'){
                            redirect("?template=admin&action=dashboard");
                        }else{
                            redirect("?template=user&action=dashboard");
                        }
                    }else{
                        setMessage("Lỗi hệ thống, thử lại sau", "error");
                    }
                }else{
                    setMessage("Mật khẩu không đúng", "error");
                }
            }else{
                setMessage("Email không tồn tại", "error");
            }
            redirect("?template=auth&action=login.view");
        }else{
            setFlashData("errors", $errors);
            setFlashData("old", $filterAll);
            setMessage("Vui lòng kiểm tra lại thông tin đã nhập", "error");
            redirect("?template=auth&action=login.view");
        }
    }
?>