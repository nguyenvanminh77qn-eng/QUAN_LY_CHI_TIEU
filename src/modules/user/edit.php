<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['edit_btn'])) {
    redirect('?template=user&action=filter');
}

if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$userId = getSession('id');
if ($id <= 0) {
    redirect('?template=user&action=filter');
}

$filterAll = filter();
$price = !empty($filterAll['price']) ? str_replace('.', '', trim((string) $filterAll['price'])) : '';
$categoryId = !empty($filterAll['category']) ? trim((string) $filterAll['category']) : '';
$description = !empty($filterAll['description']) ? trim((string) $filterAll['description']) : '';
$transactionDate = !empty($filterAll['transaction_date']) ? trim((string) $filterAll['transaction_date']) : '';
$type = !empty($filterAll['type']) ? trim((string) $filterAll['type']) : '';
$walletId = !empty($filterAll['wallet_id']) ? (int)$filterAll['wallet_id'] : 0;
$confirmSuspicious = !empty($filterAll['confirm_suspicious']);
$confirmDeficit = !empty($filterAll['confirm_deficit']);
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
    $limitCheckError = checkCategoryLimitExists($userId, $categoryId_int);
    if ($limitCheckError !== null) {
        list($errorCode, $errorMsg) = explode('|', $limitCheckError, 2);
        if ($errorCode === 'not_set') {
            setFlashData('old_data', $data);
            setMessage($errorMsg . " <a href=\"?template=user&action=limit\" style=\"color:#fff; text-decoration:underline; font-weight:bold;\">Đặt hạn mức ngay</a>", "warning");
            redirect("?template=user&action=edit&id=$id");
        }
    }
}

// ── Validate wallet ──
if ($walletId > 0) {
    $wallet = getOne("SELECT id, type FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$walletId, 'uid'=>$userId]);
    if (!$wallet) {
        setFlashData('errors', ['wallet'=>'Ví không tồn tại.']);
        setMessage('Ví không tồn tại.','error');
        setFlashData('old_data', $data);
        redirect("?template=user&action=edit&id=$id");
    }
} else {
    $defaultWallet = getOne("SELECT id FROM wallet WHERE user_id = :uid AND type = 'daily' ORDER BY is_default DESC, id LIMIT 1", ['uid'=>$userId]);
    if ($defaultWallet) {
        $walletId = (int)$defaultWallet['id'];
        $data['wallet_id'] = $walletId;
    }
}

// ── Load old transaction ──
$oldTx = getOne("SELECT * FROM transaction WHERE id = :id AND user_id = :uid", ['id' => $id, 'uid' => $userId]);
if (!$oldTx) {
    setMessage('Giao dịch không tồn tại.', 'error');
    redirect("?template=user&action=edit&id=$id");
}
$oldWalletId = $oldTx['wallet_id'];
$oldType = $oldTx['type'];
$oldPrice = (float)$oldTx['price'];

// ── Detect multi-wallet bailout mode early ──
$multiBailout = $filterAll['multi_bailout'] ?? [];
$multiAmounts = $filterAll['multi_amounts'] ?? [];

// ── Handle multi-wallet batch: collapse siblings into primary for processing ──
$batchSiblingsForEdit = [];
$batchTotalForEdit = 0;
if (!empty($oldTx['batch_id'])) {
    $batchSiblingsForEdit = getAll(
        "SELECT * FROM transaction WHERE batch_id = :bid AND user_id = :uid AND id != :eid",
        ['bid' => $oldTx['batch_id'], 'uid' => $userId, 'eid' => $id]
    ) ?: [];
    if (!empty($batchSiblingsForEdit)) {
        $batchTotalForEdit = (float)$oldTx['price'];
        foreach ($batchSiblingsForEdit as $bs) {
            $batchTotalForEdit += (float)$bs['price'];
        }
    }
}

// ── Check wallet impact: when wallet or type changes, verify old wallet(s) won't go negative ──
$walletChanged = ($walletId > 0 && $oldWalletId > 0 && $walletId != $oldWalletId);
$typeChanged = ($type !== $oldType);
$priceChanged = ((float)$price !== $oldPrice);

// For batch: also consider total price change vs the collapsed total
$priceForOldCheck = $batchTotalForEdit > 0 ? $batchTotalForEdit : $oldPrice;
if (!$walletChanged && !$typeChanged && $batchTotalForEdit > 0) {
    $priceChanged = (abs((float)$price - $priceForOldCheck) > 0.001);
}

if ($walletChanged || $typeChanged || $priceChanged) {
    // Verify OLD wallet(s) won't go negative after removing transaction(s)
    if ($batchTotalForEdit > 0) {
        // Multi-wallet batch: check ALL wallets in the batch
        $allBatchTxs = array_merge([$oldTx], $batchSiblingsForEdit);
        $impactErrors = [];
        foreach ($allBatchTxs as $bt) {
            $btWalletId = (int)$bt['wallet_id'];
            if ($btWalletId <= 0) continue;
            $btBal = getWalletBalance($btWalletId, $userId);
            $btPrice = (float)$bt['price'];
            $btType = $bt['type'];
            $btAfter = $btBal;
            if ($btType === 'income') {
                $btAfter = $btBal - $btPrice;
            } else {
                $btAfter = $btBal + $btPrice;
            }
            if ($btAfter < 0) {
                $wi = getOne("SELECT name, icon FROM wallet WHERE id = :id", ['id'=>$btWalletId]);
                $wName = ($wi['icon'] ?? '💰') . ' ' . ($wi['name'] ?? '');
                $fmtBal = number_format($btBal, 0, ',', '.');
                $fmtP = number_format($btPrice, 0, ',', '.');
                $action = ($btType === 'income') ? "xoá thu nhập {$fmtP}đ" : "xoá chi tiêu {$fmtP}đ";
                $impactErrors[] = "Ví {$wName} sẽ bị âm (SD: {$fmtBal}đ, {$action})";
            }
        }
        if (!empty($impactErrors)) {
            setFlashData('errors', ['wallet_impact' => 'Không thể sửa giao dịch nhiều ví: ' . implode('; ', $impactErrors)]);
            setMessage('Không thể sửa giao dịch vì ví sẽ bị âm.', 'error');
            setFlashData('old_data', $data);
            redirect("?template=user&action=edit&id=$id");
        }
    } else {
        // Single-wallet check (original logic)
        if ($oldWalletId > 0) {
            $oldWalletBal = getWalletBalance($oldWalletId, $userId);
            $oldWalletAfter = $oldWalletBal;
            if ($oldType === 'income') {
                $oldWalletAfter = $oldWalletBal - $oldPrice;
            } else {
                $oldWalletAfter = $oldWalletBal + $oldPrice;
            }
            if ($oldWalletAfter < 0) {
                $oldWalletInfo = getOne("SELECT name, icon FROM wallet WHERE id = :id", ['id'=>$oldWalletId]);
                $oldWName = ($oldWalletInfo['icon'] ?? '💰') . ' ' . ($oldWalletInfo['name'] ?? '');
                $fmtOldBal = number_format($oldWalletBal, 0, ',', '.');
                $fmtOldPrice = number_format($oldPrice, 0, ',', '.');
                $action = ($oldType === 'income') ? "xoá thu nhập {$fmtOldPrice}đ" : "xoá chi tiêu {$fmtOldPrice}đ";
                setFlashData('errors', ['wallet_impact' => "Không thể sửa: ví {$oldWName} sẽ bị âm. Số dư hiện tại: {$fmtOldBal}đ, {$action} sẽ làm giảm số dư."]);
                setMessage('Không thể sửa giao dịch vì ví sẽ bị âm.', 'error');
                setFlashData('old_data', $data);
                redirect("?template=user&action=edit&id=$id");
            }
        }
    }

    // Check new wallet balance for expense (same logic as before, but also considers wallet change)
    if ($type === 'expense' && $walletId > 0 && !$bailoutWalletId && empty($multiBailout)) {
        if ($walletChanged) {
            $walletBalance = getWalletBalance($walletId, $userId);
        } else {
            $walletBalance = getWalletBalance($walletId, $userId, $id);
        }
        if ((float)$price > $walletBalance) {
            $deficit = (float)$price - $walletBalance;
            $otherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$userId, 'wid'=>$walletId]);
            $sufficientWallets = [];
            foreach ($otherWallets as $ow) {
                $ob = getWalletBalance($ow['id'], $userId);
                if ($ob >= (float)$price) {
                    $sufficientWallets[] = $ow + ['balance' => $ob];
                }
            }
            $currentWalletInfo = getOne("SELECT name, icon, type FROM wallet WHERE id = :id", ['id'=>$walletId]);
            $isTarget = $currentWalletInfo && $currentWalletInfo['type'] === 'target';
            $allOtherWallets = getAll("SELECT id, name, icon, type FROM wallet WHERE user_id = :uid AND id != :wid", ['uid'=>$userId, 'wid'=>$walletId]);
            $allWallets = [['id' => $walletId, 'name' => $currentWalletInfo['name'], 'icon' => $currentWalletInfo['icon'], 'type' => $currentWalletInfo['type'], 'balance' => $walletBalance]];
            foreach ($allOtherWallets as $ow) $allWallets[] = $ow + ['balance' => getWalletBalance($ow['id'], $userId)];
            setFlashData('bailout_modal', [
                'deficit' => $deficit, 'price' => $price,
                'wallet_id' => $walletId, 'wallet_name' => $currentWalletInfo['name'] ?? '',
                'wallet_icon' => $currentWalletInfo['icon'] ?? '💰',
                'wallet_type' => $currentWalletInfo['type'] ?? 'daily',
                'current_balance' => $walletBalance,
                'is_target' => $isTarget,
                'sufficient_wallets' => $sufficientWallets,
                'all_wallets' => $allWallets,
                'category_id' => $categoryId, 'category' => $categoryId, 'description' => $description,
                'transaction_date' => $transactionDate, 'type' => $type,
            ]);
            setFlashData('old_data', $data);
            setMessage('Ví không đủ số dư.','warning');
            redirect("?template=user&action=edit&id=$id");
        }
    }
}

// ── Handle multi-wallet bailout (Edit) ──
if (!empty($multiBailout) && is_array($multiBailout)) {
    // Delete old batch siblings if this was a multi-wallet transaction
    if (!empty($batchSiblingsForEdit)) {
        foreach ($batchSiblingsForEdit as $bs) {
            delete("transaction", "id = :id AND user_id = :uid", ['id' => $bs['id'], 'uid' => $userId]);
        }
    }
    $batchId = uniqid('b', true);
    // Update original transaction: reduce price to original wallet's balance
    $origBal = getWalletBalance($walletId, $userId, $id);
    if ($origBal > 0) {
        update('transaction', ['price' => $origBal, 'batch_id' => $batchId], "id = :id AND user_id = :uid", ['id' => $id, 'uid' => $userId]);
    }
    // Create new transactions for supplementing wallets
    foreach ($multiBailout as $mWid) {
        $mWid = (int)$mWid;
        $mAmt = (float)($multiAmounts[$mWid] ?? 0);
        if ($mAmt <= 0) continue;
        if ($mWid == $walletId) continue; // already handled above
        $w = getOne("SELECT id FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$mWid, 'uid'=>$userId]);
        if (!$w) continue;
        $bal = getWalletBalance($mWid, $userId);
        if ($mAmt > $bal) {
            setMessage('Ví không đủ số dư cho phân bổ đa ví.', 'error');
            setFlashData('old_data', $data);
            redirect("?template=user&action=edit&id=$id");
        }
        insert('transaction', [
            'user_id' => $userId, 'wallet_id' => $mWid,
            'category_id' => (int)$categoryId, 'price' => $mAmt,
            'type' => 'expense', 'description' => $description,
            'transaction_date' => $transactionDate ?: date('Y-m-d'),
            'create_at' => date('Y-m-d H:i:s'), 'source_type' => 'manual',
            'batch_id' => $batchId,
        ]);
    }
    $categoryInfo = getOne("SELECT name, icon FROM category WHERE id = :id", ['id'=>(int)$categoryId]);
    setFlashData('transaction_success', [
        'type' => $type, 'price' => $price,
        'category_name' => $categoryInfo['name'] ?? 'Không xác định',
        'category_icon' => $categoryInfo['icon'] ?? '📦',
        'description' => $description,
        'transaction_date' => $transactionDate ?: date('Y-m-d'),
    ]);
    redirect("?template=user&action=edit&id=$id");
}

// ── Handle switching to another wallet (bailout) ──
if ($bailoutWalletId > 0) {
    $bw = getOne("SELECT id, type FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$bailoutWalletId, 'uid'=>$userId]);
    if (!$bw) {
        setMessage('Ví không hợp lệ.','error');
        setFlashData('old_data', $data);
        redirect("?template=user&action=edit&id=$id");
    }
    $walletId = $bailoutWalletId;
    $data['wallet_id'] = $walletId;
}

// ── Confirm deficit (continue with current wallet despite insufficient balance) ──
if ($confirmDeficit) {
    $walletId = (int)$filterAll['wallet_id'];
    if ($walletId > 0) {
        $w = getOne("SELECT id FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$walletId, 'uid'=>$userId]);
        if (!$w) {
            setMessage('Ví không hợp lệ.', 'error');
            setFlashData('old_data', $data);
            redirect("?template=user&action=edit&id=$id");
        }
    }
    $data['wallet_id'] = $walletId;
}

$result = validateTransaction($userId, $data, $id);

// Nếu user confirm deficit, bỏ qua lỗi balance để khỏi loop
if ($confirmDeficit && !empty($result['errors']['balance'])) {
    unset($result['errors']['balance']);
    if (empty($result['errors'])) {
        $result['valid'] = true;
    }
}

if (!empty($result['errors'])) {
    setFlashData('errors', $result['errors']);
    setFlashData('old_data', $data);
    $hasBudgetError = !empty($result['errors']['budget']) || !empty($result['errors']['monthly_budget']);
    $msg = $hasBudgetError ? 'Giao dịch vượt quá ngân sách cho phép.' : 'Vui lòng kiểm tra thông tin.';
    setMessage($msg, 'error');
    redirect("?template=user&action=edit&id=$id");
}

if (!empty($result['warnings']) && !$confirmSuspicious) {
    setFlashData('suspicious_warning', $result['warnings']);
    setFlashData('suspicious_form_data', $data);
    redirect("?template=user&action=edit&id=$id");
}

// ── If this was a multi-wallet batch, clean up siblings now ──
if (!empty($batchSiblingsForEdit)) {
    foreach ($batchSiblingsForEdit as $bs) {
        delete("transaction", "id = :id AND user_id = :uid", ['id' => $bs['id'], 'uid' => $userId]);
    }
}

// ── Pending edit: if wallet(s) can't support the new balance, freeze instead ──
$needsPendingEdit = false;
if ($walletId > 0 && !$confirmDeficit) {
    $currentBal = getWalletBalance($walletId, $userId, $id);
    $newBal = $currentBal + ($type === 'income' ? (float)$price : - (float)$price);
    if ($newBal < 0) {
        $needsPendingEdit = true;
    }
}

if ($needsPendingEdit) {
    $pendingWallets = [
        ['wallet_id' => (int)$walletId, 'amount' => (float)$price, 'type' => $type]
    ];
    update('transaction', [
        'sync_status' => 'pending_edit',
        'pending_amount' => (float)$price,
        'pending_type' => $type,
        'pending_wallets_json' => json_encode($pendingWallets),
        'batch_id' => null,
        'update_at' => date('Y-m-d H:i:s'),
    ], "id = :id AND user_id = :uid", ['id' => $id, 'uid' => $userId]);
    setMessage('Số dư ví không đủ. Yêu cầu sửa của bạn đã được đóng băng và sẽ tự động áp dụng khi ví có đủ tiền!');
    redirect("?template=user&action=edit&id=$id");
}

$dataUpdate = [
    'category_id' => (int) $categoryId,
    'price' => (float) $price,
    'description' => $description,
    'transaction_date' => $transactionDate !== '' ? $transactionDate : date('Y-m-d'),
    'type' => $type,
    'wallet_id' => $walletId > 0 ? $walletId : null,
    'batch_id' => null,
    'source_type' => 'manual',
    'update_at' => date('Y-m-d H:i:s'),
];

$updateStatus = update('transaction', $dataUpdate, "id = :id AND user_id = :user_id", ['id' => $id, 'user_id' => $userId]);
if ($updateStatus) {
$categoryInfo = getOne("SELECT name, icon FROM category WHERE id = :id", ['id' => (int) $categoryId]);
$catName = $categoryInfo ? $categoryInfo['name'] : 'Không xác định';
$catIcon = $categoryInfo ? ($categoryInfo['icon'] ?? '📦') : '📦';

setFlashData('transaction_success', [
    'type' => $type,
    'price' => $price,
    'category_name' => $catName,
    'category_icon' => $catIcon,
    'description' => $description,
    'transaction_date' => $transactionDate !== '' ? $transactionDate : date('Y-m-d'),
]);

redirect("?template=user&action=edit&id=$id");
}

setFlashData('message', 'Lỗi hệ thống, vui lòng thử lại sau.');
setFlashData('message_type', 'error');
redirect("?template=user&action=edit&id=$id");
