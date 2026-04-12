// Comprehensive JavaScript for all fishing app features

// Helper function to get CSRF token
function getCsrfToken() {
    return document.body.dataset.csrfToken || '';
}

// Helper to get correct API base path depending on current page location
function getApiPath(endpoint) {
    const path = window.location.pathname;
    if (path.includes('/fe/pages/') || path.includes('/be/')) {
        return '../../' + endpoint;
    }
    return endpoint;
}

const APP_DEBUG = window.location.search.includes('debug=1') || localStorage.getItem('fishingDebug') === '1';
const debugEvents = [];

function debugLog(level, message, meta) {
    const normalizedLevel = ['info', 'warn', 'error'].includes(level) ? level : 'info';
    const entry = {
        ts: new Date().toISOString(),
        level: normalizedLevel,
        message,
        meta: meta || null
    };

    debugEvents.unshift(entry);
    if (debugEvents.length > 30) {
        debugEvents.pop();
    }

    if (APP_DEBUG) {
        const logger = normalizedLevel === 'error' ? console.error : (normalizedLevel === 'warn' ? console.warn : console.info);
        logger('[FishingApp]', message, meta || '');
        renderDebugPanel();
    }
}

function getErrorMessage(err, fallback) {
    if (!err) return fallback;
    if (typeof err === 'string') return err;
    if (err.message) return err.message;
    return fallback;
}

function showAppNotice(message, type = 'danger', timeoutMs = 4200) {
    const rootId = 'appNoticeStack';
    let root = document.getElementById(rootId);

    if (!root) {
        root = document.createElement('div');
        root.id = rootId;
        root.className = 'app-notice-stack';
        document.body.appendChild(root);
    }

    const notice = document.createElement('div');
    const bsType = ['success', 'warning', 'info', 'danger'].includes(type) ? type : 'danger';
    notice.className = `alert alert-${bsType} alert-dismissible fade show shadow-sm app-notice`;
    notice.setAttribute('role', 'alert');
    notice.innerHTML = `
        <span>${message}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    root.appendChild(notice);

    window.setTimeout(() => {
        if (notice.parentElement) {
            notice.remove();
        }
    }, timeoutMs);
}

function renderDebugPanel() {
    if (!APP_DEBUG) return;
    let panel = document.getElementById('appDebugPanel');
    if (!panel) {
        panel = document.createElement('div');
        panel.id = 'appDebugPanel';
        panel.className = 'app-debug-panel';
        panel.innerHTML = `
            <div class="app-debug-header">
                <strong>Debug</strong>
                <div class="app-debug-actions">
                    <button type="button" class="btn btn-sm btn-outline-light" id="appDebugClear">Clear</button>
                    <button type="button" class="btn btn-sm btn-outline-light" id="appDebugClose">Hide</button>
                </div>
            </div>
            <div class="app-debug-body" id="appDebugBody"></div>
        `;
        document.body.appendChild(panel);

        panel.querySelector('#appDebugClear').addEventListener('click', () => {
            debugEvents.length = 0;
            renderDebugPanel();
        });

        panel.querySelector('#appDebugClose').addEventListener('click', () => {
            panel.classList.add('d-none');
        });
    }

    const body = document.getElementById('appDebugBody');
    if (!body) return;

    if (debugEvents.length === 0) {
        body.innerHTML = '<div class="app-debug-item app-debug-empty">No events yet</div>';
        return;
    }

    body.innerHTML = debugEvents
        .map((item) => {
            const time = new Date(item.ts).toLocaleTimeString();
            const meta = item.meta ? `<div class="app-debug-meta">${escapeHtml(JSON.stringify(item.meta))}</div>` : '';
            return `<div class="app-debug-item app-debug-${item.level}"><span>[${time}]</span> ${escapeHtml(item.message)}${meta}</div>`;
        })
        .join('');
}

window.addEventListener('error', (event) => {
    debugLog('error', 'Unhandled JS error', {
        message: event.message,
        source: event.filename,
        line: event.lineno
    });
});

window.addEventListener('unhandledrejection', (event) => {
    debugLog('error', 'Unhandled promise rejection', {
        reason: getErrorMessage(event.reason, 'Unknown promise rejection')
    });
});

// ==================== LIKES ====================
const likingInProgress = new Set();

function toggleLike(postId, button) {
    // Prevent multiple simultaneous requests
    if (likingInProgress.has(postId)) {
        return;
    }
    
    const isLiked = button.classList.contains('liked');
    const action = isLiked ? 'unlike' : 'like';
    
    likingInProgress.add(postId);
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('action', action);
    formData.append('csrf_token', getCsrfToken());
    
    // Optimistic UI update
    const originalHTML = button.innerHTML;
    const originalClass = button.className;
    
    fetch(getApiPath('be/posts/like.php'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state based on server response
            if (data.liked) {
                button.classList.add('liked');
                button.innerHTML = '<i class="fas fa-heart"></i> <span id="like-count-' + postId + '">' + data.like_count + '</span>';
                // Bounce animation on like
                button.classList.add('like-bounce');
                setTimeout(function() { button.classList.remove('like-bounce'); }, 450);
            } else {
                button.classList.remove('liked');
                button.innerHTML = '<i class="far fa-heart"></i> <span id="like-count-' + postId + '">' + data.like_count + '</span>';
            }
        } else if (data.error) {
            // Revert on error
            button.innerHTML = originalHTML;
            button.className = originalClass;
            debugLog('warn', 'Like action failed', { postId, error: data.error });
            showAppNotice(data.error || 'Could not update like now.');
        }
    })
    .catch(error => {
        // Revert on error
        button.innerHTML = originalHTML;
        button.className = originalClass;
        debugLog('error', 'Like request failed', { postId, error: getErrorMessage(error, 'Network error') });
        showAppNotice('Network error while updating like. Please try again.');
    })
    .finally(() => {
        likingInProgress.delete(postId);
    });
}

// ==================== COMMENTS ====================
function loadComments(postId) {
    const commentsSection = document.getElementById('comments-' + postId);
    if (!commentsSection) return;
    
    // Show loading state
    commentsSection.innerHTML = '<p class="text-center text-muted small"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('action', 'get');
    
    fetch(getApiPath('be/posts/comment.php'), {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            commentsSection.innerHTML = '';
            
            if (data.comments.length === 0) {
                commentsSection.innerHTML = '<p class="text-muted text-center small">No comments yet</p>';
                return;
            }
            
            data.comments.forEach(comment => {
                const avatar = getAvatarUrl(comment.avatar_url);
                const profileUrl = window.location.pathname.includes('/users/') ? 'profile.php?id=' + comment.user_id : 'be/users/profile.php?id=' + comment.user_id;
                let commentHtml = `
                    <div class="comment-item mb-2 pb-2 border-bottom">
                        <div class="d-flex gap-2">
                            <a href="${profileUrl}"><img src="${avatar}" class="rounded-circle" width="32" height="32" style="object-fit: cover;" onerror="handleAvatarError(this)"></a>
                            <div class="flex-grow-1">
                                <a href="${profileUrl}" class="text-decoration-none" style="color:var(--text-primary);"><small class="fw-bold">${comment.username}</small></a>
                                <p class="mb-1 small">${comment.content}</p>
                                <small class="text-muted">${formatDate(comment.created_at)}</small>
                            </div>`;
                
                // Check if current user can delete
                const currentUserId = parseInt(document.body.dataset.userId || '0');
                if (currentUserId > 0 && comment.user_id == currentUserId) {
                    commentHtml += `<button onclick="deleteComment(${postId}, ${comment.id})" class="btn btn-sm btn-link text-danger p-0">
                                    <i class="fas fa-trash-sm"></i>
                                </button>`;
                }
                commentHtml += `</div></div>`;
                commentsSection.innerHTML += commentHtml;
            });
        } else {
            commentsSection.innerHTML = '<p class="text-danger text-center small">Error: ' + (data.error || 'Failed to load') + '</p>';
        }
    })
    .catch(error => {
        debugLog('error', 'Loading comments failed', { postId, error: getErrorMessage(error, 'Network error') });
        commentsSection.innerHTML = '<p class="text-danger text-center small">Failed to load comments</p>';
    });
}

function addComment(postId) {
    const commentInput = document.getElementById('comment-input-' + postId);
    const content = commentInput.value.trim();
    
    if (!content) {
        alert('Please write a comment');
        return;
    }
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('content', content);
    formData.append('action', 'add');
    formData.append('csrf_token', getCsrfToken());
    
    fetch(getApiPath('be/posts/comment.php'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            commentInput.value = '';
            loadComments(postId);
            // Update comment count
            const countBadge = document.getElementById('comment-count-' + postId);
            if (countBadge) {
                countBadge.textContent = parseInt(countBadge.textContent) + 1;
            }
        } else {
            showAppNotice('Error: ' + (data.error || 'Failed to add comment'));
            debugLog('warn', 'Add comment rejected', { postId, error: data.error || 'Unknown error' });
        }
    })
    .catch(error => {
        debugLog('error', 'Add comment request failed', { postId, error: getErrorMessage(error, 'Network error') });
        showAppNotice('Failed to add comment. Check your connection.');
    });
}

function deleteComment(postId, commentId) {
    if (!confirm('Delete this comment?')) return;
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('comment_id', commentId);
    formData.append('action', 'delete');
    formData.append('csrf_token', getCsrfToken());
    
    fetch(getApiPath('be/posts/comment.php'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadComments(postId);
            const countBadge = document.getElementById('comment-count-' + postId);
            if (countBadge) {
                countBadge.textContent = Math.max(0, parseInt(countBadge.textContent) - 1);
            }
        }
    });
}

function toggleComments(postId) {
    const section = document.getElementById('comment-section-' + postId);
    section.classList.toggle('d-none');
    if (!section.classList.contains('d-none')) {
        loadComments(postId);
    }
}

// ==================== FOLLOWS ====================
function toggleFollow(userId, button) {
    const isFollowing = button.classList.contains('following');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    const formData = new FormData();
    formData.append('target_id', userId);
    formData.append('action', action);
    formData.append('csrf_token', getCsrfToken());
    
    fetch(getApiPath('be/users/follow.php'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.is_following) {
                button.classList.add('following');
                button.innerHTML = '<i class="fas fa-user-check"></i> Following';
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-primary');
            } else {
                button.classList.remove('following');
                button.innerHTML = '<i class="fas fa-user-plus"></i> Follow';
                button.classList.add('btn-outline-primary');
                button.classList.remove('btn-primary');
            }
            
            if (document.getElementById('followers-' + userId)) {
                document.getElementById('followers-' + userId).textContent = data.followers;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

// ==================== FRIEND REQUESTS ====================
function sendFriendRequest(receiverId) {
    const btn = document.getElementById('addFriendBtn');
    if (!btn) return;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    
    const formData = new FormData();
    formData.append('receiver_id', receiverId);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('../friends/send_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // Success
            document.getElementById('friendActionContainer').innerHTML = 
                '<span class="badge bg-warning text-dark fs-6 px-3 py-2">Request sent</span>';
        } else {
            return response.text().then(text => {
                throw new Error(text);
            });
        }
    })
    .catch(error => {
        debugLog('error', 'Friend request failed', { receiverId, error: getErrorMessage(error, 'Network error') });
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Add Friend';
        
        let message = 'Failed to send friend request';
        if (error.message.includes('already sent') || error.message.includes('already exists')) {
            message = 'Friend request already sent';
        } else if (error.message.includes('Already friends')) {
            message = 'You are already friends';
        }
        
        showAppNotice(message, 'warning');
    });
}

// ==================== SEARCH ====================
let searchTimeout;

function performSearch(element) {
    const isElement = typeof element !== 'string' && element.value !== undefined;
    const query = isElement ? element.value : element;
    clearTimeout(searchTimeout);
    
    const resultsDiv = isElement 
        ? element.closest('.dropdown-menu').querySelector('.searchResults') 
        : (document.querySelector('.searchResults') || document.getElementById('searchResults'));
    
    if (!resultsDiv) {
        return;
    }
    
    if (query.length < 2) {
        resultsDiv.innerHTML = '<div class="fb-search-item text-center" style="color: var(--text-muted); padding: 20px;">Type at least 2 characters</div>';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        const path = window.location.pathname;
        let searchPath = 'be/search.php';
        
        // If we're in /fe/pages/ or /be/ we need to go up two levels
        if (path.includes('/fe/pages/') || path.includes('/be/')) {
            searchPath = '../../be/search.php';
        }
        
        console.log('Fetching from path:', searchPath, 'query:', query);
        
        fetch(searchPath + '?q=' + encodeURIComponent(query))
            .then(response => {
                console.log('Response received, status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Search data:', data);
                if (data.success) {
                    displaySearchResults(data.results, resultsDiv);
                } else {
                    resultsDiv.innerHTML = '<div class="fb-search-item text-center" style="color: var(--text-muted); padding: 20px;">No results</div>';
                }
            })
            .catch(err => {
                debugLog('error', 'Search request failed', { query, error: getErrorMessage(err, 'Network error') });
                resultsDiv.innerHTML = '<div class="fb-search-item text-center" style="color: var(--text-muted); padding: 20px;">Search error</div>';
            });
    }, 300);
}

function displaySearchResults(results, targetDiv) {
    const resultsDiv = targetDiv || document.querySelector('.searchResults') || document.getElementById('searchResults');
    if (!resultsDiv) return;
    
    const path = window.location.pathname;
    const isInFePages = path.includes('/fe/pages/');
    const isInBe = path.includes('/be/');
    
    let html = '';
    
    // Users
    if (results.users && results.users.length > 0) {
        html += '<div class="fb-search-category">Users</div>';
        results.users.forEach(user => {
            let avatar = getAvatarUrl(user.avatar_url);
            
            // Determine correct path based on current location
            let profilePath;
            if (isInFePages || isInBe) {
                profilePath = '../../be/users/profile.php';
            } else {
                profilePath = 'be/users/profile.php';
            }
            
            const username = escapeHtml(user.username);
            const fullName = escapeHtml(user.full_name);
            
            html += `
                <a href="${profilePath}?id=${user.id}" class="fb-search-item">
                    <img src="${avatar}" class="rounded-circle" width="36" height="36" style="object-fit: cover;" onerror="handleAvatarError(this)">
                    <div class="flex-grow-1">
                        <div style="font-weight: 600; font-size: 15px;">${username}</div>
                        <div style="font-size: 13px; color: var(--text-muted);">${fullName}</div>
                    </div>
                    ${user.is_friend ? '<span class="badge bg-success">Friend</span>' : ''}
                </a>
            `;
        });
    }
    
    // Posts
    if (results.posts && results.posts.length > 0) {
        html += '<div class="fb-search-category">Posts</div>';
        results.posts.forEach(post => {
            const title = post.title ? escapeHtml(post.title) : 'Без заглавие';
            const content = post.content ? escapeHtml(post.content.substring(0, 60)) : '';
            html += `
                <div class="fb-search-item">
                    <i class="fas fa-file-alt" style="color: var(--text-muted); width: 36px; text-align: center; font-size: 18px;"></i>
                    <div class="flex-grow-1">
                        <div style="font-weight: 600; font-size: 15px;">${title}</div>
                        <div style="font-size: 13px; color: var(--text-muted);">${content}${content.length >= 60 ? '...' : ''}</div>
                    </div>
                </div>
            `;
        });
    }
    

    
    if (!html) {
        html = '<div class="fb-search-item text-center" style="color: var(--text-muted); padding: 20px;">No results found</div>';
    }
    
    resultsDiv.classList.remove('d-none');
    resultsDiv.innerHTML = html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ==================== MESSAGING ====================
function loadConversations() {
    const path = window.location.pathname;
    let messagesPath = 'be/messages/message.php';
    
    // Adjust path based on current location
    if (path.includes('/be/')) {
        messagesPath = '../messages/message.php';
    } else if (path.includes('/fe/pages/')) {
        messagesPath = '../../be/messages/message.php';
    }
    
    fetch(messagesPath + '?action=get_conversations')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversations(data.conversations);
            }
        })
        .catch(error => console.error('Error loading conversations:', error));
}

function displayConversations(conversations) {
    const container = document.getElementById('conversationsList');
    if (!container) return;
    
    if (!conversations || conversations.length === 0) {
        container.innerHTML = '<p class="text-center text-muted p-3">No conversations yet</p>';
        return;
    }
    
    let html = '';
    conversations.forEach(conv => {
        const avatar = getAvatarUrl(conv.avatar_url);
        const unreadClass = conv.unread_count > 0 ? 'fw-bold' : '';
        const lastMsg = conv.last_message ? conv.last_message.substring(0, 45) + (conv.last_message.length > 45 ? '...' : '') : 'No messages';
        html += `
            <div class="conversation-item p-3" data-user-id="${conv.other_user_id}" onclick="openConversation(${conv.other_user_id})">
                <div class="d-flex gap-2">
                    <img src="${avatar}" class="rounded-circle" width="40" height="40" style="object-fit: cover;" onerror="handleAvatarError(this)">
                    <div class="flex-grow-1 min-width-0">
                        <h6 class="mb-0 ${unreadClass}">${conv.username}</h6>
                        <small class="text-muted text-truncate d-block">${lastMsg}</small>
                    </div>
                    ${conv.unread_count > 0 ? `<span class="badge bg-primary ms-2">${conv.unread_count}</span>` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function openConversation(userId) {
    // Load and display conversation
    const path = window.location.pathname;
    let messagesPath = 'be/messages/message.php';
    
    // Adjust path based on current location
    if (path.includes('/be/')) {
        messagesPath = '../messages/message.php';
    } else if (path.includes('/fe/pages/')) {
        messagesPath = '../../be/messages/message.php';
    }
    
    fetch(messagesPath + '?action=get_conversation&receiver_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayConversation(data.messages, data.current_user_id, userId);
            }
        });
}

function displayConversation(messages, currentUserId, otherId) {
    const container = document.getElementById('conversationThread');
    if (!container) return;
    
    let html = '';
    messages.forEach(msg => {
        const isOwn = msg.sender_id == currentUserId;
        const groupClass = isOwn ? 'own' : 'other';
        
        let attachmentsHtml = '';
        if (msg.attachment_urls) {
            const attachments = JSON.parse(msg.attachment_urls);
            attachments.forEach(url => {
                const fileExt = url.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                    attachmentsHtml += `<img src="${url}" class="img-fluid rounded mb-2" style="max-width: 200px; max-height: 200px;" onclick="window.open(this.src)">`;
                } else if (['mp4', 'avi', 'mov'].includes(fileExt)) {
                    attachmentsHtml += `<video controls class="mb-2" style="max-width: 200px; max-height: 200px;"><source src="${url}" type="video/${fileExt}"></video>`;
                }
            });
        }
        
        html += `
            <div class="message-group ${groupClass}">
                <div class="message-bubble" style="max-width: 70%;">
                    ${attachmentsHtml}
                    ${msg.content ? `<p class="mb-0">${msg.content}</p>` : ''}
                    <small class="message-time">${formatDate(msg.created_at)}</small>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    const scrollArea = container.parentElement;
    scrollArea.scrollTop = scrollArea.scrollHeight;
}

function sendMessage(receiverId) {
    const input = document.getElementById('messageInput');
    const fileInput = document.getElementById('fileInput');
    const content = input.value.trim();
    const files = fileInput ? fileInput.files : [];
    
    // Allow sending if there's content or files
    if (!content && files.length === 0) return;
    
    const path = window.location.pathname;
    let messagesPath = 'be/messages/message.php';
    
    // Adjust path based on current location
    if (path.includes('/be/')) {
        messagesPath = '../messages/message.php';
    } else if (path.includes('/fe/pages/')) {
        messagesPath = '../../be/messages/message.php';
    }
    
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', receiverId);
    formData.append('content', content);
    formData.append('csrf_token', getCsrfToken());
    
    // Add files
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    
    fetch(messagesPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            if (fileInput) {
                fileInput.value = '';
                document.getElementById('filePreview').innerHTML = '';
            }
            openConversation(receiverId);
        }
    });
}

// Load conversations on page load
if (document.body.dataset.userId && document.body.dataset.userId != '0') {
    if (document.getElementById('conversationsList')) {
        loadConversations();
        setInterval(loadConversations, 15000);
    }
}

// ==================== NOTIFICATIONS ====================
function loadNotifications() {
    const path = window.location.pathname;
    let notifPath = 'be/notifications/get_notifications.php';
    
    // Adjust path based on current location
    if (path.includes('/be/')) {
        notifPath = '../notifications/get_notifications.php';
    } else if (path.includes('/fe/pages/')) {
        notifPath = '../../be/notifications/get_notifications.php';
    }
    
    fetch(notifPath + '?limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function displayNotifications(notifications) {
    const container = document.getElementById('notificationList');
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = `<div class="dropdown-item text-center text-muted">${window.i18n?.no_notifications_empty || 'No notifications'}</div>`;
        return;
    }
    
    // Determine correct paths based on current location
    const path = window.location.pathname;
    const isInFePages = path.includes('/fe/pages/');
    const isInBe = path.includes('/be/');
    
    let profilePath, friendRequestPath;
    if (isInFePages || isInBe) {
        profilePath = '../../be/users/profile.php';
        friendRequestPath = '../../be/friends/list_requests.php';
    } else {
        profilePath = 'be/users/profile.php';
        friendRequestPath = 'be/friends/list_requests.php';
    }
    
    let html = '';
    notifications.forEach(notif => {
        const avatar = getAvatarUrl(notif.avatar_url);
        let message = '';
        let clickAction = '';
        
        switch(notif.type) {
            case 'like':
                message = `<strong>${notif.username}</strong> ${window.i18n?.notif_liked_post || 'liked your post'}`;
                clickAction = notif.post_id ? `onclick="handleNotificationClick(${notif.id}, ${notif.post_id})"` : '';
                break;
            case 'comment':
                message = `<strong>${notif.username}</strong> ${window.i18n?.notif_commented_post || 'commented on your post'}`;
                clickAction = notif.post_id ? `onclick="handleNotificationClick(${notif.id}, ${notif.post_id})"` : '';
                break;
            case 'follow':
                message = `<strong>${notif.username}</strong> ${window.i18n?.notif_started_following || 'started following you'}`;
                clickAction = `onclick="handleNotificationClickAndNavigate(${notif.id}, '${profilePath}?id=${notif.from_user_id}')"`;
                break;
            case 'friend_request':
                message = `<strong>${notif.username}</strong> ${window.i18n?.notif_friend_request || 'sent you a friend request'}`;
                clickAction = `onclick="handleNotificationClickAndNavigate(${notif.id}, '${friendRequestPath}')"`;
                break;
            case 'friend_accepted':
                message = `<strong>${notif.username}</strong> ${window.i18n?.notif_friend_accepted || 'accepted your friend request'}`;
                clickAction = `onclick="handleNotificationClickAndNavigate(${notif.id}, '${profilePath}?id=${notif.from_user_id}')"`;
                break;
            case 'new_post':
                message = `<strong>${notif.username}</strong> ${window.i18n?.notif_new_post || 'shared a new post'}`;
                clickAction = notif.post_id ? `onclick="handleNotificationClick(${notif.id}, ${notif.post_id})"` : '';
                break;
            default:
                message = `<strong>${notif.username}</strong> ${window.i18n?.notif_default_action || 'performed an action'}`;
                clickAction = '';
        }
        
        const readClass = notif.is_read ? '' : 'bg-light';
        html += `
            <div class="dropdown-item ${readClass} notification-item" ${clickAction} style="cursor: pointer;">
                <div class="d-flex gap-2">
                    <img src="${avatar}" class="rounded-circle" width="32" height="32" style="object-fit: cover;" onerror="handleAvatarError(this)">
                    <div class="flex-grow-1">
                        <small>${message}</small>
                        <div class="small text-muted">${formatDate(notif.created_at)}</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (!badge) return;
    
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

function markNotificationRead(notificationId) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    formData.append('action', 'mark_read');
    formData.append('csrf_token', getCsrfToken());
    
    fetch(getApiPath('be/notifications/mark_read.php'), {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

function markAllNotificationsRead() {
    const path = window.location.pathname;
    let markReadPath = 'be/notifications/mark_read.php';
    
    // Adjust path based on current location
    if (path.includes('/be/')) {
        markReadPath = '../notifications/mark_read.php';
    } else if (path.includes('/fe/pages/')) {
        markReadPath = '../../be/notifications/mark_read.php';
    }
    
    const formData = new FormData();
    formData.append('action', 'mark_all_read');
    formData.append('csrf_token', getCsrfToken());
    
    fetch(markReadPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge immediately
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.classList.add('d-none');
            }
            // Reload notifications list
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking all read:', error));
}

function handleNotificationClick(notificationId, postId) {
    const path = window.location.pathname;
    let markReadPath = 'be/notifications/mark_read.php';
    
    // Adjust path based on current location
    if (path.includes('/be/')) {
        markReadPath = '../notifications/mark_read.php';
    } else if (path.includes('/fe/pages/')) {
        markReadPath = '../../be/notifications/mark_read.php';
    }
    
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    formData.append('action', 'mark_read');
    formData.append('csrf_token', getCsrfToken());
    
    fetch(markReadPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Immediately update the badge count
            const badge = document.getElementById('notificationBadge');
            if (badge && !badge.classList.contains('d-none')) {
                const currentCount = parseInt(badge.textContent);
                const newCount = Math.max(0, currentCount - 1);
                if (newCount > 0) {
                    badge.textContent = newCount > 99 ? '99+' : newCount;
                } else {
                    badge.classList.add('d-none');
                }
            }
            // Then reload full list
            loadNotifications();
            if (postId) {
                // Scroll to post or navigate to post if needed
            }
        }
    })
    .catch(error => console.error('Error marking notification read:', error));
}

function handleNotificationClickAndNavigate(notificationId, url) {
    const path = window.location.pathname;
    let markReadPath = 'be/notifications/mark_read.php';
    
    // Adjust path based on current location
    if (path.includes('/be/')) {
        markReadPath = '../notifications/mark_read.php';
    } else if (path.includes('/fe/pages/')) {
        markReadPath = '../../be/notifications/mark_read.php';
    }
    
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    formData.append('action', 'mark_read');
    formData.append('csrf_token', getCsrfToken());
    
    fetch(markReadPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Immediately update the badge count
            const badge = document.getElementById('notificationBadge');
            if (badge && !badge.classList.contains('d-none')) {
                const currentCount = parseInt(badge.textContent);
                const newCount = Math.max(0, currentCount - 1);
                if (newCount > 0) {
                    badge.textContent = newCount > 99 ? '99+' : newCount;
                } else {
                    badge.classList.add('d-none');
                }
            }
            // Navigate to URL
            window.location.href = url;
        }
    })
    .catch(error => {
        console.error('Error marking notification read:', error);
        // Navigate anyway
        window.location.href = url;
    });
}

function loadMessageBadge() {
    const path = window.location.pathname;
    let messagesPath = 'be/messages/message.php';
    if (path.includes('/fe/pages/') || path.includes('/be/')) {
        messagesPath = '../../be/messages/message.php';
    }

    fetch(messagesPath + '?action=get_unread_count')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('messageBadge');
                if (!badge) return;
                const count = parseInt(data.unread_count) || 0;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            }
        })
        .catch(() => {});
}

// Load notifications when page loads and refresh periodically
if (document.body.dataset.userId && document.body.dataset.userId != '0') {
    loadNotifications();
    loadMessageBadge();
    setInterval(loadNotifications, 30000); // Refresh every 30 seconds
    setInterval(loadMessageBadge, 30000);
}

// Close search dropdown when clicking outside
document.addEventListener('click', function(e) {
    document.querySelectorAll('.dropdown').forEach(dropdown => {
        const input = dropdown.querySelector('.searchInput');
        const results = dropdown.querySelector('.searchResults');
        
        if (input && results && !dropdown.contains(e.target)) {
            results.classList.add('d-none');
        }
    });
});

// ==================== UTILITIES ====================
function formatDate(dateString) {
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return '';

    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    const lang = (document.documentElement.lang || '').toLowerCase();
    const isBg = lang.startsWith('bg');

    if (days > 0) return isBg ? `преди ${days} дни` : `${days}d ago`;
    if (hours > 0) return isBg ? `преди ${hours} часа` : `${hours}h ago`;
    if (minutes > 0) return isBg ? `преди ${minutes} мин` : `${minutes}m ago`;
    return isBg ? 'току-що' : 'just now';
}

function localizeIsoDates() {
    document.querySelectorAll('[data-iso-date]').forEach(el => {
        const iso = el.getAttribute('data-iso-date');
        if (!iso) return;
        el.textContent = formatDate(iso);
    });
}

// If DOM is already ready, run immediately (scripts can be loaded at end of body)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', localizeIsoDates);
} else {
    localizeIsoDates();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (APP_DEBUG) {
            renderDebugPanel();
            showAppNotice('Debug mode enabled (disable with localStorage.removeItem("fishingDebug"))', 'info', 3000);
        }
        initScrollToTop();
    });
} else {
    if (APP_DEBUG) {
        renderDebugPanel();
    }
    initScrollToTop();
}

// Scroll-to-top button
function initScrollToTop() {
    var btn = document.createElement('button');
    btn.className = 'scroll-to-top';
    btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    btn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(btn);

    window.addEventListener('scroll', function() {
        if (window.scrollY > 400) {
            btn.classList.add('visible');
        } else {
            btn.classList.remove('visible');
        }
    }, { passive: true });

    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

// ==================== BUBBLE EFFECTS ====================
function slowDownBubble(bubble) {
    // Заменяме текущата скорост с много бавна (напр. 30 секунди за изкачване)
    bubble.style.setProperty('--duration', '30s');
    bubble.style.opacity = '0.4'; // Визуален фийдбек, че е докоснато
}

function initBubbles() {
    const TRANSITION_EXIT_MS = 320;

    const container = document.createElement('div');
    container.className = 'bubble-container';
    document.body.appendChild(container);

    const transitionContainer = document.createElement('div');
    transitionContainer.className = 'bubble-container transition-bubble-container';
    document.body.appendChild(transitionContainer);

    const overlay = document.createElement('div');
    overlay.className = 'transition-overlay';
    document.body.appendChild(overlay);

    let isNavigating = false;

    const createTransitionSpinner = () => {
        transitionContainer.innerHTML = '';
        const spinner = document.createElement('div');
        spinner.className = 'bubble-spinner';

        for (let i = 0; i < 12; i++) {
            const dot = document.createElement('span');
            dot.className = 'bubble-spinner__dot';
            dot.style.setProperty('--dot-index', i.toString());
            spinner.appendChild(dot);
        }

        transitionContainer.appendChild(spinner);
    };

    // Create background bubbles with optional stagger to avoid visual popping.
    const spawnBackgroundBubbles = (count = 15, staggerMs = 0) => {
        if (staggerMs <= 0) {
            for (let i = 0; i < count; i++) createOneBubble(container);
            return;
        }

        let created = 0;
        const intervalId = setInterval(() => {
            createOneBubble(container);
            created++;
            if (created >= count) clearInterval(intervalId);
        }, staggerMs);
    };

    spawnBackgroundBubbles(24, 0);

    // Handle page transitions
    document.addEventListener('click', e => {
        const link = e.target.closest('a');
        if (link &&
            link.href && 
            !isNavigating &&
            e.button === 0 &&
            !e.metaKey &&
            !e.ctrlKey &&
            !e.shiftKey &&
            !e.altKey &&
            !link.target && 
            !link.hasAttribute('download') &&
            !link.dataset.noTransition &&
            link.origin === window.location.origin && 
            !link.href.includes('#') &&
            !link.getAttribute('onclick')) {
            
            isNavigating = true;
            e.preventDefault();
            
            // Fade out page content smoothly
            requestAnimationFrame(() => {
                document.body.classList.add('fade-out');
                // Fade out background bubbles smoothly
                container.classList.add('fade-out');
                // Activate transition container with fade-in
                transitionContainer.classList.add('active');
            });

            createTransitionSpinner();

            setTimeout(() => {
                window.location.href = link.href;
            }, TRANSITION_EXIT_MS);
        }
    });
}

function createOneBubble(container) {
    const bubble = document.createElement('div');
    bubble.className = 'bg-bubble';
    
    const size = Math.random() * 60 + 20 + 'px';
    bubble.style.width = size;
    bubble.style.height = size;
    bubble.style.left = Math.random() * 100 + '%';
    
    bubble.style.bottom = '-150px';
    
    const duration = Math.random() * 5 + 8;
    bubble.style.setProperty('--duration', duration + 's');
    bubble.style.setProperty('--drift', (Math.random() * 80 - 40) + 'px');
    bubble.style.animationDelay = (Math.random() * 3) + 's';
    
    // Събитие за пукане при клик
    bubble.addEventListener('mousedown', (e) => {
        e.preventDefault();
        e.stopPropagation();
        slowDownBubble(bubble);
    });

    container.appendChild(bubble);
}

// Initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBubbles);
} else {
    initBubbles();
}

// ===== Post text show more / show less toggle =====
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.post-toggle-btn');
    if (!btn) return;
    const wrapper = btn.previousElementSibling;
    if (!wrapper) return;
    const isExpanded = wrapper.classList.contains('post-text-expanded');
    if (isExpanded) {
        wrapper.classList.remove('post-text-expanded');
        wrapper.classList.add('post-text-collapsed');
        btn.classList.remove('expanded');
        btn.innerHTML = btn.dataset.show + ' <i class="fas fa-chevron-down"></i>';
    } else {
        wrapper.classList.remove('post-text-collapsed');
        wrapper.classList.add('post-text-expanded');
        btn.classList.add('expanded');
        btn.innerHTML = btn.dataset.hide + ' <i class="fas fa-chevron-down"></i>';
    }
});


