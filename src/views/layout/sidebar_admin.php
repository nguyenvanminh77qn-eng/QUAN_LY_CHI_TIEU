<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$view = isset($view) ? $view : '';
$loginToken = getSession('loginToken');
?>
<aside class="sidebar admin-sidebar" id="mySidebar">
  <div class="brand">
    <h2>ADMIN PANEL</h2>
  </div>

  <nav class="menu-links">
    <a href="?template=admin&action=dashboard" class="<?= ($view == 'dashboard') ? 'active' : '' ?>">Tổng quan</a>
    <a href="?template=admin&action=users" class="<?= ($view == 'users') ? 'active' : '' ?>">Quản lý thành viên</a>
    <a href="?template=admin&action=categories" class="<?= ($view == 'categories') ? 'active' : '' ?>">Quản lý danh mục</a>
    <a href="?template=admin&action=notifications" class="<?= ($view == 'notifications') ? 'active' : '' ?>">Quản lý thông báo</a>
    <a href="?template=admin&action=inbox" class="<?= ($view == 'inbox') ? 'active' : '' ?>" style="position:relative;">Hộp thư
      <span class="sidebar-badge is-hidden" id="sidebar-inbox-badge" aria-label="Tin nhắn chưa đọc"></span>
    </a>
    <a href="?template=admin&action=report" class="<?= ($view == 'report') ? 'active' : '' ?>">Báo cáo tháng</a>
  </nav>

  <div class="sidebar-footer">
    <a href="?template=admin&action=profile" class="<?= ($view == 'profile') ? 'active' : '' ?>">Hồ sơ</a>
    <form method="POST" action="?template=auth&action=logout" style="display:inline">
      <button type="submit" class="logout-link">Đăng xuất</button>
    </form>
  </div>
</aside>
