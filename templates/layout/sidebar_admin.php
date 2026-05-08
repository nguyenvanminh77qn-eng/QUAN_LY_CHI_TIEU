<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$view = isset($view) ? $view : '';

$loginToken = getSession('loginToken');
?>
<aside class="sidebar admin-sidebar" id="mySidebar">
  <div class="brand">
    <h2 style="color: #e67e22;">ADMIN PANEL</h2>
  </div>

  <nav class="menu-links">
    <a href="?template=admin&action=dashboard" class="<?= ($view == 'dashboard') ? 'active' : '' ?>">📊 Tổng quan</a>
    <a href="?template=admin&action=users" class="<?= ($view == 'users') ? 'active' : '' ?>">👥 Quản lý thành viên</a>
    <a href="?template=admin&action=categories" class="<?= ($view == 'categories') ? 'active' : '' ?>">📂 Quản lý danh mục</a>
  </nav>

  <div class="sidebar-footer">
    <a href="?template=user&action=profile" class="<?= ($view == 'profile') ? 'active' : '' ?>">👤 Profile</a>
    <a href="?template=auth&action=logout&token=<?= $loginToken ?>" class="logout-link">↪ Đăng xuất</a>
  </div>

</aside>
