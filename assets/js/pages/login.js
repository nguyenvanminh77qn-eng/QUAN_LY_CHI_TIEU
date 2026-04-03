document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', (event) => {
            event.preventDefault();

            // Lấy giá trị từ input
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Xử lý logic giả lập
            console.log('Đang thử đăng nhập với:', email);
            
            // Bạn có thể thêm các hiệu ứng loading tại đây
            const submitBtn = loginForm.querySelector('.btn--primary');
            submitBtn.textContent = 'Đang xử lý...';
            submitBtn.style.opacity = '0.7';
            submitBtn.disabled = true;

            setTimeout(() => {
                alert('Đăng nhập thành công (giả lập)');
                submitBtn.textContent = 'Đăng nhập';
                submitBtn.style.opacity = '1';
                submitBtn.disabled = false;
            }, 1500);
        });
    }
});