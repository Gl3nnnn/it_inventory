<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/db.php';
require __DIR__ . '/activity_logger.php';

// Handle Delete
if (isset($_GET['delete']) && $_SESSION['role'] === 'admin') {
    $id = intval($_GET['delete']);

    // Get asset details before deletion for logging
    $assetResult = $conn->query("SELECT asset_name, asset_tag FROM assets WHERE id = $id");
    $asset = $assetResult->fetch_assoc();

    if ($asset) {
        $conn->query("DELETE FROM assets WHERE id = $id");

        // Log the deletion
        logActivity($conn, 'Asset Deleted', $asset['asset_name'], $asset['asset_tag'], 'Asset permanently removed from inventory');

        $_SESSION['success'] = 'Asset deleted successfully!';
    }

    header("Location: assets.php");
    exit();
}

// Handle single asset view
$singleAsset = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $assetId = intval($_GET['id']);
    $assetQuery = $conn->prepare("SELECT * FROM assets WHERE id = ?");
    $assetQuery->bind_param("i", $assetId);
    $assetQuery->execute();
    $singleAsset = $assetQuery->get_result()->fetch_assoc();
    $assetQuery->close();

    if ($singleAsset) {
        // Log the view activity
        logActivity($conn, 'Asset Viewed', $singleAsset['asset_name'], $singleAsset['asset_tag'], 'Asset details viewed from QR scanner');
    }
}

// Build WHERE conditions
$whereClauses = [];

// Search by keyword
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(asset_tag LIKE '%$search%' OR asset_name LIKE '%$search%' OR category LIKE '%$search%')";
}

// Filter by status
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClauses[] = "status = '$status'";
}

// Filter by category
if (!empty($_GET['category'])) {
    $category = $conn->real_escape_string($_GET['category']);
    $whereClauses[] = "category = '$category'";
}

// Filter by location
if (!empty($_GET['location'])) {
    $location = $conn->real_escape_string($_GET['location']);
    $whereClauses[] = "location = '$location'";
}

// Filter by date range
if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $from = $conn->real_escape_string($_GET['date_from']);
    $to   = $conn->real_escape_string($_GET['date_to']);
    $whereClauses[] = "DATE(created_at) BETWEEN '$from' AND '$to'";
}

$whereSQL = "";
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Pagination
$limit = isset($_GET['limit']) ? max(5, min(50, intval($_GET['limit']))) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$countResult = $conn->query("SELECT COUNT(*) as total FROM assets $whereSQL");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$result = $conn->query("SELECT * FROM assets $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");

// Fetch distinct categories and locations for filter dropdowns
$categories = $conn->query("SELECT DISTINCT category FROM assets ORDER BY category ASC");
$locations  = $conn->query("SELECT DISTINCT location FROM assets ORDER BY location ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets Management - IT Asset Inventory</title>
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
                    <i class="bi bi-device-ssd-fill"></i>
                </div>
                <div>
                    <h2><?php echo $singleAsset ? 'Asset Details: ' . htmlspecialchars($singleAsset['asset_name']) : 'Asset Management'; ?></h2>
                    <p class="page-subtitle"><?php echo $singleAsset ? 'Detailed information for asset tag: ' . htmlspecialchars($singleAsset['asset_tag']) : 'View and manage all IT assets in your inventory'; ?></p>
                    <?php if ($singleAsset): ?>
                        <a href="assets.php" class="btn btn-outline-secondary mt-2">
                            <i class="bi bi-arrow-left me-2"></i>Back to All Assets
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$singleAsset): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex gap-2 align-items-center">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="add_asset.php" class="btn btn-primary btn-lg d-flex align-items-center gap-2">
                            <i class="bi bi-plus-circle"></i> Add Asset
                        </a>
                        <button type="button" class="btn btn-success btn-lg d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload"></i> Import Assets
                        </button>
                        <a href="generate_stickers.php" class="btn btn-info btn-lg d-flex align-items-center gap-2">
                            <i class="bi bi-upc-scan"></i> Generate Stickers
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-primary btn-lg dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="export.php?<?= http_build_query($_GET) ?>">Export as CSV</a></li>
                                <li><a class="dropdown-item" href="export_assets.php?format=xls&<?= http_build_query($_GET) ?>">Export as Excel</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-muted fs-5">
                    Total: <strong><?= $totalRows ?></strong> assets found
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$singleAsset): ?>
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
                                <input type="text" name="search" class="form-control" placeholder="Search by tag, name, category"
                                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'In Storage') ? 'selected' : '' ?>>In Storage</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'Assigned') ? 'selected' : '' ?>>Assigned</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'Under Repair') ? 'selected' : '' ?>>Under Repair</option>
                                    <option <?= (isset($_GET['status']) && $_GET['status'] === 'Disposed') ? 'selected' : '' ?>>Disposed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option <?= (isset($_GET['category']) && $_GET['category'] === $cat['category']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Location</label>
                                <select name="location" class="form-select">
                                    <option value="">All Locations</option>
                                    <?php while ($loc = $locations->fetch_assoc()): ?>
                                        <option <?= (isset($_GET['location']) && $_GET['location'] === $loc['location']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($loc['location']) ?>
                                        </option>
                                    <?php endwhile; ?>
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
                                    <i class="bi bi-search"></i>
                                    <span>Apply Filters</span>
                                </button>
                                <a href="assets.php" class="filter-btn secondary">
                                    <i class="bi bi-x-circle"></i>
                                    <span>Clear Filters</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$singleAsset): ?>
            <!-- Page Size Selector -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0">Show:</label>
                    <select class="form-select form-select-sm" style="width: 70px;" onchange="changeLimit(this.value)">
                        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                    </select>
                    <span class="text-muted">entries per page</span>
                </div>
                <div class="text-muted">
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $limit, $totalRows) ?> of <?= $totalRows ?> entries
                </div>
            </div>
            <?php endif; ?>

            <?php if ($singleAsset): ?>
                <!-- Single Asset Details View -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5 class="card-title mb-3">Asset Information</h5>
                                <table class="table table-sm">
                                    <tr><td><strong>Asset Tag:</strong></td><td><span class="badge bg-primary fs-6"><?= htmlspecialchars($singleAsset['asset_tag']) ?></span></td></tr>
                                    <tr><td><strong>Name:</strong></td><td><?= htmlspecialchars($singleAsset['asset_name']) ?></td></tr>
                                    <tr><td><strong>Category:</strong></td><td><?= htmlspecialchars($singleAsset['category']) ?></td></tr>
                                    <tr><td><strong>Status:</strong></td><td>
                                        <span class="badge bg-<?php
                                            switch ($singleAsset['status']) {
                                                case 'In Storage': echo 'secondary'; break;
                                                case 'Assigned': echo 'success'; break;
                                                case 'Under Repair': echo 'warning'; break;
                                                case 'Disposed': echo 'danger'; break;
                                                default: echo 'dark'; break;
                                            }
                                        ?> fs-6">
                                            <?= htmlspecialchars($singleAsset['status']) ?>
                                        </span>
                                    </td></tr>
                                    <tr><td><strong>Location:</strong></td><td><?= htmlspecialchars($singleAsset['location']) ?></td></tr>
                                    <tr><td><strong>Lifespan:</strong></td><td><?php if (!empty($singleAsset['item_lifespan'])): ?><?= htmlspecialchars($singleAsset['item_lifespan']) ?> years<?php endif; ?></td></tr>
                                    <tr><td><strong>Disposal Method:</strong></td><td><?= htmlspecialchars($singleAsset['disposal_method'] ?? 'N/A') ?></td></tr>
                                    <tr><td><strong>Acquisition Date:</strong></td><td><?php if (!empty($singleAsset['acquisition_date'])): ?><?= date('M j, Y', strtotime($singleAsset['acquisition_date'])) ?><?php else: ?>Not set<?php endif; ?></td></tr>
                                    <tr><td><strong>Created:</strong></td><td><?= date('M j, Y', strtotime($singleAsset['created_at'])) ?></td></tr>
                                    <tr><td><strong>Last Updated:</strong></td><td><?php if (!empty($singleAsset['updated_at'])): ?><?= date('M j, Y', strtotime($singleAsset['updated_at'])) ?><?php else: ?>Never<?php endif; ?></td></tr>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <h5 class="card-title mb-3">Photo</h5>
                                <?php if (!empty($singleAsset['photo_path'])): ?>
                                    <img src="<?= htmlspecialchars($singleAsset['photo_path']) ?>" alt="Asset photo" class="img-fluid rounded" style="max-height: 300px; cursor: pointer;" onclick="showImageModal('<?= htmlspecialchars($singleAsset['photo_path']) ?>', '<?= htmlspecialchars($singleAsset['asset_name']) ?>')">
                                <?php else: ?>
                                    <div class="text-center p-4 border rounded" style="background-color: #f8f9fa;">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-2">No photo available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <h5 class="card-title mb-3">Documents</h5>
                                <?php
                                $documents = json_decode($singleAsset['document_paths'] ?? '[]', true) ?: [];
                                if (!empty($documents)):
                                ?>
                                    <div class="list-group">
                                        <?php foreach ($documents as $doc): ?>
                                            <a href="#" class="list-group-item list-group-item-action" onclick="showDocumentModal('<?= htmlspecialchars($doc['path']) ?>', '<?= htmlspecialchars($doc['name']) ?>')">
                                                <i class="bi bi-file-earmark-text me-2"></i>
                                                <?= htmlspecialchars($doc['name']) ?>
                                                <small class="text-muted">(<?= number_format($doc['size'] / 1024 / 1024, 2) ?> MB)</small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-4 border rounded" style="background-color: #f8f9fa;">
                                        <i class="bi bi-file-earmark-x text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-2">No documents attached</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($singleAsset['description'])): ?>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5 class="card-title">Description</h5>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($singleAsset['description'])) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <a href="edit_asset.php?id=<?= $singleAsset['id'] ?>" class="btn btn-primary">
                                            <i class="bi bi-pencil me-2"></i>Edit Asset
                                        </a>
                                        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $singleAsset['id'] ?>, '<?= htmlspecialchars($singleAsset['asset_name']) ?>')">
                                            <i class="bi bi-trash me-2"></i>Delete Asset
                                        </button>
                                    <?php endif; ?>
                                    <a href="generate_stickers.php?ids[]=<?= $singleAsset['id'] ?>" class="btn btn-info">
                                        <i class="bi bi-upc-scan me-2"></i>Generate Sticker
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
            <!-- Assets Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="assets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Asset Tag</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Lifespan</th>
                                <th>Disposal Method</th>
                                <th>Acquisition Date</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td>
                                            <?php if (!empty($row['photo_path'])): ?>
                                                <img src="<?= htmlspecialchars($row['photo_path']) ?>" alt="Asset photo" class="asset-thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;" onclick="showImageModal('<?= htmlspecialchars($row['photo_path']) ?>', '<?= htmlspecialchars($row['asset_name']) ?>')">
                                            <?php else: ?>
                                                <div class="no-photo-placeholder" style="width: 50px; height: 50px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= htmlspecialchars($row['asset_tag']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['asset_name']) ?></td>
                                        <td><?= htmlspecialchars($row['category']) ?></td>
                                        <td><?= htmlspecialchars($row['location']) ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($row['status']) {
                                                    case 'In Storage': echo 'secondary'; break;
                                                    case 'Assigned': echo 'success'; break;
                                                    case 'Under Repair': echo 'warning'; break;
                                                    case 'Disposed': echo 'danger'; break;
                                                    default: echo 'dark'; break;
                                                }
                                            ?>">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                        <td><?php if (!empty($row['item_lifespan'])): ?><?= htmlspecialchars($row['item_lifespan']) ?> years<?php endif; ?></td>
                                        <td><?= htmlspecialchars($row['disposal_method'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($row['acquisition_date'])): ?>
                                                <?= date('M j, Y', strtotime($row['acquisition_date'])) ?>
                                            <?php else: ?>
                                                <a href="edit_asset.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-plus-circle me-1"></i>Add Date
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                                    <a href="edit_asset.php?id=<?= $row['id'] ?>" class="action-btn edit">
                                                        <i class="bi bi-pencil-fill"></i>
                                                        <span>Edit</span>
                                                    </a>
                                                    <?php
                                                    $documents = json_decode($row['document_paths'] ?? '[]', true) ?: [];
                                                    if (!empty($documents)):
                                                    ?>
                                                        <div class="dropdown d-inline">
                                                            <button class="action-btn docs dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-file-earmark-text-fill"></i>
                                                                <span>Docs (<?= count($documents) ?>)</span>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <?php foreach ($documents as $doc): ?>
                                                                    <li>
                                                                        <a class="dropdown-item" href="#" onclick="showDocumentModal('<?= htmlspecialchars($doc['path']) ?>', '<?= htmlspecialchars($doc['name']) ?>')">
                                                                            <i class="bi bi-file-earmark-text me-2"></i>
                                                                            <?= htmlspecialchars($doc['name']) ?>
                                                                            <small class="text-muted">(<?= number_format($doc['size'] / 1024 / 1024, 2) ?> MB)</small>
                                                                        </a>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                    <button type="button" class="action-btn delete"
                                                            onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['asset_name']) ?>')">
                                                        <i class="bi bi-trash-fill"></i>
                                                        <span>Delete</span>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">View Only</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No assets found.</p>
                                        <a href="add_asset.php" class="btn btn-primary mt-2">
                                            <i class="bi bi-plus-circle me-2"></i>Add Your First Asset
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

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
            </div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3">
<?php if (isset($_SESSION['success'])): ?>
    <div id="successToast" class="toast align-items-center text-white border-0 fade show <?php echo strpos($_SESSION['success'], 'deleted') !== false ? 'bg-danger' : 'bg-success'; ?>" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi <?php echo strpos($_SESSION['success'], 'deleted') !== false ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'; ?> me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
<?php endif; ?>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
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
                <p>Are you sure you want to delete the asset "<strong id="assetName"></strong>"?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <a id="confirmDeleteBtn" href="#" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>Delete Asset
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Asset Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Asset photo" class="img-fluid rounded">
            </div>
            <div class="modal-footer">
                <a id="downloadImageLink" href="" download class="btn btn-primary">
                    <i class="bi bi-download me-2"></i>Download Photo
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Document Modal -->
<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalLabel">Document Viewer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe id="documentFrame" src="" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <a id="downloadLink" href="" download class="btn btn-primary">
                    <i class="bi bi-download me-2"></i>Download Document
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($_SESSION['role'] === 'admin'): ?>
<!-- Import Assets Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="bi bi-upload text-success me-2"></i>
                    Import Assets
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="import_assets.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Import Instructions:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Upload a CSV or Excel (.xlsx) file</li>
                            <li>First row must contain column headers</li>
                            <li>Required columns: <code>asset_tag</code>, <code>asset_name</code>, <code>category</code>, <code>status</code></li>
                            <li>Optional columns: <code>location</code>, <code>item_lifespan</code>, <code>disposal_method</code>, <code>acquisition_date</code></li>
                            <li>Valid status values: In Storage, Assigned, Under Repair, Disposed</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="import_file" class="form-label">Select File</label>
                        <input type="file" class="form-control" id="import_file" name="import_file"
                               accept=".csv,.xlsx,.xls" required>
                        <div class="form-text">Supported formats: CSV, Excel (.xlsx, .xls)</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="update_existing" name="update_existing" checked>
                            <label class="form-check-label" for="update_existing">
                                Update existing assets (matching by Asset Tag)
                            </label>
                        </div>
                    </div>

                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-upload me-1"></i>Import Assets
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toastEl = document.getElementById('successToast');
    if (toastEl && toastEl.querySelector('.toast-body').textContent.trim() !== '') {
        var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
    }
});

function confirmDelete(assetId, assetName) {
    <?php if ($_SESSION['role'] === 'admin'): ?>
        document.getElementById('assetName').textContent = assetName;
        document.getElementById('confirmDeleteBtn').href = 'assets.php?delete=' + assetId;

        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    <?php endif; ?>
}

function changeLimit(limit) {
    const url = new URL(window.location);
    url.searchParams.set('limit', limit);
    url.searchParams.set('page', '1'); // Reset to first page when changing limit
    window.location.href = url.toString();
}

function showImageModal(imageSrc, assetName) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModalLabel').textContent = 'Photo: ' + assetName;
    document.getElementById('downloadImageLink').href = imageSrc;
    var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    imageModal.show();
}

function showDocumentModal(docPath, docName) {
    document.getElementById('documentFrame').src = docPath;
    document.getElementById('documentModalLabel').textContent = 'Document: ' + docName;
    document.getElementById('downloadLink').href = docPath;
    var documentModal = new bootstrap.Modal(document.getElementById('documentModal'));
    documentModal.show();
}
</script>
</body>
</html>
