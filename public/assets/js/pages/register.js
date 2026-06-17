document.getElementById('signupForm')?.addEventListener('submit', function() {
    const btn = this.querySelector('button[name="btn-register"]');
    if (btn && !btn.disabled) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'btn-register';
        hidden.value = '1';
        this.appendChild(hidden);
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';
    }
});
