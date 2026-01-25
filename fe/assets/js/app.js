// Comprehensive JavaScript for all fishing app features

// Helper function to get CSRF token
function getCsrfToken() {
    return document.body.dataset.csrfToken || '';
}

// ==================== LIKES ====================
function toggleLike(postId, button) {
    const isLiked = button.classList.contains('liked');
    const action = isLiked ? 'unlike' : 'like';
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('action', action);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('be/posts/like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state
            if (data.liked) {
                button.classList.add('liked');
                button.innerHTML = '<i class="fas fa-heart"></i> <span id="like-count-' + postId + '">' + data.like_count + '</span>';
            } else {
                button.classList.remove('liked');
                button.innerHTML = '<i class="far fa-heart"></i> <span id="like-count-' + postId + '">' + data.like_count + '</span>';
            }
        } else if (data.error) {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => console.error('Error:', error));
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
    
    fetch('be/posts/comment.php', {
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
                const avatar = comment.avatar_url || 'fe/assets/img/default-avatar.png';
                let commentHtml = `
                    <div class="comment-item mb-2 pb-2 border-bottom">
                        <div class="d-flex gap-2">
                            <img src="${avatar}" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <small class="fw-bold">${comment.username}</small>
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
        console.error('Error loading comments:', error);
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
    
    fetch('be/posts/comment.php', {
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
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add comment');
    });
}

function deleteComment(postId, commentId) {
    if (!confirm('Delete this comment?')) return;
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('comment_id', commentId);
    formData.append('action', 'delete');
    formData.append('csrf_token', getCsrfToken());
    
    fetch('be/posts/comment.php', {
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
    
    fetch('be/users/follow.php', {
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

// ==================== SEARCH ====================
let searchTimeout;

function performSearch(query) {
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        document.getElementById('searchResults').classList.add('d-none');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch('be/search.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displaySearchResults(data.results);
                    document.getElementById('searchResults').classList.remove('d-none');
                }
            });
    }, 300);
}

function displaySearchResults(results) {
    let html = '<div class="search-results-dropdown">';
    
    // Users
    if (results.users && results.users.length > 0) {
        html += '<div class="search-category"><strong>Users</strong></div>';
        results.users.forEach(user => {
            const avatar = user.avatar_url || 'fe/assets/img/default-avatar.png';
            html += `
                <a href="be/users/profile.php?id=${user.id}" class="search-item">
                    <img src="${avatar}" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                    <div>
                        <strong>${user.username}</strong>
                        <small class="text-muted">${user.full_name}</small>
                    </div>
                </a>
            `;
        });
    }
    
    // Posts
    if (results.posts && results.posts.length > 0) {
        html += '<div class="search-category"><strong>Posts</strong></div>';
        results.posts.forEach(post => {
            html += `
                <div class="search-item">
                    <strong>${post.title}</strong>
                    <p class="mb-0 small text-muted">${post.content.substring(0, 100)}...</p>
                    <small class="text-muted">by ${post.username}</small>
                </div>
            `;
        });
    }
    
    // Spots
    if (results.spots && results.spots.length > 0) {
        html += '<div class="search-category"><strong>Fishing Spots</strong></div>';
        results.spots.forEach(spot => {
            html += `
                <a href="#" class="search-item" onclick="viewSpot(${spot.id})">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <strong>${spot.name}</strong>
                        <small class="text-muted">${spot.type}</small>
                    </div>
                </a>
            `;
        });
    }
    
    if ((!results.users || results.users.length === 0) && 
        (!results.posts || results.posts.length === 0) && 
        (!results.spots || results.spots.length === 0)) {
        html += '<div class="search-item">No results found</div>';
    }
    
    html += '</div>';
    document.getElementById('searchResults').innerHTML = html;
}

function viewSpot(spotId) {
    // Show spot on map or details
    alert('Spot details coming soon!');
}

// ==================== MESSAGING ====================
function loadConversations() {
    fetch('be/messages/message.php?action=get_conversations')
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
        const avatar = conv.avatar_url || 'fe/assets/img/default-avatar.png';
        const unreadClass = conv.unread_count > 0 ? 'fw-bold' : '';
        const lastMsg = conv.last_message ? conv.last_message.substring(0, 45) + (conv.last_message.length > 45 ? '...' : '') : 'No messages';
        html += `
            <div class="conversation-item p-3" onclick="openConversation(${conv.other_user_id})">
                <div class="d-flex gap-2">
                    <img src="${avatar}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
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
    fetch('be/messages/message.php?action=get_conversation&receiver_id=' + userId)
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
        const alignment = isOwn ? 'text-end' : 'text-start';
        const bgClass = isOwn ? 'bg-primary text-white' : 'bg-light';
        
        html += `
            <div class="message-group mb-2 ${alignment}">
                <div class="d-inline-block ${bgClass} p-2 rounded" style="max-width: 70%;">
                    <p class="mb-0">${msg.content}</p>
                    <small class="${isOwn ? 'text-white-50' : 'text-muted'}">${formatDate(msg.created_at)}</small>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

function sendMessage(receiverId) {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    
    if (!content) return;
    
    const formData = new FormData();
    formData.append('action', 'send');
    formData.append('receiver_id', receiverId);
    formData.append('content', content);
    formData.append('csrf_token', getCsrfToken());
    
    fetch('be/messages/message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
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
    fetch('be/notifications/get_notifications.php?limit=10')
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
    const container = document.getElementById('notificationsList');
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = '<div class="dropdown-item text-center text-muted">No notifications</div>';
        return;
    }
    
    let html = '';
    notifications.forEach(notif => {
        const avatar = notif.avatar_url || 'fe/assets/img/default-avatar.png';
        let message = '';
        
        switch(notif.type) {
            case 'like':
                message = `<strong>${notif.username}</strong> liked your post`;
                break;
            case 'comment':
                message = `<strong>${notif.username}</strong> commented on your post`;
                break;
            case 'follow':
                message = `<strong>${notif.username}</strong> started following you`;
                break;
            case 'friend_request':
                message = `<strong>${notif.username}</strong> sent you a friend request`;
                break;
            default:
                message = `<strong>${notif.username}</strong> performed an action`;
        }
        
        const readClass = notif.is_read ? '' : 'bg-light';
        html += `
            <div class="dropdown-item ${readClass} notification-item" onclick="handleNotificationClick(${notif.id}, ${notif.post_id})">
                <div class="d-flex gap-2">
                    <img src="${avatar}" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
                    <div class="flex-grow-1">
                        <small>${message}</small>
                        <div class="small text-muted">${formatDate(notif.created_at)}</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html + `
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item text-center small text-primary">View all notifications</a>
    `;
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
    
    fetch('be/notifications/mark_read.php', {
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

function handleNotificationClick(notificationId, postId) {
    markNotificationRead(notificationId);
    if (postId) {
        // Scroll to post or navigate to post
        // For now, just reload to show updated notifications
        loadNotifications();
    }
}

// Load notifications when page loads and refresh periodically
if (document.body.dataset.userId && document.body.dataset.userId != '0') {
    loadNotifications();
    setInterval(loadNotifications, 30000); // Refresh every 30 seconds
}

// ==================== UTILITIES ====================
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 0) return days + 'd ago';
    if (hours > 0) return hours + 'h ago';
    if (minutes > 0) return minutes + 'm ago';
    return 'just now';
}
