<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
?>

<div class="page-container">
    <div class="profile-layout">
        <div class="profile-header card-box">
            <div class="profile-avatar">
                <span class="avatar-text">L</span>
            </div>
            <div class="profile-info">
                <h2 class="profile-name">Nguyễn Hữu Lộc</h2>
                <p class="profile-email">loc.nguyen@student.qnu.edu.vn</p>
                <span class="profile-role">Quản trị viên</span>
            </div>
        </div>

        <div class="card-box profile-form-card">
            <h3 class="section-title">Thông tin cá nhân</h3>
            <form id="profileForm" class="profile-form">
                <div class="form-group">
                    <label class="form-label">HỌ VÀ TÊN</label>
                    <input type="text" class="form-input" value="Nguyễn Hữu Lộc" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">EMAIL</label>
                    <input type="email" class="form-input" value="loc.nguyen@student.qnu.edu.vn" disabled>
                    <small class="form-hint">Email dùng để đăng nhập không thể thay đổi.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">SỐ ĐIỆN THOẠI</label>
                    <input type="tel" class="form-input" placeholder="Nhập số điện thoại của bạn...">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-submit">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>