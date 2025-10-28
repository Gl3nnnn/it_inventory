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
    header("Location: assets.php");
    exit();
}

require __DIR__ . '/db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Prevent deleting own account
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = 'You cannot delete your own account!';
        header("Location: users.php");
        exit();
    }

    // Get user details before deletion for logging
    $userResult = $conn->query("SELECT username FROM users WHERE id = $id");
    $user = $userResult->fetch_assoc();

    if ($user) {
        $conn->query("DELETE FROM users WHERE id = $id");
        $_SESSION['success'] = 'User deleted successfully!';
    }

    header("Location: users.php");
    exit();
}

// Build WHERE conditions
$whereClauses = [];

// Search by username
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "username LIKE '%$search%'";
}

// Filter by role
if (!empty($_GET['role'])) {
    $role = $conn->real_escape_string($_GET['role']);
    $whereClauses[] = "role = '$role'";
}

$whereSQL = "";
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$countResult = $conn->query("SELECT COUNT(*) as total FROM users $whereSQL");
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$result = $conn->query("SELECT id, username, role, created_at FROM users $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - IT Asset Inventory</title>
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
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h2>User Management</h2>
                    <p class="page-subtitle">Manage system users and their permissions</p>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="add_user.php" class="btn btn-primary btn-lg d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle"></i>Add User
                    </a>
                </div>
                <div class="text-muted fs-5">
                    Total: <strong><?= $totalRows ?></strong> users found
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
                            <div class="col-md-6">
                                <label class="form-label">Search Username</label>
                                <input type="text" name="search" class="form-control" placeholder="Search by username"
                                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select">
                                    <option value="">All Roles</option>
                                    <option <?= (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option <?= (isset($_GET['role']) && $_GET['role'] === 'staff') ? 'selected' : '' ?>>Staff</option>
                                </select>
                            </div>

                            <div class="col-12 d-flex gap-3">
                                <button type="submit" class="filter-btn primary">
                                    <i class="bi bi-search"></i>
                                    <span>Apply Filters</span>
                                </button>
                                <a href="users.php" class="filter-btn secondary">
                                    <i class="bi bi-x-circle"></i>
                                    <span>Clear Filters</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                switch ($row['role']) {
                                                    case 'admin': echo 'danger'; break;
                                                    case 'staff': echo 'info'; break;
                                                    default: echo 'secondary'; break;
                                                }
                                            ?>">
                                                <?= htmlspecialchars(ucfirst($row['role'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_user.php?id=<?= $row['id'] ?>" class="action-btn edit">
                                                    <i class="bi bi-pencil-fill"></i>
                                                    <span>Edit</span>
                                                </a>
                                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="action-btn delete"
                                                        onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['username']) ?>')">
                                                    <i class="bi bi-trash-fill"></i>
                                                    <span>Delete</span>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-people-fill" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No users found.</p>
                                        <a href="add_user.php" class="btn btn-primary mt-2">
                                            <i class="bi bi-plus-circle me-2"></i>Add Your First User
                                        </a>
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
<?php if (isset($_SESSION['error'])): ?>
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0 fade show" role="alert" aria-live="assertive" aria-atomic="true">
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
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the user "<strong id="userName"></strong>"?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <a id="confirmDeleteBtn" href="#" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i>Delete User
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var successToast = document.getElementById('successToast');
    var errorToast = document.getElementById('errorToast');

    if (successToast && successToast.querySelector('.toast-body').textContent.trim() !== '') {
        var toast = new bootstrap.Toast(successToast, { delay: 5000 });
        toast.show();
    }

    if (errorToast && errorToast.querySelector('.toast-body').textContent.trim() !== '') {
        var toast = new bootstrap.Toast(errorToast, { delay: 5000 });
        toast.show();
    }
});

function confirmDelete(userId, userName) {
    document.getElementById('userName').textContent = userName;
    document.getElementById('confirmDeleteBtn').href = 'users.php?delete=' + userId;

    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
</body>
</html>
