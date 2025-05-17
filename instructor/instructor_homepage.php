<?php
session_start();
include '../db.php';  

// Ensure the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get the instructor's ID from the session
$instructor_id = $_SESSION['user_id'];

// Fetch the instructor's details from the database
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_instructor WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error); // Output the error message
}
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $instructor = $result->fetch_assoc();
    $name = $instructor['first_name'];
    if (!empty($instructor['middle_initial'])) {
        $name .= ' ' . $instructor['middle_initial'] . '.';
    }
    $name .= ' ' . $instructor['last_name'];
} else {
    die("Instructor not found.");
}

// Pagination for notifications - default is 5 per page
$notifications_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $notifications_per_page;

// Fetch notifications for instructor with pagination
$notifications_query = "SELECT * FROM notifications 
                        WHERE user_type = 'instructor' 
                        AND user_id = ? 
                        AND is_read = 0 
                        ORDER BY created_at DESC
                        LIMIT ? OFFSET ?";
                        
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("iii", $instructor_id, $notifications_per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = [];

// Count total notifications for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications 
                WHERE user_type = 'instructor' 
                AND user_id = ? 
                AND is_read = 0";
$stmt = $connection->prepare($count_query);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Load notifications
$unread_count = 0;
if ($notifications_result) {
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = $row;
        $unread_count++;
    }
}

// Function to add sample notifications if none exist (for testing)
function addSampleNotifications($connection, $instructor_id) {
    $sample_notifications = [
        ["New student violation report submitted", "view_incident_reports.php"],
        ["Reminder: Submit pending incident reports", "instructor_incident_report.php"],
        ["New guidance office announcement", "#"],
    ];

    $query = "INSERT INTO notifications (user_type, user_id, message, link, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $connection->prepare($query);

    foreach ($sample_notifications as $notification) {
        $user_type = 'instructor';
        $stmt->bind_param("siss", $user_type, $instructor_id, $notification[0], $notification[1]);
        $stmt->execute();
    }
}

// If no notifications, add sample ones (for simulation)
if (empty($notifications) && $page === 1) {
    addSampleNotifications($connection, $instructor_id);
    
    // Fetch notifications again after adding samples
    $stmt = $connection->prepare($notifications_query);
    $stmt->bind_param("iii", $instructor_id, $notifications_per_page, $offset);
    $stmt->execute();
    $notifications_result = $stmt->get_result();
    $notifications = [];
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = $row;
        $unread_count++;
    }
    
    // Update total count
    $stmt = $connection->prepare($count_query);
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_row = $count_result->fetch_assoc();
    $total_notifications = $total_row['total'];
    $total_pages = ceil($total_notifications / $notifications_per_page);
}

// Handle notification deletion via AJAX
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $delete_query = "DELETE FROM notifications WHERE id = ? AND user_type = 'instructor' AND user_id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $instructor_id);
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
    <title>CEIT - Guidance Office</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #1A6E47;
            --secondary-color: #15573A;
            --accent-color: #28a745;
            --text-color: #333;
            --background-light: #f8f9fa;
            --transition-speed: 0.3s;
        }
       

        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 90px 20px 70px;
            flex: 1;
            transition: var(--transition-speed) ease;
            background-color: var(--background-light);
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

       
        /* Navigation Grid */
        .admin-nav-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px auto;
            max-width: 800px;
            padding: 20px;
        }

        .admin-nav-grid a:nth-child(3) {
            grid-column: 1 / -1; 
            width: 50%; 
            margin: 0 auto;
            justify-self: center; 
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

        .nav-icon {
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

        .nav-content {
            flex-grow: 1;
            text-align: left;
        }

        .nav-content h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #ffffff;
        }

        .nav-content p {
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
            }

            .welcome-text h1 {
                font-size: 2rem;
            }

            .admin-nav-grid {
                grid-template-columns: 1fr;
                padding: 1rem 0.5rem;
            }
        }

        /* Menu Toggle Button */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
            padding: 0.5rem;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Notification Styles */
        .notification-icon {
            position: relative;
            font-size: 1.25rem;
            color: var(--primary-color);
            cursor: pointer;
            transition: var(--transition-speed);
        }

        .notification-icon:hover {
            color: var(--accent-color);
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
        <?php include 'instructor_sidebar.php'; ?>
        <main class="main-content">
            <div class="header1">
                <br><br>
                <div class="welcome-text">
                    <h1>Greetings <?php echo htmlspecialchars($name); ?></h1>
                </div>
                <nav class="admin-nav-grid">
                    <a href="instructor_incident_report.php" class="admin-nav-item">
                        <div class="nav-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="nav-content">
                            <h3>Submit Student Violation</h3>
                            <p>Report and document student incidents</p>
                        </div>
                    </a>
                    <a href="view_incident_reports.php" class="admin-nav-item">
                        <div class="nav-icon">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <div class="nav-content">
                            <h3>View Submitted Violations</h3>
                            <p>View all previously submitted incident reports.</p>
                        </div>
                    </a>
                </nav>
                
                <!-- Notification Panel -->
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
                        url: 'instructor_homepage.php',
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
                    url: 'get_more_instructor_notifications.php',
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
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        </script>
</body>
</html>