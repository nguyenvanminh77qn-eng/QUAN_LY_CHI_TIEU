<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = getSession('id');

if ($id <= 0) {
    redirect("?template=user&action=filter");
}

$transaction = getOne("SELECT * FROM transaction WHERE id = :id AND user_id = :user_id", ['id' => $id, 'user_id' => $userId]);

if (!$transaction) {
    setFlashData('message', 'Không tìm thấy giao dịch này.');
    setFlashData('message_type', 'error');
    redirect("?template=user&action=filter");
}

$data = getAll("SELECT * FROM category ORDER BY name");

layout("header", [
    "title" => "Sửa Chi Tiêu",
    "css" => ["layout/sidebar", "pages/user/add", "pages/user/edit"] 
]);
$view = 'filter'; 

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
                    <h1>Cập nhật Chi Tiêu</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username)?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <main class="expense-card">
                <header class="expense-card__header">
                    <span class="expense-card__tag expense-card__tag--edit">CHỈNH SỬA</span>
                    <h1 class="expense-card__title">Sửa Chi Tiêu</h1>
                </header>

                <?php if (!empty($message)) : ?>
                    <?php echo showMessage($message, $message_type); ?>
                <?php endif; ?>

                <form id="expenseForm" class="expense-form" method="POST" action="?template=user&action=edit">
                    <input type="hidden" name="id" value="<?= $transaction['id'] ?>">
                    
                    <div class="expense-form__group expense-form__group--sm">
                        <label class="expense-form__label">SỐ TIỀN</label>
                        <div class="expense-form__input-wrapper">
                            <input type="number" id="amount" class="expense-form__input" placeholder="0" name="price" value="<?= number_format($transaction['price'], 0, ',', '.') ?>">
                            <span class="expense-form__unit">VND</span>
                        </div>
                        <?php if(!empty($errors['price'])) echo "<span class='edit-error'>".$errors['price'][key($errors['price'])]."</span>"; ?>
                    </div>

                    <div class="expense-form__group expense-form__group--sm">
                        <label class="expense-form__label" for="type">LOẠI GIAO DỊCH</label>
                        <select id="type" class="expense-form__input" name="type">
                            <option value="income" <?= $transaction['type'] == 'income' ? 'selected' : '' ?>>Thu</option>
                            <option value="expense" <?= $transaction['type'] == 'expense' ? 'selected' : '' ?>>Chi</option>
                        </select>
                    </div>

                    <div class="expense-form__group expense-form__group--md">
                        <label class="expense-form__label">DANH MỤC</label>
                        <select id="category" class="expense-form__input" name="category">
                            <option value="" disabled>Chọn loại chi tiêu</option>
                            <?php if(!empty($data)) : ?>
                                <?php foreach($data as $item) : ?>
                                    <option value ="<?= $item['id'] ?>" <?= $transaction['category_id'] == $item['id'] ? 'selected' : '' ?>><?= $item['icon'] ?? '📦' ?> <?= htmlspecialchars($item['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if(!empty($errors['category'])) echo "<span class='edit-error'>".$errors['category'][key($errors['category'])]."</span>"; ?>
                    </div>

                    <div class="expense-form__group expense-form__group--sm">
                        <label class="expense-form__label">NGÀY</label>
                        <input type="date" id="date" class="expense-form__input" name="transaction_date" value="<?= date('Y-m-d', strtotime($transaction['transaction_date'])) ?>">
                        <?php if(!empty($errors['transactionDate'])) echo "<span class='edit-error'>".$errors['transactionDate'][key($errors['transactionDate'])]."</span>"; ?>
                    </div>

                    <div class="expense-form__group expense-form__group--grow">
                        <label class="expense-form__label">GHI CHÚ</label>
                        <input type="text" id="description" class="expense-form__input" placeholder="Nhập mô tả ngắn..." name="description" value="<?= htmlspecialchars($transaction['description']) ?>">
                    </div>

                    <div class="expense-form__actions">
                        <button type="submit" class="btn btn--primary btn-edit-submit" name="edit_btn">CẬP NHẬT</button>
                        <a href="?template=user&action=filter" class="btn btn--ghost btn-edit-cancel">HỦY</a>
                    </div>
                </form>
            </main>
        </div>
    </main>
</div>
<?php
layout("footer", ["js" => ["pages/sidebar"]]);