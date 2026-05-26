<?php
if (!CODE) die('Ban khong co quyen truy cap vao trang nay');

// Thời gian hiển thị toast phía user (giây). Nguồn duy nhất cho Toast_Duration — không lưu trong database.
define('TOAST_DISPLAY_SECONDS', 8);

function layout($view, $data = []) {
    $path = WEB_PATH_TEMPLATE . "layout/" . $view . ".php";

    if (!file_exists($path)) {
        die("Khong ton tai: " . $path);
    }

    extract($data);
    require_once $path;
}

function isGet() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function filter() {
    $filter = [];
    if (isGet() && !empty($_GET)) {
        foreach ($_GET as $key => $value) {
            $key = strip_tags($key);
            if (is_array($value)) {
                $filter[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
            } else {
                $filter[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
    }

    if (isPost() && !empty($_POST)) {
        foreach ($_POST as $key => $value) {
            $key = strip_tags($key);
            if (is_array($value)) {
                $filter[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
            } else {
                $filter[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }
    }

    return $filter;
}

function isEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isNumberInt($number) {
    return filter_var($number, FILTER_VALIDATE_INT);
}

function isNumberFloat($number) {
    return filter_var($number, FILTER_VALIDATE_FLOAT);
}

function isPhone($phone) {
    $checkZero = false;
    if ($phone[0] === '0') {
        $checkZero = true;
        $phone = substr($phone, 1);
    }

    $isNumber = false;
    if (isNumberInt($phone) && strlen($phone) == 9) {
        $isNumber = true;
    }

    return $checkZero && $isNumber;
}

function setMessage($message, $type = "success") {
    setFlashData("message", $message);
    setFlashData("message_type", $type);
}

function showMessage($message, $type = "success") {
    return "<div class='alert alert-$type'>$message</div>";
}

function form_error($errors, $field) {
    if (!empty($errors[$field])) {
        return "<span class='form-error'>" . reset($errors[$field]) . "</span>";
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function old($oldData, $field) {
    if (!empty($oldData[$field])) {
        return $oldData[$field];
    }
    return "";
}

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function sendMail($to, $subject, $content) {
    $logContent = "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject \nCONTENT:\n$content\n---------------------------------------------\n";
    file_put_contents(_WEB_PATH . 'mail_debug.log', $logContent, FILE_APPEND);

    if (empty($_ENV['SMTP_HOST']) || empty($_ENV['SMTP_USER']) || empty($_ENV['SMTP_PASS'])) {
        error_log("SMTP is not configured. Falling back to mail_debug.log for local testing.");
        return true;
    }

    $mail = new PHPMailer(true);
    try {
        $smtpHost = $_ENV['SMTP_HOST'];
        $smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $smtpUser = $_ENV['SMTP_USER'];
        $smtpPass = $_ENV['SMTP_PASS'];
        $appName = $_ENV['APP_NAME'] ?? 'Quan Ly Chi Tieu';

        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;

        if ($smtpPort == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($smtpUser, $appName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $content;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email send error: {$mail->ErrorInfo}");
        return false;
    }
}

function getTotalSum($id, $type) {
    $sql = "SELECT SUM(price) as total FROM transaction WHERE type = :type AND user_id = :id AND is_archived = 0";
    $result = getOne($sql, ["id" => $id, "type" => $type]);
    return ($result && $result['total']) ? $result['total'] : 0;
}

function getPagination($totalRows, $limit, $currentPage) {
    $totalPages = ceil($totalRows / $limit);
    if ($totalPages < 1) $totalPages = 1;
    if ($currentPage < 1) $currentPage = 1;
    if ($currentPage > $totalPages) $currentPage = $totalPages;

    $offset = ($currentPage - 1) * $limit;
    if ($offset < 0) $offset = 0;

    return [
        'offset' => $offset,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage
    ];
}

function renderPagination($totalPages, $currentPage, $urlPrefix) {
    if ($totalPages <= 1) return "";

    $html = '<div class="pagination-container">';
    $html .= '<ul class="pagination">';

    if ($currentPage > 1) {
        $html .= '<li><a href="' . $urlPrefix . '&page=' . ($currentPage - 1) . '">Prev</a></li>';
    }

    for ($i = 1; $i <= $totalPages; $i++) {
        $active = ($i == $currentPage) ? 'active' : '';
        $html .= '<li class="' . $active . '"><a href="' . $urlPrefix . '&page=' . $i . '">' . $i . '</a></li>';
    }

    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $urlPrefix . '&page=' . ($currentPage + 1) . '">Next</a></li>';
    }

    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}

function getNotificationTypeMap() {
    return [
        'info' => [
            'label' => 'Thông tin',
            'toast_icon' => 'i',
            'admin_bg' => '#e8f4fd',
            'admin_border' => '#3498db',
            'admin_text' => '#1f618d',
            'toast_gradient' => 'linear-gradient(135deg, #3498db, #1f78d1)',
            'toast_shadow' => 'rgba(52, 152, 219, 0.28)',
        ],
        'warning' => [
            'label' => 'Cảnh báo',
            'toast_icon' => '!',
            'admin_bg' => '#fff7e6',
            'admin_border' => '#f39c12',
            'admin_text' => '#b56d07',
            'toast_gradient' => 'linear-gradient(135deg, #f1c40f, #f39c12)',
            'toast_shadow' => 'rgba(243, 156, 18, 0.28)',
        ],
        'success' => [
            'label' => 'Thành công',
            'toast_icon' => 'OK',
            'admin_bg' => '#eafaf1',
            'admin_border' => '#2ecc71',
            'admin_text' => '#1e8449',
            'toast_gradient' => 'linear-gradient(135deg, #2ecc71, #27ae60)',
            'toast_shadow' => 'rgba(46, 204, 113, 0.28)',
        ],
        'error' => [
            'label' => 'Khẩn cấp',
            'toast_icon' => '!!',
            'admin_bg' => '#fdeeee',
            'admin_border' => '#e74c3c',
            'admin_text' => '#b03a2e',
            'toast_gradient' => 'linear-gradient(135deg, #e74c3c, #c0392b)',
            'toast_shadow' => 'rgba(231, 76, 60, 0.28)',
        ],
    ];
}

function normalizeNotificationType($type) {
    $typeMap = getNotificationTypeMap();
    return array_key_exists($type, $typeMap) ? $type : 'info';
}

function getNotificationExpiryTimestamp($notification) {
    if (!empty($notification['expires_at'])) {
        $timestamp = strtotime($notification['expires_at']);
        if ($timestamp !== false) {
            return $timestamp;
        }
    }

    return false;
}

function getActiveNotification(): ?array {
    $row = getOne(
        "SELECT n.id, n.message, n.type, n.expires_at, n.created_at, n.created_by, u.username as created_by_name
         FROM notifications n
         LEFT JOIN user u ON u.id = n.created_by
         WHERE n.is_active = 1
           AND n.expires_at > NOW()
         ORDER BY n.id DESC
         LIMIT 1"
    );
    return $row ?: null;
}

function getNotificationHistory(): array {
    return getAll(
        "SELECT n.id, n.message, n.type, n.expires_at, n.is_active, n.created_at, n.created_by, u.username as created_by_name
         FROM notifications n
         LEFT JOIN user u ON u.id = n.created_by
         WHERE NOT (n.is_active = 1 AND n.expires_at > NOW())
          ORDER BY n.id DESC"
    );
}

function cleanupNotifications(): void {
    // Bước 1: Đặt is_active = 0 cho các notification đã hết hạn
    query(
        "UPDATE notifications
         SET is_active = 0
         WHERE is_active = 1
           AND expires_at < NOW()"
    );

    // Bước 2: Xóa các notification có expires_at cũ hơn 7 ngày
    query(
    "DELETE FROM notifications 
     WHERE DATE_ADD(expires_at, INTERVAL 7 DAY) <= NOW()"
    );

    // Bước 3: Enforce constraint — đảm bảo chỉ có tối đa 1 active notification
    // Nếu có từ 2 active notification trở lên, giữ lại cái có id lớn nhất, tắt các cái còn lại
    query(
        "UPDATE notifications
         SET is_active = 0
         WHERE is_active = 1
           AND expires_at > NOW()
           AND id NOT IN (
               SELECT id FROM (
                   SELECT id FROM notifications
                   WHERE is_active = 1 AND expires_at > NOW()
                   ORDER BY id DESC LIMIT 1
               ) AS latest
           )"
    );
}
