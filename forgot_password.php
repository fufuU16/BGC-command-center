<?php
// forgot_password.php
include 'db_connection.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

session_start();
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // Check if the email exists in the database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate OTP
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $email;

        // Send OTP to email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'bgcbus2025capstone@gmail.com';
            $mail->Password = 'qxmauupgbiczqaci'; // Updated App Password (without spaces)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        
            $mail->setFrom('bgcbus2025capstone@gmail.com', 'BGC Bus');
            $mail->addAddress($email);
        
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "
                    <p>Dear Passenger,</p>
                    <p>Your One-Time-Password (OTP) is <strong>$otp</strong>.</p>
                    <p>Please do not share this code with anyone.</p>
                    <p>Thank you,</p>
                    <p>BGC Bus Management</p>
                ";
        
            $mail->send();
            // Redirect to verify_otp.php after successful OTP send
            header('Location: verify_otp.php');
            exit();
        } catch (Exception $e) {
            $message = "Failed to send OTP. Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        $message = "Email not found.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
                <label for="email">Enter your email</label>
                <input type="email" id="email" name="email" placeholder="Enter Email" required>
                <button type="submit">Send OTP</button>
            </form>
        </div>
    </main>
</body>
</html>