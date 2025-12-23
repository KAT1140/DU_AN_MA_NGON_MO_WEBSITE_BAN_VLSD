<?php
// Script tạm để thêm cột province
$conn = new mysqli('localhost', 'root', '', 'vlxd_store1');

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$sql = "ALTER TABLE orders ADD COLUMN province VARCHAR(100) AFTER customer_address";

if ($conn->query($sql)) {
    echo "Column 'province' added successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
