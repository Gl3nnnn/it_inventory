<?php
// Include security functions
require_once __DIR__ . '/security.php';

// Secure database configuration
$host = "localhost";
$user = "root";
$pass = "";
$db   = "it_inventory";

// Create secure database connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    logSecurityEvent('Database Connection Failed', $conn->connect_error);
    die("❌ Connection failed: " . $conn->connect_error);
}

// Set charset to prevent encoding issues
$conn->set_charset("utf8mb4");

// Enable strict SQL mode for better security
$conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");

// Add photo_path and document_paths columns to assets table if they don't exist
$alterQuery = "ALTER TABLE assets
    ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS document_paths JSON DEFAULT NULL";
$conn->query($alterQuery);
?>
