<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/languages.php';
$current_lang = $_SESSION['language'] ?? 'en';
?>
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
      <i class="bi bi-pc-display-horizontal me-3"></i>
      <span>IT Asset Inventory</span>
    </a>
    <!-- Hamburger menu button for mobile -->
    <button class="hamburger-btn d-md-none me-3" id="hamburgerBtn" title="Toggle Sidebar">
      <i class="bi bi-list"></i>
    </button>

    <div class="navbar-actions d-flex align-items-center">
      <div class="notification-container me-4">
        <button type="button" class="notification-btn" id="notificationBtn" title="Notifications">
          <i class="bi bi-bell-fill"></i>
          <span class="notification-badge" id="notificationBadge" style="display: none;"></span>
        </button>
      </div>
      <a href="profile.php" class="user-avatar me-3" title="Go to Profile">
        <?php if (isset($_SESSION['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $_SESSION['profile_picture'])): ?>
          <img src="uploads/profiles/<?= htmlspecialchars($_SESSION['profile_picture']) ?>" alt="Profile Picture" class="navbar-profile-picture">
        <?php else: ?>
          <div class="navbar-profile-placeholder">
            <i class="bi bi-person-circle"></i>
          </div>
        <?php endif; ?>
      </a>
      <span class="welcome-text me-4 d-none d-md-block"><?= $lang[$current_lang]['welcome_back'] ?>, <?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']) ?>!</span>
      <a href="logout.php" class="logout-btn" id="logoutBtn">
        <i class="bi bi-box-arrow-right me-2"></i>
        <span><?= $lang[$current_lang]['logout'] ?></span>
      </a>
    </div>
  </div>
</nav>



<script>
  // Sidebar toggle functionality for mobile
  console.log('Script loaded'); // Debug log

  // Wait for DOM to be fully loaded
  document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded'); // Debug log
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('main.main-content');
    const navbar = document.querySelector('.navbar');

    console.log('Elements found:', { hamburgerBtn, sidebar, mainContent, navbar }); // Debug log
    console.log('Window width:', window.innerWidth); // Debug log
    console.log('Sidebar element:', sidebar); // Debug log
    console.log('Main content element:', mainContent); // Debug log

    // Hide sidebar by default on mobile devices
    if (window.innerWidth <= 768 && sidebar && mainContent && navbar) {
      console.log('Hiding sidebar on mobile'); // Debug log
      sidebar.style.display = 'none';
      mainContent.style.marginLeft = '0';
      mainContent.style.width = '100%';
      navbar.style.left = '0';
    }

    if (hamburgerBtn && sidebar && mainContent && navbar) {
      console.log('Adding click listener'); // Debug log
      hamburgerBtn.addEventListener('click', function() {
        console.log('Hamburger clicked'); // Debug log
        const isHidden = sidebar.style.display === 'none';
        if (isHidden) {
          sidebar.style.display = 'block';
          sidebar.style.position = 'fixed';
          sidebar.style.top = '73px';
          sidebar.style.left = '0';
          sidebar.style.width = '250px';
          sidebar.style.height = 'calc(100vh - 73px)';
          sidebar.style.zIndex = '1000';
          mainContent.style.marginLeft = '0';
          mainContent.style.width = '100%';
          navbar.style.left = '0';
          console.log('Showing sidebar');
        } else {
          sidebar.style.display = 'none';
          mainContent.style.marginLeft = '0';
          mainContent.style.width = '100%';
          navbar.style.left = '0';
          console.log('Hiding sidebar');
        }
        hamburgerBtn.classList.toggle('active');
      });
    } else {
      console.log('Missing elements for hamburger functionality'); // Debug log
    }
  });

  // Logout button - redirect directly to logout.php
  const logoutBtn = document.getElementById('logoutBtn');

  logoutBtn.addEventListener('click', function(event) {
    event.preventDefault();
    window.location.href = 'logout.php';
  });
</script>
