<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/db.php';
require __DIR__ . '/activity_logger.php';

// Get ID
if (!isset($_GET['id'])) {
    header("Location: assets.php");
    exit();
}
$id = intval($_GET['id']);

// Fetch asset
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$asset = $result->fetch_assoc();

if (!$asset) {
    echo "❌ Asset not found.";
    exit();
}

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $asset_tag  = trim($_POST['asset_tag']);
    $asset_name = trim($_POST['asset_name']);
    $category   = trim($_POST['category']);
    $location   = trim($_POST['location']);
    $status     = trim($_POST['status']);
    $lifespan   = !empty($_POST['item_lifespan']) ? intval($_POST['item_lifespan']) : NULL;
    $disposal   = !empty($_POST['disposal_method']) ? $_POST['disposal_method'] : NULL;
    $acquisition = !empty($_POST['acquisition_date']) ? $_POST['acquisition_date'] : NULL;

    // Handle photo upload
    $photoPath = $asset['photo_path']; // Keep existing photo by default
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        // Remove existing photo
        if ($photoPath && file_exists($photoPath)) {
            unlink($photoPath);
        }
        $photoPath = NULL;
    } elseif (isset($_FILES['asset_photo']) && $_FILES['asset_photo']['error'] === UPLOAD_ERR_OK) {
        // Upload new photo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['asset_photo']['type'], $allowedTypes) && $_FILES['asset_photo']['size'] <= $maxSize) {
            // Remove old photo if exists
            if ($photoPath && file_exists($photoPath)) {
                unlink($photoPath);
            }

            $fileName = uniqid('asset_photo_') . '_' . time() . '.' . pathinfo($_FILES['asset_photo']['name'], PATHINFO_EXTENSION);
            $photoPath = 'uploads/assets/photos/' . $fileName;

            if (!move_uploaded_file($_FILES['asset_photo']['tmp_name'], $photoPath)) {
                $_SESSION['error'] = 'Failed to upload photo.';
                header("Location: edit_asset.php?id=$id");
                exit();
            }
        } else {
            $_SESSION['error'] = 'Invalid photo file. Only JPG, PNG, GIF, WEBP allowed. Max size: 5MB.';
            header("Location: edit_asset.php?id=$id");
            exit();
        }
    }

    // Handle document uploads
    $existingDocuments = json_decode($asset['document_paths'] ?? '[]', true) ?: [];
    $documentsToRemove = isset($_POST['remove_documents']) ? json_decode($_POST['remove_documents'], true) : [];

    // Remove selected documents
    foreach ($documentsToRemove as $docPath) {
        if (($key = array_search($docPath, array_column($existingDocuments, 'path'))) !== false) {
            if (file_exists($existingDocuments[$key]['path'])) {
                unlink($existingDocuments[$key]['path']);
            }
            unset($existingDocuments[$key]);
        }
    }
    $existingDocuments = array_values($existingDocuments); // Reindex array

    // Add new documents
    if (isset($_FILES['asset_documents'])) {
        $allowedDocTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];
        $maxDocSize = 10 * 1024 * 1024; // 10MB per document

        foreach ($_FILES['asset_documents']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['asset_documents']['error'][$key] === UPLOAD_ERR_OK) {
                if (in_array($_FILES['asset_documents']['type'][$key], $allowedDocTypes) &&
                    $_FILES['asset_documents']['size'][$key] <= $maxDocSize) {

                    $fileName = uniqid('asset_doc_') . '_' . time() . '_' . $key . '.' .
                               pathinfo($_FILES['asset_documents']['name'][$key], PATHINFO_EXTENSION);
                    $docPath = 'uploads/assets/documents/' . $fileName;

                    if (move_uploaded_file($tmpName, $docPath)) {
                        $existingDocuments[] = [
                            'name' => $_FILES['asset_documents']['name'][$key],
                            'path' => $docPath,
                            'size' => $_FILES['asset_documents']['size'][$key],
                            'type' => $_FILES['asset_documents']['type'][$key]
                        ];
                    }
                }
            }
        }
    }

    $documentJson = !empty($existingDocuments) ? json_encode($existingDocuments) : NULL;

    $stmt = $conn->prepare("UPDATE assets SET asset_tag=?, asset_name=?, category=?, location=?, status=?, item_lifespan=?, disposal_method=?, acquisition_date=?, photo_path=?, document_paths=? WHERE id=?");

    // Create variables for bind_param to handle NULL values properly
    $bind_asset_tag = $asset_tag;
    $bind_asset_name = $asset_name;
    $bind_category = $category;
    $bind_location = $location;
    $bind_status = $status;
    $bind_lifespan = $lifespan;
    $bind_disposal = $disposal;
    $bind_acquisition = $acquisition;
    $bind_photoPath = $photoPath;
    $bind_documentJson = $documentJson;
    $bind_id = $id;

    $stmt->bind_param("sssssissssi",
        $bind_asset_tag,
        $bind_asset_name,
        $bind_category,
        $bind_location,
        $bind_status,
        $bind_lifespan,
        $bind_disposal,
        $bind_acquisition,
        $bind_photoPath,
        $bind_documentJson,
        $bind_id
    );

    if ($stmt->execute()) {
        // Log the activity
        $details = "Asset details updated";
        if ($asset['status'] !== $status) {
            $details = "Status changed from '{$asset['status']}' to '$status'";
            logActivity($conn, 'Status Changed', $asset_name, $asset_tag, $details);
        } else {
            logActivity($conn, 'Asset Updated', $asset_name, $asset_tag, $details);
        }

        $_SESSION['success'] = 'Asset updated successfully!';
        header("Location: assets.php");
        exit();
    } else {
        echo "❌ Update failed: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="3.png">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Asset - IT Asset Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
  <link rel="stylesheet" href="style.css">
    <style>
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }
        .file-upload-area:hover, .file-upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e7f3ff;
        }
        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .upload-text {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        .upload-subtext {
            color: #6c757d;
            font-size: 0.875rem;
        }
        .file-preview img {
            max-width: 100%;
            max-height: 200px;
            object-fit: cover;
        }
        .file-list {
            margin-top: 1rem;
        }
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            background-color: #f8f9fa;
        }
        .file-item .file-info {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        .file-item .file-icon {
            margin-right: 0.5rem;
            color: #0d6efd;
        }
        .file-item .file-name {
            font-weight: 500;
            color: #495057;
        }
        .file-item .file-size {
            color: #6c757d;
            font-size: 0.875rem;
            margin-left: 0.5rem;
        }
        .file-item .remove-file {
            color: #dc3545;
            cursor: pointer;
            padding: 0.25rem;
        }
        .file-item .remove-file:hover {
            color: #b02a37;
        }
        .existing-files {
            margin-top: 1rem;
        }
        .existing-files .file-item {
            background-color: #e9ecef;
        }
    </style>
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
            <h2>Edit Asset</h2>
            <p class="page-subtitle">Modify the details of an existing asset in the inventory</p>
        </div>
    </div>

    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-md-6">
                    <label for="asset_tag" class="form-label required-field">Asset Tag</label>
                    <input type="text" class="form-control" id="asset_tag" name="asset_tag" value="<?= htmlspecialchars($asset['asset_tag']) ?>" required readonly />
                </div>
                <div class="col-md-6">
                    <label for="asset_name" class="form-label required-field">Asset Name</label>
                    <input type="text" class="form-control" id="asset_name" name="asset_name" value="<?= htmlspecialchars($asset['asset_name']) ?>" required placeholder="Enter asset name (e.g., Dell Laptop)" />
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="category" class="form-label required-field">Category</label>
                    <input type="text" class="form-control" id="category" name="category" value="<?= htmlspecialchars($asset['category']) ?>" required placeholder="Enter category (e.g., Laptop, Monitor)" />
                </div>
                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($asset['location']) ?>" placeholder="Enter location (e.g., Room 101)" />
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="status" class="form-label required-field">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="" disabled>Select status</option>
                        <option <?= $asset['status'] === 'In Storage' ? 'selected' : '' ?>>In Storage</option>
                        <option <?= $asset['status'] === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                        <option <?= $asset['status'] === 'Under Repair' ? 'selected' : '' ?>>Under Repair</option>
                        <option <?= $asset['status'] === 'Disposed' ? 'selected' : '' ?>>Disposed</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="item_lifespan" class="form-label">Item Lifespan (Years)</label>
                    <input type="number" class="form-control" id="item_lifespan" name="item_lifespan" value="<?= htmlspecialchars($asset['item_lifespan'] ?? '') ?>" min="1" placeholder="Enter expected lifespan in years" />
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="disposal_method" class="form-label">Disposal Method</label>
                    <select class="form-select" id="disposal_method" name="disposal_method">
                        <option value="" <?= empty($asset['disposal_method']) ? 'selected' : '' ?>>Select disposal method</option>
                        <option <?= ($asset['disposal_method'] ?? '') === 'Recycle' ? 'selected' : '' ?>>Recycle</option>
                        <option <?= ($asset['disposal_method'] ?? '') === 'Sell' ? 'selected' : '' ?>>Sell</option>
                        <option <?= ($asset['disposal_method'] ?? '') === 'Donate' ? 'selected' : '' ?>>Donate</option>
                        <option <?= ($asset['disposal_method'] ?? '') === 'Destroy' ? 'selected' : '' ?>>Destroy</option>
                        <option <?= ($asset['disposal_method'] ?? '') === 'Return to Vendor' ? 'selected' : '' ?>>Return to Vendor</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="acquisition_date" class="form-label">Acquisition Date</label>
                    <input type="date" class="form-control" id="acquisition_date" name="acquisition_date" value="<?= htmlspecialchars($asset['acquisition_date'] ?? '') ?>" />
                </div>
            </div>

            <!-- File Upload Section -->
            <div class="row g-4">
                <div class="col-12">
                    <h5 class="mb-3">Asset Photo & Documents</h5>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="asset_photo" class="form-label">Asset Photo</label>
                    <?php if (!empty($asset['photo_path'])): ?>
                        <div class="mb-3">
                            <p class="text-muted">Current photo:</p>
                            <img src="<?= htmlspecialchars($asset['photo_path']) ?>" alt="Current asset photo" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_photo" name="remove_photo" value="1">
                                <label class="form-check-label" for="remove_photo">
                                    Remove current photo
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="file-upload-area" id="photo-upload-area">
                        <div class="file-upload-content">
                            <i class="bi bi-camera-fill upload-icon"></i>
                            <p class="upload-text">Click to upload or drag and drop</p>
                            <p class="upload-subtext">JPG, PNG, GIF, WEBP (max 5MB)</p>
                        </div>
                        <input type="file" class="form-control-file d-none" id="asset_photo" name="asset_photo" accept="image/*">
                        <div class="file-preview" id="photo-preview" style="display: none;">
                            <img id="photo-preview-img" src="" alt="Preview" class="img-thumbnail">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removePhoto()">Remove</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="asset_documents" class="form-label">Documents</label>
                    <?php
                    $existingDocuments = json_decode($asset['document_paths'] ?? '[]', true) ?: [];
                    if (!empty($existingDocuments)):
                    ?>
                        <div class="existing-files mb-3">
                            <p class="text-muted">Current documents:</p>
                            <?php foreach ($existingDocuments as $doc): ?>
                                <div class="file-item">
                                    <div class="file-info">
                                        <i class="bi bi-file-earmark-text-fill file-icon"></i>
                                        <span class="file-name"><?= htmlspecialchars($doc['name']) ?></span>
                                        <span class="file-size">(<?= number_format($doc['size'] / 1024 / 1024, 2) ?> MB)</span>
                                    </div>
                                    <div>
                                        <a href="<?= htmlspecialchars($doc['path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-existing-file" data-path="<?= htmlspecialchars($doc['path']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="file-upload-area" id="documents-upload-area">
                        <div class="file-upload-content">
                            <i class="bi bi-file-earmark-text-fill upload-icon"></i>
                            <p class="upload-text">Click to upload or drag and drop</p>
                            <p class="upload-subtext">PDF, DOC, DOCX, XLS, XLSX, TXT (max 10MB each)</p>
                        </div>
                        <input type="file" class="form-control-file d-none" id="asset_documents" name="asset_documents[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" multiple>
                        <div class="file-list" id="documents-list"></div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-5">
                <a href="assets.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Assets
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Help Card -->
    <div class="info-card">
        <div class="info-card-header">
            <i class="bi bi-info-circle-fill"></i>
            <span>About Editing Assets</span>
        </div>
        <div class="info-card-body">
            <p>
                <strong>Asset Tag:</strong> A unique identifier for the asset (cannot be changed).<br>
                <strong>Asset Name:</strong> The descriptive name of the asset.<br>
                <strong>Category:</strong> The type of asset (e.g., Laptop, Monitor, Keyboard).<br>
                <strong>Location:</strong> Current location of the asset.<br>
                <strong>Status:</strong> Current state of the asset in the inventory.<br>
                <strong>Item Lifespan:</strong> Expected lifespan of the asset in years.<br>
                <strong>Disposal Method:</strong> How the asset should be disposed of when retired.<br>
                <strong>Acquisition Date:</strong> Date when the asset was acquired.
            </p>
        </div>
    </div>
</div>

    <script>
        let documentsToRemove = [];

        // Photo upload functionality
        document.getElementById('photo-upload-area').addEventListener('click', function() {
            document.getElementById('asset_photo').click();
        });

        document.getElementById('asset_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photo-preview-img').src = e.target.result;
                    document.getElementById('photo-preview').style.display = 'block';
                    document.querySelector('#photo-upload-area .file-upload-content').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        function removePhoto() {
            document.getElementById('asset_photo').value = '';
            document.getElementById('photo-preview').style.display = 'none';
            document.querySelector('#photo-upload-area .file-upload-content').style.display = 'block';
        }

        // Drag and drop for photo
        const photoArea = document.getElementById('photo-upload-area');
        photoArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        photoArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        photoArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('asset_photo').files = files;
                document.getElementById('asset_photo').dispatchEvent(new Event('change'));
            }
        });

        // Documents upload functionality
        document.getElementById('documents-upload-area').addEventListener('click', function() {
            document.getElementById('asset_documents').click();
        });

        document.getElementById('asset_documents').addEventListener('change', function(e) {
            updateDocumentList(e.target.files);
        });

        function updateDocumentList(files) {
            const list = document.getElementById('documents-list');
            list.innerHTML = '';

            Array.from(files).forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'file-item';
                item.innerHTML = `
                    <div class="file-info">
                        <i class="bi bi-file-earmark-text-fill file-icon"></i>
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">(${formatFileSize(file.size)})</span>
                    </div>
                    <i class="bi bi-x-circle-fill remove-file" onclick="removeDocument(${index})"></i>
                `;
                list.appendChild(item);
            });
        }

        function removeDocument(index) {
            const input = document.getElementById('asset_documents');
            const dt = new DataTransfer();
            const files = Array.from(input.files);

            files.splice(index, 1);
            files.forEach(file => dt.items.add(file));
            input.files = dt.files;

            updateDocumentList(input.files);
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Drag and drop for documents
        const documentsArea = document.getElementById('documents-upload-area');
        documentsArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        documentsArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        documentsArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById('asset_documents');
                const dt = new DataTransfer();

                // Add existing files
                Array.from(input.files).forEach(file => dt.items.add(file));
                // Add dropped files
                Array.from(files).forEach(file => dt.items.add(file));

                input.files = dt.files;
                updateDocumentList(input.files);
            }
        });

        // Handle removing existing documents
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-existing-file')) {
                e.preventDefault();
                const path = e.target.getAttribute('data-path');
                documentsToRemove.push(path);
                e.target.closest('.file-item').remove();
            }
        });

        // Update form submission to include documents to remove
        document.querySelector('form').addEventListener('submit', function() {
            if (documentsToRemove.length > 0) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_documents';
                input.value = JSON.stringify(documentsToRemove);
                this.appendChild(input);
            }
        });
    </script>

  <?php require __DIR__ . '/footer.php'; ?>
