<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Restrict access to admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: assets.php");
    exit();
}

// Get date range from URL parameters or default to last 30 days
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Query counts for stats
$totalAssets = $conn->query("SELECT COUNT(*) AS total FROM assets")->fetch_assoc()['total'];
$inStorage   = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='In Storage'")->fetch_assoc()['total'];
$assigned    = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='Assigned'")->fetch_assoc()['total'];
$repair      = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='Under Repair'")->fetch_assoc()['total'];
$disposed    = $conn->query("SELECT COUNT(*) AS total FROM assets WHERE status='Disposed'")->fetch_assoc()['total'];

// Get assets by category with date filter
$categoryQuery = "SELECT category, COUNT(*) as count FROM assets WHERE DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY category ORDER BY count DESC LIMIT 10";
$categoryStats = $conn->query($categoryQuery)->fetch_all(MYSQLI_ASSOC);

// Get assets by location with date filter
$locationQuery = "SELECT location, COUNT(*) as count FROM assets WHERE location IS NOT NULL AND location != '' AND DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo' GROUP BY location ORDER BY count DESC LIMIT 10";
$locationStats = $conn->query($locationQuery)->fetch_all(MYSQLI_ASSOC);

// Get recent additions within date range
$recentAdditions = $conn->query("SELECT COUNT(*) as count FROM assets WHERE DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'")->fetch_assoc()['count'];

// Get asset aging analysis (assets older than 3 years)
$agingAssets = $conn->query("SELECT COUNT(*) as count FROM assets WHERE acquisition_date IS NOT NULL AND DATEDIFF(CURDATE(), acquisition_date) > 1095")->fetch_assoc()['count'];

// Get assets nearing end of life (within 6 months of lifespan)
$endOfLifeAssets = $conn->query("
    SELECT COUNT(*) as count FROM assets
    WHERE item_lifespan IS NOT NULL
    AND acquisition_date IS NOT NULL
    AND DATEDIFF(CURDATE(), acquisition_date) >= (item_lifespan * 365 - 180)
    AND DATEDIFF(CURDATE(), acquisition_date) <= (item_lifespan * 365)
")->fetch_assoc()['count'];

// Get disposal method statistics
$disposalStats = $conn->query("SELECT disposal_method, COUNT(*) as count FROM assets WHERE disposal_method IS NOT NULL AND disposal_method != '' GROUP BY disposal_method ORDER BY count DESC")->fetch_all(MYSQLI_ASSOC);

// Get monthly asset additions for the last 12 months
$monthlyStats = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM assets
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetch_all(MYSQLI_ASSOC);

// Get asset status distribution
$statusDistribution = $conn->query("SELECT status, COUNT(*) as count FROM assets GROUP BY status")->fetch_all(MYSQLI_ASSOC);

// Get top asset categories by value (if value column exists, otherwise by count)
$valueQuery = "SELECT category, COUNT(*) as count FROM assets GROUP BY category ORDER BY count DESC LIMIT 5";
$topCategories = $conn->query($valueQuery)->fetch_all(MYSQLI_ASSOC);

// Get assets requiring attention (under repair + disposed)
$attentionAssets = $repair + $disposed;

// Calculate percentages
$storagePercent = $totalAssets > 0 ? round(($inStorage / $totalAssets) * 100, 1) : 0;
$assignedPercent = $totalAssets > 0 ? round(($assigned / $totalAssets) * 100, 1) : 0;
$repairPercent = $totalAssets > 0 ? round(($repair / $totalAssets) * 100, 1) : 0;
$disposedPercent = $totalAssets > 0 ? round(($disposed / $totalAssets) * 100, 1) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="3.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Advanced Reports - IT Asset Inventory</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <?php require __DIR__ . '/navbar.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <?php require __DIR__ . '/sidebar.php'; ?>

      <!-- Main content -->
      <main class="main-content col-md-9 col-lg-10">
        <div class="page-header">
          <div class="page-icon">
            <i class="bi bi-bar-chart-line-fill"></i>
          </div>
          <div class="d-flex justify-content-between align-items-center w-100">
            <div>
              <h2>Reports & Analytics</h2>
              <p class="page-subtitle">Comprehensive insights and data visualization for your IT assets</p>
            </div>
            <span class="current-date text-muted ms-3"><?= date('l, F j, Y') ?></span>
          </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
          <div class="stat-card-wrapper">
            <div class="stat-card card-total">
              <div class="stat-card-body">
                <div class="stat-icon">
                  <i class="bi bi-device-ssd-fill"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-number"><?= $totalAssets ?></div>
                  <div class="stat-title">Total Assets</div>
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card-wrapper">
            <div class="stat-card card-storage">
              <div class="stat-card-body">
                <div class="stat-icon">
                  <i class="bi bi-archive-fill"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-number"><?= $inStorage ?></div>
                  <div class="stat-title">In Storage</div>
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card-wrapper">
            <div class="stat-card card-assigned">
              <div class="stat-card-body">
                <div class="stat-icon">
                  <i class="bi bi-person-check-fill"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-number"><?= $assigned ?></div>
                  <div class="stat-title">Assigned</div>
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card-wrapper">
            <div class="stat-card card-repair">
              <div class="stat-card-body">
                <div class="stat-icon">
                  <i class="bi bi-tools"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-number"><?= $repair ?></div>
                  <div class="stat-title">Under Repair</div>
                </div>
              </div>
            </div>
          </div>

          <div class="stat-card-wrapper">
            <div class="stat-card card-disposed">
              <div class="stat-card-body">
                <div class="stat-icon">
                  <i class="bi bi-trash-fill"></i>
                </div>
                <div class="stat-content">
                  <div class="stat-number"><?= $disposed ?></div>
                  <div class="stat-title">Disposed</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Interactive Charts Section -->
        <div class="dashboard-grid">
          <!-- Asset Status Distribution Chart -->
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-title">
                <i class="bi bi-pie-chart"></i>
                <span>Asset Status Distribution</span>
              </div>
              <canvas id="statusChart" width="400" height="300"></canvas>
            </div>
          </div>

          <!-- Assets by Category Chart -->
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-title">
                <i class="bi bi-bar-chart"></i>
                <span>Assets by Category</span>
              </div>
              <canvas id="categoryChart" width="400" height="300"></canvas>
            </div>
          </div>

          <!-- Monthly Asset Additions Chart -->
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-title">
                <i class="bi bi-graph-up"></i>
                <span>Monthly Asset Additions</span>
              </div>
              <canvas id="monthlyChart" width="400" height="300"></canvas>
            </div>
          </div>

          <!-- Assets by Location Chart -->
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-title">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Assets by Location</span>
              </div>
              <canvas id="locationChart" width="400" height="300"></canvas>
            </div>
          </div>
        </div>

        <!-- Custom Report Builder -->
        <div class="dashboard-card">
          <div class="card-content">
            <div class="card-title">
              <i class="bi bi-wrench"></i>
              <span>Custom Report Builder</span>
            </div>
            <form id="customReportForm" class="row g-3">
              <div class="col-md-3">
                <label for="reportDateFrom" class="form-label">Date From</label>
                <input type="date" class="form-control" id="reportDateFrom" name="date_from" value="<?= $dateFrom ?>">
              </div>
              <div class="col-md-3">
                <label for="reportDateTo" class="form-label">Date To</label>
                <input type="date" class="form-control" id="reportDateTo" name="date_to" value="<?= $dateTo ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Chart Type</label>
                <select class="form-select" id="chartType">
                  <option value="pie">Pie Chart</option>
                  <option value="bar">Bar Chart</option>
                  <option value="line">Line Chart</option>
                  <option value="doughnut">Doughnut Chart</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Metric</label>
                <select class="form-select" id="reportMetric">
                  <option value="status">By Status</option>
                  <option value="category">By Category</option>
                  <option value="location">By Location</option>
                  <option value="monthly">Monthly Trend</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Filters</label>
                <div class="row g-2">
                  <div class="col-6">
                    <select class="form-select" id="filterCategory">
                      <option value="">All Categories</option>
                      <?php
                      $categories = $conn->query("SELECT DISTINCT category FROM assets ORDER BY category")->fetch_all(MYSQLI_ASSOC);
                      foreach ($categories as $cat) {
                        echo "<option value='" . htmlspecialchars($cat['category']) . "'>" . htmlspecialchars($cat['category']) . "</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-6">
                    <select class="form-select" id="filterStatus">
                      <option value="">All Statuses</option>
                      <option value="In Storage">In Storage</option>
                      <option value="Assigned">Assigned</option>
                      <option value="Under Repair">Under Repair</option>
                      <option value="Disposed">Disposed</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-6 d-flex align-items-end gap-3">
                <button type="button" class="btn btn-primary" id="generateCustomReport">
                  <i class="bi bi-graph-up me-2"></i>Generate Report
                </button>
                <button type="button" class="btn btn-success" id="exportPDF">
                  <i class="bi bi-file-earmark-pdf me-2"></i>Export as PDF
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Custom Report Display Area -->
        <div class="dashboard-card" id="customReportArea" style="display: none;">
          <div class="card-content">
            <div class="card-title">
              <i class="bi bi-file-earmark-bar-graph"></i>
              <span>Custom Report</span>
            </div>
            <canvas id="customChart" width="800" height="400"></canvas>
          </div>
        </div>

        <!-- Data Tables Section -->
        <div class="dashboard-grid">
          <!-- Assets by Category Table -->
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-title">
                <i class="bi bi-tag"></i>
                <span>Assets by Category</span>
              </div>
              <?php if (!empty($categoryStats)): ?>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Category</th>
                        <th>Count</th>
                        <th>Percentage</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($categoryStats as $stat): ?>
                        <tr>
                          <td><?= htmlspecialchars($stat['category']) ?></td>
                          <td><span class="badge bg-primary"><?= $stat['count'] ?></span></td>
                          <td><?= number_format(($stat['count'] / $totalAssets) * 100, 1) ?>%</td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">No category data available.</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Assets by Location Table -->
          <div class="dashboard-card">
            <div class="card-content">
              <div class="card-title">
                <i class="bi bi-geo-alt"></i>
                <span>Assets by Location</span>
              </div>
              <?php if (!empty($locationStats)): ?>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Location</th>
                        <th>Count</th>
                        <th>Percentage</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($locationStats as $stat): ?>
                        <tr>
                          <td><?= htmlspecialchars($stat['location']) ?></td>
                          <td><span class="badge bg-info"><?= $stat['count'] ?></span></td>
                          <td><?= number_format(($stat['count'] / $totalAssets) * 100, 1) ?>%</td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <p class="text-muted">No location data available.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Recent Activity Summary -->
        <div class="dashboard-card">
          <div class="card-content">
            <div class="card-title">
              <i class="bi bi-activity"></i>
              <span>Recent Activity Summary</span>
            </div>
            <div class="row g-4">
              <div class="col-md-3">
                <div class="text-center">
                  <div class="stat-icon icon-total mb-2">
                    <i class="bi bi-plus-circle-fill"></i>
                  </div>
                  <h4 class="stat-number text-primary"><?= $recentAdditions ?></h4>
                  <p class="stat-title">Assets Added (Last 30 Days)</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <div class="stat-icon icon-assigned mb-2">
                    <i class="bi bi-person-check-fill"></i>
                  </div>
                  <h4 class="stat-number text-success"><?= $assigned ?></h4>
                  <p class="stat-title">Currently Assigned</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <div class="stat-icon icon-repair mb-2">
                    <i class="bi bi-tools"></i>
                  </div>
                  <h4 class="stat-number text-warning"><?= $repair ?></h4>
                  <p class="stat-title">Under Repair</p>
                </div>
              </div>
              <div class="col-md-3">
                <div class="text-center">
                  <div class="stat-icon icon-disposed mb-2">
                    <i class="bi bi-trash-fill"></i>
                  </div>
                  <h4 class="stat-number text-danger"><?= $disposed ?></h4>
                  <p class="stat-title">Disposed</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Export Options -->
        <div class="dashboard-card">
          <div class="card-content">
            <div class="card-title">
              <i class="bi bi-download"></i>
              <span>Export Reports</span>
            </div>
            <div class="row g-3">
              <div class="col-md-4">
                <a href="export.php?type=assets" class="btn btn-primary w-100">
                  <i class="bi bi-file-earmark-spreadsheet me-2"></i>Export All Assets
                </a>
              </div>
              <div class="col-md-4">
                <a href="export.php?type=category" class="btn btn-info w-100">
                  <i class="bi bi-file-earmark-bar-graph me-2"></i>Export Category Report
                </a>
              </div>
              <div class="col-md-4">
                <a href="export.php?type=location" class="btn btn-success w-100">
                  <i class="bi bi-file-earmark-geo me-2"></i>Export Location Report
                </a>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php require __DIR__ . '/footer.php'; ?>

  <script>
    // Chart.js configuration and data
    const chartColors = {
      primary: '#4361ee',
      secondary: '#3f37c9',
      success: '#4cc9f0',
      info: '#4895ef',
      warning: '#f72585',
      danger: '#e63946',
      light: '#f8f9fa',
      gray: '#6c757d'
    };

    // Status Distribution Chart
    const statusData = {
      labels: ['In Storage', 'Assigned', 'Under Repair', 'Disposed'],
      datasets: [{
        data: [<?= $inStorage ?>, <?= $assigned ?>, <?= $repair ?>, <?= $disposed ?>],
        backgroundColor: [
          chartColors.info,
          chartColors.success,
          chartColors.warning,
          chartColors.danger
        ],
        borderColor: [
          chartColors.info,
          chartColors.success,
          chartColors.warning,
          chartColors.danger
        ],
        borderWidth: 2
      }]
    };

    const statusChart = new Chart(document.getElementById('statusChart'), {
      type: 'pie',
      data: statusData,
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((context.parsed / total) * 100).toFixed(1);
                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
              }
            }
          }
        }
      }
    });

    // Category Chart
    const categoryLabels = [<?php foreach ($categoryStats as $stat) { echo "'" . addslashes($stat['category']) . "', "; } ?>];
    const categoryData = [<?php foreach ($categoryStats as $stat) { echo $stat['count'] . ", "; } ?>];

    const categoryChart = new Chart(document.getElementById('categoryChart'), {
      type: 'bar',
      data: {
        labels: categoryLabels,
        datasets: [{
          label: 'Assets',
          data: categoryData,
          backgroundColor: chartColors.primary,
          borderColor: chartColors.secondary,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        },
        plugins: {
          legend: {
            display: false
          }
        }
      }
    });

    // Monthly Chart
    const monthlyLabels = [<?php foreach ($monthlyStats as $stat) { echo "'" . $stat['month'] . "', "; } ?>];
    const monthlyData = [<?php foreach ($monthlyStats as $stat) { echo $stat['count'] . ", "; } ?>];

    const monthlyChart = new Chart(document.getElementById('monthlyChart'), {
      type: 'line',
      data: {
        labels: monthlyLabels,
        datasets: [{
          label: 'Assets Added',
          data: monthlyData,
          borderColor: chartColors.success,
          backgroundColor: 'rgba(76, 201, 240, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        }
      }
    });

    // Location Chart
    const locationLabels = [<?php foreach ($locationStats as $stat) { echo "'" . addslashes($stat['location']) . "', "; } ?>];
    const locationData = [<?php foreach ($locationStats as $stat) { echo $stat['count'] . ", "; } ?>];

    const locationChart = new Chart(document.getElementById('locationChart'), {
      type: 'doughnut',
      data: {
        labels: locationLabels,
        datasets: [{
          data: locationData,
          backgroundColor: [
            chartColors.primary,
            chartColors.secondary,
            chartColors.success,
            chartColors.info,
            chartColors.warning,
            chartColors.danger
          ],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
          }
        }
      }
    });

    // Custom Report Builder functionality
    let customChart = null;

    document.getElementById('generateCustomReport').addEventListener('click', function() {
      const dateFrom = document.getElementById('reportDateFrom').value;
      const dateTo = document.getElementById('reportDateTo').value;
      const chartType = document.getElementById('chartType').value;
      const metric = document.getElementById('reportMetric').value;
      const categoryFilter = document.getElementById('filterCategory').value;
      const statusFilter = document.getElementById('filterStatus').value;

      // Build query parameters
      const params = new URLSearchParams({
        date_from: dateFrom,
        date_to: dateTo,
        metric: metric,
        category: categoryFilter,
        status: statusFilter
      });

      // Fetch custom data
      fetch('fetch_custom_report.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
          generateCustomChart(data, chartType, metric);
          document.getElementById('customReportArea').style.display = 'block';
          document.getElementById('customReportArea').scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => {
          console.error('Error fetching custom report data:', error);
          alert('Error generating custom report. Please try again.');
        });
    });

    function generateCustomChart(data, chartType, metric) {
      const ctx = document.getElementById('customChart');

      // Destroy existing chart if it exists
      if (customChart) {
        customChart.destroy();
      }

      let chartConfig = {
        type: chartType,
        data: {
          labels: data.labels,
          datasets: [{
            label: getMetricLabel(metric),
            data: data.values,
            backgroundColor: getChartColors(data.labels.length),
            borderColor: chartColors.primary,
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'top',
            }
          }
        }
      };

      // Adjust options based on chart type
      if (chartType === 'line') {
        chartConfig.data.datasets[0].fill = true;
        chartConfig.data.datasets[0].tension = 0.4;
        chartConfig.options.scales = {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          }
        };
      } else if (chartType === 'bar') {
        chartConfig.options.scales = {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          }
        };
        chartConfig.options.plugins.legend.display = false;
      }

      customChart = new Chart(ctx, chartConfig);
    }

    function getMetricLabel(metric) {
      const labels = {
        status: 'Assets by Status',
        category: 'Assets by Category',
        location: 'Assets by Location',
        monthly: 'Monthly Asset Additions'
      };
      return labels[metric] || 'Assets';
    }

    function getChartColors(count) {
      const colors = [
        chartColors.primary,
        chartColors.secondary,
        chartColors.success,
        chartColors.info,
        chartColors.warning,
        chartColors.danger,
        chartColors.gray
      ];

      const result = [];
      for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
      }
      return result;
    }

    // PDF Export functionality
    document.getElementById('exportPDF').addEventListener('click', function() {
      const { jsPDF } = window.jspdf;

      html2canvas(document.querySelector('.main-content')).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');

        const imgWidth = 210;
        const pageHeight = 295;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;

        let position = 0;

        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft >= 0) {
          position = heightLeft - imgHeight;
          pdf.addPage();
          pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
          heightLeft -= pageHeight;
        }

        pdf.save('it_inventory_report_' + new Date().toISOString().split('T')[0] + '.pdf');
      });
    });
  </script>
