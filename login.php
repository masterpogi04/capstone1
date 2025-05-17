<?php
session_start();
include 'db.php'; 

$error_message = '';
$entered_email = '';

if (isset($_POST['email']) && isset($_POST['password'])) {
    session_regenerate_id(true);

    $email = $_POST['email'];
    $password = $_POST['password'];
    $entered_email = $email; // Save the entered email

// Check if login is a student email
$stmt = $connection->prepare("SELECT student_id, password, email, status, first_name, last_name 
                            FROM tbl_student 
                            WHERE email = ? 
                            AND status = 'active'
                            AND password IS NOT NULL
                            LIMIT 1");
    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['status'] === 'disabled') {
            $error_message = "Invalid credentials!";
        } else if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['student_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = 'student';
            header("Location: student/student_homepage.php");
            exit();
        } else {
            $error_message = "Invalid credentials!";
        }
    } else {
        // Check credentials for other user types
        $tables = ['tbl_admin', 'tbl_adviser', 'tbl_instructor', 'tbl_counselor', 'tbl_facilitator', 'tbl_dean', 'tbl_guard'];
        $user_type = null;
        $user_id = null;

        // Modify the login code to add debugging for facilitator logins:
foreach ($tables as $table) {
    // Different query for admin table (no status check) vs other tables
    $query = ($table === 'tbl_admin') 
        ? "SELECT id, email, password FROM $table WHERE email = ?"
        : "SELECT id, email, password, status FROM $table WHERE email = ?";
    
    $stmt = $connection->prepare($query);
    if ($stmt === false) {
        error_log("Prepare failed for table $table: " . $connection->error);
        continue;
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Execute failed for table $table: " . $stmt->error);
        continue;
    }

    $result = $stmt->get_result();
    error_log("Query result for $table: " . $result->num_rows . " rows found");

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        error_log("User found in $table: " . print_r($user, true));

        // Only check status for non-admin users
        if ($table !== 'tbl_admin' && isset($user['status']) && $user['status'] === 'disabled') {
            error_log("Account disabled in $table");
            $error_message = "Invalid credentials!";
            break;
        } else if (password_verify($password, $user['password'])) {
            error_log("Password verified for $table");
            $user_id = $user['id'];
            $user_type = str_replace('tbl_', '', $table);
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['user_type'] = $user_type;
            
            error_log("Session set - user_id: $user_id, user_type: $user_type");
            break;
        } else {
            error_log("Password verification failed for $table");
            $error_message = "Invalid password!";
        }
    }
}

        if ($user_type && $user_id && empty($error_message)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['email'] = $email;
            $_SESSION['user_type'] = $user_type;
            
            $dashboard_paths = [
                'admin' => 'admin/admin_homepage.php',
                'adviser' => 'adviser/adviser_homepage.php',
                'instructor' => 'instructor/instructor_homepage.php',
                'counselor' => 'counselor/counselor_homepage.php',
                'facilitator' => 'facilitator/facilitator_homepage.php',
                'dean' => 'dean/dean_homepage.php',
                'guard' => 'guard/guard_homepage.php'
            ];

            $redirect_path = $dashboard_paths[$user_type] ?? 'default_dashboard.php';
            header("Location: $redirect_path");
            exit();
        } else if (empty($error_message)) {
            $error_message = "Invalid credentials!";
        }
    }
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT Guidance Office Login</title>
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

        .login-container {
            width: 100%;
            max-width: min(90vw, 1000px);
            padding: clamp(15px, 4vw, 40px);
            border-radius: 20px;
            display: flex;
            gap: clamp(50px, 4vw, 70px);
            justify-content: space-between;
            align-items: center;
        }

        .profile-img-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .profile-img {
            width: clamp(120px, 30vw, 200px);
            height: clamp(120px, 30vw, 200px);
            background: #f0f0f0;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border: 4px solid rgba(255, 255, 255, 0.2);
        }

        .profile-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-img:hover {
            transform: scale(1.05);
        }

        .img-text {
            color: white;
            font-size: clamp(16px, 3vw, 24px);
            font-weight: bold;
            text-align: center;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }

        .login-form {
            flex: 1;
            max-width: 400px;
            text-align: left;
            padding: 30px;
            margin-right: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .login-text {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
            text-align: center;
        }

        .login-subtext {
            color: #666;
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
            
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background-color:   #00a85a;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color:  #00a85a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(24, 238, 0, 0.4);
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #333;
            text-decoration: underline;
        }

        @media (max-width: 992px) {
            .login-container {
                padding: 25px;
                gap: 30px;
            }
        }

        @media (max-width: 768px) {
            .login-container {
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

            .header {
                font-size: 24px;
                letter-spacing: 5px;
                padding: 12px;
            }

            .profile-img {
                width: 150px;
                height: 150px;
            }

            .img-text {
                font-size: 20px;
                margin-top: 15px;
            }
        }

        @media (max-width: 480px) {
            .header {
                font-size: 20px;
                letter-spacing: 3px;
                padding: 10px;
            }

            .content-wrapper {
                padding: 10px;
                margin-top: 40px;
            }

            .login-container {
                padding: 15px;
            }

            .profile-img {
                width: 120px;
                height: 120px;
            }

            .img-text {
                font-size: 18px;
            }

            .login-text {
                font-size: 24px;
            }

            .login-subtext {
                font-size: 14px;
            }

            .form-group label {
                font-size: 14px;
            }

            .form-control {
                padding: 10px 15px;
            }

            .btn-primary {
                padding: 10px;
                font-size: 16px;
            }
        }

        @media (prefers-color-scheme: dark) {
            .form-control {
                background: rgba(255, 255, 255, 0.9);
            }
        }
    </style>
</head>

<body>
    <div class="header">CAVITE STATE UNIVERSITY-MAIN</div>

    <div class="content-wrapper">
        <div class="login-container">
            <div class="profile-img-container">
                <div class="profile-img">
                      <a  href="welcome_page.html">
                    <img src="logo.png" alt="CEIT Guidance Office Logo">
                </a>
                </div>
                <div class="img-text">CEIT - GUIDANCE OFFICE</div>
            </div>
            <div class="login-form">
                <div class="login-text">Log in</div>
                <div class="login-subtext">Sign in to continue</div>

                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="form-group">
                        <label for="email">E-MAIL</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Enter your e-mail" required 
                               value="<?php echo htmlspecialchars($entered_email); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">PASSWORD</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                            <button type="button" class="btn position-absolute" 
                                    style="right: 10px; top: 50%; transform: translateY(-50%);"
                                    onclick="togglePassword()">
                                <i class="fas fa-eye" id="togglePassword"></i>
                            </button>
                        </div>
                    </div>
                    
                    <script>
                    function togglePassword() {
                        const passwordInput = document.getElementById('password');
                        const toggleIcon = document.getElementById('togglePassword');
                        
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            toggleIcon.className = 'fas fa-eye-slash';
                        } else {
                            passwordInput.type = 'password';
                            toggleIcon.className = 'fas fa-eye';
                        }
                    }
                    </script>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                <div class="text-center mt-3">
                    <a href="student_registration.php" class="text-dark">New student? Sign up here</a>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>