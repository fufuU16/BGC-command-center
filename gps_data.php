
<?php
// Database Configuration
$host = 'bgcdb.mysql.database.azure.com'; // Azure database host
$username = 'judymalahay'; // Azure database username
$password = 'Malahayj123'; // Your Azure database password
$database = 'bgc_database'; // Your Azure database name

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "❌ Database connection failed: " . $conn->connect_error]));
}

// Get Data from ESP32 (Supports POST and GET)
$bus_id = $_REQUEST['bus_id'] ?? '';
$latitude = $_REQUEST['latitude'] ?? '';
$longitude = $_REQUEST['longitude'] ?? '';
$current_stop = $_REQUEST['current_stop'] ?? 'On Route';
$next_stop = $_REQUEST['next_stop'] ?? 'Unknown';
$eta = $_REQUEST['eta'] ?? '7'; // Placeholder ETA if not provided

// Validate Data
if (!empty($bus_id) && is_numeric($latitude) && is_numeric($longitude)) {
    // Check if the bus_id exists in `bus_details`
    $checkStmt = $conn->prepare("SELECT bus_id FROM bus_details WHERE bus_id = ?");
    $checkStmt->bind_param("s", $bus_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Since bus_id is the correct reference, use it directly
        $bus_number = $bus_id;

        // Insert or Update Data in `bus_stop_details`
        // If a record with the same bus_no exists, it will update the existing record.
        // Otherwise, it will insert a new record.
        $stmt = $conn->prepare("
            INSERT INTO bus_stop_details 
            (bus_no, latitude, longitude, current_stop, next_stop, eta, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                current_stop = VALUES(current_stop),
                next_stop = VALUES(next_stop),
                eta = VALUES(eta),
                timestamp = NOW()
        ");
        $stmt->bind_param("sddsss", $bus_number, $latitude, $longitude, $current_stop, $next_stop, $eta);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "✅ GPS Data Successfully Updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => "❌ Error Updating Data: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "❗ Invalid Bus ID"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "❗ Missing or Invalid GPS Data"]);
}

$conn->close();
?>
