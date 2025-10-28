<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/db.php';

// Protect endpoint
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Restrict access to admin only
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Get parameters
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$metric = isset($_GET['metric']) ? $_GET['metric'] : 'status';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

try {
    $labels = [];
    $values = [];

    // Build base WHERE clause
    $whereConditions = [];
    if (!empty($dateFrom) && !empty($dateTo)) {
        $whereConditions[] = "DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'";
    }
    if (!empty($categoryFilter)) {
        $whereConditions[] = "category = '" . $conn->real_escape_string($categoryFilter) . "'";
    }
    if (!empty($statusFilter)) {
        $whereConditions[] = "status = '" . $conn->real_escape_string($statusFilter) . "'";
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    switch ($metric) {
        case 'status':
            $query = "SELECT status, COUNT(*) as count FROM assets $whereClause GROUP BY status ORDER BY count DESC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['status'];
                $values[] = (int)$row['count'];
            }
            break;

        case 'category':
            $query = "SELECT category, COUNT(*) as count FROM assets $whereClause GROUP BY category ORDER BY count DESC LIMIT 10";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['category'];
                $values[] = (int)$row['count'];
            }
            break;

        case 'location':
            $query = "SELECT location, COUNT(*) as count FROM assets $whereClause AND location IS NOT NULL AND location != '' GROUP BY location ORDER BY count DESC LIMIT 10";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['location'];
                $values[] = (int)$row['count'];
            }
            break;

        case 'monthly':
            $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM assets $whereClause GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY month ASC";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['month'];
                $values[] = (int)$row['count'];
            }
            break;

        default:
            throw new Exception('Invalid metric specified');
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'labels' => $labels,
        'values' => $values,
        'metric' => $metric,
        'date_from' => $dateFrom,
        'date_to' => $dateTo
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
