<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    if(isset($_POST['btn-reset'])){
        $filterAll = filter();
        $errors = [];
        $password = $filterAll['password'] ?? '';
        $confirmPassword = $filterAll['confirm_password'] ?? '';

        $userId = getSession('reset_user_id');
        $resetToken = getSession('reset_token');
        if (empty($userId) || empty($resetToken)) {
            setMessage("Phiên đặt lại mật khẩu không hợp lệ. Vui lòng yêu cầu lại.", "error");
            redirect("?template=auth&action=forget.view");
        }

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
            $updatedQuery = update("user", $updateData, "id = :id AND forgotToken = :forgotToken", ['id' => $userId, 'forgotToken' => $resetToken]);
            if($updatedQuery){
                deleteSession('reset_user_id');
                deleteSession('reset_token');
                setMessage("Mật khẩu của bạn đã được reset. Bạn có thể đăng nhập bây giờ.", "success");
                redirect("?template=auth&action=login.view");
            }else{
                setMessage("Lỗi hệ thống, thử lại sau.", "error");
                redirect("?template=auth&action=forget.view");
            }
        }else{
            setFlashData("errors", $errors);
            setFlashData("old", $filterAll);
            setMessage("Vui lòng kiểm tra lại thông tin đã nhập", "error");
            redirect("?template=auth&action=reset.view&reset=".$resetToken);
        }
    }
?>