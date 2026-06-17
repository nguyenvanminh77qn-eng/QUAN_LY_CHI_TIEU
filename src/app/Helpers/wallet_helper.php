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

/**
 * Parse pending_wallets_json from a transaction row, with backward compatibility
 * for old records that use pending_amount/pending_type.
 */
function parsePendingWallets($transaction) {
    if (!empty($transaction['pending_wallets_json'])) {
        $decoded = json_decode($transaction['pending_wallets_json'], true);
        if (is_array($decoded) && !empty($decoded)) return $decoded;
    }
    if (isset($transaction['pending_amount']) && $transaction['pending_amount'] !== null) {
        $wid = (int)($transaction['wallet_id'] ?? 0);
        $type = $transaction['pending_type'] ?? $transaction['type'] ?? 'expense';
        return [['wallet_id' => $wid, 'amount' => (float)$transaction['pending_amount'], 'type' => $type]];
    }
    return [];
}

/**
 * Check if a pending_edit can be applied (all affected wallets have sufficient balance).
 * Returns true if all wallets can support the new transaction structure.
 */
function canApplyPendingEdit($transaction, $pending, $userId) {
    $batchId = $transaction['batch_id'] ?? null;

    $oldWalletIds = [];
    if (!empty($batchId)) {
        $batchRows = getAll(
            "SELECT wallet_id FROM transaction WHERE batch_id = :bid AND user_id = :uid AND id != :eid",
            ['bid' => $batchId, 'uid' => $userId, 'eid' => $transaction['id']]
        ) ?: [];
        foreach ($batchRows as $row) {
            $oldWalletIds[] = (int)$row['wallet_id'];
        }
    }
    if ($transaction['wallet_id'] > 0) {
        $oldWalletIds[] = (int)$transaction['wallet_id'];
    }
    $oldWalletIds = array_unique($oldWalletIds);

    foreach ($pending as $entry) {
        $wid = (int)($entry['wallet_id'] ?? 0);
        $amount = (float)($entry['amount'] ?? 0);
        $type = $entry['type'] ?? 'expense';
        if ($wid <= 0 || $amount <= 0) continue;

        if (in_array($wid, $oldWalletIds)) {
            $bal = getWalletBalance($wid, $userId, (int)$transaction['id']);
        } else {
            $bal = getWalletBalance($wid, $userId);
        }
        $balAfter = $type === 'income' ? $bal + $amount : $bal - $amount;
        if ($balAfter < 0) return false;
    }
    return true;
}

/**
 * Apply a pending_edit: update the transaction row with new values and clear pending columns.
 * Returns true on success.
 */
function applyPendingEdit($transaction, $pending, $userId) {
    if (empty($pending)) return false;

    $first = $pending[0];
    $newPrice = (float)($first['amount'] ?? 0);
    $newType = $first['type'] ?? 'expense';
    $newWalletId = (int)($first['wallet_id'] ?? 0);

    $txId = (int)$transaction['id'];

    if (count($pending) === 1 && $newWalletId > 0) {
        $update = [
            'price' => $newPrice,
            'type' => $newType,
            'wallet_id' => $newWalletId,
            'sync_status' => 'active',
            'pending_amount' => null,
            'pending_type' => null,
            'pending_wallets_json' => null,
            'update_at' => date('Y-m-d H:i:s'),
        ];
        return update('transaction', $update, "id = :id AND user_id = :uid", ['id' => $txId, 'uid' => $userId]);
    }

    if (count($pending) > 1 && !empty($transaction['batch_id'])) {
        delete("transaction", "batch_id = :bid AND user_id = :uid", ['bid' => $transaction['batch_id'], 'uid' => $userId]);
        $batchId = uniqid('b', true);
        $success = true;
        foreach ($pending as $entry) {
            $wid = (int)($entry['wallet_id'] ?? 0);
            $amt = (float)($entry['amount'] ?? 0);
            $typ = $entry['type'] ?? 'expense';
            if ($wid <= 0 || $amt <= 0) continue;
            $r = insert('transaction', [
                'user_id' => $userId, 'wallet_id' => $wid,
                'category_id' => (int)$transaction['category_id'],
                'price' => $amt, 'type' => $typ,
                'description' => $transaction['description'] ?? '',
                'transaction_date' => $transaction['transaction_date'] ?? date('Y-m-d'),
                'create_at' => date('Y-m-d H:i:s'),
                'source_type' => 'manual', 'batch_id' => $batchId,
            ]);
            if (!$r) $success = false;
        }
        $update = [
            'price' => $newPrice,
            'type' => $newType,
            'wallet_id' => $newWalletId,
            'batch_id' => $batchId,
            'sync_status' => 'active',
            'pending_amount' => null,
            'pending_type' => null,
            'pending_wallets_json' => null,
            'update_at' => date('Y-m-d H:i:s'),
        ];
        update('transaction', $update, "id = :id AND user_id = :uid", ['id' => $txId, 'uid' => $userId]);
        return $success;
    }

    return false;
}
