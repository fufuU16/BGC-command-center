<?php
// Include the database connection
include 'db_connection.php'; // Ensure this file exists and is correct

session_start();
if (!isset($_SESSION['username'])) {
    // Redirect to login page if not logged in
    header("Location: Login.php");
    exit();
}

// Initialize search term
$searchTerm = isset($_GET['route_search']) ? $_GET['route_search'] : '';

// Fetch bus stop details
$busStopQuery = "
    SELECT bus_no, passenger_count, eta, bus_number, route, current_stop, next_stop, end_point, timestamp
    FROM bus_stop_details
    ORDER BY route, bus_no
";
$busStopResult = $conn->query($busStopQuery);
if (!$busStopResult) {
    die("SQL error: " . $conn->error);
}
$busStopData = $busStopResult->fetch_all(MYSQLI_ASSOC);

// Fetch route data
$routeQuery = "
    SELECT route, bus_id, current_passengers, 
           SUM(current_passengers) OVER (PARTITION BY route) as overall_count
    FROM bus_passenger_data
    WHERE date = CURDATE()
";
if ($searchTerm) {
    $routeQuery .= " AND route LIKE '%" . $conn->real_escape_string($searchTerm) . "%'";
}
$routeQuery .= " ORDER BY route, bus_id";
$routeResult = $conn->query($routeQuery);
if (!$routeResult) {
    die("SQL error: " . $conn->error);
}
$routeData = [];
while ($row = $routeResult->fetch_assoc()) {
    $routeData[$row['route']][] = $row;
}

// Fetch historical data for the past 7 days
$historyQuery = "
    SELECT date, route, SUM(passengers) as total_passengers 
    FROM passenger_data 
    WHERE date >= CURDATE() - INTERVAL 7 DAY
    GROUP BY date, route
    UNION ALL
    SELECT date, 'Overall' as route, SUM(passengers) as total_passengers 
    FROM passenger_data 
    WHERE date >= CURDATE() - INTERVAL 7 DAY
    GROUP BY date
";
$historyResult = $conn->query($historyQuery);
if (!$historyResult) {
    die("SQL error: " . $conn->error);
}
$historyData = $historyResult->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Details</title>
    <link rel="stylesheet" href="passenger.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        <h1>Passenger Details</h1>
    </div>
    <div id="MapContainer">
        <h2>Bus Location Tracker</h2>
        <div id="map" style="height: 400px; width: 100%;"></div>
    </div>
    <div id="BusStopDetails">
        <h2>Bus Stop Details</h2>
        <form method="GET" action="Passenger.php">
            <input type="text" name="route_search" placeholder="Search by route" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Search</button>
            <button type="button" class="button-link" onclick="window.location.href='save_image.php'">Passenger Count View</button>
        </form>
        <table class="busStopDetails">
            <thead>
                <tr>
                    <th>Bus No.</th>
                    <th>Passenger Count</th>
                    <th>ETA</th>
                    <th>Bus Number</th>
                    <th>Route</th>
                    <th>Current Stop</th>
                    <th>Next Stop</th>
                    <th>End Point</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($busStopData)): ?>
                    <?php foreach ($busStopData as $bus): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bus['bus_no']); ?></td>
                            <td><?php echo htmlspecialchars($bus['passenger_count']); ?></td>
                            <td><?php echo htmlspecialchars($bus['eta']); ?></td>
                            <td><?php echo htmlspecialchars($bus['bus_number']); ?></td>
                            <td><?php echo htmlspecialchars($bus['route']); ?></td>
                            <td><?php echo htmlspecialchars($bus['current_stop']); ?></td>
                            <td><?php echo htmlspecialchars($bus['next_stop']); ?></td>
                            <td><?php echo htmlspecialchars($bus['end_point']); ?></td>
                            <td><?php echo htmlspecialchars($bus['timestamp']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No bus stop details available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="PassengerDetails">
        <h2>Passenger Counts by Route</h2>
        <form method="GET" action="Passenger.php">
            <input type="text" name="route_search" placeholder="Search by route" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit">Search</button>
        </form>
        <?php if (!empty($routeData)): ?>
            <?php foreach ($routeData as $route => $buses): ?>
                <h3>Route: <?php echo htmlspecialchars($route); ?></h3>
                <table class="routePassengerCounts">
                    <thead>
                        <tr>
                            <th>Bus Number</th>
                            <th>Passenger Count</th>
                            <th>Overall Count (by Route)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($buses as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['bus_id']); ?></td>
                                <td><?php echo htmlspecialchars($data['current_passengers']); ?></td>
                                <td><?php echo htmlspecialchars($data['overall_count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php else: ?>
            <table class="routePassengerCounts">
                <thead>
                    <tr>
                        <th>Bus Number</th>
                        <th>Passenger Count</th>
                        <th>Overall Count (by Route)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3">No data available.</td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
  
    <div id="PassengerHistory">
    <h2>Passenger Counts History</h2>
    <div class="controls">
        <select id="timeRangeSelector" class="button-link">
            <option value="7days">Past 7 Days</option>
            <option value="3months">Past 3 Months</option>
            <option value="6months">Past 6 Months</option>
            <option value="year">Past Year</option>
        </select>
        <button id="exportButton" class="button-link">Export to Excel</button>
    </div>
    <table class="historyTable">
        <thead>
            <tr id="historyTableHeader">
                <th>Date</th>
                <th>ARCA South Route</th>
                <th>Central Route</th>
                <th>East Route</th>
                <th>North Route</th>
                <th>Weekend Route</th>
                <th>West Route</th>
                <th>Overall</th>
            </tr>
        </thead>
        <tbody id="historyTableBody">
            <tr>
                <td colspan="8">No data available.</td>
            </tr>
        </tbody>
    </table>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timeRangeSelector = document.getElementById('timeRangeSelector');
        const exportButton = document.getElementById('exportButton');
        const historyTableBody = document.getElementById('historyTableBody');

        async function fetchHistoryData(timeRange) {
            try {
                const response = await fetch(`fetch_passenger_data.php?range=${timeRange}`);
                const historyData = await response.json();

                // Clear existing body
                historyTableBody.innerHTML = '';

                if (historyData.length > 0) {
                    const dataByDate = {};

                    // Organize data by date
                    historyData.forEach(item => {
                        if (!dataByDate[item.date]) {
                            dataByDate[item.date] = {
                                "ARCA South Route": 0,
                                "Central Route": 0,
                                "East Route": 0,
                                "North Route": 0,
                                "Weekend Route": 0,
                                "West Route": 0,
                                "Overall": 0
                            };
                        }
                        dataByDate[item.date][item.route] = item.total_passengers;
                        dataByDate[item.date]["Overall"] += item.total_passengers;
                    });

                    // Populate table body
                    Object.entries(dataByDate).forEach(([date, routeData]) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${date}</td>`;

                        ["ARCA South Route", "Central Route", "East Route", "North Route", "Weekend Route", "West Route", "Overall"].forEach(route => {
                            const count = routeData[route] || 0;
                            row.innerHTML += `<td>${count}</td>`;
                        });

                        historyTableBody.appendChild(row);
                    });
                } else {
                    historyTableBody.innerHTML = '<tr><td colspan="8">No data available.</td></tr>';
                }
            } catch (error) {
                console.error("Error fetching history data:", error);
            }
        }

        timeRangeSelector.addEventListener('change', function () {
            fetchHistoryData(this.value);
        });

        exportButton.addEventListener('click', function () {
            exportHistoryDataToExcel();
        });

        function exportHistoryDataToExcel() {
            let csvContent = "data:text/csv;charset=utf-8,Date,ARCA South Route,Central Route,East Route,North Route,Weekend Route,West Route,Overall\n";

            // Add data rows to CSV
            historyTableBody.querySelectorAll('tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = Array.from(cells).map(cell => cell.textContent).join(",");
                csvContent += rowData + "\n";
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "passenger_counts_history.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Initial fetch for the default time range
        fetchHistoryData(timeRangeSelector.value);

        // Initialize the map
        var map = L.map('map').setView([14.5531, 121.0180], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Image Icons for Bus and Stops
        const redIcon = L.icon({
            iconUrl: '../image/busmarkk.png', 
            iconSize: [30, 30]
        });

        const blueIcon = L.icon({
            iconUrl: '../image/busiconn.PNG',
            iconSize: [30, 30]
        });

        const busStops = [
            { name: "Market! Market!", lat: 14.548847458449865, lng: 121.05636632081664 },
            { name: "Nutriasia", lat: 14.551637863137922, lng: 121.05127157262918 },
            { name: "The Fort", lat: 14.54930152073757, lng: 121.04741660735782 },
            { name: "One/NEO", lat: 14.550270155719662, lng: 121.04542703040072 },
            { name: "Bonifacio Stopover", lat: 14.554152587939072, lng: 121.04603459326133 },
            { name: "Crescent Park West", lat: 14.554424695275497, lng: 121.04404419730557 },
            { name: "The Globe Tower", lat: 14.5543430, lng: 121.0440980 },
            { name: "One Parkade", lat: 14.550050520728623, lng: 121.0499049573256 },
            { name: "University Parkway", lat: 14.551577178911622, lng: 121.05723490011086 }
        ];

        // Bus Stop Markers
        busStops.forEach(stop => {
            L.marker([stop.lat, stop.lng], { icon: redIcon })
                .addTo(map)
                .bindPopup(`
                    <img src="../image/busmarkk.png" alt="Bus Stop Icon" width="20" height="20">
                    <b>${stop.name}</b>
                `);
        });

        let busMarkers = [];

        async function fetchBusData() {
            try {
                const response = await fetch('fetch_buses.php');
                const data = await response.json();

                if (!Array.isArray(data)) {
                    console.error("Invalid data format:", data);
                    return;
                }

                // Clear old markers before adding new ones
                busMarkers.forEach(marker => map.removeLayer(marker));
                busMarkers = [];

                // Collect all coordinates for bounds calculation
                const allCoordinates = [];

                // Add new markers with valid coordinates
                data.forEach(bus => {
                    const lat = parseFloat(bus.latitude);
                    const lng = parseFloat(bus.longitude);

                    if (!isNaN(lat) && !isNaN(lng)) {
                        const marker = L.marker([lat, lng], { icon: blueIcon })
                            .addTo(map)
                            .bindPopup(`
                                <img src="../image/busiconn.PNG" alt="Bus Icon" width="20" height="20"><br>
                                <b>Bus No:</b> ${bus.bus_no || 'N/A'}<br>
                                <b>Next Stop:</b> ${bus.next_stop || 'N/A'}<br>
                                <b>ETA:</b> ${bus.eta || 'Unknown'} mins
                            `);
                        busMarkers.push(marker);
                        allCoordinates.push([lat, lng]);
                    }
                });

                // Add bus stop coordinates to the bounds
                busStops.forEach(stop => {
                    allCoordinates.push([stop.lat, stop.lng]);
                });

                // Fit map to bounds if there are any coordinates
                if (allCoordinates.length > 0) {
                    const bounds = L.latLngBounds(allCoordinates);
                    map.fitBounds(bounds);
                }

            } catch (error) {
                console.error("Error fetching bus data:", error);
            }
        }

        fetchBusData();
        setInterval(fetchBusData, 10000);
    });
</script>
</main>
</body>
</html>