<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    layout("header", [
        "title" => "Thêm Chi Tiêu",
        "css" => ["layout/sidebar"]
    ]);
?>
<div class="page-container">
    <main class="expense-card">
        <header class="expense-card__header">
            <span class="expense-card__tag">GIAO DỊCH MỚI</span>
            <h1 class="expense-card__title">Thêm Chi Tiêu</h1>
        </header>

        <form id="expenseForm" class="expense-form">
            <div class="expense-form__group expense-form__group--sm">
                <label class="expense-form__label">SỐ TIỀN</label>
                <div class="expense-form__input-wrapper">
                    <input type="number" id="amount" class="expense-form__input" placeholder="0" required>
                    <span class="expense-form__unit">VND</span>
                </div>
            </div>

            <div class="expense-form__group expense-form__group--md">
                <label class="expense-form__label">DANH MỤC</label>
                <select id="category" class="expense-form__input" required>
                    <option value="" disabled selected>Chọn loại chi tiêu</option>
                    <option value="eating">Ăn uống</option>
                    <option value="transport">Di chuyển</option>
                    <option value="shopping">Mua sắm</option>
                    <option value="entertainment">Giải trí</option>
                </select>
            </div>

            <div class="expense-form__group expense-form__group--sm">
                <label class="expense-form__label">NGÀY</label>
                <input type="date" id="date" class="expense-form__input">
            </div>

            <div class="expense-form__group expense-form__group--grow">
                <label class="expense-form__label">GHI CHÚ</label>
                <input type="text" id="description" class="expense-form__input" placeholder="Nhập mô tả ngắn...">
            </div>

            <div class="expense-form__actions">
                <button type="submit" class="btn btn--primary">THÊM</button>
                <button type="button" id="btnReset" class="btn btn--ghost">HỦY</button>
            </div>
        </form>
    </main>
</div>
<?php
    layout("footer", [ "js" => ["pages/sidebar"] ]);
?>