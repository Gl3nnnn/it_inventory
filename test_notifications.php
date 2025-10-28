<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';

// Simulate user session for testing
$_SESSION['user_id'] = 1; // Assuming admin user ID is 1

echo "=== TESTING NOTIFICATION SYSTEM ===\n\n";

// Test 1: Check maintenance data
echo "1. MAINTENANCE DATA:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM maintenance m JOIN assets a ON m.asset_id = a.id WHERE m.scheduled_date = CURDATE() AND m.status IN ('Scheduled', 'In Progress') AND a.status != 'Disposed'");
$todayCount = $result->fetch_assoc()['count'];
echo "Today's maintenance count: $todayCount\n\n";

// Test 2: Generate notifications
echo "2. GENERATING NOTIFICATIONS:\n";
require __DIR__ . '/generate_notifications.php';
$generated = generateNotifications($conn, $_SESSION['user_id']);
echo "Generated notifications: $generated\n\n";

// Test 3: Check notifications in database
echo "3. NOTIFICATIONS IN DATABASE:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = 1 AND is_read = FALSE");
$unreadCount = $result->fetch_assoc()['count'];
echo "Unread notifications: $unreadCount\n\n";

$result = $conn->query("SELECT * FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 3");
while($row = $result->fetch_assoc()) {
    echo "- {$row['title']}: {$row['message']} (Type: {$row['type']}, Read: " . ($row['is_read'] ? 'Yes' : 'No') . ")\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
