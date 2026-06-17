<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

$tempUserId = getSession('temp_otp_user_id');
if (empty($tempUserId)) {
    setMessage("Vui lòng đăng nhập trước.", "error");
    redirect("?template=auth&action=login.view");
}

$OTP_MAX_ATTEMPTS = 5;
$OTP_RESEND_LIMIT = 5;
$OTP_COOLDOWN = 60;
$OTP_EXPIRY = 60;

function checkBlock($key, &$blockMinutes) {
    $blockedUntil = getSession($key . '_blocked_until');
    if ($blockedUntil && $blockedUntil > time()) {
        $remaining = $blockedUntil - time();
        $blockMinutes = ceil($remaining / 60);
        return $remaining;
    }
    if ($blockedUntil && $blockedUntil <= time()) {
        deleteSession($key . '_blocked_until');
        deleteSession($key . '_count');
    }
    return 0;
}

function applyBlock($key) {
    $level = (int)getSession($key . '_level') + 1;
    $minutes = $level;
    setSession($key . '_level', $level);
    setSession($key . '_count', 0);
    setSession($key . '_blocked_until', time() + $minutes * 60);
    return $minutes;
}

function jsonOut($data) {
    $success = $data['success'] ?? false;
    $message = $data['message'] ?? '';
    unset($data['success'], $data['message']);
    jsonResponse($success, $message, $data);
}

// === AJAX: Gửi lại OTP ===
if (isset($_POST['btn-resend-otp'])) {
    $blockRemaining = checkBlock('otp_resend', $blockMinutes);
    if ($blockRemaining > 0) {
        $msg = "Bạn đã gửi lại OTP quá nhiều lần. Vui lòng đợi {$blockMinutes} phút.";
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    $resendCount = (int)getSession('otp_resend_count') + 1;
    setSession('otp_resend_count', $resendCount);
    if ($resendCount > $OTP_RESEND_LIMIT) {
        $minutes = applyBlock('otp_resend');
        $msg = "Bạn đã gửi lại OTP quá {$OTP_RESEND_LIMIT} lần. Tạm khóa gửi lại trong {$minutes} phút.";
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    $user = getOne("SELECT * FROM user WHERE id = :id", ['id' => $tempUserId]);
    if ($user) {
        $otp = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = date('Y-m-d H:i:s', strtotime("+{$OTP_EXPIRY} seconds"));
        query(
            "UPDATE user SET otp_code = :otp_code, otp_expires = :otp_expires WHERE id = :id",
            ['otp_code' => $otp, 'otp_expires' => $otpExpires, 'id' => $tempUserId]
        );
        $subject = "Mã xác thực đăng nhập (OTP) - Quản Lý Chi Tiêu";
        $emailContent = "
            <h2>Mã OTP mới</h2>
            <p>Chào <strong>{$user['username']}</strong>,</p>
            <p>Mã xác thực OTP mới của bạn là: <strong style='font-size: 20px; color: #e74c3c; letter-spacing: 2px;'>{$otp}</strong></p>
            <p>Mã này chỉ có hiệu lực trong <strong>{$OTP_EXPIRY} giây</strong>.</p>
            <p>Trân trọng,<br>Ban quản trị hệ thống.</p>
        ";
        if (sendMail($user['email'], $subject, $emailContent)) {
            deleteSession('otp_verify_blocked_until');
            deleteSession('otp_verify_count');
            setSession('otp_sent_at', time());
            $msg = "Mã OTP mới đã được gửi tới email {$user['email']}.";
            if (!empty($_POST['ajax'])) jsonOut(['success' => true, 'message' => $msg, 'cooldown' => $OTP_COOLDOWN]);
            setMessage($msg, "success");
        } else {
            $msg = "Không thể gửi lại mã OTP. Vui lòng thử lại.";
            if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
            setMessage($msg, "error");
        }
    }
    if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => 'Lỗi hệ thống.']);
    redirect("?template=auth&action=verify_otp.view");
}

// === AJAX: Xác thực OTP ===
if (isset($_POST['btn-verify-otp'])) {
    $blockRemaining = checkBlock('otp_verify', $blockMinutes);
    if ($blockRemaining > 0) {
        $msg = "Bạn đã nhập sai OTP quá nhiều lần. Vui lòng đợi {$blockMinutes} phút.";
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    $otpInput = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    if ($otpInput === '') {
        $msg = "Vui lòng nhập mã OTP.";
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    $user = getOne("SELECT * FROM user WHERE id = :id", ['id' => $tempUserId]);

    if (!$user) {
        $msg = "Tài khoản không tồn tại.";
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    if (strtotime($user['otp_expires']) <= time()) {
        $msg = "Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.";
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    if ($user['otp_code'] !== $otpInput) {
        $count = (int)getSession('otp_verify_count') + 1;
        setSession('otp_verify_count', $count);
        $remaining = $OTP_MAX_ATTEMPTS - $count;
        if ($count >= $OTP_MAX_ATTEMPTS) {
            $minutes = applyBlock('otp_verify');
            $msg = "Bạn đã nhập sai OTP quá {$OTP_MAX_ATTEMPTS} lần. Tạm khóa xác thực trong {$minutes} phút.";
        } else {
            $msg = "Mã OTP không đúng. Bạn còn {$remaining} lần thử.";
        }
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    // OTP hợp lệ
    foreach (['otp_verify_count', 'otp_verify_level', 'otp_verify_blocked_until', 'otp_resend_count', 'otp_resend_level', 'otp_resend_blocked_until', 'otp_sent_at', 'temp_otp_user_id'] as $k) {
        deleteSession($k);
    }

    query("UPDATE user SET otp_code = NULL, otp_expires = NULL WHERE id = :id", ['id' => $tempUserId]);

    $loginToken = bin2hex(random_bytes(16));
    $insertData = [
        'user_id' => $user['id'],
        'loginToken' => $loginToken,
        'create_at' => date('Y-m-d H:i:s')
    ];

    $insertQuery = insert('logintoken', $insertData);
    if (!$insertQuery) {
        $msg = "Lỗi hệ thống, vui lòng thử lại sau.";
        if (!empty($_POST['ajax'])) jsonOut(['success' => false, 'message' => $msg]);
        setMessage($msg, "error");
        redirect("?template=auth&action=verify_otp.view");
    }

    session_regenerate_id(true);
    setSession("loginToken", $loginToken);
    setSession('username', $user['username']);
    setSession('id', $user['id']);
    setSession('role', $user['role']);

    $redirect = $user['role'] == 'admin' ? '?template=admin&action=dashboard' : '?template=user&action=dashboard';
    if (!empty($_POST['ajax'])) jsonOut(['success' => true, 'message' => 'Đăng nhập thành công!', 'redirect' => $redirect]);
    setMessage("Đăng nhập thành công!", "success");
    redirect($redirect);
}
