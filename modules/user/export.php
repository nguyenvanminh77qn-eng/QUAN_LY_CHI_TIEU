<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if(isset($_POST['btn-export-csv'])){

    $filterAll = filter();
    $id = $filterAll['id'];

    $sql = "SELECT transaction_date, category.name as category_name, description, price, type 
            FROM transaction 
            JOIN category ON category.id = transaction.category_id 
            WHERE user_id = :user_id
            ORDER BY transaction_date DESC, transaction.id DESC";

    $transactions = getAll($sql, ['user_id' => $id]);

    if(empty($transactions)){
        setMessage("Không có dữ liệu", "error");
        redirect("?template=user&action=export");
    }

    $filename = "transactions_" . date('Ymd_His') . ".csv";

    // Xóa bộ đệm đầu ra để đảm bảo không có khoảng trắng thừa
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');

    $output = fopen('php://output', 'w');

    // ✅ UTF-8 BOM (Byte Order Mark) cực kỳ quan trọng cho Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header có dấu tiếng Việt
    fputcsv($output, ['Ngày', 'Loại', 'Danh mục', 'Mô tả', 'Số tiền']);

    foreach($transactions as $row){
        $typeName = ($row['type'] == 'income') ? 'Thu nhập' : 'Chi tiêu';

        fputcsv($output, [
            $row['transaction_date'],
            $typeName,
            $row['category_name'],
            $row['description'],
            number_format($row['price'], 0, '', '') // Đảm bảo số tiền không có dấu phân cách nghìn gây lỗi CSV
        ]);
    }

    fclose($output);
    exit();
}

redirect("?template=user&action=export");
?>