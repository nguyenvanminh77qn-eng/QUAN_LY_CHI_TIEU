<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

$loginToken = getSession('loginToken');
if (empty($loginToken)) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'user') {
    redirect("?template=admin&action=inbox");
}

$userId   = (int)getSession('id');
$username = getSession('username');
$view     = 'inbox';
?>
<?php layout("header", [
    "title" => "Hộp thư — " . htmlspecialchars($username ?? ''),
    "css"   => ["layout/sidebar", "pages/user/feedback"]
]); ?>
<div class="app-container">
    <?php layout("sidebar", ["view" => $view]); ?>
    <main class="main-content">
        <header class="top-header">
            <div class="header-left">
                <button id="menu-toggle" class="btn-menu" type="button" aria-label="Mở/đóng sidebar">☰</button>
                <div>
                    <span class="subtitle">QUẢN LÝ CHI TIÊU</span>
                    <h1>Hộp thư hỗ trợ</h1>
                </div>
            </div>
            <div class="header-right">
                <div class="user-box">👤 <?= htmlspecialchars($username ?? '') ?></div>
            </div>
        </header>

        <div class="page-content" style="padding:0;display:flex;flex-direction:column;flex:1;min-height:0;">
            <!-- Full-page chat UI -->
            <div class="fb-page">
                <!-- Header bar -->
                <div class="fb-page-header">
                    <span class="material-symbols-outlined" aria-hidden="true">forum</span>
                    <span class="fb-page-title">Hỗ trợ trực tuyến</span>
                    <button class="fb-new-btn" type="button" title="Soạn tin nhắn mới" aria-label="Soạn tin nhắn mới" style="width:36px;height:36px;">
                        <span class="material-symbols-outlined">edit_square</span>
                    </button>
                    <span class="fb-page-count" id="fb-page-count" aria-live="polite">0 cuộc hội thoại</span>
                </div>

                <!-- Body: sidebar list + main panel -->
                <div class="fb-page-body">

                    <!-- Left: thread list -->
                    <div class="fb-page-sidebar">
                        <!-- Search -->
                        <div class="fb-search-bar">
                            <span class="material-symbols-outlined fb-search-icon" aria-hidden="true">search</span>
                            <input type="search" class="fb-search-input" placeholder="Tìm cuộc trò chuyện..." aria-label="Tìm kiếm cuộc trò chuyện">
                        </div>
                        <div class="fb-page-threads" id="fb-page-threads" role="list" aria-label="Danh sách cuộc trò chuyện"></div>
                    </div>

                    <!-- Right: main panel (empty / compose / thread view) -->
                    <div class="fb-page-main">

                        <!-- Empty state -->
                        <div class="fb-page-empty" role="status">
                            <span class="material-symbols-outlined" aria-hidden="true">forum</span>
                            <div class="fb-page-empty-title">Chưa có cuộc trò chuyện nào</div>
                            <div class="fb-page-empty-desc">Chọn một cuộc trò chuyện hoặc nhấn nút bút chì để gửi tin nhắn mới cho Admin.</div>
                        </div>

                        <!-- Compose new thread -->
                        <div class="fb-compose" id="fb-page-compose" aria-label="Soạn tin nhắn mới">
                            <div class="fb-compose-title">Tin nhắn mới</div>
                            <div class="fb-compose-receiver">
                                <label for="compose-admin-select">Gửi đến Admin</label>
                                <select class="fb-select-admin" id="compose-admin-select" aria-label="Chọn Admin nhận tin nhắn"></select>
                            </div>
                            <div class="fb-compose-field">
                                <label for="compose-content">Nội dung</label>
                                <textarea class="fb-compose-input-content" id="compose-content"
                                    placeholder="Nhập nội dung tin nhắn của bạn..." rows="5"
                                    aria-label="Nội dung tin nhắn"></textarea>
                            </div>
                            <div style="display:flex;gap:8px">
                                <button class="fb-compose-submit" type="button">
                                    <span class="material-symbols-outlined" style="font-size:16px">send</span>
                                    Gửi tin nhắn
                                </button>
                                <button class="fb-compose-cancel" type="button">Hủy</button>
                            </div>
                        </div>

                        <!-- Thread view (messages + reply) -->
                        <div class="fb-thread-view" id="fb-page-thread-view" aria-label="Cuộc trò chuyện">
                            <button class="fb-thread-back" type="button" aria-label="Quay lại danh sách">
                                <span class="fb-back-arrow">
                                    <span class="material-symbols-outlined">arrow_back_ios_new</span>
                                </span>
                                <div class="fb-thread-header-avatar fb-avatar is-admin" aria-hidden="true">A</div>
                                <div class="fb-thread-back-info">
                                    <div class="fb-thread-back-name">Trò chuyện</div>
                                    <div class="fb-thread-back-status">Đang hoạt động</div>
                                </div>
                            </button>

                            <div class="fb-msgs" id="fb-page-msgs"
                                role="log" aria-live="polite" aria-label="Tin nhắn trong cuộc trò chuyện"></div>

                            <div class="fb-typing-indicator" aria-live="polite"></div>

                            <div class="fb-reply-area">
                                <div class="fb-reply-wrap">
                                    <textarea class="fb-reply-input" id="fb-page-reply-input"
                                        placeholder="Nhập tin nhắn..." rows="1"
                                        aria-label="Nhập tin nhắn trả lời"></textarea>
                                </div>
                                <button class="fb-reply-btn" id="fb-page-reply-btn" type="button" aria-label="Gửi tin nhắn">
                                    <span class="material-symbols-outlined">send</span>
                                </button>
                            </div>
                        </div>
                    </div><!-- /fb-page-main -->
                </div><!-- /fb-page-body -->
            </div><!-- /fb-page -->
        </div><!-- /page-content -->
    </main>
</div>
<?php layout("footer", ["js" => ["pages/sidebar", "pages/user/feedback"]]); ?>
