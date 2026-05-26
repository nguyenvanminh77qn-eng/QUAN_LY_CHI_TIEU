<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'admin') {
    redirect("?template=user&action=dashboard");
}

if(isset($_POST['btn-change-password'])){
    $filterAll = filter();
    $errors = [];
    
    $old_password = $filterAll['old_password'] ?? '';
    $new_password = $filterAll['new_password'] ?? '';
    $confirm_password = $filterAll['confirm_password'] ?? '';
    
    if(empty($old_password)){
        $errors['old_password']['required'] = "Vui lòng nhập mật khẩu hiện tại";
    }
    
    if(empty($new_password)){
        $errors['new_password']['required'] = "Vui lòng nhập mật khẩu mới";
    } else {
        if(strlen($new_password) < 6){
            $errors['new_password']['min'] = "Mật khẩu mới phải có ít nhất 6 ký tự";
        }
        if(!empty($old_password) && $new_password === $old_password){
            $errors['new_password']['same'] = "Mật khẩu mới không thể giống mật khẩu cũ";
        }
    }
    
    if(empty($confirm_password)){
        $errors['confirm_password']['required'] = "Vui lòng xác nhận mật khẩu mới";
    } else {
        if($new_password !== $confirm_password){
            $errors['confirm_password']['match'] = "Xác nhận mật khẩu không khớp";
        }
    }
    
    if(empty($errors)){
        $id = getSession('id');
        $user = getOne("SELECT id, password FROM user WHERE id = :id", ['id' => $id]);
        
        if($user){
            if(password_verify($old_password, $user['password'])){
                $hashPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStatus = update('user', ['password' => $hashPassword], 'id = :id', ['id' => $id]);
                
                if($updateStatus){
                    setMessage("Đổi mật khẩu thành công", "success");
                }else{
                    setMessage("Lỗi hệ thống, vui lòng thử lại sau", "error");
                }
            }else{
                setMessage("Mật khẩu hiện tại không chính xác", "error");
            }
        }else{
            setMessage("Lỗi hệ thống không tìm thấy người dùng", "error");
        }
    }else{
        setFlashData("errors", $errors);
        setMessage("Vui lòng kiểm tra lại lỗi nhập liệu", "error");
    }
    
    redirect("?template=admin&action=profile");
}
?>
