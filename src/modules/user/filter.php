<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

// ── Huỷ tất cả pending_delete (từ dashboard) ──
if (!empty($_GET['cancel_all_pending'])) {
    $userId = getSession('id');
    update("transaction", ['status' => 'active'], "user_id = :uid AND status = 'pending_delete'",
           ['uid' => $userId]);
    setMessage('Đã huỷ tất cả giao dịch chờ xoá.');
    redirect("?template=user&action=filter");
}

if (isset($_POST['filter-btn'])) {
    $filterALl = filter();

    $oldInputs = [];
    $oldInputs['date_from'] = $filterALl['date_from'] ?? '';
    $oldInputs['date_to'] = $filterALl['date_to'] ?? '';
    $oldInputs['type'] = $filterALl['type'];
    $oldInputs['category_ids'] = $filterALl['category_ids'] ?? [];
    $oldInputs['description'] = $filterALl['description'];
    $oldInputs['price_min'] = $filterALl['price_min'] ?? '';
    $oldInputs['price_max'] = $filterALl['price_max'] ?? '';
    $oldInputs['wallet_id'] = $filterALl['wallet_id'] ?? '';

    $where = "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0 AND transaction.source_type != 'transfer'";
    $params = ['user_id' => getSession('id')];

    if (!empty($filterALl['date_from'])) {
        $where .= " AND transaction_date >= :date_from";
        $params['date_from'] = $filterALl['date_from'];
    }
    if (!empty($filterALl['date_to'])) {
        $where .= " AND transaction_date <= :date_to";
        $params['date_to'] = $filterALl['date_to'];
    }
    if (!empty($filterALl['type'])) {
        $where .= " AND transaction.type = :type";
        $params['type'] = $filterALl['type'];
    }
    if (!empty($filterALl['category_ids']) && is_array($filterALl['category_ids'])) {
        $ids = array_filter($filterALl['category_ids'], 'is_numeric');
        if (!empty($ids)) {
            $placeholders = [];
            foreach ($ids as $i => $cid) {
                $key = 'cat_' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = (int)$cid;
            }
            $where .= " AND category_id IN (" . implode(',', $placeholders) . ")";
        }
    }
    if (!empty($filterALl['description'])) {
        $where .= " AND LOWER(description) LIKE LOWER(:description)";
        $params['description'] = '%' . trim($filterALl['description']) . '%';
    }
    $priceMin = trim($filterALl['price_min'] ?? '');
    $priceMax = trim($filterALl['price_max'] ?? '');
    if ($priceMin !== '' && is_numeric($priceMin) && (float)$priceMin >= 0) {
        $where .= " AND price >= :price_min";
        $params['price_min'] = (float)$priceMin;
    }
    if ($priceMax !== '' && is_numeric($priceMax) && (float)$priceMax >= 0) {
        $where .= " AND price <= :price_max";
        $params['price_max'] = (float)$priceMax;
    }
    $walletFilter = $filterALl['wallet_id'] ?? '';
    if ($walletFilter !== '' && $walletFilter !== '0') {
        $where .= " AND transaction.wallet_id = :wallet_id";
        $params['wallet_id'] = (int)$walletFilter;
    }

    setSession("filter_where", $where);
    setSession("filter_params", $params);
    setSession("filter_oldInputs", $oldInputs);

    redirect("?template=user&action=filter");
}

if(isset($_POST['filter-reset-btn'])){
    deleteSession("filter_where");
    deleteSession("filter_params");
    deleteSession("filter_oldInputs");
    redirect("?template=user&action=filter");
}

// ── AJAX: Kiểm tra pending_delete ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_pending') {
    $userId = getSession('id');
    
    $pendingTxs = getAll("SELECT t.id, t.price, t.description, t.wallet_id, 
                         w.name as wallet_name, w.icon as wallet_icon,
                         c.name as category_name, c.icon as category_icon
                         FROM transaction t
                         LEFT JOIN wallet w ON w.id = t.wallet_id
                         LEFT JOIN category c ON c.id = t.category_id
                         WHERE t.user_id = :uid AND t.status = 'pending_delete'",
                         ['uid' => $userId]) ?: [];
    
    $pendingList = [];
    $readyList = [];
    
    foreach ($pendingTxs as $tx) {
        $bal = $tx['wallet_id'] > 0 ? getWalletBalance($tx['wallet_id'], $userId) : 0;
        $balAfter = $bal - (float)$tx['price'];
        $ready = $balAfter >= 0;
        
        $item = [
            'id' => $tx['id'],
            'price' => (float)$tx['price'],
            'price_fmt' => number_format($tx['price'], 0, ',', '.'),
            'description' => $tx['description'],
            'wallet_name' => ($tx['wallet_icon'] ?? '💰') . ' ' . ($tx['wallet_name'] ?? ''),
            'wallet_balance' => $bal,
            'wallet_balance_fmt' => number_format($bal, 0, ',', '.'),
            'category_name' => $tx['category_name'] ?? '',
            'category_icon' => $tx['category_icon'] ?? '📦',
            'ready' => $ready,
        ];
        
        if ($ready) {
            $readyList[] = $item;
        } else {
            $item['deficit'] = abs($balAfter);
            $item['deficit_fmt'] = number_format(abs($balAfter), 0, ',', '.');
            $pendingList[] = $item;
        }
    }
    
    jsonResponse(true, '', [
        'pending' => $pendingList,
        'ready' => $readyList,
        'pending_count' => count($pendingList),
        'ready_count' => count($readyList),
    ]);
}

// ── Xác nhận xoá pending_delete ──
if (!empty($_POST['confirm_pending_delete'])) {
    $pendingId = (int)($_POST['pending_id'] ?? 0);
    $userId = getSession('id');
    
    if ($pendingId > 0) {
        $tx = getOne("SELECT id, wallet_id, price FROM transaction WHERE id = :id AND user_id = :uid AND status = 'pending_delete'",
                     ['id' => $pendingId, 'uid' => $userId]);
        if ($tx) {
            $bal = $tx['wallet_id'] > 0 ? getWalletBalance($tx['wallet_id'], $userId) : 0;
            if ($bal - (float)$tx['price'] >= 0) {
                delete("transaction", "id = :id AND user_id = :uid", ['id' => $pendingId, 'uid' => $userId]);
                setMessage('Đã xoá giao dịch thành công.');
            } else {
                setMessage('Số dư ví hiện tại không đủ để xoá giao dịch này.', 'error');
            }
        } else {
            setMessage('Giao dịch không tồn tại hoặc đã được xử lý.', 'error');
        }
    }
    redirect("?template=user&action=filter");
}

// ── Huỷ pending_delete (chuyển về active) ──
if (!empty($_POST['cancel_pending_delete'])) {
    $pendingId = (int)($_POST['pending_id'] ?? 0);
    $userId = getSession('id');
    
    if ($pendingId > 0) {
        update("transaction", ['status' => 'active'], "id = :id AND user_id = :uid",
               ['id' => $pendingId, 'uid' => $userId]);
        setMessage('Đã huỷ yêu cầu xoá giao dịch.');
    }
    redirect("?template=user&action=filter");
}

// ── Huỷ pending_edit (chuyển sync_status về active, xoá các cột tạm) ──
if (!empty($_POST['cancel_pending_edit'])) {
    $editId = (int)($_POST['edit_id'] ?? 0);
    $userId = getSession('id');
    
    if ($editId > 0) {
        update("transaction",
               ['sync_status' => 'active', 'pending_amount' => null, 'pending_type' => null, 'pending_wallets_json' => null],
               "id = :id AND user_id = :uid",
               ['id' => $editId, 'uid' => $userId]);
        setMessage('Đã huỷ thay đổi giao dịch.');
    }
    redirect("?template=user&action=filter");
}

// ── Chấp nhận pending_edit đã sẵn sàng (sync_status = 'ready') ──
if (!empty($_POST['accept_ready_edit'])) {
    $readyId = (int)($_POST['ready_id'] ?? 0);
    $userId = getSession('id');
    
    if ($readyId > 0) {
        $tx = getOne("SELECT * FROM transaction WHERE id = :id AND user_id = :uid",
                     ['id' => $readyId, 'uid' => $userId]);
        if ($tx) {
            $pWallets = parsePendingWallets($tx);
            if (!empty($pWallets)) {
                $applied = applyPendingEdit($tx, $pWallets, $userId);
                if ($applied) {
                    setMessage('Đã cập nhật giao dịch thành công.');
                } else {
                    setMessage('Không thể áp dụng thay đổi cho giao dịch này.', 'error');
                }
            } else {
                setMessage('Không tìm thấy dữ liệu thay đổi cho giao dịch này.', 'error');
            }
        } else {
            setMessage('Giao dịch không tồn tại.', 'error');
        }
    }
    redirect("?template=user&action=filter");
}

// ── Xem chi tiết pending_edit ──
if (isset($_GET['ajax']) && $_GET['ajax'] === 'pending_edit_detail') {
    $detailId = (int)($_GET['id'] ?? 0);
    $userId = getSession('id');
    
    $tx = getOne("SELECT * FROM transaction WHERE id = :id AND user_id = :uid",
                 ['id' => $detailId, 'uid' => $userId]);
    if (!$tx) {
        jsonResponse(false, 'Không tìm thấy giao dịch.', []);
    }
    
    $pWallets = parsePendingWallets($tx);
    $walletDetails = [];
    foreach ($pWallets as $pw) {
        $wid = (int)($pw['wallet_id'] ?? 0);
        $wInfo = $wid > 0 ? getOne("SELECT name, icon, type FROM wallet WHERE id = :id", ['id' => $wid]) : null;
        $walletDetails[] = [
            'wallet_id' => $wid,
            'icon' => ($wInfo['icon'] ?? '💰'),
            'name' => ($wInfo['name'] ?? 'Không xác định'),
            'type' => $wInfo['type'] ?? 'daily',
            'amount' => (float)($pw['amount'] ?? 0),
            'amount_fmt' => number_format((float)($pw['amount'] ?? 0), 0, ',', '.'),
            'tx_type' => $pw['type'] ?? 'expense',
        ];
    }
    
    $oldWallets = [];
    if (!empty($tx['batch_id'])) {
        $batchRows = getAll("SELECT * FROM transaction WHERE batch_id = :bid AND user_id = :uid AND id != :eid",
                           ['bid' => $tx['batch_id'], 'uid' => $userId, 'eid' => $tx['id']]) ?: [];
        foreach ($batchRows as $br) {
            $wi = $br['wallet_id'] > 0 ? getOne("SELECT name, icon FROM wallet WHERE id = :id", ['id' => $br['wallet_id']]) : null;
            $oldWallets[] = [
                'icon' => ($wi['icon'] ?? '💰'), 'name' => ($wi['name'] ?? ''),
                'amount' => (float)$br['price'], 'amount_fmt' => number_format((float)$br['price'], 0, ',', '.'),
                'type' => $br['type'],
            ];
        }
    }
    if ($tx['wallet_id'] > 0) {
        $wi = getOne("SELECT name, icon FROM wallet WHERE id = :id", ['id' => $tx['wallet_id']]);
        $oldWallets[] = [
            'icon' => ($wi['icon'] ?? '💰'), 'name' => ($wi['name'] ?? ''),
            'amount' => (float)$tx['price'], 'amount_fmt' => number_format((float)$tx['price'], 0, ',', '.'),
            'type' => $tx['type'],
        ];
    }
    
    jsonResponse(true, '', [
        'id' => $tx['id'],
        'description' => $tx['description'] ?? '',
        'old_price' => (float)$tx['price'],
        'old_price_fmt' => number_format((float)$tx['price'], 0, ',', '.'),
        'old_type' => $tx['type'],
        'old_type_label' => $tx['type'] === 'income' ? 'Thu nhập' : 'Chi tiêu',
        'old_wallets' => $oldWallets,
        'new_wallets' => $walletDetails,
        'sync_status' => $tx['sync_status'],
    ]);
}
