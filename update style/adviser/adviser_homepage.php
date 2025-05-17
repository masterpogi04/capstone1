<?php
session_start();
include "../db.php";
include "adviser_sidebar.php"; 

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

// Function to fetch notifications
function fetchNotifications($connection, $adviser_id) {
    $query = "SELECT * FROM notifications 
              WHERE user_type = 'adviser' 
              AND user_id = ? 
              AND is_read = 0 
              ORDER BY created_at DESC 
              LIMIT 5";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get the adviser's ID from the session
$adviser_id = $_SESSION['user_id'];

// Fetch the adviser's details from the database
$stmt = $connection->prepare("SELECT name, profile_picture FROM tbl_adviser WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $adviser = $result->fetch_assoc();
    $name = $adviser['name'];
    $profile_picture = $adviser['profile_picture'];
} else {
    die("Adviser not found.");
}

// Fetch analytics data with corrected query
$analytics_query = "
    SELECT 
        (SELECT COUNT(*) FROM sections WHERE adviser_id = ?) AS total_sections,
        COUNT(DISTINCT s.student_id) AS total_students,
        SUM(CASE WHEN s.gender = 'male' THEN 1 ELSE 0 END) AS male_students,
        SUM(CASE WHEN s.gender = 'female' THEN 1 ELSE 0 END) AS female_students
    FROM sections sec
    LEFT JOIN tbl_student s ON s.section_id = sec.id
    WHERE sec.adviser_id = ?
";

$stmt = $connection->prepare($analytics_query);
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("ii", $adviser_id, $adviser_id);
$stmt->execute();
$analytics_result = $stmt->get_result();
$analytics = $analytics_result->fetch_assoc();

$total_sections = $analytics['total_sections'];
$total_students = $analytics['total_students'];
$male_students = $analytics['male_students'];
$female_students = $analytics['female_students'];

// Fetch notifications
$notifications = fetchNotifications($connection, $adviser_id);

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adviser Dashboard</title>
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

.first-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 20px;
}

.second-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
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
        }

/* Responsive design */
@media (max-width: 768px) {
    .first-row, .second-row {
        grid-template-columns: 1fr;
    }
}

                
                .notification-icon {
                font-size: 24px;
                color: white;
                cursor: pointer;
                margin-left: 20px;
                transition: color 0.3s ease;
            }

        .notification-icon:hover {
            color: #f2f2f2;
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
        .notification-item:last-child {
            border-bottom: none;
        }
        .delete-notification {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
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
    <?php include 'adviser_sidebar.php'; ?>
    <main class="main-content">
        <div class="header1">
            <br><br>
            <div class="welcome-text">
                <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
            </div>

            <div class="button-container">
    <div class="first-row">
        <a href="add_section.php" class="custom-btn">
            <i class="fas fa-plus-circle" ></i> Add Section
        </a>
        <a href="view_section.php" class="custom-btn">
            <i class="fas fa-eye" ></i> View Sections
        </a>
        <a href="adviser_incident_report.php" class="custom-btn">
            <i class="bi bi-file-earmark-text" ></i> Submit an Incident Report
        </a>
    </div>
    
    <div class="second-row">
        <a href="view_submitted_incident_reports-adviser.php" class="custom-btn">
            <i class="fas fa-file-alt" ></i> View my Incident Report
        </a>
        <a href="view_student_incident_reports.php" class="custom-btn">
            <i class="fas fa-file-alt" ></i> View my advise Incident Reports
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
    
    <script>
    function toggleNotifications() {
        var panel = document.getElementById('notificationPanel');
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    function deleteNotification(notificationId) {
        if (confirm('Are you sure you want to delete this notification?')) {
            $.ajax({
                url: 'adviser_dashboard.php',
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
    </script>
</body>
</html>