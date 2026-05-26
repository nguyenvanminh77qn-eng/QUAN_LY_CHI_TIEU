document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn = this.querySelector('button[name="btn-login"]');
    if (btn && !btn.disabled) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'btn-login';
        hidden.value = '1';
        this.appendChild(hidden);
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';
    }
});
