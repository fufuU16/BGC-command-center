<?php
include 'db_connection.php';
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    // Log the logout action
    $action = "User logged out";
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    if (!$logStmt) {
        die("Prepare failed: " . $conn->error);
    }
 
    $logStmt->bind_param("issss", $user_id, $username, $action, $ip_address, $user_agent);
    if (!$logStmt->execute()) {
        die("Execute failed: " . $logStmt->error);
    }
    $logStmt->close();
} else {
    echo "No session data to log.";
}

session_destroy();

// Redirect to login page
header("Location: Login.php");
exit();
?>
