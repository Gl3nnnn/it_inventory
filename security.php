<?php
/**
 * Security Functions and Configurations
 * This file contains security-related functions and configurations
 */

// Security Headers
if (!function_exists('setSecurityHeaders')) {
    function setSecurityHeaders() {
        // Prevent clickjacking
        header("X-Frame-Options: DENY");

        // Prevent MIME type sniffing
        header("X-Content-Type-Options: nosniff");

        // Enable XSS protection
        header("X-XSS-Protection: 1; mode=block");

        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // Content Security Policy (basic)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net;");

        // HSTS (HTTP Strict Transport Security) - Enable only if using HTTPS
        // header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

        // Prevent caching of sensitive pages
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
}

// CSRF Token Generation and Validation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Rate Limiting for Login Attempts
function checkLoginAttempts($username) {
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $current_time = time();

    // Clean up old attempts
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($attempt) use ($current_time, $lockout_time) {
        return ($current_time - $attempt['time']) < $lockout_time;
    });

    // Check if user is currently locked out
    $user_attempts = array_filter($_SESSION['login_attempts'], function($attempt) use ($username) {
        return $attempt['username'] === $username;
    });

    if (count($user_attempts) >= $max_attempts) {
        $last_attempt = max(array_column($user_attempts, 'time'));
        if (($current_time - $last_attempt) < $lockout_time) {
            return false; // User is locked out
        }
    }

    return true; // User can attempt login
}

function recordLoginAttempt($username, $success = false) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $_SESSION['login_attempts'][] = [
        'username' => $username,
        'time' => time(),
        'success' => $success
    ];

    // Keep only last 10 attempts to prevent session bloat
    if (count($_SESSION['login_attempts']) > 10) {
        array_shift($_SESSION['login_attempts']);
    }
}

// Password Strength Validation
function validatePasswordStrength($password) {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }

    if (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }

    if (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number.";
    }

    if (!preg_match("/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/", $password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    return $errors;
}

// Input Sanitization
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }

    // Remove HTML tags and encode special characters
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // Remove potential SQL injection attempts
    $data = str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], '', $data);

    return trim($data);
}

// Secure Session Configuration
function secureSession() {
    // Only set session cookie params if session not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie
            'path' => '/',
            'domain' => '', // Leave empty for current domain
            'secure' => isset($_SERVER['HTTPS']), // Only send over HTTPS if available
            'httponly' => true, // Prevent JavaScript access
            'samesite' => 'Strict' // CSRF protection
        ]);
    }

    // Regenerate session ID to prevent session fixation
    if (!isset($_SESSION['regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = true;
    }

    // Set session timeout (30 minutes)
    $timeout = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Log Security Events
function logSecurityEvent($event, $details = '') {
    $log_file = __DIR__ . '/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['username'] ?? 'unknown';

    $log_entry = "[$timestamp] [$ip] [$user] $event: $details" . PHP_EOL;

    // Ensure log directory exists
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Validate File Upload (if needed in the future)
function validateFileUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Upload error occurred.'];
    }

    if (!in_array($file['type'], $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type.'];
    }

    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File too large.'];
    }

    // Check for malicious file content
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'File content does not match extension.'];
    }

    return ['valid' => true];
}

// Generate secure random string
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Check if request is from same origin (basic CSRF protection)
function isSameOrigin() {
    if (!isset($_SERVER['HTTP_ORIGIN'])) {
        return true; // Not a cross-origin request
    }

    $origin = parse_url($_SERVER['HTTP_ORIGIN']);
    $host = parse_url($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME']);

    return $origin['host'] === $host['host'];
}
?>
