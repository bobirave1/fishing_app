/**
 * FISHINGLORY — Push notifications via browser Notification API.
 */
(function () {
    'use strict';

    var pushPollTimer = null;
    var lastPushTimestamp = null;
    var PUSH_POLL_INTERVAL = 15000; // 15 seconds

    var isBg = (document.documentElement.lang || '').startsWith('bg');

    function init() {
        var userId = document.body.dataset.userId;
        if (!userId || userId === '0') return;

        // Check if notifications are supported
        if (!('Notification' in window)) return;

        // Request permission if not already granted
        if (Notification.permission === 'default') {
            // Show opt-in button in navbar
            showOptInButton();
        } else if (Notification.permission === 'granted') {
            startPushPolling();
        }
    }

    function showOptInButton() {
        var navArea = document.querySelector('.navbar-notifications') || document.querySelector('.navbar-nav');
        if (!navArea) return;

        var btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-outline-warning ms-2 push-opt-in';
        btn.innerHTML = '<i class="fas fa-bell"></i> ' + (isBg ? 'Известия' : 'Notifications');
        btn.title = isBg ? 'Разреши известия' : 'Enable notifications';
        btn.addEventListener('click', requestPermission);

        // Only insert if not already there
        if (!document.querySelector('.push-opt-in')) {
            navArea.appendChild(btn);
        }
    }

    function requestPermission() {
        Notification.requestPermission().then(function (permission) {
            if (permission === 'granted') {
                startPushPolling();
                // Remove opt-in button
                var btn = document.querySelector('.push-opt-in');
                if (btn) btn.remove();

                // Save subscription
                var csrfToken = document.body.dataset.csrfToken || '';
                var formData = new FormData();
                formData.append('endpoint', 'browser-notification-api');
                formData.append('p256dh', '');
                formData.append('auth', '');
                formData.append('csrf_token', csrfToken);
                fetch(resolvePath('api/push/subscribe'), { method: 'POST', body: formData })
                    .catch(function () {});
            }
        });
    }

    function startPushPolling() {
        doPushPoll();
        pushPollTimer = setInterval(doPushPoll, PUSH_POLL_INTERVAL);
    }

    function doPushPoll() {
        var url = resolvePath('api/push/poll') + '?_=' + Date.now();
        if (lastPushTimestamp) url += '&since=' + encodeURIComponent(lastPushTimestamp);

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                lastPushTimestamp = data.timestamp;

                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(function (n) {
                        showBrowserNotification(n);
                    });
                }
            })
            .catch(function () {});
    }

    function showBrowserNotification(notification) {
        if (Notification.permission !== 'granted') return;

        var title = 'FISHINGLORY';
        var body = '';

        switch (notification.type) {
            case 'like':
                body = (notification.from_username || '') + (isBg ? ' хареса публикацията ви' : ' liked your post');
                break;
            case 'comment':
                body = (notification.from_username || '') + (isBg ? ' коментира публикацията ви' : ' commented on your post');
                break;
            case 'friend_request':
                body = (notification.from_username || '') + (isBg ? ' ви изпрати заявка за приятелство' : ' sent you a friend request');
                break;
            case 'friend_accepted':
                body = (notification.from_username || '') + (isBg ? ' прие заявката ви' : ' accepted your friend request');
                break;
            case 'badge':
                body = notification.message || (isBg ? 'Спечелихте нова значка!' : 'You earned a new badge!');
                break;
            case 'message':
                body = (notification.from_username || '') + (isBg ? ' ви изпрати съобщение' : ' sent you a message');
                break;
            default:
                body = notification.message || (isBg ? 'Ново известие' : 'New notification');
        }

        try {
            var n = new Notification(title, {
                body: body,
                icon: resolvePath('fe/assets/img/logo_rounded.png'),
                tag: 'fishinglory-' + notification.id,
                requireInteraction: false,
            });

            n.onclick = function () {
                window.focus();
                n.close();
            };

            // Auto-close after 5 seconds
            setTimeout(function () { n.close(); }, 5000);
        } catch (e) {}
    }

    // ==================== INIT ====================
    document.addEventListener('DOMContentLoaded', init);

    // Cleanup
    window.addEventListener('beforeunload', function () {
        if (pushPollTimer) clearInterval(pushPollTimer);
    });
})();
