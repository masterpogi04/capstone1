<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: ../login.php");
    exit();
}

// Function to fetch notifications
function fetchNotifications($connection, $counselor_id) {
    $query = "SELECT * FROM notifications 
              WHERE user_type = 'counselor' 
              AND user_id = ? 
              AND is_read = 0 
              ORDER BY created_at DESC 
              LIMIT 5";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $counselor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

$counselor_id = $_SESSION['user_id'];

// Fetch the counselor's details from the database
$stmt = $connection->prepare("SELECT first_name, last_name, profile_picture FROM tbl_counselor WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $counselor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $counselor = $result->fetch_assoc();
    $name = $counselor['first_name'] . ' ' . $counselor['last_name'];
    $profile_picture = $counselor['profile_picture'];
} else  {
    die("counselor not found.");
}

// Fetch notifications
$notifications = fetchNotifications($connection, $counselor_id);
$unread_count = count($notifications);

// Handle notification deletion
if (isset($_POST['delete_notification'])) {
    $notification_id = $_POST['notification_id'];
    $delete_query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $delete_stmt = $connection->prepare($delete_query);
    $delete_stmt->bind_param("ii", $notification_id, $counselor_id);
    $delete_stmt->execute();
    exit();
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
            background-color:rgb(248, 250, 248);
            border: 1px solidrgb(1, 7, 14);
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
        <h1>CEIT - GUIDANCE OFFICE</h1>
        <div class="notification-icon" onclick="toggleNotifications()">
                <i class="fa fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"></span>
                <?php endif; ?>
            </div>
    </div>
    
    <?php include 'counselor_sidebar.php'; ?>
    
    <main class="main-content">
        <div class="header1">
            <br><br>
        <div class="welcome-text">
                    <h1>WELCOME, <?php echo htmlspecialchars($name); ?>!!</h1>
                </div>
                <nav class="facilitator-nav-grid">
                        <a href="view_referrals_page.php" class="facilitator-nav-item"> View Referrals</a>
                        <a href="view_referrals_done.php" class="facilitator-nav-item"> View Done Referrals</a>
                </nav>
               

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
    <script>
    function toggleNotifications() {
        var panel = document.getElementById('notificationPanel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    function deleteNotification(notificationId) {
        if (confirm('Are you sure you want to delete this notification?')) {
            $.ajax({
                url: 'counselor_homepage.php',
                type: 'POST',
                data: {
                    delete_notification: true,
                    notification_id: notificationId
                },
                success: function(response) {
                    $('#notification-' + notificationId).remove();
                    
                    // Update notification badge
                    if ($('.notification-item').length === 0) {
                        $('.notification-badge').remove();
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
    </script>
</body>
</html>