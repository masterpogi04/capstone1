<?php
session_start();
include '../db.php';

// Set up error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Single student archive process started at " . date('Y-m-d H:i:s'));

// Basic security check
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please log in first';
    header('Location: login.php');
    error_log("Session validation failed - user not logged in");
    exit;
}

// Check if student_id is provided
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    $_SESSION['error_message'] = 'No student ID provided';
    header('Location: index.php');
    error_log("No student ID provided for archiving");
    exit;
}

$studentId = $_GET['student_id'];
error_log("Processing archive request for student ID: $studentId");

// Validate student ID format (assuming student IDs are numeric and 6-12 digits)
if (!preg_match('/^\d{6,12}$/', $studentId)) {
    $_SESSION['error_message'] = 'Invalid student ID format';
    header('Location: index.php');
    error_log("Invalid student ID format: $studentId");
    exit;
}

// Start database transaction
$connection->begin_transaction();

try {
    // Check if student exists
    $checkStmt = $connection->prepare("SELECT COUNT(*) FROM student_profiles WHERE student_id = ?");
    $checkStmt->bind_param("s", $studentId);
    $checkStmt->execute();
    $checkStmt->bind_result($studentExists);
    $checkStmt->fetch();
    $checkStmt->close();
    
    if (!$studentExists) {
        $connection->rollback();
        $_SESSION['error_message'] = "Student profile not found";
        header('Location: view_student_profiles.php');
        error_log("Student ID $studentId not found in database");
        exit;
    }
    
    // Check if already archived
    $archiveCheckStmt = $connection->prepare("SELECT COUNT(*) FROM archive_student_profiles WHERE student_id = ?");
    $archiveCheckStmt->bind_param("s", $studentId);
    $archiveCheckStmt->execute();
    $archiveCheckStmt->bind_result($alreadyArchived);
    $archiveCheckStmt->fetch();
    $archiveCheckStmt->close();
    
    if ($alreadyArchived) {
        $connection->rollback();
        $_SESSION['error_message'] = "Student profile is already archived";
        header('Location: view_student_profiles.php');
        error_log("Student ID $studentId is already archived");
        exit;
    }
    
    // Get all column names EXCEPT is_archived from the target table to ensure proper column matching
    $columnsQuery = "SHOW COLUMNS FROM archive_student_profiles";
    $columnsResult = $connection->query($columnsQuery);
    $columns = [];
    
    while ($column = $columnsResult->fetch_assoc()) {
        if ($column['Field'] != 'is_archived') {
            $columns[] = $column['Field'];
        }
    }
    
    $columnsList = implode(", ", $columns);
    error_log("Using columns for copy: " . $columnsList);
    
    // Insert into archive table
    $insertQuery = $connection->prepare("
        INSERT INTO archive_student_profiles ($columnsList, is_archived)
        SELECT $columnsList, 1 
        FROM student_profiles 
        WHERE student_id = ?
    ");
    $insertQuery->bind_param("s", $studentId);
    $insertSuccess = $insertQuery->execute();
    $errorMsg = $insertQuery->error;
    $insertQuery->close();
    
    if (!$insertSuccess) {
        $connection->rollback();
        $_SESSION['error_message'] = "Failed to archive student profile: " . $errorMsg;
        header('Location: view_student_profile.php?student_id=' . $studentId);
        error_log("Failed to insert student ID $studentId: " . $errorMsg);
        exit;
    }
    
    error_log("Successfully inserted student ID $studentId into archive_student_profiles");
    
    // Delete from original table
    $deleteQuery = $connection->prepare("DELETE FROM student_profiles WHERE student_id = ?");
    $deleteQuery->bind_param("s", $studentId);
    $deleteSuccess = $deleteQuery->execute();
    $errorMsg = $deleteQuery->error;
    $deleteQuery->close();
    
    if (!$deleteSuccess) {
        $connection->rollback();
        $_SESSION['error_message'] = "Failed to delete original student profile: " . $errorMsg;
        header('Location: view_student_profile.php?student_id=' . $studentId);
        error_log("Failed to delete student ID $studentId: " . $errorMsg);
        exit;
    }
    
    // If everything succeeded, commit the transaction
    $connection->commit();
    $_SESSION['success_message'] = "Student profile successfully archived";
    error_log("Successfully archived student ID $studentId");
    header('Location: archive_view_profile.php');
    exit;
    
} catch (Exception $e) {
    $connection->rollback();
    error_log("Exception during archiving: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    header('Location: view_student_profile.php?student_id=' . $studentId);
    exit;
}

error_log("Single student archive process completed at " . date('Y-m-d H:i:s'));
?>