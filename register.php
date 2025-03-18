<?php
// register.php
include 'db_connection.php';

session_start();
$successMessage = '';
$errorMessage = '';

// Function to log activity
function logActivity($conn, $user_id, $username, $action) {
    $logQuery = "INSERT INTO activity_logs (user_id, username, action) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($logQuery)) {
        $stmt->bind_param("iss", $user_id, $username, $action);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($username && $password && $email && $role) {
        // Check if the username already exists
        $checkQuery = "SELECT id FROM users WHERE username = ?";
        if ($checkStmt = $conn->prepare($checkQuery)) {
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $errorMessage = "Username already exists. Please choose a different username.";
            } else {
                // Hash the password for security
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user details
                $insertQuery = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
                if ($insertStmt = $conn->prepare($insertQuery)) {
                    $insertStmt->bind_param("ssss", $username, $hashedPassword, $email, $role);
                    if ($insertStmt->execute()) {
                        $successMessage = "User registered successfully!";
                        // Log the registration activity using the current session user ID
                        $currentUserId = $_SESSION['user_id'] ?? null; // Ensure user_id is stored in session
                        $currentUsername = $_SESSION['username'] ?? 'Unknown';
                        if ($currentUserId) {
                            $actionDescription = "Registered a new user: $username";
                            logActivity($conn, $currentUserId, $currentUsername, $actionDescription);
                        } else {
                            $errorMessage = "Error: User ID not found in session.";
                        }
                    } else {
                        $errorMessage = "Error registering user: " . $insertStmt->error;
                    }
                    $insertStmt->close();
                }
            }
            $checkStmt->close();
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
    <title>User Registration</title>
    <link rel="stylesheet" href="register.css">
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
        <h1>User Registration</h1>
    </div>
    <div class="form-container">
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="SuperAdmin">SuperAdmin</option>
                    <option value="MidAdmin">MidAdmin</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <button type="submit">Register</button>
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