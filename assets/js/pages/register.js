document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signupForm');
    
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;

            // 1. Kiểm tra mật khẩu khớp nhau
            if (password !== confirmPassword) {
                alert('Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại!');
                return;
            }

            // 2. Kiểm tra độ dài mật khẩu (Ví dụ ít nhất 6 ký tự)
            if (password.length < 6) {
                alert('Mật khẩu phải có ít nhất 6 ký tự.');
                return;
            }

            // 3. Giả lập gửi dữ liệu
            const submitBtn = signupForm.querySelector('.btn-submit');
            submitBtn.textContent = 'Đang xử lý...';
            submitBtn.disabled = true;

            console.log('Đang đăng ký cho:', { name, email });

            // Ở đây bạn sẽ dùng fetch() để gửi đến file PHP xử lý database
            setTimeout(() => {
                alert('Chúc mừng ' + name + '! Tài khoản của bạn đã được tạo thành công.');
                submitBtn.textContent = 'Tạo tài khoản';
                submitBtn.disabled = false;
                // signupForm.reset(); // Xóa form sau khi xong
            }, 1500);
        });
    }
});