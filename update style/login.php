<?php
session_start();
include 'db.php'; 

if (isset($_POST['login']) && isset($_POST['password'])) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $login = $_POST['login'];
    $password = $_POST['password'];

    // Check if login is a student ID
    $stmt = $connection->prepare("SELECT student_id, password FROM tbl_student WHERE student_id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['student_id'];
            $_SESSION['username'] = $login;
            $_SESSION['user_type'] = 'student';
            header("Location: student/student_homepage.php");
            exit();
        }
    } else {
        // Check credentials for other user types
        $tables = ['tbl_admin', 'tbl_adviser', 'tbl_instructor', 'tbl_counselor', 'tbl_facilitator', 'tbl_dean', 'tbl_guard'];
        $user_type = null;
        $user_id = null;

        foreach ($tables as $table) {
            $stmt = $connection->prepare("SELECT id, username, password FROM $table WHERE username = ? OR email = ?");
            if ($stmt === false) {
                die("Prepare failed for table $table: " . $connection->error);
            }
            $stmt->bind_param("ss", $login, $login);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $user_id = $user['id'];
                    $user_type = str_replace('tbl_', '', $table);
                    break;
                }
            }
        }

        if ($user_type && $user_id) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $login;
            $_SESSION['user_type'] = $user_type;
            
            // Redirect to the appropriate dashboard based on user type
            $dashboard_paths = [
                'admin' => 'admin/admin_dashboard.php',
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
        }
    }

    // If we reach here, authentication failed
    echo '<script type="text/javascript">';
    echo 'alert("Invalid credentials!");';
    echo 'window.location.href = window.location.href;';
    echo '</script>';
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
            margin-right:40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .login-text {
            color: white;
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .login-subtext {
            color: #e0e0e0;
            margin-bottom: 30px;
            font-size: 16px;
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

        .btn-primary {
            width: 100%;
            padding: 12px;
            background-color:#d28750;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #cf7e43;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 168, 90, 0.4);
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #e0e0e0;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: white;
            text-decoration: underline;
        }
/* Tablet devices */
@media (max-width: 992px) {
            .login-container {
                padding: 25px;
                gap: 30px;
            }
        }

        /* Mobile devices */
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
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .header {
                padding: 8px;
            }

            .content-wrapper {
                padding: 10px;
            }

            .login-container {
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

            .login-container {
                flex-direction: column;
                padding: 20px;
            }

            .profile-img-container {
                margin-right: 0;
                margin-bottom: 30px;
            }

            .profile-img {
                width: 150px;
                height: 150px;
            }

            .login-form {
                width: 100%;
                margin-right: 0;
                padding: 20px;
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
    </style>
</head>
<body>
    <div class="header">CAVITE STATE UNIVERSITY-MAIN</div>

    <div class="content-wrapper">
    <div class="login-container">
            <div class="profile-img-container">
                <div class="profile-img">
                <img src="CEITLOGO.png" alt="CEIT Guidance Office Logo">
                </div>
                <div class="img-text">CEIT - GUIDANCE OFFICE</div>
            </div>
            <div class="login-form">
                <div class="login-text">Welcome Back</div>
                <div class="login-subtext">Sign in to continue</div>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="form-group">
                        <label for="login">USERNAME / EMAIL / STUDENT NUMBER</label>
                        <input type="text" class="form-control" id="login" name="login" placeholder="Enter your credentials" required>
                    </div>
                    <div class="form-group">
                        <label for="password">PASSWORD</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                <div class="text-center mt-3">
                    <a href="student_registration.php" class="text-white">New student? Sign up here</a>
                </div>
                <div class="forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>






<!--
    <h1>Login</h1>
    <form action="login.php" method="POST">
        <div>
            <label for="login">Username, Email, or Student ID:</label>
            <input type="text" id="login" name="login" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <input type="submit" value="Login">
    </form>
</div>
</div>

-->

