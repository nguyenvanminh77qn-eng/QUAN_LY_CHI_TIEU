<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    if(isset($_POST['btn-login'])){
        $filterAll = filter();
        $errors = [];
        $email = $filterAll['email'];
        $password = $filterAll['password'];
        $role = isset($filterAll['role']) ? trim((string)$filterAll['role']) : '';
        
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
            $user = getOne(
                "SELECT * FROM user WHERE email = :email",
                ['email' => $email]
            );

            if($user){
                // 1. Kiểm tra tài khoản có đang bị khóa (lockout) không
                if (!empty($user['lockout_until'])) {
                    $lockoutTime = strtotime($user['lockout_until']);
                    $now = time();
                    if ($lockoutTime > $now) {
                        $diffMinutes = ceil(($lockoutTime - $now) / 60);
                        setMessage("Tài khoản của bạn đang bị khóa tạm thời. Vui lòng thử lại sau {$diffMinutes} phút.", "error");
                        redirect("?template=auth&action=login.view");
                    }
                }

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

                    // 2. Thành công -> Reset trạng thái lỗi & Tạo mã OTP 2FA
                    $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $otpExpires = date('Y-m-d H:i:s', strtotime('+60 seconds'));

                    $updateFields = [
                        'failed_attempts' => 0,
                        'lockout_until' => null,
                        'otp_code' => $otp,
                        'otp_expires' => $otpExpires,
                        'id' => $user['id']
                    ];
                    query("UPDATE user SET failed_attempts = :failed_attempts, lockout_until = :lockout_until, otp_code = :otp_code, otp_expires = :otp_expires WHERE id = :id", $updateFields);

                    // 3. Gửi Email OTP
                    $subject = "Mã xác thực đăng nhập (OTP) - Quản Lý Chi Tiêu";
                    $emailContent = "
                        <h2>Xác thực đăng nhập tài khoản</h2>
                        <p>Chào <strong>{$user['username']}</strong>,</p>
                        <p>Hệ thống ghi nhận yêu cầu đăng nhập vào tài khoản của bạn.</p>
                        <p>Mã xác thực OTP của bạn là: <strong style='font-size: 20px; color: #e74c3c; letter-spacing: 2px;'>{$otp}</strong></p>
                        <p>Mã này chỉ có hiệu lực trong <strong>60 giây</strong>. Tuyệt đối không chia sẻ mã này cho bất kỳ ai.</p>
                        <p>Trân trọng,<br>Ban quản trị hệ thống.</p>
                    ";
                    
                    if (sendMail($user['email'], $subject, $emailContent)) {
                        setSession('temp_otp_user_id', $user['id']);
                        setSession('otp_sent_at', time());
                        setSession('otp_resend_count', 0);
                        setMessage("Mã OTP đã được gửi tới email {$user['email']}. Vui lòng kiểm tra hộp thư của bạn.", "success");
                        redirect("?template=auth&action=verify_otp.view");
                    } else {
                        setMessage("Không thể gửi mã OTP qua email. Vui lòng liên hệ quản trị viên.", "error");
                    }
                }else{
                    // 4. Sai mật khẩu -> Tăng số lần thử sai
                    $attempts = (int)$user['failed_attempts'] + 1;
                    $lockoutUntil = null;
                    if ($attempts >= 5) {
                        $lockoutUntil = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $attempts = 0; // reset attempts for next lockout cycle
                        query("UPDATE user SET failed_attempts = :attempts, lockout_until = :lockout WHERE id = :id", ['attempts' => $attempts, 'lockout' => $lockoutUntil, 'id' => $user['id']]);
                        setMessage("Sai mật khẩu quá 5 lần. Tài khoản của bạn tạm thời bị khóa trong 15 phút.", "error");
                    } else {
                        query("UPDATE user SET failed_attempts = :attempts WHERE id = :id", ['attempts' => $attempts, 'id' => $user['id']]);
                        $remaining = 5 - $attempts;
                        setMessage("Mật khẩu không đúng. Bạn còn {$remaining} lần thử lại.", "error");
                    }
                }
            }else{
                setMessage("Không tìm thấy tài khoản phù hợp để đăng nhập.", "error");
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