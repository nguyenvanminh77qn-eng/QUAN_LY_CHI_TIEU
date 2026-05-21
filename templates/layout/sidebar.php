<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
$view = isset($view) ? $view : '';

$loginToken = getSession('loginToken');
?>
<aside class="sidebar" id="mySidebar">
  <div class="brand">
    <h2>Quản lí chi tiêu</h2>
  </div>

  <nav class="menu-links">
    <a href="?template=user&action=dashboard" class="<?= ($view == 'dashboard') ? 'active' : '' ?>">Danh sách chi tiêu</a>
    <a href="?template=user&action=budget" class="<?= ($view == 'budget') ? 'active' : '' ?>">Ngân sách</a>
    <a href="?template=user&action=filter" class="<?= ($view == 'filter') ? 'active' : '' ?>">Lọc chi tiêu</a>
    <a href="?template=user&action=export" class="<?= ($view == 'export') ? 'active' : '' ?>">Xuất dữ liệu (CSV)</a>
  </nav>

  <div class="sidebar-action">
    <a href="?template=user&action=add" class="<?= ($view == 'add') ? 'active ' : '' ?>btn-primary-menu">＋ Thêm chi tiêu</a>
  </div>

  <div class="sidebar-footer">
    <a href="?template=user&action=profile" class="<?= ($view == 'profile') ? 'active' : '' ?>">👤 Profile</a>
    <a href="?template=auth&action=logout&token=<?= $loginToken ?>" class="logout-link">↪ Đăng xuất</a>
  </div>
</aside>