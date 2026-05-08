<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Quản lý chi tiêu' ?></title>
    <link rel="stylesheet" href="<?= _CSS ?>main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <?php
    if(!empty($css)){
    foreach($css as $item){
        echo '<link rel="stylesheet" href="'. _CSS . $item . '.css?v=' . time() . '">' . "\n";
    }
}
?>

</head>
<body>
<?php
    $globalNotification = getOne("SELECT * FROM notifications WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    
    // Chỉ hiển thị cho User, và chỉ hiển thị ở trang Dashboard (để không làm phiền khi ở trang khác)
    $currentAction = $_GET['action'] ?? 'dashboard';
    
    $showNotification = false;
    if ($globalNotification && getSession('role') !== 'admin' && !empty(getSession('loginToken')) && $currentAction === 'dashboard') {
        $lastSeenNotificationId = getSession('last_seen_notification_id');
        if ($lastSeenNotificationId !== $globalNotification['id']) {
            $showNotification = true;
            setSession('last_seen_notification_id', $globalNotification['id']);
        }
    }
    
    if ($showNotification) {
        $bgColors = [
            'info' => 'linear-gradient(135deg, #3498db, #2980b9)',
            'warning' => 'linear-gradient(135deg, #f1c40f, #f39c12)',
            'success' => 'linear-gradient(135deg, #2ecc71, #27ae60)',
            'error' => 'linear-gradient(135deg, #e74c3c, #c0392b)'
        ];
        $bgColor = $bgColors[$globalNotification['type']] ?? $bgColors['info'];
?>
    <!-- Toast Notification -->
    <div id="global-toast" style="position: fixed; bottom: 30px; right: 30px; background: <?= $bgColor ?>; color: white; padding: 18px 24px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); z-index: 10000; display: flex; align-items: center; gap: 15px; animation: slideInRight 0.6s cubic-bezier(0.68, -0.55, 0.27, 1.55); max-width: 450px; min-width: 300px;">
        <div style="font-size: 24px; background: rgba(255,255,255,0.2); width: 48px; height: 48px; border-radius: 50%; display: flex; justify-content: center; align-items: center; flex-shrink: 0;">🔔</div>
        <div>
            <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Hệ thống</h4>
            <p style="margin: 0; font-size: 15px; opacity: 0.95; line-height: 1.5;"><?= htmlspecialchars($globalNotification['message']) ?></p>
        </div>
    </div>

    <style>
        @keyframes slideInRight {
            from { transform: translateX(150%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(150%); opacity: 0; }
        }
    </style>
    <script>
        setTimeout(function() {
            var toast = document.getElementById('global-toast');
            if (toast) {
                toast.style.animation = 'slideOutRight 0.5s ease-in forwards';
                setTimeout(function() { toast.remove(); }, 500);
            }
        }, 8000); // Ẩn sau 8 giây (đã tăng thêm thời gian)
    </script>
<?php 
    } 
?>
