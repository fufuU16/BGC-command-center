<?php
// Include the database connection script
include 'db_connection.php';

session_start();

$message = ""; // Initialize a message variable
$remainingTime = 0; // Initialize remaining time

// Load reCAPTCHA Secret Key from environment variable or configuration file
$secretKey = getenv('6LcZjfQqAAAAAAsT6cGIhQmThQdrc_TCQePtDjsD'); // Ensure this environment variable is set

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    // Verify reCAPTCHA
    $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptchaData = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse
    ];
    $recaptchaOptions = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($recaptchaData)
        ]
    ];
    $recaptchaContext = stream_context_create($recaptchaOptions);
    $recaptchaVerify = file_get_contents($recaptchaUrl, false, $recaptchaContext);

    if ($recaptchaVerify === FALSE) {
        $message = "Error connecting to reCAPTCHA server.";
        error_log("Error connecting to reCAPTCHA server.");
    } else {
        $recaptchaResponseData = json_decode($recaptchaVerify);
        if ($recaptchaResponseData->success) {
            // Proceed with login logic
            // ... (rest of your login logic)
        } else {
            $message = "reCAPTCHA verification failed. Please try again.";
            error_log("reCAPTCHA verification failed: " . implode(', ', $recaptchaResponseData->{'error-codes'}));
        }
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
                
                <div class="g-recaptcha" data-sitekey="6LcZjfQqAAAAAGOvI8WCYSf9pXC3_KMCiTHRpQSR"></div> <!-- Replace with your actual site key -->
                
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </main>
</body>
</html>
