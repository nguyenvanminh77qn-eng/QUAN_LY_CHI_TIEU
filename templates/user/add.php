<?php
if (!CODE) {
    die('Bạn không có quyền truy cập vào trang này');
}

layout("header", [
    "title" => "Thêm Chi Tiêu",
    "css" => ["layout/sidebar", "pages/user/add"]
]);
$view = 'add';
$data = getAll("SELECT * FROM category ORDER BY id");
$loginToken = getSession('loginToken');
if (empty($loginToken)) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}

$username = getSession('username');
$errors = getFlashData("errors");
$suspiciousWarning = getFlashData("suspicious_warning");
$suspiciousFormData = getFlashData("suspicious_form_data");
$oldData = getFlashData("old_data");
$formData = $suspiciousFormData ?? $oldData ?? [];

$message = getFlashData("message");
$messageType = getFlashData("message_type");

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
                    <h1>Thêm Chi Tiêu</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content" style="padding-top: 20px;">
            <?php if (!empty($message)) echo showMessage($message, $messageType); ?>

            <main class="expense-card">
                <header class="expense-card__header">
                    <span class="expense-card__tag">GIAO DỊCH MỚI</span>
                    <h1 class="expense-card__title">Thêm Giao Dịch</h1>
                </header>

                <form id="expenseForm" class="expense-form" method="POST" action="?template=user&action=add">
                    <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div class="expense-form__group" style="flex: 2; min-width: 180px;">
                            <label class="expense-form__label">SỐ TIỀN</label>
                            <div class="expense-form__input-wrapper">
                                <input type="number" id="amount" class="expense-form__input" placeholder="Nhập số tiền..." name="price" value="<?= htmlspecialchars($formData['price'] ?? '') ?>" required>
                                <span class="expense-form__unit">VND</span>
                            </div>
                            <?= form_error($errors, 'price') ?>
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
                                <option value="" disabled <?= empty($formData['category_id']) ? 'selected' : '' ?>>Chọn danh mục</option>
                                <?php if (!empty($data)): ?>
                                    <?php foreach ($data as $item): ?>
                                        <option value="<?= $item['id'] ?>" <?= (!empty($formData['category_id']) && $formData['category_id'] == $item['id']) ? 'selected' : '' ?>>
                                            <?= $item['icon'] ?? '📦' ?> <?= htmlspecialchars($item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?= form_error($errors, 'category') ?>
                        </div>

                        <div class="expense-form__group" style="flex: 1.5; min-width: 150px;">
                            <label class="expense-form__label">NGÀY</label>
                            <input type="date" id="date" class="expense-form__input" name="transaction_date" value="<?= htmlspecialchars($formData['transaction_date'] ?? '') ?>" required>
                            <?= form_error($errors, 'transactionDate') ?>
                        </div>
                    </div>

                    <div class="form-row" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <div class="expense-form__group" style="flex: 1; min-width: 250px;">
                            <label class="expense-form__label">MÔ TẢ</label>
                            <input type="text" id="description" class="expense-form__input" placeholder="Nhập mô tả ngắn..." name="description" value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
                            <?= form_error($errors, 'description') ?>
                        </div>
                    </div>

                    <div class="expense-form__actions" style="margin-top: 20px; width: 100%; display: flex; gap: 10px; align-items: center;">
                        <button type="submit" class="btn btn--primary" id="btnSubmitAdd" name="add_btn" style="padding: 12px 40px; background: linear-gradient(135deg, #0f4a7b, #1b6ca8); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 700;">THÊM GIAO DỊCH</button>
                        <a href="?template=user&action=dashboard" class="btn btn--ghost" style="padding: 12px 24px; text-decoration: none; display: inline-block; border: 1px solid #e5e7eb; border-radius: 8px; color: #6b7280; font-size: 15px; font-weight: 600;">Hủy</a>
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
                <form action="?template=user&action=add" method="POST" class="suspicious-modal-form">
                    <input type="hidden" name="confirm_suspicious" value="1">
                    <input type="hidden" name="price" value="<?= htmlspecialchars($suspiciousFormData['price']) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($suspiciousFormData['type']) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($suspiciousFormData['category_id']) ?>">
                    <input type="hidden" name="transaction_date" value="<?= htmlspecialchars($suspiciousFormData['transaction_date']) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($suspiciousFormData['description'] ?? '') ?>">
                    <button type="submit" name="add_btn" class="suspicious-modal-button confirm">Xác nhận giao dịch</button>
                </form>
                <button onclick="document.getElementById('suspiciousModal').style.display='none'" class="suspicious-modal-button cancel">Hủy bỏ</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$transactionSuccess = getFlashData("transaction_success");
if (!empty($transactionSuccess)):
    $typeStr = $transactionSuccess['type'] === 'income' ? 'Thu nhập' : 'Chi tiêu';
    $priceFormatted = number_format($transactionSuccess['price'], 0, ',', '.');
?>
    <div class="confirm-popup-overlay">
        <div class="confirm-popup-content">
            <div class="success-icon">✅</div>
            <h2>Giao dịch thành công!</h2>
            <p class="success-message">Giao dịch của bạn đã được lưu lại</p>

            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">LOẠI</span>
                    <span class="detail-value"><?= $typeStr ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">DANH MỤC</span>
                    <span class="detail-value"><?= htmlspecialchars($transactionSuccess['category_icon']) ?> <?= htmlspecialchars($transactionSuccess['category_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">SỐ TIỀN</span>
                    <span class="detail-value amount"><?= $priceFormatted ?>đ</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">NGÀY</span>
                    <span class="detail-value"><?= htmlspecialchars($transactionSuccess['transaction_date']) ?></span>
                </div>
                <?php if (!empty($transactionSuccess['description'])): ?>
                    <div class="detail-row">
                        <span class="detail-label">MÔ TẢ</span>
                        <span class="detail-value"><?= htmlspecialchars($transactionSuccess['description']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <button onclick="location.reload()" class="btn-primary-action">Thêm giao dịch khác</button>
                <button onclick="location.href='?template=user&action=dashboard'" class="btn-secondary-action">Xem dashboard</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($topLevelErrors)): ?>
    <div id="errorModal" class="suspicious-modal-overlay">
        <div class="suspicious-modal-content" style="text-align: center;">
            <h3 class="suspicious-modal-header" style="color: #c62828; margin-bottom: 20px;">Không thể thêm giao dịch</h3>

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
