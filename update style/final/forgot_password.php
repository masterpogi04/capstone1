<?php
session_start();
include 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    $tables = ['tbl_student', 'tbl_admin', 'tbl_counselor', 'tbl_facilitator', 'tbl_dean', 'tbl_instructor', 'tbl_adviser', 'tbl_guard'];
    $user = null;
    $table = '';

    foreach ($tables as $t) {
        switch ($t) {
            case 'tbl_student':
                $stmt = $connection->prepare("SELECT student_id, first_name, last_name FROM $t WHERE email = ?");
                break;
            case 'tbl_admin':
            case 'tbl_counselor':
            case 'tbl_facilitator':
            case 'tbl_dean':
            case 'tbl_instructor':
            case 'tbl_adviser':
            case 'tbl_guard':
                $stmt = $connection->prepare("SELECT id, username FROM $t WHERE email = ?");
                break;
            default:
                continue 2;
        }
        
        if ($stmt === false) {
            die("Prepare failed: " . $connection->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $table = $t;
            break;
        }
        $stmt->close();
    }
    
    if ($user) {
        $token = bin2hex(random_bytes(50));
        
        if ($table === 'tbl_student') {
            $stmt = $connection->prepare("UPDATE $table SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE student_id = ?");
        } else {
            $stmt = $connection->prepare("UPDATE $table SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        }
        
        if ($stmt === false) {
            die("Prepare failed: " . $connection->error);
        }
        $stmt->bind_param("ss", $token, $user[$table === 'tbl_student' ? 'student_id' : 'id']);
        $stmt->execute();
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ceitguidanceoffice@gmail.com';
            $mail->Password   = 'qapb ebhc owts ioel';  
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('ceitguidanceoffice@gmail.com', 'CEIT Guidance Office');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            
            $user_identifier = $table === 'tbl_student' ? 
                $user['first_name'] . ' ' . $user['last_name'] : 
                $user['username'];

            $mail->Body    = "Hi {$user_identifier},<br><br>Click the link below to reset your password:<br><br>
                              <a href='http://localhost/capstone1/reset_password.php?token=$token&table=" . urlencode($table) . "'>Reset Password</a><br><br>
                              This link will expire in 1 hour.
                              <br><br>
                            This is an automated email, please do not reply
                              ";

            $mail->send();
            $message = "Password reset link has been sent to your email.";
        } catch (Exception $e) {
            $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT Guidance Office - Forgot Password</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://use.fontawesome.com/releases/v5.15.3/js/all.js"></script>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #00a85a, #004d4d);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            width: 100%;
            padding: clamp(10px, 2vw, 15px);
            background: #ff7f00;
            text-align: center;
            color: white;
            font-size: clamp(16px, 4vw, 28px);
            font-family: Georgia, serif;
            letter-spacing: clamp(3px, 1vw, 7px);
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: clamp(10px, 3vw, 20px);
            margin-top: clamp(20px, 5vw, 20px);
        }

        .login-container {
            width: 100%;
            max-width:500px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            align-items: center;
        }
        .forgot-form {
            text-align: left;
        }

        .font-text {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
        }

        .form-title {
            color: white;
            font-size: 32px;
            margin-bottom: 9px;
            font-weight: bold;
        }

        .form-subtitle {
            color: #e0e0e0;
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.5;
        }

        .form-group label {
            color: white;
            font-weight: 500;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: none;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: white;
            box-shadow: 0 0 15px rgba(0, 168, 90, 0.5);
        }

        .btn-reset {
            width: 100%;
            padding: 12px;
            margin-top: 9px;
            background-color: #d28750;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background-color: #cf7e43;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 168, 90, 0.4);
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .back-to-login a:hover {
            color: white;
            text-decoration: underline;
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.9);
            color: #00a85a;
            text-align: center;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                padding: 20px;
            }

            .profile-img-container {
                margin-bottom: 20px;
            }

            .forgot-form {
                width: 100%;
                margin-right: 0;
            }
        }

        @media (max-width: 480px) {
            .header {
                font-size: 20px;
                letter-spacing: 3px;
            }

            .form-title {
                font-size: 24px;
            }

            .form-subtitle {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="header">CAVITE STATE UNIVERSITY-MAIN</div>
    <div class="content-wrapper">
        <div class="login-container">
        <div class="forgot-form">
        <div class="form-title">Forgot Password</div>
            <?php if (isset($message)) echo "<p class='message'>$message</p>"; ?>
            <form method="post">
            <div class="form-subtitle">Please enter the email address associated with your account. We'll send you a link to reset your password.</p>
            <div class="form-group">
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                <button type="submit" class="btn btn-reset" >Reset Password</button>
            </form>
            <a href="login.php" class="btn btn-reset" >Back to Login</a>
        </div>
    </div>
</body>
</html>