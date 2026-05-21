<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['edit_btn'])) {
    redirect('?template=user&action=filter');
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
$confirmSuspicious = !empty($filterAll['confirm_suspicious']);

$data = [
    'price' => $price,
    'category_id' => $categoryId,
    'description' => $description,
    'transaction_date' => $transactionDate,
    'type' => $type,
];

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
    setMessage('Giao dịch cần xác nhận. Vui lòng xem cảnh báo bên dưới.', 'warning');
    redirect("?template=user&action=edit&id=$id");
}

$dataUpdate = [
    'category_id' => (int) $categoryId,
    'price' => (float) $price,
    'description' => $description,
    'transaction_date' => $transactionDate !== '' ? $transactionDate : date('Y-m-d'),
    'type' => $type,
    'source_type' => 'manual',
];

$updateStatus = update('transaction', $dataUpdate, "id = :id AND user_id = :user_id", ['id' => $id, 'user_id' => $userId]);
if ($updateStatus) {
    setFlashData('message', 'Cập nhật giao dịch thành công.');
    setFlashData('message_type', 'success');
    redirect('?template=user&action=filter');
}

setFlashData('message', 'Lỗi hệ thống, vui lòng thử lại sau.');
setFlashData('message_type', 'error');
redirect("?template=user&action=edit&id=$id");
