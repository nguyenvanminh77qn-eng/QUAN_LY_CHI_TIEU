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
    <a href="?template=user&action=wallet" class="<?= ($view == 'wallet') ? 'active' : '' ?>">Quản lý Ví</a>
    <a href="?template=user&action=savings" class="<?= ($view == 'savings') ? 'active' : '' ?>">Quỹ Tiết Kiệm</a>
  </nav>

  <div class="sidebar-action">
    <a href="?template=user&action=add" class="<?= ($view == 'add') ? 'active ' : '' ?>btn-primary-menu">＋ Thêm chi tiêu</a>
  </div>

  <div class="sidebar-footer">
    <a href="?template=user&action=profile" class="<?= ($view == 'profile') ? 'active' : '' ?>">👤 Profile</a>
    <form method="POST" action="?template=auth&action=logout" style="display:inline">
      <button type="submit" class="logout-link" style="background:none;border:none;cursor:pointer;font-size:14px;padding:10px 0;width:100%;text-align:left;color:#b91c1c;">↪ Đăng xuất</button>
    </form>
  </div>
</aside>