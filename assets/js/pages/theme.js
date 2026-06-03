(function() {
  var html = document.documentElement;
  var app = document.querySelector('.app-container');
  var isAdmin = app && app.querySelector('.admin-sidebar');
  var isAuth = !!document.querySelector('.auth-page');
  if (isAdmin) {
    html.setAttribute('data-theme', 'dark');
    if (app) app.setAttribute('data-theme', 'dark');
  } else {
    html.setAttribute('data-theme', 'light');
    if (app) app.setAttribute('data-theme', 'light');
  }

  var btn = document.getElementById('themeToggle');
  if (!btn) return;

  btn.addEventListener('click', function() {
    var isDark = html.getAttribute('data-theme') === 'dark';
    var newTheme = isDark ? 'light' : 'dark';

    var rect = btn.getBoundingClientRect();
    var x = rect.left + rect.width / 2;
    var y = rect.top + rect.height / 2;
    var maxR = Math.hypot(
      Math.max(x, window.innerWidth - x),
      Math.max(y, window.innerHeight - y)
    );

    html.style.setProperty('--click-x', x + 'px');
    html.style.setProperty('--click-y', y + 'px');

    function applyTheme() {
      html.setAttribute('data-theme', newTheme);
      if (app) app.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
    }

    if (document.startViewTransition) {
      document.startViewTransition(applyTheme);
    } else {
      var overlay = document.createElement('div');
      var overlayBg = newTheme === 'dark' ? '#0b1120' : '#f1f5f9';
      overlay.style.cssText =
        'position:fixed;inset:0;z-index:9999;pointer-events:none;' +
        'background:' + overlayBg + ';' +
        'clip-path:circle(0% at ' + x + 'px ' + y + 'px);' +
        'transition:clip-path 1.1s cubic-bezier(.22,1,.36,1)';
      document.body.appendChild(overlay);
      overlay.offsetHeight;
      applyTheme();
      overlay.style.clipPath = 'circle(' + maxR + 'px at ' + x + 'px ' + y + 'px)';
      setTimeout(function() { overlay.remove(); }, 1150);
    }
  });
})();
