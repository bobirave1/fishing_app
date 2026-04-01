// Messages Page JavaScript
let currentConversationUserId = null;
let currentConversationUsername = null;
let searchTimeout = null;
let conversationRefreshInterval = null;

// ==================== FRIEND SEARCH ====================
function initFriendSearch() {
    const input = document.getElementById('friendSearchInput');
    const results = document.getElementById('friendSearchResults');
    if (!input || !results) return;

    input.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(searchTimeout);
        if (q.length < 1) {
            results.classList.add('d-none');
            results.innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(function () {
            fetchFriends(q);
        }, 300);
    });

    // Hide dropdown on outside click
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !results.contains(e.target)) {
            results.classList.add('d-none');
        }
    });
}

function fetchFriends(query) {
    const results = document.getElementById('friendSearchResults');
    fetch(resolvePath('be/messages/message.php') + '?action=search_friends&q=' + encodeURIComponent(query))
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) return;
            if (data.friends.length === 0) {
                const isBg = (document.documentElement.lang || '').startsWith('bg');
                results.innerHTML = '<div class="friend-search-empty">' +
                    '<i class="fas fa-user-slash me-1"></i> ' +
                    (isBg ? 'Няма намерени приятели' : 'No friends found') +
                    '</div>';
                results.classList.remove('d-none');
                return;
            }
            let html = '';
            data.friends.forEach(function (f) {
                const avatar = getAvatarUrl(f.avatar_url);
                const displayName = f.full_name || f.username;
                html += '<div class="friend-search-item" data-user-id="' + f.id + '" ' +
                    'data-username="' + escapeHtml(f.username) + '" ' +
                    'data-avatar="' + escapeHtml(avatar) + '">' +
                    '<img src="' + avatar + '" alt="">' +
                    '<div class="friend-info">' +
                    '<div class="friend-username">' + escapeHtml(f.username) + '</div>' +
                    (f.full_name ? '<div class="friend-fullname">' + escapeHtml(f.full_name) + '</div>' : '') +
                    '</div>' +
                    '<i class="fas fa-comment friend-msg-icon"></i>' +
                    '</div>';
            });
            results.innerHTML = html;
            results.classList.remove('d-none');

            // Attach click handlers
            results.querySelectorAll('.friend-search-item').forEach(function (item) {
                item.addEventListener('click', function () {
                    const uid = parseInt(this.dataset.userId);
                    const uname = this.dataset.username;
                    const uavatar = this.dataset.avatar;
                    openConversation(uid, uname, uavatar);
                    document.getElementById('friendSearchInput').value = '';
                    results.classList.add('d-none');
                    results.innerHTML = '';
                });
            });
        })
        .catch(function (err) {
            debugLog('error', 'Friend search failed', { error: getErrorMessage(err, 'Network error') });
        });
}

// ==================== CONVERSATIONS ====================
function openConversation(userId, username, avatarUrl) {
    currentConversationUserId = userId;

    // Mark active in list
    document.querySelectorAll('.conversation-item').forEach(function (el) {
        el.classList.remove('active');
    });
    const clickedItem = document.querySelector('.conversation-item[data-user-id="' + userId + '"]');
    if (clickedItem) {
        clickedItem.classList.add('active');
        // Extract username/avatar from list item if not provided
        if (!username) {
            const nameEl = clickedItem.querySelector('h6');
            username = nameEl ? nameEl.textContent : '';
        }
        if (!avatarUrl) {
            const imgEl = clickedItem.querySelector('img');
            avatarUrl = imgEl ? imgEl.src : '';
        }
    }

    currentConversationUsername = username || '';

    // Update conversation header
    const headerName = document.getElementById('convHeaderName');
    const headerAvatar = document.getElementById('convHeaderAvatar');
    if (headerName) headerName.textContent = username || '';
    if (headerAvatar) headerAvatar.src = avatarUrl || getAvatarUrl(null);

    // Show conversation view
    document.getElementById('conversationView').classList.remove('d-none');
    document.getElementById('noConversationView').classList.add('d-none');

    // Load messages
    loadMessages(userId);

    // Focus the input
    const msgInput = document.getElementById('messageInput');
    if (msgInput) setTimeout(function () { msgInput.focus(); }, 100);

    // Start auto-refresh for this conversation
    clearInterval(conversationRefreshInterval);
    conversationRefreshInterval = setInterval(function () {
        if (currentConversationUserId) {
            loadMessages(currentConversationUserId, true);
        }
    }, 5000);
}

function loadMessages(userId, silent) {
    fetch(resolvePath('be/messages/message.php') + '?action=get_conversation&receiver_id=' + userId)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                const scrollArea = document.querySelector('.messages-scroll-area');
                let wasAtBottom = false;
                if (scrollArea) {
                    wasAtBottom = (scrollArea.scrollHeight - scrollArea.scrollTop - scrollArea.clientHeight) < 60;
                }
                displayConversationView(data.messages, data.current_user_id, userId);
                // Auto-scroll: always scroll on first load, only on bottom for silent refresh
                if (scrollArea && (!silent || wasAtBottom)) {
                    scrollArea.scrollTop = scrollArea.scrollHeight;
                }
            }
        });
}

function displayConversationView(messages, currentUserId, otherId) {
    const container = document.getElementById('conversationThread');
    let html = '';
    const isBg = (document.documentElement.lang || '').startsWith('bg');

    messages.forEach(function (msg) {
        const isOwn = msg.sender_id == currentUserId;
        const groupClass = isOwn ? 'own' : 'other';

        let attachmentsHtml = '';
        if (msg.attachment_urls) {
            try {
                const attachments = JSON.parse(msg.attachment_urls);
                attachments.forEach(function (url) {
                    const safeUrl = escapeHtml(url);
                    const fileExt = url.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(fileExt) !== -1) {
                        attachmentsHtml += '<img src="../' + safeUrl + '" class="img-fluid rounded mb-2" style="max-width: 200px; max-height: 200px; cursor:pointer;" onclick="window.open(this.src)">';
                    } else if (['mp4', 'avi', 'mov'].indexOf(fileExt) !== -1) {
                        attachmentsHtml += '<video controls class="mb-2" style="max-width: 200px; max-height: 200px;"><source src="../' + safeUrl + '" type="video/' + fileExt + '"></video>';
                    }
                });
            } catch (e) {}
        }

        html += '<div class="message-group ' + groupClass + '">' +
            '<div class="message-bubble" style="max-width: 70%;">' +
            attachmentsHtml +
            (msg.content ? '<p class="mb-0">' + escapeHtml(msg.content) + '</p>' : '') +
            '<small class="message-time">' + formatDate(msg.created_at) + '</small>' +
            '</div></div>';
    });

    container.innerHTML = html || '<div class="text-center text-muted py-5"><i class="fas fa-envelope-open fa-3x mb-3" style="opacity:0.3;"></i><p>' + (isBg ? 'Все още няма съобщения' : 'No messages yet') + '</p></div>';
}

function sendMessageToCurrentUser() {
    if (!currentConversationUserId) return;
    sendMessage(currentConversationUserId);
}

// ==================== SMART CONVERSATIONS LIST ====================
function loadConversationsSmart() {
    const activeUserId = currentConversationUserId;
    fetch(resolvePath('be/messages/message.php') + '?action=get_conversations')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                displayConversationsSmart(data.conversations, activeUserId);
            }
        })
        .catch(function (err) {
            debugLog('error', 'Loading conversations failed', { error: getErrorMessage(err, 'Network error') });
        });
}

function displayConversationsSmart(conversations, activeUserId) {
    const container = document.getElementById('conversationsList');
    if (!container) return;
    const isBg = (document.documentElement.lang || '').startsWith('bg');

    if (!conversations || conversations.length === 0) {
        container.innerHTML = '<div class="text-center text-muted p-4">' +
            '<i class="fas fa-inbox fa-2x mb-2" style="opacity:0.4;"></i>' +
            '<p class="mb-0">' + (isBg ? 'Все още няма разговори' : 'No conversations yet') + '</p>' +
            '<small>' + (isBg ? 'Потърси приятел по-горе' : 'Search for a friend above') + '</small>' +
            '</div>';
        return;
    }

    let html = '';
    conversations.forEach(function (conv) {
        const avatar = getAvatarUrl(conv.avatar_url);
        const unreadBadge = conv.unread_count > 0 ? '<span class="badge bg-primary rounded-pill">' + conv.unread_count + '</span>' : '';
        const unreadClass = conv.unread_count > 0 ? 'fw-bold' : '';
        const activeClass = (activeUserId && conv.other_user_id == activeUserId) ? ' active' : '';
        const lastMsg = conv.last_message ?
            (conv.last_message.length > 40 ? conv.last_message.substring(0, 40) + '...' : conv.last_message) :
            (isBg ? 'Няма съобщения' : 'No messages');

        html += '<div class="conversation-item' + activeClass + '" data-user-id="' + conv.other_user_id + '" ' +
            'data-username="' + escapeHtml(conv.username) + '" data-avatar="' + escapeHtml(avatar) + '">' +
            '<img src="' + avatar + '" class="conversation-avatar" alt="">' +
            '<div class="conversation-info">' +
            '<h6 class="conversation-name ' + unreadClass + '">' + escapeHtml(conv.username) + '</h6>' +
            '<p class="conversation-preview mb-0">' + escapeHtml(lastMsg) + '</p>' +
            '</div>' +
            unreadBadge +
            '</div>';
    });

    container.innerHTML = html;

    // Attach click handlers
    container.querySelectorAll('.conversation-item').forEach(function (item) {
        item.addEventListener('click', function () {
            const uid = parseInt(this.dataset.userId);
            const uname = this.dataset.username;
            const uavatar = this.dataset.avatar;
            openConversation(uid, uname, uavatar);
        });
    });
}

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function () {
    // Init friend search
    initFriendSearch();

    // Load conversations
    loadConversationsSmart();
    setInterval(loadConversationsSmart, 10000);

    // Enter key to send
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessageToCurrentUser();
            }
        });
    }

    // File input
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelection);
    }

    // Back button for mobile
    const backBtn = document.getElementById('backToListBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function () {
            document.getElementById('conversationView').classList.add('d-none');
            document.getElementById('noConversationView').classList.remove('d-none');
            currentConversationUserId = null;
            clearInterval(conversationRefreshInterval);
        });
    }
});

function handleFileSelection(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('filePreview');
    if (!previewContainer) return;

    previewContainer.innerHTML = '';

    for (const i = 0; i < files.length; i++) {
        (function (index) {
            const file = files[index];
            const fileItem = document.createElement('div');
            fileItem.className = 'file-preview-item d-inline-block me-2 mb-2';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'img-thumbnail';
                img.style.maxWidth = '100px';
                img.style.maxHeight = '100px';
                fileItem.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(file);
                video.className = 'img-thumbnail';
                video.style.maxWidth = '100px';
                video.style.maxHeight = '100px';
                video.controls = false;
                fileItem.appendChild(video);
            }

            const removeBtn = document.createElement('button');
            removeBtn.className = 'btn btn-sm btn-outline-danger ms-1';
            removeBtn.innerHTML = '&times;';
            removeBtn.onclick = function () {
                const dt = new DataTransfer();
                const currentFiles = document.getElementById('fileInput').files;
                for (const j = 0; j < currentFiles.length; j++) {
                    if (j !== index) dt.items.add(currentFiles[j]);
                }
                document.getElementById('fileInput').files = dt.files;
                handleFileSelection({ target: document.getElementById('fileInput') });
            };

            fileItem.appendChild(removeBtn);
            previewContainer.appendChild(fileItem);
        })(i);
    }
}
