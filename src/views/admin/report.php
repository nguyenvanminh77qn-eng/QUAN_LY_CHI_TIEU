<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

if (empty(getSession('loginToken'))) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'admin') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=user&action=dashboard");
}

layout("header", [
    "title"  => "Báo cáo tháng",
    "css"    => ["layout/sidebar", "pages/admin/theme"]
]);

$view = 'report';

$message = getFlashData("message");
$message_type = getFlashData("message_type");

$users = getAll("SELECT id, username, email FROM user WHERE role = 'user' ORDER BY username ASC");
$now   = new DateTime();
$month = (int)$now->format('m');
$year  = (int)$now->format('Y');
?>
<style>
.report-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; margin-bottom: 20px; }
.report-card h2 { margin: 0 0 12px 0; font-size: 15px; font-weight: 700; color: var(--text-primary); }
.report-card p { margin: 0 0 16px 0; font-size: 13px; }
.report-label { display: block; font-size: 11.5px; font-weight: 600; color: var(--text-secondary); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
.report-select { padding: 8px 12px; border: 1.5px solid var(--border-color); border-radius: 6px; background: var(--card-bg); color: var(--text-primary); font-size: 13px; font-family: inherit; outline: none; }
.report-select:focus { border-color: #d4a843; box-shadow: 0 0 0 3px rgba(212,168,67,0.1); }
.report-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: inherit; transition: transform 0.15s, box-shadow 0.15s; }
.report-btn:hover { transform: translateY(-1px); }
.report-btn:active { transform: scale(0.97); }
.report-btn-gold { background: linear-gradient(135deg, #b8922e, #d4a843); color: #0d0d0d; box-shadow: 0 3px 12px rgba(212,168,67,0.2); }
.report-btn-outline { background: transparent; color: #d4a843; border: 1.5px solid #d4a843; }
.report-btn-outline:hover { background: rgba(212,168,67,0.1); }
.report-btn-sm { padding: 6px 14px; font-size: 12px; }
.report-table { width: 100%; border-collapse: collapse; }
.report-table th { text-align: left; padding: 10px 12px; border-bottom: 2px solid var(--border-color); font-size: 11.5px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.report-table td { padding: 10px 12px; border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-primary); }
.report-table tr:hover td { background: var(--hover-bg); }
[data-theme="light"] .report-btn-gold { color: #fff; }
</style>

<div class="app-container">
    <?php layout("sidebar_admin"); ?>
    <main class="main-content">
        <div class="page-header">
            <h1>Báo cáo tài chính tháng</h1>
            <p style="color:var(--text-secondary);font-size:13px">Tạo và gửi báo cáo PDF cho người dùng</p>
        </div>

        <?php if(!empty($message)) echo showMessage($message, $message_type); ?>

        <!-- Shared month/year + send all -->
        <div class="report-card" style="display:flex;gap:16px;align-items:end;flex-wrap:wrap">
            <div>
                <label class="report-label">Tháng</label>
                <select id="sharedMonth" class="report-select" style="width:90px">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="report-label">Năm</label>
                <select id="sharedYear" class="report-select" style="width:100px">
                    <?php for ($y = $year - 2; $y <= $year; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <button type="button" class="report-btn report-btn-gold" onclick="confirmSendAll()">Gửi cho tất cả</button>
            </div>
        </div>

        <div class="report-card">
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
                <button type="button" class="report-btn report-btn-outline report-btn-sm" onclick="document.querySelectorAll('.user-checkbox').forEach(c=>c.checked=true)">Chọn tất cả</button>
                <button type="button" class="report-btn report-btn-outline report-btn-sm" onclick="document.querySelectorAll('.user-checkbox').forEach(c=>c.checked=false)">Bỏ chọn</button>
                <button type="button" class="report-btn report-btn-gold" onclick="confirmSendSelected()">Gửi cho người đã chọn</button>
            </div>

        <?php if (!empty($users)): ?>
            <div style="overflow-x:auto">
                <table class="report-table">
                    <thead>
                        <tr><th style="width:36px"><input type="checkbox" id="selectAllCheckbox" onchange="document.querySelectorAll('.user-checkbox').forEach(c=>c.checked=this.checked)"></th><th>#</th><th>Tên</th><th>Email</th><th style="text-align:center">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($users as $u): ?>
                        <tr>
                            <td><input type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" class="user-checkbox" form="sendSelectedForm"></td>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td style="color:var(--text-secondary)"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="text-align:center">
                                <button type="button" class="report-btn report-btn-outline report-btn-sm" onclick="previewOne(<?= $u['id'] ?>)">PDF</button>
                                <button type="button" class="report-btn report-btn-gold report-btn-sm" style="margin-left:6px" onclick="confirmSendOne(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">Gửi</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        </div>

        <!-- Hidden send_all form -->
        <form method="POST" action="" id="sendAllForm" style="display:none">
            <input type="hidden" name="action" value="send_all">
            <input type="hidden" name="month" id="sendAllMonth">
            <input type="hidden" name="year" id="sendAllYear">
        </form>

        <!-- Hidden send_one form -->
        <form method="POST" action="" id="sendOneForm" style="display:none">
            <input type="hidden" name="action" value="send_one">
            <input type="hidden" name="user_id" id="sendOneUserId">
            <input type="hidden" name="month" id="sendOneMonth">
            <input type="hidden" name="year" id="sendOneYear">
        </form>

        <!-- Hidden send_selected form -->
        <form method="POST" action="" id="sendSelectedForm">
            <input type="hidden" name="action" value="send_selected">
            <input type="hidden" name="month" id="sendSelectedMonth">
            <input type="hidden" name="year" id="sendSelectedYear">
        </form>

        <!-- Hidden preview form -->
        <form method="POST" action="" id="previewForm" style="display:none">
            <input type="hidden" name="action" value="preview">
            <input type="hidden" name="user_id" id="previewUserId">
            <input type="hidden" name="month" id="previewMonth">
            <input type="hidden" name="year" id="previewYear">
        </form>
    </main>
</div>
<!-- Confirm send modal -->
<div id="confirmModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99998;align-items:center;justify-content:center;font-family:'Inter','Outfit',sans-serif;backdrop-filter:blur(4px)">
  <div style="background:var(--card-bg,#1a1512);border:1px solid rgba(212,168,67,0.15);border-radius:20px;width:min(400px,92vw);padding:32px 28px 24px;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,0.5);animation:fadeScaleIn 0.25s ease">
    <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;background:rgba(212,168,67,0.15);color:#d4a843">?</div>
    <h2 style="margin:0 0 4px;font-size:16px;font-weight:700;color:var(--text-primary,#f5f0eb)">Xác nhận gửi báo cáo</h2>
    <p id="confirmMsg" style="margin:0 0 6px;font-size:14px;color:var(--text-secondary,#a8a09a);line-height:1.5"></p>
    <p id="confirmDetail" style="margin:0 0 24px;font-size:12px;color:var(--text-secondary,#a8a09a)"></p>
    <div style="display:flex;gap:12px">
      <button onclick="closeConfirmModal()" style="flex:1;padding:11px;border:1.5px solid rgba(255,255,255,0.1);border-radius:10px;background:transparent;color:var(--text-secondary,#a8a09a);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Hủy</button>
      <button id="confirmOkBtn" onclick="confirmAction()" style="flex:1;padding:11px;border:none;border-radius:10px;background:linear-gradient(135deg,#b8922e,#d4a843);color:#0d0d0d;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit">Xác nhận gửi</button>
    </div>
  </div>
</div>
<script>
var pendingForm = null;
function getMY() {
  return { m: document.getElementById('sharedMonth').value, y: document.getElementById('sharedYear').value };
}
function confirmSendAll() {
  var d = getMY();
  document.getElementById('confirmMsg').textContent = 'Gửi báo cáo tháng ' + d.m + '/' + d.y + ' cho tất cả người dùng?';
  document.getElementById('confirmDetail').textContent = 'Hệ thống sẽ tạo PDF và gửi qua email cho từng người.';
  document.getElementById('confirmOkBtn').textContent = 'Xác nhận gửi';
  document.getElementById('sendAllMonth').value = d.m;
  document.getElementById('sendAllYear').value = d.y;
  pendingForm = document.getElementById('sendAllForm');
  document.getElementById('confirmModal').style.display = 'flex';
}
function confirmSendOne(userId, userName) {
  var d = getMY();
  document.getElementById('confirmMsg').textContent = 'Gửi báo cáo tháng ' + d.m + '/' + d.y + ' cho ' + userName + '?';
  document.getElementById('confirmDetail').textContent = 'Hệ thống sẽ tạo PDF và gửi qua email.';
  document.getElementById('confirmOkBtn').textContent = 'Xác nhận gửi';
  document.getElementById('sendOneUserId').value = userId;
  document.getElementById('sendOneMonth').value = d.m;
  document.getElementById('sendOneYear').value = d.y;
  pendingForm = document.getElementById('sendOneForm');
  document.getElementById('confirmModal').style.display = 'flex';
}
function confirmSendSelected() {
  var checked = document.querySelectorAll('.user-checkbox:checked');
  if (checked.length === 0) {
    document.getElementById('confirmModal').style.display = 'flex';
    document.getElementById('confirmMsg').textContent = 'Chưa chọn người dùng nào.';
    document.getElementById('confirmDetail').textContent = 'Vui lòng chọn ít nhất một người dùng trong danh sách.';
    document.getElementById('confirmOkBtn').textContent = 'Đã hiểu';
    pendingForm = 'noop';
    return;
  }
  var d = getMY();
  document.getElementById('confirmMsg').textContent = 'Gửi báo cáo tháng ' + d.m + '/' + d.y + ' cho ' + checked.length + ' người dùng đã chọn?';
  document.getElementById('confirmDetail').textContent = 'Hệ thống sẽ tạo PDF và gửi qua email cho từng người.';
  document.getElementById('confirmOkBtn').textContent = 'Xác nhận gửi';
  document.getElementById('sendSelectedMonth').value = d.m;
  document.getElementById('sendSelectedYear').value = d.y;
  pendingForm = document.getElementById('sendSelectedForm');
  document.getElementById('confirmModal').style.display = 'flex';
}
function previewOne(userId) {
  var d = getMY();
  document.getElementById('previewUserId').value = userId;
  document.getElementById('previewMonth').value = d.m;
  document.getElementById('previewYear').value = d.y;
  document.getElementById('previewForm').submit();
}
function closeConfirmModal() {
  document.getElementById('confirmModal').style.display = 'none';
  pendingForm = null;
}
function confirmAction() {
  document.getElementById('confirmModal').style.display = 'none';
  if (pendingForm && pendingForm !== 'noop') {
    pendingForm.submit();
  }
  pendingForm = null;
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeConfirmModal()});
</script>
<!-- Send result modal -->
<?php
$sid   = trim($_GET['sid'] ?? '');
$fid   = trim($_GET['fid'] ?? '');
$suIds = $sid ? array_map('intval', explode(',', $sid)) : [];
$faIds = $fid ? array_map('intval', explode(',', $fid)) : [];
$su    = [];
$fa    = [];
foreach ($users as $u) {
    if (in_array((int)$u['id'], $suIds)) $su[] = $u['username'];
    if (in_array((int)$u['id'], $faIds)) $fa[] = $u['username'];
}
$total = count($su) + count($fa);
?>
<?php if ($total > 0): ?>
<div id="reportModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99999;display:flex;align-items:center;justify-content:center;font-family:'Inter','Outfit',sans-serif;backdrop-filter:blur(4px)">
  <div style="background:var(--card-bg,#1a1512);border:1px solid rgba(212,168,67,0.15);border-radius:20px;width:min(440px,94vw);max-height:90vh;overflow-y:auto;padding:32px 28px 24px;box-shadow:0 24px 64px rgba(0,0,0,0.5);animation:fadeScaleIn 0.3s ease">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">
      <div style="width:48px;height:48px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;
        <?= !empty($fa) ? 'background:rgba(239,68,68,0.15);color:#f87171' : 'background:rgba(16,185,129,0.15);color:#34d399' ?>">
        <?= !empty($fa) ? '!' : 'OK' ?>
      </div>
      <div>
        <h2 style="margin:0;font-size:17px;font-weight:700;color:var(--text-primary,#f5f0eb)"><?= !empty($fa) ? 'Gửi thất bại' : 'Gửi thành công' ?></h2>
        <p style="margin:2px 0 0;font-size:13px;color:var(--text-secondary,#a8a09a)">Đã xử lý <?= $total ?> người dùng</p>
      </div>
    </div>

    <?php if (!empty($su)): ?>
    <div style="margin-bottom:<?= !empty($fa) ? '16px' : '0' ?>">
      <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#34d399;text-transform:uppercase;letter-spacing:0.04em">Thành công (<?= count($su) ?>)</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px">
        <?php foreach ($su as $name): ?>
          <span style="padding:4px 10px;border-radius:6px;background:rgba(16,185,129,0.1);color:#34d399;font-size:12px;font-weight:600"><?= htmlspecialchars($name) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($fa)): ?>
    <div>
      <p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#f87171;text-transform:uppercase;letter-spacing:0.04em">Thất bại (<?= count($fa) ?>)</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px">
        <?php foreach ($fa as $name): ?>
          <span style="padding:4px 10px;border-radius:6px;background:rgba(239,68,68,0.1);color:#f87171;font-size:12px;font-weight:600"><?= htmlspecialchars($name) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <button onclick="document.getElementById('reportModal').remove()" style="width:100%;margin-top:20px;padding:12px;border:none;border-radius:10px;background:linear-gradient(135deg,#b8922e,#d4a843);color:#0d0d0d;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">Đã hiểu</button>
  </div>
</div>
<style>
  @keyframes fadeScaleIn { from { opacity:0; transform:scale(0.92); } to { opacity:1; transform:scale(1); } }
</style>
<script>document.addEventListener('keydown',function(e){if(e.key==='Escape'){var m=document.getElementById('reportModal');if(m)m.remove()}});</script>
<?php endif; ?>
<?php layout("footer"); ?>
