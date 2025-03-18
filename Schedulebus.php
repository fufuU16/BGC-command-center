<?php
include 'db_connection.php'; // Ensure this file correctly sets up the database connection

// Fetch all bus details from the database
$query = "SELECT bus_id, plate_number AS bus_number, driver1, driver2,  current_status, route FROM bus_details";
$result = $conn->query($query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Schedule</title>
    <link rel="stylesheet" href="Schedulebus.css">
   
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
        <h1>Bus Schedule</h1>
    </div>
    
    <!-- Controls for Search, Add Schedule, and Export Report -->
    <div class="controls">
        <input type="text" id="searchInput" placeholder="Search by Bus ID, Bus Number, Driver, or Route..." onkeyup="searchBusSchedule()">
        <button onclick="window.location.href='schedule.php'">Add Schedule</button>
        <button onclick="window.location.href='addbus.php'">Add Bus</button> <!-- New button added here -->
        <button onclick="exportReport()">Export Report</button>
    </div>
    
    <div class="schedule-table">
        <table id="scheduleTable">
            <thead>
                <tr>
                    <th>Bus ID</th>
                    <th>Bus Number</th>
                    <th>Driver 1</th>
                    <th>Driver 2</th>
                    <th>Current Status</th>
                    <th>Route</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr onclick="goToBusDetails('<?php echo $row['bus_id']; ?>')">
                        <td><?php echo htmlspecialchars($row['bus_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['bus_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['driver1']); ?></td>
                        <td><?php echo htmlspecialchars($row['driver2']); ?></td>
                        <td><?php echo htmlspecialchars($row['current_status']); ?></td>
                        <td><?php echo htmlspecialchars($row['route']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
<script>
        function goToBusDetails(busId) {
            window.location.href = `busDetails.php?bus_id=${busId}`;
        }

        function searchBusSchedule() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let table = document.getElementById("scheduleTable");
            let rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) {
                let row = rows[i];
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            }
        }

        function exportReport() {
            let table = document.getElementById("scheduleTable");
            let rows = table.getElementsByTagName("tr");
            let csvContent = "Bus ID,Bus Number,Driver 1,Driver 2,Next Scheduled Maintenance,Current Status,Route\n";

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
            link.download = "Bus_Schedule_Report.csv";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>