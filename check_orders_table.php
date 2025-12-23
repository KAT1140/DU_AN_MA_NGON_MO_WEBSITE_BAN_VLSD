<?php
$conn = new mysqli('localhost', 'root', '', 'vlxd_store1');

if ($conn->connect_error) {
    die('Connection failed');
}

echo "<h3>Structure of orders table:</h3>";
echo "<pre>";
$result = $conn->query('DESCRIBE orders');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "</pre>";

$conn->close();
?>
