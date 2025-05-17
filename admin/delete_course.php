<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Unauthorized access');
}

if (isset($_POST['course_id']) && isset($_POST['action'])) {
    $course_id = $_POST['course_id'];
    $action = $_POST['action'];
    
    // Set status value based on action
    $status = ($action === 'disable') ? 'disabled' : 'active';
    
    $stmt = $connection->prepare("UPDATE courses SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $course_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} 
?>