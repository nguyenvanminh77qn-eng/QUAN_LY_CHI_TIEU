<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
if (!isset($_POST['transfer_btn'])) return;
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền","error"); redirect("?template=admin&action=dashboard"); }

$filterAll = filter();
$userId = getSession('id');

$fromId = (int)($filterAll['from_wallet'] ?? 0);
$toId = (int)($filterAll['to_wallet'] ?? 0);
$amount = (float)($filterAll['amount'] ?? 0);
$note = trim($filterAll['note'] ?? '');

if ($amount <= 0) { setMessage('Số tiền không hợp lệ.','error'); redirect('?template=user&action=wallet'); }
if ($fromId === $toId) { setMessage('Không thể chuyển tiền giữa cùng một ví.','error'); redirect('?template=user&action=wallet'); }

$fromWallet = getOne("SELECT id,name,icon,type FROM wallet WHERE id=:id AND user_id=:uid", ['id'=>$fromId, 'uid'=>$userId]);
$toWallet = getOne("SELECT id,name,icon,type FROM wallet WHERE id=:id AND user_id=:uid", ['id'=>$toId, 'uid'=>$userId]);

if (!$fromWallet || !$toWallet) { setMessage('Ví không hợp lệ.','error'); redirect('?template=user&action=wallet'); }

$fromBal = getWalletBalance($fromId, $userId);
if ($fromBal < $amount) { setMessage('Số dư ví <strong>'.htmlspecialchars($fromWallet['name']).'</strong> không đủ ('.number_format($fromBal,0,',','.').'đ).','error'); redirect('?template=user&action=wallet'); }

$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');
$description = !empty($note) ? $note : 'Chuyển khoản';

// Expense on source
insert('transaction', [
    'user_id' => $userId, 'wallet_id' => $fromId, 'category_id' => 8,
    'price' => $amount, 'type' => 'expense',
    'description' => 'Chuyển: '.$description,
    'transaction_date' => $today, 'create_at' => $now, 'source_type' => 'transfer',
]);
// Income on destination
insert('transaction', [
    'user_id' => $userId, 'wallet_id' => $toId, 'category_id' => 8,
    'price' => $amount, 'type' => 'income',
    'description' => 'Nhận: '.$description,
    'transaction_date' => $today, 'create_at' => $now, 'source_type' => 'transfer',
]);
// Log to wallet_transfer for clean history lookup
insert('wallet_transfer', [
    'user_id' => $userId,
    'from_wallet_id' => $fromId,
    'to_wallet_id' => $toId,
    'amount' => $amount,
    'description' => $description,
    'created_at' => $now,
]);

setMessage('Đã chuyển <strong>'.number_format($amount,0,',','.').'đ</strong> từ <strong>'.htmlspecialchars($fromWallet['icon']??'💰').' '.htmlspecialchars($fromWallet['name']).'</strong> → <strong>'.htmlspecialchars($toWallet['icon']??'💰').' '.htmlspecialchars($toWallet['name']).'</strong>','success');
redirect('?template=user&action=wallet');
