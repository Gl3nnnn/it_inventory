<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require __DIR__ . '/db.php';

// Build query same as assets.php
$where = [];
if (!empty($_GET['search'])) {
    $s = "%" . $conn->real_escape_string($_GET['search']) . "%";
    $where[] = "(asset_tag LIKE '$s' OR asset_name LIKE '$s' OR category LIKE '$s')";
}
if (!empty($_GET['status'])) {
    $st = $conn->real_escape_string($_GET['status']);
    $where[] = "status='$st'";
}
if (!empty($_GET['category'])) {
    $cat = $conn->real_escape_string($_GET['category']);
    $where[] = "category='$cat'";
}
if (!empty($_GET['location'])) {
    $loc = $conn->real_escape_string($_GET['location']);
    $where[] = "location='$loc'";
}
if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $df = $conn->real_escape_string($_GET['date_from']);
    $dt = $conn->real_escape_string($_GET['date_to']);
    $where[] = "DATE(created_at) BETWEEN '$df' AND '$dt'";
}

$sql = "SELECT * FROM assets";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=assets_export_' . date("Y-m-d") . '.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, ['ID', 'Asset Tag', 'Asset Name', 'Category', 'Location', 'Status', 'Item Lifespan', 'Disposal Method', 'Acquisition Date', 'Created At']);

// Write rows
while ($row = $result->fetch_assoc()) {
    // Format dates for Excel
    $created_at = '"' . date("Y-m-d H:i:s", strtotime($row['created_at'])) . '"';
    $acquisition_date = !empty($row['acquisition_date']) ? '"' . date("Y-m-d", strtotime($row['acquisition_date'])) . '"' : '';

    fputcsv($output, [
        $row['id'],
        "'" . $row['asset_tag'],   // <-- single quote forces text in Excel
        $row['asset_name'],
        $row['category'],
        $row['location'],
        $row['status'],
        $row['item_lifespan'],
        $row['disposal_method'],
        $acquisition_date,
        $created_at
    ]);
}

fclose($output);
exit();
