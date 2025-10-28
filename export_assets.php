<?php
session_start();
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

setSecurityHeaders();
secureSession();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check user role if needed for export permission
// if ($_SESSION['role'] !== 'admin') {
//     http_response_code(403);
//     echo "Forbidden";
//     exit();
// }

$format = $_GET['format'] ?? 'csv';
$allowed_formats = ['csv', 'xls'];

if (!in_array($format, $allowed_formats)) {
    http_response_code(400);
    echo "Invalid format";
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$sql = "SELECT asset_tag, asset_name, category, status, location, item_lifespan, disposal_method, acquisition_date, created_at FROM assets";
$result = $conn->query($sql);

if (!$result) {
    $_SESSION['error'] = "Failed to fetch assets for export.";
    header("Location: assets.php");
    exit();
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="assets_export.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Asset Tag', 'Asset Name', 'Category', 'Status', 'Location', 'Item Lifespan', 'Disposal Method', 'Acquisition Date', 'Created At']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
} else {
    // XLS export
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = ['Asset Tag', 'Asset Name', 'Category', 'Status', 'Location', 'Item Lifespan', 'Disposal Method', 'Acquisition Date', 'Created At'];
    $sheet->fromArray($headers, NULL, 'A1');

    $row_num = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->fromArray(array_values($row), NULL, 'A' . $row_num);
        $row_num++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="assets_export.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
}
?>
