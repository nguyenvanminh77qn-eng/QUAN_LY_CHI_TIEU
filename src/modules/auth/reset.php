<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    if(isset($_POST['btn-reset'])){
        $filterAll = filter();
        $errors = [];
        $password = $filterAll['password'] ?? '';
        $confirmPassword = $filterAll['confirm_password'] ?? '';
        $reset = $filterAll['reset'];

        if(empty($password)){
            $errors['password']['required'] = "Mật khẩu không được để trống";
        } elseif(strlen($password) < 6){
            $errors['password']['min'] = "Mật khẩu phải ít nhất 6 ký tự";
        }

        if(empty($confirmPassword)){
            $errors['confirm_password']['required'] = "Phải nhập lại xác thực mật khẩu";
        } elseif($confirmPassword !== $password){
            $errors['confirm_password']['match'] = "Xác thực mật khẩu không đúng";
        }
        if(empty($errors)){
            $updateData = [
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'forgotToken' => null,
                'forgot_expires' => null,
                'update_at' => date("Y-m-d H:i:s")
            ];  
            $updatedQuery = update("user", $updateData, "forgotToken = :forgotToken", ['forgotToken' => $reset]);
            if($updatedQuery){
                setMessage("Mật khẩu của bạn đã được reset. Bạn có thể đăng nhập bây giờ.", "success");
                redirect("?template=auth&action=login.view");
            }else{
                setMessage("Lỗi hệ thống, thử lại sau.", "error");
                redirect("?template=auth&action=reset.view&reset=".$reset);
            }
        }else{
            setFlashData("errors", $errors);
            setFlashData("old", $filterAll);
            setMessage("Vui lòng kiểm tra lại thông tin đã nhập", "error");
            redirect("?template=auth&action=reset.view&reset=".$reset);
        }
    }
?>