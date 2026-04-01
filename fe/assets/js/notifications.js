// Notifications module for FISHINGLORY

function loadNotifications() {
    fetch(resolvePath('be/notifications/get_notifications.php') + '?limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications);
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => debugLog('error', 'Loading notifications failed', { error: getErrorMessage(error, 'Network error') }));
}

function displayNotifications(notifications) {
    const container = document.getElementById('notificationList');
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = '<div class="dropdown-item text-center text-muted">No notifications</div>';
        return;
    }
    
    const profilePath = resolvePath('be/users/profile.php');
    const friendRequestPath = resolvePath('be/friends/list_requests.php');
    
    let html = '';
    notifications.forEach(notif => {
        const avatar = getAvatarUrl(notif.avatar_url);
        const safeAvatar = escapeHtml(avatar);
        const safeUsername = escapeHtml(notif.username || '');
        let message = '';
        let clickAction = '';
        
        switch(notif.type) {
            case 'like':
                message = `<strong>${safeUsername}</strong> liked your post`;
                clickAction = notif.post_id ? `onclick="handleNotificationClick(${notif.id}, ${notif.post_id})"` : '';
                break;
            case 'comment':
                message = `<strong>${safeUsername}</strong> commented on your post`;
                clickAction = notif.post_id ? `onclick="handleNotificationClick(${notif.id}, ${notif.post_id})"` : '';
                break;
            case 'follow':
                message = `<strong>${safeUsername}</strong> started following you`;
                clickAction = `onclick="handleNotificationClickAndNavigate(${notif.id}, '${profilePath}?id=${notif.from_user_id}')"`;
                break;
            case 'friend_request':
                message = `<strong>${safeUsername}</strong> sent you a friend request`;
                clickAction = `onclick="handleNotificationClickAndNavigate(${notif.id}, '${friendRequestPath}')"`;
                break;
            case 'friend_accepted':
                message = `<strong>${safeUsername}</strong> accepted your friend request`;
                clickAction = `onclick="handleNotificationClickAndNavigate(${notif.id}, '${profilePath}?id=${notif.from_user_id}')"`;
                break;
            case 'new_post':
                message = `<strong>${safeUsername}</strong> shared a new post`;
                clickAction = notif.post_id ? `onclick="handleNotificationClick(${notif.id}, ${notif.post_id})"` : '';
                break;
            default:
                message = `<strong>${safeUsername}</strong> performed an action`;
                clickAction = '';
        }
        
        const readClass = notif.is_read ? '' : 'bg-light';
        html += `
            <div class="dropdown-item ${readClass} notification-item" ${clickAction} style="cursor: pointer;">
                <div class="d-flex gap-2">
                    <img src="${safeAvatar}" class="rounded-circle" width="32" height="32" style="object-fit: cover;">
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
    
    fetch(resolvePath('be/notifications/mark_read.php'), {
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
    const markReadPath = resolvePath('be/notifications/mark_read.php');
    
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
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                badge.classList.add('d-none');
            }
            loadNotifications();
        }
    })
    .catch(error => debugLog('error', 'Marking all notifications read failed', { error: getErrorMessage(error, 'Network error') }));
}

function handleNotificationClick(notificationId, postId) {
    const markReadPath = resolvePath('be/notifications/mark_read.php');
    
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
            loadNotifications();
            if (postId) {
                // Scroll to post or navigate to post if needed
            }
        }
    })
    .catch(error => debugLog('error', 'Marking notification read failed', { error: getErrorMessage(error, 'Network error') }));
}

function handleNotificationClickAndNavigate(notificationId, url) {
    const markReadPath = resolvePath('be/notifications/mark_read.php');
    
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
            window.location.href = url;
        }
    })
    .catch(error => {
        debugLog('error', 'Marking notification read failed', { error: getErrorMessage(error, 'Network error') });
        window.location.href = url;
    });
}

// Load notifications when page loads and refresh periodically
if (document.body.dataset.userId && document.body.dataset.userId != '0') {
    loadNotifications();
    setInterval(loadNotifications, 30000);
}
