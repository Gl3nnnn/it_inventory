<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_GET['tag']) || empty($_GET['tag'])) {
    echo json_encode(['success' => false, 'message' => 'Asset tag is required']);
    exit();
}

$assetTag = trim($_GET['tag']);

// Prepare query to find asset by tag
$stmt = $conn->prepare("SELECT * FROM assets WHERE asset_tag = ?");
$stmt->bind_param("s", $assetTag);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $asset = $result->fetch_assoc();

    // Log the lookup activity
    require __DIR__ . '/activity_logger.php';
    logActivity($conn, 'Asset QR Scanned', $asset['asset_name'], $asset['asset_tag'], 'Asset looked up via QR scanner');

    echo json_encode([
        'success' => true,
        'asset' => $asset
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Asset not found'
    ]);
}

$stmt->close();
$conn->close();
?>
