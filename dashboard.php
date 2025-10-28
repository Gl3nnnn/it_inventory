<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';
require __DIR__ . '/languages.php';
$current_lang = $_SESSION['language'] ?? 'en';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Restrict access to admin and staff only
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}

// Include notification generation function
require __DIR__ . '/generate_notifications.php';

// Generate notifications for the current user when dashboard loads
generateNotifications($conn, $_SESSION['user_id']);

// Query counts for stats
$totalAssets = $conn->query("SELECT COUNT(*) AS total FROM assets")->fetch_assoc()['total'];
$inStorage   = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='In Storage'")->fetch_assoc()['total'];
$assigned    = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='Assigned'")->fetch_assoc()['total'];
$repair      = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='Under Repair'")->fetch_assoc()['total'];
$disposed    = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='Disposed'")->fetch_assoc()['total'];

try {
    $recentActivities = $conn->query("
        SELECT action, asset_name, timestamp
        FROM activities
        ORDER BY timestamp DESC
LIMIT 4
    ")->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    // Table does not exist or other error, fallback to empty array
    $recentActivities = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="3.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - IT Asset Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">
    
</head>
<body>

  <?php require __DIR__ . '/navbar.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <?php require __DIR__ . '/sidebar.php'; ?>

      <!-- Main content -->
      <main class="main-content">
        <div class="dashboard-header d-flex justify-content-between align-items-center">
          <h2 class="mb-0"><?= $lang[$current_lang]['dashboard_overview'] ?></h2>
          <span class="text-muted"><?= date('l, F j, Y') ?></span>
        </div>

        <!-- Welcome banner -->
        <div class="user-welcome">
          <div class="welcome-content">
            <h4><?= $lang[$current_lang]['welcome_back'] ?>, <?= htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']) ?>! 👋</h4>
            <p class="mb-0">Here's what's happening with your IT assets today.</p>
          </div>
        </div>

        <!-- Stats cards -->
        <div class="stats-grid">
          <div class="stat-card-wrapper">
            <a href="assets.php" class="text-decoration-none">
              <div class="stat-card card-total">
                <div class="stat-card-body">
                  <div class="stat-icon">
                    <i class="bi bi-device-ssd-fill"></i>
                  </div>
                  <div class="stat-content">
                    <h3 class="stat-number"><?= $totalAssets ?></h3>
                    <p class="stat-title"><?= $lang[$current_lang]['total_assets'] ?></p>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <div class="stat-card-wrapper">
            <a href="assets.php?status=In Storage" class="text-decoration-none">
              <div class="stat-card card-storage">
                <div class="stat-card-body">
                  <div class="stat-icon">
                    <i class="bi bi-archive-fill"></i>
                  </div>
                  <div class="stat-content">
                    <h3 class="stat-number"><?= $inStorage ?></h3>
                    <p class="stat-title"><?= $lang[$current_lang]['in_storage'] ?></p>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <div class="stat-card-wrapper">
            <a href="assets.php?status=Assigned" class="text-decoration-none">
              <div class="stat-card card-assigned">
                <div class="stat-card-body">
                  <div class="stat-icon">
                    <i class="bi bi-person-check-fill"></i>
                  </div>
                  <div class="stat-content">
                    <h3 class="stat-number"><?= $assigned ?></h3>
                    <p class="stat-title"><?= $lang[$current_lang]['assigned'] ?></p>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <div class="stat-card-wrapper">
            <a href="assets.php?status=Under Repair" class="text-decoration-none">
              <div class="stat-card card-repair">
                <div class="stat-card-body">
                  <div class="stat-icon">
                    <i class="bi bi-tools"></i>
                  </div>
                  <div class="stat-content">
                    <h3 class="stat-number"><?= $repair ?></h3>
                    <p class="stat-title"><?= $lang[$current_lang]['under_repair'] ?></p>
                  </div>
                </div>
              </div>
            </a>
          </div>

          <div class="stat-card-wrapper">
            <a href="assets.php?status=Disposed" class="text-decoration-none">
              <div class="stat-card card-disposed">
                <div class="stat-card-body">
                  <div class="stat-icon">
                    <i class="bi bi-trash-fill"></i>
                  </div>
                  <div class="stat-content">
                    <h3 class="stat-number"><?= $disposed ?></h3>
                    <p class="stat-title"><?= $lang[$current_lang]['disposed'] ?></p>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
          <!-- Recent Activity Section -->
          <div class="dashboard-card">
            <div class="card-content">
              <h5 class="card-title"><?= $lang[$current_lang]['recent_activities'] ?></h5>
              <div class="activity-list">
                <?php if (!empty($recentActivities)): ?>
                  <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                      <div class="activity-header">
                        <strong class="activity-action"><?= htmlspecialchars($activity['action']) ?></strong>
                        <small class="activity-time"><?= date('M j, g:i a', strtotime($activity['timestamp'])) ?></small>
                      </div>
                      <p class="activity-name"><?= htmlspecialchars($activity['asset_name']) ?></p>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <!-- Sample activities for demonstration -->
                  <div class="activity-item">
                    <div class="activity-header">
                      <strong class="activity-action">Asset Added</strong>
                      <small class="activity-time"><?= date('M j, g:i a') ?></small>
                    </div>
                    <p class="activity-name">MacBook Pro 16"</p>
                  </div>
                  <div class="activity-item">
                    <div class="activity-header">
                      <strong class="activity-action">Status Changed</strong>
                      <small class="activity-time"><?= date('M j, g:i a', time() - 3600) ?></small>
                    </div>
                    <p class="activity-name">Dell Monitor 27" to Assigned</p>
                  </div>
                  <div class="activity-item">
                    <div class="activity-header">
                      <strong class="activity-action">Asset Updated</strong>
                      <small class="activity-time"><?= date('M j, g:i a', time() - 7200) ?></small>
                    </div>
                    <p class="activity-name">iPad Pro 12.9"</p>
                  </div>
                  <div class="activity-item">
                    <div class="activity-header">
                      <strong class="activity-action">Maintenance Scheduled</strong>
                      <small class="activity-time"><?= date('M j, g:i a', time() - 10800) ?></small>
                    </div>
                    <p class="activity-name">Server Rack Unit 5</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Quick Actions Section -->
          <div class="dashboard-card">
            <div class="card-content">
              <h5 class="card-title"><?= $lang[$current_lang]['quick_actions'] ?></h5>
              <div class="quick-actions-grid">
                <a href="add_asset.php" class="action-btn primary">
                  <i class="bi bi-plus-circle"></i>
                  <span><?= $lang[$current_lang]['add_new_asset'] ?></span>
                </a>
                <a href="assets.php" class="action-btn secondary">
                  <i class="bi bi-search"></i>
                  <span><?= $lang[$current_lang]['browse_assets'] ?></span>
                </a>
                <a href="reports.php" class="action-btn info">
                  <i class="bi bi-graph-up"></i>
                  <span><?= $lang[$current_lang]['generate_reports'] ?></span>
                </a>
                <a href="users.php" class="action-btn gray">
                  <i class="bi bi-people"></i>
                  <span><?= $lang[$current_lang]['manage_users'] ?></span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var dropdownElement = document.getElementById('allAssetsDropdown');
    if (dropdownElement) {
      // Ensure a Bootstrap Dropdown instance exists
      var bsDropdown = bootstrap.Dropdown.getInstance(dropdownElement);
      if (!bsDropdown) {
        bsDropdown = new bootstrap.Dropdown(dropdownElement);
      }

      // Add a click listener to show the dropdown
      dropdownElement.addEventListener('click', function(event) {
        event.preventDefault(); // Prevent default navigation
        bsDropdown.toggle(); // Toggle the dropdown visibility
      });
    }
  });
</script>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>

