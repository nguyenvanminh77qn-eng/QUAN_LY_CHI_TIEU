<?php
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../Core/functions.php';
require_once __DIR__ . '/../../Core/session.php';
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict']);
session_start();
require_once __DIR__ . '/../../Core/connect.php';
require_once __DIR__ . '/../../Core/database.php';
require_once __DIR__ . '/../../Helpers/feedback_helper.php';

/* Giải phóng session lock sớm — cho phép các tab/request khác chạy song song */
session_write_close();

$userId = (int) getSession('id');
$role   = getSession('role');

if (!$userId) {
    jsonResponse(false, 'Unauthorized');
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'get_threads':
        $threads = getFeedbackThreads($userId, $role);
        foreach ($threads as &$t) {
            $t['sender_hash'] = getUserHash((int)$t['sender_id']);
            $t['last_sender_hash'] = getUserHash((int)$t['last_sender_id']);
        }
        jsonResponse(true, '', ['threads' => $threads ?: []]);

    case 'get_thread':
        $threadId = (int) ($_GET['thread_id'] ?? 0);
        if (!$threadId) {
            jsonResponse(false, 'Missing thread_id');
        }
        $messages = getFeedbackThread($threadId);
        foreach ($messages as &$m) {
            $m['sender_hash'] = getUserHash((int)$m['sender_id']);
        }
        jsonResponse(true, '', ['messages' => $messages ?: []]);

    case 'get_admins':
        $admins = getAllAdmins();
        jsonResponse(true, '', ['admins' => $admins ?: []]);

    case 'get_users':
        if ($role !== 'admin') {
            jsonResponse(false, 'Forbidden');
        }
        $users = getAllUsers();
        jsonResponse(true, '', ['users' => $users ?: []]);

    case 'get_latest_id':
        $latestId = getLatestMessageId($userId);
        $unread   = getUnreadCount($userId, $role);
        jsonResponse(true, '', ['last_id' => $latestId, 'unread_count' => $unread]);

    case 'send':
        $content    = trim($_POST['content'] ?? '');
        $parentId   = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $receiverId = (int) ($_POST['receiver_id'] ?? 0);

        if (!$content) {
            jsonResponse(false, 'Nội dung không được để trống');
        }

        $senderType = $role === 'admin' ? 'admin' : 'user';

        // --- Reply trong thread đang mở ---
        if ($parentId) {
            $parent = getFeedbackMessage($parentId);
            if (!$parent) {
                jsonResponse(false, 'Cuộc trò chuyện không tồn tại');
            }
            // Xác định receiver là người kia
            $receiverId = ((int)$parent['sender_id'] === $userId)
                ? (int)$parent['receiver_id']
                : (int)$parent['sender_id'];

            $newId = sendFeedback($userId, $receiverId, $senderType, $content, $parentId);
            if ($newId) {
                $msg = getFeedbackMessage($newId);
                $msg['sender_hash'] = getUserHash((int)$msg['sender_id']);
                // root_id luôn là parentId khi reply
                $msg['root_id'] = $parentId;
                jsonResponse(true, '', ['message' => $msg]);
            } else {
                jsonResponse(false, 'Không thể gửi tin nhắn');
            }
            break;
        }

        // --- Gửi tin mới (từ compose hoặc click vào admin/user) ---
        if (!$receiverId) {
            if ($senderType === 'admin') {
                jsonResponse(false, 'Vui lòng chọn người nhận');
            }
            $receiverId = getAdminUserId();
            if (!$receiverId) {
                jsonResponse(false, 'Không tìm thấy admin');
            }
        }

        // sendFeedback tự kiểm tra conversation đã tồn tại chưa
        $newId = sendFeedback($userId, $receiverId, $senderType, $content);

        if ($newId) {
            $msg = getFeedbackMessage($newId);
            $msg['sender_hash'] = getUserHash((int)$msg['sender_id']);
            // Tính root_id: nếu msg có parent_id thì root = parent_id, không thì root = chính nó
            $msg['root_id'] = $msg['parent_id'] ? (int)$msg['parent_id'] : (int)$msg['id'];
            jsonResponse(true, '', ['message' => $msg]);
        } else {
            jsonResponse(false, 'Không thể gửi tin nhắn');
        }
        break;

    case 'get_unread_count':
        $count = getUnreadCount($userId, $role);
        jsonResponse(true, '', ['total_unread' => (int)$count]);

    case 'mark_read':
        $msgId = (int) ($_GET['message_id'] ?? 0);
        if ($msgId) {
            markFeedbackRead($msgId);
            jsonResponse(true, '');
        } else {
            jsonResponse(false, 'Missing message_id');
        }
        break;

    case 'mark_thread_read':
        $threadId = (int) ($_GET['thread_id'] ?? 0);
        if ($threadId) {
            markThreadRead($threadId, $userId, $role);
            jsonResponse(true, '');
        } else {
            jsonResponse(false, 'Missing thread_id');
        }
        break;

    default:
        jsonResponse(false, 'Invalid action');
}
