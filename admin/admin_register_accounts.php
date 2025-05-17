<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$form_data = []; // Array to store form data for repopulation
$error_fields = []; // Array to track which fields have errors

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store all form data in session for repopulation
    $form_data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'first_name' => $_POST['first_name'],
        'middle_initial' => $_POST['middle_initial'],
        'last_name' => $_POST['last_name'],
        'user_type' => $_POST['user_type']
    ];
    
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $last_name = $_POST['last_name'];
    $user_type = $_POST['user_type'];
    
    $table_name = "tbl_" . strtolower($user_type);
    
    // Check for email existence across all tables
    $all_tables = ['tbl_admin', 'tbl_adviser', 'tbl_counselor', 'tbl_dean', 'tbl_facilitator', 'tbl_guard', 'tbl_instructor'];
    $email_exists = false;
    
    foreach ($all_tables as $table) {
        $check_email_stmt = $connection->prepare("SELECT email FROM $table WHERE email = ?");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $check_email_result = $check_email_stmt->get_result();
        
        if ($check_email_result->num_rows > 0) {
            $email_exists = true;
            $error_fields['email'] = true;
            break;
        }
        $check_email_stmt->close();
    }
    
    // Check if the username exists in the specific table
    $check_username_stmt = $connection->prepare("SELECT username FROM $table_name WHERE username = ?");
    $check_username_stmt->bind_param("s", $username);
    $check_username_stmt->execute();
    $check_username_result = $check_username_stmt->get_result();
    
    if ($check_username_result->num_rows > 0) {
        $error_fields['username'] = true;
    }
    
    if ($email_exists && isset($error_fields['username'])) {
        $message = "Error: Both email and username already exist.";
        // Clear both fields from form data
        $form_data['email'] = '';
        $form_data['username'] = '';
    } elseif ($email_exists) {
        $message = "Error: Email already exists in the system.";
        // Clear only email from form data
        $form_data['email'] = '';
    } elseif (isset($error_fields['username'])) {
        $message = "Error: Username already exists.";
        // Clear only username from form data
        $form_data['username'] = '';
    } else {
        $stmt = $connection->prepare("INSERT INTO $table_name (username, password, email, first_name, middle_initial, last_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssssss", $username, $password, $email, $first_name, $middle_initial, $last_name);
        
        if ($stmt->execute()) {
            $message = "New $user_type account created successfully";
            
            $_SESSION['new_account'] = [
                'user_type' => $user_type,
                'username' => $username,
                'email' => $email,
                'password' => $_POST['password'],
                'first_name' => $first_name,
                'middle_initial' => $middle_initial,
                'last_name' => $last_name
            ];
            
            header("Location: admin_send_credentials_email.php");
            exit();
        } else {
            $message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    $check_username_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

    <style> 
        :root {
            --primary-color: #003366;
            --secondary-color: #4a90e2;
            --background-color: #f9f9f9;
            --text-color: #333;
            --error-color: #dc3545;
            --success-color: #28a745;
            --border-radius: 8px;
            --box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body{
            background: #c4f1cf;
        }

        .form-container {
            width: 100%;
            max-width: 600px; 
            margin: 1rem auto;
            background-color: #ffffff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            transition: var(--transition);
        }

        .form-container h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem; 
            text-align: center;
            font-size: 1.8rem; 
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .form-container h2::after {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: var(--secondary-color);
        }

        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem; 
            font-weight: 600;
            color: var(--primary-color);
            transition: var(--transition);
            font-size: 0.9rem; 
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #ccc;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: var(--background-color);
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
            outline: none;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23003366' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
            padding: 0.9rem 2rem;
            border: none;
            border-radius: 25px; 
            cursor: pointer;
            font-size: 1.1rem; 
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1.5px; 
            position: relative;
            overflow: hidden;
            font-weight: 600; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            width: auto; 
            min-width: 200px;
            display: block;
            margin: 1.5rem auto 0;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #3a7bc8;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255,255,255,0.7);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn-primary:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% { transform: scale(0, 0); opacity: 1; }
            20% { transform: scale(25, 25); opacity: 1; }
            100% { opacity: 0; transform: scale(40, 40); }
        }

        /* Form Validation Styling */
        .form-control.is-invalid {
            border-color: var(--error-color);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23dc3545' viewBox='0 0 16 16'%3E%3Cpath d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }

        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 80%;
            color: var(--error-color);
        }

        .form-control.is-invalid ~ .invalid-feedback {
            display: block;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .form-container {
                max-width: 90%;
                padding: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .form-container {
                max-width: 95%;
                padding: 1.5rem;
                margin: 1rem auto;
            }

            .form-container h2 {
                font-size: 1.5rem;
                margin-bottom: 1.2rem;
            }

            .btn-primary {
                padding: 0.8rem 1.8rem;
                font-size: 1rem;
                min-width: 180px;
            }

            .dashboard-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .form-control,
            .btn-primary {
                font-size: 16px; 
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1rem;
            }

            .form-container h2 {
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }

            .btn-primary {
                font-size: 0.9rem;
                padding: 0.7rem 1.5rem;
                min-width: 160px;
            }
        }

        /* Back Button*/
        .modern-back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #2EDAA8;
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(46, 218, 168, 0.15);
            letter-spacing: 0.3px;
        }

        .modern-back-button:hover {
            background-color: #28C498;
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
            color: white;
            text-decoration: none;
        }

        .modern-back-button:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(46, 218, 168, 0.15);
        }

        .modern-back-button i {
            font-size: 0.9rem;
            position: relative;
            top: 1px;
        }
    </style>

<body>
<div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="form-container">
            <h2>Register New Account</h2>
            <form method="post" id="registrationForm">
                <div class="form-group">
                    <label for="user_type">User Type:</label>
                    <select class="form-control" id="user_type" name="user_type" required>
                        <option value="counselor" <?php echo isset($form_data['user_type']) && $form_data['user_type'] == 'counselor' ? 'selected' : ''; ?>>Counselor</option>
                        <option value="facilitator" <?php echo isset($form_data['user_type']) && $form_data['user_type'] == 'facilitator' ? 'selected' : ''; ?>>Facilitator</option>
                        <option value="instructor" <?php echo isset($form_data['user_type']) && $form_data['user_type'] == 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                        <option value="adviser" <?php echo isset($form_data['user_type']) && $form_data['user_type'] == 'adviser' ? 'selected' : ''; ?>>Adviser</option>
                        <option value="guard" <?php echo isset($form_data['user_type']) && $form_data['user_type'] == 'guard' ? 'selected' : ''; ?>>UCSS Guard</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($form_data['first_name']) ? htmlspecialchars($form_data['first_name']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="middle_initial">Middle Initial:</label>
                            <input type="text" class="form-control" id="middle_initial" name="middle_initial" maxlength="1" value="<?php echo isset($form_data['middle_initial']) ? htmlspecialchars($form_data['middle_initial']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($form_data['last_name']) ? htmlspecialchars($form_data['last_name']) : ''; ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" class="form-control <?php echo isset($error_fields['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo isset($form_data['username']) ? htmlspecialchars($form_data['username']) : ''; ?>" required>
                    <?php if(isset($error_fields['username'])): ?>
                        <div class="invalid-feedback">Username already exists</div>
                    <?php endif; ?>
                </div>
                 <div class="form-group-row" style="display: flex; justify-content: space-between;">
                    <div class="form-group" style="flex: 1; margin-right: 10px;">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control <?php echo isset($error_fields['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>" required>
                        <?php if(isset($error_fields['email'])): ?>
                            <div class="invalid-feedback">Email already exists</div>
                        <?php endif; ?>
                    </div>
                </div>
                <center><button type="submit" class="btn btn-primary">Register Account</button></center>
            </form>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Get form element
    const form = document.getElementById('registrationForm');

    // Add form submission event listener with confirmation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Confirm Registration',
            text: "Are you sure you want to register this account?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, register it!'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
    
    // Display message if available
    <?php if($message): ?>
    Swal.fire({
        title: 'Registration Status',
        text: "<?php echo $message; ?>",
        icon: '<?php echo strpos($message, "successfully") !== false ? "success" : "error"; ?>',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    });
    <?php endif; ?>
    
    // Middle initial validation
    const middleInitial = document.getElementById('middle_initial');
    if (middleInitial) {
        middleInitial.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
            if (this.value.length > 1) {
                this.value = this.value.charAt(0);
            }
        });
    }
});
    </script>
</body>
</html>