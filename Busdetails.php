<?php
include 'db_connection.php'; // Ensure this file correctly sets up the database connection

$busDetails = null;
$maintenanceData = [];

if (isset($_GET['bus_id'])) {
    $busId = $_GET['bus_id'];

    // Query to fetch bus details
    $detailsQuery = "
        SELECT * FROM bus_details
        WHERE bus_id = ?
    ";

    if ($stmt = $conn->prepare($detailsQuery)) {
        $stmt->bind_param("s", $busId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $busDetails = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // Query to fetch the latest maintenance data for specific types
    $maintenanceQuery = "
        SELECT TypeofMaintenance, MAX(last_maintenance) as last_maintenance, odometer_at_maintenance
        FROM maintenance_data
        WHERE bus_id = ? AND TypeofMaintenance IN ('Tire Replacement', 'Oil Change', 'Brake Replacement')
        GROUP BY TypeofMaintenance
        ORDER BY TypeofMaintenance DESC
    ";

    if ($stmt = $conn->prepare($maintenanceQuery)) {
        $stmt->bind_param("s", $busId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $maintenanceData[$row['TypeofMaintenance']] = $row;
        }
        $stmt->close();
    }
}

$conn->close();

// Maintenance Calculation Logic
$totalOdometer = $busDetails['TotalOdometer'] ?? 0;

// Recommended intervals
$oilChangeInterval = 10000; // 10,000 km
$tireChangeInterval = 40000; // 40,000 km
$brakeCheckInterval = 30000; // 30,000 km

// Calculate required maintenance based on the latest maintenance data
$nextOilChange = ($maintenanceData['Oil Change']['odometer_at_maintenance'] ?? 0) + $oilChangeInterval;
$nextTireChange = ($maintenanceData['Tire Replacement']['odometer_at_maintenance'] ?? 0) + $tireChangeInterval;
$nextBrakeCheck = ($maintenanceData['Brake Replacement']['odometer_at_maintenance'] ?? 0) + $brakeCheckInterval;

// Warnings
$oilChangeNeeded = $totalOdometer >= $nextOilChange;
$tireChangeNeeded = $totalOdometer >= $nextTireChange;
$brakeCheckNeeded = $totalOdometer >= $nextBrakeCheck;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Details</title>
    <link rel="stylesheet" href="Busdetails.css">
</head>
<body>
<header>
    <?php
    session_start();
    if (!isset($_SESSION['username'])) {
        // Redirect to login page if not logged in
        header("Location: Login.php");
        exit();
    }

    // Assuming the user's role is stored in the session
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    include 'role_check.php';

    // Check if the user has the required role
    checkUserRole(['SuperAdmin', 'MidAdmin','Admin']);
    ?>
    <div class="header-content">
        <div class="username-display">
            <?php if (isset($_SESSION['username'])): ?>
                <span> <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <?php endif; ?>
        </div>
        <nav>
        <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
        <div class="dropdown">
                <a href="#" class="dropbtn <?php echo in_array($current_page, ['Shiftlogs.php', 'activity_logs.php', 'drivers.php']) ? 'active-dropdown' : ''; ?>">Logs</a>
                <div class="dropdown-content">
                    <a href="Shiftlogs.php" class="<?php echo $current_page == 'Shiftlogs.php' ? 'active' : ''; ?>">Shift Logs</a>
                    <?php if ($userRole == 'SuperAdmin'): ?>
                        <a href="activity_logs.php" class="<?php echo $current_page == 'activity_logs.php' ? 'active' : ''; ?>">Activity Logs</a>
                    <?php endif; ?>
                    <?php if (in_array($userRole, ['MidAdmin', 'SuperAdmin'])): ?>
                        <a href="drivers.php" class="<?php echo $current_page == 'drivers.php' ? 'active' : ''; ?>">Driver List</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dropdown">
                <a href="#" class="dropbtn <?php echo in_array($current_page, ['Maintenance.php', 'Schedulebus.php']) ? 'active-dropdown' : ''; ?>">Bus</a>
                <div class="dropdown-content">
                    <a href="Maintenance.php" class="<?php echo $current_page == 'Maintenance.php' ? 'active' : ''; ?>">Maintenance</a>
                    <?php if (in_array($userRole, ['MidAdmin', 'SuperAdmin'])): ?>
                        <a href="Schedulebus.php" class="<?php echo $current_page == 'Schedulebus.php' ? 'active' : ''; ?>">Bus Schedule</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dropdown">
                <a href="#" class="dropbtn <?php echo in_array($current_page, ['Passenger.php', 'Feedback.php']) ? 'active-dropdown' : ''; ?>">Passenger</a>
                <div class="dropdown-content">
                    <a href="Passenger.php" class="<?php echo $current_page == 'Passenger.php' ? 'active' : ''; ?>">Passenger Details</a>
                    <a href="Feedback.php" class="<?php echo $current_page == 'Feedback.php' ? 'active' : ''; ?>">Feedback</a>
                </div>
            </div>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>
    
    <main>
        <div class="Title">
            <h1>Bus Details</h1>
        </div>
        
        <div id="Busdetails">
            <?php if ($busDetails): ?>
                <h2>Bus Number: <?php echo htmlspecialchars($busDetails['bus_id']); ?></h2>
                <div class="detail-item"><span>Plate Number:</span> <span><?php echo htmlspecialchars($busDetails['plate_number']); ?></span></div>
                <div class="detail-item"><span>Chassis Number:</span> <span><?php echo htmlspecialchars($busDetails['chassis_number']); ?></span></div>
                <div class="detail-item"><span>Engine Number:</span> <span><?php echo htmlspecialchars($busDetails['engine_number']); ?></span></div>
                <div class="detail-item"><span>Seating Capacity:</span> <span><?php echo htmlspecialchars($busDetails['seating_capacity']); ?></span></div>
                <div class="detail-item"><span>Fuel Type:</span> <span><?php echo htmlspecialchars($busDetails['fuel_type']); ?></span></div>
                <div class="detail-item"><span>Next Scheduled Maintenance:</span> <span><?php echo htmlspecialchars($busDetails['next_scheduled_maintenance']); ?></span></div>
                <div class="detail-item"><span>Total Odometer:</span> <span><?php echo htmlspecialchars($busDetails['TotalOdometer']); ?></span></div>
                <div class="detail-item"><span>Driver 1:</span> <span><?php echo htmlspecialchars($busDetails['driver1']); ?></span></div>
                <div class="detail-item"><span>Driver 2:</span> <span><?php echo htmlspecialchars($busDetails['driver2']); ?></span></div>
                
                <h2>Maintenance Report</h2>
                <?php foreach ($maintenanceData as $maintenance): ?>
                    <!-- Display maintenance data if needed -->
                <?php endforeach; ?>

                <div class="detail-item" style="color: <?php echo $oilChangeNeeded ? 'red' : 'green'; ?>;">
                    <span>Oil Change Status:</span> 
                    <span><?php echo $oilChangeNeeded ? '⚠️ Required' : '✔️ Not Required'; ?></span>
                </div>
                <div class="detail-item" style="color: <?php echo $tireChangeNeeded ? 'red' : 'green'; ?>;">
                    <span>Tire Replacement Status:</span> 
                    <span><?php echo $tireChangeNeeded ? '⚠️ Required' : '✔️ Not Required'; ?></span>
                </div>
                <div class="detail-item" style="color: <?php echo $brakeCheckNeeded ? 'red' : 'green'; ?>;">
                    <span>Brake Check Status:</span> 
                    <span><?php echo $brakeCheckNeeded ? '⚠️ Required' : '✔️ Not Required'; ?></span>
                </div>

                <form action="EditBusdetails.php" method="GET">
                    <input type="hidden" name="bus_id" value="<?php echo htmlspecialchars($busDetails['bus_id']); ?>">
                    <button type="submit">Edit Details</button>
                </form>
            <?php else: ?>
                <p>No details found for the specified bus.</p>
            <?php endif; ?>
        </div>
    </main>
    <script src="Busdetails.js"></script>
</body>
</html>