<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "bgc_database");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Get the bus_id from the query parameter
$busId = isset($_GET['bus_id']) ? $_GET['bus_id'] : '';

if ($busId) {
    // Retrieve the latest image for the specified bus
    $stmt = $conn->prepare("SELECT image_path FROM images WHERE bus_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $busId);
    $stmt->execute();
    $result = $stmt->get_result();
    $latestImage = $result->fetch_assoc();
    $stmt->close();
} else {
    $latestImage = null;
}

$conn->close();

if ($latestImage) {
    echo json_encode(['newImage' => $latestImage['image_path']]);
} else {
    echo json_encode(['newImage' => null]);
}
?>