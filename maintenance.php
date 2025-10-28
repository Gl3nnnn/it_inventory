<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Restrict access to admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require __DIR__ . '/db.php';
require __DIR__ . '/activity_logger.php';

// Handle maintenance actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_maintenance' && isset($_POST['asset_id'])) {
        $asset_id = intval($_POST['asset_id']);
        $maintenance_type = $conn->real_escape_string($_POST['maintenance_type']);
        $description = $conn->real_escape_string($_POST['description']);
        $scheduled_date = !empty($_POST['scheduled_date']) ? $conn->real_escape_string($_POST['scheduled_date']) : null;
        $priority = $conn->real_escape_string($_POST['priority']);
        $assigned_to = $conn->real_escape_string($_POST['assigned_to']);
        $cost = !empty($_POST['cost']) ? floatval($_POST['cost']) : 0;
        $notes = $conn->real_escape_string($_POST['notes']);

        $sql = "INSERT INTO maintenance (asset_id, maintenance_type, description, scheduled_date, priority, assigned_to, cost, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssdsi", $asset_id, $maintenance_type, $description, $scheduled_date, $priority, $assigned_to, $cost, $notes, $_SESSION['user_id']);

        if ($stmt->execute()) {
            // Get asset details for logging
            $assetResult = $conn->query("SELECT asset_name, asset_tag FROM assets WHERE id = $asset_id");
            $asset = $assetResult->fetch_assoc();

            logActivity($conn, 'Maintenance Scheduled', $asset['asset_name'], $asset['asset_tag'], "Maintenance type: $maintenance_type, Scheduled: $scheduled_date");
            $_SESSION['success'] = 'Maintenance scheduled successfully!';
        } else {
            $_SESSION['error'] = 'Failed to schedule maintenance.';
        }

        header("Location: maintenance.php");
        exit();
    }

    if ($action === 'update_status' && isset($_POST['maintenance_id'])) {
        $maintenance_id = intval($_POST['maintenance_id']);
        $status = $conn->real_escape_string($_POST['status']);
        $completed_date = ($status === 'Completed') ? date('Y-m-d') : null;
        $notes = $conn->real_escape_string($_POST['completion_notes']);

        $sql = "UPDATE maintenance SET status = ?, completed_date = ?, notes = CONCAT(IFNULL(notes, ''), '\n\nStatus updated to $status on " . date('Y-m-d H:i:s') . ":\n', ?) WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $status, $completed_date, $notes, $maintenance_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Maintenance status updated successfully!';
        } else {
            $_SESSION['error'] = 'Failed to update maintenance status.';
        }

        header("Location: maintenance.php");
        exit();
    }

    if ($action === 'delete_maintenance' && isset($_POST['maintenance_id'])) {
        $maintenance_id = intval($_POST['maintenance_id']);

        // Get maintenance details before deletion
        $maintenanceResult = $conn->query("SELECT m.*, a.asset_name, a.asset_tag FROM maintenance m JOIN assets a ON m.asset_id = a.id WHERE m.id = $maintenance_id");
        $maintenance = $maintenanceResult->fetch_assoc();

        if ($maintenance) {
            $conn->query("DELETE FROM maintenance WHERE id = $maintenance_id");
            logActivity($conn, 'Maintenance Deleted', $maintenance['asset_name'], $maintenance['asset_tag'], 'Maintenance record deleted');
            $_SESSION['success'] = 'Maintenance record deleted successfully!';
        }

        header("Location: maintenance.php");
        exit();
    }
}

// Build WHERE conditions for filtering
$whereClauses = [];
$joinClause = " JOIN assets a ON m.asset_id = a.id";

// Search by asset
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(a.asset_tag LIKE '%$search%' OR a.asset_name LIKE '%$search%' OR m.description LIKE '%$search%')";
}

// Filter by status
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClauses[] = "m.status = '$status'";
}

// Filter by type
if (!empty($_GET['type'])) {
    $type = $conn->real_escape_string($_GET['type']);
    $whereClauses[] = "m.maintenance_type = '$type'";
}

// Filter by priority
if (!empty($_GET['priority'])) {
    $priority = $conn->real_escape_string($_GET['priority']);
    $whereClauses[] = "m.priority = '$priority'";
}

// Filter by date range
if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $from = $conn->real_escape_string($_GET['date_from']);
    $to = $conn->real_escape_string($_GET['date_to']);
    $whereClauses[] = "m.scheduled_date BETWEEN '$from' AND '$to'";
}

$whereSQL = "";
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$countResult = $conn->query("SELECT COUNT(*) as total FROM maintenance m $joinClause $whereSQL");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$result = $conn->query("SELECT m.*, a.asset_name, a.asset_tag, a.category, a.location
                       FROM maintenance m $joinClause $whereSQL
                       ORDER BY m.scheduled_date ASC, m.created_at DESC
                       LIMIT $limit OFFSET $offset");

// Get assets for the add maintenance form
$assets = $conn->query("SELECT id, asset_name, asset_tag FROM assets ORDER BY asset_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Management - IT Asset Inventory</title>
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
        <main class="main-content col-md-9 col-lg-10">
            <div class="page-header">
                <div class="page-icon">
                    <i class="bi bi-tools-fill"></i>
                </div>
                <div>
                    <h2>Maintenance Management</h2>
                    <p class="page-subtitle">Schedule and track asset maintenance activities</p>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <button class="btn btn-primary btn-lg d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                        <i class="bi bi-plus-circle"></i>Schedule Maintenance
                    </button>
                </div>
                <div class="text-muted fs-5">
                    Total: <strong><?= $totalRows ?></strong> maintenance records
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-card">
                <div class="filters-header">
                    <button class="filter-toggle-btn" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection">
                        <i class="bi bi-funnel-fill"></i>
                        <span>Filters</span>
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </button>
                </div>

                <div class="collapse <?= (!empty($_GET)) ? 'show' : '' ?>" id="filterSection">
                    <div class="filters-content">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search by asset or description"
                                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'Scheduled') ? 'selected' : '' ?>>Scheduled</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'Completed') ? 'selected' : '' ?>>Completed</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'Overdue') ? 'selected' : '' ?>>Overdue</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="">All Types</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Preventive') ? 'selected' : '' ?>>Preventive</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Corrective') ? 'selected' : '' ?>>Corrective</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Inspection') ? 'selected' : '' ?>>Inspection</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Calibration') ? 'selected' : '' ?>>Calibration</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Software Update') ? 'selected' : '' ?>>Software Update</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Hardware Upgrade') ? 'selected' : '' ?>>Hardware Upgrade</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Cleaning') ? 'selected' : '' ?>>Cleaning</option>
                                    <option <?= (isset($_GET['type']) && $_GET['type'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="">All Priorities</option>
                                    <option <?= (isset($_GET['priority']) && $_GET['priority'] === 'Low') ? 'selected' : '' ?>>Low</option>
                                    <option <?= (isset($_GET['priority']) && $_GET['priority'] === 'Medium') ? 'selected' : '' ?>>Medium</option>
                                    <option <?= (isset($_GET['priority']) && $_GET['priority'] === 'High') ? 'selected' : '' ?>>High</option>
                                    <option <?= (isset($_GET['priority']) && $_GET['priority'] === 'Critical') ? 'selected' : '' ?>>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <div class="input-group">
                                    <input type="date" name="date_from" class="form-control"
                                           value="<?= isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : '' ?>">
                                    <span class="input-group-text">to</span>
                                    <input type="date" name="date_to" class="form-control"
                                           value="<?= isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : '' ?>">
                                </div>
                            </div>

                            <div class="col-12 d-flex gap-3">
                                <button type="submit" class="filter-btn primary">
                                    <i class="bi bi-funnel-fill"></i>
                                    <span>Apply Filters</span>
                                </button>
                                <a href="maintenance.php" class="filter-btn secondary">
                                    <i class="bi bi-x-circle"></i>
                                    <span>Clear Filters</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Maintenance Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Scheduled Date</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Cost</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()):
                                    $isOverdue = ($row['status'] !== 'Completed' && $row['status'] !== 'Cancelled' && $row['scheduled_date'] < date('Y-m-d'));
                                    if ($isOverdue && $row['status'] !== 'Overdue') {
                                        // Auto-update overdue status
                                        $conn->query("UPDATE maintenance SET status = 'Overdue' WHERE id = {$row['id']}");
                                        $row['status'] = 'Overdue';
                                    }
                                ?>
                                    <tr class="<?= $isOverdue ? 'overdue' : '' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($row['asset_tag']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($row['asset_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($row['maintenance_type']) ?></span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(substr($row['description'], 0, 50)) ?>
                                            <?= strlen($row['description']) > 50 ? '...' : '' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['scheduled_date'])): ?>
                                                <?= date('M j, Y', strtotime($row['scheduled_date'])) ?>
                                                <?php if ($isOverdue): ?>
                                                    <br><small class="text-danger maintenance-status">OVERDUE</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Not scheduled
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($row['status']) {
                                                    case 'Scheduled': echo 'secondary'; break;
                                                    case 'In Progress': echo 'warning'; break;
                                                    case 'Completed': echo 'success'; break;
                                                    case 'Cancelled': echo 'dark'; break;
                                                    case 'Overdue': echo 'danger'; break;
                                                    default: echo 'dark'; break;
                                                }
                                            ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($row['priority']) {
                                                    case 'Low': echo 'secondary'; break;
                                                    case 'Medium': echo 'info'; break;
                                                    case 'High': echo 'warning'; break;
                                                    case 'Critical': echo 'danger'; break;
                                                    default: echo 'dark'; break;
                                                }
                                            ?>">
                                                <?= htmlspecialchars($row['priority']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['assigned_to'] ?? 'Not assigned') ?></td>
                                        <td>
                                            <?php if (!empty($row['cost']) && $row['cost'] > 0): ?>
                                                $<?= number_format($row['cost'], 2) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="action-btn edit"
                                                        onclick="updateStatus(<?= $row['id'] ?>, '<?= htmlspecialchars($row['status']) ?>')">
                                                    <i class="bi bi-pencil-fill"></i>
                                                    <span>Update</span>
                                                </button>
                                                <button type="button" class="action-btn delete"
                                                        onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['asset_name']) ?>')">
                                                    <i class="bi bi-trash-fill"></i>
                                                    <span>Delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="bi bi-tools-fill" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No maintenance records found.</p>
                                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                                            <i class="bi bi-plus-circle me-2"></i>Schedule Your First Maintenance
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;

                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'">'.$totalPages.'</a></li>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<style>
.pagination {
    --bs-pagination-padding-x: 0.75rem;
    --bs-pagination-padding-y: 0.5rem;
    --bs-pagination-font-size: 1rem;
    --bs-pagination-color: #4361ee;
    --bs-pagination-bg: #fff;
    --bs-pagination-border-color: #dee2e6;
    --bs-pagination-hover-color: #fff;
    --bs-pagination-hover-bg: #4361ee;
    --bs-pagination-hover-border-color: #4361ee;
    --bs-pagination-focus-color: #fff;
    --bs-pagination-focus-bg: #4361ee;
    --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
    --bs-pagination-active-color: #fff;
    --bs-pagination-active-bg: #4361ee;
    --bs-pagination-active-border-color: #4361ee;
    --bs-pagination-disabled-color: #6c757d;
    --bs-pagination-disabled-bg: #fff;
    --bs-pagination-disabled-border-color: #dee2e6;
    display: flex;
    padding-left: 0;
    list-style: none;
    border-radius: 0.375rem;
    user-select: none;
    gap: 0.25rem;
}

.pagination .page-item {
    border-radius: 0.375rem;
    transition: background-color 0.3s ease;
}

.pagination .page-item .page-link {
    color: var(--bs-pagination-color);
    background-color: var(--bs-pagination-bg);
    border: 1px solid var(--bs-pagination-border-color);
    padding: var(--bs-pagination-padding-y) var(--bs-pagination-padding-x);
    font-size: var(--bs-pagination-font-size);
    border-radius: 0.375rem;
    transition: color 0.3s ease, background-color 0.3s ease;
}

.pagination .page-item:hover:not(.active):not(.disabled) .page-link {
    color: var(--bs-pagination-hover-color);
    background-color: var(--bs-pagination-hover-bg);
    border-color: var(--bs-pagination-hover-border-color);
    text-decoration: none;
}

.pagination .page-item:focus:not(.active):not(.disabled) .page-link {
    color: var(--bs-pagination-focus-color);
    background-color: var(--bs-pagination-focus-bg);
    border-color: var(--bs-pagination-focus-bg);
    box-shadow: var(--bs-pagination-focus-box-shadow);
    outline: 0;
}

.pagination .page-item.active .page-link {
    z-index: 3;
    color: var(--bs-pagination-active-color);
    background-color: var(--bs-pagination-active-bg);
    border-color: var(--bs-pagination-active-border-color);
    cursor: default;
}

.pagination .page-item.disabled .page-link {
    color: var(--bs-pagination-disabled-color);
    pointer-events: none;
    background-color: var(--bs-pagination-disabled-bg);
    border-color: var(--bs-pagination-disabled-border-color);
    cursor: default;
}

.pagination .page-item.disabled .page-link:hover {
    background-color: var(--bs-pagination-disabled-bg);
    border-color: var(--bs-pagination-disabled-border-color);
}

.pagination .page-item.disabled .page-link:focus {
    box-shadow: none;
}

.pagination .page-item .page-link:focus-visible {
    outline: 2px solid var(--bs-pagination-active-bg);
    outline-offset: 2px;
}
</style>

<!-- Add Maintenance Modal -->
<div class="modal fade" id="addMaintenanceModal" tabindex="-1" aria-labelledby="addMaintenanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="maintenance.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMaintenanceModalLabel">Schedule Maintenance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add_maintenance" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>" />

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="asset_id" class="form-label">Asset *</label>
                        <select name="asset_id" id="asset_id" class="form-select" required>
                            <option value="">Select Asset</option>
                            <?php while ($asset = $assets->fetch_assoc()): ?>
                                <option value="<?= $asset['id'] ?>">
                                    <?= htmlspecialchars($asset['asset_tag']) ?> - <?= htmlspecialchars($asset['asset_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="maintenance_type" class="form-label">Maintenance Type *</label>
                        <select name="maintenance_type" id="maintenance_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <option value="Preventive">Preventive</option>
                            <option value="Corrective">Corrective</option>
                            <option value="Inspection">Inspection</option>
                            <option value="Calibration">Calibration</option>
                            <option value="Software Update">Software Update</option>
                            <option value="Hardware Upgrade">Hardware Upgrade</option>
                            <option value="Cleaning">Cleaning</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="description" class="form-label">Description *</label>
                        <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="scheduled_date" class="form-label">Scheduled Date</label>
                        <input type="date" name="scheduled_date" id="scheduled_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="priority" class="form-label">Priority</label>
                        <select name="priority" id="priority" class="form-select">
                            <option value="Medium">Medium</option>
                            <option value="Low">Low</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="assigned_to" class="form-label">Assigned To</label>
                        <input type="text" name="assigned_to" id="assigned_to" class="form-control" placeholder="IT Support, External Vendor, etc.">
                    </div>
                    <div class="col-md-6">
                        <label for="cost" class="form-label">Estimated Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="cost" id="cost" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Schedule Maintenance</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="maintenance.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Maintenance Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_status" />
                <input type="hidden" name="maintenance_id" id="update_maintenance_id" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>" />

                <div class="mb-3">
                    <label for="update_status" class="form-label">Status *</label>
                    <select name="status" id="update_status" class="form-select" required>
                        <option value="Scheduled">Scheduled</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="completion_notes" class="form-label">Notes</label>
                    <textarea name="completion_notes" id="completion_notes" class="form-control" rows="3" placeholder="Add notes about the status update..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update Status</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the maintenance record for "<strong id="assetName"></strong>"?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <form action="maintenance.php" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_maintenance" />
                    <input type="hidden" name="maintenance_id" id="delete_maintenance_id" />
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCSRFToken()) ?>" />
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3">
<?php if (isset($_SESSION['success'])): ?>
    <div id="successToast" class="toast align-items-center text-white border-0 fade show bg-success" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div id="errorToast" class="toast align-items-center text-white border-0 fade show bg-danger" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toastEl = document.getElementById('successToast');
    if (toastEl && toastEl.querySelector('.toast-body').textContent.trim() !== '') {
        var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    }

    var errorToastEl = document.getElementById('errorToast');
    if (errorToastEl && errorToastEl.querySelector('.toast-body').textContent.trim() !== '') {
        var toast = new bootstrap.Toast(errorToastEl, { delay: 5000 });
        toast.show();
    }
});

function updateStatus(maintenanceId, currentStatus) {
    document.getElementById('update_maintenance_id').value = maintenanceId;
    document.getElementById('update_status').value = currentStatus;

    // Show the modal
    var modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

function confirmDelete(maintenanceId, assetName) {
    document.getElementById('delete_maintenance_id').value = maintenanceId;
    document.getElementById('assetName').textContent = assetName;

    // Show the modal
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

</body>
</html>