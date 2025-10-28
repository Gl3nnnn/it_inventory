<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all unread notifications as read for the current user
$query = "UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notifications marked as read']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to mark notifications as read']);
}

$stmt->close();
?>
