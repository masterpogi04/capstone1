<?php
session_start();
include '../db.php';



// Ensure the user is logged in and is a guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guard') {
    header("Location: ../login.php");
    exit();
}

// Get the guard's ID from the session
$guard_id = $_SESSION['user_id'];

// Replace the existing fetchNotifications function with this one
function fetchNotifications($connection, $guard_id) {
    $query = "SELECT * FROM notifications 
              WHERE user_type = 'guard' 
              AND user_id = ? 
              AND is_read = 0 
              ORDER BY created_at DESC";
              
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $guard_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle notification deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notification_id = $_POST['notification_id'];
    $delete_query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = 'guard' AND user_id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $guard_id);
    $stmt->execute();
    exit();
}

// Fetch the guard's details from the database
$stmt = $connection->prepare("SELECT first_name, last_name, profile_picture FROM tbl_guard WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $guard_id);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows === 1) {
    $guard = $result->fetch_assoc();
    $name = $guard['first_name'] . ' ' . $guard['last_name'];  // Concatenate first and last name
    $profile_picture = $guard['profile_picture'];
} else  {
    die("guard not found.");
}


$notifications = fetchNotifications($connection, $guard_id);

mysqli_close($connection);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT - Guidance Office</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
     
     .main-content {
            margin-left: 250px;
            padding: 80px 20px 70px;
            flex: 1;
            transition: margin-left 0.3s ease;
        }

    
        .welcome-text {
    position: relative;
    background-image: url('cvsu1.jpg');
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    padding: 60px 40px;
    margin: -75px -20px 20px;
    width: calc(100% + 40px);
    min-height: 200px;
}

.welcome-text::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 128, 0, 0.85); /* Darker green overlay */
    z-index: 0;
}

.welcome-text h1 {
    position: relative;
    z-index: 1;
    color: white;
    font-size: 64px;
    font-weight: 700;
    margin: 0;
    text-transform: uppercase;
    font-family: 'Arial', sans-serif;
    letter-spacing: 1px;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .welcome-text {
        padding: 40px 20px;
    }
    
    .welcome-text h1 {
        font-size: 48px;
    }
}

@media screen and (max-width: 480px) {
    .welcome-text {
        padding: 30px 15px;
    }
    
    .welcome-text h1 {
        font-size: 36px;
    }
}
        .container {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
    /* Responsive design */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
        }
        .main-content {
            margin-left: 0;
        }
    }
        .button-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .button-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .custom-btn {
            padding: 20px;
            font-size: 1rem;
            color: #ffffff;
            background-color: #1A6E47;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 80px;
        }

        .custom-btn i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .custom-btn:hover {
            background-color: #15573A;
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
            color: white;
            text-decoration: none;
        }

        .notification-icon {
            font-size: 24px;
            color: black;
            cursor: pointer;
            margin-left: 20px;
            transition: color 0.3s ease;
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

    

        @media screen and (max-width: 768px) {
            .header {
                left: 0;
                width: 100%;
                padding-left: 60px;
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .button-row {
                grid-template-columns: 1fr;
            }

            .footer {
                left: 0;
            }
        }
         .notification-icon {
        position: relative;
    }
    
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 10px;
        height: 10px;
        background-color: red;
        border-radius: 50%;
    }

    .notification-item {
        padding: 10px;
        border-bottom: 1px solid #dee2e6;
        position: relative;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-item a {
        color: #333;
        text-decoration: none;
        display: block;
        margin-bottom: 5px;
    }

    .notification-item small {
        color: #666;
        font-size: 0.8em;
    }

    .delete-notification {
        position: absolute;
        right: 5px;
        top: 5px;
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
    }

    .delete-notification:hover {
        color: #c82333;
    }
    </style>
</head>
<body>
<div class="header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1>CEIT - GUIDANCE OFFICE</h1>
        <i class="fas fa-bell notification-icon" onclick="toggleNotifications()">
                            <?php if (!empty($notifications)): ?>
                                <span class="notification-badge"></span>
                            <?php endif; ?>
                        </i>
                    </div>
                    <?php include 'guard_sidebar.php'; ?>
                <main class="main-content">
            <div class="header1">
              <br><br>
             <div class="welcome-text">
                    <h1>WELCOME, CEIT <?php echo htmlspecialchars($name); ?>!!</h1>
                </div>

                <div class="button-container">
                    <div class="button-row">
                        <a href="incident_report_guards.php" class="custom-btn">
                            <i class="fas fa-file-alt"></i> Submit Student Violation
                        </a>
                        <a href="view_submitted_incident_reports_guard.php" class="custom-btn">
                            <i class="fas fa-history"></i> View Submitted Student Violation
                        </a>
                    </div>
                </div>
                <div class="notification-panel" id="notificationPanel">
                    <h5>Notifications</h5>
                    <?php if (empty($notifications)): ?>
                        <p>No new notifications</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item" id="notification-<?php echo $notif['id']; ?>">
                                <a href="<?php echo htmlspecialchars($notif['link']); ?>">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </a>
                                <small><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                                <button class="delete-notification" onclick="deleteNotification(<?php echo $notif['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                </main>
                <footer class="footer">
                   <p>&copy; 2024 All Rights Reserved</p> 
    </footer>
           
    </div>
    </div>

    <script>

    function toggleNotifications() {
        var panel = document.getElementById('notificationPanel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    function deleteNotification(notificationId) {
        if (confirm('Are you sure you want to delete this notification?')) {
            $.ajax({
                url: 'dean_homepage.php',
                type: 'POST',
                data: {
                    delete_notification: true,
                    notification_id: notificationId
                },
                success: function(response) {
                    $('#notification-' + notificationId).remove();
                    if ($('.notification-item').length === 0) {
                        $('#notificationPanel').html('<h5>Notifications</h5><p>No new notifications</p>');
                    }
                },
                error: function() {
                    alert('Error deleting notification. Please try again.');
                }
            });
        }
    }

    // Close notifications when clicking outside
    window.onclick = function(event) {
        if (!event.target.matches('.notification-icon') && !event.target.closest('.notification-panel')) {
            var panel = document.getElementById('notificationPanel');
            if (panel.style.display === 'block') {
                panel.style.display = 'none';
            }
        }
    }
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 992) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>
