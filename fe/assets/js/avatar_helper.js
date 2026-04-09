/**
 * Avatar Helper Functions for JavaScript
 * Client-side avatar path management
 */

/**
 * Get theme-appropriate default avatar path
 */
function getDefaultAvatarForTheme() {
    var isDark = document.body.getAttribute('data-theme') === 'dark';
    return isDark
        ? 'fe/assets/img/avatars/dark_avatar.png'
        : 'fe/assets/img/avatars/light_avatar.png';
}

function getAvatarUrl(avatarUrl) {
    const defaultAvatar = getDefaultAvatarForTheme();
    // Treat null, undefined, empty string, and "null" as missing
    const avatar = (avatarUrl && avatarUrl !== 'null') ? avatarUrl : defaultAvatar;
    
    // Detect if we're in a subdirectory
    const path = window.location.pathname;
    const isInFePages = path.includes('/fe/pages/');
    const isInBe = path.includes('/be/');
    
    // If in subdirectory and avatar starts with fe/, add ../../ prefix
    let finalPath = avatar;
    if ((isInFePages || isInBe) && avatar.startsWith('fe/')) {
        finalPath = '../../' + avatar;
    }
    
    // Add cache buster for default avatar
    if (avatar === defaultAvatar) {
        finalPath += '?v=' + Date.now();
    }
    
    // Handle broken images by falling back to default
    return finalPath;
}

/**
 * Handle broken avatar images by replacing with default
 */
function handleAvatarError(img) {
    const defaultSrc = getAvatarUrl(null);
    if (img.src !== defaultSrc) {
        img.src = defaultSrc;
    }
}

/**
 * Create avatar image HTML
 * @param {string|null} avatarUrl - The avatar URL from server
 * @param {number} size - Size in pixels
 * @param {string} extraClasses - Additional CSS classes
 * @returns {string} - HTML string for avatar image
 */
function createAvatarHtml(avatarUrl, size = 32, extraClasses = '') {
    const src = getAvatarUrl(avatarUrl);
    return `<img src="${src}" class="rounded-circle ${extraClasses}" width="${size}" height="${size}" style="object-fit: cover;" onerror="handleAvatarError(this)">`;
}
