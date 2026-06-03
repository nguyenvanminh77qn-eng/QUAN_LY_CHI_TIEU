<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Kích hoạt tài khoản",
        "css" => ["pages/auth/auth-entrance"]
    ]);
    $filterAll = filter();
    $redirectToLogin = false;
    $delaySeconds = 2;
    $message = "";
    $message_type = "";
    if(empty($filterAll['active'])){
        $message = "Bạn chưa có link kích hoạt tài khoản";
        $message_type = "error";
    }else{
        $query = getOne("SELECT id, active_expires FROM user WHERE activeToken = :activeToken",["activeToken" => $filterAll['active']]);
        if($query){
            if (!empty($query['active_expires']) && strtotime($query['active_expires']) <= time()) {
                $message = "Link kích hoạt đã hết hạn. Vui lòng đăng ký lại.";
                $message_type = "error";
            } else {
                $updateData = [
                    "activeToken" => null,
                    "active_expires" => null,
                    "status" => 1
                ];
                $updateStatus = update("user", $updateData, "id=:id", ['id' => $query['id']]);
                if($updateStatus){
                    $message = "Kích hoạt tài khoản thành công. Bạn có thể đăng nhập.";
                    $message_type = "success";
                    setMessage($message, $message_type);
                    $redirectToLogin = true;
                }else{
                    $message = "Lỗi hệ thống, thử lại sau";
                    $message_type = "error";
                }
            }
        }else{
            $message = "Link đã hết hạn hoặc không tồn tại";
            $message_type = "error";
        }
    }

?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f0fdfa,#f8fafc);padding:24px;box-sizing:border-box;position:relative;overflow:hidden;">
    <div style="position:fixed;inset:0;pointer-events:none;z-index:0;overflow:hidden;" aria-hidden="true">
        <svg viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:100%;max-width:400px;max-height:400px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
            <circle cx="340" cy="60" r="80" fill="#0d9488" opacity="0.04"/>
            <circle cx="60" cy="340" r="60" fill="#38bdf8" opacity="0.04"/>
            <g transform="translate(320, 100)" style="animation: activeDecoBob 4s ease-in-out infinite">
                <path d="M0 0 L5 -14 L10 0 L24 5 L10 10 L5 24 L0 10 L-14 5 Z" fill="#0d9488" opacity="0.15"/>
            </g>
            <g transform="translate(50, 80)" style="animation: activeDecoBob 4.5s ease-in-out infinite 0.5s">
                <path d="M0 0 L4 -10 L8 0 L18 4 L8 8 L4 18 L0 8 L-10 4 Z" fill="#f59e0b" opacity="0.12"/>
            </g>
            <g transform="translate(370, 340)" style="animation: activeDecoBob 5s ease-in-out infinite 1s">
                <path d="M0 0 L3 -8 L6 0 L14 3 L6 6 L3 14 L0 6 L-8 3 Z" fill="#38bdf8" opacity="0.12"/>
            </g>
        </svg>
    </div>
    <div style="max-width:440px;width:100%;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);padding:48px 40px;border-radius:24px;border:1px solid rgba(226,232,240,0.6);box-shadow:0 20px 60px rgba(0,0,0,0.06);text-align:center;position:relative;z-index:1;" class="auth-entrance-scale auth-e-1">
        <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;margin-bottom:20px;">
            <span class="material-symbols-outlined" style="font-size:32px">
                <?= $message_type === 'success' ? 'check_circle' : 'error_outline' ?>
            </span>
        </div>
        <?php echo showMessage($message, $message_type); ?>
        <?php if($redirectToLogin): ?>
            <p style="margin-top:16px;font-size:13px;color:#94a3b8;">Đang chuyển hướng đến trang đăng nhập...</p>
        <?php else: ?>
            <p style="margin-top:16px;">
                <a href="?template=auth&action=login.view" style="color:#0d9488;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">arrow_back</span>
                    Quay lại đăng nhập
                </a>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php if($redirectToLogin): ?>
<script>
    setTimeout(function () {
        window.location.href = "?template=auth&action=login.view";
    }, <?= $delaySeconds * 1000 ?>);
</script>
<?php endif; ?>
<?php
layout("footer", ["js" => []]);
?>
