<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$facilitator_id = $_SESSION['user_id'];

// Updated SQL query to use correct column names
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name, profile_picture FROM tbl_facilitator WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $facilitator = $result->fetch_assoc();
    // Construct full name from components
    $name = trim($facilitator['first_name'] . ' ' . 
            ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
            $facilitator['last_name']);
    $profile_picture = $facilitator['profile_picture'];
} else {
    die("Facilitator not found.");
}

// Handle notification deletion
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $delete_stmt = $connection->prepare("DELETE FROM notifications WHERE id = ? AND user_type = 'facilitator' AND user_id = ?");
    $delete_stmt->bind_param("ii", $notification_id, $facilitator_id);
    $delete_stmt->execute();
    exit(); // End processing after AJAX request
}

// Fetch all notifications with formatted date
$notifications_query = "SELECT *, 
    CASE 
        WHEN created_at > NOW() - INTERVAL 24 HOUR THEN DATE_FORMAT(created_at, '%l:%i %p')
        WHEN created_at > NOW() - INTERVAL 7 DAY THEN CONCAT(DATEDIFF(NOW(), created_at), ' days ago')
        ELSE DATE_FORMAT(created_at, '%M %d, %Y')
    END as formatted_date 
    FROM notifications 
    WHERE user_type = 'facilitator' AND user_id = ? 
    ORDER BY created_at DESC";
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Get count of unread notifications
$unread_stmt = $connection->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_type = 'facilitator' AND user_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $facilitator_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];

$connection->close();
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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

        /* Admin-style navigation grid */
        .facilitator-nav-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px auto;
            max-width: 800px;
            padding: 20px;
        }

        .facilitator-nav-item {
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

        .facilitator-nav-item:hover {
            background-color: #15573A;
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
            color: #ffffff;
            text-decoration: none;
        }

        .notification-icon {
            font-size: 24px;
            color: white;
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

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            .facilitator-nav-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header">
        <h1>CAVITE STATE UNIVERSITY-MAIN<h1>
    </div>
        <div class="notification-icon" onclick="toggleNotifications()">
            <i class="fa fa-bell"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'facilitator_sidebar.php'; ?>
    
    <main class="main-content">
        <div class="header1">
            <br><br>
        <div class="welcome-text">
            <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
        </div>

        <nav class="facilitator-nav-grid">
            <a href="facilitator_incident_report.php" class="facilitator-nav-item">
                <i class="fas fa-file-alt me-2"></i>Submit an Incident Report
            </a>
            <a href="incident_reports-facilitator.php" class="facilitator-nav-item">
                <i class="fas fa-folder-open me-2"></i>View my Submitted Reports
            </a>
            <a href="view_profiles.php" class="facilitator-nav-item">
                <i class="fas fa-user-graduate me-2"></i>View Student Profile
            </a>
            <a href="guidanceservice.html" class="facilitator-nav-item">
                <i class="fas fa-hands-helping me-2"></i>Guidance Services
            </a>
        </nav>

        <!-- Notification Panel -->
        <div class="notification-panel" id="notificationPanel">
            <!-- [Previous notification panel content remains the same] -->
        </div>
    </main>

    <footer class="footer">
        <p>&copy; 2024 All Rights Reserved</p>
    </footer>

    <script>
        function toggleNotifications() {
            var panel = document.getElementById('notificationPanel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        function deleteNotification(notificationId) {
            if (confirm('Are you sure you want to delete this notification?')) {
                $.ajax({
                    url: 'facilitator_homepage.php',
                    type: 'POST',
                    data: {
                        delete_notification: true,
                        notification_id: notificationId
                    },
                    success: function(response) {
                        $('#notification-' + notificationId).remove();
                        if ($('.notification-item').length === 0) {
                            $('#notificationPanel').html(
                                '<div class="notifications-header">' +
                                '<h5 style="margin: 0;">Notifications</h5></div>' +
                                '<div class="no-notifications"><p>No notifications</p></div>'
                            );
                        }
                    },
                    error: function() {
                        alert('Error deleting notification. Please try again.');
                    }
                });
            }
        }

        // Close notifications when clicking outside
        document.addEventListener('click', function(event) {
            var panel = document.getElementById('notificationPanel');
            var icon = document.querySelector('.notification-icon');
            if (!icon.contains(event.target) && !panel.contains(event.target)) {
                panel.style.display = 'none';
            }
        });
    </script>
</body>
</html>