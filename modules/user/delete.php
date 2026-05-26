<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn-delete'])) {
    $filterALl = filter();
    $ids = $filterALl['ids'] ?? [];

    if (!empty($ids)) {
        // PDO không hỗ trợ bind cả chuỗi "1,2,3" vào IN(:ids)
        // Phải tạo placeholder riêng cho mỗi ID
        $ids = array_map('intval', $ids); // Ép kiểu int để an toàn
        $placeholders = [];
        $userId = getSession('id');
        $params = ['user_id' => $userId];
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

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Đếm lại tổng số record sau khi xóa
$where = getSession("filter_where") ?? "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0";
$params = getSession("filter_params") ?? ['user_id' => getSession('id')];
$sqlCount = "SELECT COUNT(*) as cnt FROM transaction JOIN category ON category.id = transaction.category_id $where";
$countResult = getOne($sqlCount, $params);
$totalResults = $countResult ? (int)$countResult['cnt'] : 0;

$limit = 5;
$totalPages = ceil($totalResults / $limit);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;

redirect("?template=user&action=filter&page=" . $page);