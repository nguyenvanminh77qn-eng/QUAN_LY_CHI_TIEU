function loadBudget() {
  var f = document.querySelector('.card form');
  var c = document.getElementById('budgetContent');
  if (!f || !c) return;
  var fd = new FormData(f);
  var qs = new URLSearchParams(fd).toString() + '&ajax=1';
  c.innerHTML = '<div style="text-align:center;padding:20px;color:#999;">Đang tải...</div>';
  fetch('?' + qs)
    .then(function(r) { return r.json(); })
    .then(function(res) { if (res.success && res.data.html) c.innerHTML = res.data.html; })
    .catch(function() { window.location.href = '?' + qs; });
}