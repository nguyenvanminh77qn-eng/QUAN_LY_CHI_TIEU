<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['reconcile_btn'])) {
    redirect('?template=user&action=dashboard');
}

if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

$filterAll = filter();
$userId = getSession('id');
$reconciliationDate = !empty($filterAll['reconciliation_date']) ? trim((string) $filterAll['reconciliation_date']) : date('Y-m-d');
$actualBalanceRaw = isset($filterAll['actual_balance']) ? trim((string) $filterAll['actual_balance']) : '';
$note = !empty($filterAll['note']) ? trim((string) $filterAll['note']) : '';

if ($actualBalanceRaw === '' || !is_numeric($actualBalanceRaw)) {
    setMessage('Vui lòng nhập số dư thực tế hợp lệ.', 'error');
    redirect('?template=user&action=dashboard');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reconciliationDate)) {
    setMessage('Ngày chốt sổ không hợp lệ.', 'error');
    redirect('?template=user&action=dashboard');
}

$actualBalance = (float) $actualBalanceRaw;
$systemBalance = getCurrentBalance($userId, null, $reconciliationDate);
$differenceAmount = $actualBalance - $systemBalance;
$adjustmentTransactionId = null;

if (abs($differenceAmount) >= 1) {
    $adjustmentTransactionId = createBalanceAdjustmentTransaction($userId, $differenceAmount, $reconciliationDate, $note);
}

$reconciliationId = insertGetId('reconciliation', [
    'user_id' => $userId,
    'reconciliation_date' => $reconciliationDate,
    'actual_balance' => $actualBalance,
    'system_balance' => $systemBalance,
    'difference_amount' => $differenceAmount,
    'adjustment_transaction_id' => $adjustmentTransactionId,
    'note' => $note !== '' ? mb_substr($note, 0, 255, 'UTF-8') : null,
    'created_at' => date('Y-m-d H:i:s'),
]);

if ($reconciliationId) {
    $msg = '✅ Chốt sổ ngày ' . date('d/m/Y', strtotime($reconciliationDate))
        . ' | Số dư thực tế: ' . number_format($actualBalance, 0, ',', '.') . 'đ'
        . ' | Hệ thống: ' . number_format($systemBalance, 0, ',', '.') . 'đ'
        . ' | Chênh lệch: ' . number_format($differenceAmount, 0, ',', '.') . 'đ';
    if (abs($differenceAmount) < 1) {
        setMessage($msg . ' — Số dư đã khớp.', 'success');
    } else {
        $msg .= ' → Đã tạo giao dịch điều chỉnh ' . number_format(abs($differenceAmount), 0, ',', '.') . 'đ.';
        setMessage($msg, 'success');
    }
} else {
    setMessage('Không thể chốt sổ lúc này. Vui lòng thử lại sau.', 'error');
}

redirect('?template=user&action=dashboard');
