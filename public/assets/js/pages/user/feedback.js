/**
 * Realtime Chat — QUAN LY CHI TIEU
 * Long-polling, dual-mode: floating widget + full-page inbox.
 */
(function () {
    'use strict';

    if (window.__fbInitialized) return;
    window.__fbInitialized = true;

    /* ── STATE ── */
    var role             = document.body.getAttribute('data-role') || 'user';
    var userHash         = document.body.getAttribute('data-user-hash') || '0';
    var threads          = {};
    var threadList       = [];
    var unreadTotal      = 0;
    var prevUnreadCount  = -1;
    var toastEl          = null;
    var toastTimer       = null;
    var initialFetchDone = false;
    var currentThreadId  = 0;
    var lastGlobalId     = 0;
    var lastThreadId     = 0;
    var globalPollXhr    = null;
    var threadPollXhr    = null;
    var globalReconnect  = null;
    var threadReconnect  = null;
    var adminList        = [];
    var userList         = [];
    var isFullPage       = false;
    var eventsBound      = false;
    var searchQuery      = '';

    /* ── DOM REFS ── */
    var badge, sidebarBadge, fbToast;
    var fbWidget, fbBubble, fbChatbox;
    var fbThreadListEl, fbThreadView, fbMsgs, fbReplyInput, fbReplyBtn;
    var fbCompose, fbComposeContent, fbComposeBtn, fbComposeReceiver;
    var pageThreadListEl, pageThreadView, pageMsgs, pageReplyInput, pageReplyBtn;
    var pageEmpty, pageCount, pageCompose;
    var curList, curView, curMsgs, curInput, curBtn;

    /* ════════════════════════════════════
       BUILD FLOATING WIDGET
    ════════════════════════════════════ */
    function buildWidget() {
        if (document.getElementById('fb-widget')) return;
        var receiverHtml = role === 'user'
            ? '<div class="fb-compose-receiver"><label>Gửi đến Admin</label><select class="fb-select-admin" aria-label="Chọn Admin"></select></div>'
            : '<div class="fb-compose-receiver"><label>Gửi đến User</label><select class="fb-select-user" aria-label="Chọn User"></select></div>';

        var w = document.createElement('div');
        w.className = 'fb-widget';
        w.id = 'fb-widget';
        w.innerHTML = [
            '<div class="fb-chatbox" role="dialog" aria-label="Hỗ trợ trực tuyến">',
              '<div class="fb-chatbox-header">',
                '<span class="material-symbols-outlined" aria-hidden="true">forum</span>',
                '<span class="fb-chatbox-title">Hỗ trợ trực tuyến</span>',
                '<button class="fb-new-btn" type="button" aria-label="Soạn tin nhắn mới">',
                  '<span class="material-symbols-outlined">edit_square</span>',
                '</button>',
                '<button class="fb-chatbox-close" type="button" aria-label="Đóng">',
                  '<span class="material-symbols-outlined">close</span>',
                '</button>',
              '</div>',
              '<div class="fb-thread-list" role="list"></div>',
              '<div class="fb-compose">',
                '<div class="fb-compose-title">Tin nhắn mới</div>',
                receiverHtml,
                '<div class="fb-compose-field">',
                  '<label>Nội dung</label>',
                  '<textarea class="fb-compose-input-content" placeholder="Nhập nội dung..." rows="4"></textarea>',
                '</div>',
                '<div style="display:flex;gap:8px">',
                '<button class="fb-compose-submit" type="button">',
                  '<span class="material-symbols-outlined" style="font-size:16px">send</span> Gửi tin nhắn',
                '</button>',
                '<button class="fb-compose-cancel" type="button">Hủy</button>',
              '</div>',
              '</div>',
              '<div class="fb-thread-view">',
                '<button class="fb-thread-back" type="button" aria-label="Quay lại">',
                  '<span class="fb-back-arrow"><span class="material-symbols-outlined">arrow_back_ios_new</span></span>',
                  '<div class="fb-thread-header-avatar fb-avatar is-user" aria-hidden="true">?</div>',
                  '<div class="fb-thread-back-info">',
                    '<div class="fb-thread-back-name">Trò chuyện</div>',
                    '<div class="fb-thread-back-status">Đang hoạt động</div>',
                  '</div>',
                '</button>',
                '<div class="fb-msgs" role="log" aria-live="polite"></div>',
                '<div class="fb-typing-indicator" aria-live="polite"></div>',
                '<div class="fb-reply-area">',
                  '<div class="fb-reply-wrap">',
                    '<textarea class="fb-reply-input" placeholder="Nhập tin nhắn..." rows="1"></textarea>',
                  '</div>',
                  '<button class="fb-reply-btn" type="button" aria-label="Gửi">',
                    '<span class="material-symbols-outlined">send</span>',
                  '</button>',
                '</div>',
              '</div>',
            '</div>',
            '<button class="fb-bubble" type="button" aria-label="Hộp thư hỗ trợ">',
              '<span class="material-symbols-outlined" aria-hidden="true">chat</span>',
              '<span class="fb-badge is-hidden">0</span>',
            '</button>',
        ].join('');
        document.body.appendChild(w);
    }

    /* ════════════════════════════════════
       INIT
    ════════════════════════════════════ */
    function init() {
        userHash = document.body.getAttribute('data-user-hash') || '0';
        role   = document.body.getAttribute('data-role') || 'user';
        if (!userHash || userHash === '0') return;

        isFullPage = !!document.querySelector('.fb-page');
        if (!isFullPage) buildWidget();

        setupRefs();
        bindEvents();
        loadInitialData();
    }

    function setupRefs() {
        var w = document.getElementById('fb-widget');
        if (w) {
            fbWidget  = w;
            fbBubble  = w.querySelector('.fb-bubble');
            fbChatbox = w.querySelector('.fb-chatbox');
            badge     = w.querySelector('.fb-badge');
            if (!isFullPage) {
                fbCompose      = w.querySelector('.fb-compose');
                fbThreadListEl = w.querySelector('.fb-thread-list');
                fbThreadView   = w.querySelector('.fb-thread-view');
                fbMsgs         = w.querySelector('.fb-msgs');
                fbReplyInput   = w.querySelector('.fb-reply-input');
                fbReplyBtn     = w.querySelector('.fb-reply-btn');
                if (fbCompose) {
                    fbComposeContent  = fbCompose.querySelector('.fb-compose-input-content');
                    fbComposeBtn      = fbCompose.querySelector('.fb-compose-submit');
                    fbComposeReceiver = fbCompose.querySelector(role === 'admin' ? '.fb-select-user' : '.fb-select-admin');
                }
            }
        }

        if (isFullPage) {
            pageThreadListEl = document.getElementById('fb-page-threads');
            pageThreadView   = document.getElementById('fb-page-thread-view');
            pageMsgs         = document.getElementById('fb-page-msgs');
            pageReplyInput   = document.getElementById('fb-page-reply-input');
            pageReplyBtn     = document.getElementById('fb-page-reply-btn');
            pageEmpty        = document.querySelector('.fb-page-empty');
            pageCount        = document.getElementById('fb-page-count');
            pageCompose      = document.getElementById('fb-page-compose');
            if (pageCompose) {
                fbCompose         = pageCompose;
                fbComposeContent  = pageCompose.querySelector('.fb-compose-input-content');
                fbComposeBtn      = pageCompose.querySelector('.fb-compose-submit');
                fbComposeReceiver = pageCompose.querySelector(role === 'admin' ? '.fb-select-user' : '.fb-select-admin');
            }
        }

        sidebarBadge = document.getElementById('sidebar-inbox-badge');
        refreshActiveRefs();
    }

    function refreshActiveRefs() {
        if (isFullPage) {
            curList  = pageThreadListEl;
            curView  = pageThreadView;
            curMsgs  = pageMsgs;
            curInput = pageReplyInput;
            curBtn   = pageReplyBtn;
        } else {
            curList  = fbThreadListEl;
            curView  = fbThreadView;
            curMsgs  = fbMsgs;
            curInput = fbReplyInput;
            curBtn   = fbReplyBtn;
        }
    }

    /* ════════════════════════════════════
       EVENTS
    ════════════════════════════════════ */
    function bindEvents() {
        if (eventsBound) return;
        eventsBound = true;

        if (fbBubble) {
            fbBubble.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleWidget();
            });
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('.fb-chatbox-close'))  { closeWidget(); return; }
            if (e.target.closest('.fb-thread-back'))    { backToList();  return; }
            if (e.target.closest('.fb-new-btn'))        { showCompose(); return; }
            if (e.target.closest('.fb-compose-submit')) { submitCompose(); return; }
            if (e.target.closest('.fb-compose-cancel')) { showList(); return; }

            var row = e.target.closest('.fb-thread-item');
            if (row) {
                var tid = parseInt(row.getAttribute('data-thread-id') || '0', 10);
                if (tid) openThread(tid);
                return;
            }

            var replyBtn = e.target.closest('.fb-reply-btn');
            if (replyBtn) { sendReply(replyBtn); return; }

            if (fbWidget && fbChatbox && fbChatbox.classList.contains('is-open') && !fbWidget.contains(e.target)) {
                closeWidget();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (fbCompose && fbCompose.classList.contains('is-open')) { showList(); return; }
                if (fbChatbox && fbChatbox.classList.contains('is-open')) { closeWidget(); return; }
                if (isFullPage && currentThreadId) { backToList(); return; }
            }
            var inp = e.target.closest('.fb-reply-input');
            if (inp && e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(inp); }
            var comp = e.target.closest('.fb-compose-input-content');
            if (comp && e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); submitCompose(); }
        });

        document.addEventListener('input', function (e) {
            if (e.target.closest('.fb-reply-input')) autoResize(e.target.closest('.fb-reply-input'));
            if (e.target.closest('.fb-search-input')) {
                searchQuery = e.target.closest('.fb-search-input').value.toLowerCase().trim();
                renderThreadList();
            }
        });
    }

    /* ════════════════════════════════════
       WIDGET OPEN / CLOSE
    ════════════════════════════════════ */
    function toggleWidget() {
        if (!fbChatbox) return;
        if (fbChatbox.classList.contains('is-open')) closeWidget();
        else openWidget();
    }
    function openWidget() {
        if (!fbChatbox) return;
        fbChatbox.classList.add('is-open');
        if (fbBubble) fbBubble.style.display = 'none';
        if (!currentThreadId) showList();
    }
    function closeWidget() {
        if (!fbChatbox) return;
        fbChatbox.classList.remove('is-open');
        setTimeout(function () { if (fbBubble) fbBubble.style.display = ''; }, 220);
    }

    /* ════════════════════════════════════
       VIEW SWITCHING
    ════════════════════════════════════ */
    function showList() {
        if (curView)   curView.classList.remove('is-open');
        if (curList)   curList.style.display = '';
        if (fbCompose) fbCompose.classList.remove('is-open');
        if (isFullPage && pageEmpty) pageEmpty.style.display = 'flex';
        if (pageCount) pageCount.textContent = threadList.length + ' cuộc hội thoại';
        currentThreadId = 0;
        stopThreadPolling();
        startGlobalPolling();
        renderThreadList();
    }

    function showThread() {
        if (curList)   curList.style.display = 'none';
        if (fbCompose) fbCompose.classList.remove('is-open');
        if (curView)   curView.classList.add('is-open');
        if (isFullPage && pageEmpty) pageEmpty.style.display = 'none';
    }

    function showCompose() {
        if (curView)   curView.classList.remove('is-open');
        if (curList)   curList.style.display = 'none';
        if (isFullPage && pageEmpty) pageEmpty.style.display = 'none';
        if (fbCompose) {
            fbCompose.classList.add('is-open');
            if (fbComposeContent) {
                fbComposeContent.value = '';
                setTimeout(function () { fbComposeContent.focus(); }, 80);
            }
            populateReceivers();
        }
        currentThreadId = 0;
        stopThreadPolling();
    }

    function backToList() { showList(); }

    /* ════════════════════════════════════
       LOAD DATA
    ════════════════════════════════════ */
    function loadInitialData() {
        var urlThread = parseInt(getUrlParam('thread_id') || '0', 10);

        // Load admin/user list for compose dropdown
        ajaxGet('src/app/Http/Api/feedback.php?action=' + (role === 'admin' ? 'get_users' : 'get_admins'), function (res) {
            if (res && res.success) {
                if (role === 'admin') userList  = res.data.users  || [];
                else                 adminList = res.data.admins || [];
            }
        });

        // Get starting poll ID + unread count, then load threads
        ajaxGet('src/app/Http/Api/feedback.php?action=get_latest_id', function (res) {
            if (res && res.success) {
                lastGlobalId = res.data.last_id      || 0;
                unreadTotal  = res.data.unread_count || 0;
                prevUnreadCount = unreadTotal;
                updateBadge();
                initialFetchDone = true;
            }
            loadThreads(function () {
                startGlobalPolling();
                startUnreadPolling();
                if (urlThread) {
                    if (!isFullPage) openWidget();
                    openThread(urlThread);
                }
            });
        });
    }

    /* ════════════════════════════════════
       COMPOSE — receiver dropdown
    ════════════════════════════════════ */
    function populateReceivers() {
        if (!fbComposeReceiver) return;
        var list = role === 'admin' ? userList : adminList;
        var html = '<option value="">-- Chọn người nhận --</option>';
        list.forEach(function (u) {
            var hasConv = threadList.some(function (t) {
                return parseInt(t.sender_id, 10) === u.id || parseInt(t.receiver_id, 10) === u.id;
            });
            var suffix = role === 'admin'
                ? (hasConv ? ' (đang có cuộc trò chuyện)' : '')
                : ' (Admin)' + (hasConv ? ' ✓' : '');
            html += '<option value="' + u.id + '" data-has-conv="' + (hasConv ? '1' : '0') + '">'
                  + escHtml(u.username + suffix) + '</option>';
        });
        fbComposeReceiver.innerHTML = html;
        updateComposeHint();
        fbComposeReceiver.onchange = updateComposeHint;
    }

    function updateComposeHint() {
        if (!fbComposeReceiver || !fbCompose) return;
        var opt     = fbComposeReceiver.options[fbComposeReceiver.selectedIndex];
        var hasConv = opt && opt.getAttribute('data-has-conv') === '1';
        var recId   = opt ? parseInt(opt.value, 10) : 0;
        var existing = null;
        if (hasConv && recId) {
            existing = threadList.find(function (t) {
                return parseInt(t.sender_id, 10) === recId || parseInt(t.receiver_id, 10) === recId;
            }) || null;
        }
        var hint = fbCompose.querySelector('.fb-compose-hint');
        if (!hint) {
            hint = document.createElement('div');
            hint.className = 'fb-compose-hint';
            hint.style.cssText = 'margin-bottom:14px;padding:10px 14px;border-radius:10px;' +
                'background:rgba(13,148,136,0.1);border:1px solid rgba(13,148,136,0.25);' +
                'font-size:12.5px;color:var(--fb-accent);font-weight:600;' +
                'display:none;align-items:center;gap:8px;cursor:pointer;transition:background 0.2s;';
            var field = fbCompose.querySelector('.fb-compose-field');
            if (field) fbCompose.insertBefore(hint, field);
        }
        if (existing) {
            var tid = existing.id;
            hint.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:\'FILL\' 1">chat_bubble</span>' +
                'Đã có cuộc trò chuyện. <u>Mở ngay</u>';
            hint.style.display = 'flex';
            hint.onclick = function () { openThread(parseInt(tid, 10)); };
        } else {
            hint.style.display = 'none';
        }
    }

    /* ════════════════════════════════════
       THREAD LIST
    ════════════════════════════════════ */
    function loadThreads(cb) {
        ajaxGet('src/app/Http/Api/feedback.php?action=get_threads', function (data) {
            if (data && data.success) {
                threads    = {};
                threadList = data.data.threads || [];
                threadList.forEach(function (t) { threads[t.id] = t; });
                if (!currentThreadId) renderThreadList();
            }
            if (cb) cb();
        });
    }

    function renderThreadList() {
        var list = curList;
        if (!list) return;

        var filtered = threadList;
        if (searchQuery) {
            filtered = threadList.filter(function (t) {
                var n = getThreadName(t).toLowerCase();
                var p = (t.last_message || t.message_content || '').toLowerCase();
                return n.indexOf(searchQuery) !== -1 || p.indexOf(searchQuery) !== -1;
            });
        }

        if (filtered.length === 0) {
            list.innerHTML =
                '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;' +
                'gap:12px;padding:48px 20px;text-align:center;">' +
                '<span class="material-symbols-outlined" style="font-size:52px;color:var(--fb-text-tertiary);' +
                'opacity:0.5;font-variation-settings:\'FILL\' 1">forum</span>' +
                '<div style="font-size:15px;font-weight:700;color:var(--fb-text)">' +
                escHtml(searchQuery ? 'Không tìm thấy kết quả' : 'Chưa có tin nhắn') + '</div>' +
                '<div style="font-size:13px;color:var(--fb-text-secondary)">' +
                escHtml(searchQuery ? 'Thử từ khóa khác' : 'Các cuộc trò chuyện sẽ xuất hiện ở đây') +
                '</div></div>';
            if (pageCount) pageCount.textContent = '0 cuộc hội thoại';
            return;
        }

        var html = '';
        filtered.forEach(function (t) {
            var isUnread = parseInt(t.unread_count || 0, 10) > 0;
            var isActive = parseInt(t.id, 10) === currentThreadId;
            var name     = getThreadName(t);
            var avCls    = getAvatarClass(t);
            var avChr    = (name || '?')[0].toUpperCase();
            var oOnl     = t.other_online ? parseInt(t.other_online, 10) === 1 : false;
            var oDot     = '<span class="fb-thread-online-dot ' + (oOnl ? 'is-online' : 'is-offline') + '"></span>';
            var preview  = t.last_message || t.message_content || '';
            var lastSH  = t.last_sender_hash || t.sender_hash || '0';
            var pfx      = lastSH === userHash ? 'Bạn: ' : '';
            var time     = fmtTime(t.last_activity || t.created_at);
            var rowCls   = 'fb-thread-item' + (isUnread ? ' is-unread' : ' is-read') + (isActive ? ' is-active' : '');

            html +=
                '<div class="' + rowCls + '" data-thread-id="' + t.id + '" tabindex="0">' +
                '<div class="fb-avatar ' + avCls + '">' + escHtml(avChr) + '</div>' +
                '<div class="fb-thread-info">' +
                  '<div class="fb-thread-name">' + oDot + escHtml(name) + '</div>' +
                  '<div class="fb-thread-preview">' + escHtml(pfx + trunc(preview, 42)) + '</div>' +
                '</div>' +
                '<div class="fb-thread-time">' + escHtml(time) +
                  (isUnread ? '<span class="fb-thread-badge">' + escHtml(String(t.unread_count)) + '</span>' : '') +
                '</div>' +
                '</div>';
        });
        list.innerHTML = html;
        if (pageCount) pageCount.textContent = filtered.length + ' cuộc hội thoại';
    }

    function getThreadName(t) {
        var mine = (t.sender_hash || '0') === userHash;
        var n    = mine ? t.receiver_username : t.sender_username;
        return n || (role === 'admin' ? 'Người dùng' : 'Admin');
    }

    function getAvatarClass(t) {
        if (role === 'admin') return 'is-user';
        var mine = (t.sender_hash || '0') === userHash;
        return (mine ? 'admin' : t.sender_type) === 'admin' ? 'is-admin' : 'is-user';
    }

    /* ════════════════════════════════════
       OPEN THREAD
    ════════════════════════════════════ */
    function openThread(id) {
        id = parseInt(id, 10);
        if (!id || currentThreadId === id) return;

        currentThreadId = id;
        lastThreadId    = 0;
        stopGlobalPolling();
        showThread();

        if (curMsgs) {
            curMsgs.innerHTML =
                '<div class="fb-skeleton">' +
                '<div class="fb-skeleton-row"></div>' +
                '<div class="fb-skeleton-row"></div>' +
                '<div class="fb-skeleton-row"></div>' +
                '</div>';
        }

        updateThreadHeader(id);
        highlightActive(id);

        ajaxGet('src/app/Http/Api/feedback.php?action=get_thread&thread_id=' + id, function (data) {
            if (data && data.success && data.data.messages) {
                var msgs = data.data.messages;
                if (msgs.length > 0) {
                    lastThreadId = parseInt(msgs[msgs.length - 1].id, 10);
                    lastGlobalId = Math.max(lastGlobalId, lastThreadId);
                }
                renderMsgs(msgs, false);
                markRead(id);
                startThreadPolling();
            }
        });
    }

    function updateThreadHeader(id) {
        var t      = threads[id];
        var name   = t ? getThreadName(t) : 'Trò chuyện';
        var avCls  = t ? getAvatarClass(t) : 'is-user';
        var avChr  = (name || '?')[0].toUpperCase();
        var online = t ? parseInt(t.other_online, 10) === 1 : false;
        [fbThreadView, pageThreadView].forEach(function (v) {
            if (!v) return;
            var el    = v.querySelector('.fb-thread-back-name');
            var av    = v.querySelector('.fb-thread-header-avatar');
            var st    = v.querySelector('.fb-thread-back-status');
            if (el) el.textContent = name;
            if (av) { av.className = 'fb-thread-header-avatar fb-avatar ' + avCls; av.textContent = avChr; }
            if (st) { st.textContent = online ? 'Đang hoạt động' : 'Đã offline'; st.classList.toggle('is-offline', !online); }
        });
    }

    function highlightActive(id) {
        document.querySelectorAll('.fb-thread-item').forEach(function (el) {
            el.classList.toggle('is-active', parseInt(el.getAttribute('data-thread-id'), 10) === id);
        });
    }

    /* ════════════════════════════════════
       RENDER MESSAGES
    ════════════════════════════════════ */
    function renderMsgs(msgs, append) {
        if (!curMsgs) return;
        if (!append && (!msgs || !msgs.length)) {
            curMsgs.innerHTML =
                '<div style="flex:1;display:flex;align-items:center;justify-content:center;' +
                'color:var(--fb-text-secondary);font-size:13px;text-align:center;padding:20px;">Bắt đầu cuộc trò chuyện...</div>';
            return;
        }

        var html = '';
        var lastDate = '';
        var prevSH  = null;

        if (append) {
            var divs = curMsgs.querySelectorAll('.fb-msg-date-divider');
            if (divs.length) lastDate = divs[divs.length - 1].textContent.trim();
        }

        msgs.forEach(function (m) {
            var dl = fmtDate(m.created_at);
            if (dl !== lastDate) {
                html += '<div class="fb-msg-date-divider">' + escHtml(dl) + '</div>';
                lastDate = dl;
                prevSId  = null;
            }
            var mine    = (m.sender_hash || '0') === userHash;
            var sameSdr = prevSH === (m.sender_hash || '0');
            prevSH     = m.sender_hash || '0';
            var cls     = 'fb-msg fb-msg--' + (mine ? 'outgoing' : 'incoming');
            var sOnl   = m.sender_online ? parseInt(m.sender_online, 10) === 1 : false;
            var dotCls = 'fb-online-dot ' + (sOnl ? 'is-online' : 'is-offline');
            var snHtml  = (!mine && !sameSdr && role === 'admin' && m.sender_username)
                ? '<div class="fb-msg-title"><span class="' + dotCls + '"></span>' + escHtml(m.sender_username) + '</div>' : '';
            var body    = escHtml(m.message_content || '').replace(/\n/g, '<br>');
            html +=
                '<div class="' + cls + '" data-msg-id="' + (m.id || '') + '">' +
                snHtml + body +
                '<span class="fb-msg-meta"><span>' + escHtml(fmtTime(m.created_at)) + '</span>' +
                (mine ? '<span class="material-symbols-outlined" style="font-size:12px;opacity:0.7;font-variation-settings:\'FILL\' 1">done_all</span>' : '') +
                '</span></div>';
        });

        if (append) curMsgs.insertAdjacentHTML('beforeend', html);
        else        curMsgs.innerHTML = html;

        setTimeout(function () {
            curMsgs.scrollTo({ top: curMsgs.scrollHeight, behavior: append ? 'smooth' : 'auto' });
        }, 40);
    }

    function markRead(threadId) {
        ajaxGet('src/app/Http/Api/feedback.php?action=mark_thread_read&thread_id=' + threadId, function () {
            if (threads[threadId]) threads[threadId].unread_count = 0;
            var idx = threadList.findIndex(function (t) { return parseInt(t.id, 10) === threadId; });
            if (idx >= 0) threadList[idx].unread_count = 0;
            ajaxGet('src/app/Http/Api/feedback.php?action=get_unread_count', function (res) {
                if (res && res.success) { unreadTotal = res.data.total_unread; updateBadge(); }
            });
            var row = document.querySelector('.fb-thread-item[data-thread-id="' + threadId + '"]');
            if (row) {
                row.classList.remove('is-unread');
                row.classList.add('is-read');
                var dot = row.querySelector('.fb-thread-badge');
                if (dot) dot.remove();
            }
        });
    }

    /* ════════════════════════════════════
       COMPOSE — send new thread
    ════════════════════════════════════ */
    function submitCompose() {
        if (!fbComposeContent || !fbComposeBtn) return;
        var content = fbComposeContent.value.trim();
        if (!content) { fbComposeContent.focus(); return; }
        var recId = fbComposeReceiver ? parseInt(fbComposeReceiver.value, 10) : 0;
        if (!recId) { showErr('Vui lòng chọn người nhận'); return; }

        fbComposeBtn.disabled = true;
        fbComposeBtn.innerHTML = '<span class="material-symbols-outlined" style="animation:fbShimmerAnim 0.8s infinite;font-size:16px">more_horiz</span> Đang gửi...';

        ajaxPost('src/app/Http/Api/feedback.php?action=send', 'content=' + encodeURIComponent(content) + '&receiver_id=' + recId, function (res) {
            fbComposeBtn.disabled = false;
            fbComposeBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px">send</span> Gửi tin nhắn';
            if (res && res.success && res.data.message) {
                fbComposeContent.value = '';
                var rootId = res.data.message.root_id
                    || (res.data.message.parent_id ? parseInt(res.data.message.parent_id, 10) : parseInt(res.data.message.id, 10));
                loadThreads(function () {
                    if (!isFullPage) openWidget();
                    openThread(rootId);
                });
            } else {
                showErr((res && res.message) || 'Lỗi hệ thống');
            }
        });
    }

    /* ════════════════════════════════════
       SEND REPLY
    ════════════════════════════════════ */
    function sendReply(el) {
        if (!currentThreadId) return;
        var area = el.closest ? el.closest('.fb-reply-area') : null;
        var inp  = area ? area.querySelector('.fb-reply-input') : curInput;
        if (!inp) inp = curInput;
        if (!inp) return;

        var text = inp.value.trim();
        if (!text) return;
        inp.value = '';
        autoResize(inp);

        var btn = area ? area.querySelector('.fb-reply-btn') : curBtn;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;animation:fbShimmerAnim 0.8s infinite">more_horiz</span>';
        }

        ajaxPost('src/app/Http/Api/feedback.php?action=send', 'parent_id=' + currentThreadId + '&content=' + encodeURIComponent(text), function (res) {
            if (btn) { btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined">send</span>'; }
            if (res && res.success && res.data.message) {
                renderMsgs([res.data.message], true);
                var nid = parseInt(res.data.message.id, 10);
                lastThreadId = Math.max(lastThreadId, nid);
                lastGlobalId = Math.max(lastGlobalId, nid);
                loadThreads();
            } else {
                showErr((res && res.message) || 'Lỗi hệ thống');
                inp.value = text;
            }
        });
    }

    /* ════════════════════════════════════
       LONG POLLING
    ════════════════════════════════════ */
    function startGlobalPolling() { stopGlobalPolling(); pollGlobal(); }
    function stopGlobalPolling() {
        if (globalPollXhr)  { try { globalPollXhr.abort(); } catch(e){} globalPollXhr = null; }
        if (globalReconnect){ clearTimeout(globalReconnect); globalReconnect = null; }
    }

    /* ── Fallback: 2s interval polling for unread count ── */
    function startUnreadPolling() {
        if (window.__fbUnreadInt) return;
        window.__fbUnreadInt = setInterval(function () {
            if (!initialFetchDone) return;
            ajaxGet('src/app/Http/Api/feedback.php?action=get_unread_count', function (data) {
                if (!data || !data.success) return;
                var c = parseInt(data.data.total_unread || 0, 10);
                if (c === prevUnreadCount) return;
                var increased = (c > prevUnreadCount) && prevUnreadCount >= 0;
                prevUnreadCount = c;
                unreadTotal = c;
                updateBadge();
                if (increased) {
                    loadThreads(function () { showFallbackToast(c); });
                } else {
                    loadThreads();
                }
            });
        }, 2200);
    }
    function stopUnreadPolling() {
        if (window.__fbUnreadInt) { clearInterval(window.__fbUnreadInt); window.__fbUnreadInt = null; }
    }

    function pollGlobal() {
        if (currentThreadId > 0) return;
        if (globalPollXhr) return;

        globalPollXhr = new XMLHttpRequest();
        globalPollXhr.open('GET', 'src/app/Http/Api/chat_poll.php?mode=global&last_id=' + lastGlobalId + '&_t=' + Date.now(), true);
        globalPollXhr.timeout = 25000;
        globalPollXhr.onload = function () {
            globalPollXhr = null;
            try {
                var res = JSON.parse(this.responseText);
                if (res.success && res.data.mode === 'global' && res.data.messages && res.data.messages.length > 0) {
                    lastGlobalId = res.data.last_id;
                    unreadTotal  = typeof res.data.unread_count !== 'undefined' ? res.data.unread_count : unreadTotal;
                    updateBadge();
                    if (initialFetchDone) showToast(res.data.messages[res.data.messages.length - 1]);
                    loadThreads();
                }
            } catch(e) {}
            globalReconnect = setTimeout(pollGlobal, 100);
        };
        globalPollXhr.onerror = function () {
            globalPollXhr = null;
            globalReconnect = setTimeout(pollGlobal, 5000);
        };
        globalPollXhr.ontimeout = function () {
            globalPollXhr = null;
            globalReconnect = setTimeout(pollGlobal, 100);
        };
        globalPollXhr.send();
    }

    function startThreadPolling() { stopThreadPolling(); pollThread(); }
    function stopThreadPolling() {
        if (threadPollXhr)  { try { threadPollXhr.abort(); } catch(e){} threadPollXhr = null; }
        if (threadReconnect){ clearTimeout(threadReconnect); threadReconnect = null; }
    }

    function pollThread() {
        if (!currentThreadId) return;
        if (threadPollXhr) return;

        threadPollXhr = new XMLHttpRequest();
        threadPollXhr.open('GET', 'src/app/Http/Api/chat_poll.php?mode=thread&thread_id=' + currentThreadId + '&last_id=' + lastThreadId + '&_t=' + Date.now(), true);
        threadPollXhr.timeout = 25000;
        threadPollXhr.onload = function () {
            threadPollXhr = null;
            try {
                var res = JSON.parse(this.responseText);
                if (res.success && res.data.mode === 'thread' && res.data.messages && res.data.messages.length > 0) {
                    var incoming = [];
                    res.data.messages.forEach(function (m) {
                        var mid = parseInt(m.id, 10);
                        if (mid > lastThreadId) {
                            if ((m.sender_hash || '0') !== userHash) incoming.push(m);
                            lastThreadId = Math.max(lastThreadId, mid);
                        }
                    });
                    lastGlobalId = Math.max(lastGlobalId, res.data.last_id);
                    if (incoming.length > 0) { renderMsgs(incoming, true); markRead(currentThreadId); }
                }
            } catch(e) {}
            threadReconnect = setTimeout(pollThread, 100);
        };
        threadPollXhr.onerror = function () {
            threadPollXhr = null;
            threadReconnect = setTimeout(pollThread, 5000);
        };
        threadPollXhr.ontimeout = function () {
            threadPollXhr = null;
            threadReconnect = setTimeout(pollThread, 100);
        };
        threadPollXhr.send();
    }

    /* ════════════════════════════════════
       BADGE
    ════════════════════════════════════ */
    function updateBadge() {
        var show  = unreadTotal > 0;
        var label = unreadTotal > 99 ? '99+' : String(unreadTotal);
        if (badge) {
            badge.textContent = label;
            badge.classList.toggle('is-hidden', !show);
            if (show) { badge.classList.remove('is-bump'); void badge.offsetWidth; badge.classList.add('is-bump'); }
        }
        if (sidebarBadge) {
            sidebarBadge.textContent = label;
            if (show) { sidebarBadge.classList.remove('is-hidden'); sidebarBadge.classList.add('pulse'); }
            else      { sidebarBadge.classList.add('is-hidden');    sidebarBadge.classList.remove('pulse'); }
        }
        var base = document.title.replace(/^\(\d+\+?\) /, '');
        document.title = show ? '(' + label + ') ' + base : base;
    }

    /* ════════════════════════════════════
       TOAST — replace immediately, no queue
    ════════════════════════════════════ */
    function showToast(msgObj) {
        var rootId  = msgObj.parent_id ? parseInt(msgObj.parent_id, 10) : parseInt(msgObj.id, 10);
        var sender  = msgObj.sender_username || (role === 'admin' ? 'Người dùng' : 'Admin');
        var preview = trunc(msgObj.message_content || '', 55);
        var url     = role === 'admin'
            ? '?template=admin&action=inbox&thread_id=' + rootId
            : '?template=user&action=inbox&thread_id='  + rootId;
        renderToast(sender, preview, url, rootId);
    }

    function showFallbackToast(count) {
        var unreadThread = null;
        for (var i = 0; i < threadList.length; i++) {
            if (parseInt(threadList[i].unread_count || 0, 10) > 0) { unreadThread = threadList[i]; break; }
        }
        var sender = role === 'admin' ? 'Người dùng' : 'Admin';
        var preview = count > 1 ? 'Bạn có ' + count + ' tin nhắn chưa đọc' : 'Bạn có 1 tin nhắn mới';
        var rootId = unreadThread ? unreadThread.id : 0;
        var url = role === 'admin'
            ? '?template=admin&action=inbox' + (rootId ? '&thread_id=' + rootId : '')
            : '?template=user&action=inbox'  + (rootId ? '&thread_id=' + rootId : '');
        renderToast(sender, preview, url, rootId);
    }

    function hideToast() {
        clearToast(false);
    }

    function renderToast(sender, preview, url, rootId) {
        clearToast(true);

        var MS = 7000;
        toastEl = document.createElement('div');
        toastEl.className = 'fb-toast';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML =
            '<div class="fb-toast-icon"><span class="material-symbols-outlined" style="font-size:20px;color:#fff;font-variation-settings:\'FILL\' 1">chat</span></div>' +
            '<div class="fb-toast-body">' +
              '<div class="fb-toast-label">Tin nhắn mới từ <strong>' + escHtml(sender) + '</strong></div>' +
              '<div class="fb-toast-text">'  + escHtml(preview) + '</div>' +
              '<div class="fb-toast-actions">' +
                '<a href="' + escAttr(url) + '" class="fb-toast-btn-primary">Xem ngay</a>' +
                '<button class="fb-toast-btn-close" type="button">Bỏ qua</button>' +
              '</div>' +
            '</div>' +
            '<div class="fb-toast-progress"><div class="fb-toast-progress-bar" style="animation-duration:' + MS + 'ms"></div></div>';
        document.body.appendChild(toastEl);

        toastEl.addEventListener('click', function (e) {
            if (e.target.closest('.fb-toast-btn-close')) { clearToast(false); return; }
            if (e.target.closest('.fb-toast-btn-primary')) return;
            clearToast(true);
            if (isFullPage && rootId) openThread(rootId);
            else window.location.href = url;
        });

        toastTimer = setTimeout(function () { clearToast(false); }, MS);
    }

    function clearToast(immediate) {
        if (toastTimer) { clearTimeout(toastTimer); toastTimer = null; }
        if (toastEl) {
            if (immediate) {
                if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
            } else {
                toastEl.classList.add('removing');
                var el = toastEl;
                setTimeout(function () { if (el && el.parentNode) el.remove(); }, 320);
            }
            toastEl = null;
        }
        // Clean up any stray .removing toasts from previous cycles
        var stray = document.querySelectorAll('.fb-toast.removing');
        for (var i = 0; i < stray.length; i++) {
            if (stray[i].parentNode) stray[i].parentNode.removeChild(stray[i]);
        }
    }

    function showErr(msg) {
        clearToast(true);
        toastEl = document.createElement('div');
        toastEl.className = 'fb-toast';
        toastEl.style.borderLeftColor = 'var(--fb-danger)';
        toastEl.innerHTML =
            '<div style="display:flex;align-items:center;gap:8px;color:#dc2626;font-weight:600;">' +
            '<span class="material-symbols-outlined" style="font-size:18px;font-variation-settings:\'FILL\' 1">error</span>' +
            escHtml(msg) + '</div>';
        document.body.appendChild(toastEl);
        toastTimer = setTimeout(function () { clearToast(false); }, 4000);
    }

    /* ════════════════════════════════════
       XHR HELPERS
    ════════════════════════════════════ */
    function ajaxGet(url, cb) {
        var x = new XMLHttpRequest();
        x.open('GET', url + (url.indexOf('?') !== -1 ? '&' : '?') + '_t=' + Date.now(), true);
        x.onload = function () {
            if (x.status === 200) { try { cb(JSON.parse(x.responseText)); } catch(e) { cb(null); } }
            else cb(null);
        };
        x.onerror = function () { cb(null); };
        x.send();
    }
    function ajaxPost(url, data, cb) {
        var x = new XMLHttpRequest();
        x.open('POST', url, true);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.onload = function () {
            if (x.status === 200) { try { cb(JSON.parse(x.responseText)); } catch(e) { cb(null); } }
            else cb(null);
        };
        x.onerror = function () { cb(null); };
        x.send(data);
    }

    /* ════════════════════════════════════
       UTILITIES
    ════════════════════════════════════ */
    function fmtTime(s) {
        if (!s) return '';
        var d = new Date(s.replace(' ', 'T'));
        if (isNaN(d)) return '';
        var diff = (Date.now() - d) / 1000;
        if (diff < 55)     return 'Vừa xong';
        if (diff < 3600)   return Math.floor(diff / 60) + ' phút';
        if (diff < 86400)  return zp(d.getHours()) + ':' + zp(d.getMinutes());
        if (diff < 172800) return 'Hôm qua ' + zp(d.getHours()) + ':' + zp(d.getMinutes());
        return zp(d.getDate()) + '/' + zp(d.getMonth() + 1);
    }
    function fmtDate(s) {
        if (!s) return '';
        var d = new Date(s.replace(' ', 'T'));
        if (isNaN(d)) return '';
        var now = new Date();
        if (d.toDateString() === now.toDateString()) return 'Hôm nay';
        var y = new Date(now); y.setDate(y.getDate() - 1);
        if (d.toDateString() === y.toDateString()) return 'Hôm qua';
        return zp(d.getDate()) + '/' + zp(d.getMonth() + 1) + '/' + d.getFullYear();
    }
    function zp(n) { return n < 10 ? '0' + n : '' + n; }
    function trunc(s, max) { return s && s.length > max ? s.slice(0, max) + '...' : (s || ''); }
    function escHtml(s) {
        if (s === null || s === undefined) return '';
        var d = document.createElement('div'); d.textContent = String(s); return d.innerHTML;
    }
    function escAttr(s) { return escHtml(s).replace(/"/g, '&quot;'); }
    function autoResize(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 90) + 'px';
    }
    function getUrlParam(name) {
        var m = window.location.search.match(new RegExp('[?&]' + name + '=([^&]+)'));
        return m ? decodeURIComponent(m[1]) : null;
    }
    /* IE polyfill */
    if (!Array.prototype.findIndex) {
        Array.prototype.findIndex = function (fn) {
            for (var i = 0; i < this.length; i++) if (fn(this[i], i, this)) return i;
            return -1;
        };
    }
    if (!Array.prototype.find) {
        Array.prototype.find = function (fn) {
            for (var i = 0; i < this.length; i++) if (fn(this[i], i, this)) return this[i];
            return undefined;
        };
    }

    /* ════════════════════════════════════
       BOOT
    ════════════════════════════════════ */
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();

    document.addEventListener('page-loaded', function () {
        window.__fbInitialized = false;
        eventsBound = false;
        init();
    });

})();
