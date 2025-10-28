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

// If user already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}



// Generate CSRF token for login form
$csrf_token = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>IT Asset Inventory - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>



    <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100">
        <div class="row w-100 justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="form-container">
                    <div class="text-center mb-4">
                        <div class="page-icon mx-auto mb-3">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h2 class="mb-2">IT Asset Inventory</h2>
                        <p class="text-muted">Secure Access Portal</p>
                    </div>

                    <form id="loginForm" action="login.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>" />

                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person-fill me-2"></i>Username
                            </label>
                            <input type="text" name="username" id="username" class="form-control" required autocomplete="username" placeholder="Enter your username" value="<?php if (isset($_COOKIE['remember_me'])) echo htmlspecialchars($_COOKIE['remember_me']); ?>" />
                            <div class="invalid-feedback">
                                Please enter a valid username.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock-fill me-2"></i>Password
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password" placeholder="Enter your password" />
                                <button type="button" class="btn btn-outline-secondary password-toggle" id="passwordToggle">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>

                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me" <?php if (isset($_COOKIE['remember_me'])) echo 'checked'; ?>>
                                <label class="form-check-label" for="rememberMe">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </button>
                    </form>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= $_SESSION['error']; ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="text-center">
                        <small class="text-muted">© 2025 IT Asset Inventory System</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Logging you in...</p>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
            <div class="particle1"></div>
            <div class="particle2"></div>
            <div class="particle3"></div>
            <div class="particle4"></div>
            <div class="particle5"></div>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');

        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            const icon = this.querySelector('i');
            icon.className = type === 'password' ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
        });

        // Form validation
        const loginForm = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');

        function validateForm() {
            let isValid = true;

            // Validate username
            if (usernameInput.value.trim().length < 3) {
                usernameInput.classList.add('is-invalid');
                usernameInput.classList.remove('is-valid');
                isValid = false;
            } else {
                usernameInput.classList.remove('is-invalid');
                usernameInput.classList.add('is-valid');
            }

            // Validate password
            if (passwordInput.value.length < 6) {
                passwordInput.classList.add('is-invalid');
                passwordInput.classList.remove('is-valid');
                isValid = false;
            } else {
                passwordInput.classList.remove('is-invalid');
                passwordInput.classList.add('is-valid');
            }

            return isValid;
        }

        // Real-time validation
        usernameInput.addEventListener('input', function() {
            if (this.value.trim().length >= 3) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });

        passwordInput.addEventListener('input', function() {
            if (this.value.length >= 6) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });

        // Form submission with validation
        loginForm.addEventListener('submit', function(event) {
            if (!validateForm()) {
                event.preventDefault();
                // Shake animation for invalid form
                const card = document.querySelector('.form-container');
                card.style.animation = 'shake 0.5s ease-in-out';
                setTimeout(() => {
                    card.style.animation = '';
                }, 500);
                return;
            }

            event.preventDefault();
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            // Force animation restart for typing effect
            const textElement = overlay.querySelector('p');
            textElement.style.animation = 'none';
            setTimeout(() => {
                textElement.style.animation = 'textGlow 3s ease-in-out infinite alternate, typeText 2.5s steps(16, end) 0.5s both';
            }, 10);

            // Simulate loading animation for 2.5 seconds before submitting the form
            setTimeout(() => {
                this.submit();
            }, 2500);
        });

        // Shake animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);



        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            usernameInput.focus();
        });

        // Enter key navigation
        usernameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                passwordInput.focus();
            }
        });

        passwordInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                loginForm.dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>
