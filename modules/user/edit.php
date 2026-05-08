<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_btn'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
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
    $errors = [];

    if ($price === ''){
        $errors['price']['required'] = "Số tiền không được để trống";
    }else{
        if(!isNumberFloat($price) || (float) $price <= 0)
            $errors['price']['min'] = "Số tiền phải lớn hơn 0";
    }

    if ($categoryId === '' || !ctype_digit($categoryId)) {
        $errors['category']['required'] = 'Vui lòng chọn danh mục.';
    }
    if ($transactionDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDate)) {
        $errors['transactionDate']['invalid'] = 'Ngày giao dịch không hợp lệ.';
    }

    if (!empty($errors)) {
        setFlashData("errors",$errors);
        setMessage("Vui lòng kiểm tra thông tin.","error");
        redirect("?template=user&action=edit&id=$id");
    }

    $date = $transactionDate !== '' ? $transactionDate : date('Y-m-d');
    $dataUpdate = [
        'category_id' => (int) $categoryId,
        'price' => (float) $price,
        'description' => $description,
        'transaction_date' => $date,
        'type' => $type
    ];

    $updateStatus = update('transaction', $dataUpdate, "id = :id AND user_id = :user_id", ['id' => $id, 'user_id' => $userId]);

    if($updateStatus){
        setFlashData("message", "Cập nhật giao dịch thành công.");
        setFlashData("message_type", "success");
        redirect('?template=user&action=filter');
    }else{
        setFlashData("message", "Lỗi hệ thống, vui lòng thử lại sau.");
        setFlashData("message_type", "error");
        redirect("?template=user&action=edit&id=$id");
    }
} else {
    redirect('?template=user&action=filter');
}
