<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

function getFilterTransactions($where, $params, $limit = 5, $lastId = 0, $lastDate = '') {
    $cursorWhere = '';
    $cursorParams = [];
    if ($lastId > 0 && $lastDate !== '') {
        $cursorWhere = "WHERE (combined.transaction_date < :last_date OR (combined.transaction_date = :last_date AND combined.id < :last_id))";
        $cursorParams = ['last_id' => $lastId, 'last_date' => $lastDate];
    }
    $allParams = array_merge($params, $cursorParams);
    $fetchLimit = $limit + 1;

    $items = getAll(
        "SELECT * FROM (
            SELECT transaction.id, transaction.wallet_id, transaction_date, category.name as category_name, category.icon as category_icon, description, price, transaction.type, transaction.status, transaction.sync_status, transaction.pending_amount, transaction.pending_type, transaction.pending_wallets_json, wallet.name as wallet_name, wallet.type as wallet_type, COALESCE(transaction.source_type, 'manual') as source_type, NULL as batch_id
            FROM transaction
            JOIN category ON category.id = transaction.category_id
            LEFT JOIN wallet ON wallet.id = transaction.wallet_id
            $where AND transaction.batch_id IS NULL
            UNION ALL
            SELECT MAX(transaction.id) as id, NULL as wallet_id, MAX(transaction_date) as transaction_date, category.name, category.icon, GROUP_CONCAT(DISTINCT transaction.description ORDER BY transaction.id SEPARATOR ', ') as description, SUM(price) as price, MAX(transaction.type) as type, 'active' as status, 'active' as sync_status, NULL as pending_amount, NULL as pending_type, NULL as pending_wallets_json, 'Nhiều ví' as wallet_name, NULL as wallet_type, 'multi' as source_type, transaction.batch_id
            FROM transaction
            JOIN category ON category.id = transaction.category_id
            $where AND transaction.batch_id IS NOT NULL
            GROUP BY transaction.batch_id, category.id
        ) combined
        $cursorWhere
        ORDER BY combined.transaction_date DESC, combined.id DESC
        LIMIT $fetchLimit",
        $allParams
    );

    $hasMore = count($items) > $limit;
    if ($hasMore) $items = array_slice($items, 0, $limit);

    $nextId = 0;
    $nextDate = '';
    if (!empty($items)) {
        $lastItem = end($items);
        $nextId = $lastItem['id'];
        $nextDate = $lastItem['transaction_date'];
    }

    return ['items' => $items, 'hasMore' => $hasMore, 'nextId' => $nextId, 'nextDate' => $nextDate];
}

function getFilterBatchDetails($items, $userId) {
    $batchIds = [];
    foreach ($items as $item) {
        if (($item['source_type'] ?? '') === 'multi' && !empty($item['batch_id'])) {
            $batchIds[] = $item['batch_id'];
        }
    }
    $map = [];
    if (!empty($batchIds)) {
        $uniqueIds = array_unique($batchIds);
        $placeholders = [];
        $batchParams = ['batch_uid' => $userId];
        foreach ($uniqueIds as $i => $bid) {
            $key = 'bid_' . $i;
            $placeholders[] = ':' . $key;
            $batchParams[$key] = $bid;
        }
        $details = getAll(
            "SELECT t.batch_id, t.price, w.icon, w.name
             FROM transaction t
             LEFT JOIN wallet w ON w.id = t.wallet_id
             WHERE t.batch_id IN (" . implode(',', $placeholders) . ") AND t.user_id = :batch_uid
             ORDER BY t.id",
            $batchParams
        );
        foreach ($details as $d) {
            $map[$d['batch_id']][] = $d;
        }
    }
    return $map;
}

function getFilterReadyIdMap($items, $userId) {
    $map = [];
    foreach ($items as $item) {
        if (($item['status'] ?? 'active') === 'pending_delete' && ($item['source_type'] ?? '') !== 'multi') {
            $bal = ($item['wallet_id'] ?? 0) > 0 ? getWalletBalance($item['wallet_id'], $userId) : 0;
            $map[$item['id']] = ($bal - (float)$item['price'] >= 0);
        }
    }
    return $map;
}

function getFilterPendingInfo($userId) {
    $allPendingTxs = getAll(
        "SELECT t.id, t.price, t.description, t.wallet_id,
                w.name as wallet_name, w.icon as wallet_icon
         FROM transaction t
         LEFT JOIN wallet w ON w.id = t.wallet_id
         WHERE t.user_id = :uid AND t.status = 'pending_delete'",
        ['uid' => $userId]
    ) ?: [];
    $readyIdMap = [];
    $pendingItems = [];
    $readyItems = [];
    foreach ($allPendingTxs as $tx) {
        $bal = $tx['wallet_id'] > 0 ? getWalletBalance($tx['wallet_id'], $userId) : 0;
        $isReady = $bal - (float)$tx['price'] >= 0;
        $readyIdMap[$tx['id']] = $isReady;
        if ($isReady) $readyItems[] = $tx;
        else $pendingItems[] = $tx;
    }
    return [$pendingItems, $readyItems, $readyIdMap];
}

function renderFilterTransactionRow($item, $batchDetails, $readyIdMap) {
    $isMulti = ($item['source_type'] ?? '') === 'multi';
    $priceFormatted = number_format($item['price'], 0, ',', '.');
    $typeClass = $item['type'] === 'income' ? 'income' : 'expense';
    $sign = $item['type'] === 'income' ? '+' : '-';
    $isPendingDel = !$isMulti && ($item['status'] ?? 'active') === 'pending_delete';
    $syncStatus = $item['sync_status'] ?? 'active';
    $isPendingEdit = !$isMulti && $syncStatus === 'pending_edit';
    $isReady = !$isMulti && $syncStatus === 'ready';
    $rowClass = $isPendingDel ? ' tr-pending-delete' : ($isPendingEdit || $isReady ? ' tr-pending-edit' : '');
    if ($isMulti) $rowClass .= ' has-batch';
    $pWallets = !$isMulti ? parsePendingWallets($item) : [];

    $html = '<tr class="filter-tr' . $rowClass . '"' . ($isPendingDel ? ' data-pending-id="' . $item['id'] . '"' : '') . '>';
    $html .= '<td class="filter-td">' . htmlspecialchars($item['transaction_date']) . '</td>';
    $html .= '<td class="filter-td category"><span class="category-badge"><span class="category-badge__icon">' . htmlspecialchars($item['category_icon'] ?? '📦') . '</span><span class="category-badge__name">' . htmlspecialchars($item['category_name']) . '</span></span></td>';
    $html .= '<td class="filter-td desc">' . htmlspecialchars($item['description']);
    if ($isPendingDel) $html .= ' <span class="pending-badge pending-badge-del">Đang chờ xoá</span>';
    elseif ($isReady) $html .= ' <span class="pending-badge pending-badge-ready">✅ Sẵn sàng cập nhật</span>';
    elseif ($isPendingEdit) $html .= ' <span class="pending-badge pending-badge-edit">⏳ Đang chờ xử lý dòng tiền...</span>';
    $html .= '</td>';

    if ($isMulti) {
        $html .= '<td class="filter-td source-cell"><span class="source-badge source-wallet">Nhiều ví';
        if (!empty($batchDetails[$item['batch_id']])) {
            $html .= '<div class="multi-wallet-detail">';
            foreach ($batchDetails[$item['batch_id']] as $bd) {
                $html .= '<div><span class="bwi-icon">' . htmlspecialchars($bd['icon'] ?? '💰') . '</span> ' . htmlspecialchars($bd['name']) . ': <strong>' . number_format($bd['price'], 0, ',', '.') . 'đ</strong></div>';
            }
            $html .= '</div>';
        }
        $html .= '</span></td>';
    } else {
        $walletName = $item['wallet_name'] ?? '';
        $walletType = $item['wallet_type'] ?? 'daily';
        if ($walletName !== '') {
            $html .= '<td class="filter-td"><span class="source-badge source-wallet type-' . htmlspecialchars($walletType) . '">' . htmlspecialchars($walletName) . '</span></td>';
        } else {
            $html .= '<td class="filter-td"><span class="source-badge source-manual">Nhập tay</span></td>';
        }
    }

    if (($isPendingEdit || $isReady) && !empty($pWallets)) {
        $firstPw = $pWallets[0];
        $pSign = ($firstPw['type'] ?? $item['type']) === 'income' ? '+' : '-';
        $pFmt = number_format((float)($firstPw['amount'] ?? 0), 0, ',', '.');
        $html .= '<td class="filter-td ' . $typeClass . '"><span class="price-old">' . $sign . ' ' . $priceFormatted . ' đ</span><span class="price-pending">→ ' . $pSign . ' ' . $pFmt . ' đ</span></td>';
    } else {
        $html .= '<td class="filter-td ' . $typeClass . '">' . $sign . ' ' . $priceFormatted . ' đ</td>';
    }

    $html .= '<td class="filter-td text-center">';
    if ($isPendingDel) {
        $pendingIsReady = $readyIdMap[$item['id']] ?? false;
        $html .= '<span class="pending-row-actions">';
        if ($pendingIsReady) $html .= '<button type="button" class="btn-pending-row-confirm" onclick="submitPendingAction(' . $item['id'] . ', \'confirm\')">Xác nhận</button>';
        else $html .= '<button type="button" class="btn-pending-row-wait" disabled>Chờ xử lý</button>';
        $html .= '<button type="button" class="btn-pending-row-cancel" onclick="submitPendingAction(' . $item['id'] . ', \'cancel\')">Huỷ</button>';
        $html .= '</span>';
    } elseif ($isMulti) {
        $html .= '<a href="?template=user&action=edit&id=' . $item['id'] . '" class="btn-action-edit">Sửa</a>';
    } else {
        $html .= '<div class="action-group">';
        if ($isReady) $html .= '<button type="button" class="btn-pending-row-confirm" onclick="submitReadyAccept(' . $item['id'] . ')">Xác nhận</button>';
        else $html .= '<a href="?template=user&action=edit&id=' . $item['id'] . '" class="btn-action-edit">Sửa</a>';
        if ($isPendingEdit || $isReady) {
            $html .= '<button type="button" class="btn-action-detail" onclick="showPendingDetail(' . $item['id'] . ')" title="Xem chi tiết">🔍</button>';
            $html .= '<button type="button" class="btn-pending-row-cancel" onclick="submitPendingEditCancel(' . $item['id'] . ')">Huỷ</button>';
        }
        $html .= '</div>';
    }
    $html .= '</td>';
    $html .= '<td class="filter-td text-center">';
    if (!$isPendingDel) $html .= '<input type="checkbox" class="checkItem" name="ids[]" value="' . $item['id'] . '">';
    $html .= '</td>';
    $html .= '</tr>';
    return $html;
}
