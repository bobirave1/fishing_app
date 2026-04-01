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
    
    // Check file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Invalid file extension';
    }
    
    // Server-side MIME verification via finfo (not trusting client Content-Type)
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    if (!in_array($detectedMime, $allowedMimes)) {
        $errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed';
    }
    
    // Verify it's actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = 'File is not a valid image';
    }
    
    return $errors;
}

// Media Upload Validation (Images + Videos)
function validateMediaUpload($file, $maxSize = 20971520) { // 20MB default
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'File is too large (max 20MB)';
    }
    
    // Check file extension
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'avi', 'mov'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Invalid file extension';
    }
    
    // Server-side MIME verification via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $videoMimes = ['video/mp4', 'video/webm', 'video/x-msvideo', 'video/quicktime', 'application/octet-stream'];
    
    if (in_array($extension, $imageExts) && !in_array($detectedMime, $imageMimes)) {
        $errors[] = 'Invalid image file type';
    } elseif (!in_array($extension, $imageExts) && !in_array($detectedMime, $videoMimes)) {
        $errors[] = 'Invalid video file type';
    }
    
    return $errors;
}

// Secure file upload: validates, generates random filename, and moves file
function secureUploadFile(array $file, string $targetDir, string $type = 'media'): array {
    $errors = ($type === 'image')
        ? validateImageUpload($file)
        : validateMediaUpload($file);
    
    if (!empty($errors)) {
        return ['success' => false, 'error' => implode(', ', $errors)];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    $targetPath = rtrim($targetDir, '/\\') . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    return ['success' => true, 'filename' => $filename];
}
// Security Headers
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Allow geolocation for weather widget, block microphone and camera
    header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');
    // Updated CSP - strict policy (no unsafe-eval needed)
    if (!headers_sent()) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com; img-src 'self' data: https: http:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self' https://nominatim.openstreetmap.org https://api.openweathermap.org https://cdn.jsdelivr.net;");
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
