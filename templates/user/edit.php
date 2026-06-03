<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

$view = 'filter';
$loginToken = getSession('loginToken');
if (empty($loginToken)) { setMessage("Bạn phải đăng nhập","error"); redirect("?template=auth&action=login.view"); }
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền truy cập trang này","error"); redirect("?template=admin&action=dashboard"); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = getSession('id');
if ($id <= 0) redirect("?template=user&action=filter");

$transaction = getOne("SELECT * FROM transaction WHERE id = :id AND user_id = :user_id", ['id'=>$id, 'user_id'=>$userId]);
if (!$transaction) {
    setFlashData('message','Không tìm thấy giao dịch này.');
    setFlashData('message_type','error');
    redirect("?template=user&action=filter");
}

$data = getAll("SELECT * FROM category ORDER BY name");
createDefaultWallets($userId);
$wallets = getWallets($userId);
layout("header", ["title"=>"Sửa Chi Tiêu","css"=>["layout/sidebar","pages/user/add","pages/user/edit"]]);

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
    if (!empty($errors['balance'])) $topLevelErrors[] = $errors['balance'];
    if (!empty($errors['limit'])) $topLevelErrors[] = $errors['limit'];
    if (!empty($errors['rate_limit'])) $topLevelErrors[] = $errors['rate_limit'];
    if (!empty($errors['budget'])) $topLevelErrors[] = $errors['budget'];
    if (!empty($errors['monthly_budget'])) $topLevelErrors[] = $errors['monthly_budget'];
}

$bailout = getFlashData("bailout_modal");
$transactionId = $transaction['id'];
?>
<div class="app-container">
    <?php layout("sidebar", ["view"=>$view]); ?>
    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div><span class="subtitle">DIGITAL CURATOR</span><h1>Cập Nhật Chi Tiêu</h1></div>
            </div>
            <div class="header-right"><div class="user-box">👤 <?= htmlspecialchars($username) ?></div></div>
        </header>

        <div class="page-content" style="padding-top:20px;">
            <main class="expense-card">
                <header class="expense-card__header">
                    <span class="expense-card__tag expense-card__tag--edit">CHỈNH SỬA</span>
                    <h1 class="expense-card__title">Sửa Giao Dịch</h1>
                </header>
                <?php if (!empty($message)) echo showMessage($message, $messageType); ?>

                <form id="expenseForm" class="expense-form" method="POST" action="?template=user&action=edit">
                    <input type="hidden" name="id" value="<?= $transaction['id'] ?>">

                    <div class="form-row" style="display:flex;gap:15px;flex-wrap:wrap;">
                        <div class="expense-form__group" style="flex:2;min-width:180px;">
                            <label class="expense-form__label">SỐ TIỀN</label>
                            <div class="expense-form__input-wrapper">
                                <input type="number" id="amount" class="expense-form__input" placeholder="Nhập số tiền..." name="price" value="<?= htmlspecialchars($formData['price'] ?? '') ?>" required>
                                <span class="expense-form__unit">VND</span>
                            </div>
                            <?php if (!empty($errors['price'])) echo "<span class='edit-error'>".$errors['price'][key($errors['price'])]."</span>"; ?>
                        </div>
                        <div class="expense-form__group" style="flex:1;min-width:120px;">
                            <label class="expense-form__label" for="type">LOẠI GIAO DỊCH</label>
                            <select id="type" class="expense-form__input" name="type">
                                <option value="income" <?= (!empty($formData['type']) && $formData['type']=='income')?'selected':'' ?>>Thu</option>
                                <option value="expense" <?= (empty($formData['type'])||$formData['type']=='expense')?'selected':'' ?>>Chi</option>
                            </select>
                        </div>
                        <div class="expense-form__group" style="flex:2;min-width:180px;">
                            <label class="expense-form__label">DANH MỤC</label>
                            <select id="category" class="expense-form__input" name="category" required>
                                <option value="" disabled <?= empty($formData['category_id'])?'selected':'' ?>>Chọn loại chi tiêu</option>
                                <?php if (!empty($data)): foreach ($data as $item): ?>
                                    <option value="<?= $item['id'] ?>" <?= (!empty($formData['category_id'])&&$formData['category_id']==$item['id'])?'selected':'' ?>><?= $item['icon']??'📦' ?> <?= htmlspecialchars($item['name']) ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <?php if (!empty($errors['category'])) echo "<span class='edit-error'>".$errors['category'][key($errors['category'])]."</span>"; ?>
                        </div>
                        <div class="expense-form__group" style="flex:1.5;min-width:150px;">
                            <label class="expense-form__label">NGÀY</label>
                            <input type="date" id="date" class="expense-form__input" name="transaction_date" value="<?= htmlspecialchars($formData['transaction_date'] ?? '') ?>" required>
                            <?php if (!empty($errors['transactionDate'])) echo "<span class='edit-error'>".$errors['transactionDate'][key($errors['transactionDate'])]."</span>"; ?>
                        </div>
                    </div>

                    <div class="form-row" style="display:flex;gap:15px;flex-wrap:wrap;">
                        <div class="expense-form__group" style="flex:1;min-width:250px;">
                            <label class="expense-form__label">NGUỒN TIỀN (VÍ)</label>
                            <div class="wallet-selector">
                                <input type="hidden" name="wallet_id" id="wallet_id" value="<?= htmlspecialchars($formData['wallet_id'] ?? '') ?>">
                                <div class="wallet-selector-trigger" onclick="toggleWalletDropdown(event)" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' ')toggleWalletDropdown(event)">
                                    <span class="ws-icon" id="wsIcon">💰</span>
                                    <span class="ws-name" id="wsName">Chọn ví...</span>
                                    <span class="ws-bal" id="wsBal"></span>
                                    <span class="material-symbols-outlined ws-arrow">expand_more</span>
                                </div>
                                <div class="wallet-selector-dropdown" id="walletDropdown">
                                    <?php
                                    $groups = ['daily'=>'Ví Chi Tiêu','ewallet'=>'Ví Điện Tử','target'=>'Quỹ / Mục Tiêu'];
                                    $typeLabels = ['daily'=>'Chi tiêu','ewallet'=>'Điện tử','target'=>'Mục tiêu'];
                                    $typeClasses = ['daily'=>'type-daily','ewallet'=>'type-ewallet','target'=>'type-target'];
                                    $typeIcons = ['daily'=>'account_balance','ewallet'=>'phone_android','target'=>'savings'];
                                    foreach ($groups as $gtype=>$glabel):
                                        $gw = array_filter($wallets, fn($w)=>$w['type']===$gtype);
                                        if (empty($gw)) continue;
                                    ?>
                                    <div class="ws-group-header"><span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;"><?= $typeIcons[$gtype] ?></span><?= $glabel ?></div>
                                    <?php foreach ($gw as $w):
                                        $excludeId = ($w['id'] == $transaction['wallet_id']) ? $transaction['id'] : null;
                                        $bal = getWalletBalance($w['id'], $userId, $excludeId);
                                        $sel = (!empty($formData['wallet_id']) && $formData['wallet_id']==$w['id']);
                                        $bc = $bal >= 0 ? '#059669' : '#dc2626';
                                    ?>
                                    <div class="ws-option<?= $sel?' selected':'' ?>" data-id="<?= $w['id'] ?>" data-icon="<?= htmlspecialchars($w['icon']??'💰') ?>" data-name="<?= htmlspecialchars($w['name']) ?>" data-bal="<?= $bal ?>" onclick="selectWallet(this)">
                                        <span class="ws-o-icon"><?= htmlspecialchars($w['icon']??'💰') ?></span>
                                        <div class="ws-o-info">
                                            <div class="ws-o-name"><?= htmlspecialchars($w['name']) ?></div>
                                            <span class="ws-o-type <?= $typeClasses[$gtype] ?>"><?= $typeLabels[$gtype] ?></span>
                                        </div>
                                        <span class="ws-o-bal" style="color:<?= $bc ?>"><?= number_format($bal,0,',','.') ?>đ</span>
                                        <span class="material-symbols-outlined ws-o-check">check_circle</span>
                                    </div>
                                    <?php endforeach; endforeach; ?>
                                </div>
                            </div>
                            <div class="ws-balance-preview" id="wsBalancePreview" style="display:none;">
                                <span class="material-symbols-outlined bp-icon">info</span>
                                <span class="bp-text">
                                    <span id="bpLabel">Số dư trước GD:</span>
                                    <strong class="bp-amount" id="bpCurrent">0đ</strong>
                                    <span id="bpArrow"> → </span>
                                    <strong class="bp-amount" id="bpRemaining">0đ</strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" style="display:flex;gap:15px;flex-wrap:wrap;">
                        <div class="expense-form__group" style="flex:1;min-width:250px;">
                            <label class="expense-form__label">MÔ TẢ</label>
                            <input type="text" id="description" class="expense-form__input" placeholder="Nhập mô tả ngắn..." name="description" value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
                            <?php if (!empty($errors['description'])) echo "<span class='edit-error'>".$errors['description'][key($errors['description'])]."</span>"; ?>
                        </div>
                    </div>

                    <div class="expense-form__actions" style="margin-top:20px;justify-content:flex-end;width:100%;display:flex;gap:10px;">
                        <a href="?template=user&action=filter" class="btn btn--ghost btn-edit-cancel" style="padding:12px 30px;text-decoration:none;display:inline-block;text-align:center;">HỦY</a>
                        <button type="submit" class="btn btn--primary btn-edit-submit" id="btnSubmitAdd" name="edit_btn" style="padding:12px 40px;background:linear-gradient(135deg,#0f4a7b,#1b6ca8);color:white;border:none;border-radius:8px;cursor:pointer;">CẬP NHẬT</button>
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
                <?php if (!empty($suspiciousWarning['suspicious'])): foreach ($suspiciousWarning['suspicious'] as $reason): ?>
                    <p class="suspicious-reason-item"><?= htmlspecialchars($reason) ?></p>
                <?php endforeach; endif; ?>
            </div>
            <div class="suspicious-modal-details">
                <p class="suspicious-detail-item"><strong>Loại:</strong> <?= $suspiciousFormData['type']==='income'?'Thu nhập':'Chi tiêu' ?></p>
                <p class="suspicious-detail-item"><strong>Số tiền:</strong> <?= number_format($suspiciousFormData['price'],0,',','.') ?>đ</p>
                <p class="suspicious-detail-item"><strong>Ngày:</strong> <?= htmlspecialchars($suspiciousFormData['transaction_date']) ?></p>
                <?php if (!empty($suspiciousFormData['description'])): ?><p class="suspicious-detail-item"><strong>Mô tả:</strong> <?= htmlspecialchars($suspiciousFormData['description']) ?></p><?php endif; ?>
            </div>
            <div class="suspicious-modal-actions">
                <form action="?template=user&action=edit" method="POST" class="suspicious-modal-form">
                    <input type="hidden" name="id" value="<?= $transaction['id'] ?>">
                    <input type="hidden" name="confirm_suspicious" value="1">
                    <input type="hidden" name="price" value="<?= htmlspecialchars($suspiciousFormData['price']) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($suspiciousFormData['type']) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($suspiciousFormData['category_id']) ?>">
                    <input type="hidden" name="transaction_date" value="<?= htmlspecialchars($suspiciousFormData['transaction_date']) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($suspiciousFormData['description']??'') ?>">
                    <input type="hidden" name="wallet_id" value="<?= htmlspecialchars($suspiciousFormData['wallet_id']??'') ?>">
                    <button type="submit" name="edit_btn" class="suspicious-modal-button confirm">Xác nhận cập nhật</button>
                </form>
                <button onclick="document.getElementById('suspiciousModal').style.display='none'" class="suspicious-modal-button cancel">Hủy bỏ</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// ── Unified Bailout Modal (Edit) ──
if (!empty($bailout)):
    $wType = $bailout['wallet_type'] ?? 'daily';
    $typeIcons = ['daily'=>'account_balance_wallet','ewallet'=>'phone_android','target'=>'savings'];
    $typeIcon = $typeIcons[$wType] ?? 'account_balance_wallet';
?>
    <div id="bailoutModal" class="suspicious-modal-overlay" style="display:flex;">
        <div class="suspicious-modal-content bailout-content">
            <div class="bailout-header">
                <div class="bailout-header-icon <?= $wType ?>"><span class="material-symbols-outlined"><?= $typeIcon ?></span></div>
                <div class="bailout-header-text">
                    <h3>Ví không đủ số dư</h3>
                    <p>Chọn cách xử lý cho giao dịch này</p>
                </div>
            </div>

            <div class="bailout-info-row">
                <span class="bir-label">Ví hiện tại</span>
                <span class="bir-value"><?= htmlspecialchars($bailout['wallet_icon']) ?> <?= htmlspecialchars($bailout['wallet_name']) ?></span>
            </div>
            <div class="bailout-info-row">
                <span class="bir-label">Số dư hiện có</span>
                <span class="bir-value"><?= number_format($bailout['current_balance'],0,',','.') ?>đ</span>
            </div>
            <div class="bailout-info-row">
                <span class="bir-label">Số tiền giao dịch</span>
                <span class="bir-value"><?= number_format($bailout['price'],0,',','.') ?>đ</span>
            </div>
            <div class="bailout-info-row">
                <span class="bir-label">Thiếu</span>
                <span class="bir-value negative">-<?= number_format($bailout['deficit'],0,',','.') ?>đ</span>
            </div>

            <?php if (!empty($bailout['sufficient_wallets'])): ?>
            <hr class="bailout-divider">
            <div class="bailout-section-title"><span class="material-symbols-outlined" style="font-size:18px">swap_horiz</span> Chọn ví khác để thanh toán</div>
            <div class="bailout-wallet-list">
                <?php foreach ($bailout['sufficient_wallets'] as $sw):
                    $swTypeLabel = ['daily'=>'Chi tiêu','ewallet'=>'Điện tử','target'=>'Mục tiêu'][$sw['type']] ?? '';
                ?>
                <form action="?template=user&action=edit" method="POST" style="margin:0;" class="bailout-form">
                    <input type="hidden" name="id" value="<?= $transactionId ?>">
                    <input type="hidden" name="bailout_wallet" value="<?= $sw['id'] ?>">
                    <input type="hidden" name="price" value="<?= htmlspecialchars($bailout['price']) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($bailout['type']) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($bailout['category_id']) ?>">
                    <input type="hidden" name="transaction_date" value="<?= htmlspecialchars($bailout['transaction_date']) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($bailout['description'] ?? '') ?>">
                    <button type="button" name="edit_btn" class="bailout-wallet-item" style="width:100%;text-align:left;" onclick="confirmBailoutWallet(this)">
                        <span class="bwi-icon"><?= htmlspecialchars($sw['icon']??'💰') ?></span>
                        <div class="bwi-info">
                            <div class="bwi-name"><?= htmlspecialchars($sw['name']) ?> &middot; <span style="font-size:11px;color:#64748b;"><?= $swTypeLabel ?></span></div>
                            <div class="bwi-sub">Số dư: <?= number_format($sw['balance'],0,',','.') ?>đ</div>
                        </div>
                        <span class="bwi-bal">Dùng →</span>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($bailout['all_wallets'])): ?>
            <hr class="bailout-divider">
            <div class="bailout-section-title"><span class="material-symbols-outlined" style="font-size:18px">call_merge</span> Hoặc kết hợp nhiều ví</div>
            <p style="font-size:12px;color:#64748b;margin:0 0 10px;">Chọn nhiều ví và nhập số tiền muốn thanh toán từ mỗi ví (không vượt quá số dư)</p>
            <form action="?template=user&action=edit" method="POST" id="multiBailoutForm">
                <input type="hidden" name="id" value="<?= $transactionId ?>">
                <?php foreach (['price','type','category','transaction_date','description','wallet_id'] as $hf): ?>
                <input type="hidden" name="<?= $hf ?>" value="<?= htmlspecialchars($bailout[$hf] ?? '') ?>">
                <?php endforeach; ?>
                <div class="multi-wallet-list" style="max-height:240px;overflow-y:auto;">
                    <?php foreach ($bailout['all_wallets'] as $aw):
                        $isCurrent = $aw['id'] == $bailout['wallet_id'];
                    ?>
                    <div class="bailout-wallet-item" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:6px;background:<?= $isCurrent?'#fefce8':'#fff' ?>;">
                        <label style="flex:1;display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" name="multi_bailout[]" value="<?= $aw['id'] ?>" class="multi-cb" onchange="updateMultiTotal(this)">
                            <span class="bwi-icon"><?= htmlspecialchars($aw['icon']??'💰') ?></span>
                            <div class="bwi-info" style="flex:1;">
                                <div class="bwi-name" style="font-size:13px;"><?= htmlspecialchars($aw['name']) ?> <?= $isCurrent?'<span style="font-size:10px;color:#d97706;">(hiện tại)</span>':'' ?></div>
                                <div class="bwi-sub" style="font-size:11px;color:#64748b;">Số dư: <?= number_format($aw['balance'],0,',','.') ?>đ</div>
                            </div>
                        </label>
                        <input type="number" name="multi_amounts[<?= $aw['id'] ?>]" min="0" max="<?= $aw['balance'] ?>" placeholder="0" class="multi-amount" style="width:85px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;text-align:right;" disabled onchange="updateMultiTotal(this)" oninput="updateMultiTotal(this)">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding:0 4px;">
                    <span id="multiTotalText" style="font-size:13px;font-weight:600;color:#475569;">Tổng đã chọn: 0đ / <?= number_format($bailout['price'],0,',','.') ?>đ</span>
                    <button type="submit" name="edit_btn" id="multiSubmitBtn" class="bailout-confirm-btn" style="opacity:0.5;pointer-events:none;" disabled>Thanh toán</button>
                </div>
            </form>
            <?php endif; ?>

            <button onclick="document.getElementById('bailoutModal').style.display='none'" class="bailout-cancel-btn">Hủy cập nhật</button>
        </div>
    </div>

    <!-- Bailout confirmation overlay -->
    <div id="bailoutConfirmOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:10000;align-items:center;justify-content:center;">
        <div style="background:var(--color-surface,#fff);border-radius:16px;padding:28px 32px;width:380px;max-width:90vw;box-shadow:0 16px 48px rgba(0,0,0,0.2);text-align:center;">
            <span class="material-symbols-outlined" style="font-size:40px;color:#f59e0b;margin-bottom:12px;">warning</span>
            <h3 style="margin:0 0 6px;font-size:18px;font-weight:800;color:var(--color-text-main,#0f172a);">Xác nhận chuyển ví</h3>
            <p style="margin:0 0 18px;font-size:14px;color:#64748b;">Bạn có chắc chắn muốn dùng ví này để thanh toán?</p>
            <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:20px;text-align:left;font-size:13px;line-height:1.8;">
                <div><strong>Ví:</strong> <span id="bcWalletName"></span></div>
                <div><strong>Số dư ví:</strong> <span id="bcWalletBal"></span></div>
                <div><strong>Số tiền GD:</strong> <span id="bcAmount"></span></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button onclick="submitBailout()" style="padding:10px 28px;background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;">Xác nhận</button>
                <button onclick="cancelBailoutConfirm()" style="padding:10px 28px;background:#f1f5f9;color:#64748b;border:none;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;">Hủy</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($topLevelErrors)): ?>
    <div id="errorModal" class="suspicious-modal-overlay">
        <div class="suspicious-modal-content" style="text-align:center;">
            <h3 class="suspicious-modal-header" style="color:#c62828;margin-bottom:20px;">Không thể cập nhật giao dịch</h3>
            <div class="suspicious-modal-reasons" style="background:#ffebee;border:1px solid #ef9a9a;">
                <?php foreach ($topLevelErrors as $err): ?>
                    <p class="suspicious-reason-item" style="color:#b71c1c;font-weight:500;font-size:15px;"><?= htmlspecialchars($err) ?></p>
                <?php endforeach; ?>
            </div>
            <div class="suspicious-modal-actions" style="margin-top:25px;">
                <button type="button" onclick="document.getElementById('errorModal').style.display='none'" class="suspicious-modal-button cancel" style="background:#e0e0e0;color:#333;font-weight:bold;border:none;padding:12px 30px;">Đã hiểu</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$transactionSuccess = getFlashData("transaction_success");
if (!empty($transactionSuccess)):
    $typeStr = $transactionSuccess['type']==='income'?'Thu nhập':'Chi tiêu';
    $priceFormatted = number_format($transactionSuccess['price'],0,',','.');
?>
    <div class="confirm-popup-overlay">
        <div class="confirm-popup-content">
            <div class="success-icon">✅</div>
            <h2>Cập nhật thành công!</h2>
            <p class="success-message">Giao dịch đã được cập nhật</p>
            <div class="transaction-details">
                <div class="detail-row"><span class="detail-label">LOẠI</span><span class="detail-value"><?= $typeStr ?></span></div>
                <div class="detail-row"><span class="detail-label">DANH MỤC</span><span class="detail-value"><?= htmlspecialchars($transactionSuccess['category_icon']) ?> <?= htmlspecialchars($transactionSuccess['category_name']) ?></span></div>
                <div class="detail-row"><span class="detail-label">SỐ TIỀN</span><span class="detail-value amount"><?= $priceFormatted ?>đ</span></div>
                <div class="detail-row"><span class="detail-label">NGÀY</span><span class="detail-value"><?= htmlspecialchars($transactionSuccess['transaction_date']) ?></span></div>
                <?php if (!empty($transactionSuccess['description'])): ?>
                <div class="detail-row"><span class="detail-label">MÔ TẢ</span><span class="detail-value"><?= htmlspecialchars($transactionSuccess['description']) ?></span></div>
                <?php endif; ?>
            </div>
            <div class="action-buttons">
                <button onclick="location.href='?template=user&action=filter'" class="btn-primary-action">Quay lại danh sách</button>
                <button onclick="location.href='?template=user&action=dashboard'" class="btn-secondary-action">Xem dashboard</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
function toggleWalletDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('walletDropdown');
    const trig = document.querySelector('.wallet-selector-trigger');
    const isOpen = dd.classList.contains('show');
    closeAllDropdowns();
    if (!isOpen) { dd.classList.add('show'); trig.classList.add('open'); }
}
function closeAllDropdowns() {
    document.querySelectorAll('.wallet-selector-dropdown').forEach(d => d.classList.remove('show'));
    document.querySelectorAll('.wallet-selector-trigger').forEach(t => t.classList.remove('open'));
}
var originalPrice = <?= $transaction['price'] ?>;
var originalType = '<?= $transaction['type'] ?>';

function selectWallet(el) {
    document.querySelectorAll('.ws-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    const id = el.dataset.id;
    const icon = el.dataset.icon;
    const name = el.dataset.name;
    const bal = parseFloat(el.dataset.bal);
    document.getElementById('wallet_id').value = id;
    document.getElementById('wsIcon').textContent = icon;
    document.getElementById('wsName').textContent = name;
    document.getElementById('wsName').style.color = '#0f172a';
    document.getElementById('wsBal').textContent = bal >= 0 ? numberFormat(bal) + 'đ' : '-' + numberFormat(Math.abs(bal)) + 'đ';
    document.getElementById('wsBal').style.color = bal >= 0 ? '#059669' : '#dc2626';
    closeAllDropdowns();
    updateBalancePreview(id);
}
function updateBalancePreview(walletId) {
    const preview = document.getElementById('wsBalancePreview');
    const bpCurrent = document.getElementById('bpCurrent');
    const bpRemaining = document.getElementById('bpRemaining');
    const bpLabel = document.getElementById('bpLabel');
    const options = document.querySelectorAll('.ws-option');
    let bal = 0;
    options.forEach(function(o) {
        if (o.dataset.id == walletId) bal = parseFloat(o.dataset.bal);
    });
    const price = parseFloat(document.getElementById('amount').value) || 0;
    const type = document.getElementById('type').value;
    const remaining = type === 'income' ? bal + price : bal - price;
    if (walletId) {
        preview.style.display = 'flex';
        bpCurrent.textContent = numberFormat(bal) + 'đ';
        bpRemaining.textContent = remaining >= 0 ? numberFormat(remaining) + 'đ' : '-' + numberFormat(Math.abs(remaining)) + 'đ (sẽ âm)';
        bpRemaining.style.color = remaining >= 0 ? '#166534' : '#dc2626';
        document.querySelector('#wsBalancePreview .bp-icon').textContent = remaining >= 0 ? 'check_circle' : 'warning';
        preview.className = remaining >= 0 ? 'ws-balance-preview' : 'ws-balance-preview warning';
    } else { preview.style.display = 'none'; }
}
function numberFormat(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.wallet-selector')) closeAllDropdowns();
});
document.getElementById('amount').addEventListener('input', function() {
    const wid = document.getElementById('wallet_id').value;
    if (wid) updateBalancePreview(wid);
});
document.getElementById('type').addEventListener('change', function() {
    const wid = document.getElementById('wallet_id').value;
    if (wid) updateBalancePreview(wid);
});
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('wallet_id').value;
    if (sel) {
        const opt = document.querySelector('.ws-option[data-id="' + sel + '"]');
        if (opt) selectWallet(opt);
    }
});

// Multi-wallet bailout
function updateMultiTotal(src) {
    var form = document.getElementById('multiBailoutForm');
    if (!form) return;
    var cbs = form.querySelectorAll('.multi-cb');
    var price = parseFloat(form.querySelector('input[name="price"]').value) || 0;
    var total = 0;
    cbs.forEach(function(cb) {
        var inp = form.querySelector('input[name="multi_amounts[' + cb.value + ']"]');
        if (cb.checked) {
            inp.disabled = false;
            var val = parseFloat(inp.value) || 0;
            var max = parseFloat(inp.getAttribute('max')) || 0;
            if (val > max) { inp.value = max; val = max; }
            if (val < 0) { inp.value = 0; val = 0; }
            total += val;
        } else {
            inp.disabled = true;
            inp.value = '';
        }
    });
    var txt = document.getElementById('multiTotalText');
    if (txt) txt.textContent = 'Tổng đã chọn: ' + numberFormat(total) + 'đ / ' + numberFormat(price) + 'đ';
    var btn = document.getElementById('multiSubmitBtn');
    if (btn) {
        var ok = total >= price;
        btn.disabled = !ok;
        btn.style.opacity = ok ? '1' : '0.5';
        btn.style.pointerEvents = ok ? 'auto' : 'none';
    }
}

// Bailout confirmation
var pendingBailoutForm = null;
function confirmBailoutWallet(btn) {
    var form = btn.closest('.bailout-form');
    if (!form) return;
    pendingBailoutForm = form;
    var icon = btn.querySelector('.bwi-icon');
    var name = btn.querySelector('.bwi-name');
    var sub = btn.querySelector('.bwi-sub');
    document.getElementById('bcWalletName').textContent = (icon ? icon.textContent : '') + ' ' + (name ? name.textContent : '');
    document.getElementById('bcWalletBal').textContent = sub ? sub.textContent.replace('Số dư: ', '') : '';
    document.getElementById('bcAmount').textContent = numberFormat(parseFloat(form.querySelector('input[name="price"]').value || 0)) + 'đ';
    document.getElementById('bailoutConfirmOverlay').style.display = 'flex';
}
function submitBailout() {
    if (pendingBailoutForm) pendingBailoutForm.submit();
}
function cancelBailoutConfirm() {
    pendingBailoutForm = null;
    document.getElementById('bailoutConfirmOverlay').style.display = 'none';
}
</script>
<?php layout("footer", ["js" => ["pages/sidebar"]]); ?>
