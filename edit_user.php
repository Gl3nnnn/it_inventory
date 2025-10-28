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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: users.php");
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'];

    $errors = [];

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    }

    // Check if username already exists (excluding current user)
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Username already exists.";
    }
    $stmt->close();

    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    if (empty($role) || !in_array($role, ['admin', 'staff'])) {
        $errors[] = "Please select a valid role.";
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $role, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = 'User updated successfully!';
            header("Location: users.php");
            exit();
        } else {
            $errors[] = "Failed to update user. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - IT Asset Inventory</title>
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
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h2>Edit User</h2>
                    <p class="page-subtitle">Update user account information</p>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="form-container">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <h6 class="alert-heading mb-2">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following errors:
                                    </h6>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="post" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="username" name="username"
                                               value="<?= htmlspecialchars($user['username']) ?>"
                                               required minlength="3" maxlength="50">
                                        <div class="form-text">Choose a unique username (3-50 characters)</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="">Select Role</option>
                                            <option value="staff" <?= ($user['role'] === 'staff') ? 'selected' : '' ?>>Staff</option>
                                            <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <div class="form-text">Admin has full access, Staff has limited access</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="password" name="password"
                                               minlength="6">
                                        <div class="form-text">Leave blank to keep current password</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                               minlength="6">
                                        <div class="form-text">Re-enter the new password</div>
                                    </div>

                                    <div class="col-12">
                                        <div class="alert alert-info" role="alert">
                                            <i class="bi bi-info-circle-fill me-2"></i>
                                            <strong>User Information:</strong><br>
                                            Created: <?= date('M j, Y \a\t g:i A', strtotime($user['created_at'])) ?><br>
                                            User ID: <?= $user['id'] ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-3 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Update User
                                    </button>
                                    <a href="users.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back to Users
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

<script>
// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;

    if (password && password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('password').addEventListener('input', function() {
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword.value) {
        if (this.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
});
</script>
</body>
</html>
