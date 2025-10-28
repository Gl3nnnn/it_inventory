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

// Get all notifications for the current user, with a limit
$query = "SELECT id, title, message, link, type, is_read, created_at FROM notifications
          WHERE user_id = ?
          ORDER BY created_at DESC
          LIMIT 50"; // Limit to last 50 notifications

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'link' => $row['link'],
        'type' => $row['type'],
        'is_read' => (bool)$row['is_read'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();

// Get the count of unread notifications for the badge
$unread_count_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE";
$unread_stmt = $conn->prepare($unread_count_query);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result()->fetch_assoc();
$unread_count = (int)$unread_result['unread_count'];
$unread_stmt->close();

header('Content-Type: application/json');
echo json_encode(['notifications' => $notifications, 'unread_count' => $unread_count]);
?>