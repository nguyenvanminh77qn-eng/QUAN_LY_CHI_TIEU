document.addEventListener('DOMContentLoaded', function() {
    // Check All Checkboxes Logic
    const checkAll = document.getElementById('checkAll');
    const checkItems = document.querySelectorAll('.checkItem');

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkItems.forEach(item => {
                item.checked = checkAll.checked;
            });
        });
    }

    // Update single checkboxes to uncheck Check All if one is unchecked
    checkItems.forEach(item => {
        item.addEventListener('change', function() {
            if (!this.checked) {
                if(checkAll) checkAll.checked = false;
            } else {
                const allChecked = Array.from(checkItems).every(i => i.checked);
                if(checkAll) checkAll.checked = allChecked;
            }
        });
    });

    // Bulk Delete Confirmation Logic
    const btnDelete = document.querySelector('.filter-btn-delete');
    if (btnDelete) {
        btnDelete.addEventListener('click', function(e) {
            const checkedCount = document.querySelectorAll('.checkItem:checked').length;
            if (checkedCount === 0) {
                alert('Vui lòng chọn ít nhất một mục để xóa.');
                e.preventDefault();
                return;
            }
            if (!confirm('Bạn có chắc chắn muốn xóa các mục đã chọn?')) {
                e.preventDefault();
            }
        });
    }

    // Single Delete Confirmation Logic
    const deleteLinks = document.querySelectorAll('.action-btn.delete');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa giao dịch này?')) {
                e.preventDefault();
            }
        });
    });
});
