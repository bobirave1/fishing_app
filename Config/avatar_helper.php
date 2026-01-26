<?php
/**
 * Avatar Helper Functions
 * Centralized avatar path management for consistent display across all pages
 */

/**
 * Get user avatar with proper path resolution
 * 
 * @param string|null $avatarUrl The avatar URL from database (can be NULL)
 * @param string|null $currentPath Current page path for relative path calculation
 * @return string The properly formatted avatar path
 */
function getUserAvatar($avatarUrl, $currentPath = null) {
    // Default avatar if none set
    $defaultAvatar = 'fe/assets/img/default-avatar.png';
    
    // Use default if no avatar
    $avatar = $avatarUrl ?? $defaultAvatar;
    
    // If no current path provided, detect it
    if ($currentPath === null) {
        $currentPath = $_SERVER['PHP_SELF'] ?? '';
    }
    
    // Determine path prefix based on location
    if (strpos($currentPath, '/fe/pages/') !== false) {
        // We're in fe/pages/, need to go up two levels
        if (strpos($avatar, 'fe/') === 0) {
            $path = '../../' . $avatar;
            // Add cache buster for default avatar
            if ($avatar === $defaultAvatar) {
                $path .= '?v=' . filemtime(__DIR__ . '/../' . $defaultAvatar);
            }
            return $path;
        }
    } elseif (strpos($currentPath, '/be/') !== false) {
        // We're in be/ subdirectory, need to go up two levels
        if (strpos($avatar, 'fe/') === 0) {
            $path = '../../' . $avatar;
            // Add cache buster for default avatar
            if ($avatar === $defaultAvatar) {
                $path .= '?v=' . filemtime(__DIR__ . '/../' . $defaultAvatar);
            }
            return $path;
        }
    }
    // Root level or already has correct path
    if ($avatar === $defaultAvatar) {
        return $avatar . '?v=' . filemtime(__DIR__ . '/../' . $defaultAvatar);
    }
    return $avatar;
}

/**
 * Get default avatar path
 * 
 * @return string Default avatar database path
 */
function getDefaultAvatarPath() {
    $path = 'fe/assets/img/default-avatar.png';
    // Add cache buster
    return $path . '?v=' . time();
}

/**
 * Validate and sanitize avatar URL for display
 * 
 * @param string|null $avatarUrl The avatar URL to validate
 * @return string Safe HTML-ready avatar path
 */
function sanitizeAvatarUrl($avatarUrl) {
    $avatar = getUserAvatar($avatarUrl);
    return htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8');
}
