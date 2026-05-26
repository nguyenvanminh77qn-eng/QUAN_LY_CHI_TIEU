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
    <a href="?template=admin&action=profile" class="<?= ($view == 'profile') ? 'active' : '' ?>">👤 Profile</a>
    <form method="POST" action="?template=auth&action=logout" style="display:inline">
      <button type="submit" class="logout-link" style="background:none;border:none;cursor:pointer;font-size:14px;padding:10px 0;width:100%;text-align:left;color:#b91c1c;">↪ Đăng xuất</button>
    </form>
  </div>

</aside>
