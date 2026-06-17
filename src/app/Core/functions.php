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
    $icons = ['success' => 'check_circle', 'error' => 'error', 'info' => 'info', 'warning' => 'warning'];
    $icon = $icons[$type] ?? 'info';
    $id = 'toast-' . uniqid();
    return <<<HTML
<div class="toast toast-{$type}" id="{$id}">
    <span class="toast-icon">{$icon}</span>
    <span class="toast-msg">{$message}</span>
    <button class="toast-close" onclick="this.closest('.toast').classList.add('removing');setTimeout(function(){this.closest('.toast').remove()}.bind(this),300)">&times;</button>
    <div class="toast-bar"></div>
</div>
HTML;
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

function sendMail($to, $subject, $content, $attachmentPath = null) {
    $logContent = "[" . date('Y-m-d H:i:s') . "] TO: $to | SUBJECT: $subject \nCONTENT:\n$content\n---------------------------------------------\n";
    if ($attachmentPath) $logContent .= "ATTACHMENT: $attachmentPath\n";
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
        $mail->SMTPAutoTLS = true;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

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

        if ($attachmentPath && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        $result = $mail->send();
        file_put_contents(_WEB_PATH . 'mail_debug.log', "[OK] Email sent successfully to $to\n", FILE_APPEND);
        return $result;
    } catch (Exception $e) {
        $errorInfo = $mail->ErrorInfo ?? $e->getMessage();
        file_put_contents(_WEB_PATH . 'mail_debug.log', "[FAIL] $errorInfo\n", FILE_APPEND);
        error_log("Email send error: $errorInfo");
        return false;
    }
}

function getTotalSum($id, $type, $walletId = null) {
    if ($walletId > 0) {
        $result = getOne("SELECT SUM(price) as total FROM transaction WHERE type = :type AND user_id = :id AND wallet_id = :wid AND is_archived = 0", ["id" => $id, "type" => $type, "wid" => $walletId]);
    } else {
        $result = getOne("SELECT SUM(price) as total FROM transaction WHERE type = :type AND user_id = :id AND is_archived = 0", ["id" => $id, "type" => $type]);
    }
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
            'admin_bg' => '#0f1a2e',
            'admin_border' => '#3b82f6',
            'admin_text' => '#60a5fa',
            'toast_gradient' => 'linear-gradient(135deg, #1e3a5f, #3b82f6)',
            'toast_shadow' => 'rgba(59, 130, 246, 0.25)',
        ],
        'warning' => [
            'label' => 'Cảnh báo',
            'toast_icon' => '!',
            'admin_bg' => '#2a1f0a',
            'admin_border' => '#f59e0b',
            'admin_text' => '#fbbf24',
            'toast_gradient' => 'linear-gradient(135deg, #92400e, #f59e0b)',
            'toast_shadow' => 'rgba(245, 158, 11, 0.30)',
        ],
        'success' => [
            'label' => 'Thành công',
            'toast_icon' => 'OK',
            'admin_bg' => '#0a1f14',
            'admin_border' => '#10b981',
            'admin_text' => '#34d399',
            'toast_gradient' => 'linear-gradient(135deg, #065f46, #10b981)',
            'toast_shadow' => 'rgba(16, 185, 129, 0.28)',
        ],
        'error' => [
            'label' => 'Khẩn cấp',
            'toast_icon' => '!!',
            'admin_bg' => '#2a0a0a',
            'admin_border' => '#ef4444',
            'admin_text' => '#f87171',
            'toast_gradient' => 'linear-gradient(135deg, #7f1d1d, #ef4444)',
            'toast_shadow' => 'rgba(239, 68, 68, 0.30)',
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

/**
 * Get cached categories list. Reads from cache_categories.json if available,
 * otherwise queries MySQL and writes to cache.
 *
 * @param string $orderBy Column to sort by (e.g. 'name', 'id')
 * @return array
 */
function getCachedCategories($orderBy = 'name') {
    $cacheFile = _WEB_PATH . 'cache_categories.json';

    if (file_exists($cacheFile)) {
        $json = file_get_contents($cacheFile);
        $categories = json_decode($json, true);
        if (is_array($categories)) {
            return sortCategories($categories, $orderBy);
        }
    }

    $categories = getAll("SELECT * FROM category") ?: [];

    file_put_contents($cacheFile, json_encode($categories, JSON_UNESCAPED_UNICODE));

    return sortCategories($categories, $orderBy);
}

/**
 * Sort categories array by given column.
 */
function sortCategories($categories, $orderBy) {
    $orderBy = strtolower($orderBy);
    usort($categories, function ($a, $b) use ($orderBy) {
        if ($orderBy === 'id') return $a['id'] - $b['id'];
        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
    });
    return $categories;
}

/**
 * Delete category cache so it is rebuilt on next read.
 */
function clearCategoryCache() {
    $cacheFile = _WEB_PATH . 'cache_categories.json';
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
}

/**
 * Unified JSON response format for all AJAX endpoints.
 *
 * @param bool   $success
 * @param string $message
 * @param mixed  $data
 */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
