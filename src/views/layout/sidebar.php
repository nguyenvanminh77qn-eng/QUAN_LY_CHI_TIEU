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
    <a href="?template=user&action=limit" class="<?= ($view == 'limit') ? 'active' : '' ?>">Hạn mức</a>
    <a href="?template=user&action=filter" class="<?= ($view == 'filter') ? 'active' : '' ?>">Tìm kiếm</a>
    <a href="?template=user&action=inbox" class="<?= ($view == 'inbox') ? 'active' : '' ?>" style="position:relative;">Hộp thư
      <span class="sidebar-badge is-hidden" id="sidebar-inbox-badge" aria-label="Tin nhắn chưa đọc"></span>
    </a>
    <a href="?template=user&action=export" class="<?= ($view == 'export') ? 'active' : '' ?>">Xuất báo cáo</a>
    <a href="?template=user&action=wallet" class="<?= ($view == 'wallet') ? 'active' : '' ?>">Quản lý Ví</a>
  </nav>

  <div class="sidebar-action">
    <a href="?template=user&action=add" class="<?= ($view == 'add') ? 'active ' : '' ?>btn-primary-menu">＋ Thêm chi tiêu</a>
  </div>

  <div class="sidebar-footer">
    <a href="?template=user&action=profile" class="<?= ($view == 'profile') ? 'active' : '' ?>">Hồ sơ</a>
    <form method="POST" action="?template=auth&action=logout" style="display:inline">
      <button type="submit" class="logout-link">Đăng xuất</button>
    </form>
  </div>
</aside>
