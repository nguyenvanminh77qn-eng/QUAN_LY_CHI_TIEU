<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$view = 'savings';
$loginToken = getSession('loginToken');
if (empty($loginToken)) { setMessage("Bạn phải đăng nhập","error"); redirect("?template=auth&action=login.view"); }
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền","error"); redirect("?template=admin&action=dashboard"); }

$userId = getSession('id');
createDefaultWallets($userId);
$targetWallets = getAll("SELECT * FROM wallet WHERE user_id = :uid AND type = 'target' ORDER BY id", ['uid'=>$userId]) ?: [];
$dailyWallets = getAll("SELECT id,name,icon FROM wallet WHERE user_id = :uid AND type = 'daily' ORDER BY id", ['uid'=>$userId]) ?: [];
$username = getSession('username');

// Pre-query transactions for ALL target wallets
$allWalletTx = [];
foreach ($targetWallets as $tw) {
    $txs = getAll("SELECT t.*, c.name as cat_name, c.icon as cat_icon FROM transaction t LEFT JOIN category c ON t.category_id = c.id WHERE t.wallet_id = :wid AND t.user_id = :uid AND t.is_archived = 0 ORDER BY t.transaction_date DESC, t.id DESC LIMIT 20", ['wid'=>$tw['id'], 'uid'=>$userId]) ?: [];
    $allWalletTx[$tw['id']] = $txs;
}

layout("header", ["title"=>"Quỹ Tiết Kiệm","css"=>["layout/sidebar","pages/user/savings"]]);

$totalSavings = 0;
foreach ($targetWallets as $tw) {
    $totalSavings += getWalletBalance($tw['id'], $userId);
}

$message = getFlashData("message");
$messageType = getFlashData("message_type");
?>
<div class="app-container">
    <?php layout("sidebar", ["view"=>$view]); ?>
    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button">☰</button>
                <div><span class="subtitle">QUỸ TIẾT KIỆM</span><h1>Quỹ Tiết Kiệm</h1></div>
            </div>
            <div class="header-right"><div class="user-box">👤 <?= htmlspecialchars($username) ?></div></div>
        </header>

        <div class="page-content savings-page">
            <?php if (!empty($message)) echo showMessage($message, $messageType); ?>

            <!-- Hero -->
            <div class="sav-hero">
                <div class="sav-hero-bg"></div>
                <div class="sav-hero-content">
                    <div class="sav-hero-left">
                        <div class="sav-hero-label">TỔNG QUỸ TIẾT KIỆM</div>
                        <div class="sav-hero-amount" id="savHeroAmount">0</div>
                        <div class="sav-hero-sub">
                            <span><?= count($targetWallets) ?> quỹ</span>
                        </div>
                    </div>
                    <div class="sav-hero-right">
                        <a href="?template=user&action=wallet" class="sav-btn-link">Quản lý ví</a>
                    </div>
                </div>
            </div>

            <?php if (empty($targetWallets)): ?>
            <div class="sav-empty">
                <div class="sav-empty-icon"><span class="material-symbols-outlined">savings</span></div>
                <div class="sav-empty-text">Chưa có quỹ tiết kiệm nào</div>
                <div class="sav-empty-sub">Tạo quỹ tại trang Quản lý Ví</div>
                <a href="?template=user&action=wallet" class="sav-empty-btn">Tạo quỹ ngay</a>
            </div>
            <?php else: ?>

            <!-- Fund cards -->
            <div class="sav-cards">
                <?php $i = 0; foreach ($targetWallets as $tw):
                    $bal = getWalletBalance($tw['id'], $userId);
                    $txCount = count($allWalletTx[$tw['id']] ?? []);
                    $i++;
                ?>
                <div class="sav-card-wrapper" style="animation-delay:<?= $i*0.1 ?>s">
                    <div class="sav-card" data-target="<?= $tw['id'] ?>" onclick="toggleFund(<?= $tw['id'] ?>)">
                        <div class="sav-card-left">
                            <div class="sav-card-icon"><?= htmlspecialchars($tw['icon'] ?? '🐷') ?></div>
                            <div class="sav-card-body">
                                <div class="sav-card-name"><?= htmlspecialchars($tw['name']) ?></div>
                                <div class="sav-card-bal <?= $bal>=0?'pos':'neg' ?>"><?= number_format($bal, 0, ',', '.') ?>đ</div>
                            </div>
                        </div>
                        <div class="sav-card-actions">
                            <span class="sav-card-btn deposit-btn" data-target="<?= $tw['id'] ?>" data-name="<?= htmlspecialchars($tw['name'], ENT_QUOTES) ?>" onclick="event.stopPropagation();openDeposit(<?= $tw['id'] ?>,'<?= htmlspecialchars($tw['name'], ENT_QUOTES) ?>')"><span class="material-symbols-outlined">add_circle</span></span>
                            <span class="sav-card-btn withdraw-btn" data-target="<?= $tw['id'] ?>" data-name="<?= htmlspecialchars($tw['name'], ENT_QUOTES) ?>" onclick="event.stopPropagation();openWithdraw(<?= $tw['id'] ?>,'<?= htmlspecialchars($tw['name'], ENT_QUOTES) ?>')"><span class="material-symbols-outlined">remove_circle</span></span>
                            <span class="sav-card-arrow" id="savArrow<?= $tw['id'] ?>"><span class="material-symbols-outlined">expand_more</span></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Transaction sections (pre-rendered, toggled via JS) -->
            <?php foreach ($targetWallets as $tw):
                $txs = $allWalletTx[$tw['id']] ?? [];
            ?>
            <div class="sav-tx-section" id="savTxSection<?= $tw['id'] ?>">
                <div class="sav-tx-header">
                    <div class="sav-tx-header-left">
                        <span class="sav-tx-icon"><?= htmlspecialchars($tw['icon'] ?? '🐷') ?></span>
                        <span class="sav-tx-title"><?= htmlspecialchars($tw['name']) ?></span>
                        <span class="sav-tx-count"><?= count($txs) ?> giao dịch</span>
                    </div>
                </div>
                <?php if (empty($txs)): ?>
                <div class="sav-tx-empty">Chưa có giao dịch nào. Hãy gửi tiền vào quỹ!</div>
                <?php else: ?>
                <div class="sav-tx-list">
                    <?php foreach ($txs as $tx):
                        $txType = $tx['type']==='income' ? 'thu' : 'chi';
                        $txColor = $tx['type']==='income' ? '#059669' : '#dc2626';
                        $txSign = $tx['type']==='income' ? '+' : '-';
                    ?>
                    <div class="sav-tx-row">
                        <div class="sav-tx-dot <?= $txType ?>"></div>
                        <div class="sav-tx-cat"><?= htmlspecialchars($tx['cat_icon'] ?? '📦') ?></div>
                        <div class="sav-tx-info">
                            <div class="sav-tx-main"><?= htmlspecialchars($tx['description'] ?: $tx['cat_name'] ?? 'Khác') ?></div>
                            <div class="sav-tx-date"><?= date('d/m/Y', strtotime($tx['transaction_date'])) ?></div>
                        </div>
                        <div class="sav-tx-amount" style="color:<?= $txColor ?>"><?= $txSign . number_format($tx['price'], 0, ',', '.') ?>đ</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Deposit Modal -->
<div class="sav-modal-overlay" id="depositModal">
    <div class="sav-modal">
        <div class="sav-modal-header">
            <span class="sav-modal-icon"><span class="material-symbols-outlined">add_circle</span></span>
            <span class="sav-modal-title">Gửi tiền vào quỹ</span>
            <button class="sav-modal-close" onclick="closeModal('depositModal')">&times;</button>
        </div>
        <form method="POST" action="?template=user&action=savings">
            <input type="hidden" name="target_id" id="depTargetId" value="">
            <div class="sav-modal-body">
                <div class="sav-mf">
                    <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">account_balance</span> Quỹ tiết kiệm</label>
                    <input type="text" id="depTargetName" readonly style="background:#f8fafc;font-weight:700;color:#0f172a;">
                </div>
                <div class="sav-mf">
                    <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">output</span> Nguồn tiền</label>
                    <select name="source_wallet" id="depSourceWallet" required>
                        <option value="">-- Chọn ví --</option>
                        <?php foreach ($dailyWallets as $dw):
                            $bal = getWalletBalance($dw['id'], $userId);
                        ?>
                        <option value="<?= $dw['id'] ?>" data-bal="<?= $bal ?>"><?= htmlspecialchars($dw['icon']??'💵') ?> <?= htmlspecialchars($dw['name']) ?> (<?= number_format($bal,0,',','.') ?>đ)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="sav-mf-balance" id="depBalDisplay" style="display:none;margin-top:6px;font-size:12px;color:#64748b;">
                        <span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">account_balance_wallet</span>
                        Số dư: <strong id="depBalValue">0</strong>đ
                    </div>
                </div>
                <div class="sav-mf">
                    <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">payments</span> Số tiền gửi</label>
                    <div class="sav-mf-amount-wrap">
                        <input type="number" name="amount" min="1000" step="1000" required placeholder="Nhập số tiền..." id="depAmount">
                        <span class="sav-mf-unit">đ</span>
                    </div>
                </div>
            </div>
            <div class="sav-modal-actions">
                <button type="button" class="sav-btn sav-btn-ghost" onclick="closeModal('depositModal')">Hủy</button>
                <button type="submit" name="savings_deposit" class="sav-btn sav-btn-primary">Gửi tiền</button>
            </div>
        </form>
    </div>
</div>

<!-- Withdraw Modal -->
<div class="sav-modal-overlay" id="withdrawModal">
    <div class="sav-modal">
        <div class="sav-modal-header">
            <span class="sav-modal-icon" style="color:#dc2626;"><span class="material-symbols-outlined">remove_circle</span></span>
            <span class="sav-modal-title">Rút tiền từ quỹ</span>
            <button class="sav-modal-close" onclick="closeModal('withdrawModal')">&times;</button>
        </div>
        <form method="POST" action="?template=user&action=savings">
            <input type="hidden" name="target_id" id="wdrTargetId" value="">
            <div class="sav-modal-body">
                <div class="sav-mf">
                    <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">account_balance</span> Quỹ tiết kiệm</label>
                    <input type="text" id="wdrTargetName" readonly style="background:#f8fafc;font-weight:700;color:#0f172a;">
                </div>
                <div class="sav-mf">
                    <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">input</span> Nhận vào ví</label>
                    <select name="dest_wallet" id="wdrDestWallet" required>
                        <option value="">-- Chọn ví --</option>
                        <?php foreach ($dailyWallets as $dw):
                            $bal = getWalletBalance($dw['id'], $userId);
                        ?>
                        <option value="<?= $dw['id'] ?>" data-bal="<?= $bal ?>"><?= htmlspecialchars($dw['icon']??'💵') ?> <?= htmlspecialchars($dw['name']) ?> (<?= number_format($bal,0,',','.') ?>đ)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sav-mf">
                    <label><span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">payments</span> Số tiền rút</label>
                    <div class="sav-mf-amount-wrap">
                        <input type="number" name="amount" min="1000" step="1000" required placeholder="Nhập số tiền..." id="wdrAmount">
                        <span class="sav-mf-unit">đ</span>
                    </div>
                </div>
            </div>
            <div class="sav-modal-actions">
                <button type="button" class="sav-btn sav-btn-ghost" onclick="closeModal('withdrawModal')">Hủy</button>
                <button type="submit" name="savings_withdraw" class="sav-btn sav-btn-danger">Rút tiền</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var heroEl = document.getElementById('savHeroAmount');
    if (heroEl) {
        var target = <?= $totalSavings ?>;
        var duration = 1400;
        var start = performance.now();
        function step(now) {
            var pct = Math.min((now - start) / duration, 1);
            var eased = 1 - Math.pow(1 - pct, 3);
            var current = Math.round(target * eased);
            heroEl.textContent = current.toLocaleString('vi-VN') + ' đ';
            if (pct < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }
})();

var currentOpenFund = null;

function slideSection(section, open, callback) {
    // If stuck animating, force-complete first
    if (section.getAttribute('data-animating') === '1') {
        clearTimeout(section._animTimer);
        section.style.display = open ? 'block' : '';
        section.style.height = '';
        section.style.overflow = '';
        section.removeAttribute('data-animating');
        if (callback) callback();
        return;
    }
    section.setAttribute('data-animating', '1');

    function done() {
        section.removeEventListener('transitionend', done);
        clearTimeout(section._animTimer);
        if (open) {
            section.style.height = '';
            section.style.overflow = '';
        } else {
            section.style.display = '';
            section.style.height = '';
            section.style.overflow = '';
        }
        section.removeAttribute('data-animating');
        if (callback) callback();
    }

    if (open) {
        section.style.display = 'block';
        section.style.overflow = 'hidden';
        section.style.height = '0';
        section.offsetHeight;
        section.style.height = section.scrollHeight + 'px';
    } else {
        section.style.display = 'block';
        section.style.overflow = 'hidden';
        var h = section.scrollHeight;
        section.style.height = h + 'px';
        section.offsetHeight;
        section.style.height = '0';
    }

    section._animTimer = setTimeout(done, 500);
    section.addEventListener('transitionend', done, { once: true });
}

function toggleFund(id) {
    var section = document.getElementById('savTxSection' + id);
    if (!section) return;

    // Clicking same one → close it
    if (currentOpenFund === id) {
        section.classList.remove('sav-tx-open');
        setArrow(id, 'expand_more');
        document.querySelector('.sav-card[data-target="' + id + '"]')?.classList.remove('active');
        slideSection(section, false);
        currentOpenFund = null;
        return;
    }

    function doOpen() {
        slideSection(section, true, function() {
            section.classList.add('sav-tx-open');
        });
        setArrow(id, 'expand_less');
        document.querySelector('.sav-card[data-target="' + id + '"]')?.classList.add('active');
        currentOpenFund = id;
    }

    // If switching from another fund: force previous closed immediately (no wait)
    if (currentOpenFund !== null) {
        var prev = document.getElementById('savTxSection' + currentOpenFund);
        if (prev) {
            clearTimeout(prev._animTimer);
            prev.classList.remove('sav-tx-open');
            prev.style.display = '';
            prev.style.height = '';
            prev.style.overflow = '';
            prev.removeAttribute('data-animating');
            setArrow(currentOpenFund, 'expand_more');
            document.querySelector('.sav-card[data-target="' + currentOpenFund + '"]')?.classList.remove('active');
        }
    }
    doOpen();
}

function setArrow(id, icon) {
    var arrow = document.getElementById('savArrow' + id);
    if (arrow) arrow.querySelector('span').textContent = icon;
}

// Modal functions
function openDeposit(id, name) {
    document.getElementById('depTargetId').value = id;
    document.getElementById('depTargetName').value = name;
    document.getElementById('depositModal').classList.add('show');
    var sel = document.getElementById('depSourceWallet');
    sel.value = '';
    document.getElementById('depBalDisplay').style.display = 'none';
}
function openWithdraw(id, name) {
    document.getElementById('wdrTargetId').value = id;
    document.getElementById('wdrTargetName').value = name;
    document.getElementById('withdrawModal').classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
// Balance preview on wallet select
var depSel = document.getElementById('depSourceWallet');
if (depSel) {
    depSel.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var display = document.getElementById('depBalDisplay');
        if (opt && opt.value) {
            var bal = parseInt(opt.getAttribute('data-bal')) || 0;
            document.getElementById('depBalValue').textContent = bal.toLocaleString('vi-VN');
            display.style.display = 'block';
        } else {
            display.style.display = 'none';
        }
    });
}
// Close on overlay click
document.querySelectorAll('.sav-modal-overlay').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
});
</script>
<?php layout("footer",["js"=>["pages/sidebar"]]); ?>
