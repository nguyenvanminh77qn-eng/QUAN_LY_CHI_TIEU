<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$view = 'wallet';
$loginToken = getSession('loginToken');
if (empty($loginToken)) { setMessage("Bạn phải đăng nhập","error"); redirect("?template=auth&action=login.view"); }
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền","error"); redirect("?template=admin&action=dashboard"); }

$userId = getSession('id');
createDefaultWallets($userId);
$wallets = getWallets($userId);
$username = getSession('username');

layout("header", ["title"=>"Quản lý Ví","css"=>["layout/sidebar","pages/user/wallet"]]);

$message = getFlashData("message");
$messageType = getFlashData("message_type");

// Calculate totals by group
$totalDaily = 0; $totalEwallet = 0; $totalTarget = 0;
$groups = ['daily'=>[],'ewallet'=>[],'target'=>[]];
foreach ($wallets as $w) {
    $groups[$w['type']][] = $w;
    $bal = getWalletBalance($w['id'], $userId);
    if ($w['type'] === 'daily') $totalDaily += $bal;
    elseif ($w['type'] === 'ewallet') $totalEwallet += $bal;
    else $totalTarget += $bal;
}
$legacyBalance = getCurrentBalance($userId) - $totalDaily - $totalEwallet - $totalTarget;
$totalDaily += max(0, $legacyBalance);
$grandTotal = $totalDaily + $totalEwallet + $totalTarget;
$noanim = isset($_GET['noanim']) ? 1 : 0;

// Transfer history from wallet_transfer
$pairedTransfers = [];
$rawTransfers = getAll(
    "SELECT wt.*,
            f.icon AS from_icon, f.name AS from_name,
            t.icon AS to_icon, t.name AS to_name
     FROM wallet_transfer wt
     LEFT JOIN wallet f ON f.id = wt.from_wallet_id
     LEFT JOIN wallet t ON t.id = wt.to_wallet_id
     WHERE wt.user_id = :uid
     ORDER BY wt.created_at DESC LIMIT 20",
    ['uid' => $userId]
) ?: [];
foreach ($rawTransfers as $r) {
    $pairedTransfers[] = [
        'price' => $r['amount'],
        'note' => $r['description'],
        'date' => $r['created_at'],
        'from_name' => $r['from_name'] ? ($r['from_icon'].' '.$r['from_name']) : 'Đã xóa',
        'to_name' => $r['to_name'] ? ($r['to_icon'].' '.$r['to_name']) : 'Đã xóa',
        'from_id' => $r['from_wallet_id'],
        'to_id' => $r['to_wallet_id'],
    ];
}
?>
<div class="app-container">
    <?php layout("sidebar", ["view"=>$view]); ?>
    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div><span class="subtitle">DIGITAL CURATOR</span><h1>Quản lý Ví</h1></div>
            </div>
            <div class="header-right"><div class="user-box">👤 <?= htmlspecialchars($username) ?></div></div>
        </header>

        <div class="page-content wallet-page">
            <?php if (!empty($message)) echo showMessage($message, $messageType); ?>

            <!-- Hero Summary -->
            <div class="wallet-hero">
                <div class="wallet-hero-total">
                    <div class="wallet-hero-label">Tổng tài sản</div>
                    <div class="wallet-hero-amount"><span id="walletHeroAmount">0</span><span class="wallet-hero-currency"> đ</span></div>
                    <div class="wallet-hero-sub">
                        <?= count($wallets) ?> ví · 
                        <?= number_format($totalDaily,0,',','.') ?>đ chi tiêu · 
                        <?= number_format($totalEwallet,0,',','.') ?>đ điện tử · 
                        <?= number_format($totalTarget,0,',','.') ?>đ mục tiêu
                    </div>
                </div>
                <div class="wallet-hero-actions">
                    <button class="wallet-btn-add" onclick="openModal()">＋ Thêm ví</button>
                </div>
            </div>

            <?php
            $groupMeta = [
                'daily' => ['label' => 'Ví Chi Tiêu Hằng Ngày', 'icon' => '💳', 'dot' => 'daily', 'total' => $totalDaily],
                'ewallet' => ['label' => 'Ví Điện Tử', 'icon' => '📱', 'dot' => 'ewallet', 'total' => $totalEwallet],
                'target' => ['label' => 'Quỹ / Mục Tiêu', 'icon' => '🎯', 'dot' => 'target', 'total' => $totalTarget],
            ];
            foreach (['daily','ewallet','target'] as $gtype):
                if (empty($groups[$gtype])) continue;
                $meta = $groupMeta[$gtype];
            ?>
            <div class="wallet-group">
                <div class="wallet-group-header">
                    <span class="wallet-group-dot <?= $meta['dot'] ?>"></span>
                    <span class="wallet-group-title"><?= $meta['icon'] ?> <?= $meta['label'] ?></span>
                    <span class="wallet-group-count <?= $meta['dot'] ?>"><?= count($groups[$gtype]) ?> ví</span>
                    <span class="wallet-group-total" style="color:<?= $meta['total']>=0?'#059669':'#dc2626' ?>;"><?= number_format($meta['total'],0,',','.') ?>đ</span>
                </div>
                <div class="wallet-list">
                    <?php foreach ($groups[$gtype] as $w):
                        $bal = getWalletBalance($w['id'], $userId);
                        $balClass = $bal >= 0 ? 'pos' : 'neg';
                    ?>
                    <div class="wallet-card type-<?= $gtype ?>" data-wallet-id="<?= $w['id'] ?>">
                        <div class="wallet-card-icon-wrap"><?= htmlspecialchars($w['icon'] ?? '💰') ?></div>
                        <div class="wallet-card-body">
                            <div class="wallet-card-top">
                                <span class="wallet-card-name"><?= htmlspecialchars($w['name']) ?></span>
                                <?php if ($w['is_default']): ?><span class="wallet-card-badge">Mặc định</span><?php endif; ?>
                            </div>
                            <div class="wallet-card-balance <?= $balClass ?>"><?= number_format($bal, 0, ',', '.') ?>đ</div>
                        </div>
                        <div class="wallet-card-actions">
                            <button onclick="openModal({id:'<?= $w['id'] ?>',name:'<?= htmlspecialchars($w['name'], ENT_QUOTES) ?>',icon:'<?= htmlspecialchars($w['icon'] ?? "💰", ENT_QUOTES) ?>',type:'<?= $w['type'] ?>'})" title="Sửa"><span class="material-symbols-outlined" style="font-size:18px">edit</span></button>
                            <?php if (!$w['is_default']): ?>
                            <button class="btn-del" onclick="confirmDelete(<?= $w['id'] ?>,'<?= htmlspecialchars($w['name'], ENT_QUOTES) ?>')" title="Xóa"><span class="material-symbols-outlined" style="font-size:18px">delete</span></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($wallets)): ?>
            <div class="wallet-empty">
                <div class="wallet-empty-icon"><span class="material-symbols-outlined" style="font-size:56px">account_balance_wallet</span></div>
                <div class="wallet-empty-text">Chưa có ví nào</div>
                <div class="wallet-empty-sub">Nhấn "Thêm ví" để bắt đầu quản lý tài chính của bạn</div>
            </div>
            <?php endif; ?>

            <!-- ═══ Transfer Section ═══ -->
            <div class="tf-section">
                <div class="tf-section-header" onclick="toggleCollapse(this)">
                    <div class="tf-section-header-left">
                        <span class="material-symbols-outlined tf-section-icon">swap_horiz</span>
                        <span class="tf-section-title">Chuyển tiền giữa các ví</span>
                    </div>
                    <span class="material-symbols-outlined tf-section-arrow">expand_more</span>
                </div>
                <div class="tf-section-body">
                    <form method="POST" action="?template=user&action=transfer" class="tf-form">
                        <div class="tf-row">
                            <div class="tf-field">
                                <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">output</span> Từ ví</label>
                                <select name="from_wallet" required>
                                    <option value="">-- Chọn --</option>
                                    <?php foreach ($wallets as $w):
                                        $bal = getWalletBalance($w['id'], $userId);
                                    ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['icon']??'💰') ?> <?= htmlspecialchars($w['name']) ?> (<?= number_format($bal,0,',','.') ?>đ)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="tf-arrow-col">
                                <span class="material-symbols-outlined tf-arr-icon">arrow_forward</span>
                            </div>
                            <div class="tf-field">
                                <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">input</span> Đến ví</label>
                                <select name="to_wallet" required>
                                    <option value="">-- Chọn --</option>
                                    <?php foreach ($wallets as $w):
                                        $typeL = ['daily'=>'Chi tiêu','ewallet'=>'Điện tử','target'=>'Mục tiêu'][$w['type']] ?? '';
                                    ?>
                                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['icon']??'💰') ?> <?= htmlspecialchars($w['name']) ?> (<?= $typeL ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="tf-row">
                            <div class="tf-field" style="flex:1;">
                                <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">payments</span> Số tiền</label>
                                <div style="position:relative;">
                                    <input type="number" name="amount" min="1000" step="1000" required placeholder="0">
                                    <span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-weight:700;font-size:13px;color:#94a3b8;pointer-events:none;">đ</span>
                                </div>
                            </div>
                            <div class="tf-field" style="flex:2;">
                                <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">edit_note</span> Nội dung</label>
                                <input type="text" name="note" placeholder="Ghi chú..." maxlength="200">
                            </div>
                            <div class="tf-field" style="flex:0 0 auto;display:flex;align-items:flex-end;">
                                <button type="submit" name="transfer_btn" class="tf-submit-btn">
                                    <span class="material-symbols-outlined" style="font-size:18px">send_money</span>
                                    Chuyển
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ═══ Transfer History ═══ -->
            <?php if (!empty($pairedTransfers)): ?>
            <div class="tf-section open">
                <div class="tf-section-header" onclick="toggleCollapse(this)">
                    <div class="tf-section-header-left">
                        <span class="material-symbols-outlined tf-section-icon">history</span>
                        <span class="tf-section-title">Lịch sử chuyển tiền</span>
                        <span class="tf-section-count"><?= count($pairedTransfers) ?></span>
                    </div>
                    <span class="material-symbols-outlined tf-section-arrow">expand_less</span>
                </div>
                <div class="tf-section-body">
                    <div class="tf-history">
                        <?php foreach ($pairedTransfers as $pt): ?>
                        <div class="tf-h-row">
                            <div class="tf-h-route">
                                <span class="tf-h-wallet from"><?= htmlspecialchars($pt['from_name']) ?></span>
                                <span class="material-symbols-outlined tf-h-arrow">arrow_forward</span>
                                <span class="tf-h-wallet to"><?= htmlspecialchars($pt['to_name']) ?></span>
                            </div>
                            <div class="tf-h-info">
                                <span class="tf-h-note"><?= htmlspecialchars($pt['note'] ?: 'Chuyển khoản') ?></span>
                                <span class="tf-h-date"><?= date('d/m/Y', strtotime($pt['date'])) ?></span>
                            </div>
                            <div class="tf-h-amount"><?= number_format($pt['price'],0,',','.') ?>đ</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /.page-content -->
    </main>
</div>

<!-- Modal -->
<div class="wallet-modal-overlay" id="walletModal">
    <div class="wallet-modal">
        <div class="wallet-modal-title" id="modalTitle">➕ Thêm ví mới</div>
        <form method="POST" action="?template=user&action=wallet" id="walletForm">
            <input type="hidden" name="id" id="walletId" value="">
            <input type="hidden" name="ajax" value="1">
            <div class="wallet-modal-field">
                <label>Tên ví</label>
                <input type="text" name="name" id="walletName" placeholder="VD: Tiền mặt, Momo, Quỹ tiết kiệm..." required maxlength="50">
            </div>
            <div class="wallet-modal-field">
                <label>Biểu tượng</label>
                <input type="text" name="icon" id="walletIcon" placeholder="💰" maxlength="10" value="💰">
            </div>
            <div class="wallet-modal-field">
                <label>Loại ví</label>
                <div id="walletTypeSelector">
                    <?php
                    $typeOptions = [
                        ['daily', '💳', 'Ví Chi Tiêu Hằng Ngày', 'Tiền mặt, tài khoản ngân hàng — dùng chi tiêu hàng ngày'],
                        ['ewallet', '📱', 'Ví Điện Tử', 'Momo, ZaloPay, ShopeePay — hạn chế lạm dụng'],
                        ['target', '🎯', 'Quỹ / Mục Tiêu', 'Tiết kiệm, khẩn cấp — chỉ dùng khi thực sự cần'],
                    ];
                    foreach ($typeOptions as $i => $t):
                    ?>
                    <label class="type-option" id="typeOpt<?= $t[0] ?>" onclick="document.getElementById('walletType<?= $t[0] ?>').checked=true;document.querySelectorAll('.type-option').forEach(el=>el.classList.remove('active'));this.classList.add('active');">
                        <input type="radio" name="type" id="walletType<?= $t[0] ?>" value="<?= $t[0] ?>" <?= $i===0?'checked':'' ?> style="display:none;">
                        <span class="type-option-icon"><?= $t[1] ?></span>
                        <div>
                            <div class="type-option-text"><?= $t[2] ?></div>
                            <div class="type-option-desc"><?= $t[3] ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="wallet-modal-actions">
                <button type="button" class="btn-ghost" onclick="closeModal()">Hủy</button>
                <button type="submit" name="save_wallet" class="btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
  var noanim = <?= $noanim ? 'true' : 'false' ?>;
  var heroEl = document.getElementById('walletHeroAmount');
  var grandTotal = <?= $grandTotal ?>;

  if (noanim) {
    if (heroEl) heroEl.textContent = grandTotal.toLocaleString('vi-VN');
    document.querySelectorAll('.wallet-card').forEach(function(c){ c.classList.add('reveal'); });
    document.querySelectorAll('.tf-h-row').forEach(function(r){ r.classList.add('reveal'); });
  } else {
    if (heroEl) {
      var duration = 1200;
      var start = performance.now();
      function step(now) {
        var pct = Math.min((now - start) / duration, 1);
        var eased = 1 - Math.pow(1 - pct, 3);
        heroEl.textContent = Math.round(grandTotal * eased).toLocaleString('vi-VN');
        if (pct < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }
    (function() {
      var cards = document.querySelectorAll('.wallet-card');
      if (cards.length) {
        cards.forEach(function(card, i) {
          setTimeout(function() { card.classList.add('reveal'); }, 150 + i * 100);
        });
      }
    })();
    (function() {
      var rows = document.querySelectorAll('.tf-h-row');
      if (rows.length) {
        rows.forEach(function(row, i) {
          setTimeout(function() { row.classList.add('reveal'); }, 100 + i * 60);
        });
      }
    })();
  }
})();

function openModal(data) {
    var m = document.getElementById('walletModal');
    m.classList.add('show');
    document.getElementById('walletId').value = data ? data.id : '';
    document.getElementById('walletName').value = data ? data.name : '';
    document.getElementById('walletIcon').value = data ? data.icon : '💰';
    if (data) {
        var type = data.type || 'daily';
        var radio = document.getElementById('walletType' + type);
        if (radio) radio.checked = true;
        document.querySelectorAll('.type-option').forEach(function(el){ el.classList.remove('active'); });
        var opt = document.getElementById('typeOpt' + type);
        if (opt) opt.classList.add('active');
    } else {
        document.getElementById('walletTypedaily').checked = true;
        document.querySelectorAll('.type-option').forEach(function(el){ el.classList.remove('active'); });
        var def = document.getElementById('typeOptdaily');
        if (def) def.classList.add('active');
    }
    document.getElementById('modalTitle').textContent = data ? '✏️ Sửa ví' : '➕ Thêm ví mới';
}
function closeModal() { document.getElementById('walletModal').classList.remove('show'); }

// Smooth collapsible toggle
function toggleCollapse(header) {
    var section = header.parentElement;
    section.classList.toggle('open');
}

function updateHeroTotal(total) {
    var el = document.getElementById('walletHeroAmount');
    if (el) el.textContent = total.toLocaleString('vi-VN');
}

function generateWalletCardHtml(w, bal) {
    var esc = function(s) { return String(s).replace(/'/g, "\\'"); };
    var isDefault = w.is_default || false;
    var balClass = bal >= 0 ? 'pos' : 'neg';
    var name = esc(w.name);
    var icon = esc(w.icon || '💰');
    var delBtn = isDefault ? '' : '<button class="btn-del" onclick="confirmDelete(' + w.id + ',\'' + name + '\')" title="Xóa"><span class="material-symbols-outlined" style="font-size:18px">delete</span></button>';
    var badge = isDefault ? '<span class="wallet-card-badge">Mặc định</span>' : '';
    return '<div class="wallet-card type-' + w.type + ' reveal" data-wallet-id="' + w.id + '">'
        + '<div class="wallet-card-icon-wrap">' + (w.icon || '💰') + '</div>'
        + '<div class="wallet-card-body">'
        + '<div class="wallet-card-top"><span class="wallet-card-name">' + w.name + '</span>' + badge + '</div>'
        + '<div class="wallet-card-balance ' + balClass + '">' + bal.toLocaleString('vi-VN') + 'đ</div>'
        + '</div>'
        + '<div class="wallet-card-actions">'
        + '<button onclick="openModal({id:\'' + w.id + '\',name:\'' + name + '\',icon:\'' + icon + '\',type:\'' + w.type + '\'})" title="Sửa"><span class="material-symbols-outlined" style="font-size:18px">edit</span></button>'
        + delBtn
        + '</div>'
        + '</div>';
}

function recalcGroupTotals() {
    ['daily','ewallet','target'].forEach(function(type) {
        var dot = document.querySelector('.wallet-group-dot.' + type);
        if (!dot) return;
        var group = dot.closest('.wallet-group');
        if (!group) return;
        var cards = group.querySelectorAll('.wallet-card');
        var total = 0;
        cards.forEach(function(c) {
            var el = c.querySelector('.wallet-card-balance');
            if (!el) return;
            var val = parseInt(el.textContent.replace(/[^0-9]/g, '')) || 0;
            if (el.classList.contains('neg')) val = -val;
            total += val;
        });
        var cnt = group.querySelector('.wallet-group-count');
        if (cnt) cnt.textContent = cards.length + ' ví';
        var ttl = group.querySelector('.wallet-group-total');
        if (ttl) {
            ttl.textContent = total.toLocaleString('vi-VN') + 'đ';
            ttl.style.color = total >= 0 ? '#059669' : '#dc2626';
        }
    });
    var hSub = document.querySelector('.wallet-hero-sub');
    if (hSub) {
        var wCount = document.querySelectorAll('.wallet-card').length;
        var dTotal = 0, eTotal = 0, tTotal = 0;
        document.querySelectorAll('.wallet-card').forEach(function(c) {
            var el = c.querySelector('.wallet-card-balance');
            if (!el) return;
            var val = parseInt(el.textContent.replace(/[^0-9]/g, '')) || 0;
            if (el.classList.contains('neg')) val = -val;
            if (c.classList.contains('type-daily')) dTotal += val;
            else if (c.classList.contains('type-ewallet')) eTotal += val;
            else if (c.classList.contains('type-target')) tTotal += val;
        });
        hSub.textContent = wCount + ' ví · ' + dTotal.toLocaleString('vi-VN') + 'đ chi tiêu · ' + eTotal.toLocaleString('vi-VN') + 'đ điện tử · ' + tTotal.toLocaleString('vi-VN') + 'đ mục tiêu';
    }
}

function showToast(msg, type) {
    var existing = document.querySelector('.wallet-toast');
    if (existing) existing.remove();
    var t = document.createElement('div');
    t.className = 'wallet-toast';
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:14px 24px;border-radius:14px;font-weight:600;font-size:14px;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.2);animation:slideInRight .35s ease forwards;max-width:400px;';
    t.style.background = type === 'success' ? 'linear-gradient(135deg,#10b981,#059669)' : 'linear-gradient(135deg,#ef4444,#dc2626)';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function() {
        t.style.animation = 'slideOutRight .3s ease forwards';
        setTimeout(function() { t.remove(); }, 320);
    }, 3000);
}

// AJAX save
document.getElementById('walletForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    var origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳';
    var formData = new FormData(this);
    formData.set('ajax', '1');
    formData.set('save_wallet', '1');
    fetch('?template=user&action=wallet', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            closeModal();
            var d = res.data;
            if (d.wallet && d.wallet.id) {
                var card = document.querySelector('.wallet-card[data-wallet-id="' + d.wallet.id + '"]');
                if (card) {
                    card.querySelector('.wallet-card-icon-wrap').textContent = d.wallet.icon;
                    card.querySelector('.wallet-card-name').textContent = d.wallet.name;
                    card.className = 'wallet-card type-' + d.wallet.type + ' reveal';
                    var balEl = card.querySelector('.wallet-card-balance');
                    if (d.wallet.balance !== undefined) {
                        balEl.textContent = d.wallet.balance.toLocaleString('vi-VN') + 'đ';
                        balEl.className = 'wallet-card-balance ' + (d.wallet.balance >= 0 ? 'pos' : 'neg');
                    }
                    var dot = document.querySelector('.wallet-group-dot.' + d.wallet.type);
                    if (dot) {
                        var targetList = dot.closest('.wallet-group').querySelector('.wallet-list');
                        if (targetList && targetList !== card.parentNode) card.parentNode.removeChild(card);
                        if (targetList) targetList.insertBefore(card, targetList.firstChild);
                    }
                } else {
                    var list = null;
                    var dot = document.querySelector('.wallet-group-dot.' + d.wallet.type);
                    if (dot) list = dot.closest('.wallet-group').querySelector('.wallet-list');
                    if (list) {
                        list.insertAdjacentHTML('afterbegin', generateWalletCardHtml(d.wallet, d.wallet.balance !== undefined ? d.wallet.balance : 0));
                    } else {
                        location.reload(); return;
                    }
                }
            }
            recalcGroupTotals();
            if (d.grand_total !== undefined) updateHeroTotal(d.grand_total);
            showToast(res.message, 'success');
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(function() { showToast('Lỗi kết nối.', 'error'); })
    .finally(function() { btn.disabled = false; btn.textContent = origText; });
});

// AJAX delete
function confirmDelete(id, name) {
    if (!confirm('Xóa ví "' + name + '"?')) return;
    var formData = new FormData();
    formData.set('delete_wallet', '1');
    formData.set('id', id);
    formData.set('ajax', '1');
    fetch('?template=user&action=wallet', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            var d = res.data;
            var card = document.querySelector('.wallet-card[data-wallet-id="' + id + '"]');
            if (card) {
                card.style.transition = 'all .4s';
                card.style.opacity = '0';
                card.style.transform = 'scale(.9)';
                setTimeout(function() { card.remove(); recalcGroupTotals(); }, 400);
            }
            if (d.grand_total !== undefined) updateHeroTotal(d.grand_total);
            showToast(res.message, 'success');
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(function() { showToast('Lỗi kết nối.', 'error'); });
}

document.getElementById('walletModal').addEventListener('click',function(e){ if(e.target===this) closeModal(); });
</script>

<?php layout("footer",["js"=>["pages/sidebar"]]); ?>
