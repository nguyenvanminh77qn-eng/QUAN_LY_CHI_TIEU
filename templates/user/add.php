<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');
layout("header", [
    "title" => "Thêm Chi Tiêu",
    "css" => ["layout/sidebar", "pages/user/add"]]);
$view = 'add';
$data = getAll("SELECT * FROM category ORDER BY id");
$loginToken = getSession('loginToken');
if(empty($loginToken)){
    setMessage("Bạn phải đăng nhập","error");
    redirect("?template=auth&action=login.view");
}
$username = getSession('username');


$message = getFlashData("message");
$message_type = getFlashData("message_type");
$errors = getFlashData("errors");

?>
<div class="app-container">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">DIGITAL CURATOR</span>
                    <h1>Thêm Chi Tiêu</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <main class="expense-card">
                <header class="expense-card__header">
                    <span class="expense-card__tag">GIAO DỊCH MỚI</span>
                    <h1 class="expense-card__title">Thêm Chi Tiêu</h1>
                </header>

                <?php if (!empty($message)) : ?>
                    <?php echo showMessage($message, $message_type); ?>
                <?php endif; ?>

                <form id="expenseForm" class="expense-form" method="POST" action="?template=user&action=add">
                    <div class="expense-form__group expense-form__group--sm">
                        <label class="expense-form__label">SỐ TIỀN</label>
                        <div class="expense-form__input-wrapper">
                            <input type="number" id="amount" class="expense-form__input" placeholder="0" name="price" >
                            <span class="expense-form__unit">VND</span>
                        </div>
                        <?= form_error($errors, 'price') ?>
                    </div>

                    <div class="expense-form__group expense-form__group--sm">
                        <label class="expense-form__label" for="type">LOẠI GIAO DỊCH</label>
                        <select id="type" class="expense-form__input" name="type" >
                            <option value="income">Thu</option>
                            <option value="expense" selected>Chi</option>
                        </select>
                    </div>

                    <div class="expense-form__group expense-form__group--md">
                        <label class="expense-form__label">DANH MỤC</label>
                        <select id="category" class="expense-form__input" name="category" >
                            <option value="" disabled selected>Chọn loại chi tiêu</option>
                            <?php if(!empty($data)) : ?>
                                <?php foreach($data as $item) : ?>
                                    <option value ="<?= $item['id'] ?>"><?= $item['icon'] ?? '📦' ?> <?= htmlspecialchars($item['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?= form_error($errors, 'category') ?>
                    </div>

                    <div class="expense-form__group expense-form__group--sm">
                        <label class="expense-form__label">NGÀY</label>
                        <input type="date" id="date" class="expense-form__input" name="transaction_date">
                        <?= form_error($errors, 'transactionDate') ?>
                    </div>

                    <div class="expense-form__group expense-form__group--grow">
                        <label class="expense-form__label">GHI CHÚ</label>
                        <input type="text" id="description" class="expense-form__input" placeholder="Nhập mô tả ngắn..." name="description">
                    </div>

                    <div class="expense-form__actions">
                        <button type="submit" class="btn btn--primary" name="add_btn">THÊM</button>
                        <button type="button" id="btnReset" class="btn btn--ghost" name="cancel_btn">HỦY</button>
                    </div>
                    <input type="text" style="display:none" name="id" value=>
                </form>
            </main>
        </div>
    </main>
</div>
<?php
layout("footer", ["js" => ["pages/sidebar"]]);