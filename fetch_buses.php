<?php
header('Content-Type: application/json');

// Database connection
$host = 'bgcdb.mysql.database.azure.com'; // Azure database host
$username = 'judymalahay'; // Azure database username
$password = 'Malahayj123'; // Your Azure database password
$database = 'bgc_database'; // Your Azure database name

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch bus data with valid coordinates
$sql = "SELECT bus_no, IFNULL(latitude, 0) AS latitude, IFNULL(longitude, 0) AS longitude, 
        IFNULL(next_stop, 'N/A') AS next_stop, IFNULL(eta, 'Unknown') AS eta 
        FROM bus_stop_details 
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
$result = $conn->query($sql);

$busData = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $busData[] = $row;
    }
}

echo json_encode($busData);

$conn->close();
?>
