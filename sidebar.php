<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/languages.php';
$current_lang = $_SESSION['language'] ?? 'en';
$currentPage = basename($_SERVER['PHP_SELF']);
$assetPages = ['assets.php', 'add_asset.php', 'edit_asset.php', 'generate_stickers.php', 'import_assets.php'];
$userPages = ['users.php', 'add_user.php', 'edit_user.php'];
?>
<div class="sidebar">
    <nav class="nav flex-column">
        <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> <span><?= $lang[$current_lang]['dashboard'] ?></span>
        </a>
        
        <div class="nav-item dropdown assets-dropdown">
            <div class="assets-main-link-container">
                <a class="nav-link assets-main-link <?= in_array($currentPage, $assetPages) ? 'active' : '' ?>" href="assets.php">
                    <i class="bi bi-device-ssd"></i> <span><?= $lang[$current_lang]['assets'] ?></span>
                </a>
                <button class="dropdown-toggle dropdown-toggle-btn" id="allAssetsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-chevron-down dropdown-arrow"></i>
                </button>
            </div>
            <ul class="dropdown-menu assets-dropdown-menu" aria-labelledby="allAssetsDropdown">
                <li class="dropdown-header"><i class="bi bi-filter-circle"></i> Filter by Status</li>
                <li><a class="dropdown-item status-all" href="assets.php"><i class="bi bi-grid"></i><span class="status-text">All Assets</span><span class="status-badge all">View All</span></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item status-storage" href="assets.php?status=In Storage"><i class="bi bi-archive"></i><span class="status-text">In Storage</span><span class="status-badge storage">Available</span></a></li>
                <li><a class="dropdown-item status-assigned" href="assets.php?status=Assigned"><i class="bi bi-person-check"></i><span class="status-text">Assigned</span><span class="status-badge assigned">Active</span></a></li>
                <li><a class="dropdown-item status-repair" href="assets.php?status=Under Repair"><i class="bi bi-tools"></i><span class="status-text">Under Repair</span><span class="status-badge repair">Maintenance</span></a></li>
                <li><a class="dropdown-item status-disposed" href="assets.php?status=Disposed"><i class="bi bi-trash"></i><span class="status-text">Disposed</span><span class="status-badge disposed">Removed</span></a></li>
            </ul>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.querySelector('.assets-dropdown');
            const toggle = dropdown.querySelector('.dropdown-toggle-btn');
            const menu = dropdown.querySelector('.dropdown-menu');

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const isOpen = menu.classList.contains('show');
                if (isOpen) {
                    menu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                    dropdown.classList.remove('show');
                } else {
                    menu.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                    dropdown.classList.add('show');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    menu.classList.remove('show');
                    toggle.setAttribute('aria-expanded', 'false');
                    dropdown.classList.remove('show');
                }
            });
        });
        </script>

        <a class="nav-link <?= $currentPage === 'maintenance.php' ? 'active' : '' ?>" href="maintenance.php">
            <i class="bi bi-tools"></i> <span><?= $lang[$current_lang]['maintenance'] ?></span>
        </a>

        <a class="nav-link <?= $currentPage === 'add_asset.php' ? 'active' : '' ?>" href="add_asset.php">
            <i class="bi bi-plus-circle"></i> <span><?= $lang[$current_lang]['add_new_asset'] ?></span>
        </a>

        <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php">
            <i class="bi bi-graph-up"></i> <span><?= $lang[$current_lang]['reports'] ?></span>
        </a>

        <a class="nav-link <?= $currentPage === 'qr_scanner.php' ? 'active' : '' ?>" href="qr_scanner.php">
            <i class="bi bi-qr-code-scan"></i> <span>QR Scanner</span>
        </a>

        <a class="nav-link <?= in_array($currentPage, $userPages) ? 'active' : '' ?>" href="users.php">
            <i class="bi bi-people"></i> <span><?= $lang[$current_lang]['users'] ?></span>
        </a>

        <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="profile.php">
            <i class="bi bi-person-circle"></i> <span><?= $lang[$current_lang]['profile'] ?></span>
        </a>
    </nav>
</div>
