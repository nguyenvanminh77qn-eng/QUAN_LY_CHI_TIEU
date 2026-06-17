<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
if (!isset($_POST['add_btn'])) return;
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền","error"); redirect("?template=admin&action=dashboard"); }

$filterAll = filter();
$price = !empty($filterAll['price']) ? str_replace('.', '', trim($filterAll['price'])) : '';
$categoryId = !empty($filterAll['category']) ? trim($filterAll['category']) : '';
$description = !empty($filterAll['description']) ? trim($filterAll['description']) : '';
$transactionDate = !empty($filterAll['transaction_date']) ? trim($filterAll['transaction_date']) : '';
$type = !empty($filterAll['type']) ? trim($filterAll['type']) : '';
$walletId = !empty($filterAll['wallet_id']) ? (int)$filterAll['wallet_id'] : 0;
$id = getSession('id');
$confirmSuspicious = !empty($filterAll['confirm_suspicious']);
$useSavings = !empty($filterAll['use_savings']);
$bailoutWalletId = !empty($filterAll['bailout_wallet']) ? (int)$filterAll['bailout_wallet'] : 0;

$data = [
    'price' => $price,
    'category_id' => $categoryId,
    'description' => $description,
    'transaction_date' => $transactionDate,
    'type' => $type,
    'wallet_id' => $walletId,
];

// ── CHECK: Danh mục phải có hạn mức ──
if ($type === 'expense' && !empty($categoryId)) {
    $categoryId_int = (int)$categoryId;
    $limitCheckError = checkCategoryLimitExists($id, $categoryId_int);
    if ($limitCheckError !== null) {
        list($errorCode, $errorMsg) = explode('|', $limitCheckError, 2);
        if ($errorCode === 'not_set') {
            setFlashData('old_data', $data);
            setMessage($errorMsg . " <a href=\"?template=user&action=limit\" style=\"color:#fff; text-decoration:underline; font-weight:bold;\">Đặt hạn mức ngay</a>", "warning");
            redirect('?template=user&action=add');
        }
    }
}

// ── Validate wallet ──
if ($walletId > 0) {
    $wallet = getOne("SELECT id, type FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$walletId, 'uid'=>$id]);
    if (!$wallet) {
        setFlashData('errors', ['wallet'=>'Ví không tồn tại.']);
        setMessage('Ví không tồn tại.','error');
        setFlashData('old_data', $data);
        redirect('?template=user&action=add');
    }
} else {
    $defaultWallet = getOne("SELECT id FROM wallet WHERE user_id = :uid AND type = 'daily' ORDER BY is_default DESC, id LIMIT 1", ['uid'=>$id]);
    if ($defaultWallet) {
        $walletId = (int)$defaultWallet['id'];
        $data['wallet_id'] = $walletId;
    }
}

// ── Detect multi-wallet bailout mode early ──
$multiBailout = $filterAll['multi_bailout'] ?? [];
$multiAmounts = $filterAll['multi_amounts'] ?? [];

// ── Check balance for expense + unified bailout ──
$balanceError = null;
$deficit = 0;
if ($type === 'expense' && $walletId > 0 && !$bailoutWalletId && empty($multiBailout)) {
    $walletBalance = getWalletBalance($walletId, $id);
    if ((float)$price > $walletBalance) {
        $deficit = (float)$price - $walletBalance;

        // Find all other wallets with sufficient balance
        $otherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$id, 'wid'=>$walletId]);
        $sufficientWallets = [];
        foreach ($otherWallets as $ow) {
            $ob = getWalletBalance($ow['id'], $id);
            if ($ob >= (float)$price) {
                $sufficientWallets[] = $ow + ['balance' => $ob];
            }
        }

        $currentWalletInfo = getOne("SELECT name, icon, type FROM wallet WHERE id = :id", ['id'=>$walletId]);
        $isTarget = $currentWalletInfo && $currentWalletInfo['type'] === 'target';

        $allOtherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$id, 'wid'=>$walletId]);
        $allWallets = [['id' => $walletId, 'name' => $currentWalletInfo['name'], 'icon' => $currentWalletInfo['icon'], 'type' => $currentWalletInfo['type'], 'balance' => $walletBalance]];
        foreach ($allOtherWallets as $ow) {
            $allWallets[] = $ow + ['balance' => getWalletBalance($ow['id'], $id)];
        }

        setFlashData('bailout_modal', [
            'deficit' => $deficit,
            'price' => $price,
            'wallet_id' => $walletId,
            'wallet_name' => $currentWalletInfo['name'] ?? '',
            'wallet_icon' => $currentWalletInfo['icon'] ?? '💰',
            'wallet_type' => $currentWalletInfo['type'] ?? 'daily',
            'current_balance' => $walletBalance,
            'is_target' => $isTarget,
            'sufficient_wallets' => $sufficientWallets,
            'all_wallets' => $allWallets,
            'category_id' => $categoryId,
            'category' => $categoryId,
            'description' => $description,
            'transaction_date' => $transactionDate,
            'type' => $type,
        ]);
        setFlashData('old_data', $data);
        setMessage('Ví không đủ số dư.','warning');
        redirect('?template=user&action=add');
    }
}

// ── Handle switching to another wallet (bailout) ──
if ($bailoutWalletId > 0) {
    $bw = getOne("SELECT id, type FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$bailoutWalletId, 'uid'=>$id]);
    if (!$bw) {
        setMessage('Ví không hợp lệ.','error');
        setFlashData('old_data', $data);
        redirect('?template=user&action=add');
    }
    $walletId = $bailoutWalletId;
    $data['wallet_id'] = $walletId;
}

// ── Handle multi-wallet bailout ──
if (!empty($multiBailout) && is_array($multiBailout)) {
    $batchId = uniqid('b', true);
    $insIds = [];
    $totalMulti = 0;
    foreach ($multiBailout as $mWid) {
        $mWid = (int)$mWid;
        $mAmt = (float)($multiAmounts[$mWid] ?? 0);
        if ($mAmt <= 0) continue;
        $w = getOne("SELECT id FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$mWid, 'uid'=>$id]);
        if (!$w) continue;
        $bal = getWalletBalance($mWid, $id);
        if ($mAmt > $bal) {
            setMessage('Ví không đủ số dư cho phân bổ đa ví.', 'error');
            setFlashData('old_data', $data);
            redirect('?template=user&action=add');
        }
        $totalMulti += $mAmt;
        $insIds[] = insertGetId('transaction', [
            'user_id' => $id, 'wallet_id' => $mWid,
            'category_id' => (int)$categoryId, 'price' => $mAmt,
            'type' => 'expense', 'description' => $description,
            'transaction_date' => $transactionDate ?: date('Y-m-d'),
            'create_at' => date('Y-m-d H:i:s'), 'source_type' => 'manual',
            'batch_id' => $batchId,
        ]);
    }
    if (!empty($insIds)) {
        $categoryInfo = getOne("SELECT name, icon FROM category WHERE id = :id", ['id'=>(int)$categoryId]);
        setFlashData('transaction_success', [
            'type' => $type, 'price' => $price,
            'category_name' => $categoryInfo['name'] ?? 'Không xác định',
            'category_icon' => $categoryInfo['icon'] ?? '📦',
            'description' => $description,
            'transaction_date' => $transactionDate ?: date('Y-m-d'),
            'wallet_name' => count($insIds) . ' ví',
        ]);
        redirect('?template=user&action=add');
    }
    setMessage('Không thể thanh toán với các ví đã chọn.','error');
    setFlashData('old_data', $data);
    redirect('?template=user&action=add');
}

// ── Validation ──
if ($type === 'expense' && !$bailoutWalletId && !$confirmDeficit && empty($multiBailout)) {
    $balanceCheck = checkBalanceSufficient($id, (float)$price, null, $walletId);
    if ($balanceCheck !== null) {
        $parts = explode('|', $balanceCheck);
        $deficitVal = (int)($parts[1] ?? 0);
        // Use unified bailout instead
        if ($deficitVal > 0) {
            // try to find wallets
            $otherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$id, 'wid'=>$walletId]);
            $sufficientWallets = [];
            foreach ($otherWallets as $ow) {
                $ob = getWalletBalance($ow['id'], $id);
                if ($ob >= (float)$price) {
                    $sufficientWallets[] = $ow + ['balance' => $ob];
                }
            }
            $cwi = getOne("SELECT name, icon, type FROM wallet WHERE id = :id", ['id'=>$walletId]);
            $isTarget = $cwi && $cwi['type'] === 'target';
            $allOtherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$id, 'wid'=>$walletId]);
            $allWallets = [['id' => $walletId, 'name' => $cwi['name'], 'icon' => $cwi['icon'], 'type' => $cwi['type'], 'balance' => getWalletBalance($walletId, $id)]];
            foreach ($allOtherWallets as $ow) $allWallets[] = $ow + ['balance' => getWalletBalance($ow['id'], $id)];
            setFlashData('bailout_modal', [
                'deficit' => $deficitVal, 'price' => $price,
                'wallet_id' => $walletId, 'wallet_name' => $cwi['name']??'', 'wallet_icon' => $cwi['icon']??'💰',
                'wallet_type' => $cwi['type']??'daily', 'current_balance' => getWalletBalance($walletId, $id),
                'is_target' => $isTarget, 'sufficient_wallets' => $sufficientWallets, 'all_wallets' => $allWallets,
                'category_id' => $categoryId, 'category' => $categoryId, 'description' => $description,
                'transaction_date' => $transactionDate, 'type' => $type,
            ]);
            setFlashData('old_data', $data);
            setMessage('Ví không đủ số dư.','warning');
            redirect('?template=user&action=add');
        }
    }
}

// ── Validate fields + limits + budget + suspicious ──
$result = validateTransaction($id, $data);

// Nếu user confirm deficit, bỏ qua lỗi balance để khỏi loop
if (!empty($result['errors'])) {
    $balanceError = $result['errors']['balance'] ?? '';
    if ($balanceError !== null && str_starts_with($balanceError, 'deficit|')) {
        $parts = explode('|', $balanceError);
        $deficitVal = (int)($parts[1] ?? 0);
        $otherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$id, 'wid'=>$walletId]);
        $sufficientWallets = [];
        foreach ($otherWallets as $ow) {
            $ob = getWalletBalance($ow['id'], $id);
            if ($ob >= (float)$price) {
                $sufficientWallets[] = $ow + ['balance' => $ob];
            }
        }
        $cwi = getOne("SELECT name, icon, type FROM wallet WHERE id = :id", ['id'=>$walletId]);
        $isTarget = $cwi && $cwi['type'] === 'target';
        $allOtherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$id, 'wid'=>$walletId]);
        $allWallets = [['id' => $walletId, 'name' => $cwi['name'], 'icon' => $cwi['icon'], 'type' => $cwi['type'], 'balance' => getWalletBalance($walletId, $id)]];
        foreach ($allOtherWallets as $ow) $allWallets[] = $ow + ['balance' => getWalletBalance($ow['id'], $id)];
        setFlashData('bailout_modal', [
            'deficit' => $deficitVal, 'price' => $price,
            'wallet_id' => $walletId, 'wallet_name' => $cwi['name']??'', 'wallet_icon' => $cwi['icon']??'💰',
            'wallet_type' => $cwi['type']??'daily', 'current_balance' => getWalletBalance($walletId, $id),
            'is_target' => $isTarget, 'sufficient_wallets' => $sufficientWallets, 'all_wallets' => $allWallets,
            'category_id' => $categoryId, 'category' => $categoryId, 'description' => $description,
            'transaction_date' => $transactionDate, 'type' => $type,
        ]);
        setFlashData('old_data', $data);
        setMessage('Ví không đủ số dư.','warning');
        redirect('?template=user&action=add');
    }
    setFlashData('errors', $result['errors']);
    $hasBudgetError = !empty($result['errors']['budget']) || !empty($result['errors']['monthly_budget']);
    $msg = $hasBudgetError ? 'Giao dịch vượt quá ngân sách cho phép.' : 'Vui lòng kiểm tra lại thông tin bên dưới.';
    setMessage($msg, 'error');
    setFlashData('old_data', $data);
    redirect('?template=user&action=add');
}

if (!empty($result['warnings']) && !$confirmSuspicious) {
    setFlashData('suspicious_warning', $result['warnings']);
    setFlashData('suspicious_form_data', $data);
    redirect('?template=user&action=add');
}

// ── Insert transaction ──
$insertData = [
    'user_id' => $id,
    'wallet_id' => $walletId > 0 ? $walletId : null,
    'category_id' => (int)$categoryId,
    'price' => (float)$price,
    'description' => $description,
    'transaction_date' => $transactionDate !== '' ? $transactionDate : date('Y-m-d'),
    'type' => $type,
    'create_at' => date('Y-m-d H:i:s'),
    'source_type' => 'manual',
];
$insertId = insertGetId('transaction', $insertData);

if ($insertId) {
    // ── Auto-retry pending_edit: check if income wallet can un-freeze any pending edit ──
    if ($type === 'income' && $walletId > 0) {
        $allPending = getAll(
            "SELECT * FROM transaction WHERE sync_status = 'pending_edit' AND user_id = :uid",
            ['uid' => $id]
        ) ?: [];
        foreach ($allPending as $pend) {
            $pendId = (int)$pend['id'];
            $pWallets = parsePendingWallets($pend);
            if (empty($pWallets)) continue;
            $found = false;
            foreach ($pWallets as $pw) {
                if ((int)($pw['wallet_id'] ?? 0) === $walletId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) continue;
            if (canApplyPendingEdit($pend, $pWallets, $id)) {
                update('transaction', [
                    'sync_status' => 'ready',
                ], "id = :id AND user_id = :uid", ['id' => $pendId, 'uid' => $id]);
            }
        }
    }

    $categoryInfo = getOne("SELECT name, icon FROM category WHERE id = :id", ['id'=>(int)$categoryId]);
    $catName = $categoryInfo ? $categoryInfo['name'] : 'Không xác định';
    $catIcon = $categoryInfo ? ($categoryInfo['icon'] ?? '📦') : '📦';
    $wName = '';
    if ($walletId > 0) {
        $wi = getOne("SELECT name FROM wallet WHERE id = :id", ['id'=>$walletId]);
        $wName = $wi ? $wi['name'] : '';
    }
    setFlashData('transaction_success', [
        'type' => $type,
        'price' => $price,
        'category_name' => $catName,
        'category_icon' => $catIcon,
        'description' => $description,
        'transaction_date' => $transactionDate !== '' ? $transactionDate : date('Y-m-d'),
        'wallet_name' => $wName,
    ]);
    redirect('?template=user&action=add');
}

setMessage('Lỗi hệ thống, vui lòng thử lại sau.','error');
redirect('?template=user&action=add');
