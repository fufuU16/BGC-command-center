<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "bgc_database");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);
$imagePath = $data['imagePath'];
$count = $data['count'];

// Update the passenger count in the images table
$stmt = $conn->prepare("UPDATE images SET passenger_count = ? WHERE image_path = ?");
$stmt->bind_param("is", $count, $imagePath);

if ($stmt->execute()) {
    // Fetch the bus_id associated with the image
    $stmt = $conn->prepare("SELECT bus_id FROM images WHERE image_path = ?");
    $stmt->bind_param("s", $imagePath);
    $stmt->execute();
    $result = $stmt->get_result();
    $busData = $result->fetch_assoc();
    $busId = $busData['bus_id'];

    // Update the passenger count in the bus_stop_details table
    $stmt = $conn->prepare("UPDATE bus_stop_details SET passenger_count = ? WHERE bus_no = ?");
    $stmt->bind_param("is", $count, $busId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Passenger count updated in both tables']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating passenger count in bus_stop_details']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating passenger count in images']);
}

$stmt->close();
$conn->close();
?>