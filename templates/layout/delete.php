<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Xóa chi tiêu",
        "css" => ["layout/sidebar"]
    ]);
?>
<div class="card-box" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h3 style="margin-bottom: 8px; font-size: 16px; color: var(--negative);">🗑️ Xóa chi tiêu</h3>
            <p style="color: var(--text-muted); font-size: 14px;">Chọn các khoản giao dịch ở bảng bên dưới và nhấn nút xác nhận xóa.</p>
        </div>
        
        <button class="btn-danger">Xóa các mục đã chọn</button>
    </div>
</div>

<div class="card-box">
    <h3 style="margin-bottom: 15px; font-size: 16px; color: var(--text-main);">Danh sách giao dịch</h3>
    
    <table class="data-table">
        <tr>
            <th>NGÀY</th>
            <th>DANH MỤC</th>
            <th>MÔ TẢ</th>
            <th>SỐ TIỀN</th>
            <th style="text-align: right; white-space: nowrap; padding-right: 15px;">
            <label for="checkAll" style="cursor: pointer; margin-right: 8px; font-size: 13px;">Chọn tất cả</label>
            <input type="checkbox" id="checkAll"> 
        </th>
        </tr>
        
        <tr>
            <td>2026-10-24</td>
            <td>🍽️ Ăn uống</td>
            <td>Ăn trưa văn phòng</td>
            <td class="negative">-85.000 đ</td>
            <td style="text-align: center;">
                <input type="checkbox" class="checkItem" value="1">
            </td>
        </tr>

        <tr>
            <td>2026-10-22</td>
            <td>💰 Thu nhập</td>
            <td>Thanh toán dự án</td>
            <td class="positive">+5.000.000 đ</td>
            <td style="text-align: center;">
                <input type="checkbox" class="checkItem" value="2">
            </td>
        </tr>
        
        <tr>
            <td>2026-10-20</td>
            <td>🛍️ Mua sắm</td>
            <td>Mua quần áo</td>
            <td class="negative">-450.000 đ</td>
            <td style="text-align: center;">
                <input type="checkbox" class="checkItem" value="3">
            </td>
        </tr>
    </table>
</div>
<?php
    layout("footer", ["js" => ["pages/sidebar"]] );
?>