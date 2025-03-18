<?php
include 'db_connection.php';

session_start();
$busDetails = null;

// Function to log activity
function logActivity($conn, $user_id, $username, $action) {
    $logQuery = "INSERT INTO activity_logs (user_id, username, action, timestamp) VALUES (?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($logQuery)) {
        $stmt->bind_param("iss", $user_id, $username, $action);
        $stmt->execute();
        $stmt->close();
    }
}

if (isset($_GET['bus_id'])) {
    $busId = $_GET['bus_id'];

    // Query to fetch bus details including TotalOdometer
    $detailsQuery = "SELECT *, TotalOdometer FROM bus_details WHERE bus_id = ?";
    if ($stmt = $conn->prepare($detailsQuery)) {
        $stmt->bind_param("s", $busId);
        $stmt->execute();
        $result = $stmt->get_result();
        $busDetails = $result->fetch_assoc() ?: null;
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentDate = date('Y-m-d');
    $updateType = $_POST['update_type'] ?? null;
    $newOdometer = isset($_POST['odometer']) && is_numeric($_POST['odometer']) ? $_POST['odometer'] : null;

    $fieldsToUpdate = array_filter([
        'TotalOdometer' => $newOdometer,
        'AfterMaintenanceOdometer' => ($updateType === 'maintenance') ? $newOdometer : null,
        'daily_usage' => $_POST['daily_usage'] ?? null,
        'current_status' => $_POST['current_status'] ?? null,
        'driver1' => $_POST['driver1'] ?? null,
        'driver2' => $_POST['driver2'] ?? null,
        'registration_expiry' => $_POST['registration_expiry'] ?? null,
        'safety_inspection_date' => $_POST['safety_inspection_date'] ?? null,
        'last_maintenance' => ($updateType === 'maintenance') ? $currentDate : null
    ], fn($value) => $value !== null);

    if ($fieldsToUpdate) {
        $updateQuery = "UPDATE bus_details SET " . implode(", ", array_map(fn($key) => "$key = ?", array_keys($fieldsToUpdate))) . " WHERE bus_id = ?";
        if ($stmt = $conn->prepare($updateQuery)) {
            $values = array_values($fieldsToUpdate);
            $values[] = $busId;

            $types = str_repeat("s", count($values));
            if ($newOdometer !== null) {
                $types[0] = "i";
            }

            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                // Log the update activity
                $currentUsername = $_SESSION['username'] ?? 'Unknown';
                $currentUserId = $_SESSION['user_id'] ?? null; // Ensure user_id is stored in session
                if ($currentUserId) {
                    $actionDescription = "Updated bus details for bus ID: $busId";
                    logActivity($conn, $currentUserId, $currentUsername, $actionDescription);
                }
            } else {
                die("MySQL Error: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    // Update maintenance_data table
    if (in_array($updateType, ['oil_change', 'tire_replacement', 'brake_replacement'])) {
        $maintenanceType = ucfirst(str_replace('_', ' ', $updateType));
        $status = 'Done';

        // Use TotalOdometer as odometer_at_maintenance
        $odometerAtMaintenance = $busDetails['TotalOdometer'] ?? $newOdometer;

        $maintenanceQuery = "INSERT INTO maintenance_data (bus_id, last_maintenance, TypeofMaintenance, status, odometer_at_maintenance) VALUES (?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE last_maintenance = VALUES(last_maintenance), status = VALUES(status), odometer_at_maintenance = VALUES(odometer_at_maintenance)";
        if ($stmt = $conn->prepare($maintenanceQuery)) {
            $stmt->bind_param("ssssi", $busId, $currentDate, $maintenanceType, $status, $odometerAtMaintenance);
            if ($stmt->execute()) {
                // Log the maintenance activity
                $currentUsername = $_SESSION['username'] ?? 'Unknown';
                $currentUserId = $_SESSION['user_id'] ?? null; // Ensure user_id is stored in session
                if ($currentUserId) {
                    $actionDescription = "Performed $maintenanceType on bus ID: $busId";
                    logActivity($conn, $currentUserId, $currentUsername, $actionDescription);
                }
            } else {
                die("MySQL Error: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    header("Location: Busdetails.php?bus_id=" . $busId);
    exit();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bus Details</title>
    <link rel="stylesheet" href="EditBusdetails.css">
    <script>
        function toggleFields() {
            document.querySelectorAll('.update-field').forEach(field => field.style.display = 'none');
            const updateType = document.querySelector('select[name="update_type"]').value;
            if (updateType) {
                document.getElementById(updateType + '-field').style.display = 'block';
            }
        }
    </script>
</head>
<body>
<header>
    <?php
    if (!isset($_SESSION['username'])) {
        // Redirect to login page if not logged in
        header("Location: Login.php");
        exit();
    }

    // Assuming the user's role is stored in the session
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
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
            <h1>Edit Bus Details</h1>
        </div>
        
        <div id="EditBusdetails">
            <?php if ($busDetails): ?>
                <form method="POST">
                    <div class="detail-item">
                        <label for="update_type">Update Type:</label>
                        <select name="update_type" onchange="toggleFields()">
                            <option value="">Select Update Type</option>
                            <option value="odometer">Odometer Update</option>
                            <option value="maintenance">Maintenance Update</option>
                            <option value="update_drivers">Update Drivers</option>
                            <option value="tire_replacement">Tire Replacement</option>
                            <option value="oil_change">Oil Change</option>
                            <option value="brake_replacement">Brake Replacement</option>
                        </select>
                    </div>
                    <div class="detail-item update-field" id="odometer-field" style="display: none;">
                        <label for="odometer">Current Odometer Reading:</label>
                        <input type="text" name="odometer">
                    </div>
                    <div class="detail-item update-field" id="update_drivers-field" style="display: none;">
                        <label for="driver1">Driver 1:</label>
                        <input type="text" name="driver1">
                        <label for="driver2">Driver 2:</label>
                        <input type="text" name="driver2">
                    </div>
                    <!-- Removed individual update buttons -->
                    <div class="detail-item">
                        <label for="current_status">Current Status:</label>
                        <select name="current_status">
                            <option value="operational">Operational</option>
                            <option value="under_maintenance">Under Maintenance</option>
                        </select>
                    </div>
                    <button type="submit">Save Changes</button>
                </form>
            <?php else: ?>
                <p>No details found for the specified bus.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>