<?php
// verify_otp.php

session_start();
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['otp']) {
        header("Location: reset_password.php");
        exit();
    } else {
        $message = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="Login.css">
</head>
<body>
    <main>
        <div class="login-container">
            <?php if (!empty($message)): ?>
                <div class="message-box error"><?php echo $message; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <label for="otp">Enter OTP</label>
                <input type="text" id="otp" name="otp" placeholder="Enter OTP" required>
                <button type="submit">Verify OTP</button>
            </form>
        </div>
    </main>
</body>
</html>