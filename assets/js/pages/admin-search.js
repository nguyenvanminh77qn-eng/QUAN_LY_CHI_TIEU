var _adminSkeletonShown = false;

function initAdminSearch() {
  var resultCard = document.querySelector('.filter-result-card');
  var tbody = resultCard ? resultCard.querySelector('.filter-tbody') : null;
  var prevBtn = document.querySelector('.filter-arrow--prev');
  var nextBtn = document.querySelector('.filter-arrow--next');
  var paginationAjax = document.querySelector('.pagination-ajax');

  // =========================================
  // SKELETON — only on first full load
  // =========================================
  if (resultCard && tbody && !_adminSkeletonShown) {
    var realRows = tbody.querySelectorAll('.filter-tr');
    var theadRow = resultCard.querySelector('.filter-thead tr');
    var colCount = theadRow ? theadRow.querySelectorAll('th').length : 6;

    if (realRows.length > 0 && realRows[0].querySelector('.filter-empty-state') === null) {
      var fragment = document.createDocumentFragment();
      for (var i = 0; i < Math.min(realRows.length, 5); i++) {
        var skRow = document.createElement('tr');
        skRow.className = 'skeleton-row';
        for (var j = 0; j < colCount; j++) {
          var skTd = document.createElement('td');
          var skCell = document.createElement('div');
          skCell.className = 'skeleton-cell';
          if (j === 2 || j === 3) skCell.classList.add('long');
          else if (j === 0) skCell.classList.add('short');
          else skCell.classList.add('medium');
          skTd.appendChild(skCell);
          skRow.appendChild(skTd);
        }
        fragment.appendChild(skRow);
      }
      tbody.insertBefore(fragment, tbody.firstChild);

      for (var r = 0; r < realRows.length; r++) realRows[r].style.display = 'none';

      setTimeout(function() {
        for (var r = 0; r < realRows.length; r++) realRows[r].style.display = '';
        var skeletons = tbody.querySelectorAll('.skeleton-row');
        for (var s = 0; s < skeletons.length; s++) skeletons[s].remove();
        resultCard.classList.add('is-ready');
        _adminSkeletonShown = true;
        updateArrowState();
      }, 600);
    } else {
      resultCard.classList.add('is-ready');
      _adminSkeletonShown = true;
      updateArrowState();
    }
  } else if (resultCard) {
    resultCard.classList.add('is-ready');
    updateArrowState();
  }

  // =========================================
  // Arrow state
  // =========================================
  function updateArrowState() {
    if (!paginationAjax) return;
    var totalPages = parseInt(paginationAjax.getAttribute('data-total-pages') || '1', 10);
    var currentPage = parseInt(paginationAjax.getAttribute('data-current-page') || '1', 10);
    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function() {
      var tp = parseInt(paginationAjax.getAttribute('data-total-pages') || '1', 10);
      var cp = parseInt(paginationAjax.getAttribute('data-current-page') || '1', 10);
      if (cp > 1) doAdminAjaxPage(cp - 1, tp, cp);
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function() {
      var tp = parseInt(paginationAjax.getAttribute('data-total-pages') || '1', 10);
      var cp = parseInt(paginationAjax.getAttribute('data-current-page') || '1', 10);
      if (cp < tp) doAdminAjaxPage(cp + 1, tp, cp);
    });
  }

  // =========================================
  // Pagination links — use AJAX instead of reload
  // =========================================
  function bindPaginationLinks() {
    var links = document.querySelectorAll('.pagination a');
    for (var i = 0; i < links.length; i++) {
      links[i].addEventListener('click', function(e) {
        e.preventDefault();
        var href = this.getAttribute('href');
        var match = href.match(/[?&]page=(\d+)/);
        if (!match) return;
        var page = parseInt(match[1], 10);
        var container = document.querySelector('.pagination-ajax');
        if (!container) return;
        var current = parseInt(container.getAttribute('data-current-page') || '1', 10);
        if (page === current) return;
        var total = parseInt(container.getAttribute('data-total-pages') || '1', 10);
        doAdminAjaxPage(page, total, current);
      });
    }
  }

  bindPaginationLinks();

  // =========================================
  // AJAX pagination — slide-in only
  // =========================================
  function doAdminAjaxPage(targetPage, totalPages, currentPage) {
    if (!tbody || !paginationAjax) return;

    var slideInName = 'fadeSlideDown';

    var url = new URL(window.location.href);
    url.searchParams.set('page', targetPage);
    url.searchParams.set('ajax', '1');

    fetch(url.toString())
      .then(function(r) { return r.json(); })
      .then(function(data) {
        tbody.innerHTML = data.rows;
        paginationAjax.innerHTML = data.pagination;
        paginationAjax.setAttribute('data-total-pages', data.totalPages);
        paginationAjax.setAttribute('data-current-page', data.currentPage);

        var countEl = document.querySelector('.result-count');
        if (countEl) countEl.textContent = data.count || 0;

        tbody.style.animation = 'none';
        void tbody.offsetHeight;
        tbody.style.animation = slideInName + ' 0.55s cubic-bezier(0.15, 0.85, 0.35, 1) both';

        updateArrowState();
        bindPaginationLinks();

        setTimeout(function() { tbody.style.animation = ''; }, 600);
      })
      .catch(function() {
        tbody.style.animation = '';
        url.searchParams.delete('ajax');
        window.location.href = url.toString();
      });
  }
}

document.addEventListener('DOMContentLoaded', initAdminSearch);
document.addEventListener('page-loaded', initAdminSearch);