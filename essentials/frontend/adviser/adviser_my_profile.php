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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
</head>
<style>
    :root {
        --primary-color: #2563eb;
    --primary-hover: #1d4ed8;
    --secondary-color: #64748b;
    --success-color: #22c55e;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --background-color: #f8fafc;
    --card-background: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
/* Header Section Spacing */
.col-md-9 {
    margin-top: 1rem; /* Reduced overall top margin */
}

.flex-md-nowrap {
    padding-top: 0.75rem !important; /* Reduced from pt-3 (1rem) */
    padding-bottom: 0.5rem !important; /* Reduced from pb-1 */
    margin-bottom: 0.5rem !important; /* Reduced from mb-2 */
    border-bottom: 1px solid #dee2e6;
}

.h2 {
    font-size: 1.75rem; /* Slightly reduced heading size if needed */
    margin-bottom: 0; /* Remove bottom margin from heading */
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-9 {
        margin-top: 0.75rem;
    }
    
    .flex-md-nowrap {
        padding-top: 0.5rem !important;
    }
}
/* Responsive adjustments */
@media (max-width: 768px) {
    .main-container {
        margin-top: 1.5rem;  /* Reduced spacing on mobile */
        padding: 1rem;
    }
}
/* Card Styles */
.card {
    background: var(--card-background);
    border-radius: 1rem;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-lg);
   
}

.card-body {
    padding: 2.5rem;
}

/* Container Styles */
.profile-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Profile Picture Section */
.profile-picture-section {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #eee;
}

.profile-image {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 1.5rem;
    border: 3px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.profile-picture-buttons {
    display: flex;
    gap: 10px;
}

.change-photo-btn {
    background-color: #0066ff;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.change-photo-btn:hover {
    background-color: #0052cc;
}

.remove-photo-btn {
    background-color: #ff4d4d;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.remove-photo-btn:hover {
    background-color: #ff3333;
}

/* Form Styles */
.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    flex: 1;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

.form-input::placeholder {
    color: #999;
}

/* Required Field Indicator */
.required::after {
    content: '*';
    color: #ff4d4d;
    margin-left: 4px;
}
/* Form Controls */
.form-control {
    border: 2px solid var(--border-color);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.form-control:disabled,
.form-control[readonly] {
    background-color: light gray;
    border-color: #e2e8f0;
    cursor: not-allowed;
}

/* Button Section */
.button-section {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.save-btn {
    background-color: #0066ff;
    color: white;
    padding: 10px 24px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.save-btn:hover {
    background-color: #0052cc;
}

.cancel-btn {
    background-color: white;
    color: #666;
    padding: 10px 24px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cancel-btn:hover {
    background-color: #f5f5f5;
    border-color: #ccc;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .profile-container {
        padding: 1rem;
    }
    
    .profile-picture-section {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-image {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}

/* Error States */
.form-input.error {
    border-color: #ff4d4d;
}

.error-message {
    color: #ff4d4d;
    font-size: 12px;
    margin-top: 4px;
}

/* Loading States */
.form-input.loading {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

.button-loading {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Success States */
.form-input.success {
    border-color: #00cc66;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-container {
    animation: fadeIn 0.3s ease-out;
}

/* File Upload Button */
.file-upload-btn {
    display: none;
}

.file-upload-label {
    display: inline-block;
    background-color: #0066ff;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.file-upload-label:hover {
    background-color: #0052cc;
}

/* Tooltips */
.tooltip {
    position: relative;
    display: inline-block;
}

.tooltip .tooltip-text {
    visibility: hidden;
    width: 200px;
    background-color: #333;
    color: white;
    text-align: center;
    padding: 5px;
    border-radius: 4px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.2s;
    font-size: 12px;
}

.tooltip:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}
@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .form-row {
        flex-direction: column;
    }

    .col-md-6 {
        width: 100%;
        margin-bottom: 1rem;
    }

    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
/* Container Layout */
.main-container {
    min-height: 100vh;
    padding: 1.5rem;
    margin-top: 2rem;  /* Reduced from 4.5rem to 2rem */
    display: flex;
    flex-direction: column;
}

.content-wrapper {
    flex: 1;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    padding: 0 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .main-container {
        margin-top: 1.5rem;  /* Reduced spacing on mobile */
        padding: 1rem;
    }
}



</style>
<body>
    <div class="header">
        <h1>CAVITE STATE UNIVERSITY-MAIN</h1>
    </div>
    <?php include 'adviser_sidebar.php'; ?>
    
    <div class="main-content">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-1 mb-2 border-bottom">
        <h1 class="h1">Update Profile</h1>
    </div>

            <div class="card">
            <div class="card-body">
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
            
            <div class="form-group">
                            <label for="profile_picture">Profile Picture:</label>
                            <input type="file" class="form-control-file" id="profile_picture" name="profile_picture">
                        </div>
                        <?php if (!empty($profile_picture)): ?>
                            <img src="<?php echo htmlspecialchars(UPLOADS_URL . basename($profile_picture)); ?>" alt="Current Profile Picture" class="mb-3" style="max-width: 200px;">
                        <?php endif; ?>
                        <div>
                            <button type="submit" class="btn btn-primary" id="updateButton">Update Profile</button>
                        </div>
                    </form>
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