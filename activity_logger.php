<?php
/**
 * Activity Logger Utility
 * Logs asset-related activities for tracking and dashboard display
 */

function logActivity($conn, $action, $asset_name, $asset_tag = null, $details = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';

    $stmt = $conn->prepare("INSERT INTO activities (action, asset_name, asset_tag, user_id, username, details) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $action, $asset_name, $asset_tag, $user_id, $username, $details);
    $stmt->execute();
    $stmt->close();
}

function getRecentActivities($conn, $limit = 10) {
    $result = $conn->query("SELECT action, asset_name, timestamp FROM activities ORDER BY timestamp DESC LIMIT $limit");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getActivitiesByUser($conn, $user_id, $limit = 20) {
    $stmt = $conn->prepare("SELECT action, asset_name, timestamp, details FROM activities WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getActivitiesByDateRange($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("SELECT * FROM activities WHERE DATE(timestamp) BETWEEN ? AND ? ORDER BY timestamp DESC");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
