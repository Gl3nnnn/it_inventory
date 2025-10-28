<?php
require __DIR__ . '/db.php';

echo "=== ASSETS TABLE STRUCTURE ===\n";
$result = $conn->query('DESCRIBE assets');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== SAMPLE ASSETS DATA ===\n";
$result = $conn->query('SELECT id, asset_name, status, created_at FROM assets LIMIT 3');
while($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Name: {$row['asset_name']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
}
?>
