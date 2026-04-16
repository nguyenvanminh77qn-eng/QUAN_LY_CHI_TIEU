<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    layout("header", [
        "title" => "Thêm Chi Tiêu",
        "css" => ["layout/sidebar"]
    ]);
?>

<div class="page-container" style="display: flex; justify-content: center; padding-top: 40px;">
    <div class="edit-expense-card">
        <h2 class="edit-card-title">Sửa chi tiêu</h2>

        <form id="editExpenseForm" class="edit-form">
            
            <div class="edit-form-group">
                <label class="edit-form-label">Ngày</label>
                <input type="date" class="edit-form-input" value="2023-11-15" required>
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Số tiền</label>
                <input type="number" class="edit-form-input" value="1250000" required>
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Danh mục</label>
                <div class="edit-radio-group">
                    <label class="radio-label">
                        <input type="radio" name="category" value="food"> Ăn uống
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="category" value="transport"> Di chuyển
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="category" value="shopping"> Mua sắm
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="category" value="salary" checked> Lương
                    </label>
                </div>
            </div>

            <div class="edit-form-group">
                <label class="edit-form-label">Mô tả</label>
                <textarea class="edit-form-textarea" rows="3">Bữa tối thứ 6 với gia đình tại nhà hàng Sen Tây Hồ.</textarea>
            </div>

            <div class="edit-form-actions">
                <button type="button" class="btn-cancel" onclick="window.location.href='?template=layout&action=sidebar&view=dashboard'">Hủy</button>
                <button type="submit" class="btn-save">Lưu</button>
            </div>
        </form>
    </div>
</div>
<?php
    layout("footer", [ "js" => ["pages/sidebar"] ]);
?>