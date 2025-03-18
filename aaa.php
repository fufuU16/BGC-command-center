<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "bgc_database";

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bus_no = $_POST['bus_no'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $eta = $_POST['eta'] ?? ''; // ETA can be calculated or received

    if (!empty($bus_no) && !empty($latitude) && !empty($longitude) && !empty($eta)) {
        // Update the bus_stop_details table with GPS data
        $stmt = $conn->prepare("UPDATE bus_stop_details SET latitude = ?, longitude = ?, eta = ?, timestamp = NOW() WHERE bus_no = ?");
        $stmt->bind_param("ddsi", $latitude, $longitude, $eta, $bus_no);

        if ($stmt->execute()) {
            echo "GPS data successfully updated for Bus No: $bus_no";
        } else {
            echo "Error updating GPS data: " . $conn->error;
        }

        $stmt->close();
    } else {
        echo "Invalid or missing data.";
    }
} else {
    echo "Invalid request method.";
}

// Close the database connection
$conn->close();
?>
