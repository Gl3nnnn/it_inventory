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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['id']) ? (int)$data['id'] : 0;

if ($notification_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid notification ID']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Update the notification, ensuring it belongs to the current user and is unread
$stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ? AND is_read = FALSE");
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    // This can happen if the notification was already read or doesn't belong to the user.
    // For the client-side logic, this is not a critical error.
    echo json_encode(['success' => false, 'message' => 'Notification already marked as read or not found.']);
}

$stmt->close();
?>