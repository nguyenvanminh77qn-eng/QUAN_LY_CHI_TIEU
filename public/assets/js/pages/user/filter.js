var _filterSkeletonShown = false;

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

function initFilter() {
  var resultCard = document.querySelector('.filter-result-card');
  var tbody = resultCard ? resultCard.querySelector('.filter-tbody') : null;

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
        rebindCheckboxes();
      }, 1500);
    } else {
      resultCard.classList.add('is-ready');
      _filterSkeletonShown = true;
      rebindCheckboxes();
    }
  } else if (resultCard) {
    resultCard.classList.add('is-ready');
    rebindCheckboxes();
  }

  // =========================================
  // Bulk delete → custom modal via onsubmit
  // =========================================
  var btnDelete = document.querySelector('.filter-btn-delete');
  if (btnDelete) {
    btnDelete.addEventListener('click', function(e) {
      var checked = document.querySelectorAll('.checkItem:checked').length;
      if (checked === 0) {
        alert('Vui lòng chọn ít nhất một mục để xóa.');
        e.preventDefault();
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', initFilter);
document.addEventListener('page-loaded', initFilter);
