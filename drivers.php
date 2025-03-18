<?php
session_start();
include 'db_connection.php'; // Ensure this file correctly sets up the database connection

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_driver_id'])) {
    $driverIdToDelete = intval($_POST['delete_driver_id']);
    $deleteQuery = "DELETE FROM drivers WHERE driver_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $driverIdToDelete);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Driver deleted successfully.";
    } else {
        $_SESSION['message'] = "Error deleting driver: " . $conn->error;
    }
    $stmt->close();
    header("Location: drivers.php");
    exit();
}

// Query to fetch driver data
$driversQuery = "SELECT driver_id, name, email, rfid_tag FROM drivers ORDER BY name ASC";
$driversResult = $conn->query($driversQuery);

// Check for query errors
if (!$driversResult) {
    die("SQL error: " . $conn->error);
}

// Fetch data into an array
$driversData = [];
if ($driversResult->num_rows > 0) {
    while ($row = $driversResult->fetch_assoc()) {
        $driversData[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver List</title>
    <link rel="stylesheet" href="drivers.css">
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
        <h1>Driver List</h1>
        
    </div>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message">
            <?php
            echo htmlspecialchars($_SESSION['message']);
            unset($_SESSION['message']);
            ?>
        </div>
    <?php endif; ?>
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by Name, Email, or RFID Tag...">
        <button onclick="searchDrivers()">Search</button>
        <button id="addDriverButton" onclick="addNewDriver()">Add New Driver</button>
        <button id="exportButton" onclick="exportDriverList()">Export Driver List</button>
    </div>
    <div class="drivers-table">
        <table id="driversTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>RFID Tag</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($driversData)): ?>
                <?php foreach ($driversData as $driver): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($driver['name']); ?></td>
                        <td><?php echo htmlspecialchars($driver['email']); ?></td>
                        <td><?php echo htmlspecialchars($driver['rfid_tag']); ?></td>
                        <td><button onclick="confirmDelete(<?php echo htmlspecialchars($driver['driver_id']); ?>)">Delete</button></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No drivers found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script src="drivers.js"></script>
<script>
    function confirmDelete(driverId) {
        if (confirm("Are you sure you want to delete this driver?")) {
            // Create a form to submit the delete request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_driver_id';
            input.value = driverId;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
</body>
</html>
