<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

$adviser_id = $_SESSION['user_id'];
$error_message = [];
$update_successful = false;

// Fetch current adviser details
$query = "SELECT username, email, first_name, middle_initial, last_name, profile_picture FROM tbl_adviser WHERE id = ?";
$stmt = $connection->prepare($query);

if (!$stmt) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $adviser = $result->fetch_assoc();
    $username = $adviser['username'];
    $email = $adviser['email'];
    $first_name = $adviser['first_name'];
    $middle_initial = $adviser['middle_initial'];
    $last_name = $adviser['last_name'];
    $profile_picture = $adviser['profile_picture'];
} else {
    die("Adviser not found.");
}
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_first_name = trim($_POST['first_name']);
    $new_middle_initial = trim($_POST['middle_initial']);
    $new_last_name = trim($_POST['last_name']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Update name fields
    if (!empty($new_first_name) && !empty($new_last_name)) {
        $update_stmt = $connection->prepare("UPDATE tbl_adviser SET first_name = ?, middle_initial = ?, last_name = ?, updated_at = NOW() WHERE id = ?");
        if (!$update_stmt) {
            $error_message[] = "Error preparing name update: " . $connection->error;
        } else {
            $update_stmt->bind_param("sssi", $new_first_name, $new_middle_initial, $new_last_name, $adviser_id);
            if ($update_stmt->execute()) {
                $first_name = $new_first_name;
                $middle_initial = $new_middle_initial;
                $last_name = $new_last_name;
                $update_successful = true;
            } else {
                $error_message[] = "Error updating name: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
    }
    
    // Update password only if both password fields are filled
if (!empty($new_password) && !empty($confirm_password)) {
    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $connection->prepare("UPDATE tbl_adviser SET password = ?, updated_at = NOW() WHERE id = ?");
        if (!$update_stmt) {
            $error_message[] = "Error preparing password update: " . $connection->error;
        } else {
            $update_stmt->bind_param("si", $hashed_password, $adviser_id);
            if ($update_stmt->execute()) {
                $update_successful = true;
            } else {
                $error_message[] = "Error updating password: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
    } else {
        $error_message[] = "Passwords do not match.";
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="autocomplete" content="off">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Adviser Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
    margin-left: 250px;
    padding: 2rem;
}
h1 {
    font-size: 2.2rem;
    font-weight: 600;
    margin: 0;
    
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
</style>
</head>
<body>
<div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    
    <?php include 'adviser_sidebar.php'; ?>
    <div class="main-content">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-1 pb-2 mb-3 border-bottom">  
    <h1>Update Profile</h1>
    </div>
                
                <div class="card">
                    <div class="card-body">
                        <form id="updateProfileForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username:</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email:</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">First Name:</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" pattern="[A-Za-z ]+" onkeypress="return /[A-Za-z ]/.test(event.key)" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_initial" class="form-label">Middle Initial:</label>
                                    <input type="text" class="form-control" id="middle_initial" name="middle_initial" maxlength="1" value="<?php echo htmlspecialchars($middle_initial); ?>"pattern="[A-Za-z ]+" onkeypress="return /[A-Za-z ]/.test(event.key)" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Last Name:</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" pattern="[A-Za-z ]+" onkeypress="return /[A-Za-z ]/.test(event.key)" required>
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

            // Form validation and submission
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

                // Validate middle initial
                var middleInitial = document.getElementById('middle_initial').value;
                if (middleInitial && middleInitial.length > 1) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Middle initial should be a single character.',
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
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Toggle password visibility
            function togglePasswordVisibility(inputId, buttonId) {
                const input = document.getElementById(inputId);
                const button = document.getElementById(buttonId);
                
                button.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        button.innerHTML = '<i class="fa fa-eye-slash"></i>';
                    } else {
                        input.type = 'password';
                        button.innerHTML = '<i class="fa fa-eye"></i>';
                    }
                });
            }

            togglePasswordVisibility('password', 'togglePassword');
            togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
        });
    </script>
</body>
</html>