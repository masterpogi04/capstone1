<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

if (isset($_POST['id']) && isset($_POST['table']) && isset($_POST['status'])) {
    $id = $_POST['id'];
    $table = $_POST['table'];
    $new_status = $_POST['status'];
    
    $allowed_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_guard'];
    
    if (!in_array($table, $allowed_tables)) {
        die(json_encode(['success' => false, 'error' => 'Invalid table']));
    }
    
    $stmt = $connection->prepare("UPDATE $table SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Account status updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update account status'
        ]);
    }
    
    $stmt->close();
}

mysqli_close($connection);
?>