<?php
session_start();
include '../db.php'; // Ensure database connection is established

// Ensure the user is logged in and is a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

// Get the counselor's ID from the session
$counselor_id = $_SESSION['user_id'];

// Get pagination parameters
$notifications_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $notifications_per_page;

// Fetch notifications for counselor with pagination
$notifications_query = "SELECT * FROM notifications 
                       WHERE user_type = 'counselor' 
                       AND user_id = ? 
                       AND is_read = 0 
                       ORDER BY created_at DESC
                       LIMIT ? OFFSET ?";
                       
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("iii", $counselor_id, $notifications_per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = [];
while ($row = $notifications_result->fetch_assoc()) {
    $notifications[] = $row;
}

// Count total notifications for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications 
               WHERE user_type = 'counselor' 
               AND user_id = ? 
               AND is_read = 0";
$stmt = $connection->prepare($count_query);
$stmt->bind_param("i", $counselor_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Check if there are more pages
$has_more = ($page < $total_pages);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'has_more' => $has_more,
    'current_page' => $page,
    'total_pages' => $total_pages
]);

mysqli_close($connection);
?>