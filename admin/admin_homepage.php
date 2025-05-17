<?php
session_start();
include '../db.php'; // Ensure database connection is established
// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
// Get the admin's ID from the session
$admin_id = $_SESSION['user_id'];
// In admin_homepage.php where you fetch the admin details
$stmt = $connection->prepare("SELECT first_name, last_name, profile_picture FROM tbl_admin WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $name = $admin['first_name'] . ' ' . $admin['last_name'];  // Simple concatenation
    $profile_picture = $admin['profile_picture'];
} else {
    die("Admin not found.");
}

// Fetch user counts for each table
$user_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_student', 'tbl_guard'];
$user_counts = [];

foreach ($user_tables as $table) {
    $count_query = "SELECT COUNT(*) as count FROM $table";
    $count_result = mysqli_query($connection, $count_query);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $user_counts[$table] = $count_row['count'];
    } else {
        $user_counts[$table] = 0;
    }
}

mysqli_close($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
     <style>
         
         .main-content {
        margin-left: 250px;
        padding: 80px 20px 70px;
         flex: 1;
        transition: margin-left 0.3s ease;
    }

    

      /* Welcome Banner Styles */
      .welcome-text {
            position: relative;
            background-image: linear-gradient(rgba(26, 110, 71, 0.8), rgba(26, 110, 71, 0.8)), url('cvsu1.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 50px;
            margin: -75px -20px 20px;
            width: calc(100% + 40px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .welcome-text h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        /* Admin-style navigation grid */
        .admin-nav-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px auto;
            max-width: 800px;
            padding: 20px;
        }

        .admin-nav-item {
            padding: 20px;
            background-color: #1A6E47;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            color: #ffffff;
            height: 100px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-icon {
            font-size: 24px;
            margin-right: 15px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .btn-content {
            flex-grow: 1;
            text-align: left;
        }

        .btn-content h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffffff;
        }

        .btn-content p {
            margin: 5px 0 0;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.3;
        }

        .admin-nav-item:hover {
            background-color: #15573A;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            color: #ffffff;
            text-decoration: none;
        }


        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .header {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .footer {
                left: 0;
            }

            .welcome-text {
                padding: 2rem;
                margin: -20px -10px 1.5rem;
                width: calc(100% + 30px);
            }

            .welcome-text h1 {
                font-size: 2rem;
            }

            .admin-nav-grid {
                grid-template-columns: 1fr;
                padding: 1rem 0.5rem;
            }
        }

        .notification-icon {
            font-size: 24px;
            color: #1b651b;
            cursor: pointer;
            margin-left: 20px;
            transition: color 0.3s ease;
        }

        .notification-icon:hover {
            color:rgb(101, 223, 101);
        }

        .notification-panel {
            position: fixed;
            top: 60px;
            right: 20px;
            width: 300px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-item a {
            text-decoration: none;
            color: inherit;
        }

        .notification-item a:hover {
            text-decoration: none;
            color: #1b651b;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .delete-notification {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            .first-row, .second-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            .admin-nav-grid {
                grid-template-columns: 1fr;
            }
        }
</style>
<body>
<div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
        <h1>CEIT - GUIDANCE OFFICE</h1>
       
    </div>
    <?php include 'admin_sidebar.php'; ?> <!-- Include sidebar with admin-specific links -->
    <main class="main-content">
        <div class="header1">
            <br><br>
            <div class="welcome-text">
            <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
        </div>

        <nav class="admin-nav-grid">
            <a href="admin_manage_accounts.php" class="admin-nav-item">
                <i class="fas fa-users-cog me-2"></i>Manage Users
            </a>
            <a href="admin_register_accounts.php" class="admin-nav-item">
                <i class="fas fa-user-plus me-2"></i>Register New Accounts
            </a>
            <a href="admin_add_departments.php" class="admin-nav-item">
                <i class="fas fa-building me-2"></i>Manage Departments
            </a>
            <a href="admin_dashboard.php" class="admin-nav-item">
                <i class="fas fa-chart-bar me-2"></i>Dashboard
            </a>
        </nav>
        </div>
    </main>

    <footer class="footer">
            <p>&copy; 2024 All Rights Reserved</p>
        </footer>
    
</body>
</html>