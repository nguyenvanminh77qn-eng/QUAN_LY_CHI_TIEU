<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

if (!isset($_POST['quick_add_btn'])) {
    redirect('?template=user&action=dashboard');
}

$filterAll = filter();
$rawText = $filterAll['quick_text'] ?? '';
if (empty($rawText)) {
    setMessage("Vui lòng nhập nội dung", "error");
    redirect('?template=user&action=dashboard');
}

$id = getSession('id');
$price = 0;
$type = 'expense';
$description = $rawText;

// 1. Trích xuất giá tiền (Regex)
// Hỗ trợ: 50k, 1.5tr, 2 triệu, 100000, 50 đ, 50 vnd
$pattern = '/(\d+(?:[.,]\d+)?)\s*(k|tr|triệu|đ|vnd|nghìn|ngàn)?/i';
if (preg_match_all($pattern, $rawText, $matches, PREG_SET_ORDER)) {
    $bestMatch = null;
    foreach ($matches as $match) {
        $numStr = str_replace(',', '.', $match[1]); // Normalize decimal
        $num = (float)$numStr;
        $unit = strtolower($match[2] ?? '');
        
        if ($unit === 'k' || $unit === 'nghìn' || $unit === 'ngàn') {
            $num *= 1000;
        } elseif ($unit === 'tr' || $unit === 'triệu') {
            $num *= 1000000;
        }
        
        $bestMatch = $num;
    }
    
    if ($bestMatch) {
        $price = $bestMatch;
    }
}

if ($price <= 0) {
    setMessage("Không nhận diện được số tiền hợp lệ trong câu của bạn.", "error");
    redirect('?template=user&action=dashboard');
}

// 2. Trích xuất Loại giao dịch (Thu/Chi)
$incomeKeywords = ['lương', 'thu nhập', 'nhận', 'thưởng', 'bán', 'lãi', 'cho', 'cấp'];
$lowerText = mb_strtolower($rawText, 'UTF-8');
foreach ($incomeKeywords as $kw) {
    if (mb_strpos($lowerText, $kw, 0, 'UTF-8') !== false) {
        $type = 'income';
        break;
    }
}

// 3. Trích xuất Danh mục
$categories = getAll("SELECT id, name FROM category");
$foundCategory = null;

// Khớp theo tên danh mục trực tiếp
foreach ($categories as $cat) {
    if (mb_strpos($lowerText, mb_strtolower($cat['name'], 'UTF-8'), 0, 'UTF-8') !== false) {
        $foundCategory = $cat['id'];
        break;
    }
}

// Khớp theo từ khóa phụ
if (!$foundCategory) {
    $keywordMap = [
        'cafe' => 'Ăn uống', 'cà phê' => 'Ăn uống', 'phở' => 'Ăn uống', 'cơm' => 'Ăn uống', 'ăn' => 'Ăn uống', 'uống' => 'Ăn uống', 'nhậu' => 'Ăn uống',
        'xăng' => 'Di chuyển', 'grab' => 'Di chuyển', 'taxi' => 'Di chuyển', 'xe' => 'Di chuyển', 'vé' => 'Di chuyển',
        'phim' => 'Giải trí', 'game' => 'Giải trí', 'chơi' => 'Giải trí', 'netflix' => 'Giải trí',
        'điện' => 'Nhà cửa', 'nước' => 'Nhà cửa', 'nhà' => 'Nhà cửa', 'phòng' => 'Nhà cửa',
        'áo' => 'Mua sắm', 'quần' => 'Mua sắm', 'giày' => 'Mua sắm', 'túi' => 'Mua sắm', 'shopee' => 'Mua sắm'
    ];
    
    foreach ($keywordMap as $kw => $catName) {
        if (mb_strpos($lowerText, $kw, 0, 'UTF-8') !== false) {
            foreach ($categories as $cat) {
                if (mb_strtolower($cat['name'], 'UTF-8') === mb_strtolower($catName, 'UTF-8')) {
                    $foundCategory = $cat['id'];
                    break 2;
                }
            }
        }
    }
}

// Mặc định danh mục 'Khác' nếu không tìm thấy
if (!$foundCategory) {
    $foundOtherCat = false;
    foreach ($categories as $cat) {
        if (mb_strtolower($cat['name'], 'UTF-8') === 'khác') {
            $foundCategory = $cat['id'];
            $foundOtherCat = true;
            break;
        }
    }
    
    // Nếu chưa có danh mục 'Khác' trong cơ sở dữ liệu thì thêm vào
    if (!$foundOtherCat) {
        $insertOtherCat = insert("category", [
            'name' => 'Khác',
            'icon' => '🏷️'
        ]);
        if ($insertOtherCat) {
            $otherCat = getOne("SELECT id FROM category WHERE name = 'Khác' LIMIT 1");
            if ($otherCat) {
                $foundCategory = $otherCat['id'];
                $categories[] = ['id' => $foundCategory, 'name' => 'Khác'];
            }
        }
    }
}

if (!$foundCategory) {
    setMessage("Lỗi: Hệ thống chưa có danh mục nào và không thể tạo danh mục Khác.", "error");
    redirect('?template=user&action=dashboard');
}

// 4. Lưu vào CSDL
$date = date('Y-m-d');
$create_at = date("Y-m-d H:i:s");
$dataInsert = [
    'user_id' => $id,
    'category_id' => (int) $foundCategory,
    'price' => (float) $price,
    'description' => ucfirst($description),
    'transaction_date' => $date,
    'type' => $type,
    'create_at' => $create_at
];

$insertQuery = insert("transaction", $dataInsert);
if ($insertQuery) {
    $catName = "Mặc định";
    foreach ($categories as $cat) {
        if ($cat['id'] == $foundCategory) {
            $catName = $cat['name'];
            break;
        }
    }
    $typeStr = $type === 'income' ? 'Thu nhập' : 'Chi tiêu';
    setMessage("🤖 AI nhận diện thành công: $typeStr " . number_format($price, 0, ',', '.') . "đ vào mục '$catName'", "success");
} else {
    setMessage('Lỗi hệ thống, vui lòng thử lại sau', 'error');
}

redirect('?template=user&action=dashboard');
?>
