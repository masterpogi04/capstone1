<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['token']) && isset($_GET['table'])) {
    $token = $_GET['token'];
    $table = $_GET['table'];
    
    // Verify token and check if it's still valid
    $stmt = $connection->prepare("SELECT " . ($table === 'tbl_student' ? 'student_id' : 'id') . " FROM $table WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error_message = "Invalid or expired token.";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $table = $_POST['table'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    // Update password and clear token
    $stmt = $connection->prepare("UPDATE $table SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?");
    $stmt->bind_param("ss", $new_password, $token);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $message = "Your password has been successfully reset. You can now login with your new password.";
    } else {
        $error_message = "Password reset failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT Guidance Office - Reset Password</title>
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
            max-width: 500px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            align-items: center;
        }

        .reset-form {
            text-align: left;
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
            margin-bottom: 15px;
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
            cursor: pointer;
        }

        .btn-reset:hover {
            background-color: #cf7e43;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 168, 90, 0.4);
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

        .error {
            margin-top: 15px;
            padding: 10px;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.9);
            color: #dc3545;
            text-align: center;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 20px;
            }

            .form-title {
                font-size: 28px;
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
            <div class="reset-form">
            <div class="form-title">Reset Password</div>
            <?php if (isset($message)): ?>
                <p class="message"><?php echo $message; ?></p>
            <?php elseif (isset($error_message)): ?>
                <p class="error"><?php echo $error_message; ?></p>
            <?php else: ?>
                <div class="form-subtitle">Please enter your new password below.</div>
                <form method="post" onsubmit="return validatePassword()">
                    <input type="hidden" name="token" value="<?php echo $token; ?>">
                    <input type="hidden" name="table" value="<?php echo $table; ?>">
                <div class="form-group">
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New Password" required>
                </div>
                 <div class="form-group">
                 <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm New Password" required>
                 </div>
                </form>
                <button type="submit" class="btn btn-reset">Reset Password</button>
               
            <?php endif; ?>
            <a href="login.php" class="btn btn-reset">Back to Login</a>
            </div>
        </div>
    </div>


    <script>
    function validatePassword() {
        var newPassword = document.getElementById("new_password").value;
        var confirmPassword = document.getElementById("confirm_password").value;
        if (newPassword != confirmPassword) {
            alert("Passwords do not match.");
            return false;
        }
        return true;
    }
    </script>
</body>
</html>