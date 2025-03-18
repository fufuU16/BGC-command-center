<?php
include 'db_connection.php'; // Ensure this file correctly sets up the database connection

session_start();
include 'role_check.php';

    // Check if the user has the required role
    checkUserRole(['SuperAdmin']);
// Function to log activity
function logActivity($conn, $user_id, $username, $action) {
    $logQuery = "INSERT INTO activity_logs (user_id, username, action, timestamp) VALUES (?, ?, ?, NOW())";
    if ($stmt = $conn->prepare($logQuery)) {
        $stmt->bind_param("iss", $user_id, $username, $action);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle form submission for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $userId = $_POST['delete_id'];

    // Prepare and execute the delete query
    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $deleteMessage = "Admin deleted successfully.";

        // Log the deletion activity
        $currentUsername = $_SESSION['username'] ?? 'Unknown';
        $currentUserId = $_SESSION['user_id'] ?? null; // Ensure user_id is stored in session
        if ($currentUserId) {
            $actionDescription = "Deleted admin with ID: $userId";
            logActivity($conn, $currentUserId, $currentUsername, $actionDescription);
        }
    } else {
        $deleteMessage = "Failed to delete admin.";
    }

    $stmt->close();
}

// Query to fetch admins
$adminQuery = "
    SELECT id, username, role, email
    FROM users
    WHERE role IN ('Admin', 'SuperAdmin', 'MidAdmin')
    ORDER BY username ASC
";

$adminResult = $conn->query($adminQuery);

// Check for query errors
if (!$adminResult) {
    die("SQL error: " . $conn->error);
}

// Fetch data into an array
$adminData = [];
if ($adminResult->num_rows > 0) {
    while ($row = $adminResult->fetch_assoc()) {
        $adminData[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin List</title>
    <link rel="stylesheet" href="admin_list.css">
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
        <h1>Admin List</h1>
    </div>
    
    <!-- Display delete message if set -->
    <?php if (isset($deleteMessage)): ?>
        <p><?php echo $deleteMessage; ?></p>
    <?php endif; ?>
    
    <!-- Search bar -->
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by Username, Role, or Email..." onkeyup="searchAdmins()">
        <button onclick="window.location.href='register.php'">Add Admin</button>
        <button onclick="exportAdminReport()">Export Admin Report</button>

    </div>
    
    <div class="logs-table">
        <table id="adminTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($adminData)): ?>
                <?php foreach ($adminData as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['id']); ?></td>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['role']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                <input type="hidden" name="delete_id" value="<?php echo $admin['id']; ?>">
                                <button type="submit">X</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No admins found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<script>
    function exportAdminReport() {
        let table = document.getElementById("adminTable");
        let rows = table.getElementsByTagName("tr");
        let csvContent = "ID,Username,Role,Email\n";

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
        link.download = "Admin_Report.csv";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
</body>
</html>