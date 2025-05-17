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
$stmt = $connection->prepare("SELECT username, email, first_name, middle_initial, last_name, profile_picture FROM tbl_dean WHERE id = ?");
if (!$stmt) {
    die("Error preparing statement: " . $connection->error);
}

$stmt->bind_param("i", $dean_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $dean = $result->fetch_assoc();
    $username = $dean['username'];
    $email = $dean['email'];
    $first_name = $dean['first_name'];
    $middle_initial = $dean['middle_initial'];
    $last_name = $dean['last_name'];
    $profile_picture = $dean['profile_picture'];
} else {
    die("Dean not found.");
}
$stmt->close();

$update_successful = false;
$error_message = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_first_name = trim($_POST['first_name']);
    $new_middle_initial = trim($_POST['middle_initial']);
    $new_last_name = trim($_POST['last_name']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Update profile
    $update_stmt = $connection->prepare("UPDATE tbl_dean SET 
        username = ?, 
        email = ?, 
        first_name = ?, 
        middle_initial = ?, 
        last_name = ?, 
        updated_at = NOW() 
        WHERE id = ?");
        
    if (!$update_stmt) {
        die("Error preparing update statement: " . $connection->error);
    }

    $update_stmt->bind_param("sssssi", 
        $new_username, 
        $new_email, 
        $new_first_name,
        $new_middle_initial,
        $new_last_name,
        $dean_id
    );

    if ($update_stmt->execute()) {
        $username = $new_username;
        $email = $new_email;
        $first_name = $new_first_name;
        $middle_initial = $new_middle_initial;
        $last_name = $new_last_name;
        $update_successful = true;
    }
    $update_stmt->close();
    
    // Update password
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $connection->prepare("UPDATE tbl_dean SET password = ?, updated_at = NOW() WHERE id = ?");
            if (!$update_stmt) {
                die("Error preparing password update statement: " . $connection->error);
            }
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
    <title>Dean Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
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

body {
    background-color: var(--background-color);
    font-family: Arial, sans-serif;
}

.header {
    background-color: #F4A261;
    padding: 15px;
    color: black;
    text-align: center;
    font-weight: bold;
}

.main-content {
    margin-left: 250px;
    padding: 2rem;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Card styles */
.card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.card-body {
    padding: 2rem;
}

/* Form styles */
.form-group {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(26, 110, 71, 0.25);
}

.form-control[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

/* Button styles */

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
/* Password toggle button */
.input-group-append .btn {
    border: 1px solid var(--border-color);
    background-color: #fff;
    color: var(--text-color);
}

.input-group-append .btn:hover {
    background-color: #f8f9fa;
}

/* Responsive layout */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: -0.75rem;
}

.col-md-4, .col-md-6 {
    padding: 0.75rem;
}

.col-md-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }
    
    .col-md-4, .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
    </style>
</head>
<body>

<div class="header">
        <h1>CAVITE STATE UNIVERSITY-MAIN<h1>
    </div>
    <?php include 'dean_sidebar.php'; ?>
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
               
<div class="row">        
    <div class="col-md-4">
            <div class="form-group">      
                <label for="username">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
    </div>
</div>
<div class="col-md-4">
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
        </div>
    </div>
</div>
<div class="row">
        <div class="col-md-4">
                        <label for="first_name">First Name:</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="middle_initial">Middle Initial:</label>
                        <input type="text" class="form-control" id="middle_initial" name="middle_initial" maxlength="1" value="<?php echo htmlspecialchars($middle_initial); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="last_name">Last Name:</label>
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