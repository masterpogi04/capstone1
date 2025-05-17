<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Check database connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Modified query to get only active student record
$stmt = $connection->prepare("SELECT id, first_name, last_name, email 
                            FROM tbl_student 
                            WHERE student_id = ? 
                            AND status = 'active'
                            AND password IS NOT NULL");  // Ensure the account is set up

if (!$stmt) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("s", $student_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();
    $first_name = $student['first_name'];
    $student_name = trim($student['first_name'] . ' ' . $student['last_name']);
} else {
    // Redirect to account setup if the account exists but password is not set
    $check_exists = $connection->prepare("SELECT id FROM tbl_student WHERE student_id = ?");
    $check_exists->bind_param("s", $student_id);
    $check_exists->execute();
    $exists_result = $check_exists->get_result();
    
    if ($exists_result->num_rows > 0) {
        header("Location: ../account_setup.php");
    } else {
        header("Location: ../login.php?error=invalid_account");
    }
    exit();
}

// Pagination for notifications - default is 5 per page
$notifications_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $notifications_per_page;

// Fetch notifications for student with pagination
$notifications_query = "SELECT * FROM notifications 
                      WHERE user_type = 'student' 
                      AND user_id = ? 
                      AND is_read = 0 
                      ORDER BY created_at DESC
                      LIMIT ? OFFSET ?";
                      
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("sii", $student_id, $notifications_per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);

// Count total notifications for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications 
               WHERE user_type = 'student' 
               AND user_id = ? 
               AND is_read = 0";
$stmt = $connection->prepare($count_query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Function to add sample notifications (for simulation purposes)
function addSampleNotifications($connection, $student_id) {
    $sample_notifications = [
        ["New document request status update", "request_form.php"],
        ["Profile form update required", "student_profile_form.php"],
        ["New announcement from the Guidance Office", "#"],
    ];

    $query = "INSERT INTO notifications (user_type, user_id, message, link, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $connection->prepare($query);

    foreach ($sample_notifications as $notification) {
        $user_type = 'student';
        $stmt->bind_param("ssss", $user_type, $student_id, $notification[0], $notification[1]);
        $stmt->execute();
    }
}

// If no notifications, add sample ones (for simulation)
if (empty($notifications) && $page === 1) {
    addSampleNotifications($connection, $student_id);
    
    // Fetch notifications again after adding samples
    $stmt = $connection->prepare($notifications_query);
    $stmt->bind_param("sii", $student_id, $notifications_per_page, $offset);
    $stmt->execute();
    $notifications_result = $stmt->get_result();
    $notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
    
    // Update total count
    $stmt = $connection->prepare($count_query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_row = $count_result->fetch_assoc();
    $total_notifications = $total_row['total'];
    $total_pages = ceil($total_notifications / $notifications_per_page);
}

// Handle notification deletion via AJAX
if (isset($_POST['delete_notification'])) {
    $notification_id = $_POST['notification_id'];
    $delete_query = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    $delete_stmt = $connection->prepare($delete_query);
    $delete_stmt->bind_param("is", $notification_id, $student_id);
    $result = $delete_stmt->execute();
    
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
    <title>Student Dashboard</title>
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
        .button-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .first-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .second-row {
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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            .first-row, .second-row {
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
            <?php if (!empty($notifications)): ?>
                <span class="notification-badge"></span>
            <?php endif; ?>
        </i>
    </div>
    <?php include 'student_sidebar.php'; ?>
    <main class="main-content">
        <div class="header1">
            <br><br>
        <div class="welcome-text">
            <h1>WELCOME <?php echo htmlspecialchars($first_name); ?>!</h1>
        </div>
        <div class="button-container">
    <div class="first-row">
        <a href="View_student_incident_reports.php" class="custom-btn">
            <div class="btn-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="btn-content">
                <h3 class="btn-title">View My Violation Records</h3>
                <p class="btn-subtitle">Check your disciplinary history</p>
            </div>
        </a>

        <a href="request_form.php" class="custom-btn">
            <div class="btn-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="btn-content">
                <h3 class="btn-title">Request Document</h3>
                <p class="btn-subtitle">Submit official document requests</p>
            </div>
        </a>

        <a href="student_profile_form.php" class="custom-btn">
            <div class="btn-icon">
                <i class="fas fa-user-edit"></i>
            </div>
            <div class="btn-content">
                <h3 class="btn-title">Student Profile Form</h3>
                <p class="btn-subtitle">Update your personal information</p>
            </div>
        </a>

        <a href="view_student_profile.php" class="custom-btn">
            <div class="btn-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="btn-content">
                <h3 class="btn-title">View My Student Profile</h3>
                <p class="btn-subtitle">Access your profile details</p>
            </div>
        </a>

        <a href="student_incident_report.php" class="custom-btn">
            <div class="btn-icon">
                <i class="fas fa-flag"></i>
            </div>
            <div class="btn-content">
                <h3 class="btn-title">Submit Incident Report</h3>
                <p class="btn-subtitle">Report incidents or violations</p>
            </div>
        </a>

        <a href="view_submitted_incident_reports.php" class="custom-btn">
            <div class="btn-icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="btn-content">
                <h3 class="btn-title">View Submitted Incident Report</h3>
                <p class="btn-subtitle">Track your submitted reports</p>
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
                    url: 'student_homepage.php',
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
                url: 'get_more_student_notifications.php',
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