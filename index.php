<?php
// Include database connection
include 'db_connection.php';

date_default_timezone_set('Asia/Manila');
// Query to fetch data from the database, including overall totals
$query = "
    SELECT date, route, SUM(passengers) as total_passengers 
    FROM passenger_data 
    GROUP BY date, route
    UNION ALL
    SELECT date, 'Overall' as route, SUM(passengers) as total_passengers 
    FROM passenger_data 
    GROUP BY date
";

$shiftLogsQuery = "
    SELECT 
        sl.log_id, 
        d.name AS driver_name, 
        sl.bus_id, 
        sl.shift_date, 
        sl.status, 
        sl.route, 
        d.image AS driver_image
    FROM 
        shiftlogs sl
    JOIN 
        drivers d ON sl.driver_id = d.driver_id
    ORDER BY 
        sl.shift_date DESC
    LIMIT 5
";
$shiftLogsResult = $conn->query($shiftLogsQuery);
$shiftLogs = [];

if ($shiftLogsResult->num_rows > 0) {
    while ($row = $shiftLogsResult->fetch_assoc()) {
        $shiftLogs[] = $row;
    }
}

$busCountQuery = "SELECT COUNT(*) as bus_count FROM bus_details";
$busCountResult = $conn->query($busCountQuery);
$busCountRow = $busCountResult->fetch_assoc();
$busCount = $busCountRow['bus_count'];

// Fetch the total number of passengers
$passengerCountQuery = "SELECT SUM(current_passengers) as total_passengers FROM bus_passenger_data";
$passengerCountResult = $conn->query($passengerCountQuery);
$passengerCountRow = $passengerCountResult->fetch_assoc();
$totalPassengers = $passengerCountRow['total_passengers'];

// Fetch the number of schedules
$scheduleCountQuery = "SELECT COUNT(*) as schedule_count FROM bus_details WHERE driver1 IS NOT NULL OR driver2 IS NOT NULL";
$scheduleCountResult = $conn->query($scheduleCountQuery);
$scheduleCountRow = $scheduleCountResult->fetch_assoc();
$scheduleCount = $scheduleCountRow['schedule_count'];

// Query to fetch upcoming maintenance alerts
$maintenanceAlertQuery = "
    SELECT bus_id, next_scheduled_maintenance, last_maintenance
    FROM bus_details
    WHERE next_scheduled_maintenance <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
    OR last_maintenance <= DATE_SUB(CURDATE(), INTERVAL 180 DAY)
    ORDER BY next_scheduled_maintenance ASC
";

// Execute passenger data query
$result = $conn->query($query);
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Execute the maintenance alerts query
$maintenanceAlerts = [];
$maintenanceResult = $conn->query($maintenanceAlertQuery);
if ($maintenanceResult->num_rows > 0) {
    while ($row = $maintenanceResult->fetch_assoc()) {
        $maintenanceAlerts[] = $row;
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
    <title>Dashboard</title>
    <link rel="stylesheet" href="Dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Add cursor pointer for clickable elements */
        .chartjs-render-monitor {
            cursor: pointer;
        }
    </style>
</head>
<body>
<?php
// Determine the current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
    <?php
    session_start();
    if (!isset($_SESSION['username'])) {
        // Redirect to login page if not logged in
        header("Location: Login.php");
        exit();
    }
    include 'role_check.php';

    // Check if the user has the required role
    checkUserRole(['SuperAdmin','MidAdmin','Admin']);
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
            <h1>Fleet Management</h1>
        </div>
        <div class="card-container">
                <div class="card-content">
                <div class="card-icon">
                    <img src="../image/bus.png" alt="Icon" />
                </div>
                <div class="card-text">
                    <div class="card-text-upper"><?php echo $busCount; ?></div>
                    <div class="card-text-lower">Buses</div>
                </div>
            </div>

            <div class="card-content">
        <div class="card-icon">
            <img src="../image/passenger.png" alt="Icon" />
        </div>
        <div class="card-text">
            <div class="card-text-upper"><?php echo $totalPassengers; ?></div>
            <div class="card-text-lower">Passengers</div>
        </div>
    </div>
            <div class="card-content">
                <div class="card-icon">
                    <img src="../image/busstop.png" alt="Icon" />
                </div>
                <div class="card-text">
                    <div class="card-text-upper">40</div>
                    <div class="card-text-lower">Bus stops</div>
                </div>
            </div>
            <div class="card-content">
        <div class="card-icon">
            <img src="../image/calendar.png" alt="Icon" />
        </div>
        <div class="card-text">
            <div class="card-text-upper"><?php echo $scheduleCount; ?></div>
            <div class="card-text-lower">Schedules</div>
        </div>
    </div>
       
        </div>
        <div class="devider">
            <div class="Title1">
                <h1><?php echo date('l, F j, Y \a\t g:i A'); ?></h1>
            </div>
        </div>
        <div class="special-card-container">
            <div class="card card-left-top">
                <div class="card-text">
                    <div class="card-text-upper">16</div>
                    <div class="card-text-lower">Bus Deployed</div>
                </div>
            </div>
            <div class="card card-left-middle">
                <div class="card-text">
                    <div class="card-text-upper">Upcoming Maintenance</div>
                    <div class="card-text-lower">
                        <ul>
                            <?php if (!empty($maintenanceAlerts)): ?>
                                <?php foreach ($maintenanceAlerts as $alert): ?>
                                    <li>
                                        Bus <?php echo htmlspecialchars($alert['bus_id']); ?>: 
                                        Next maintenance on <?php echo htmlspecialchars($alert['next_scheduled_maintenance']); ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>No upcoming maintenance scheduled.</li>
                            <?php endif; ?>
                        </ul>
                      
                       
                    </div>
                </div>
            </div>

            <div class="card card-middle">
                <h2>Passenger Counts</h2>
                <canvas id="passengerChart"></canvas>
            </div>
        <div class="card card-right shift-logs-card" style="border: 3px solid #F28C28;">
    <h2 class="card-title">Shift Logs</h2>
    <div class="card-text">
        <ul class="shift-logs">
            <?php if (!empty($shiftLogs)): ?>
                <?php foreach ($shiftLogs as $log): ?>
                    <li>
                        <img src="<?php echo htmlspecialchars($log['driver_image']); ?>" alt="Profile Icon" class="profile-icon">
                        <div class="log-details">
                            <div>Driver: <?php echo htmlspecialchars($log['driver_name']); ?></div>
                            <div>Date: <?php echo htmlspecialchars($log['shift_date']); ?></div>
                            <div>
                                <?php if ($log['status'] === 'Time In'): ?>
                                   Shift date & Time: <?php echo htmlspecialchars($log['shift_date']); ?>
                               
                                <?php endif; ?>
                            </div>
                            <div>Bus #: <?php echo htmlspecialchars($log['bus_id']); ?></div>
                            <div>Route: <?php echo htmlspecialchars($log['route']); ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No shift logs available.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
        </div>
    </main>

    <script>
  document.addEventListener('DOMContentLoaded', function () {
    fetch('fetch_passenger_data.php') // Fetch data from the new PHP file
        .then(response => response.json())
        .then(data => {
            let labels = [];
            let datasets = {};

            let routeColors = {
                "Overall": "#00796B", // Overall total color
                "ARCA South Route": "#E65100", // Unique color for ARCA South Route
                "Central Route": "#4682B4", // Unique color for Central Route
                "East Route": "#2E7D32", // Unique color for East Route
                "North Route": "#C62828", // Unique color for North Route
                "Weekend Route": "#FFC107", // Unique color for Weekend Route
                "West Route": "#673AB7" // Unique color for West Route
            };

            // Sort routes alphabetically, keeping "Overall" first
            let sortedRoutes = Object.keys(routeColors).sort((a, b) => {
                if (a === "Overall") return -1;
                if (b === "Overall") return 1;
                return a.localeCompare(b);
            });

            data.forEach(item => {
                if (!labels.includes(item.date)) {
                    labels.push(item.date);
                }
                if (!datasets[item.route]) {
                    datasets[item.route] = {
                        label: item.route,
                        data: [],
                        backgroundColor: routeColors[item.route] || '#000000',
                        borderColor: routeColors[item.route] || '#000000',
                        borderWidth: 1
                    };
                }
                datasets[item.route].data.push(item.total_passengers);
            });

            let ctx = document.getElementById('passengerChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: sortedRoutes.map(route => datasets[route])
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, ticks: { autoSkip: false } },
                        y: { beginAtZero: true, min: 0, ticks: { stepSize: 2000 } }
                    },
                    hover: {
                        onHover: function(event, chartElement) {
                            event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                        }
                    },
                    onClick: (e, elements) => {
                        if (elements.length > 0) {
                            const chart = elements[0]._chart;
                            const datasetIndex = elements[0]._datasetIndex;
                            const route = chart.data.datasets[datasetIndex].label;
                            alert(`You clicked on the ${route} route.`);
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching passenger data:', error));
});
    </script>

</body>
</html>