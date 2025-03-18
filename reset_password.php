<?php
// reset_password.php
include 'db_connection.php';

session_start();
$message = "";

if (!isset($_SESSION['email'])) {
    // Redirect to login or forgot password page if email is not set
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['email'];

// Fetch the username associated with the email
$stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate password
    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/', $new_password)) {
        $message = "Password must be at least 12 characters long, include at least one uppercase letter, one digit, and one special character.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute()) {
            $message = "Password reset successfully.";
            unset($_SESSION['otp']);
            unset($_SESSION['email']);
        } else {
            $message = "Failed to reset password. Please try again.";
        }

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
    <title>Reset Password</title>
    <link rel="stylesheet" href="Login.css">
</head>
<body>
    <main>
        <div class="login-container">
           
            <?php if (!empty($message)): ?>
                <div class="message-box <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
            <h2>Reset Password</h2>
            <p>User: <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($email); ?>)</p>
                <label for="new_password">Enter New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter New Password" required>
                
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
                
                <button type="submit">Reset Password</button>
            </form>
        </div>
    </main>
</body>
</html>