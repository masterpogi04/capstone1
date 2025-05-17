<?php
session_start();
include 'db.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function checkEmailExists($connection, $email) {
    $tables = [
        'tbl_admin',
        'tbl_adviser',
        'tbl_counselor',
        'tbl_dean',
        'tbl_facilitator',
        'tbl_guard',
        'tbl_instructor',
        'tbl_student'
    ];

    foreach ($tables as $table) {
        $stmt = $connection->prepare("SELECT email FROM $table WHERE email = ?");
        if ($stmt === false) {
            die("Prepare failed for table $table: " . $connection->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return true; // Email exists in this table
        }
    }
    
    return false; // Email not found in any table
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate student ID
    if (!preg_match('/^\d{9}$/', $student_id)) {
        $error = "Student ID must be exactly 9 digits.";
    }
    // Validate email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@cvsu\.edu\.ph$/', $email)) {
        $error = "Please use a valid @cvsu.edu.ph email address.";
    }
    // Validate password
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    }
    // Check if email already exists in any table
    elseif (checkEmailExists($connection, $email)) {
        $error = "This email address is already registered in the system. Please use a different email.";
    }
    else {
       
        $stmt = $connection->prepare("SELECT section_id, email FROM tbl_student WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Student ID not found in any section. Please contact your adviser.";
        } else {
            $row = $result->fetch_assoc();
            
            // Check if student ID already has an email
            if (!empty($row['email'])) {
                $error = "This Student ID is already registered with an email. Please contact your adviser if you need to update your email.";
            } else {
                $section_id = $row['section_id'];

                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Update student information
                $update_stmt = $connection->prepare("UPDATE tbl_student SET email = ?, password = ? WHERE student_id = ?");
                $update_stmt->bind_param("sss", $email, $hashed_password, $student_id);

                if ($update_stmt->execute()) {
                    // Send email with credentials
                    $mail = new PHPMailer(true);

                    try {
                        //Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'ceitguidanceoffice@gmail.com';
                        $mail->Password   = 'qapb ebhc owts ioel';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        //Recipients
                        $mail->setFrom('ceitguidanceoffice@gmail.com', 'CEIT Guidance Office');
                        $mail->addAddress($email);

                        //Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Your CEIT Guidance Office Account Credentials';
                        $mail->Body    = "Your account has been created successfully.<br><br>"
                                       . "Student ID: $student_id<br>"
                                       . "Email: $email<br>"
                                       . "Password: $password<br><br>"
                                       . "Use your CvSU E-mail and this password to login"
                                       . "Please keep this information secure.";

                        $mail->send();
                        $success = "Registration successful! Please check your email for your account credentials.";
                    } catch (Exception $e) {
                        $error = "Registration successful, but failed to send email. Error: {$mail->ErrorInfo}";
                    }
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT Guidance Office Student Registration</title>
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
            background:rgb(255, 255, 255);
            text-align: center;
            color:  #1b651b;
            font-size: clamp(16px, 4vw, 28px);
            font-family: Georgia, serif;
            letter-spacing: clamp(2px, 1vw, 5px);
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

        .registration-container {
            width: 100%;
            max-width:500px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            align-items: center;
        }

        .registration-form {
            text-align: left;
        }

        .registration-text {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
        }

        .registration-subtext {
            color:rgb(11, 11, 11);
            margin-bottom: 30px;
            font-size: 16px;
            text-align: center;
        }

        .form-group label {
            color: #333;
            font-weight: 500;
        }

        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: none;
            background: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 5px rgba(29, 28, 27, 0.5);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: white;
            box-shadow: 0 0 15px rgba(0, 168, 90, 0.5);
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background-color: #00a85a;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #00a85a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 168, 90, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color:rgb(13, 13, 13);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: white;
            text-decoration: underline;
        }
        .password-input {
            position: relative;
        }

        .password-input .form-control {
            padding-right: 40px;
        }

        .password-input .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            background: none;
            border: none;
            padding: 0;
        }

        .password-input .toggle-password:focus {
            outline: none;
        }
        .email-input-container {
            position: relative;
        }

        .email-input {
            width: 100%;
            padding-right: 110px;
        }

        .email-domain {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
            pointer-events: none;
        }
        /* Tablet devices */
@media (max-width: 992px) {
            .registration-container {
                padding: 25px;
                gap: 30px;
            }
        }

        /* Mobile devices */
        @media (max-width: 768px) {
            .registration-container {
                flex-direction: column;
                padding: 20px;
            }

            .profile-img-container {
                margin-bottom: 20px;
            }

            .login-form {
                width: 100%;
                margin-right: 0;
            }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .header {
                padding: 8px;
            }

            .content-wrapper {
                padding: 10px;
            }

            .registration-container {
                padding: 15px;
            }

            .form-control {
                padding: 8px 12px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .form-control {
                background: rgba(255, 255, 255, 0.9);
            }
        }
        @media (max-width: 768px) {
            .header {
                font-size: 24px;
                letter-spacing: 5px;
                padding: 12px;
            }

            .registration-container {
                flex-direction: column;
                padding: 20px;
            }

    
        }

    </style>
</head>
<body>
    <div class="header">CAVITE STATE UNIVERSITY-MAIN</div>

    <div class="content-wrapper">
        <div class="registration-container">
            <div class="registration-form">
                <div class="registration-text">Student Registration</div>
                <div class="registration-subtext">Create your account</div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="student_id">Student ID:</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" required pattern="\d{9}" maxlength="9" placeholder="Please Enter your Student ID" title="Please enter exactly 9 digits">
                    </div>
                    <div class="form-group">
                        <label for="email">Enter your email:</label>
                        <div class="email-input-container">
                       <input type="text" class="form-control email-input" id="email" name="email" placeholder="Please Enter your CvSU Email" required>
                       <span class="email-domain">@cvsu.edu.ph</span>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <div class="password-input">
                        <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Please Enter 8 characters" >
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fa fa-eye"></i>
                    </button>
                    </div>
                    </div>
                    <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <div class="password-input">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Please confirm your password">
                    <button type="button" class="toggle-password" onclick="toggleConfirmPassword()">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
                <div class="login-link">
                    <a href="login.php">Already have an account? Login here</a>
                </div>
            </div>
        </div>
    </div>
    <script>
       $(document).ready(function() {
    $('#student_id').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
    });

    $('#email').on('input', function() {
        // Remove any "@cvsu.edu.ph" if the user types it
        this.value = this.value.replace(/@cvsu\.edu\.ph$/, '');
    });

    $('form').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#student_id').val().length !== 9) {
            alert('Student ID must be exactly 9 digits.');
            return;
        }

        var emailInput = $('#email');
        var emailValue = emailInput.val();
        var fullEmail = emailValue.includes('@') ? emailValue : emailValue + '@cvsu.edu.ph';
        var password = $('#password').val();
        var confirmPassword = $('#confirm_password').val();

        if (password !== confirmPassword) {
            alert('Passwords do not match.');
            return;
        }

        if (!isValidEmail(fullEmail)) {
            alert('Please enter a valid email address.');
            return;
        }

        emailInput.val(fullEmail);

        if (confirm('Are you sure you want to register this student?')) {
            this.submit();
        } else {
            emailInput.val(emailValue); 
        }
    });

    function isValidEmail(email) {
        var regex = /^[a-zA-Z0-9._%+-]+@cvsu\.edu\.ph$/;
        return regex.test(email);
    }
});

function togglePassword() {
    var passwordInput = document.getElementById("password");
    var toggleIcon = document.querySelector(".toggle-password i");
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.classList.remove("fa-eye");
        toggleIcon.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        toggleIcon.classList.remove("fa-eye-slash");
        toggleIcon.classList.add("fa-eye");
    }
}


function toggleConfirmPassword() {
    var passwordInput = document.getElementById("confirm_password");
    var toggleIcon = passwordInput.nextElementSibling.querySelector("i");
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        toggleIcon.classList.remove("fa-eye");
        toggleIcon.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        toggleIcon.classList.remove("fa-eye-slash");
        toggleIcon.classList.add("fa-eye");
    }
}


document.querySelector('.toggle-password').addEventListener('mousedown', function(e) {
    e.preventDefault();
});
        </script>
</body>
</html>