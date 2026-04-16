<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    layout("header", ["title" => "Quản lý chi tiêu",
        "css" => ["layout/sidebar"]
    ]);
    $view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
?>
  <body>
    <div class="app-container">
      
      <aside class="sidebar" id="mySidebar">
        <div class="brand">
          <h2>Quản lí chi tiêu</h2>
        </div>
        <nav class="menu-links">
          <a href="?template=layout&action=sidebar&view=dashboard" class="<?= ($view == 'dashboard') ? 'active' : '' ?>">Danh sách chi tiêu</a>
          
          <a href="?template=layout&action=sidebar&view=delete" class="<?= ($view == 'delete') ? 'active' : '' ?>">Xóa chi tiêu</a>
          
          <a href="?template=layout&action=sidebar&view=edit" class="<?= ($view == 'edit') ? 'active' : '' ?>">Sửa chi tiêu</a>
          
          <a href="?template=layout&action=sidebar&view=filter" class="<?= ($view == 'filter') ? 'active' : '' ?>">Lọc chi tiêu</a>
        </nav>
        <div class="sidebar-action">
          <a href="?template=layout&action=sidebar&view=add" class="<?= ($view=='add') ? 'active':''?>btn-primary-menu">＋ Thêm chi tiêu</a>
        </div>

        <div class="sidebar-footer">
         <a href="?template=layout&action=sidebar&view=profile">👤 Profile</a>
          <a href="?page=logout" class="logout-link">↪ Đăng xuất</a>
        </div>
      </aside>

      <main class="main-content">
        
        <header class="top-header">
          <div class="header-left">
            <button id="menu-toggle" class="btn-menu">☰</button>
            <div>
              <span class="subtitle">DIGITAL CURATOR</span>
              <h1>Quản lý chi tiêu</h1>
            </div>
          </div>
          <div class="header-right">
            <button class="btn-filter">Tháng 10, 2026</button>
            <div class="user-avatar">👤 Lộc</div>
          </div>
        </header>
        <div class="page-content" style="padding-top: 20px;">
          <?php if ($view == 'dashboard'): ?>
              <section class="stats-grid">
                <div class="stat-card dark-green-card">
                  <p class="card-title">SỐ DƯ KHẢ DỤNG</p>
                  <h2>128.500.000 đ</h2>
                  <p class="card-trend">↗ +2.4% so với tháng trước</p>
                </div>
                <div class="stat-card">
                  <div class="card-icon income">↙</div>
                  <p class="card-amount positive">+ 45.000.000 đ</p>
                  <h3>Tổng thu</h3>
                  <p class="card-desc">Lương & Các khoản thu nhập phụ</p>
                </div>
                <div class="stat-card">
                  <div class="card-icon expense">↗</div>
                  <p class="card-amount negative">- 16.500.000 đ</p>
                  <h3>Tổng chi</h3>
                  <p class="card-desc">Chi phí sinh hoạt & Mua sắm</p>
                </div>
              </section>

              <section class="data-grid">
                <div class="chart-section card-box">
                  <h3>Phân bố chi tiêu</h3>
                  <div class="placeholder-chart">
                    <div class="circle">16.5M<br /><span>THÁNG NÀY</span></div>
                  </div>
                </div>
                <div class="transactions-section card-box">
                  <div class="section-header">
                    <h3>Giao dịch gần đây</h3>
                    <a href="#" class="view-all">Xem tất cả</a>
                  </div>
                  <table class="data-table">
                    <tr>
                      <th>NGÀY</th>
                      <th>DANH MỤC</th>
                      <th>MÔ TẢ</th>
                      <th>SỐ TIỀN</th>
                    </tr>
                    <tr>
                      <td>24/10/2026</td>
                      <td>🍽️ Ăn uống</td>
                      <td>Ăn trưa văn phòng</td>
                      <td class="negative">- 85.000 đ</td>
                    </tr>
                    <tr>
                      <td>22/10/2026</td>
                      <td>💰 Thu nhập</td>
                      <td>Thanh toán dự án</td>
                      <td class="positive">+ 5.000.000 đ</td>
                    </tr>
                  </table>
                </div>
              </section>

        <?php elseif ($view == 'delete'): ?>
              <?php include 'templates/layout/delete.php'; ?>
          <?php elseif ($view == 'filter'): ?>
              <?php include 'templates/layout/filter.php'; ?>
          <?php elseif($view == 'add'):?>
              <?php include 'templates/layout/add.php';?>   
                   <?php elseif($view == 'edit'):?>
              <?php include 'templates/layout/edit.php';?>   
            <?php elseif($view == 'profile'): ?>
    <?php include 'templates/layout/profile.php'; ?>
     <?php else: ?>
              <div class="card-box">
                  <h2>Trang này đang được xây dựng...</h2>
              </div>
          <?php endif; ?>

        </div>
      </main>
    </div>

  </body>
<?php
    layout("footer", ["js" => ["pages/sidebar"]] );
?>