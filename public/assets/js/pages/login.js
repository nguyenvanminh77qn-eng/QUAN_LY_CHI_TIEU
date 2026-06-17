document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn = this.querySelector('button[name="btn-login"]');
    if (btn && !btn.disabled) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'btn-login';
        hidden.value = '1';
        this.appendChild(hidden);
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;animation:spin 1s linear infinite">refresh</span> Đang xử lý...';
    }
});
