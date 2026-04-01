/**
 * FISHINGLORY — Real-time chat enhancements.
 * Long-polling with typing indicators, online presence, and read receipts.
 * Works alongside the existing messages.js.
 */
(function () {
    'use strict';

    let pollTimer = null;
    let lastPollTimestamp = null;
    let heartbeatTimer = null;
    let typingTimer = null;
    let currentTypingTo = null;

    const POLL_INTERVAL = 3000;      // 3 seconds
    const HEARTBEAT_INTERVAL = 30000; // 30 seconds
    const TYPING_TIMEOUT = 3000;      // Stop typing indicator after 3s

    const isBg = (document.documentElement.lang || '').startsWith('bg');

    // ==================== POLLING ====================

    function startPolling() {
        stopPolling();
        doPoll();
        pollTimer = setInterval(doPoll, POLL_INTERVAL);
    }

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    function doPoll() {
        const userId = document.body.dataset.userId;
        if (!userId || userId === '0') return;

        let url = resolvePath('api/chat/poll') + '?_=' + Date.now();
        if (lastPollTimestamp) url += '&since=' + encodeURIComponent(lastPollTimestamp);
        if (typeof currentConversationUserId !== 'undefined' && currentConversationUserId) {
            url += '&partner_id=' + currentConversationUserId;
        }

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                lastPollTimestamp = data.timestamp;

                // New messages
                if (data.messages && data.messages.length > 0) {
                    handleNewMessages(data.messages);
                }

                // Typing indicators
                if (data.typing) {
                    updateTypingIndicators(data.typing);
                }

                // Online status
                if (data.online) {
                    updateOnlineStatus(data.online);
                }

                // Unread badges
                if (data.unread) {
                    updateUnreadBadges(data.unread);
                }
            })
            .catch(function () { /* silent fail */ });
    }

    // ==================== NEW MESSAGES ====================

    function handleNewMessages(messages) {
        const container = document.getElementById('conversationThread');
        if (!container) return;

        const scrollArea = container.closest('.messages-scroll-area') || container;
        const wasAtBottom = (scrollArea.scrollHeight - scrollArea.scrollTop - scrollArea.clientHeight) < 60;
        const currentUserId = parseInt(document.body.dataset.userId || '0');

        messages.forEach(function (msg) {
            // Check if message already displayed
            if (container.querySelector('[data-msg-id="' + msg.id + '"]')) return;

            const isOwn = parseInt(msg.sender_id) === currentUserId;
            const groupClass = isOwn ? 'own' : 'other';

            const div = document.createElement('div');
            div.className = 'message-group ' + groupClass;
            div.dataset.msgId = msg.id;
            div.innerHTML = '<div class="message-bubble" style="max-width:70%;">' +
                (msg.content ? '<p class="mb-0">' + escapeHtml(msg.content) + '</p>' : '') +
                '<small class="message-time">' + formatDate(msg.created_at) + '</small>' +
                (msg.seen_at ? '<span class="message-seen"><i class="fas fa-check-double"></i></span>' : '') +
                '</div>';
            container.appendChild(div);
        });

        // Play notification sound for other's messages
        const otherMsgs = messages.filter(function (m) { return parseInt(m.sender_id) !== currentUserId; });
        if (otherMsgs.length > 0) {
            playNotificationSound();
        }

        if (wasAtBottom) {
            scrollArea.scrollTop = scrollArea.scrollHeight;
        }
    }

    // ==================== TYPING INDICATORS ====================

    function updateTypingIndicators(typingUsers) {
        const indicator = document.getElementById('typingIndicator');
        if (!indicator) return;

        if (typingUsers.length > 0) {
            const names = typingUsers.map(function (u) { return u.username; }).join(', ');
            indicator.innerHTML = '<i class="fas fa-ellipsis-h typing-animation"></i> ' +
                '<span>' + escapeHtml(names) + (isBg ? ' пише...' : ' is typing...') + '</span>';
            indicator.classList.remove('d-none');
        } else {
            indicator.classList.add('d-none');
        }
    }

    function sendTypingIndicator(targetId) {
        if (currentTypingTo === targetId) return;
        currentTypingTo = targetId;

        const csrfToken = document.body.dataset.csrfToken || '';
        const formData = new FormData();
        formData.append('target_id', targetId);
        formData.append('csrf_token', csrfToken);

        fetch(resolvePath('api/chat/typing'), { method: 'POST', body: formData })
            .catch(function () {});

        clearTimeout(typingTimer);
        typingTimer = setTimeout(function () {
            currentTypingTo = null;
            // Clear typing
            const clearData = new FormData();
            clearData.append('target_id', '0');
            clearData.append('csrf_token', csrfToken);
            fetch(resolvePath('api/chat/typing'), { method: 'POST', body: clearData })
                .catch(function () {});
        }, TYPING_TIMEOUT);
    }

    // ==================== ONLINE STATUS ====================

    function updateOnlineStatus(statuses) {
        statuses.forEach(function (s) {
            // Update conversation list indicators
            var item = document.querySelector('.conversation-item[data-user-id="' + s.user_id + '"]');
            if (item) {
                var dot = item.querySelector('.online-dot');
                if (!dot) {
                    dot = document.createElement('span');
                    dot.className = 'online-dot';
                    var avatar = item.querySelector('.conversation-avatar');
                    if (avatar) avatar.parentNode.insertBefore(dot, avatar.nextSibling);
                }
                dot.className = 'online-dot status-' + s.status;
                dot.title = s.status === 'online' ? (isBg ? 'Онлайн' : 'Online') :
                           s.status === 'away' ? (isBg ? 'Отсъстващ' : 'Away') :
                           (isBg ? 'Офлайн' : 'Offline');
            }

            // Update header online status
            if (typeof currentConversationUserId !== 'undefined' && currentConversationUserId == s.user_id) {
                var headerStatus = document.getElementById('convHeaderStatus');
                if (headerStatus) {
                    headerStatus.className = 'conv-status status-' + s.status;
                    headerStatus.textContent = s.status === 'online' ? (isBg ? 'Онлайн' : 'Online') :
                                               s.status === 'away' ? (isBg ? 'Отсъстващ' : 'Away') : '';
                }
            }
        });
    }

    // ==================== UNREAD BADGES ====================

    function updateUnreadBadges(unreads) {
        var totalUnread = 0;
        unreads.forEach(function (u) {
            totalUnread += parseInt(u.unread_count);
            var item = document.querySelector('.conversation-item[data-user-id="' + u.sender_id + '"]');
            if (item) {
                var badge = item.querySelector('.badge');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge bg-primary rounded-pill';
                    item.appendChild(badge);
                }
                badge.textContent = u.unread_count;
            }
        });

        // Update navbar messages badge
        var navBadge = document.querySelector('.messages-badge');
        if (navBadge) {
            if (totalUnread > 0) {
                navBadge.textContent = totalUnread;
                navBadge.classList.remove('d-none');
            } else {
                navBadge.classList.add('d-none');
            }
        }
    }

    // ==================== HEARTBEAT ====================

    function startHeartbeat() {
        sendHeartbeat();
        heartbeatTimer = setInterval(sendHeartbeat, HEARTBEAT_INTERVAL);
    }

    function sendHeartbeat() {
        var csrfToken = document.body.dataset.csrfToken || '';
        var formData = new FormData();
        formData.append('csrf_token', csrfToken);
        if (typeof currentConversationUserId !== 'undefined' && currentConversationUserId) {
            formData.append('typing_to', currentConversationUserId);
        }
        fetch(resolvePath('api/chat/heartbeat'), { method: 'POST', body: formData })
            .catch(function () {});
    }

    // ==================== SOUND ====================

    function playNotificationSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 800;
            gain.gain.value = 0.1;
            osc.start();
            osc.stop(ctx.currentTime + 0.15);
        } catch (e) {}
    }

    // ==================== INIT ====================

    document.addEventListener('DOMContentLoaded', function () {
        var userId = document.body.dataset.userId;
        if (!userId || userId === '0') return;

        // Only activate on messages page
        if (!document.getElementById('conversationThread') && !document.getElementById('conversationsList')) return;

        startPolling();
        startHeartbeat();

        // Attach typing listener to message input
        var msgInput = document.getElementById('messageInput');
        if (msgInput) {
            msgInput.addEventListener('input', function () {
                if (typeof currentConversationUserId !== 'undefined' && currentConversationUserId) {
                    sendTypingIndicator(currentConversationUserId);
                }
            });
        }

        // Inject typing indicator element
        var chatArea = document.querySelector('.messages-scroll-area');
        if (chatArea && !document.getElementById('typingIndicator')) {
            var indicator = document.createElement('div');
            indicator.id = 'typingIndicator';
            indicator.className = 'typing-indicator d-none';
            chatArea.parentNode.insertBefore(indicator, chatArea.nextSibling);
        }

        // Inject online status element in header
        var headerName = document.getElementById('convHeaderName');
        if (headerName && !document.getElementById('convHeaderStatus')) {
            var statusEl = document.createElement('small');
            statusEl.id = 'convHeaderStatus';
            statusEl.className = 'conv-status';
            headerName.parentNode.insertBefore(statusEl, headerName.nextSibling);
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function () {
        stopPolling();
        clearInterval(heartbeatTimer);
    });
})();
