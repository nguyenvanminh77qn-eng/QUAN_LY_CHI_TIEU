<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Lọc chi tiêu",
        "css" => ["layout/sidebar"]
    ]);
?>
<?php
// DỮ LIỆU MẪU
$danh_sach_chi_tieu = [
    ['ngay' => '2026-10-24', 'icon' => '🍽️', 'danh_muc' => 'Ăn uống', 'mo_ta' => 'Ăn trưa văn phòng', 'so_tien' => -85000, 'loai' => 'chi_tieu'],
    ['ngay' => '2026-10-22', 'icon' => '💰', 'danh_muc' => 'Thu nhập', 'mo_ta' => 'Thanh toán dự án', 'so_tien' => 5000000, 'loai' => 'thu_nhap'],
    ['ngay' => '2026-10-20', 'icon' => '🛍️', 'danh_muc' => 'Mua sắm', 'mo_ta' => 'Mua quần áo', 'so_tien' => -450000, 'loai' => 'chi_tieu'],
    ['ngay' => '2026-10-18', 'icon' => '🛵', 'danh_muc' => 'Di chuyển', 'mo_ta' => 'Đổ xăng', 'so_tien' => -60000, 'loai' => 'chi_tieu']
];

// NHẬN DỮ LIỆU TỪ FORM LỌC
$tu_ngay  = $_GET['tu_ngay'] ?? '';
$den_ngay = $_GET['den_ngay'] ?? '';
$loai     = $_GET['loai'] ?? 'tat_ca';
$danh_muc = $_GET['danh_muc'] ?? 'tat_ca';

// XỬ LÝ LỌC DỮ LIỆU
$ket_qua_loc = []; 

foreach ($danh_sach_chi_tieu as $item) {
    $thoa_man_dieu_kien = true; 
    
    if ($tu_ngay != '' && $item['ngay'] < $tu_ngay) {
        $thoa_man_dieu_kien = false;
    }
    if ($den_ngay != '' && $item['ngay'] > $den_ngay) {
        $thoa_man_dieu_kien = false;
    }
    if ($loai != 'tat_ca' && $item['loai'] != $loai) {
        $thoa_man_dieu_kien = false;
    }
    if ($danh_muc != 'tat_ca' && $item['danh_muc'] != $danh_muc) {
        $thoa_man_dieu_kien = false;
    }
    
    if ($thoa_man_dieu_kien == true) {
        $ket_qua_loc[] = $item;
    }
}
?>
</head>
<body>
    <div class="app-layout">
        

        <main class="main-content">
            

            <div class="card">
                <h3>Bộ lọc tìm kiếm</h3>
                <form action="index.php" method="GET" class="filter-form">
                    
                    <input type="date" name="tu_ngay" value="<?= htmlspecialchars($tu_ngay) ?>" title="Từ ngày">
                    <input type="date" name="den_ngay" value="<?= htmlspecialchars($den_ngay) ?>" title="Đến ngày">
                    
                    <select name="loai">
                        <option value="tat_ca" <?= $loai == 'tat_ca' ? 'selected' : '' ?>>Tất cả (Thu/Chi)</option>
                        <option value="thu_nhap" <?= $loai == 'thu_nhap' ? 'selected' : '' ?>>Thu nhập (+)</option>
                        <option value="chi_tieu" <?= $loai == 'chi_tieu' ? 'selected' : '' ?>>Chi tiêu (-)</option>
                    </select>

                    <select name="danh_muc">
                        <option value="tat_ca" <?= $danh_muc == 'tat_ca' ? 'selected' : '' ?>>Tất cả danh mục</option>
                        <option value="Ăn uống" <?= $danh_muc == 'Ăn uống' ? 'selected' : '' ?>>Ăn uống</option>
                        <option value="Mua sắm" <?= $danh_muc == 'Mua sắm' ? 'selected' : '' ?>>Mua sắm</option>
                        <option value="Thu nhập" <?= $danh_muc == 'Thu nhập' ? 'selected' : '' ?>>Thu nhập</option>
                        <option value="Di chuyển" <?= $danh_muc == 'Di chuyển' ? 'selected' : '' ?>>Di chuyển</option>
                    </select>

                    <a href="index.php" class="btn-reset">Xóa lọc</a>
                    <button type="submit" class="btn-submit">Áp dụng</button>
                </form>
            </div>

            <div class="card">
                <h3>Kết quả</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Danh mục</th>
                            <th>Mô tả</th>
                            <th>Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ket_qua_loc) == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">Không có giao dịch nào phù hợp.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ket_qua_loc as $item): ?>
                                <tr>
                                    <td><?= $item['ngay'] ?></td>
                                    <td><?= $item['icon'] . ' ' . $item['danh_muc'] ?></td>
                                    <td><?= $item['mo_ta'] ?></td>
                                    
                                    <?php if ($item['loai'] == 'thu_nhap'): ?>
                                        <td class="text-income">+<?= number_format($item['so_tien'], 0, ',', '.') ?> đ</td>
                                    <?php else: ?>
                                        <td class="text-expense"><?= number_format($item['so_tien'], 0, ',', '.') ?> đ</td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>
</body>
</html>
<?php
    layout("footer", ["js" => ["pages/sidebar"]] );
?>