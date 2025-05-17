<?php
error_log(print_r($_POST, true)); // At the top to see what's being received
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Unauthorized access');
}

if (isset($_POST['dept_id']) && isset($_POST['action'])) {
    $dept_id = $_POST['dept_id'];
    $action = $_POST['action'];
    
    // Set status value based on action
    $status = ($action === 'disable') ? 'disabled' : 'active';
    
    // Start transaction
    $connection->begin_transaction();
    
    try {
        // First update the department
        $stmt = $connection->prepare("UPDATE departments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $dept_id);
        $stmt->execute();
        
        // If disabling department, also disable all its courses
        if ($action === 'disable') {
            $stmt = $connection->prepare("UPDATE courses SET status = 'disabled' WHERE department_id = ?");
            $stmt->bind_param("i", $dept_id);
            $stmt->execute();
        }
        
        $connection->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(['success' => false]);
    }
}
?>