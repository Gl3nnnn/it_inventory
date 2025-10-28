<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Function to generate notifications based on asset conditions
function generateNotifications($conn, $user_id) {
    $notifications = [];
    $today = date('Y-m-d');

    // Enhanced maintenance notifications
    try {
        // Check for maintenance scheduled for today
        $todayMaintenanceQuery = "SELECT COUNT(*) as count FROM maintenance m
                                 JOIN assets a ON m.asset_id = a.id
                                 WHERE m.scheduled_date = CURDATE()
                                 AND m.status IN ('Scheduled', 'In Progress')
                                 AND a.status != 'Disposed'";
        $result = $conn->query($todayMaintenanceQuery);
        $todayCount = $result->fetch_assoc()['count'];

        if ($todayCount > 0) {
            $notifications[] = [
                'title' => 'Maintenance Today',
                'message' => "You have $todayCount maintenance tasks scheduled for today",
                'link' => "maintenance.php?date_from=$today&date_to=$today",
                'type' => 'info'
            ];
        }

        // Check for maintenance scheduled for future days (next 7 days, excluding today)
        $futureMaintenanceQuery = "SELECT COUNT(*) as count FROM maintenance m
                                  JOIN assets a ON m.asset_id = a.id
                                  WHERE m.scheduled_date > CURDATE()
                                  AND m.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                                  AND m.status = 'Scheduled'
                                  AND a.status != 'Disposed'";
        $result = $conn->query($futureMaintenanceQuery);
        $futureCount = $result->fetch_assoc()['count'];

        if ($futureCount > 0) {
            $notifications[] = [
                'title' => 'Upcoming Maintenance',
                'message' => "You have $futureCount maintenance tasks scheduled for the coming week",
                'link' => 'maintenance.php?status=Scheduled',
                'type' => 'info'
            ];
        }

        // Check for overdue maintenance (past due date and not completed/cancelled)
        $overdueMaintenanceQuery = "SELECT COUNT(*) as count FROM maintenance m
                                   JOIN assets a ON m.asset_id = a.id
                                   WHERE m.scheduled_date < CURDATE()
                                   AND m.status NOT IN ('Completed', 'Cancelled')
                                   AND a.status != 'Disposed'";
        $result = $conn->query($overdueMaintenanceQuery);
        $overdueCount = $result->fetch_assoc()['count'];

        if ($overdueCount > 0) {
            $notifications[] = [
                'title' => 'Overdue Maintenance',
                'message' => "You have $overdueCount overdue maintenance tasks that need attention",
                'link' => 'maintenance.php?status=Overdue',
                'type' => 'danger'
            ];
        }

        // Fallback: Check for assets due for maintenance (legacy support)
        $maintenanceQuery = "SELECT COUNT(*) as count FROM assets WHERE maintenance_due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'Disposed'";
        $result = $conn->query($maintenanceQuery);
        $maintenanceCount = $result->fetch_assoc()['count'];

        if ($maintenanceCount > 0 && $todayCount == 0 && $futureCount == 0 && $overdueCount == 0) {
            $notifications[] = [
                'title' => 'Maintenance Due',
                'message' => "$maintenanceCount assets are due for maintenance this week",
                'link' => 'maintenance.php',
                'type' => 'warning'
            ];
        }
    } catch (Exception $e) {
        // Maintenance table might not exist, skip this notification
    }

    // Check for low stock items (if quantity column exists)
    try {
        $lowStockQuery = "SELECT COUNT(*) as count FROM assets WHERE quantity <= 2 AND status = 'In Storage'";
        $result = $conn->query($lowStockQuery);
        $lowStockCount = $result->fetch_assoc()['count'];

        if ($lowStockCount > 0) {
            $notifications[] = [
                'title' => 'Low Stock Alert',
                'message' => "$lowStockCount items are running low in stock",
                'link' => 'assets.php',
                'type' => 'danger'
            ];
        }
    } catch (Exception $e) {
        // Quantity column might not exist, skip this notification
    }

    // Check for assets under repair for too long
    try {
        $repairQuery = "SELECT COUNT(*) as count FROM assets WHERE status = 'Under Repair' AND created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $result = $conn->query($repairQuery);
        $longRepairCount = $result->fetch_assoc()['count'];

        if ($longRepairCount > 0) {
            $notifications[] = [
                'title' => 'Assets in Repair',
                'message' => "$longRepairCount assets have been in repair for over 30 days",
                'link' => 'assets.php?status=Under+Repair',
                'type' => 'warning'
            ];
        }
    } catch (Exception $e) {
        // Skip this notification if column doesn't exist
    }

    // Insert notifications into database, avoiding duplicates
    foreach ($notifications as $notification) {
        $title = $notification['title'];
        $type = $notification['type'];

        // Check for a similar recent, unread notification to prevent duplicates
        $checkStmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND title = ? AND type = ? AND is_read = FALSE AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $checkStmt->bind_param("iss", $user_id, $title, $type);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows === 0) {
            // No similar recent notification found, so insert it
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $user_id, $notification['title'], $notification['message'], $notification['link'], $notification['type']);
            $stmt->execute();
            $stmt->close();
        }
        $checkStmt->close();
    }

    return count($notifications);
}

// Generate notifications for current user
$user_id = $_SESSION['user_id'];
$generated = generateNotifications($conn, $user_id);
?>
