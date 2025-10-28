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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="3.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - <?= $lang[$current_lang]['it_asset_inventory'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
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
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <div>
                    <h2>QR Scanner</h2>
                    <p class="page-subtitle">Scan QR codes to quickly lookup assets</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-camera-video me-2"></i>Camera Scanner</h5>
                        </div>
                        <div class="card-body">
                            <div class="scanner-container">
                                <video id="preview" class="scanner-video"></video>
                                <div id="scanner-overlay" class="scanner-overlay">
                                    <div class="scanner-frame"></div>
                                    <div class="scanner-instructions">
                                        <i class="bi bi-qr-code-scan"></i>
                                        <p>Position QR code within the frame</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button id="startScan" class="btn btn-primary">
                                    <i class="bi bi-play-circle me-2"></i>Start Scanning
                                </button>
                                <button id="stopScan" class="btn btn-secondary" disabled>
                                    <i class="bi bi-stop-circle me-2"></i>Stop Scanning
                                </button>
                                <button id="switchCamera" class="btn btn-outline-secondary ms-2" disabled>
                                    <i class="bi bi-arrow-repeat me-2"></i>Switch Camera
                                </button>
                                <div id="scanStatus" class="mt-2"></div>
                                <div id="cameraWarning" class="alert alert-warning mt-2" style="display: none;">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Camera Access Required:</strong> For QR scanning to work, please ensure:
                                    <ul class="mb-0 mt-2">
                                        <li>The page is accessed via HTTPS (required by browsers)</li>
                                        <li>Camera permissions are granted when prompted</li>
                                        <li>Try refreshing the page and allowing camera access</li>
                                        <li>Use the "Switch Camera" button to toggle between front and back cameras</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Asset Lookup</h5>
                        </div>
                        <div class="card-body">
                            <form id="manualLookup" class="mb-3">
                                <div class="mb-3">
                                    <label for="assetTag" class="form-label">Asset Tag</label>
                                    <input type="text" class="form-control" id="assetTag" placeholder="Enter asset tag manually">
                                </div>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="bi bi-search me-2"></i>Lookup Asset
                                </button>
                            </form>

                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>How to use:</h6>
                                <ul class="mb-0 small">
                                    <li>Click "Start Scanning" to activate camera</li>
                                    <li>Point camera at QR code on asset sticker</li>
                                    <li>Asset details will appear automatically</li>
                                    <li>Or enter asset tag manually above</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Details Modal -->
            <div class="modal fade" id="assetModal" tabindex="-1" aria-labelledby="assetModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="assetModalLabel">Asset Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="assetDetails">
                            <!-- Asset details will be loaded here -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <a href="#" id="viewFullAsset" class="btn btn-primary">View Full Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let scanner = null;
let scanning = false;
let cameras = [];
let currentCameraIndex = 0;

document.getElementById('startScan').addEventListener('click', function() {
    if (scanning) return;

    const preview = document.getElementById('preview');
    const status = document.getElementById('scanStatus');

    scanner = new Instascan.Scanner({ video: preview, facingMode: 'environment' });

    scanner.addListener('scan', function(content) {
        console.log('QR Code scanned:', content);
        handleScannedData(content);
    });

    Instascan.Camera.getCameras().then(function(cameraList) {
        cameras = cameraList;
        if (cameras.length > 0) {
            // Prefer back camera: if multiple cameras, start with index 1 (usually back camera on mobile)
            // If only one camera, use index 0
            currentCameraIndex = cameras.length > 1 ? 1 : 0;
            scanner.start(cameras[currentCameraIndex]);
            scanning = true;
            status.innerHTML = '<div class="alert alert-success"><i class="bi bi-camera-video me-2"></i>Camera activated. Scanning for QR codes...</div>';
            document.getElementById('startScan').disabled = true;
            document.getElementById('stopScan').disabled = false;
            if (cameras.length > 1) {
                document.getElementById('switchCamera').disabled = false;
            }
        } else {
            status.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>No cameras found.</div>';
        }
    }).catch(function(e) {
        console.error(e);
        status.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Camera access denied or not supported.</div>';
        document.getElementById('cameraWarning').style.display = 'block';
    });
});

document.getElementById('stopScan').addEventListener('click', function() {
    if (scanner) {
        scanner.stop();
        scanning = false;
        document.getElementById('startScan').disabled = false;
        document.getElementById('stopScan').disabled = true;
        document.getElementById('switchCamera').disabled = true;
        document.getElementById('scanStatus').innerHTML = '<div class="alert alert-info"><i class="bi bi-stop-circle me-2"></i>Scanning stopped.</div>';
    }
});

document.getElementById('switchCamera').addEventListener('click', function() {
    if (scanner && cameras.length > 1) {
        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
        scanner.stop();
        scanner.start(cameras[currentCameraIndex]);
        document.getElementById('scanStatus').innerHTML = '<div class="alert alert-success"><i class="bi bi-camera-video me-2"></i>Switched to camera ' + (currentCameraIndex + 1) + ' of ' + cameras.length + '. Scanning for QR codes...</div>';
    }
});

function handleScannedData(content) {
    // Parse QR code content
    const lines = content.split('\n');
    let assetTag = null;

    // Try to extract asset tag from different formats
    for (let line of lines) {
        if (line.includes('Asset Tag:') || line.includes('TAG:')) {
            assetTag = line.split(':')[1]?.trim();
            break;
        }
    }

    // If no asset tag found in structured format, try to use the whole content as tag
    if (!assetTag) {
        assetTag = content.trim();
    }

    if (assetTag) {
        lookupAsset(assetTag);
    } else {
        document.getElementById('scanStatus').innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Could not extract asset tag from QR code.</div>';
    }
}

function lookupAsset(assetTag) {
    fetch('api_lookup_asset.php?tag=' + encodeURIComponent(assetTag))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAssetDetails(data.asset);
                document.getElementById('scanStatus').innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Asset found: ' + data.asset.asset_tag + '</div>';
            } else {
                document.getElementById('scanStatus').innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Asset not found: ' + assetTag + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('scanStatus').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Error looking up asset.</div>';
        });
}

document.getElementById('manualLookup').addEventListener('submit', function(e) {
    e.preventDefault();
    const assetTag = document.getElementById('assetTag').value.trim();
    if (assetTag) {
        lookupAsset(assetTag);
    }
});

function displayAssetDetails(asset) {
    const details = document.getElementById('assetDetails');
    const viewFullLink = document.getElementById('viewFullAsset');

    details.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Asset Tag:</strong></td><td>${asset.asset_tag}</td></tr>
                    <tr><td><strong>Name:</strong></td><td>${asset.asset_name}</td></tr>
                    <tr><td><strong>Category:</strong></td><td>${asset.category}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>${asset.status}</td></tr>
                    <tr><td><strong>Location:</strong></td><td>${asset.location || 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Additional Details</h6>
                <table class="table table-sm">
                    <tr><td><strong>Acquisition Date:</strong></td><td>${asset.acquisition_date || 'N/A'}</td></tr>
                    <tr><td><strong>Lifespan:</strong></td><td>${asset.item_lifespan ? asset.item_lifespan + ' years' : 'N/A'}</td></tr>
                    <tr><td><strong>Disposal Method:</strong></td><td>${asset.disposal_method || 'N/A'}</td></tr>
                    <tr><td><strong>Created:</strong></td><td>${new Date(asset.created_at).toLocaleDateString()}</td></tr>
                    <tr><td><strong>Last Updated:</strong></td><td>${asset.updated_at ? new Date(asset.updated_at).toLocaleDateString() : 'N/A'}</td></tr>
                </table>
            </div>
        </div>
        ${asset.description ? `<div class="mt-3"><h6>Description</h6><p>${asset.description}</p></div>` : ''}
    `;

    viewFullLink.href = `assets.php?id=${asset.id}`;

    const modal = new bootstrap.Modal(document.getElementById('assetModal'));
    modal.show();
}

// Stop scanning when page unloads
window.addEventListener('beforeunload', function() {
    if (scanner && scanning) {
        scanner.stop();
    }
});
</script>

<style>
.scanner-container {
    position: relative;
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
}

.scanner-video {
    width: 100%;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.scanner-frame {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 250px;
    height: 250px;
    border: 3px solid #007bff;
    border-radius: 8px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}

.scanner-instructions {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.scanner-instructions i {
    font-size: 2rem;
    display: block;
    margin-bottom: 10px;
}
</style>

<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
