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
$price = !empty($filterAll['price']) ? trim((string) $filterAll['price']) : '';
$categoryId = !empty($filterAll['category']) ? trim((string) $filterAll['category']) : '';
$description = !empty($filterAll['description']) ? trim((string) $filterAll['description']) : '';
$transactionDate = !empty($filterAll['transaction_date']) ? trim((string) $filterAll['transaction_date']) : '';
$type = !empty($filterAll['type']) ? trim((string) $filterAll['type']) : '';
$walletId = !empty($filterAll['wallet_id']) ? (int)$filterAll['wallet_id'] : 0;
$confirmSuspicious = !empty($filterAll['confirm_suspicious']);
$bailoutWalletId = !empty($filterAll['bailout_wallet']) ? (int)$filterAll['bailout_wallet'] : 0;


$data = [
    'price' => $price,
    'category_id' => $categoryId,
    'description' => $description,
    'transaction_date' => $transactionDate,
    'type' => $type,
    'wallet_id' => $walletId,
];

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

// ── Detect multi-wallet bailout mode early ──
$multiBailout = $filterAll['multi_bailout'] ?? [];
$multiAmounts = $filterAll['multi_amounts'] ?? [];

// ── Check balance for expense + unified bailout (Edit) ──
if ($type === 'expense' && $walletId > 0 && !$bailoutWalletId && empty($multiBailout)) {
    $walletBalance = getWalletBalance($walletId, $userId, $id);
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

// ── Handle multi-wallet bailout (Edit) ──
if (!empty($multiBailout) && is_array($multiBailout)) {
    $batchId = uniqid('b', true);
    // Update original transaction: reduce price to original wallet's balance
    $origBal = getWalletBalance($walletId, $userId, $id);
    if ($origBal > 0) {
        update('transaction', ['price' => $origBal, 'batch_id' => $batchId], "id = :id AND user_id = :uid", ['id'=>$id, 'user_id'=>$userId]);
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
        if ($mAmt > $bal) continue;
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
    $data['wallet_id'] = $walletId;
}

$result = validateTransaction($userId, $data, $id);
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

$dataUpdate = [
    'category_id' => (int) $categoryId,
    'price' => (float) $price,
    'description' => $description,
    'transaction_date' => $transactionDate !== '' ? $transactionDate : date('Y-m-d'),
    'type' => $type,
    'wallet_id' => $walletId > 0 ? $walletId : null,
    'source_type' => 'manual',
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
