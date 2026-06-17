<?php
if (!defined('CODE')) die('Bạn không có quyền truy cập vào trang này');

/**
 * Tìm conversation root ID giữa 2 người dùng.
 * Mỗi cặp (userA, userB) chỉ có DUY NHẤT 1 conversation.
 * Trả về root ID nếu tồn tại, null nếu chưa có.
 */
function findConversation(int $userA, int $userB): ?int
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT id FROM feedbacks
         WHERE parent_id IS NULL
           AND (
               (sender_id = :a1 AND receiver_id = :b1)
            OR (sender_id = :a2 AND receiver_id = :b2)
           )
         ORDER BY id ASC
         LIMIT 1"
    );
    $stmt->execute(['a1' => $userA, 'b1' => $userB, 'a2' => $userB, 'b2' => $userA]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
}

/**
 * Lấy danh sách conversations (mỗi cặp user↔admin = 1 row).
 * Với admin: thấy tất cả conversations.
 * Với user: chỉ thấy conversations của mình.
 */
function getFeedbackThreads(int $userId, string $role = 'user'): array
{
    global $conn;

    // User role: kiểm tra xem có admin nào online không (shared inbox)
    // Admin role: kiểm tra user cụ thể
    $otherOnlineSql = ($role !== 'admin')
        ? "EXISTS (SELECT 1 FROM logintoken lt JOIN `user` u ON u.id = lt.user_id WHERE u.role = 'admin')"
        : "EXISTS (SELECT 1 FROM logintoken lt WHERE lt.user_id = IF(f.sender_id = :uid_me2, f.receiver_id, f.sender_id))";

    // Subquery lấy tin nhắn mới nhất trong mỗi conversation
    $sql = "SELECT
        f.id,
        f.sender_id,
        f.receiver_id,
        f.sender_type,
        f.title,
        f.message_content,
        f.created_at,

        -- Tin nhắn mới nhất (root hoặc reply)
        COALESCE(
            (SELECT lm.message_content FROM feedbacks lm
             WHERE lm.parent_id = f.id ORDER BY lm.id DESC LIMIT 1),
            f.message_content
        ) AS last_message,

        COALESCE(
            (SELECT lm.sender_id FROM feedbacks lm
             WHERE lm.parent_id = f.id ORDER BY lm.id DESC LIMIT 1),
            f.sender_id
        ) AS last_sender_id,

        COALESCE(
            (SELECT lm.sender_type FROM feedbacks lm
             WHERE lm.parent_id = f.id ORDER BY lm.id DESC LIMIT 1),
            f.sender_type
        ) AS last_sender_type,

        COALESCE(
            (SELECT lm.created_at FROM feedbacks lm
             WHERE lm.parent_id = f.id ORDER BY lm.id DESC LIMIT 1),
            f.created_at
        ) AS last_activity,

        -- Đếm tin chưa đọc: admin dùng shared inbox, user dùng receiver_id
        (SELECT COUNT(*) FROM feedbacks u
         WHERE (u.parent_id = f.id OR u.id = f.id)
           AND u.is_read = 0
           " . ($role === 'admin'
                ? "AND u.receiver_id IN (SELECT id FROM `user` WHERE role = 'admin')"
                : "AND u.receiver_id = :uid_unread") . "
        ) AS unread_count,

        su.username AS sender_username,
        ru.username AS receiver_username,

        -- Người kia trong cuộc trò chuyện (so với user hiện tại)
        IF(f.sender_id = :uid_me, f.receiver_id, f.sender_id) AS other_id,

        -- Trạng thái online dựa trên logintoken
        $otherOnlineSql AS other_online
    FROM feedbacks f
    LEFT JOIN `user` su ON su.id = f.sender_id
    LEFT JOIN `user` ru ON ru.id = f.receiver_id
    WHERE f.parent_id IS NULL AND f.is_archived = 0";

    $params = ['uid_me' => $userId];
    if ($role === 'admin') {
        $params['uid_me2'] = $userId;
    }
    if ($role !== 'admin') {
        $params['uid_unread'] = $userId;
    }

    if ($role !== 'admin') {
        $sql .= " AND (f.sender_id = :uid1 OR f.receiver_id = :uid2)";
        $params['uid1'] = $userId;
        $params['uid2'] = $userId;
    }

    $sql .= " ORDER BY last_activity DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy toàn bộ tin nhắn trong 1 conversation (root + replies), sắp xếp ASC.
 */
function getFeedbackThread(int $rootId): array
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT f.*, u.username AS sender_username,
                EXISTS (SELECT 1 FROM logintoken lt WHERE lt.user_id = f.sender_id) AS sender_online
         FROM feedbacks f
         LEFT JOIN `user` u ON u.id = f.sender_id
         WHERE f.id = :id OR f.parent_id = :id2
         ORDER BY f.created_at ASC, f.id ASC"
    );
    $stmt->execute(['id' => $rootId, 'id2' => $rootId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Gửi tin nhắn.
 * - Nếu đã có conversation giữa 2 người → reply vào đó (parent_id = rootId)
 * - Nếu chưa → tạo conversation mới (parent_id = NULL)
 */
function sendFeedback(
    int $senderId,
    int $receiverId,
    string $senderType,
    string $messageContent,
    ?int $parentId = null,
    ?string $title = null
): ?int {
    // Nếu đã chỉ định parent_id thì dùng luôn (reply trong thread)
    if ($parentId !== null) {
        return insertGetId('feedbacks', [
            'parent_id'       => $parentId,
            'sender_id'       => $senderId,
            'receiver_id'     => $receiverId,
            'sender_type'     => $senderType,
            'title'           => null,
            'message_content' => $messageContent,
            'is_read'         => 0,
        ]);
    }

    // Kiểm tra đã có conversation chưa
    $existingRoot = findConversation($senderId, $receiverId);

    if ($existingRoot !== null) {
        query(
            "UPDATE feedbacks SET is_archived = 0 WHERE id = :id OR parent_id = :pid",
            ['id' => $existingRoot, 'pid' => $existingRoot]
        );
        return insertGetId('feedbacks', [
            'parent_id'       => $existingRoot,
            'sender_id'       => $senderId,
            'receiver_id'     => $receiverId,
            'sender_type'     => $senderType,
            'title'           => null,
            'message_content' => $messageContent,
            'is_read'         => 0,
        ]);
    }

    // Chưa có → tạo conversation mới
    if (!$title) {
        $title = mb_substr($messageContent, 0, 50) . (mb_strlen($messageContent) > 50 ? '...' : '');
    }
    return insertGetId('feedbacks', [
        'parent_id'       => null,
        'sender_id'       => $senderId,
        'receiver_id'     => $receiverId,
        'sender_type'     => $senderType,
        'title'           => $title,
        'message_content' => $messageContent,
        'is_read'         => 0,
    ]);
}

function getUnreadCount(int $userId, string $role = 'user'): int
{
    global $conn;
    if ($role === 'admin') {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM feedbacks
             WHERE is_read = 0 AND is_archived = 0 AND receiver_id IN (SELECT id FROM `user` WHERE role = 'admin')"
        );
        $stmt->execute();
    } else {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cnt FROM feedbacks
             WHERE receiver_id = :uid AND is_read = 0 AND is_archived = 0"
        );
        $stmt->execute(['uid' => $userId]);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['cnt'] : 0;
}

function markFeedbackRead(int $msgId): void
{
    query("UPDATE feedbacks SET is_read = 1 WHERE id = :id", ['id' => $msgId]);
}

function markThreadRead(int $rootId, int $userId, string $role = 'user'): void
{
    global $conn;
    if ($role === 'admin') {
        // Shared admin inbox: mark unread messages addressed to ANY admin
        $stmt = $conn->prepare(
            "UPDATE feedbacks SET is_read = 1
             WHERE is_read = 0
               AND receiver_id IN (SELECT id FROM `user` WHERE role = 'admin')
               AND (id = :rid OR parent_id = :rid2)"
        );
        $stmt->execute(['rid' => $rootId, 'rid2' => $rootId]);
    } else {
        $stmt = $conn->prepare(
            "UPDATE feedbacks SET is_read = 1
             WHERE receiver_id = :uid
               AND is_read = 0
               AND (id = :rid OR parent_id = :rid2)"
        );
        $stmt->execute(['uid' => $userId, 'rid' => $rootId, 'rid2' => $rootId]);
    }
}

function isUserOnline(int $userId): bool
{
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM logintoken WHERE user_id = :uid");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && (int)$row['cnt'] > 0;
}

function getAdminUserId(): int
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT id FROM `user` WHERE role = 'admin' ORDER BY id ASC LIMIT 1"
    );
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : 0;
}

function getAllAdmins(): array
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT id, username, email FROM `user`
         WHERE role = 'admin' ORDER BY username ASC"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllUsers(): array
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT id, username, email FROM `user`
         WHERE role = 'user' ORDER BY username ASC"
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFeedbackMessage(int $msgId): ?array
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM feedbacks WHERE id = :id");
    $stmt->execute(['id' => $msgId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Long-poll: tin nhắn mới trong 1 conversation kể từ sinceId.
 */
function getNewMessagesInThread(int $rootId, int $sinceId): array
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT f.*, u.username AS sender_username,
                EXISTS (SELECT 1 FROM logintoken lt WHERE lt.user_id = f.sender_id) AS sender_online
         FROM feedbacks f
         LEFT JOIN `user` u ON u.id = f.sender_id
         WHERE (f.id = :rid OR f.parent_id = :rid2)
           AND f.id > :since
         ORDER BY f.created_at ASC, f.id ASC"
    );
    $stmt->execute(['rid' => $rootId, 'rid2' => $rootId, 'since' => $sinceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Long-poll: tin nhắn mới gửi đến userId kể từ sinceId (dùng cho global badge).
 */
function getNewMessagesForUser(int $userId, int $sinceId): array
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT f.id, f.parent_id, f.sender_id, f.sender_type,
                f.title, f.message_content, f.created_at,
                u.username AS sender_username
         FROM feedbacks f
         LEFT JOIN `user` u ON u.id = f.sender_id
         WHERE f.receiver_id = :uid AND f.id > :since AND f.is_archived = 0
         ORDER BY f.created_at ASC
         LIMIT 20"
    );
    $stmt->execute(['uid' => $userId, 'since' => $sinceId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy message ID lớn nhất liên quan đến userId (dùng làm điểm bắt đầu poll).
 */
function getLatestMessageId(int $userId): int
{
    global $conn;
    $stmt = $conn->prepare(
        "SELECT MAX(id) AS max_id FROM feedbacks
         WHERE (receiver_id = :uid OR sender_id = :uid2) AND is_archived = 0"
    );
    $stmt->execute(['uid' => $userId, 'uid2' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)($row['max_id'] ?? 0) : 0;
}

function getUserHash(int $userId): string {
    return substr(hash('sha256', 'user_' . $userId . '_' . USER_HASH_SALT), 0, 16);
}

const FEEDBACK_ARCHIVE_DAYS = 90;
const FEEDBACK_PURGE_DAYS = 365;

function archiveOldFeedbacks(): int
{
    $cutoff = date('Y-m-d H:i:s', strtotime('-' . FEEDBACK_ARCHIVE_DAYS . ' days'));

    $countResult = getOne(
        "SELECT COUNT(*) as cnt FROM feedbacks
         WHERE is_archived = 0
           AND parent_id IS NULL
           AND (
               SELECT COALESCE(MAX(created_at), feedbacks.created_at)
               FROM feedbacks sub
               WHERE sub.id = feedbacks.id OR sub.parent_id = feedbacks.id
           ) < :cutoff",
        ['cutoff' => $cutoff]
    );
    $archivedCount = $countResult ? (int) $countResult['cnt'] : 0;

    if ($archivedCount > 0) {
        $roots = getAll(
            "SELECT id FROM feedbacks
             WHERE is_archived = 0
               AND parent_id IS NULL
               AND (
                   SELECT COALESCE(MAX(created_at), feedbacks.created_at)
                   FROM feedbacks sub
                   WHERE sub.id = feedbacks.id OR sub.parent_id = feedbacks.id
               ) < :cutoff",
            ['cutoff' => $cutoff]
        );
        $ids = array_column($roots, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        query(
            "UPDATE feedbacks SET is_archived = 1 WHERE id IN ($placeholders)",
            $ids
        );
        query(
            "UPDATE feedbacks SET is_archived = 1 WHERE parent_id IN ($placeholders)",
            $ids
        );
    }

    return $archivedCount;
}

function purgeOldArchivedFeedbacks(): int
{
    $cutoff = date('Y-m-d H:i:s', strtotime('-' . FEEDBACK_PURGE_DAYS . ' days'));

    $roots = getAll(
        "SELECT f.id FROM feedbacks f
         WHERE f.parent_id IS NULL
           AND f.is_archived = 1
           AND (
               SELECT COALESCE(MAX(created_at), f.created_at)
               FROM feedbacks sub
               WHERE sub.id = f.id OR sub.parent_id = f.id
           ) < :cutoff",
        ['cutoff' => $cutoff]
    );
    $purgeCount = count($roots);

    if ($purgeCount > 0) {
        $ids = array_column($roots, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        query(
            "DELETE FROM feedbacks WHERE parent_id IN ($placeholders)",
            $ids
        );

        query(
            "DELETE FROM feedbacks WHERE id IN ($placeholders)",
            $ids
        );
    }

    return $purgeCount;
}
