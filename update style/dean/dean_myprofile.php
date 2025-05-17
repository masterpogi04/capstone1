<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a dean
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: ../login.php");
    exit();
}

$dean_id = $_SESSION['user_id'];

// Fetch current dean details
$stmt = $connection->prepare("SELECT username, email, name, profile_picture FROM tbl_dean WHERE id = ?");
$stmt->bind_param("i", $dean_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $dean = $result->fetch_assoc();
    $username = $dean['username'];
    $email = $dean['email'];
    $name = $dean['name'];
    $profile_picture = $dean['profile_picture'];
} else {
    die("dean not found.");
}
$stmt->close();

$update_successful = false;
$error_message = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_name = trim($_POST['name']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Update profile
    $update_stmt = $connection->prepare("UPDATE tbl_dean SET username = ?, email = ?, name = ?, updated_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("sssi", $new_username, $new_email, $new_name, $dean_id);
    if ($update_stmt->execute()) {
        $username = $new_username;
        $email = $new_email;
        $name = $new_name;
        $update_successful = true;
    }
    $update_stmt->close();
    
             // Update password
            if (!empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $connection->prepare("UPDATE tbl_dean SET password = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("si", $hashed_password, $dean_id);
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
    <title>dean Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
      :root {
    --primary-color: #3498db;
    --secondary-color: #2980b9;
    --primary-dark: #00674b;
    --text-color: #2c3e50;
    --background-color: #f8f9fa;
    --input-background: #fff;
    --input-border: #bdc3c7;
    --success-color: #2ecc71;
    --error-color: #e74c3c;
    --shadow-color: rgba(0, 0, 0, 0.1);
}

/* Base Styles */
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    background-color: var(--background-color);
}

/* Main Content Layout */
.main-content {
    margin-left: 250px;
    padding: 3rem;
    margin-top: 70px;
    min-height: calc(100vh - 70px);
    background: #f5f6fa;
    transition: margin-left 0.3s ease;
}

/* Page Title */
h2 {
    color: var(--primary-dark);
    font-weight: 700;
    font-size: 1.75rem;
    text-align: center;
    margin: 10px 0 25px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

/* Form Container */
.form-container {
    max-width: 800px;
    padding: 3rem;
    margin: 0 auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
    margin-left: -60px;
}

.card-body {
    padding: 1rem;
}

/* Form Groups and Controls */
.form-group {
    margin-bottom: 1.5rem;
}

.form-control {
    width: 100%;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    transition: border-color 0.2s ease;
    background: #f8f9fa;
}

.form-control:focus {
    border-color: #4a90e2;
    outline: none;
    box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
}

/* Input Groups */
.input-group {
    position: relative;
    display: flex;
    align-items: stretch;
    width: 100%;
}

.input-group .form-control {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group-append {
    display: flex;
}

.input-group-append button {
    padding: 0.39rem;
    border: 1px solid #ddd;
    border-left: none;
    background: #fff;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.input-group-append button:hover {
    background: #f8f9fa;
}

/* Submit Button */
.btn-primary {
    background: #4a90e2;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    width: 100%;
    max-width: 200px;
    margin: 1.5rem auto 0;
    display: block;
    cursor: pointer;
    transition: background 0.2s ease;
}

.btn-primary:hover {
    background: #357abd;
}

/* Footer */
.footer {
    background-color: #F4A261;
    color: black;
    text-align: center;
    padding: 10px;
    margin-top: auto;
}

/* Responsive Design */
@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
    
    .form-container {
        margin: 0 1rem;
    }
}

@media (max-width: 768px) {
    .col-md-6 {
        flex: 0 0 100%;
    }
    
    .form-container {
        padding: 1.5rem;
    }
}

@media (max-width: 576px) {
    .main-content {
        padding: 1rem;
    }
    
    .form-container {
        padding: 1rem;
        margin: 0;
    }
    
    .btn-primary {
        max-width: 100%;
    }
}

/* Touch Device Optimization */
@media (hover: none) and (pointer: coarse) {
    .form-control,
    .btn-primary,
    .input-group-append button {
        min-height: 48px;
    }
    
    .form-control {
        font-size: 16px;
    }
}
    </style>
</head>
<body>
<div class="header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1>CEIT - GUIDANCE OFFICE</h1>
        <i class="fas fa-bell notification-icon" onclick="toggleNotifications()"></i>
    </div>
    <?php include 'dean_sidebar.php'; ?>
    <main class="main-content">

        <div class="content-wrapper">
            <h2 class="mb-4">Update Profile</h2>

            <div class="form-container">
                <form id="updateProfileForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">New Password:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>

        <footer class="footer">
            <p>&copy; 2024 All Rights Reserved</p>
        </footer>
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

            var form = document.getElementById('updateProfileForm');
            var originalFormData = new FormData(form);

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var currentFormData = new FormData(form);
                var hasChanges = false;

                for (var pair of currentFormData.entries()) {
                    if (pair[1] !== originalFormData.get(pair[0])) {
                        hasChanges = true;
                        break;
                    }
                }

                if (!hasChanges) {
                    Swal.fire({
                        title: 'No Changes',
                        text: 'No changes were made to your profile.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                var password = document.getElementById('password').value;
                var confirmPassword = document.getElementById('confirm_password').value;

                if (password !== confirmPassword) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Passwords do not match.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Confirm Update',
                    text: 'Are you sure you want to update your profile?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'No, cancel!',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>