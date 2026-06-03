function loadBudget() {
  var f = document.querySelector('.card form');
  var c = document.getElementById('budgetContent');
  if (!f || !c) return;
  var fd = new FormData(f);
  var qs = new URLSearchParams(fd).toString() + '&ajax=1';
  c.innerHTML = '<div style="text-align:center;padding:20px;color:#999;">Đang tải...</div>';
  fetch('?' + qs)
    .then(function(r) { return r.json(); })
    .then(function(d) { if (d.html) c.innerHTML = d.html; })
    .catch(function() { window.location.href = '?' + qs; });
}