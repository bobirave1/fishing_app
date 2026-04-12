<?php
// CSRF Protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getCsrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function assetVersion(string $relativePath): string {
    $relativePath = ltrim($relativePath, "/\\");
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relativePath;
    if (is_file($fullPath)) {
        return (string) filemtime($fullPath);
    }
    return (string) time();
}

// Rate Limiting
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 900) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $limitKey = $key . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    // Clean old entries
    if (isset($_SESSION['rate_limit'][$limitKey])) {
        $_SESSION['rate_limit'][$limitKey] = array_filter(
            $_SESSION['rate_limit'][$limitKey],
            fn($timestamp) => ($now - $timestamp) < $timeWindow
        );
    } else {
        $_SESSION['rate_limit'][$limitKey] = [];
    }
    
    // Check limit
    if (count($_SESSION['rate_limit'][$limitKey]) >= $maxAttempts) {
        return false;
    }
    
    // Add current attempt
    $_SESSION['rate_limit'][$limitKey][] = $now;
    return true;
}

// Input Validation
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    // Minimum 8 characters, at least one letter and one number
    $length = strlen($password);
    $hasLetter = preg_match('/[A-Za-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    
    return $length >= 8 && $hasLetter && $hasNumber;
}

function validateUsername($username) {
    // 3-20 characters, alphanumeric and underscore only
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function sanitizeInput($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

// File Upload Validation
function validateImageUpload($file, $maxSize = 5242880) { // 5MB default
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'File is too large (max 5MB)';
    }
    
    // Check MIME type
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedMimes)) {
        $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed';
    }
    
    // Verify it's actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = 'File is not a valid image';
    }
    
    // Check file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Invalid file extension';
    }
    
    return $errors;
}

// Media Upload Validation (Images + Videos)
function validateMediaUpload($file, $maxSize = 20971520) { // 20MB default
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error (code: ' . $file['error'] . ')';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'File is too large (max 20MB)';
    }
    
    // Check file extension first
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'avi', 'mov'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Invalid file extension';
        return $errors;
    }
    
    // Check MIME type (browser-reported + finfo)
    $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/avi', 'video/quicktime',
        'video/x-msvideo', 'video/x-matroska', 'application/octet-stream'
    ];
    
    $detectedMime = $file['type'];
    if (function_exists('finfo_open') && !empty($file['tmp_name'])) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    }
    
    $videoExtensions = ['mp4', 'webm', 'avi', 'mov'];
    $isVideoByExt = in_array($extension, $videoExtensions);
    
    // For video files, allow if extension is valid (finfo may misdetect video types)
    if (!$isVideoByExt && !in_array($detectedMime, $allowedMimes) && !in_array($file['type'], $allowedMimes)) {
        $errors[] = 'Invalid file type. Only images (JPG, PNG, GIF, WebP) and videos (MP4, WebM, AVI, MOV) are allowed';
    }
    
    return $errors;
}
// Security Headers
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Allow geolocation for weather widget, block microphone and camera
    header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
    // Updated CSP - allow unsafe-eval for development and Bootstrap compatibility
    if (!headers_sent()) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com; img-src 'self' data: blob: https: http:; media-src 'self' blob:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self' https://nominatim.openstreetmap.org https://api.openweathermap.org https://cdn.jsdelivr.net;");
    }
}

// Session Security
function secureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        );

        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.use_strict_mode', 1);
        // Lax works well for typical web navigation flows while still mitigating CSRF in many cases.
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}
