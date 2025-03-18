<?php
include 'db_connection.php'; // Ensure this file correctly sets up the database connection

// Query to fetch maintenance data with all possible maintenance types
$maintenanceQuery = "
    SELECT bd.bus_id, bd.plate_number, bd.next_scheduled_maintenance, bd.current_status AS status, 
           md.TypeofMaintenance, md.odometer_at_maintenance, bd.TotalOdometer
    FROM bus_details bd
    LEFT JOIN maintenance_data md ON bd.bus_id = md.bus_id
    ORDER BY bd.next_scheduled_maintenance DESC
";

$maintenanceResult = $conn->query($maintenanceQuery);

// Check for query errors
if (!$maintenanceResult) {
    die("SQL error: " . $conn->error);
}

// Fetch data into an array
$maintenanceData = [];
if ($maintenanceResult->num_rows > 0) {
    while ($row = $maintenanceResult->fetch_assoc()) {
        $maintenanceData[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance</title>
    <link rel="stylesheet" href="Maintenance.css">
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
        <h1>Maintenance</h1>
    </div>
    
    <!-- Search bar and export button -->
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by Bus ID, Plate Number, or Status..." onkeyup="searchLogs()">
        <button onclick="exportReport()">Export Report</button>
    </div>
    
    <div class="logs-table">
        <table id="busTable">
            <thead>
                <tr>
                    <th>Bus ID</th>
                    <th>Plate Number</th>
                    <th>Next Scheduled Maintenance</th>
                    <th>Last Change/Check</th>
                    <th>Maintenance Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($maintenanceData)): ?>
                <?php foreach ($maintenanceData as $bus): ?>
                    <?php
                    $maintenanceIntervals = [
                        'Oil Change' => 10000,
                        'Tire Replacement' => 40000,
                        'Brake Replacement' => 30000
                    ];

                    $maintenanceNeeded = false;
                    if (isset($maintenanceIntervals[$bus['TypeofMaintenance']])) {
                        $nextMaintenanceOdometer = $bus['odometer_at_maintenance'] + $maintenanceIntervals[$bus['TypeofMaintenance']];
                        $maintenanceNeeded = $bus['TotalOdometer'] >= $nextMaintenanceOdometer;
                    }
                    ?>
                    <tr data-bus-id="<?php echo htmlspecialchars($bus['bus_id']); ?>">
                        <td><?php echo htmlspecialchars($bus['bus_id']); ?></td>
                        <td><?php echo htmlspecialchars($bus['plate_number']); ?></td>
                        <td><?php echo htmlspecialchars($bus['next_scheduled_maintenance']); ?></td>
                        <td><?php echo htmlspecialchars($bus['odometer_at_maintenance']); ?></td>
                        <td><?php echo htmlspecialchars($bus['TypeofMaintenance']); ?></td>
                        <td><?php echo $maintenanceNeeded ? 'Required' : 'Done'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No maintenance records found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    function searchLogs() {
        let input = document.getElementById("searchInput").value.toLowerCase();
        let table = document.getElementById("busTable");
        let rows = table.getElementsByTagName("tr");

        for (let i = 1; i < rows.length; i++) {
            let row = rows[i];
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? "" : "none";
        }
    }

    function exportReport() {
        let table = document.getElementById("busTable");
        let rows = table.getElementsByTagName("tr");
        let csvContent = "Bus ID,Plate Number,Next Scheduled Maintenance,Last Change/Check,Maintenance Type,Status\n";

        for (let i = 1; i < rows.length; i++) {
            let cells = rows[i].getElementsByTagName("td");
            if (cells.length > 0) {
                let rowData = [];
                for (let cell of cells) {
                    rowData.push(cell.innerText);
                }
                csvContent += rowData.join(",") + "\n";
            }
        }

        let blob = new Blob([csvContent], { type: "text/csv" });
        let link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = "Maintenance_Report.csv";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function goToSchedule() {
        window.location.href = "ScheduleBus.php";
    }

    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('#busTable tbody tr');

        rows.forEach(row => {
            row.addEventListener('click', function() {
                const busId = this.getAttribute('data-bus-id');
                if (busId && busId.trim() !== "") {
                    window.location.href = `busDetails.php?bus_id=${encodeURIComponent(busId)}`;
                }
            });
        });
    });
</script>
</body>
</html>