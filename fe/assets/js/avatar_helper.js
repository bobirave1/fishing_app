/**
 * Avatar Helper Functions for JavaScript
 * Client-side avatar path management
 */

/**
 * Get theme-appropriate default avatar path
 */
function getDefaultAvatarForTheme() {
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    return isDark
        ? 'fe/assets/img/avatars/default_avatar_dark.jpg'
        : 'fe/assets/img/avatars/default_avatar_light.jpg';
}

function getAvatarUrl(avatarUrl) {
    const defaultAvatar = getDefaultAvatarForTheme();
    const avatar = avatarUrl || defaultAvatar;
    
    // Use resolvePath if available, otherwise fallback to manual detection
    let finalPath = avatar;
    if (avatar.startsWith('fe/')) {
        finalPath = (typeof resolvePath === 'function') ? resolvePath(avatar) : avatar;
    }
    
    // Add cache buster for default avatar
    if (avatar === defaultAvatar) {
        finalPath += '?v=' + Date.now();
    }
    
    return finalPath;
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
    return `<img src="${src}" class="rounded-circle ${extraClasses}" width="${size}" height="${size}" style="object-fit: cover;">`;
}
