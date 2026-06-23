<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

$view = 'filter';
$loginToken = getSession('loginToken');
if(empty($loginToken)){ setMessage("Bạn phải đăng nhập","error"); redirect("?template=auth&action=login.view"); }
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền truy cập trang này","error"); redirect("?template=admin&action=dashboard"); }

// ── AJAX: Cursor-based pagination (load more) ──
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $userId = getSession('id');
    $where = getSession("filter_where") ?? "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0 AND transaction.source_type != 'transfer'";
    $where = preg_replace('/(?<![.\w])type\s*=/', 'transaction.type =', $where);
    $params = getSession("filter_params") ?? ['user_id' => $userId];

    $page = getFilterTransactions($where, $params, 5, isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0, $_GET['last_date'] ?? '');
    $batchDetails = getFilterBatchDetails($page['items'], $userId);
    $readyIdMap = getFilterReadyIdMap($page['items'], $userId);

    $rowsHtml = '';
    foreach ($page['items'] as $item) $rowsHtml .= renderFilterTransactionRow($item, $batchDetails, $readyIdMap);

    jsonResponse(true, '', [
        'rows' => $rowsHtml, 'count' => count($page['items']),
        'has_more' => $page['hasMore'], 'next_last_id' => $page['nextId'],
        'next_last_date' => $page['nextDate'], 'empty' => empty($page['items']),
    ]);
}

layout("header", ["title" => "Lọc Dữ Liệu", "css" => ["layout/sidebar", "pages/user/filter"]]);

// ── Load initial data ──
$username = getSession('username');
$userId = getSession('id');

$where = getSession("filter_where") ?? "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0 AND transaction.source_type != 'transfer'";
$where = preg_replace('/(?<![.\w])type\s*=/', 'transaction.type =', $where);
$params = getSession("filter_params") ?? ['user_id' => $userId];
$oldInputs = getSession("filter_oldInputs") ?? [];

$page = getFilterTransactions($where, $params);
$filterTransaction = $page['items'];
$filterHasMore = $page['hasMore'];
$lastId = $page['nextId'];
$lastDate = $page['nextDate'];
$batchDetails = getFilterBatchDetails($filterTransaction, $userId);

$categoryList = getCachedCategories('name');
$wallets = getWallets($userId);

$message = getFlashData("message");
$message_type = getFlashData("message_type");

[$pendingItems, $readyItems, $readyIdMap] = getFilterPendingInfo($userId);
?>
<div class="app-container filter-page">
    <?php layout("sidebar", ["view" => $view]); ?>

    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div>
                    <span class="subtitle">DIGITAL CURATOR</span>
                    <h1>Quản lý Chi Tiêu</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username) ?></div>
            </div>
        </header>

        <div class="page-content">
            <div class="card filter-form-card">
                <form action="?template=user&action=filter" method="POST" class="filter-form-inline">
                    <input type="date" name="date_from" class="filter-input" placeholder="Từ ngày"
                           value="<?= $oldInputs['date_from'] ?? '' ?>">
                    <span class="filter-separator">→</span>
                    <input type="date" name="date_to" class="filter-input" placeholder="Đến ngày"
                           value="<?= $oldInputs['date_to'] ?? '' ?>">

                    <select name="type" class="filter-input">
                        <option value="" selected >-- Tất cả loại --</option>
                        <option value="income" <?= (!empty($oldInputs['type']) && $oldInputs['type'] == 'income' ? 'selected' : '') ?>>Thu nhập (+)</option>
                        <option value="expense" <?= (!empty($oldInputs['type']) && $oldInputs['type'] == 'expense' ? 'selected' : '') ?>>Chi tiêu (-)</option>
                    </select>

                    <div class="filter-category-checkboxes">
                        <div class="filter-category-header">Danh mục</div>
                        <div class="filter-category-list">
                            <?php if($categoryList): foreach($categoryList as $dm): ?>
                                <label class="filter-category-item">
                                    <input type="checkbox" name="category_ids[]" value="<?= $dm['id'] ?>"
                                        <?= (!empty($oldInputs['category_ids']) && in_array($dm['id'], (array)$oldInputs['category_ids']) ? 'checked' : '') ?>>
                                    <span><?= $dm['icon'] ?? '📦' ?> <?= $dm['name'] ?></span>
                                </label>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <input type="text" name="description" placeholder="Mô tả..." class="filter-input"
                           value="<?= $oldInputs['description'] ?? '' ?>">

                    <input type="number" name="price_min" placeholder="Số tiền từ..." class="filter-input" min="0" step="1000"
                           value="<?= $oldInputs['price_min'] ?? '' ?>">

                    <input type="number" name="price_max" placeholder="Số tiền đến..." class="filter-input" min="0" step="1000"
                           value="<?= $oldInputs['price_max'] ?? '' ?>">

                    <select name="wallet_id" class="filter-input">
                        <option value="">-- Tất cả ví --</option>
                        <?php if (!empty($wallets)): foreach ($wallets as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= (!empty($oldInputs['wallet_id']) && $oldInputs['wallet_id'] == $w['id'] ? 'selected' : '') ?>>
                                <?= htmlspecialchars($w['icon'] ?? '💰') ?> <?= htmlspecialchars($w['name']) ?>
                            </option>
                        <?php endforeach; endif; ?>
                    </select>

                    <div style="display:flex; gap:8px; align-items:center;">
                        <button type="submit" class="filter-btn-submit" name="filter-btn">Lọc</button>
                        <button type="submit" class="filter-btn-reset" name="filter-reset-btn">Xóa lọc</button>
                    </div>
                </form>
            </div>

            <?php if(!empty($message)) echo showMessage($message, $message_type); ?>

            <?php if (!empty($pendingItems)): ?>
            <div class="pending-notification">
                <div class="pending-notif-icon"><span class="material-symbols-outlined">hourglass_top</span></div>
                <div class="pending-notif-body">
                    <h4><?= count($pendingItems) ?> giao dịch đang chờ xử lý</h4>
                    <p>Số dư ví không đủ để xoá. Nạp thêm tiền vào ví để kích hoạt:</p>
                    <ul class="pending-notif-list">
                    <?php foreach ($pendingItems as $tx): ?>
                        <li>
                            <?= htmlspecialchars($tx['description']) ?>
                            (<strong><?= number_format($tx['price'],0,',','.') ?>đ</strong>)
                            - <?= ($tx['wallet_icon'] ?? '💰') . ' ' . htmlspecialchars($tx['wallet_name'] ?? '') ?>
                            <button type="button" class="btn-pending-cancel" onclick="submitPendingAction(<?= $tx['id'] ?>, 'cancel')">Huỷ</button>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($readyItems)): ?>
            <div class="pending-notification pending-ready">
                <div class="pending-notif-icon"><span class="material-symbols-outlined">check_circle</span></div>
                <div class="pending-notif-body">
                    <h4><?= count($readyItems) ?> giao dịch đã sẵn sàng để xoá!</h4>
                    <p>Số dư ví đã đủ, nhấn Xác nhận để hoàn tất:</p>
                    <ul class="pending-notif-list">
                    <?php foreach ($readyItems as $rt): ?>
                        <li>
                            <?= htmlspecialchars($rt['description']) ?>
                            (<strong><?= number_format($rt['price'],0,',','.') ?>đ</strong>)
                            - <?= ($rt['wallet_icon'] ?? '💰') . ' ' . htmlspecialchars($rt['wallet_name'] ?? '') ?>
                            <button type="button" class="btn-pending-confirm" onclick="submitPendingAction(<?= $rt['id'] ?>, 'confirm')">Xác nhận xoá</button>
                            <button type="button" class="btn-pending-cancel" onclick="submitPendingAction(<?= $rt['id'] ?>, 'cancel')">Huỷ</button>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <form action="?template=user&action=delete" method="POST" id="formDelete" onsubmit="return showDeleteModal()">
                <input type="hidden" name="btn-delete" id="btnDeleteFlag" value="1">
                <div class="card filter-result-card">
                    <div class="filter-toolbar">
                        <div class="filter-toolbar-left">
                            <h3>Kết quả (<span class="result-count"><?= count($filterTransaction) ?></span>)</h3>
                        </div>
                        <button type="submit" class="filter-btn-delete" name="btn-delete">
                            🗑️ Xóa các mục đã chọn
                        </button>
                    </div>

                    <div class="filter-table-wrapper">
                        <div class="filter-arrows">
                            <button type="button" class="filter-arrow filter-arrow--prev" title="Trang trước" aria-label="Trang trước" style="display:none;">&#x2039;</button>
                            <button type="button" class="filter-arrow filter-arrow--next" title="Trang sau" aria-label="Trang sau" style="display:none;">&#x203A;</button>
                        </div>
                        <table class="filter-data-table">
                            <thead class="filter-thead">
                                <tr>
                                    <th class="filter-th">Ngày</th>
                                    <th class="filter-th">Danh mục</th>
                                    <th class="filter-th">Mô tả</th>
                                    <th class="filter-th">Nguồn</th>
                                    <th class="filter-th text-right">Số tiền</th>
                                    <th class="filter-th text-center action-col">Hành động</th>
                                    <th class="filter-th text-center checkbox-col">
                                        <input type="checkbox" id="checkAll">
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="filter-tbody" id="filterTbody">
                            <?php if (empty($filterTransaction)): ?>
                                    <tr id="emptyRow">
                                        <td colspan="7" class="filter-td filter-empty-state">Không có giao dịch nào phù hợp.</td>
                                    </tr>
                            <?php else: ?>
                                <?php foreach ($filterTransaction as $item) echo renderFilterTransactionRow($item, $batchDetails, $readyIdMap); ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="load-more-container" id="loadMoreContainer">
                    <button type="button" class="btn-load-more" id="btnLoadMore">Xem thêm</button>
                    <div class="load-more-spinner" id="loadMoreSpinner" style="display:none;">Đang tải...</div>
                </div>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- Hidden form for pending actions -->
<form id="pendingActionForm" method="POST" action="?template=user&action=filter" style="display:none;">
    <input type="hidden" name="pending_id" id="pendingActionId" value="">
    <input type="hidden" name="confirm_pending_delete" id="pendingActionConfirm" value="">
    <input type="hidden" name="cancel_pending_delete" id="pendingActionCancel" value="">
    <input type="hidden" name="edit_id" id="pendingEditId" value="">
    <input type="hidden" name="cancel_pending_edit" id="pendingEditCancel" value="">
    <input type="hidden" name="ready_id" id="readyActionId" value="">
    <input type="hidden" name="accept_ready_edit" id="readyActionAccept" value="">
</form>

<!-- Delete confirmation modal -->
<div id="deleteConfirmModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="material-symbols-outlined modal-icon">delete_forever</span>
            <h3>Xác nhận xoá</h3>
        </div>
        <div class="modal-body">
            <p>Bạn có chắc chắn muốn xoá <strong id="deleteCount">0</strong> giao dịch đã chọn?</p>
            <p style="font-size:13px;color:var(--color-text-muted);">Hành động này không thể khôi phục.</p>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Huỷ</button>
            <button type="button" class="btn-modal-confirm" onclick="confirmDeleteSubmit()">Xoá</button>
        </div>
    </div>
</div>

<!-- Pending edit detail modal -->
<div id="pendingDetailModal" class="modal-overlay" style="display:none;">
    <div class="modal-content pending-detail-content">
        <div class="modal-header">
            <span class="material-symbols-outlined modal-icon">edit_note</span>
            <h3>Chi tiết thay đổi</h3>
            <button type="button" class="modal-close-btn" onclick="closePendingDetail()" style="background:none;border:none;font-size:24px;cursor:pointer;margin-left:auto;">&times;</button>
        </div>
        <div class="modal-body" id="pendingDetailBody">
            <p>Đang tải...</p>
        </div>
        <div class="modal-actions" id="pendingDetailActions" style="display:none;">
            <button type="button" class="btn-modal-cancel" onclick="closePendingDetail()">Đóng</button>
        </div>
    </div>
</div>

<script>
// ── Cursor-based pagination state ──
var cursorLastId = <?= $lastId ?>;
var cursorLastDate = <?= json_encode($lastDate) ?>;
var cursorLoading = false;
var cursorHasMore = <?= $filterHasMore ? 'true' : 'false' ?>;

function loadMore() {
    if (cursorLoading || !cursorHasMore) return;
    cursorLoading = true;
    document.getElementById('btnLoadMore').style.display = 'none';
    document.getElementById('loadMoreSpinner').style.display = 'inline';

    var url = '?template=user&action=filter&ajax=1&last_id=' + cursorLastId + '&last_date=' + encodeURIComponent(cursorLastDate);

    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            cursorLoading = false;
            document.getElementById('loadMoreSpinner').style.display = 'none';

            if (!res.success) {
                document.getElementById('btnLoadMore').textContent = 'Lỗi tải dữ liệu';
                document.getElementById('btnLoadMore').style.display = 'inline';
                return;
            }

            var data = res.data;
            if (data.empty || data.count === 0) {
                cursorHasMore = false;
                document.getElementById('loadMoreContainer').style.display = 'none';
                return;
            }

            var emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            var tbody = document.getElementById('filterTbody');
            tbody.insertAdjacentHTML('beforeend', data.rows);
            if (window.rebindCheckboxes) window.rebindCheckboxes();

            cursorLastId = data.next_last_id;
            cursorLastDate = data.next_last_date;
            cursorHasMore = data.has_more;

            var countEl = document.querySelector('.result-count');
            var currentCount = countEl ? parseInt(countEl.textContent) : 0;
            if (countEl) countEl.textContent = currentCount + data.count;

            if (cursorHasMore) {
                document.getElementById('btnLoadMore').style.display = 'inline';
            } else {
                document.getElementById('loadMoreContainer').style.display = 'none';
            }
        })
        .catch(function() {
            cursorLoading = false;
            document.getElementById('loadMoreSpinner').style.display = 'none';
            document.getElementById('btnLoadMore').textContent = 'Lỗi, thử lại';
            document.getElementById('btnLoadMore').style.display = 'inline';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    if (!cursorHasMore) document.getElementById('loadMoreContainer').style.display = 'none';
    document.getElementById('btnLoadMore').addEventListener('click', loadMore);
});

function submitPendingAction(id, action) {
    document.getElementById('pendingActionId').value = id;
    document.getElementById('pendingEditId').value = '';
    document.getElementById('pendingEditCancel').value = '';
    document.getElementById('readyActionId').value = '';
    document.getElementById('readyActionAccept').value = '';
    if (action === 'confirm') {
        document.getElementById('pendingActionConfirm').value = '1';
        document.getElementById('pendingActionCancel').value = '';
    } else {
        document.getElementById('pendingActionCancel').value = '1';
        document.getElementById('pendingActionConfirm').value = '';
    }
    document.getElementById('pendingActionForm').submit();
}

function submitPendingEditCancel(id) {
    document.getElementById('pendingEditId').value = id;
    document.getElementById('pendingEditCancel').value = '1';
    document.getElementById('pendingActionId').value = '';
    document.getElementById('pendingActionConfirm').value = '';
    document.getElementById('pendingActionCancel').value = '';
    document.getElementById('readyActionId').value = '';
    document.getElementById('readyActionAccept').value = '';
    document.getElementById('pendingActionForm').submit();
}

function submitReadyAccept(id) {
    if (!confirm('Xác nhận áp dụng thay đổi cho giao dịch này?')) return;
    document.getElementById('readyActionId').value = id;
    document.getElementById('readyActionAccept').value = '1';
    document.getElementById('pendingActionId').value = '';
    document.getElementById('pendingEditId').value = '';
    document.getElementById('pendingEditCancel').value = '';
    document.getElementById('pendingActionConfirm').value = '';
    document.getElementById('pendingActionCancel').value = '';
    document.getElementById('pendingActionForm').submit();
}

function showPendingDetail(id) {
    var body = document.getElementById('pendingDetailBody');
    body.innerHTML = '<p>Đang tải...</p>';
    document.getElementById('pendingDetailActions').style.display = 'none';
    document.getElementById('pendingDetailModal').style.display = 'flex';
    fetch('?template=user&action=filter&ajax=pending_edit_detail&id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success) { body.innerHTML = '<p class="error">' + res.message + '</p>'; return; }
            var d = res.data;
            var html = '<div class="pd-section"><h4>Thông tin giao dịch</h4>';
            html += '<p><strong>Mô tả:</strong> ' + (d.description || '') + '</p>';
            html += '<p><strong>Giá cũ:</strong> ' + d.old_price_fmt + 'đ (' + d.old_type_label + ')</p>';
            html += '</div>';
            html += '<div class="pd-section"><h4>Ví cũ</h4>';
            if (d.old_wallets && d.old_wallets.length > 0) {
                d.old_wallets.forEach(function(w) {
                    var wSign = w.type === 'income' ? '+' : '-';
                    html += '<div class="pd-wallet-row"><span class="pd-wallet-icon">' + w.icon + '</span><span class="pd-wallet-name">' + w.name + '</span><span class="pd-wallet-amount">' + wSign + ' ' + w.amount_fmt + 'đ</span></div>';
                });
            } else { html += '<p style="color:#64748b;">Không có dữ liệu</p>'; }
            html += '</div>';
            html += '<div class="pd-section"><h4>Ví mới (sau khi sửa)</h4>';
            if (d.new_wallets && d.new_wallets.length > 0) {
                d.new_wallets.forEach(function(w) {
                    var wSign = w.tx_type === 'income' ? '+' : '-';
                    html += '<div class="pd-wallet-row"><span class="pd-wallet-icon">' + w.icon + '</span><span class="pd-wallet-name">' + w.name + '</span><span class="pd-wallet-amount">' + wSign + ' ' + w.amount_fmt + 'đ</span></div>';
                });
            } else { html += '<p style="color:#64748b;">Không có dữ liệu</p>'; }
            html += '</div>';
            body.innerHTML = html;
            document.getElementById('pendingDetailActions').style.display = 'flex';
        })
        .catch(function() {
            body.innerHTML = '<p class="error">Lỗi tải dữ liệu.</p>';
            document.getElementById('pendingDetailActions').style.display = 'flex';
        });
}

function closePendingDetail() { document.getElementById('pendingDetailModal').style.display = 'none'; }

function showDeleteModal() {
    var checked = document.querySelectorAll('.checkItem:checked').length;
    if (checked === 0) { alert('Vui lòng chọn ít nhất một mục để xóa.'); return false; }
    document.getElementById('deleteCount').textContent = checked;
    document.getElementById('deleteConfirmModal').style.display = 'flex';
    return false;
}

function closeDeleteModal() { document.getElementById('deleteConfirmModal').style.display = 'none'; }

function confirmDeleteSubmit() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    document.getElementById('formDelete').submit();
}
</script>

<?php
layout("footer", ["js" => ["pages/sidebar", "pages/user/filter"]]);
?>
