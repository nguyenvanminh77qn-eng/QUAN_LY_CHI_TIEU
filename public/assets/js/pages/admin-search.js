var _adminSkeletonShown = false;

function initAdminSearch() {
  var resultCard = document.querySelector('.filter-result-card');
  var tbody = resultCard ? resultCard.querySelector('.filter-tbody') : null;

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
      }, 600);
    } else {
      resultCard.classList.add('is-ready');
      _adminSkeletonShown = true;
    }
  } else if (resultCard) {
    resultCard.classList.add('is-ready');
  }

  // =========================================
  // Cursor-based "Xem thêm" for Users table
  // =========================================
  initLoadMore('userTbody', 'userBtnLoadMore', 'userLoadMoreContainer', 'userLoadMoreSpinner', {
    lastId: typeof usersLastId !== 'undefined' ? usersLastId : 0,
    lastCreateAt: typeof usersLastCreateAt !== 'undefined' ? usersLastCreateAt : '',
    hasMore: typeof usersHasMore !== 'undefined' ? usersHasMore : true,
    extraParams: {},
    buildUrl: function(params) {
      var url = new URL(window.location.href);
      url.searchParams.set('ajax', '1');
      url.searchParams.set('last_id', params.lastId);
      url.searchParams.set('last_create_at', params.lastCreateAt);
      return url.toString();
    },
    parseResponse: function(data) {
      return {
        rows: data.rows,
        hasMore: data.has_more,
        lastId: data.next_last_id,
        lastCreateAt: data.next_last_create_at,
        lastUsageCount: -1,
        lastName: ''
      };
    }
  });

  // =========================================
  // Cursor-based "Xem thêm" for Categories table
  // =========================================
  initLoadMore('catTbody', 'catBtnLoadMore', 'catLoadMoreContainer', 'catLoadMoreSpinner', {
    lastId: typeof categoriesLastId !== 'undefined' ? categoriesLastId : 0,
    lastUsageCount: typeof categoriesLastUsageCount !== 'undefined' ? categoriesLastUsageCount : -1,
    lastName: typeof categoriesLastName !== 'undefined' ? categoriesLastName : '',
    hasMore: typeof categoriesHasMore !== 'undefined' ? categoriesHasMore : true,
    extraParams: {},
    buildUrl: function(params) {
      var url = new URL(window.location.href);
      url.searchParams.set('ajax', '1');
      url.searchParams.set('last_id', params.lastId);
      url.searchParams.set('last_usage_count', params.lastUsageCount);
      url.searchParams.set('last_name', params.lastName);
      return url.toString();
    },
    parseResponse: function(data) {
      return {
        rows: data.rows,
        hasMore: data.has_more,
        lastId: data.next_last_id,
        lastUsageCount: data.next_last_usage_count,
        lastName: data.next_last_name,
        lastCreateAt: ''
      };
    }
  });
}

// ── Generic "Xem thêm" loader ──
function initLoadMore(tbodyId, btnId, containerId, spinnerId, opts) {
  var tbody = document.getElementById(tbodyId);
  var btn = document.getElementById(btnId);
  var container = document.getElementById(containerId);
  var spinner = document.getElementById(spinnerId);
  if (!tbody || !btn || !container) return;

  var state = {
    loading: false,
    hasMore: opts.hasMore,
    lastId: opts.lastId,
    lastCreateAt: opts.lastCreateAt || '',
    lastUsageCount: opts.lastUsageCount || -1,
    lastName: opts.lastName || ''
  };

  var initialRows = tbody.querySelectorAll('.filter-tr').length;
  if (initialRows === 0 || !state.hasMore) {
    if (container) container.style.display = 'none';
    return;
  }

  btn.addEventListener('click', function() {
    if (state.loading || !state.hasMore) return;
    state.loading = true;
    btn.style.display = 'none';
    if (spinner) spinner.style.display = 'inline';

    var params = {
      lastId: state.lastId,
      lastCreateAt: state.lastCreateAt,
      lastUsageCount: state.lastUsageCount,
      lastName: state.lastName
    };
    var fetchUrl = opts.buildUrl(params);

    fetch(fetchUrl)
      .then(function(r) { return r.json(); })
      .then(function(res) {
        state.loading = false;
        if (spinner) spinner.style.display = 'none';

        if (!res.success) {
          btn.textContent = 'Lỗi tải dữ liệu';
          btn.style.display = 'inline';
          return;
        }

        var processed = opts.parseResponse(res.data);
        if (processed.rows === '') {
          state.hasMore = false;
          if (container) container.style.display = 'none';
          return;
        }

        tbody.insertAdjacentHTML('beforeend', processed.rows);

        state.lastId = processed.lastId;
        state.lastCreateAt = processed.lastCreateAt || '';
        state.lastUsageCount = processed.lastUsageCount || -1;
        state.lastName = processed.lastName || '';
        state.hasMore = processed.hasMore;

        if (state.hasMore) {
          btn.style.display = 'inline';
        } else {
          if (container) container.style.display = 'none';
        }
      })
      .catch(function() {
        state.loading = false;
        if (spinner) spinner.style.display = 'none';
        btn.textContent = 'Lỗi, thử lại';
        btn.style.display = 'inline';
      });
  });
}

document.addEventListener('DOMContentLoaded', initAdminSearch);
document.addEventListener('page-loaded', initAdminSearch);
