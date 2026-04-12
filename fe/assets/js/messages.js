// ============================================================
//  MESSAGES PAGE - full standalone controller
// ============================================================

let currentConversationUserId = null;
let currentConversationName    = '';
let refreshTimer               = null;
let msgCurrentTab              = 'conversations';

// ── Helpers ─────────────────────────────────────────────────
function getMsgPath() {
    return '../../be/messages/message.php';
}

function switchMsgTab(tab) {
    msgCurrentTab = tab;
    const convPanel  = document.getElementById('panelConversations');
    const frndPanel  = document.getElementById('panelFriends');
    const convBtn    = document.getElementById('tabConvBtn');
    const frndBtn    = document.getElementById('tabFriendsBtn');

    if (tab === 'conversations') {
        convPanel.classList.remove('d-none');
        frndPanel.classList.add('d-none');
        convBtn.classList.add('active');
        frndBtn.classList.remove('active');
    } else {
        convPanel.classList.add('d-none');
        frndPanel.classList.remove('d-none');
        convBtn.classList.remove('active');
        frndBtn.classList.add('active');
    }
    filterMsgPanel(document.getElementById('msgSearch').value);
}

function filterMsgPanel(query) {
    const q = query.toLowerCase().trim();
    const items = document.querySelectorAll(
        msgCurrentTab === 'conversations'
            ? '#panelConversations .conversation-item'
            : '#panelFriends .friend-item'
    );
    items.forEach(el => {
        const name = el.dataset.name || el.textContent.toLowerCase();
        el.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
}

// ── Conversations ────────────────────────────────────────────
function loadConversations() {
    fetch(getMsgPath() + '?action=get_conversations')
        .then(r => r.json())
        .then(data => {
            if (data.success) displayConversations(data.conversations);
        })
        .catch(() => {});
}

function displayConversations(conversations) {
    const container = document.getElementById('conversationsList');
    if (!container) return;

    let totalUnread = 0;

    if (!conversations || conversations.length === 0) {
        container.innerHTML = '<p class="text-center text-muted p-3 small">Няма разговори</p>';
        updateUnreadBadge(0);
        return;
    }

    let html = '';
    conversations.forEach(conv => {
        const avatar  = getAvatarUrl(conv.avatar_url);
        const isOwn   = conv.other_user_id == currentConversationUserId;
        totalUnread  += parseInt(conv.unread_count || 0);

        html += `
        <div class="conversation-item${isOwn ? ' active' : ''}"
             data-user-id="${conv.other_user_id}"
             data-name="${escapeAttr(conv.username)}"
             onclick="openConversation(${conv.other_user_id}, '${escapeJs(conv.username)}', '${escapeJs(avatar)}')">
            <div style="position:relative; flex-shrink:0;">
                <img src="${avatar}" class="conversation-avatar" alt="" onerror="handleAvatarError(this)">
                ${conv.unread_count > 0 ? `<span class="msg-unread-badge">${conv.unread_count}</span>` : ''}
            </div>
            <div class="conversation-info">
                <div class="conversation-name${conv.unread_count > 0 ? ' fw-bold' : ''}">${escapeHtml(conv.username)}</div>
                <div class="conversation-preview">${conv.last_message ? escapeHtml(conv.last_message.substring(0, 50)) : '&mdash;'}</div>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    updateUnreadBadge(totalUnread);
    filterMsgPanel(document.getElementById('msgSearch').value);
}

function updateUnreadBadge(count) {
    const dot = document.getElementById('unreadDot');
    if (dot) {
        dot.style.display = count > 0 ? 'inline-block' : 'none';
        dot.textContent   = count > 9 ? '9+' : (count || '');
    }
    // Also sync the navbar message badge
    const navBadge = document.getElementById('messageBadge');
    if (navBadge) {
        if (count > 0) {
            navBadge.textContent = count > 99 ? '99+' : count;
            navBadge.classList.remove('d-none');
        } else {
            navBadge.classList.add('d-none');
        }
    }
}

// ── Open / Close Conversation ─────────────────────────────────
function openConversation(userId, name, avatarSrc) {
    currentConversationUserId = userId;
    currentConversationName   = name || '';

    // Switch to conversations tab if in friends tab
    if (msgCurrentTab === 'friends') switchMsgTab('conversations');

    // Update active state in list
    document.querySelectorAll('.conversation-item').forEach(el => {
        el.classList.toggle('active', el.dataset.userId == userId);
    });

    // Update chat header
    if (name)      document.getElementById('chatName').textContent      = name;
    if (avatarSrc) document.getElementById('chatAvatar').src            = avatarSrc;
    const profileLink = document.getElementById('chatProfileLink');
    if (profileLink) profileLink.href = `../../be/users/profile.php?id=${userId}`;

    document.getElementById('conversationView').classList.remove('d-none');
    document.getElementById('noConversationView').classList.add('d-none');

    fetchConversation(userId);

    // Restart auto-refresh
    clearInterval(refreshTimer);
    refreshTimer = setInterval(() => fetchConversation(userId, true), 5000);
}

function closeConversation() {
    clearInterval(refreshTimer);
    currentConversationUserId = null;
    document.getElementById('conversationView').classList.add('d-none');
    document.getElementById('noConversationView').classList.remove('d-none');
}

function fetchConversation(userId, silent = false) {
    const area = document.getElementById('messagesScrollArea');
    if (!silent) {
        area.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary-color);"></i></div>';
    }

    const savedScroll = silent ? area.scrollTop : null;
    const atBottom    = silent ? (area.scrollHeight - area.scrollTop - area.clientHeight < 60) : true;

    fetch(getMsgPath() + '?action=get_conversation&receiver_id=' + userId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderMessages(data.messages, data.current_user_id);
                // Restore position or scroll to bottom
                if (atBottom) {
                    area.scrollTop = area.scrollHeight;
                } else if (savedScroll !== null) {
                    area.scrollTop = savedScroll;
                }
                // Update header avatar/name from conversation list if not set yet
                if (!document.getElementById('chatName').textContent) {
                    const convEl = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
                    if (convEl) {
                        document.getElementById('chatName').textContent = convEl.dataset.name || '';
                        const img = convEl.querySelector('img');
                        if (img) document.getElementById('chatAvatar').src = img.src;
                    }
                }
                loadConversations(); // refresh unread counts
            }
        })
        .catch(() => {});
}

function renderMessages(messages, currentUserId) {
    const area = document.getElementById('messagesScrollArea');
    if (!messages || messages.length === 0) {
        area.innerHTML = '<p class="text-center text-muted small p-4">Все още няма съобщения. Напиши първото!</p>';
        return;
    }

    let html = '';
    messages.forEach(msg => {
        const isOwn    = msg.sender_id == currentUserId;
        const cls      = isOwn ? 'own' : 'other';
        const avatar   = getAvatarUrl(msg.avatar_url);
        const timeStr  = formatDate(msg.created_at);

        let attachHtml = '';
        if (msg.attachment_urls) {
            try {
                const atts = JSON.parse(msg.attachment_urls);
                atts.forEach(url => {
                    const ext = url.split('.').pop().toLowerCase();
                    const src = `../assets/uploads/messages/${url.split('/').pop()}`;
                    if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                        attachHtml += `<img src="${src}" class="img-fluid rounded mb-1 msg-attachment-img" onclick="window.open(this.src)" title="Open image" style="max-width:200px;max-height:200px;cursor:zoom-in;">`;
                    } else if (['mp4','avi','mov'].includes(ext)) {
                        attachHtml += `<video controls class="mb-1 rounded" style="max-width:200px;max-height:200px;"><source src="${src}"></video>`;
                    }
                });
            } catch(e) {}
        }

        html += `
        <div class="message-group ${cls}">
            ${!isOwn ? `<img src="${avatar}" class="message-avatar" onerror="handleAvatarError(this)">` : ''}
            <div class="message-bubble">
                ${attachHtml}
                ${msg.content ? `<p class="mb-0">${escapeHtml(msg.content)}</p>` : ''}
                <div class="message-time">${timeStr}</div>
            </div>
            ${isOwn ? `<img src="${avatar}" class="message-avatar" onerror="handleAvatarError(this)">` : ''}
        </div>`;
    });

    area.innerHTML = html;
}

// ── Send Message ─────────────────────────────────────────────
function sendMessageToCurrentUser() {
    if (!currentConversationUserId) return;
    sendMessage(currentConversationUserId);
}

function sendMessage(receiverId) {
    const input     = document.getElementById('messageInput');
    const fileInput = document.getElementById('fileInput');
    const content   = input.value.trim();
    const files     = fileInput ? fileInput.files : [];

    if (!content && files.length === 0) return;

    const sendBtn = document.querySelector('.message-send-btn');
    if (sendBtn) sendBtn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', receiverId);
    formData.append('content', content);
    formData.append('csrf_token', document.body.dataset.csrfToken || '');
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }

    fetch(getMsgPath(), { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                input.style.height = '';
                if (fileInput) { fileInput.value = ''; }
                document.getElementById('filePreview').innerHTML = '';
                fetchConversation(receiverId);
            }
        })
        .catch(() => {})
        .finally(() => { if (sendBtn) sendBtn.disabled = false; });
}

// ── File Preview ─────────────────────────────────────────────
function handleFileSelection(event) {
    const files    = event.target.files;
    const preview  = document.getElementById('filePreview');
    if (!preview) return;

    preview.innerHTML = '';
    Array.from(files).forEach((file, i) => {
        const wrap = document.createElement('div');
        wrap.className = 'file-preview-item';

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src   = URL.createObjectURL(file);
            img.style.cssText = 'width:60px;height:60px;object-fit:cover;border-radius:8px;';
            wrap.appendChild(img);
        } else {
            wrap.innerHTML = `<span class="small text-muted">${escapeHtml(file.name)}</span>`;
        }

        const rm = document.createElement('button');
        rm.className = 'btn btn-sm btn-danger';
        rm.style.cssText = 'position:absolute;top:-6px;right:-6px;width:18px;height:18px;padding:0;font-size:10px;line-height:1;border-radius:50%;';
        rm.textContent = '×';
        rm.onclick = () => { wrap.remove(); };
        wrap.style.position = 'relative';
        wrap.appendChild(rm);
        preview.appendChild(wrap);
    });
}

// ── Auto-grow textarea ────────────────────────────────────────
function autoGrow(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

// ── Escape helpers ────────────────────────────────────────────
function escapeAttr(str) {
    return String(str || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
function escapeJs(str) {
    return String(str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Load conversations
    loadConversations();
    setInterval(loadConversations, 15000);

    // Enter to send
    const input = document.getElementById('messageInput');
    if (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessageToCurrentUser();
            }
            autoGrow(this);
        });
        input.addEventListener('input', function() { autoGrow(this); });
    }

    // File input
    const fi = document.getElementById('fileInput');
    if (fi) fi.addEventListener('change', handleFileSelection);
});

