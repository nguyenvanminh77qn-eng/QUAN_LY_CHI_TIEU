var _filterSkeletonShown = false;

function initFilter() {
  var resultCard = document.querySelector('.filter-result-card');
  var tbody = resultCard ? resultCard.querySelector('.filter-tbody') : null;
  var prevBtn = document.querySelector('.filter-arrow--prev');
  var nextBtn = document.querySelector('.filter-arrow--next');
  var ajaxContainer = document.querySelector('.pagination-ajax');

  // =========================================
  // SKELETON — only on first full load
  // =========================================
  if (resultCard && tbody && !_filterSkeletonShown) {
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
          if (j === 2) skCell.classList.add('long');
          else if (j === 1 || j === 4) skCell.classList.add('short');
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
        _filterSkeletonShown = true;
        updateArrowState();
      }, 1500);
    } else {
      resultCard.classList.add('is-ready');
      _filterSkeletonShown = true;
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
    if (!ajaxContainer) return;
    var totalPages = parseInt(ajaxContainer.getAttribute('data-total-pages') || '1', 10);
    var currentPage = parseInt(ajaxContainer.getAttribute('data-current-page') || '1', 10);
    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function() { paginateTo('prev'); });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function() { paginateTo('next'); });
  }

  // =========================================
  // AJAX pagination — slide-in only
  // =========================================
  function paginateTo(dir) {
    if (!ajaxContainer) return;
    var current = parseInt(ajaxContainer.getAttribute('data-current-page') || '1', 10);
    var total = parseInt(ajaxContainer.getAttribute('data-total-pages') || '1', 10);
    var target = dir === 'next' ? current + 1 : current - 1;
    if (target < 1 || target > total) return;
    doAjaxPage(target, dir === 'next' ? 'right' : 'left');
  }

  function doAjaxPage(page, direction) {
    if (!tbody || !ajaxContainer) return;

    var slideInCls = 'fade-scale-in';

    fetch('?template=user&action=filter&ajax=1&page=' + page)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        tbody.innerHTML = data.rows;
        ajaxContainer.innerHTML = data.pagination;
        ajaxContainer.setAttribute('data-total-pages', data.totalPages);
        ajaxContainer.setAttribute('data-current-page', data.currentPage);

        var countEl = document.querySelector('.result-count');
        if (countEl) countEl.textContent = data.count || 0;

        var formDelete = document.getElementById('formDelete');
        if (formDelete) formDelete.setAttribute('action', '?template=user&action=delete&page=' + data.currentPage);

        tbody.classList.add(slideInCls);
        setTimeout(function() { tbody.classList.remove(slideInCls); }, 600);

        updateArrowState();
        rebindCheckboxes();
        bindPaginationLinks();
      })
      .catch(function() {
        window.location.href = '?template=user&action=filter&page=' + page;
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
        var direction = page > current ? 'right' : 'left';
        doAjaxPage(page, direction);
      });
    }
  }

  bindPaginationLinks();

  // =========================================
  // Checkboxes (rebind after AJAX)
  // =========================================
  function rebindCheckboxes() {
    var checkAll = document.getElementById('checkAll');
    if (checkAll) {
      var newAll = checkAll.cloneNode(true);
      checkAll.parentNode.replaceChild(newAll, checkAll);
      newAll.addEventListener('change', function() {
        document.querySelectorAll('.checkItem').forEach(function(c) { c.checked = newAll.checked; });
      });
    }
    document.querySelectorAll('.checkItem').forEach(function(item) {
      var newItem = item.cloneNode(true);
      item.parentNode.replaceChild(newItem, item);
      newItem.addEventListener('change', function() {
        if (!this.checked) {
          var ca = document.getElementById('checkAll');
          if (ca) ca.checked = false;
        } else {
          var all = document.querySelectorAll('.checkItem');
          var allChecked = Array.from(all).every(function(i) { return i.checked; });
          var ca = document.getElementById('checkAll');
          if (ca) ca.checked = allChecked;
        }
      });
    });
  }

  rebindCheckboxes();

  // =========================================
  // Bulk delete confirmation
  // =========================================
  var btnDelete = document.querySelector('.filter-btn-delete');
  if (btnDelete) {
    btnDelete.addEventListener('click', function(e) {
      var checked = document.querySelectorAll('.checkItem:checked').length;
      if (checked === 0) {
        alert('Vui lòng chọn ít nhất một mục để xóa.');
        e.preventDefault();
        return;
      }
      if (!confirm('Bạn có chắc chắn muốn xóa các mục đã chọn?')) e.preventDefault();
    });
  }
}

document.addEventListener('DOMContentLoaded', initFilter);
document.addEventListener('page-loaded', initFilter);