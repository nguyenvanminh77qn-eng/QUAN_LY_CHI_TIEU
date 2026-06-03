<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if(!empty(getSession('loginToken'))){
    $role = getSession('role');
    if($role == 'admin'){
        redirect('?template=admin&action=dashboard');
    }else{
        redirect('?template=user&action=dashboard');
    }
}

layout("header", [
    "title" => "MoneyMaster - Quản Lý Chi Tiêu",
    "css" => ["pages/home/welcome"]
]);
?>

<main class="welcome-page">

  <!-- Ambient particles -->
  <div class="ambient-particles" aria-hidden="true">
    <span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span>
  </div>

  <!-- Navbar -->
  <nav class="wnav" id="navbar">
    <a href="#" class="wnav-brand">
      <span class="wnav-brand-icon">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#0d9488"/><path d="M8 18V12h3v6H8Zm4.5 0V8h3v10h-3Zm4.5 0v-4h3v4h-3Z" fill="#fff" opacity="0.9"/></svg>
      </span>
      MoneyMaster
    </a>
    <ul class="wnav-links" id="navLinks">
      <li><a href="#hero">Trang chủ</a></li>
      <li><a href="#features">Tính năng</a></li>
      <li><a href="#how-it-works">Cách hoạt động</a></li>
      <li><a href="#testimonials">Khách hàng</a></li>
      <li><a href="#cta">Liên hệ</a></li>
    </ul>
    <div class="wnav-actions">
      <a href="?template=auth&action=login.view" class="wnav-btn wnav-btn--ghost">Đăng nhập</a>
      <a href="?template=auth&action=register.view" class="wnav-btn wnav-btn--solid">Đăng ký</a>
    </div>
    <button class="wnav-hamburger" id="menuToggle" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </nav>

  <!-- Hero: Full-bleed background -->
  <section class="whero" id="hero">
    <div class="whero-overlay"></div>
    <div class="whero-bg">
      <img src="https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=1600&q=80" alt="" aria-hidden="true"/>
    </div>
    <div class="whero-glow"></div>
    <div class="whero-inner">
      <div class="whero-tag">Quản lý tài chính thông minh</div>
      <h1>Kiểm soát <span class="gradient-text">tài chính</span><br>của bạn mỗi ngày</h1>
      <p>Theo dõi thu chi, lập ngân sách và đạt được mục tiêu tài chính cá nhân một cách dễ dàng cùng MoneyMaster.</p>
      <div class="whero-actions">
        <a href="?template=auth&action=register.view" class="btn-primary">
          Bắt đầu miễn phí
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
        <a href="?template=auth&action=login.view" class="btn-secondary">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Đăng nhập
        </a>
      </div>
      <div class="whero-stats">
        <div class="whero-stat">
          <strong>50K+</strong>
          <span>Người dùng</span>
        </div>
        <div class="whero-stat-dot"></div>
        <div class="whero-stat">
          <strong>₫50B+</strong>
          <span>Đã theo dõi</span>
        </div>
        <div class="whero-stat-dot"></div>
        <div class="whero-stat">
          <strong>4.9★</strong>
          <span>Đánh giá</span>
        </div>
      </div>
    </div>
    <div class="whero-scroll-hint">
      <svg width="20" height="28" viewBox="0 0 20 28" fill="none"><rect x="1.5" y="1.5" width="17" height="25" rx="8.5" stroke="rgba(255,255,255,0.4)" stroke-width="2"/><circle cx="10" cy="9" r="2" fill="#0d9488"><animate attributeName="cy" values="9;15;9" dur="2s" repeatCount="indefinite"/><animate attributeName="opacity" values="1;0.3;1" dur="2s" repeatCount="indefinite"/></circle></svg>
    </div>
  </section>

  <!-- Parallax Divider -->
  <section class="wparallax">
    <div class="wparallax-bg">
      <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=1400&q=80" alt="" aria-hidden="true" class="wparallax-img" id="parallaxImg"/>
    </div>
    <div class="wparallax-overlay"></div>
    <div class="wparallax-content">
      <span class="wparallax-tag">Tại sao chọn MoneyMaster?</span>
      <h2>Công cụ tài chính <span class="gradient-text">thông minh</span> dành cho người Việt</h2>
      <p>Được thiết kế để giúp bạn hiểu rõ dòng tiền, tối ưu chi tiêu và đạt được tự do tài chính.</p>
    </div>
  </section>

  <!-- Stats -->
  <section class="wstats" id="stats">
    <div class="wstats-inner">
      <div class="wstats-item" data-delay="0">
        <span class="wstats-number" data-count="50000">0</span>
        <span class="wstats-label">Người dùng đăng ký</span>
      </div>
      <div class="wstats-divider"></div>
      <div class="wstats-item" data-delay="150">
        <span class="wstats-number" data-count="50000000000">0</span>
        <span class="wstats-label">Số tiền theo dõi</span>
      </div>
      <div class="wstats-divider"></div>
      <div class="wstats-item" data-delay="300">
        <span class="wstats-number" data-count="4.9">0</span>
        <span class="wstats-label">Đánh giá trung bình</span>
      </div>
      <div class="wstats-divider"></div>
      <div class="wstats-item" data-delay="450">
        <span class="wstats-number" data-count="99.9">0</span>
        <span class="wstats-label">Uptime %</span>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="wsection" id="features">
    <div class="wsection-header">
      <h2>Tính năng nổi bật</h2>
      <p>Mọi công cụ bạn cần để kiểm soát tài chính cá nhân</p>
    </div>
    <div class="wfeatures-grid" id="featureGrid">
      <div class="wfeature-card" data-tilt>
        <div class="wfeature-icon" style="background:linear-gradient(135deg,#0d9488,#0f766e)">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3>Theo dõi chi tiêu</h3>
        <p>Ghi chép và phân tích từng khoản thu chi hàng ngày một cách chi tiết, trực quan.</p>
      </div>
      <div class="wfeature-card" data-tilt>
        <div class="wfeature-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <h3>Lập ngân sách</h3>
        <p>Đặt hạn mức chi tiêu cho từng danh mục và nhận cảnh báo khi sắp vượt ngân sách.</p>
      </div>
      <div class="wfeature-card" data-tilt>
        <div class="wfeature-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </div>
        <h3>Báo cáo thông minh</h3>
        <p>Biểu đồ và báo cáo trực quan giúp bạn hiểu rõ thói quen tài chính của bản thân.</p>
      </div>
      <div class="wfeature-card" data-tilt>
        <div class="wfeature-icon" style="background:linear-gradient(135deg,#38bdf8,#0284c7)">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21v-7M10 21V9M16 21v-5M22 21V3"/></svg>
        </div>
        <h3>Đồng bộ hóa</h3>
        <p>Dữ liệu được đồng bộ an toàn, truy cập mọi lúc mọi nơi trên tất cả thiết bị.</p>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="wsection" id="how-it-works">
    <div class="wsection-header">
      <h2>Cách hoạt động</h2>
      <p>Bắt đầu quản lý tài chính chỉ với 3 bước đơn giản</p>
    </div>
    <div class="whow-grid">
      <div class="whow-card">
        <div class="whow-number"><span>1</span></div>
        <div class="whow-content">
          <div class="whow-image">
            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=200&q=80" alt="Tạo tài khoản" loading="lazy"/>
          </div>
          <h3>Tạo tài khoản</h3>
          <p>Đăng ký tài khoản miễn phí và bảo mật chỉ trong vài giây. Không cần thẻ tín dụng.</p>
        </div>
      </div>
      <div class="whow-card">
        <div class="whow-number"><span>2</span></div>
        <div class="whow-content">
          <div class="whow-image">
            <img src="https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=200&q=80" alt="Ghi chép giao dịch" loading="lazy"/>
          </div>
          <h3>Ghi chép giao dịch</h3>
          <p>Thêm các khoản thu chi hàng ngày với danh mục thông minh và ghi chú chi tiết.</p>
        </div>
      </div>
      <div class="whow-card">
        <div class="whow-number"><span>3</span></div>
        <div class="whow-content">
          <div class="whow-image">
            <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=200&q=80" alt="Phân tích và tối ưu" loading="lazy"/>
          </div>
          <h3>Phân tích & tối ưu</h3>
          <p>Xem báo cáo trực quan, nhận insights và điều chỉnh thói quen chi tiêu hợp lý.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Image Gallery Showcase -->
  <section class="wsection wgallery-section">
    <div class="wsection-header">
      <h2>Trải nghiệm trực quan</h2>
      <p>Giao diện đẹp, dễ dùng, thông tin rõ ràng</p>
    </div>
    <div class="wgallery">
      <div class="wgallery-item wgallery-item--large">
        <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&q=80" alt="Quản lý chi tiêu trên điện thoại" loading="lazy"/>
        <div class="wgallery-overlay">
          <span>Theo dõi mọi lúc</span>
        </div>
      </div>
      <div class="wgallery-item">
        <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=400&q=80" alt="Báo cáo tài chính" loading="lazy"/>
        <div class="wgallery-overlay">
          <span>Báo cáo trực quan</span>
        </div>
      </div>
      <div class="wgallery-item">
        <img src="https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?w=400&q=80" alt="Lập ngân sách" loading="lazy"/>
        <div class="wgallery-overlay">
          <span>Lập ngân sách</span>
        </div>
      </div>
      <div class="wgallery-item">
        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=400&q=80" alt="Phân tích dữ liệu" loading="lazy"/>
        <div class="wgallery-overlay">
          <span>Phân tích thông minh</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Marquee -->
  <div class="wmarquee">
    <div class="wmarquee-track">
      <span>Kiểm soát tài chính</span>
      <span>✦</span>
      <span>Tiết kiệm thông minh</span>
      <span>✦</span>
      <span>Đầu tư tương lai</span>
      <span>✦</span>
      <span>An toàn & Bảo mật</span>
      <span>✦</span>
      <span>Kiểm soát tài chính</span>
      <span>✦</span>
      <span>Tiết kiệm thông minh</span>
      <span>✦</span>
      <span>Đầu tư tương lai</span>
      <span>✦</span>
      <span>An toàn & Bảo mật</span>
      <span>✦</span>
    </div>
  </div>

  <!-- Testimonials -->
  <section class="wsection" id="testimonials">
    <div class="wsection-header">
      <h2>Khách hàng nói gì?</h2>
      <p>Hàng ngàn người dùng đã cải thiện thói quen tài chính với MoneyMaster</p>
    </div>
    <div class="wtestim-grid">
      <div class="wtestim-card">
        <div class="wtestim-stars">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <p>"Nhờ MoneyMaster, tôi đã tiết kiệm được 25% thu nhập mỗi tháng. Biểu đồ trực quan giúp tôi thấy ngay những khoản chi không cần thiết."</p>
        <div class="wtestim-author">
          <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=80&q=80" alt="Nguyễn Thị Lan" loading="lazy"/>
          <div>
            <strong>Nguyễn Thị Lan</strong>
            <span>Nhân viên văn phòng</span>
          </div>
        </div>
      </div>
      <div class="wtestim-card">
        <div class="wtestim-stars">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <p>"Giao diện đẹp, dễ sử dụng. Tính năng lập ngân sách giúp tôi không còn lo lắng về chi tiêu cuối tháng. Rất đáng dùng!"</p>
        <div class="wtestim-author">
          <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&q=80" alt="Trần Văn Minh" loading="lazy"/>
          <div>
            <strong>Trần Văn Minh</strong>
            <span>Freelancer</span>
          </div>
        </div>
      </div>
      <div class="wtestim-card">
        <div class="wtestim-stars">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="#f59e0b" stroke="#f59e0b"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <p>"Tôi đã dùng nhiều app quản lý chi tiêu, nhưng MoneyMaster là app duy nhất giúp tôi gắn bó lâu dài. Báo cáo chi tiết và trực quan."</p>
        <div class="wtestim-author">
          <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=80&q=80" alt="Phạm Hồng Nhung" loading="lazy"/>
          <div>
            <strong>Phạm Hồng Nhung</strong>
            <span>Chủ doanh nghiệp nhỏ</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section class="wsection" id="cta">
    <div class="wcta">
      <div class="wcta-bg"></div>
      <h2>Sẵn sàng kiểm soát tài chính?</h2>
      <p>Tham gia MoneyMaster ngay hôm nay — hoàn toàn miễn phí. Không rủi ro, không cam kết.</p>
      <a href="?template=auth&action=register.view" class="wcta-btn">
        Tạo tài khoản miễn phí
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
      </a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="wfooter">
    <div class="wfooter-inner">
      <div class="wfooter-brand">
        <span class="wfooter-brand-icon">
          <svg width="22" height="22" viewBox="0 0 28 28" fill="none"><rect width="28" height="28" rx="8" fill="#0d9488"/><path d="M8 18V12h3v6H8Zm4.5 0V8h3v10h-4.5Zm4.5 0v-4h3v4h-3Z" fill="#fff" opacity="0.9"/></svg>
        </span>
        MoneyMaster
      </div>
      <p class="wfooter-copy">&copy; 2026 MoneyMaster. Tất cả quyền được bảo lưu.</p>
    </div>
  </footer>
</main>

<script>
(function(){
  var navbar = document.getElementById('navbar');
  var menuToggle = document.getElementById('menuToggle');
  var navLinks = document.getElementById('navLinks');

  if (navbar) {
    var ticking = false;
    window.addEventListener('scroll', function(){
      if (!ticking) {
        requestAnimationFrame(function(){
          navbar.classList.toggle('scrolled', window.scrollY > 20);
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  if (menuToggle && navLinks) {
    menuToggle.addEventListener('click', function(e){
      e.stopPropagation();
      navLinks.classList.toggle('open');
      menuToggle.classList.toggle('open');
    });
    document.addEventListener('click', function(){
      navLinks.classList.remove('open');
      menuToggle.classList.remove('open');
    });
    navLinks.addEventListener('click', function(e){ e.stopPropagation(); });
  }

  /* Scroll reveal observer */
  var observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll('.wsection-header, .wfeature-card, .whow-card, .wtestim-card, .wcta, .wgallery-item').forEach(function(el){
    observer.observe(el);
  });

  /* Stats counter */
  var statsSection = document.querySelector('.wstats');
  var statsObserved = false;
  var statsObserver = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if (entry.isIntersecting && !statsObserved) {
        statsObserved = true;
        entry.target.classList.add('visible');
        animateCounters();
        statsObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });
  if (statsSection) statsObserver.observe(statsSection);

  function animateCounters(){
    document.querySelectorAll('.wstats-number').forEach(function(counter){
      var target = parseFloat(counter.getAttribute('data-count'));
      var isFloat = target % 1 !== 0;
      var duration = 2000;
      var start = performance.now();

      function update(now){
        var progress = Math.min((now - start) / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        var current = target * eased;

        if (isFloat) {
          counter.textContent = current.toFixed(1) + (target >= 10 ? '%' : '');
        } else if (target >= 1000000000) {
          counter.textContent = '₫' + (current / 1000000000).toFixed(0) + 'B+';
        } else if (target >= 1000) {
          counter.textContent = Math.floor(current / 1000).toLocaleString() + 'K+';
        } else {
          counter.textContent = Math.floor(current).toLocaleString();
        }
        if (progress < 1) requestAnimationFrame(update);
      }
      requestAnimationFrame(update);
    });
  }

  /* Smooth scroll for anchor links */
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor){
    anchor.addEventListener('click', function(e){
      var id = this.getAttribute('href');
      if (id === '#') return;
      var target = document.querySelector(id);
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  });

  /* 3D Tilt on feature cards */
  var tiltCards = document.querySelectorAll('[data-tilt]');
  if (tiltCards.length && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    tiltCards.forEach(function(card){
      card.addEventListener('mousemove', function(e){
        var rect = card.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        var centerX = rect.width / 2;
        var centerY = rect.height / 2;
        var rotateX = (y - centerY) / centerY * -8;
        var rotateY = (x - centerX) / centerX * 8;
        card.style.setProperty('--rx', rotateX + 'deg');
        card.style.setProperty('--ry', rotateY + 'deg');
        card.style.setProperty('--s', '1.02');
      });
      card.addEventListener('mouseleave', function(){
        card.style.setProperty('--rx', '0deg');
        card.style.setProperty('--ry', '0deg');
        card.style.setProperty('--s', '1');
      });
    });
  }

  /* Parallax scroll for hero + divider images */
  var parallaxImg = document.getElementById('parallaxImg');
  var heroBg = document.querySelector('.whero-bg img');
  var pTick = false;
  if ((parallaxImg || heroBg) && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    window.addEventListener('scroll', function(){
      if (!pTick) {
        requestAnimationFrame(function(){
          var sy = window.scrollY;
          if (parallaxImg) parallaxImg.style.transform = 'translateY(' + (sy * 0.2) + 'px)';
          if (heroBg) heroBg.style.transform = 'translateY(' + (sy * 0.15) + 'px)';
          pTick = false;
        });
        pTick = true;
      }
    }, { passive: true });
  }
})();
</script>

<?php
layout("footer", ["js" => []]);
?>
