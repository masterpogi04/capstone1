<?php
session_start();
include "../db.php"; 

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

// Get the adviser's ID from the session
$adviser_id = $_SESSION['user_id'];

// Pagination for notifications - default is 5 per page
$notifications_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $notifications_per_page;

// Fetch notifications for adviser with pagination
$notifications_query = "SELECT * FROM notifications 
                      WHERE user_type = 'adviser' 
                      AND user_id = ? 
                      AND is_read = 0 
                      ORDER BY created_at DESC
                      LIMIT ? OFFSET ?";
                      
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("iii", $adviser_id, $notifications_per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);

// Count total notifications for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications 
               WHERE user_type = 'adviser' 
               AND user_id = ? 
               AND is_read = 0";
$stmt = $connection->prepare($count_query);
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Fetch the adviser's details from the database
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name, profile_picture FROM tbl_adviser WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $adviser = $result->fetch_assoc();
    $name = $adviser['first_name'];
    if (!empty($adviser['middle_initial'])) {
        $name .= ' ' . $adviser['middle_initial'] . '.';
    }
    $name .= ' ' . $adviser['last_name'];
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

// Function to add sample notifications (for simulation purposes)
function addSampleNotifications($connection, $adviser_id) {
    $sample_notifications = [
        ["New incident report submitted by a student in your section", "view_student_incident_reports.php"],
        ["Reminder: Complete section assignments", "view_section.php"],
        ["New guidance office announcement", "#"],
    ];

    $query = "INSERT INTO notifications (user_type, user_id, message, link, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $connection->prepare($query);

    foreach ($sample_notifications as $notification) {
        $user_type = 'adviser';
        $stmt->bind_param("siss", $user_type, $adviser_id, $notification[0], $notification[1]);
        $stmt->execute();
    }
}

// If no notifications, add sample ones (for simulation)
if (empty($notifications) && $page === 1) {
    addSampleNotifications($connection, $adviser_id);
    
    // Fetch notifications again after adding samples
    $stmt = $connection->prepare($notifications_query);
    $stmt->bind_param("iii", $adviser_id, $notifications_per_page, $offset);
    $stmt->execute();
    $notifications_result = $stmt->get_result();
    $notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
    
    // Update total count
    $stmt = $connection->prepare($count_query);
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_row = $count_result->fetch_assoc();
    $total_notifications = $total_row['total'];
    $total_pages = ceil($total_notifications / $notifications_per_page);
}

// Handle notification deletion via AJAX
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $delete_query = "DELETE FROM notifications WHERE id = ? AND user_type = 'adviser' AND user_id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $adviser_id);
    $result = $stmt->execute();
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $connection->error]);
    }
    exit();
}

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    

    <style>

         .first-row{
            overflow-y: hidden;
         }
        .main-content {
        margin-left: 250px;
        padding: 80px 20px 70px;
         flex: 1;
        transition: margin-left 0.3s ease;
        overflow-y: hidden;
    }

    .header{
        overflow-y: hidden;
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
            padding: 30px;
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

.first-row, .second-row {
    display: grid;
    gap: 20px;
    margin-bottom: 10px;
}

.first-row {
    grid-template-columns: repeat(3, 1fr);
}

.second-row {
    grid-template-columns: repeat(3, 1fr);
}

.custom-btn {
    background-color: #1A6E47;
    border-radius: 12px;
    color: #ffffff;
    display: flex;
    align-items: center;
    padding: 20px;
    text-decoration: none;
    transition: all 0.3s ease;
    height: auto;
    min-height: 100px;
}

.custom-btn:hover {
    background-color: #15573A;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: white;
    text-decoration: none;
}

.icon-container {
    background-color: rgba(255,255,255,0.1);
    border-radius: 50%;
    padding: 15px;
    margin-right: 15px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-container i {
    font-size: 1.8rem;
    color: white;
}

.btn-text {
    flex: 1;
}

.btn-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.btn-description {
    font-size: 0.9rem;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .first-row, .second-row {
        grid-template-columns: 1fr;
    }
    
    .custom-btn {
        min-height: 100px;

    }
}
.notification-icon {
    font-size: 24px;
    color: #1b651b;
    cursor: pointer;
    margin-left: 20px;
    transition: color 0.3s ease;
    position: relative;
}

.notification-icon:hover {
    color:rgb(101, 223, 101);
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
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

.notification-item a:hover {
    text-decoration: none;
    color: #1b651b;
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

.see-more-btn {
    width: 100%;
    text-align: center;
    padding: 8px;
    background-color: #f0f0f0;
    border: none;
    border-radius: 4px;
    margin-top: 10px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.see-more-btn:hover {
    background-color: #e0e0e0;
}

.no-notifications {
    text-align: center;
    padding: 15px;
    color: #666;
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
    <?php include 'adviser_sidebar.php'; ?>
    <main class="main-content">
        <div class="header1">
            <br><br>
            <div class="welcome-text">
                <h1>Greetings <?php echo htmlspecialchars($name); ?></h1>
            </div>

            <div class="button-container">
    <div class="first-row">
        <a href="add_section.php" class="custom-btn">
            <div class="icon-container">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="btn-text">
                <div class="btn-title">Add Section</div>
                <div class="btn-description">Create a new section for your class</div>
            </div>
        </a>
        <a href="view_section.php" class="custom-btn">
            <div class="icon-container">
                <i class="fas fa-eye"></i>
            </div>
            <div class="btn-text">
                <div class="btn-title">View Sections</div>
                <div class="btn-description">Check your assigned sections</div>
            </div>
        </a>
        <a href="adviser_incident_report.php" class="custom-btn">
            <div class="icon-container">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="btn-text">
                <div class="btn-title">Submit Incident Report</div>
                <div class="btn-description">File a detailed report on incidents</div>
            </div>
        </a>
    </div>
    
    <div class="second-row">
        <a href="view_submitted_incident_reports-adviser.php" class="custom-btn">
            <div class="icon-container">
                <i class="fas fa-list-ul"></i>
            </div>
            <div class="btn-text">
                <div class="btn-title">View My Submitted Reports</div>
                <div class="btn-description">Check your submitted incident reports</div>
            </div>
        </a>
        <a href="view_student_incident_reports.php" class="custom-btn">
            <div class="icon-container">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="btn-text">
                <div class="btn-title">View Advisee Reports</div>
                <div class="btn-description">Check incident reports of your students</div>
            </div>
        </a>
        <a href="view_referrals.php" class="custom-btn">
            <div class="icon-container">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="btn-text">
                <div class="btn-title">View Advisee Referrals</div>
                <div class="btn-description">Check referrals of your students</div>
            </div>
        </a>
    </div>
</div>
    <div class="notification-panel" id="notificationPanel">
        <h5>Notifications</h5>
        <div id="notifications-container">
            <?php if (empty($notifications)): ?>
                <p class="no-notifications">No new notifications</p>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item" id="notification-<?php echo $notif['id']; ?>">
                        <a href="<?php echo htmlspecialchars($notif['link']); ?>"><?php echo htmlspecialchars($notif['message']); ?></a>
                        <small><?php echo htmlspecialchars($notif['created_at']); ?></small>
                        <button class="delete-notification" data-id="<?php echo $notif['id']; ?>">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($page < $total_pages): ?>
                    <button id="see-more-btn" class="see-more-btn" data-page="<?php echo $page + 1; ?>">
                        See More
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    </main>

    <footer class="footer">
            <p>&copy; 2024 All Rights Reserved</p>
        </footer>
    
    <script>
    $(document).ready(function() {
        // Toggle notifications panel
        $('.notification-icon').click(function(e) {
            e.stopPropagation();
            $('#notificationPanel').toggle();
        });
        
        // Handle delete notification
        $(document).on('click', '.delete-notification', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const notificationId = $(this).data('id');
            const notificationItem = $(this).closest('.notification-item');
            
            if (confirm('Are you sure you want to delete this notification?')) {
                $.ajax({
                    url: 'adviser_homepage.php',
                    type: 'POST',
                    data: {
                        delete_notification: true,
                        notification_id: notificationId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            notificationItem.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Check if there are no more notifications
                                if ($('.notification-item').length === 0) {
                                    $('#notifications-container').html('<p class="no-notifications">No new notifications</p>');
                                    $('.notification-badge').remove();
                                }
                            });
                        } else {
                            alert('Error deleting notification. Please try again.');
                        }
                    },
                    error: function() {
                        alert('Error deleting notification. Please try again.');
                    }
                });
            }
        });
        
        // Handle "See More" button click
        $(document).on('click', '#see-more-btn', function() {
            const nextPage = $(this).data('page');
            
            $.ajax({
                url: 'get_more_adviser_notifications.php',
                type: 'GET',
                data: { page: nextPage },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove the current "See More" button
                        $('#see-more-btn').remove();
                        
                        // Append new notifications
                        $.each(response.notifications, function(index, notif) {
                            const notificationHtml = `
                                <div class="notification-item" id="notification-${notif.id}">
                                    <a href="${notif.link}">${notif.message}</a>
                                    <small>${notif.created_at}</small>
                                    <button class="delete-notification" data-id="${notif.id}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                            $('#notifications-container').append(notificationHtml);
                        });
                        
                        // Add a new "See More" button if there are more pages
                        if (response.has_more) {
                            const seeMoreBtn = `
                                <button id="see-more-btn" class="see-more-btn" data-page="${nextPage + 1}">
                                    See More
                                </button>
                            `;
                            $('#notifications-container').append(seeMoreBtn);
                        }
                    }
                },
                error: function() {
                    alert('Error loading more notifications. Please try again.');
                }
            });
        });
        
        // Close notifications when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('#notificationPanel').length && 
                !$(e.target).closest('.notification-icon').length) {
                $('#notificationPanel').hide();
            }
        });
    });
    
    
    </script>
</body>
</html>