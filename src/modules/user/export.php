<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

if (isset($_POST['btn-export-csv'])) {
    $filterAll = filter();
    $userId = getSession('id');

    $where = "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0";
    $params = ['user_id' => $userId];

    $dateFrom = trim($filterAll['date_from'] ?? '');
    $dateTo   = trim($filterAll['date_to'] ?? '');
    $type     = trim($filterAll['type'] ?? '');
    $catId    = trim($filterAll['category_id'] ?? '');

    if ($dateFrom !== '') {
        $where .= " AND transaction_date >= :date_from";
        $params['date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $where .= " AND transaction_date <= :date_to";
        $params['date_to'] = $dateTo;
    }
    if ($type !== '') {
        $where .= " AND transaction.type = :type";
        $params['type'] = $type;
    }
    if ($catId !== '' && ctype_digit($catId)) {
        $where .= " AND transaction.category_id = :category_id";
        $params['category_id'] = (int)$catId;
    }

    $sql = "SELECT transaction_date, category.name as category_name, description, price, type
            FROM transaction
            JOIN category ON category.id = transaction.category_id
            $where
            ORDER BY transaction_date DESC, transaction.id DESC";

    $transactions = getAll($sql, $params);

    if (empty($transactions)) {
        setMessage("Không có dữ liệu phù hợp với bộ lọc đã chọn", "error");
        redirect("?template=user&action=export");
    }

    $filename = "transactions_" . date('Ymd_His') . ".csv";

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM cho Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, ['Ngày', 'Loại', 'Danh mục', 'Mô tả', 'Số tiền (đ)']);

    foreach ($transactions as $row) {
        $typeName = ($row['type'] == 'income') ? 'Thu nhập' : 'Chi tiêu';
        fputcsv($output, [
            $row['transaction_date'],
            $typeName,
            $row['category_name'],
            $row['description'] ?? '',
            number_format($row['price'], 0, '', ''),
        ]);
    }

    fclose($output);
    exit();
}

redirect("?template=user&action=export");
