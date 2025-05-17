<?php
session_start();
include '../db.php'; // Ensure database connection is established

// Ensure the user is logged in and is a dean
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

// Get pagination parameters
$notifications_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $notifications_per_page;

// Fetch notifications for dean with pagination
$notifications_query = "SELECT * FROM notifications WHERE user_type = 'dean' AND is_read = 0 ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $connection->prepare($notifications_query);
$stmt->bind_param("ii", $notifications_per_page, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = [];

// Count total notifications for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_type = 'dean' AND is_read = 0";
$count_result = $connection->query($count_query);
$total_row = $count_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $notifications_per_page);

// Load notifications
if ($notifications_result) {
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

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