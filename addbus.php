<?php
include 'db_connection.php'; // Ensure this file correctly sets up the database connection

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plateNumber = $_POST['plate_number'] ?? '';
    $chassisNumber = $_POST['chassis_number'] ?? '';
    $seatingCapacity = $_POST['seating_capacity'] ?? '';
    $engineNumber = $_POST['engine_number'] ?? '';
    $fuelType = $_POST['fuel_type'] ?? '';
    $totalOdometer = $_POST['total_odometer'] ?? '';

    if ($plateNumber && $chassisNumber && $seatingCapacity && $engineNumber && $fuelType && $totalOdometer) {
        // Fetch the next bus_id (bus_number)
        $query = "SELECT MAX(CAST(SUBSTRING(bus_id, 4) AS UNSIGNED)) as max_id FROM bus_details";
        $result = $conn->query($query);
        $row = $result->fetch_assoc();
        $nextBusId = ($row['max_id'] !== null) ? $row['max_id'] + 1 : 1; // Handle NULL case

        // Format the nextBusId with a prefix (e.g., "Bus")
        $nextBusIdStr = 'Bus' . str_pad($nextBusId, 2, '0', STR_PAD_LEFT);

        // Insert new bus details
        $insertQuery = "
            INSERT INTO bus_details (bus_id, plate_number, chassis_number, seating_capacity, engine_number, fuel_type, TotalOdometer)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";

        if ($stmt = $conn->prepare($insertQuery)) {
            $stmt->bind_param("sssssss", $nextBusIdStr, $plateNumber, $chassisNumber, $seatingCapacity, $engineNumber, $fuelType, $totalOdometer);
            if ($stmt->execute()) {
                $successMessage = "Bus added successfully! Bus Number: " . htmlspecialchars($nextBusIdStr);
            } else {
                $errorMessage = "Error adding bus: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $errorMessage = "All fields are required.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Bus</title>
    <link rel="stylesheet" href="addbus.css">
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
        <h1>Add New Bus</h1>
    </div>
    <div class="form-container">
        <form action="addbus.php" method="POST">
            <div class="form-group">
                <label for="plate_number">Plate Number:</label>
                <input type="text" id="plate_number" name="plate_number" required>
            </div>
            <div class="form-group">
                <label for="chassis_number">Chassis Number:</label>
                <input type="text" id="chassis_number" name="chassis_number" required>
            </div>
            <div class="form-group">
                <label for="seating_capacity">Seating Capacity:</label>
                <input type="number" id="seating_capacity" name="seating_capacity" required>
            </div>
            <div class="form-group">
                <label for="engine_number">Engine Number:</label>
                <input type="text" id="engine_number" name="engine_number" required>
            </div>
            <div class="form-group">
                <label for="fuel_type">Fuel Type:</label>
                <select id="fuel_type" name="fuel_type" required>
                    <option value="">Select Fuel Type</option>
                    <option value="Diesel">Diesel</option>
                    <option value="Petrol">Petrol</option>
                    <option value="Electric">Electric</option>
                    <option value="Hybrid">Hybrid</option>
                </select>
            </div>
            <div class="form-group">
                <label for="total_odometer">Total Odometer:</label>
                <input type="number" id="total_odometer" name="total_odometer" required>
            </div>
            <button type="submit">Add Bus</button>
        </form>
        <?php if ($successMessage): ?>
            <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>