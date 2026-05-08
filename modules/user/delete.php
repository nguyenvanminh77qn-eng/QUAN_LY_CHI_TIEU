<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn-delete'])) {
    $filterALl = filter();
    $ids = $filterALl['ids'] ?? [];
    $id = $filterALl['id'];

    if (!empty($ids)) {
        // PDO không hỗ trợ bind cả chuỗi "1,2,3" vào IN(:ids)
        // Phải tạo placeholder riêng cho mỗi ID
        $ids = array_map('intval', $ids); // Ép kiểu int để an toàn
        $placeholders = [];
        $params = ['user_id' => $id];
        foreach ($ids as $i => $idVal) {
            $key = "id$i";
            $placeholders[] = ":$key";
            $params[$key] = $idVal;
        }
        $inClause = implode(',', $placeholders);
        
        $deleteStatus = delete("transaction", "id IN ($inClause) AND user_id = :user_id", $params);

        if ($deleteStatus) {
            setMessage("Đã xóa thành công");
        } else {
            setMessage("Lỗi hệ thống, vui lòng thử lại sau.");
        }
    }else{
        setMessage("Vui lòng chọn ít nhất 1 giao dịch", "error");   
    }
}

redirect("?template=user&action=filter");