<?php
// Security: Disable error reporting in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include security functions
require_once __DIR__ . '/security.php';

// Set security headers
setSecurityHeaders();

// Secure session configuration
secureSession();

require __DIR__ . '/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        logSecurityEvent('CSRF Token Validation Failed', 'Login attempt with invalid CSRF token');
        $_SESSION['error'] = "❌ Security validation failed. Please try again.";
        header("Location: index.php");
        exit();
    }

    // Sanitize inputs
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password as it might contain special chars

    // Check rate limiting
    if (!checkLoginAttempts($username)) {
        logSecurityEvent('Rate Limit Exceeded', "Login attempts exceeded for user: $username");
        $_SESSION['error'] = "❌ Too many login attempts. Please try again later.";
        header("Location: index.php");
        exit();
    }

    // Validate input length
    if (strlen($username) < 3 || strlen($username) > 50) {
        $_SESSION['error'] = "❌ Invalid username length.";
        recordLoginAttempt($username, false);
        header("Location: index.php");
        exit();
    }

    if (strlen($password) < 1) {
        $_SESSION['error'] = "❌ Password is required.";
        recordLoginAttempt($username, false);
        header("Location: index.php");
        exit();
    }

    // Prepare statement with error handling
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    if (!$stmt) {
        logSecurityEvent('Database Error', 'Failed to prepare login statement: ' . $conn->error);
        $_SESSION['error'] = "❌ System error. Please try again later.";
        header("Location: index.php");
        exit();
    }

    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        logSecurityEvent('Database Error', 'Failed to execute login statement: ' . $stmt->error);
        $_SESSION['error'] = "❌ System error. Please try again later.";
        $stmt->close();
        header("Location: index.php");
        exit();
    }

    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $user, $hashed_pass, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_pass)) {
            // Successful login
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $user;
            $_SESSION['role'] = $role;
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

            // Load additional user data from database
            $user_stmt = $conn->prepare("SELECT display_name, profile_picture FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $id);
            $user_stmt->execute();
            $user_stmt->bind_result($display_name, $profile_picture);
            $user_stmt->fetch();
            $user_stmt->close();

            // Set display name in session (fallback to username if empty)
            $_SESSION['display_name'] = $display_name ?: $user;

            // Set profile picture in session if exists
            if (!empty($profile_picture)) {
                $_SESSION['profile_picture'] = $profile_picture;
            }

            // Handle "Remember me" functionality
            if (!empty($_POST['remember_me'])) {
                // Set a cookie valid for 30 days
                setcookie('remember_me', $user, time() + (30 * 24 * 60 * 60), "/", "", isset($_SERVER['HTTPS']), true);
            } else {
                // Clear the cookie if unchecked
                if (isset($_COOKIE['remember_me'])) {
                    setcookie('remember_me', '', time() - 3600, "/", "", isset($_SERVER['HTTPS']), true);
                }
            }

            // Record successful login attempt
            recordLoginAttempt($username, true);

            // Log successful login
            logSecurityEvent('Successful Login', "User: $username, Role: $role");

            // Regenerate session ID after successful login
            session_regenerate_id(true);

            header("Location: dashboard.php");
            exit();
        } else {
            // Failed login - wrong password
            recordLoginAttempt($username, false);
            logSecurityEvent('Failed Login Attempt', "Wrong password for user: $username");
            $_SESSION['error'] = "❌ Invalid password.";
            header("Location: index.php");
            exit();
        }
    } else {
        // Failed login - user not found
        recordLoginAttempt($username, false);
        logSecurityEvent('Failed Login Attempt', "User not found: $username");
        $_SESSION['error'] = "❌ Invalid username or password.";
        header("Location: index.php");
        exit();
    }

    $stmt->close();
}
