<?php
session_start();
include '../db.php'; // Ensure database connection is established
include "dean_sidebar.php";

// Ensure the user is logged in and is an dean
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: ../login.php");
    exit();
}

// Get the dean's ID from the session
$dean_id = $_SESSION['user_id'];

// Fetch the dean's details from the database
$stmt = $connection->prepare("SELECT name, profile_picture FROM tbl_dean WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error); // Output the error message
}
$stmt->bind_param("i", $dean_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $dean = $result->fetch_assoc();
    $name = $dean['name'];
    $profile_picture = $dean['profile_picture'];
} else {
    die("dean not found.");
}

mysqli_close($connection);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEIT - Guidance Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
            color: white;
            padding: 50px;
            margin: -75px -20px 20px;
            width: calc(100% + 40px);
        }

        .welcome-text::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            background-color: rgba(0, 128, 0, 0.7);
            z-index: 0;
        }

        .welcome-text h1 {
            position: relative;
            z-index: 1;
            font-size: 48px;
            font-weight: 700;
            margin: 0;
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
    <div class="header1">
        <br><br>
        <div class="welcome-text">
                <h1>WELCOME, <?php echo htmlspecialchars($name); ?>!!</h1>
                </div>
                
                <div class="button-container">
            <div class="button-row">
                <a href="dean_view_incident_reports_from-Guards.php" class="custom-btn">
                    <i class="fas fa-file-alt"></i> View Incident Reports
                </a>
                <a href="guard_reports_history.php" class="custom-btn">
                    <i class="fas fa-history"></i> View History of Incident Reports
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
                        <a href="<?php echo htmlspecialchars($notif['link']); ?>"><?php echo htmlspecialchars($notif['message']); ?></a>
                        <small><?php echo htmlspecialchars($notif['created_at']); ?></small>
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
