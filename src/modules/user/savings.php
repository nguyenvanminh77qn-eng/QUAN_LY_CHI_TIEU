<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền","error"); redirect("?template=admin&action=dashboard"); }

$filterAll = filter();
$userId = getSession('id');

// ── Deposit to savings ──
if (isset($_POST['savings_deposit'])) {
    $targetId = (int)($filterAll['target_id'] ?? 0);
    $sourceId = (int)($filterAll['source_wallet'] ?? 0);
    $amount = (float)($filterAll['amount'] ?? 0);

    if ($amount <= 0) { setMessage('Số tiền không hợp lệ.','error'); redirect('?template=user&action=savings'); }

    $target = getOne("SELECT id,name FROM wallet WHERE id=:id AND user_id=:uid AND type='target'", ['id'=>$targetId, 'uid'=>$userId]);
    $source = getOne("SELECT id,name,type FROM wallet WHERE id=:id AND user_id=:uid AND type='daily'", ['id'=>$sourceId, 'uid'=>$userId]);

    if (!$target || !$source) { setMessage('Ví không hợp lệ.','error'); redirect('?template=user&action=savings'); }

    $sourceBal = getWalletBalance($sourceId, $userId);
    if ($sourceBal < $amount) { setMessage('Số dư ví nguồn không đủ.','error'); redirect('?template=user&action=savings'); }

    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    // Expense on source daily wallet
    insert('transaction', [
        'user_id' => $userId,
        'wallet_id' => $sourceId,
        'category_id' => 8,
        'price' => $amount,
        'type' => 'expense',
        'description' => 'Chuyển vào quỹ '.$target['name'],
        'transaction_date' => $today,
        'create_at' => $now,
        'source_type' => 'transfer',
    ]);
    // Income on target savings wallet
    insert('transaction', [
        'user_id' => $userId,
        'wallet_id' => $targetId,
        'category_id' => 8,
        'price' => $amount,
        'type' => 'income',
        'description' => 'Nhận từ '.$source['name'],
        'transaction_date' => $today,
        'create_at' => $now,
        'source_type' => 'transfer',
    ]);

    setMessage('Đã chuyển '.number_format($amount,0,',','.').'đ vào '.$target['name'],'success');
    redirect('?template=user&action=savings');
}

// ── Withdraw from savings ──
if (isset($_POST['savings_withdraw'])) {
    $targetId = (int)($filterAll['target_id'] ?? 0);
    $destId = (int)($filterAll['dest_wallet'] ?? 0);
    $amount = (float)($filterAll['amount'] ?? 0);

    if ($amount <= 0) { setMessage('Số tiền không hợp lệ.','error'); redirect('?template=user&action=savings'); }

    $target = getOne("SELECT id,name FROM wallet WHERE id=:id AND user_id=:uid AND type='target'", ['id'=>$targetId, 'uid'=>$userId]);
    $dest = getOne("SELECT id,name,type FROM wallet WHERE id=:id AND user_id=:uid AND type='daily'", ['id'=>$destId, 'uid'=>$userId]);

    if (!$target || !$dest) { setMessage('Ví không hợp lệ.','error'); redirect('?template=user&action=savings'); }

    $targetBal = getWalletBalance($targetId, $userId);
    if ($targetBal < $amount) { setMessage('Số dư quỹ không đủ.','error'); redirect('?template=user&action=savings'); }

    $now = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    // Expense on target savings wallet
    insert('transaction', [
        'user_id' => $userId,
        'wallet_id' => $targetId,
        'category_id' => 8,
        'price' => $amount,
        'type' => 'expense',
        'description' => 'Rút về '.$dest['name'],
        'transaction_date' => $today,
        'create_at' => $now,
        'source_type' => 'transfer',
    ]);
    // Income on destination daily wallet
    insert('transaction', [
        'user_id' => $userId,
        'wallet_id' => $destId,
        'category_id' => 8,
        'price' => $amount,
        'type' => 'income',
        'description' => 'Rút từ quỹ '.$target['name'],
        'transaction_date' => $today,
        'create_at' => $now,
        'source_type' => 'transfer',
    ]);

    setMessage('Đã rút '.number_format($amount,0,',','.').'đ từ '.$target['name'],'success');
    redirect('?template=user&action=savings');
}
