<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

function getWallets($userId) {
    return getAll("SELECT * FROM wallet WHERE user_id = :uid ORDER BY FIELD(type,'daily','ewallet','target'), id", ['uid' => $userId]) ?: [];
}

function getWalletBalance($walletId, $userId, $excludeTransactionId = null) {
    $cond = ["wallet_id = :wid", "user_id = :uid", "is_archived = 0"];
    $params = ['wid' => $walletId, 'uid' => $userId];
    if ($excludeTransactionId !== null) {
        $cond[] = "id != :eid";
        $params['eid'] = $excludeTransactionId;
    }
    $where = implode(' AND ', $cond);
    $income = getOne("SELECT COALESCE(SUM(price),0) FROM transaction WHERE $where AND type = 'income'", $params);
    $expense = getOne("SELECT COALESCE(SUM(price),0) FROM transaction WHERE $where AND type = 'expense'", $params);
    return (float)($income['COALESCE(SUM(price),0)'] ?? 0) - (float)($expense['COALESCE(SUM(price),0)'] ?? 0);
}

function createDefaultWallets($userId) {
    $existing = getOne("SELECT id FROM wallet WHERE user_id = :uid LIMIT 1", ['uid' => $userId]);
    if ($existing) return;
    insert('wallet', ['user_id' => $userId, 'name' => 'Tiền mặt', 'icon' => '💵', 'type' => 'daily', 'is_default' => 1, 'created_at' => date('Y-m-d H:i:s')]);
    insert('wallet', ['user_id' => $userId, 'name' => 'Ngân hàng', 'icon' => '🏦', 'type' => 'daily', 'is_default' => 0, 'created_at' => date('Y-m-d H:i:s')]);
    insert('wallet', ['user_id' => $userId, 'name' => 'Momo', 'icon' => '📱', 'type' => 'ewallet', 'is_default' => 0, 'created_at' => date('Y-m-d H:i:s')]);
    insert('wallet', ['user_id' => $userId, 'name' => 'Quỹ tiết kiệm', 'icon' => '🐷', 'type' => 'target', 'is_default' => 0, 'created_at' => date('Y-m-d H:i:s')]);
}

function getWalletGroupLabel($type) {
    $labels = ['daily' => 'Ví Chi Tiêu Hằng Ngày', 'ewallet' => 'Ví Điện Tử', 'target' => 'Quỹ/Mục Tiêu'];
    return $labels[$type] ?? 'Khác';
}
