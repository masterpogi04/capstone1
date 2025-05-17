<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Fetch current admin details
$stmt = $connection->prepare("SELECT username, email, first_name, middle_initial, last_name FROM tbl_admin WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $username = $admin['username'];
    $email = $admin['email'];
    $first_name = $admin['first_name'];
    $middle_initial = $admin['middle_initial'];
    $last_name = $admin['last_name'];
    
    $_SESSION['first_name'] = $first_name;
    $_SESSION['middle_initial'] = $middle_initial;
    $_SESSION['last_name'] = $last_name;
} else {
    die("Admin not found.");
}
$stmt->close();

$update_successful = false;
$error_message = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_first_name = trim($_POST['first_name']);
    $new_middle_initial = trim($_POST['middle_initial']);
    $new_last_name = trim($_POST['last_name']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Update names
    if (!empty($new_first_name) || !empty($new_middle_initial) || !empty($new_last_name)) {
        $update_stmt = $connection->prepare("UPDATE tbl_admin SET first_name = ?, middle_initial = ?, last_name = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("sssi", $new_first_name, $new_middle_initial, $new_last_name, $admin_id);
        if ($update_stmt->execute()) {
            $first_name = $new_first_name;
            $middle_initial = $new_middle_initial;
            $last_name = $new_last_name;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['middle_initial'] = $middle_initial;
            $_SESSION['last_name'] = $last_name;
            $update_successful = true;
        }
        $update_stmt->close();
    }
    
    // Update password
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $connection->prepare("UPDATE tbl_admin SET password = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $admin_id);
            if ($update_stmt->execute()) {
                $update_successful = true;
            }
            $update_stmt->close();
        } else {
            $error_message[] = "Passwords do not match.";
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
    <title>Admin Profile</title>
    <link rel="stylesheet" type="text/css" href="admin_styles.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
            :root {
            --primary-color: #003366;
            --secondary-color: #4a90e2;
            --background-color: #f4f7fa;
            --text-color: #333;
            --border-color: #d1d9e6;
            --success-color: #28a745;
            --error-color: #dc3545;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }



        .main-content {
            margin-left: 250px; /* Adjust based on your sidebar width */
            padding: 2rem;
        }

        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            border: none;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.75rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 0.7;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: #3a7bc8;
            box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3);
        }

        .btn-outline-secondary {
            color: var(--primary-color);
            border-color: var(--border-color);
        }

        .btn-outline-secondary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .input-group-append .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        #profile_picture {
            border: 1px solid var(--border-color);
            padding: 0.5rem;
            border-radius: 4px;
        }

        img.profile-picture {
            max-width: 200px;
            border-radius: 50%;
            box-shadow: var(--box-shadow);
            margin-bottom: 1rem;
        }



        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .btn-primary {
                width: 100%;
            }
        }


        .swal2-popup {
            font-family: 'Roboto', sans-serif;
        }

        .swal2-title {
            color: var(--primary-color);
        }

        .swal2-confirm {
            background-color: var(--secondary-color) !important;
        }

        .swal2-confirm:focus {
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.5) !important;
        }
    </style>
</head>
<body>
    <div class="header">
        CAVITE STATE UNIVERSITY-MAIN
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="container mt-4">
            <br><br>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Update Profile</h1>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form id="updateProfileForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username:</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email:</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">First Name:</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_initial" class="form-label">Middle Initial:</label>
                                    <input type="text" class="form-control" id="middle_initial" name="middle_initial" maxlength="1" value="<?php echo htmlspecialchars($middle_initial); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Last Name:</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">New Password:</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm New Password:</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div class="footer">
        <p>Contact number | Email | Copyright</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($update_successful): ?>
            Swal.fire({
                title: 'Success!',
                text: 'Your profile has been updated successfully.',
                icon: 'success',
                confirmButtonText: 'OK'
            });
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?php echo implode(" ", $error_message); ?>',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            <?php endif; ?>

            function togglePasswordVisibility(inputId, toggleId) {
                const input = document.getElementById(inputId);
                const toggle = document.getElementById(toggleId);
                
                toggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }

            togglePasswordVisibility('password', 'togglePassword');
            togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');

            document.getElementById('updateProfileForm').addEventListener('submit', function(e) {
                var password = document.getElementById('password').value;
                var confirmPassword = document.getElementById('confirm_password').value;

                if (password !== confirmPassword) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Passwords do not match.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
</body>
</html>