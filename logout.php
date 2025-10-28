<?php
session_start();
session_unset();
session_destroy();

// Do not clear the "remember me" cookie on logout to keep it remembered
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Logging Out - IT Asset Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>


    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Logging out...</p>
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
        // Show loading overlay immediately
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.add('active');

            // Force animation restart for typing effect
            const textElement = overlay.querySelector('p');
            textElement.style.animation = 'none';
            setTimeout(() => {
                textElement.style.animation = 'textGlow 3s ease-in-out infinite alternate, typeText 2.5s steps(16, end) 0.5s both';
            }, 10);

            // Redirect after animation completes
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2500);
        });
    </script>
</body>
</html>
