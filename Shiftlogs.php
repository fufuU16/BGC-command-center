<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: Login.php");
    exit();
}

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "bgc_database";

// Create database connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch shift logs
$shiftLogsQuery = "
    SELECT 
        sl.log_id, 
        d.name AS driver_name, 
        sl.bus_id, 
        sl.shift_date, 
        sl.status, 
        sl.route
    FROM 
        shiftlogs sl
    JOIN 
        drivers d ON sl.driver_id = d.driver_id
    ORDER BY 
        sl.shift_date DESC
";

$shiftLogsResult = $conn->query($shiftLogsQuery);
$shiftLogs = [];

if ($shiftLogsResult->num_rows > 0) {
    while ($row = $shiftLogsResult->fetch_assoc()) {
        $shiftLogs[] = $row;
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Logs</title>
    <link rel="stylesheet" href="Shiftlogs.css">
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
        <h1>Shift Logs</h1>
    </div>
    
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by Driver, Bus, or Status...">
        <button onclick="searchLogs()">Search</button>
        <button id="exportButton" onclick="exportReport()">Export Report</button>     
        <button id="viewDriverListButton" onclick="viewDriverList()">View Driver List</button>
    </div>
    
    <div class="logs-table">
        <table id="logsTable">
            <thead>
                <tr>
                    <th>Driver</th>
                    <th>Bus</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Route</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($shiftLogs)): ?>
                    <?php foreach ($shiftLogs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['driver_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['bus_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['shift_date']); ?></td>
                            <td><?php echo htmlspecialchars($log['status']); ?></td>
                            <td><?php echo htmlspecialchars($log['route']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No shift logs available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="Shiftlogs.js"></script>
</body>
</html>