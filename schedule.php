<?php
include 'db_connection.php'; // Ensure this file correctly sets up the database connection

session_start();
$message = '';
$messageClass = '';

// Function to log activity
function logActivity($conn, $user_id, $username, $action) {
    $logQuery = "INSERT INTO activity_logs (user_id, username, action, timestamp) VALUES (?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($logQuery)) {
        $stmt->bind_param("iss", $user_id, $username, $action);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bus_id = $_POST['bus_number'];
    $driver1_name = $_POST['driver1_name'];
    $driver1_shift = $_POST['driver1_shift'];
    $driver2_name = $_POST['driver2_name'];
    $driver2_shift = $_POST['driver2_shift'];
    $route = $_POST['route'];

    // Check if the bus_id exists in the bus_details table
    $busCheckStmt = $conn->prepare("SELECT * FROM bus_details WHERE bus_id = ?");
    $busCheckStmt->bind_param("s", $bus_id);
    $busCheckStmt->execute();
    $busResult = $busCheckStmt->get_result();

    if ($busResult->num_rows > 0) {
        // Check if driver1 exists in the drivers table
        $driver1CheckStmt = $conn->prepare("SELECT * FROM drivers WHERE name = ?");
        $driver1CheckStmt->bind_param("s", $driver1_name);
        $driver1CheckStmt->execute();
        $driver1Result = $driver1CheckStmt->get_result();

        // Check if driver2 exists in the drivers table
        $driver2CheckStmt = $conn->prepare("SELECT * FROM drivers WHERE name = ?");
        $driver2CheckStmt->bind_param("s", $driver2_name);
        $driver2CheckStmt->execute();
        $driver2Result = $driver2CheckStmt->get_result();

        if ($driver1Result->num_rows > 0 && $driver2Result->num_rows > 0) {
            // Update the bus_details table
            $updateStmt = $conn->prepare("UPDATE bus_details SET driver1 = ?, driver1_shift = ?, driver2 = ?, driver2_shift = ?, route = ? WHERE bus_id = ?");
            $updateStmt->bind_param("ssssss", $driver1_name, $driver1_shift, $driver2_name, $driver2_shift, $route, $bus_id);

            if ($updateStmt->execute()) {
                // Update the drivers table with the route and bus number
                $updateDriver1Stmt = $conn->prepare("UPDATE drivers SET bus_id = ?, route = ? WHERE name = ?");
                $updateDriver1Stmt->bind_param("sss", $bus_id, $route, $driver1_name);
                $updateDriver1Stmt->execute();
                $updateDriver1Stmt->close();

                $updateDriver2Stmt = $conn->prepare("UPDATE drivers SET bus_id = ?, route = ? WHERE name = ?");
                $updateDriver2Stmt->bind_param("sss", $bus_id, $route, $driver2_name);
                $updateDriver2Stmt->execute();
                $updateDriver2Stmt->close();

                $message = "Schedule updated successfully!";
                $messageClass = "success";

                // Log the schedule update activity
                $currentUsername = $_SESSION['username'] ?? 'Unknown';
                $currentUserId = $_SESSION['user_id'] ?? null; // Ensure user_id is stored in session
                if ($currentUserId) {
                    logActivity($conn, $currentUserId, $currentUsername, "Updated schedule for bus $bus_id");
                } else {
                    $message = "Error: User ID not found in session.";
                    $messageClass = "error";
                }
            } else {
                $message = "Error: " . $updateStmt->error;
                $messageClass = "error";
            }

            $updateStmt->close();
        } else {
            $message = "Error: One or both drivers are not registered.";
            $messageClass = "error";
        }

        $driver1CheckStmt->close();
        $driver2CheckStmt->close();
    } else {
        $message = "Error: Bus number not found.";
        $messageClass = "error";
    }

    $busCheckStmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Bus and Drivers</title>
    <link rel="stylesheet" href="schedule.css">
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
        <h1>Schedule Bus and Drivers</h1>
    </div>
    <div class="form-container">
        <form action="schedule.php" method="POST">
            <div class="form-group">
                <label for="bus_number">Bus Number:</label>
                <input type="text" id="bus_number" name="bus_number" required>
            </div>
            <div class="form-group">
                <label for="driver1_name">Driver 1 Name:</label>
                <input type="text" id="driver1_name" name="driver1_name" required>
                <label for="driver1_shift">Driver 1 Shift:</label>
                <select id="driver1_shift" name="driver1_shift" required>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                </select>
            </div>
            <div class="form-group">
                <label for="driver2_name">Driver 2 Name:</label>
                <input type="text" id="driver2_name" name="driver2_name" required>
                <label for="driver2_shift">Driver 2 Shift:</label>
                <select id="driver2_shift" name="driver2_shift" required>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                </select>
            </div>
            <div class="form-group">
                <label for="route">Route:</label>
                <select id="route" name="route" required>
                    <option value="ARCA South Route">ARCA South Route</option>
                    <option value="Central Route">Central Route</option>
                    <option value="East Route">East Route</option>
                    <option value="North Route">North Route</option>
                    <option value="Weekend Route">Weekend Route</option>
                    <option value="West Route">West Route</option>
                </select>
            </div>
            <button type="submit">Update Schedule</button>
        </form>
        <?php if ($message): ?>
            <div class="message <?php echo $messageClass; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>