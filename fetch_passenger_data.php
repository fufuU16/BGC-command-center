<?php
include 'db_connection.php';

$query = "
    SELECT date, route, SUM(passengers) as total_passengers 
    FROM passenger_data 
    GROUP BY date, route
    UNION ALL
    SELECT date, 'Overall' as route, SUM(passengers) as total_passengers 
    FROM passenger_data 
    GROUP BY date
    ORDER BY date ASC
";

$result = $conn->query($query);
$passengerData = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $passengerData[] = $row;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($passengerData);
exit;
?>
