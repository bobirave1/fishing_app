// Shared utility functions for FISHINGLORY

/**
 * Resolve a root-relative path (e.g. 'be/posts/comment.php') to
 * the correct relative path based on current page location.
 */
function resolvePath(rootRelativePath) {
    const path = window.location.pathname;
    if (path.includes('/fe/pages/') || path.includes('/be/')) {
        return '../../' + rootRelativePath;
    }
    return rootRelativePath;
}

/**
 * Safely escape HTML entities to prevent XSS.
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(text || '')));
    return div.innerHTML;
}

/**
 * Format an ISO date string to a relative time string (e.g. "3h ago").
 */
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

    if (days > 0) return isBg ? 'преди ' + days + ' дни' : days + 'd ago';
    if (hours > 0) return isBg ? 'преди ' + hours + ' часа' : hours + 'h ago';
    if (minutes > 0) return isBg ? 'преди ' + minutes + ' мин' : minutes + 'm ago';
    return isBg ? 'току-що' : 'just now';
}

/**
 * Find all elements with data-iso-date and replace their text with relative time.
 */
function localizeIsoDates() {
    document.querySelectorAll('[data-iso-date]').forEach(function(el) {
        const iso = el.getAttribute('data-iso-date');
        if (!iso) return;
        el.textContent = formatDate(iso);
    });
}
