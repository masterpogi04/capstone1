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

// Pagination for notifications - default is 5 per page
$notifications_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $notifications_per_page;

// Fetch notifications for facilitator with pagination
$notifications_query = "SELECT *, 
                      CASE 
                          WHEN created_at > NOW() - INTERVAL 24 HOUR THEN DATE_FORMAT(created_at, '%l:%i %p')
                          WHEN created_at > NOW() - INTERVAL 7 DAY THEN CONCAT(DATEDIFF(NOW(), created_at), ' days ago')
                          ELSE DATE_FORMAT(created_at, '%M %d, %Y')
                      END as formatted_date
                      FROM notifications 
                      WHERE user_type = 'facilitator' 
                      AND user_id = ? 
                      ORDER BY created_at DESC
                      LIMIT ? OFFSET ?";
                      
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("iii", $facilitator_id, $notifications_per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);

// Count total notifications for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications 
               WHERE user_type = 'facilitator' 
               AND user_id = ?";
$stmt = $connection->prepare($count_query);
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Get count of unread notifications
$unread_stmt = $connection->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_type = 'facilitator' AND user_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $facilitator_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['unread_count'];

// Handle notification deletion via AJAX
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $delete_query = "DELETE FROM notifications WHERE id = ? AND user_type = 'facilitator' AND user_id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $facilitator_id);
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

        .facilitator-nav-item:hover {
            background-color: #15573A;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            color: #ffffff;
            text-decoration: none;
        }


        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
            width: 100%;
            height: auto;
            position: relative;
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
            }

            .welcome-text h1 {
                font-size: 2rem;
            }

            .facilitator-nav-grid {
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
            position: relative;
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

        .notification-content {
            flex: 1;
            padding-right: 25px;
        }

        .notification-message {
            color: #333;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }

        .notification-item a:hover {
            text-decoration: none;
            color: #1b651b;
        }

        .notification-time {
            font-size: 0.8em;
            color: #666;
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
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 10px;
            height: 10px;
            background-color: red;
            border-radius: 50%;
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 10px;
        }
        
        .no-notifications {
            text-align: center;
            padding: 15px;
            color: #666;
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
        <i class="fas fa-bell notification-icon" onclick="toggleNotifications()">
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge"></span>
            <?php endif; ?>
        </i>
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
        <div class="btn-icon">
            <i class="fas fa-file-alt me-2"></i>
        </div>
        <div class="btn-content">
            <h3 class="btn-title">Submit an Incident Report</h3>
            <p class="btn-subtitle">File a detailed report on incidents for documentation.</p>
        </div>
    </a>
    <a href="incident_reports-facilitator.php" class="facilitator-nav-item">
        <div class="btn-icon">
            <i class="fas fa-folder-open me-2"></i>
        </div>
        <div class="btn-content">
            <h3 class="btn-title">View My Submitted Reports</h3>
            <p class="btn-subtitle">Check the status of your previously submitted reports.</p>
        </div>
    </a>
    <a href="view_profiles.php" class="facilitator-nav-item">
        <div class="btn-icon">
            <i class="fas fa-user-graduate me-2"></i>
        </div>
        <div class="btn-content">
            <h3 class="btn-title">View Student Profile</h3>
            <p class="btn-subtitle">Access detailed profiles and records of students.</p>
        </div>
    </a>
    <a href="guidanceservice.html" class="facilitator-nav-item">
        <div class="btn-icon">
            <i class="fas fa-hands-helping me-2"></i>
        </div>
        <div class="btn-content">
            <h3 class="btn-title">Guidance Services</h3>
            <p class="btn-subtitle">Explore the available services provided by the guidance office.</p>
        </div>
    </a>
    
</nav>

        <div class="notification-panel" id="notificationPanel">
            <div class="notifications-header">
                <h5 style="margin: 0;">Notifications</h5>
            </div>
            <div id="notifications-container">
                <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <p>No notifications</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item" id="notification-<?php echo $notif['id']; ?>">
                            <div class="notification-content">
                                <a href="<?php echo htmlspecialchars($notif['link']); ?>" 
                                   class="notification-message">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </a>
                                <div class="notification-time">
                                    <?php echo htmlspecialchars($notif['formatted_date']); ?>
                                </div>
                            </div>
                            <button class="delete-notification" data-id="<?php echo $notif['id']; ?>">
                                <i class="fa fa-times"></i>
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
                    url: 'facilitator_homepage.php',
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
                                    $('#notifications-container').html(
                                        '<div class="no-notifications"><p>No notifications</p></div>'
                                    );
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
                url: 'get_more_facilitator_notifications.php',
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
                                    <div class="notification-content">
                                        <a href="${notif.link}" class="notification-message">
                                            ${notif.message}
                                        </a>
                                        <div class="notification-time">
                                            ${notif.formatted_date}
                                        </div>
                                    </div>
                                    <button class="delete-notification" data-id="${notif.id}">
                                        <i class="fa fa-times"></i>
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