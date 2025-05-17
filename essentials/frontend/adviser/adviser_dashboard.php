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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #1A3636;
            --secondary-color: #FF885B;
            --background-color: #f0f2f5;
            --card-background:#EEEEEE ;
            --text-color: #1E201E;
            
        }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f0f2f5;
            }
       
            
          
          
    .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        .dashboard-container {
            background-color: var(--card-background);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .dashboard-container h2 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
        }

        .analytics-card {
            background-color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }

        .analytics-title {
            font-size: 1.1em;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .analytics-value {
            font-size: 2.5em;
            font-weight: 700;
            color: var(--secondary-color);
        }

        
    </style>
</head>
<body>
    <div class="header">
        <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'adviser_sidebar.php'; ?>
    <main class="main-content">
       
            <div class="dashboard-container">
                <h2>Adviser User Analytics</h2>
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="analytics-card">
                            <div class="analytics-title">Total Sections</div>
                            <div class="analytics-value"><?php echo $total_sections; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="analytics-card">
                            <div class="analytics-title">Total Students</div>
                            <div class="analytics-value"><?php echo $total_students; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="analytics-card">
                            <div class="analytics-title">Male Students</div>
                            <div class="analytics-value"><?php echo $male_students; ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="analytics-card">
                            <div class="analytics-title">Female Students</div>
                            <div class="analytics-value"><?php echo $female_students; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

    <div class="footer">
        <p>Contact number | Email | Copyright</p>
    </div>
    
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