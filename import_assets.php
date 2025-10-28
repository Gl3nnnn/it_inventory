<?php
session_start();
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

setSecurityHeaders();
secureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    $_SESSION['error'] = "❌ Security validation failed. Please try again.";
    header("Location: assets.php");
    exit();
}

// Check if file uploaded
if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "❌ File upload failed.";
    header("Location: assets.php");
    exit();
}

$file = $_FILES['import_file'];
$allowed_types = [
    'text/csv',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    $_SESSION['error'] = "❌ Invalid file type. Only CSV and XLS/XLSX are allowed.";
    header("Location: assets.php");
    exit();
}

// Use PhpSpreadsheet for XLS/XLSX parsing
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$imported_rows = 0;
$errors = [];

try {
    if (in_array(strtolower($extension), ['xls', 'xlsx'])) {
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
    } else {
        // CSV parsing
        $rows = array_map('str_getcsv', file($file['tmp_name']));
    }

    // Assuming first row is header
    $header = array_map('strtolower', $rows[0]);
    $required_columns = ['asset_tag', 'asset_name', 'category', 'status'];

    foreach ($required_columns as $col) {
        if (!in_array($col, $header)) {
            $errors[] = "Missing required column: $col";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: assets.php");
        exit();
    }

    // Map header columns to indexes
    $col_indexes = array_flip($header);

    // Process data rows
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        // Basic validation
        $asset_tag = trim($row[$col_indexes['asset_tag']] ?? '');
        $asset_name = trim($row[$col_indexes['asset_name']] ?? '');
        $category = trim($row[$col_indexes['category']] ?? '');
        $status = trim($row[$col_indexes['status']] ?? '');
        $location = trim($row[$col_indexes['location']] ?? '');
        $item_lifespan = !empty($row[$col_indexes['item_lifespan']] ?? '') ? intval($row[$col_indexes['item_lifespan']] ?? '') : NULL;
        $disposal_method = trim($row[$col_indexes['disposal_method']] ?? '');
        $acquisition_date_raw = trim($row[$col_indexes['acquisition_date']] ?? '');
        $acquisition_date = !empty($acquisition_date_raw) ? $acquisition_date_raw : NULL;

        if (empty($asset_tag) || empty($asset_name)) {
            $errors[] = "Row $i: Asset Tag and Asset Name are required.";
            continue;
        }

        // Validate status
        $valid_statuses = ['In Storage', 'Assigned', 'Under Repair', 'Disposed'];
        if (!in_array($status, $valid_statuses)) {
            $errors[] = "Row $i: Invalid status '$status'. Must be one of: " . implode(', ', $valid_statuses);
            continue;
        }

        // Insert or update asset
        $stmt = $conn->prepare("SELECT id FROM assets WHERE asset_tag = ?");
        $stmt->bind_param("s", $asset_tag);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update existing asset
            $stmt->bind_result($id);
            $stmt->fetch();
            $update_stmt = $conn->prepare("UPDATE assets SET asset_name=?, category=?, status=?, location=?, item_lifespan=?, disposal_method=?, acquisition_date=? WHERE id=?");
            $update_stmt->bind_param("ssssissi", $asset_name, $category, $status, $location, $item_lifespan, $disposal_method, $acquisition_date, $id);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new asset
            $insert_stmt = $conn->prepare("INSERT INTO assets (asset_tag, asset_name, category, status, location, item_lifespan, disposal_method, acquisition_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssssiss", $asset_tag, $asset_name, $category, $status, $location, $item_lifespan, $disposal_method, $acquisition_date);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $stmt->close();
        $imported_rows++;
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    } else {
        $_SESSION['success'] = "Successfully imported $imported_rows assets.";
    }
    header("Location: assets.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = "Error processing file: " . $e->getMessage();
    header("Location: assets.php");
    exit();
}
?>
