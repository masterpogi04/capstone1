<?php
session_start();
include '../db.php';

// Define the external uploads path
define('UPLOADS_PATH', 'C:/xampp/htdocs/capstone1/uploads/adviser_profiles/');
define('UPLOADS_URL', '/capstone1/uploads/adviser_profiles/');

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

$adviser_id = $_SESSION['user_id'];

// Fetch current adviser details
$stmt = $connection->prepare("SELECT username, email, name, profile_picture FROM tbl_adviser WHERE id = ?");
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $adviser = $result->fetch_assoc();
    $username = $adviser['username'];
    $email = $adviser['email'];
    $name = $adviser['name'];
    $profile_picture = $adviser['profile_picture'];
    $_SESSION['name'] = $name;
    $_SESSION['profile_picture'] = $profile_picture;
} else {
    die("Adviser not found.");
}
$stmt->close();

$update_successful = false;
$error_message = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = trim($_POST['name']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Update name
    if (!empty($new_name) && $new_name !== $name) {
        $update_stmt = $connection->prepare("UPDATE tbl_adviser SET name = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->bind_param("si", $new_name, $adviser_id);
        if ($update_stmt->execute()) {
            $name = $new_name;
            $_SESSION['name'] = $name;
            $update_successful = true;
        }
        $update_stmt->close();
    }
    
    // Update password
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $connection->prepare("UPDATE tbl_adviser SET password = ?, updated_at = NOW() WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $adviser_id);
            if ($update_stmt->execute()) {
                $update_successful = true;
            }
            $update_stmt->close();
        } else {
            $error_message[] = "Passwords do not match.";
        }
    }
    
// Handle file upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["profile_picture"]["name"];
        $filetype = $_FILES["profile_picture"]["type"];
        $filesize = $_FILES["profile_picture"]["size"];
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            $error_message[] = "Error: Please select a valid file format.";
        }
    
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $error_message[] = "Error: File size is larger than the allowed limit.";
        }
    
        // Verify MIME type of the file
        if (in_array($filetype, $allowed)) {
            // Generate a unique filename
            $new_filename = uniqid() . "." . $ext;
            $upload_path = UPLOADS_PATH . $new_filename;
            
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $upload_path)) {
                $db_path = 'adviser_profiles/' . $new_filename; // Store relative path in database
                $update_stmt = $connection->prepare("UPDATE tbl_adviser SET profile_picture = ?, updated_at = NOW() WHERE id = ?");
                $update_stmt->bind_param("si", $db_path, $adviser_id);
                if ($update_stmt->execute()) {
                    // Delete old profile picture if it exists
                    if (!empty($profile_picture) && file_exists(UPLOADS_PATH . basename($profile_picture))) {
                        unlink(UPLOADS_PATH . basename($profile_picture));
                    }
                    $profile_picture = $db_path;
                    $_SESSION['profile_picture'] = $db_path;
                    $update_successful = true;
                }
                $update_stmt->close();
            } else {
                $error_message[] = "Error uploading file. Please try again.";
            }
        } else {
            $error_message[] = "Error: Invalid file type. Please try again.";
        }
    }
}

if ($update_successful) {
    // Refresh adviser details
    $stmt = $connection->prepare("SELECT username, email, name, profile_picture FROM tbl_adviser WHERE id = ?");
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $adviser = $result->fetch_assoc();
        $name = $adviser['name'];
        $profile_picture = $adviser['profile_picture'];
        $_SESSION['name'] = $name;
        $_SESSION['profile_picture'] = $profile_picture;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
< lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Adviser Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    
</head>
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
    text-align: left;
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
.row {
    display: flex;
    flex-wrap: wrap;
    margin: -10px;
}

.col-md-6 {
    flex: 0 0 50%;
    padding: 11px;
    
}

.form-group {
    margin-bottom: 1.5rem;
    
}

.mb-3 {
    margin-bottom: 1rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    font-weight: 500;
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

/* File Input */
.form-control-file {
    padding: 0.5rem 0;
    width: 100%;
}

/* Profile Picture */
.profile-picture {
    max-width: 200px;
    border-radius: 8px;
    margin-top: 1rem;
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



/* Responsive Breakpoints */
@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
    
    .form-container {
        margin: 0 1rem;
    }
}

@media (max-width: 768px) {
    .header h1 {
        font-size: 1.25rem;
    }
    
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
    
    .header {
        padding: 0.75rem;
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
<body>
    <div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
        <h1>CAVITE STATE UNIVERSITY-MAIN</h1>
    </div>
    <?php include 'adviser_sidebar.php'; ?>
    
    <div class="main-content">
    <div class-="form-container">
        <h2>Update Profile</h2>
    </div>
            <div class="card">
            <div class= "card-body">
            <form id="updateProfileForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username">Username:</label>
                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                    <label for="email">Email:</label>
                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                    </div>
                     </div>
                            
                        <div class="mb-3">
                    <label for="name">Name:</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
                    </div>
                         
            <div class="row mb-3">
                <div class="col-md-6">
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
                <div class="col-md-6">
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
            </div>
            
          
                            <button type="submit" class="btn btn-primary" id="updateButton">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
            <footer class="footer">
                <p>Contact number | Email | Copyright</p>
                        </footer>


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