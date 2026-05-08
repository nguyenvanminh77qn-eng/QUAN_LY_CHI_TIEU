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
$errors = [];
if ($price === ''){
    $errors['price']['required'] = "Giá không được để trống";
}else{
    if(!isNumberFloat($price) || (float) $price <= 0)
    $errors['price']['min'] = "Giá phải lớn hơn 0";
}

if ($categoryId === '' || !ctype_digit($categoryId)) {
    $errors['category']['required'] = 'Vui lòng chọn danh mục.';
}
if ($transactionDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDate)) {
    $errors['transactionDate']['invalid'] = 'Ngày giao dịch không hợp lệ.';
}

if (!empty($errors)) {
    setFlashData("errors",$errors);
    setMessage("Vui lòng kiểm tra lỗi dưới đây","error");
    redirect('?template=user&action=add');
}


$date = $transactionDate !== '' ? $transactionDate : date('Y-m-d');
$create_at = date("Y-m-d H:i:s");
$dataInsert = [
    'user_id' => $id,
    'category_id' => (int) $categoryId,
    'price' => (float) $price,
    'description' => $description,
    'transaction_date' => $date,
    'type' => $type,
    'create_at'=>$create_at
];

$insertQuery = insert("transaction",$dataInsert);
if($insertQuery){
    setMessage('Thêm giao dịch thành công.');
    redirect('?template=user&action=add');
}else{
    setMessage('Lỗi hệ thống, vui lòng thử lại sau','error');
    redirect('?template=user&action=add');
}


