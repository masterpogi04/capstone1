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

// Pagination for notifications - default is 10 per page
$notifications_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $notifications_per_page;

// Fetch notifications for guard with pagination
$notifications_query = "SELECT * FROM notifications 
                        WHERE user_type = 'guard' 
                        AND user_id = ? 
                        AND is_read = 0 
                        ORDER BY created_at DESC
                        LIMIT ? OFFSET ?";
                        
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("iii", $guard_id, $notifications_per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);

// Count total notifications for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications 
                WHERE user_type = 'guard' 
                AND user_id = ? 
                AND is_read = 0";
$stmt = $connection->prepare($count_query);
$stmt->bind_param("i", $guard_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Handle notification deletion via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notification_id = $_POST['notification_id'];
    $delete_query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = 'guard' AND user_id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $guard_id);
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

// Fetch the guard's details from the database
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_guard WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("i", $guard_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $guard = $result->fetch_assoc();
    $name = $guard['first_name'] . ' ' . $guard['last_name'];
} else  {
    die("guard not found.");
}

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
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
}

.button-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.custom-btn {
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

.btn-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #ffffff;
}

.btn-subtitle {
    margin: 5px 0 0;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.3;
}

.custom-btn:hover {
    background-color: #15573A;
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    color: #ffffff;
    text-decoration: none;
}

/* Responsive styles */
@media (max-width: 768px) {
    .button-container {
        padding: 1rem 0.5rem;
    }

    .button-row {
        grid-template-columns: 1fr;
    }

    .custom-btn {
        height: auto;
        min-height: 100px;
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
    <?php include 'guard_sidebar.php'; ?>
    <main class="main-content">
        <div class="header1">
            <br><br>
            <div class="welcome-text">
                <h1>GREETINGS CEIT <?php echo htmlspecialchars($name); ?>!!</h1>
            </div>

            <div class="button-container">
                <div class="button-row">
                    <a href="incident_report_guards.php" class="custom-btn">
                        <div class="btn-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="btn-content">
                            <h3 class="btn-title">Submit Student Violation</h3>
                            <p class="btn-subtitle">Create new violation report</p>
                        </div>
                    </a>
                    <a href="view_submitted_incident_reports_guard.php" class="custom-btn">
                        <div class="btn-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="btn-content">
                            <h3 class="btn-title">View Submitted Student Violation</h3>
                            <p class="btn-subtitle">Review past violation records</p>
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
                                <a href="<?php echo htmlspecialchars($notif['link']); ?>">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </a>
                                <small><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
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
                    url: 'guard_homepage.php',
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
                url: 'get_more_guard_notifications.php',
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
                                    <small>${formatDate(notif.created_at)}</small>
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
        
        // Format date for new notifications
        function formatDate(dateString) {
            const date = new Date(dateString);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = months[date.getMonth()];
            const day = date.getDate();
            const year = date.getFullYear();
            let hours = date.getHours();
            const minutes = date.getMinutes().toString().padStart(2, '0');
            
            return `${month} ${day}, ${year} ${hours}:${minutes}`;
        }
        
        // Close notifications when clicking outside
        $(document).click(function(e) {
            if (!$(e.target).closest('#notificationPanel').length && 
                !$(e.target).closest('.notification-icon').length) {
                $('#notificationPanel').hide();
            }
        });
    });

    
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