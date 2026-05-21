<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

if (!isset($_POST['add_btn'])) {
    return;
}

$filterAll = filter();
$price = !empty($filterAll['price']) ? trim((string) $filterAll['price']) : '';
$categoryId = !empty($filterAll['category']) ? trim((string) $filterAll['category']) : '';
$description = !empty($filterAll['description']) ? trim((string) $filterAll['description']) : '';
$transactionDate = !empty($filterAll['transaction_date']) ? trim((string) $filterAll['transaction_date']) : '';
$type = !empty($filterAll['type']) ? trim((string) $filterAll['type']) : '';
$id = getSession('id');
$confirmSuspicious = !empty($filterAll['confirm_suspicious']);

$data = [
    'price' => $price,
    'category_id' => $categoryId,
    'description' => $description,
    'transaction_date' => $transactionDate,
    'type' => $type,
];

$result = validateTransaction($id, $data);
if (!empty($result['errors'])) {
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
    setMessage('Giao dịch cần xác nhận. Vui lòng xem cảnh báo bên dưới.', 'warning');
    redirect('?template=user&action=add');
}

$insertId = insertGetId('transaction', [
    'user_id' => $id,
    'category_id' => (int) $categoryId,
    'price' => (float) $price,
    'description' => $description,
    'transaction_date' => $transactionDate !== '' ? $transactionDate : date('Y-m-d'),
    'type' => $type,
    'create_at' => date('Y-m-d H:i:s'),
    'source_type' => 'manual',
]);

if ($insertId) {
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

    setMessage('Giao dịch được thêm thành công.');
    redirect('?template=user&action=add');
}

setMessage('Lỗi hệ thống, vui lòng thử lại sau.', 'error');
redirect('?template=user&action=add');
