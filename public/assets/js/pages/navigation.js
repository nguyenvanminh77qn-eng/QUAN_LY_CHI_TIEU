(function() {
  'use strict';

  var pageSequences = {
    user: [
      { action: 'dashboard', label: 'Dashboard' },
      { action: 'filter',   label: 'Lọc chi tiêu' },
      { action: 'add',      label: 'Thêm chi tiêu' },
      { action: 'budget',   label: 'Ngân sách' },
      { action: 'export',   label: 'Xuất báo cáo' },
      { action: 'profile',  label: 'Hồ sơ' }
    ],
    admin: [
      { action: 'dashboard',  label: 'Tổng quan' },
      { action: 'users',      label: 'Thành viên' },
      { action: 'categories', label: 'Danh mục' },
      { action: 'profile',    label: 'Hồ sơ' }
    ]
  };

  var params = new URLSearchParams(window.location.search);
  var template = params.get('template');
  var action = params.get('action');

  if (!template || !action) return;

  var role = template === 'admin' ? 'admin' : 'user';
  var seq = pageSequences[role];
  if (!seq || seq.length === 0) return;

  var currentIdx = -1;
  for (var i = 0; i < seq.length; i++) {
    if (seq[i].action === action) { currentIdx = i; break; }
  }
  if (currentIdx === -1) return;

  var prevPage = currentIdx > 0 ? seq[currentIdx - 1] : null;
  var nextPage = currentIdx < seq.length - 1 ? seq[currentIdx + 1] : null;

  function createArrow(direction) {
    var btn = document.createElement('button');
    btn.className = 'page-nav-arrow page-nav-arrow--' + direction;
    btn.innerHTML = direction === 'prev' ? '&#x2039;' : '&#x203A;';
    btn.setAttribute('aria-label', direction === 'prev' ? 'Trang trước' : 'Trang sau');
    document.body.appendChild(btn);
    return btn;
  }

  if (prevPage) {
    var prevBtn = createArrow('prev');
    setTimeout(function() { prevBtn.classList.add('visible'); }, 800);
    prevBtn.addEventListener('click', function(e) {
      e.preventDefault();
      navigate(prevPage.action, currentIdx, currentIdx - 1);
    });
  }

  if (nextPage) {
    var nextBtn = createArrow('next');
    setTimeout(function() { nextBtn.classList.add('visible'); }, 800);
    nextBtn.addEventListener('click', function(e) {
      e.preventDefault();
      navigate(nextPage.action, currentIdx, currentIdx + 1);
    });
  }

  function navigate(targetAction, fromIdx, toIdx) {
    var distance = Math.abs(toIdx - fromIdx);
    var duration = distance <= 1 ? 380 : Math.max(150, 380 - distance * 60);
    var direction = toIdx > fromIdx ? 'right' : 'left';
    var exitClass = 'is-exiting-' + direction;
    var enterClass = 'is-entering-' + direction;

    var pageContent = document.querySelector('.page-content');
    if (!pageContent) return;

    pageContent.style.setProperty('--slide-duration', (duration / 1000) + 's');
    pageContent.classList.add(exitClass);

    var targetUrl = '?template=' + template + '&action=' + targetAction;

    fetch(targetUrl)
      .then(function(resp) { return resp.text(); })
      .then(function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newContent = doc.querySelector('.page-content');

        if (!newContent) {
          window.location.href = targetUrl;
          return;
        }

        // Extract inline scripts from the fetched page-content
        var inlineScripts = [];
        var scriptTags = newContent.querySelectorAll('script');
        scriptTags.forEach(function(s) {
          if (!s.src && s.textContent.trim()) {
            inlineScripts.push(s.textContent);
          }
        });

        // Prevent white flash during dark mode page transitions
        pageContent.style.opacity = '0';

        // Replace content
        pageContent.innerHTML = newContent.innerHTML;

        // Force reflow so browser applies [data-theme="dark"] CSS before revealing
        void pageContent.offsetHeight;

        // Update URL silently
        window.history.pushState({ template: template, action: targetAction }, '', targetUrl);

        // Update document title
        var titleEl = doc.querySelector('title');
        if (titleEl) document.title = titleEl.textContent;

        // Update sidebar active link
        updateSidebarActive(targetAction);

        // Re-execute inline scripts from the new content
        inlineScripts.forEach(function(code) {
          try {
            var fn = new Function(code);
            fn();
          } catch(e) {}
        });

        // Dispatch event so page-specific init functions rerun
        document.dispatchEvent(new CustomEvent('page-loaded', {
          detail: { template: template, action: targetAction }
        }));

        // Slide in
        pageContent.classList.remove(exitClass);
        pageContent.classList.add(enterClass);
        setTimeout(function() {
          pageContent.classList.remove(enterClass);
          pageContent.style.opacity = '';
        }, duration + 100);
      })
      .catch(function() {
        window.location.href = targetUrl;
      });
  }

  function updateSidebarActive(targetAction) {
    var links = document.querySelectorAll('.menu-links a');
    links.forEach(function(link) {
      link.classList.remove('active');
      if (link.getAttribute('href') && link.getAttribute('href').indexOf('action=' + targetAction) !== -1) {
        link.classList.add('active');
      }
    });
    var footerLinks = document.querySelectorAll('.sidebar-footer a');
    footerLinks.forEach(function(link) {
      link.classList.remove('active');
      if (link.getAttribute('href') && link.getAttribute('href').indexOf('action=' + targetAction) !== -1) {
        link.classList.add('active');
      }
    });
  }

  window.addEventListener('popstate', function(e) {
    if (e.state && e.state.action) {
      window.location.href = '?template=' + e.state.template + '&action=' + e.state.action;
    }
  });

})();
