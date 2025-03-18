<?php
// Include the database connection script
include 'db_connection.php';

session_start();

$message = ""; // Initialize a message variable
$remainingTime = 0; // Initialize remaining time

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepare and execute the query to check if the username exists
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        $current_time = new DateTime();
        $locked_until = null;

        // Check if the account is locked
        $lockCheckStmt = $conn->prepare("SELECT locked_until FROM login_attempts WHERE username = ? ORDER BY last_attempt DESC LIMIT 1");
        $lockCheckStmt->bind_param("s", $username);
        $lockCheckStmt->execute();
        $lockCheckStmt->bind_result($locked_until);
        $lockCheckStmt->fetch();
        $lockCheckStmt->close();

        if ($locked_until) {
            $locked_until = new DateTime($locked_until);
        }

        // Count failed attempts in the last 15 minutes
        $attemptStmt = $conn->prepare("SELECT COUNT(*) as attempt_count FROM login_attempts WHERE username = ? AND last_attempt > (NOW() - INTERVAL 15 MINUTE)");
        $attemptStmt->bind_param("s", $username);
        $attemptStmt->execute();
        $result = $attemptStmt->get_result();
        $row = $result->fetch_assoc();
        $attempts = $row['attempt_count'] ?? 0;
        $attemptStmt->close();

        // Check if account should be locked
        if ($attempts >= 2) { // 2 previous attempts + current = 3
            $locked_until_time = (new DateTime())->add(new DateInterval('PT3M'))->format('Y-m-d H:i:s');
            $updateStmt = $conn->prepare("INSERT INTO login_attempts (username, last_attempt, locked_until) VALUES (?, NOW(), ?)");
            $updateStmt->bind_param("ss", $username, $locked_until_time);
            $updateStmt->execute();
            $updateStmt->close();

            $locked_until = new DateTime($locked_until_time);
            $interval = $current_time->diff($locked_until);
            $remainingTime = $interval->i * 60 + $interval->s; // Total remaining seconds
            $message = "Account is locked. Please try again in <span id='countdown'></span>.";
        } elseif ($locked_until && $current_time < $locked_until) {
            $interval = $current_time->diff($locked_until);
            $remainingTime = $interval->i * 60 + $interval->s; // Total remaining seconds
            $message = "Account is locked. Please try again in <span id='countdown'></span>.";
        } else {
            // Verify password and check role
            if (password_verify($password, $hashed_password) && in_array($role, ['SuperAdmin', 'MidAdmin', 'Admin'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['user_id'] = $user_id;

                // Reset attempts on successful login
                $resetStmt = $conn->prepare("DELETE FROM login_attempts WHERE username = ?");
                $resetStmt->bind_param("s", $username);
                $resetStmt->execute();
                $resetStmt->close();

                // Log the login event
                $action = "User logged in";
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];

                $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
                $logStmt->bind_param("issss", $user_id, $username, $action, $ip_address, $user_agent);
                $logStmt->execute();
                $logStmt->close();

                header("Location: index.php");
                exit();
            } else {
                // Record failed attempt
                $failedAttemptStmt = $conn->prepare("INSERT INTO login_attempts (username, last_attempt) VALUES (?, NOW())");
                $failedAttemptStmt->bind_param("s", $username);
                $failedAttemptStmt->execute();
                $failedAttemptStmt->close();

                $message = "Invalid credentials or insufficient permissions.";
            }
        }
    } else {
        $message = "Invalid credentials.";
    }

    if (isset($stmt)) {
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BGC Bus Login</title>
    <link rel="stylesheet" href="Login.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var remainingTime = <?php echo $remainingTime; ?>;
            var countdownElement = document.getElementById('countdown');

            function updateCountdown() {
                if (remainingTime > 0) {
                    var minutes = Math.floor(remainingTime / 60);
                    var seconds = remainingTime % 60;
                    countdownElement.textContent = minutes + " minutes and " + seconds + " seconds";
                    remainingTime--;
                } else {
                    countdownElement.textContent = "0 minutes and 0 seconds";
                }
            }

            if (countdownElement) {
                updateCountdown();
                setInterval(updateCountdown, 1000);
            }
        });
    </script>
</head>
<body>
    <main>
        <div class="login-container">
            <?php if (!empty($message)): ?>
                <div class="message-box error"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="bgclogo">
                    <img src="../image/bgc.PNG" alt="Bgc Logo">
                </div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter Username" required>
                
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter Password" required>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </main>
</body>
</html>