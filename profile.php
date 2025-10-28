<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/db.php';
require __DIR__ . '/languages.php';
$current_lang = $_SESSION['language'] ?? 'en';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = 'Only JPG, PNG, and GIF files are allowed.';
        } elseif ($file['size'] > $max_size) {
            $_SESSION['error'] = 'File size must be less than 2MB.';
        } else {
            $upload_dir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database with new profile picture path
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->bind_param("si", $filename, $_SESSION['user_id']);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Profile picture updated successfully!';
                    $_SESSION['profile_picture'] = $filename;
                } else {
                    $_SESSION['error'] = 'Failed to update profile picture.';
                }
            } else {
                $_SESSION['error'] = 'Failed to upload file.';
            }
        }
    } else {
        $_SESSION['error'] = 'Please select a file to upload.';
    }

    header("Location: profile.php");
    exit();
}

// Handle display name change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_display_name'])) {
    $display_name = trim($_POST['display_name'] ?? '');

    if (empty($display_name)) {
        $_SESSION['error'] = 'Display name cannot be empty.';
    } elseif (strlen($display_name) > 50) {
        $_SESSION['error'] = 'Display name must be less than 50 characters.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET display_name = ? WHERE id = ?");
        $stmt->bind_param("si", $display_name, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Display name updated successfully!';
            $_SESSION['display_name'] = $display_name;
        } else {
            $_SESSION['error'] = 'Failed to update display name.';
        }
    }

    header("Location: profile.php");
    exit();
}

// Handle language preference change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_language'])) {
    $language = $_POST['language'] ?? 'en';

    $stmt = $conn->prepare("UPDATE users SET language = ? WHERE id = ?");
    $stmt->bind_param("si", $language, $_SESSION['user_id']);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Language preference updated successfully!';
        $_SESSION['language'] = $language;
    } else {
        $_SESSION['error'] = 'Failed to update language preference.';
    }

    header("Location: profile.php");
    exit();
}

// Handle data export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_data'])) {
    // Get user data
    $stmt = $conn->prepare("SELECT username, display_name, role, created_at, language FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();

    // Get user's assets
    $stmt = $conn->prepare("SELECT asset_name, asset_tag, category, status, location, created_at FROM assets WHERE created_by = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $assets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get user's activities
    $stmt = $conn->prepare("SELECT action, details, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Create export data
    $export_data = [
        'export_date' => date('Y-m-d H:i:s'),
        'user_data' => $user_data,
        'assets' => $assets,
        'activities' => $activities
    ];

    // Generate JSON file
    $filename = 'user_data_' . $_SESSION['username'] . '_' . date('Y-m-d') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    exit();
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $_SESSION['error'] = 'New password must be at least 8 characters long.';
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);

            if ($update_stmt->execute()) {
                $_SESSION['success'] = 'Password changed successfully!';
            } else {
                $_SESSION['error'] = 'Failed to update password. Please try again.';
            }
        } else {
            $_SESSION['error'] = 'Current password is incorrect.';
        }
    }

    header("Location: profile.php");
    exit();
}

// Get user information
$stmt = $conn->prepare("SELECT username, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = 'User not found.';
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang[$current_lang]['profile'] ?> - <?= $lang[$current_lang]['it_asset_inventory'] ?></title>
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
                    <i class="bi bi-person-circle-fill"></i>
                </div>
                <div>
                    <h2><?= $lang[$current_lang]['my_profile'] ?></h2>
                    <p class="page-subtitle"><?= $lang[$current_lang]['manage_account_settings'] ?></p>
                </div>
            </div>

            <!-- Profile Picture Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="form-container">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-camera-fill me-2"></i><?= $lang[$current_lang]['profile_picture'] ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <div class="profile-picture-container">
                                        <?php if (isset($_SESSION['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $_SESSION['profile_picture'])): ?>
                                            <img src="uploads/profiles/<?= htmlspecialchars($_SESSION['profile_picture']) ?>" alt="Profile Picture" class="profile-picture" id="profilePicture" onclick="openImageModal()">
                                        <?php else: ?>
                                            <div class="profile-picture-placeholder">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <form method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="upload_picture" value="1">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <input type="file" name="profile_picture" class="form-control" accept="image/*" required>
                                                <div class="form-text"><?= $lang[$current_lang]['supported_formats'] ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="bi bi-upload me-2"></i><?= $lang[$current_lang]['upload'] ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Profile Information & Display Name -->
                <div class="col-lg-6">
                    <div class="form-container">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i><?= $lang[$current_lang]['profile_information'] ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label"><?= $lang[$current_lang]['username'] ?></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label"><?= $lang[$current_lang]['display_name'] ?></label>
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="change_display_name" value="1">
                                        <input type="text" name="display_name" class="form-control" value="<?= htmlspecialchars($_SESSION['display_name'] ?? $user['username']) ?>" maxlength="50" required>
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="col-12">
                                    <label class="form-label"><?= $lang[$current_lang]['role'] ?></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($user['role'])) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label"><?= $lang[$current_lang]['account_created'] ?></label>
                                    <input type="text" class="form-control" value="<?= date('F j, Y \a\t g:i A', strtotime($user['created_at'])) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-lg-6">
                    <div class="form-container">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i><?= $lang[$current_lang]['change_password'] ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="change_password" value="1">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label"><?= $lang[$current_lang]['current_password'] ?></label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label"><?= $lang[$current_lang]['new_password'] ?></label>
                                        <input type="password" name="new_password" class="form-control" required minlength="8">
                                        <div class="form-text"><?= $lang[$current_lang]['password_length'] ?></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label"><?= $lang[$current_lang]['confirm_new_password'] ?></label>
                                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-key-fill me-2"></i><?= $lang[$current_lang]['change_password'] ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Language Preferences -->
                <div class="col-lg-6">
                    <div class="form-container">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-translate me-2"></i><?= $lang[$current_lang]['language_preferences'] ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <input type="hidden" name="change_language" value="1">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label"><?= $lang[$current_lang]['preferred_language'] ?></label>
                                        <select name="language" class="form-select" required>
                                            <option value="en" <?= ($_SESSION['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                            <option value="es" <?= ($_SESSION['language'] ?? 'en') === 'es' ? 'selected' : '' ?>>Español</option>
                                            <option value="fr" <?= ($_SESSION['language'] ?? 'en') === 'fr' ? 'selected' : '' ?>>Français</option>
                                            <option value="de" <?= ($_SESSION['language'] ?? 'en') === 'de' ? 'selected' : '' ?>>Deutsch</option>
                                            <option value="zh" <?= ($_SESSION['language'] ?? 'en') === 'zh' ? 'selected' : '' ?>>中文</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-save me-2"></i><?= $lang[$current_lang]['save_language'] ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Data Export -->
                <div class="col-lg-6">
                    <div class="form-container">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-download me-2"></i><?= $lang[$current_lang]['export_account_data'] ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <p class="text-muted mb-3"><?= $lang[$current_lang]['download_account_data'] ?></p>
                                    <form method="post">
                                        <input type="hidden" name="export_data" value="1">
                                        <button type="submit" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-file-earmark-arrow-down me-2"></i><?= $lang[$current_lang]['export_my_data'] ?>
                                        </button>
                                    </form>
                                    <div class="form-text"><?= $lang[$current_lang]['data_exported_as_json'] ?></div>
                                </div>
                            </div>
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
</script>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Profile Picture" class="img-fluid rounded" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<script>
function openImageModal() {
    const profileImg = document.getElementById('profilePicture');
    const modalImg = document.getElementById('modalImage');
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));

    if (profileImg && modalImg) {
        modalImg.src = profileImg.src;
        modal.show();
    }
}
</script>

<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
