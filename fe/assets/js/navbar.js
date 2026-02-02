// Navbar JavaScript

// Auto-load notifications every 30 seconds
document.addEventListener('DOMContentLoaded', function() {
    if (typeof loadNotifications === 'function') {
        loadNotifications();
        setInterval(loadNotifications, 30000);
    }
});
