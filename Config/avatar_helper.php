<?php
/**
 * Avatar Helper Functions
 * Centralized avatar path management for consistent display across all pages
 */

/**
 * Safely get file version token for cache busting.
 */
function avatarAssetVersion($relativePath) {
    $absolutePath = __DIR__ . '/../' . ltrim($relativePath, '/');
    if (is_file($absolutePath)) {
        $mtime = filemtime($absolutePath);
        if ($mtime !== false) {
            return (string) $mtime;
        }
    }
    return null;
}

/**
 * Resolve a default avatar path that actually exists in the project.
 */
function resolveDefaultAvatarPath() {
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $candidates = [
        'fe/assets/img/default-avatar.png',
        'fe/assets/img/avatars/default.png',
        'fe/assets/img/logo_rounded.png',
    ];

    foreach ($candidates as $candidate) {
        if (is_file(__DIR__ . '/../' . $candidate)) {
            $resolved = $candidate;
            return $resolved;
        }
    }

    $resolved = 'fe/assets/img/logo.png';
    return $resolved;
}

/**
 * Get user avatar with proper path resolution
 * 
 * @param string|null $avatarUrl The avatar URL from database (can be NULL)
 * @param string|null $currentPath Current page path for relative path calculation
 * @return string The properly formatted avatar path
 */
function getUserAvatar($avatarUrl, $currentPath = null) {
    // Default avatar if none set
    $defaultAvatar = resolveDefaultAvatarPath();
    
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
                $version = avatarAssetVersion($defaultAvatar);
                if ($version !== null) {
                    $path .= '?v=' . $version;
                }
            }
            return $path;
        }
    } elseif (strpos($currentPath, '/be/') !== false) {
        // We're in be/ subdirectory, need to go up two levels
        if (strpos($avatar, 'fe/') === 0) {
            $path = '../../' . $avatar;
            // Add cache buster for default avatar
            if ($avatar === $defaultAvatar) {
                $version = avatarAssetVersion($defaultAvatar);
                if ($version !== null) {
                    $path .= '?v=' . $version;
                }
            }
            return $path;
        }
    }
    // Root level or already has correct path
    if ($avatar === $defaultAvatar) {
        $version = avatarAssetVersion($defaultAvatar);
        if ($version !== null) {
            return $avatar . '?v=' . $version;
        }
    }
    return $avatar;
}

/**
 * Get default avatar path
 * 
 * @return string Default avatar database path
 */
function getDefaultAvatarPath() {
    $path = resolveDefaultAvatarPath();
    $version = avatarAssetVersion($path);
    if ($version !== null) {
        return $path . '?v=' . $version;
    }
    return $path;
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
