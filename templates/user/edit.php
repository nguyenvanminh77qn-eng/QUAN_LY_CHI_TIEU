<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
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
if (empty($loginToken)) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}

$username = getSession('username');
$message = getFlashData("message");
$messageType = getFlashData("message_type");
$errors = getFlashData("errors");
$suspiciousWarning = getFlashData("suspicious_warning");
$suspiciousFormData = getFlashData("suspicious_form_data");
$oldData = getFlashData("old_data");
$formData = $suspiciousFormData ?? $oldData ?? $transaction;

$topLevelErrors = [];
if (!empty($errors)) {
    if (!empty($errors['balance'])) {
        $topLevelErrors[] = $errors['balance'];
    }
    if (!empty($errors['limit'])) {
        $topLevelErrors[] = $errors['limit'];
    }
    if (!empty($errors['rate_limit'])) {
        $topLevelErrors[] = $errors['rate_limit'];
    }
    if (!empty($errors['budget'])) {
        $topLevelErrors[] = $errors['budget'];
    }
    if (!empty($errors['monthly_budget'])) {
        $topLevelErrors[] = $errors['monthly_budget'];
    }
}
?>
<div class="app-container">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">DIGITAL CURATOR</span>
                    <h1>Cập Nhật Chi Tiêu</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <main class="expense-card">
                <header class="expense-card__header">
                    <span class="expense-card__tag expense-card__tag--edit">CHỈNH SỬA</span>
                    <h1 class="expense-card__title">Sửa Giao Dịch</h1>
                </header>

                <?php if (!empty($message)): ?>
                    <?= showMessage($message, $messageType) ?>
                <?php endif; ?>

                <form id="expenseForm" class="expense-form" method="POST" action="?template=user&action=edit">
                    <input type="hidden" name="id" value="<?= $transaction['id'] ?>">

                    <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div class="expense-form__group" style="flex: 2; min-width: 180px;">
                            <label class="expense-form__label">SỐ TIỀN</label>
                            <div class="expense-form__input-wrapper">
                                <input type="number" id="amount" class="expense-form__input" placeholder="Nhập số tiền..." name="price" value="<?= htmlspecialchars($formData['price'] ?? '') ?>" required>
                                <span class="expense-form__unit">VND</span>
                            </div>
                            <?php if (!empty($errors['price'])) echo "<span class='edit-error'>" . $errors['price'][key($errors['price'])] . "</span>"; ?>
                        </div>

                        <div class="expense-form__group" style="flex: 1; min-width: 120px;">
                            <label class="expense-form__label" for="type">LOẠI GIAO DỊCH</label>
                            <select id="type" class="expense-form__input" name="type">
                                <option value="income" <?= (!empty($formData['type']) && $formData['type'] == 'income') ? 'selected' : '' ?>>Thu</option>
                                <option value="expense" <?= (empty($formData['type']) || $formData['type'] == 'expense') ? 'selected' : '' ?>>Chi</option>
                            </select>
                        </div>

                        <div class="expense-form__group" style="flex: 2; min-width: 180px;">
                            <label class="expense-form__label">DANH MỤC</label>
                            <select id="category" class="expense-form__input" name="category" required>
                                <option value="" disabled>Chọn loại chi tiêu</option>
                                <?php if (!empty($data)): ?>
                                    <?php foreach ($data as $item): ?>
                                        <option value="<?= $item['id'] ?>" <?= (!empty($formData['category_id']) && $formData['category_id'] == $item['id']) ? 'selected' : '' ?>>
                                            <?= $item['icon'] ?? '📦' ?> <?= htmlspecialchars($item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (!empty($errors['category'])) echo "<span class='edit-error'>" . $errors['category'][key($errors['category'])] . "</span>"; ?>
                        </div>

                        <div class="expense-form__group" style="flex: 1.5; min-width: 150px;">
                            <label class="expense-form__label">NGÀY</label>
                            <input type="date" id="date" class="expense-form__input" name="transaction_date" value="<?= htmlspecialchars($formData['transaction_date'] ?? '') ?>" required>
                            <?php if (!empty($errors['transactionDate'])) echo "<span class='edit-error'>" . $errors['transactionDate'][key($errors['transactionDate'])] . "</span>"; ?>
                        </div>
                    </div>

                    <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div class="expense-form__group" style="flex: 1; min-width: 250px;">
                            <label class="expense-form__label">MÔ TẢ</label>
                            <input type="text" id="description" class="expense-form__input" placeholder="Nhập mô tả ngắn..." name="description" value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
                            <?php if (!empty($errors['description'])) echo "<span class='edit-error'>" . $errors['description'][key($errors['description'])] . "</span>"; ?>
                        </div>
                    </div>

                    <div class="expense-form__actions" style="margin-top: 20px; justify-content: flex-end; width: 100%; display: flex; gap: 10px;">
                        <a href="?template=user&action=filter" class="btn btn--ghost btn-edit-cancel" style="padding: 12px 30px; text-decoration: none; display: inline-block; text-align: center;">HỦY</a>
                        <button type="submit" class="btn btn--primary btn-edit-submit" id="btnSubmitAdd" name="edit_btn" style="padding: 12px 40px; background: linear-gradient(135deg, #0f4a7b, #1b6ca8); color: white; border: none; border-radius: 8px; cursor: pointer;">CẬP NHẬT</button>
                    </div>
                </form>
            </main>
        </div>
    </main>
</div>

<?php if (!empty($suspiciousWarning) && !empty($suspiciousFormData)): ?>
    <div id="suspiciousModal" class="suspicious-modal-overlay">
        <div class="suspicious-modal-content">
            <h3 class="suspicious-modal-header">Giao dịch cần xác nhận</h3>

            <div class="suspicious-modal-reasons">
                <?php if (!empty($suspiciousWarning['suspicious'])): ?>
                    <?php foreach ($suspiciousWarning['suspicious'] as $reason): ?>
                        <p class="suspicious-reason-item"><?= htmlspecialchars($reason) ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="suspicious-modal-details">
                <p class="suspicious-detail-item"><strong>Loại:</strong> <?= $suspiciousFormData['type'] === 'income' ? 'Thu nhập' : 'Chi tiêu' ?></p>
                <p class="suspicious-detail-item"><strong>Số tiền:</strong> <?= number_format($suspiciousFormData['price'], 0, ',', '.') ?>đ</p>
                <p class="suspicious-detail-item"><strong>Ngày:</strong> <?= htmlspecialchars($suspiciousFormData['transaction_date']) ?></p>
                <?php if (!empty($suspiciousFormData['description'])): ?>
                    <p class="suspicious-detail-item"><strong>Mô tả:</strong> <?= htmlspecialchars($suspiciousFormData['description']) ?></p>
                <?php endif; ?>
            </div>

            <div class="suspicious-modal-actions">
                <form action="?template=user&action=edit" method="POST" class="suspicious-modal-form">
                    <input type="hidden" name="id" value="<?= $transaction['id'] ?>">
                    <input type="hidden" name="confirm_suspicious" value="1">
                    <input type="hidden" name="price" value="<?= htmlspecialchars($suspiciousFormData['price']) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($suspiciousFormData['type']) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($suspiciousFormData['category_id']) ?>">
                    <input type="hidden" name="transaction_date" value="<?= htmlspecialchars($suspiciousFormData['transaction_date']) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($suspiciousFormData['description'] ?? '') ?>">
                    <button type="submit" name="edit_btn" class="suspicious-modal-button confirm">Xác nhận cập nhật</button>
                </form>
                <button onclick="document.getElementById('suspiciousModal').style.display='none'" class="suspicious-modal-button cancel">Hủy bỏ</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($topLevelErrors)): ?>
    <div id="errorModal" class="suspicious-modal-overlay">
        <div class="suspicious-modal-content" style="text-align: center;">
            <h3 class="suspicious-modal-header" style="color: #c62828; margin-bottom: 20px;">Không thể cập nhật giao dịch</h3>

            <div class="suspicious-modal-reasons" style="background: #ffebee; border: 1px solid #ef9a9a;">
                <?php foreach ($topLevelErrors as $err): ?>
                    <p class="suspicious-reason-item" style="color: #b71c1c; font-weight: 500; font-size: 15px;"><?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
            </div>

            <div class="suspicious-modal-actions" style="margin-top: 25px;">
                <button type="button" onclick="document.getElementById('errorModal').style.display='none'" class="suspicious-modal-button cancel" style="background: #e0e0e0; color: #333; font-weight: bold; border: none; padding: 12px 30px;">Đã hiểu</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
layout("footer", ["js" => ["pages/sidebar"]]);
