<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/db.php';


if ($conn) {
    echo "✅ Database connection works!<br>";

    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "Tables in DB:<br>";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "⚠️ Query failed: " . $conn->error;
    }
} else {
    echo "❌ Connection failed.";
}
