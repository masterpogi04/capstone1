<?php
session_start();
include '../db.php';

// Set up error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Student profile restore process started at " . date('Y-m-d H:i:s'));

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
    header('Location: view_archived_profiles.php');
    error_log("No student ID provided for restoration");
    exit;
}

$studentId = $_GET['student_id'];
error_log("Processing restore request for student ID: $studentId");

// Validate student ID format (assuming student IDs are numeric and 6-12 digits)
if (!preg_match('/^\d{6,12}$/', $studentId)) {
    $_SESSION['error_message'] = 'Invalid student ID format';
    header('Location: view_archived_profiles.php');
    error_log("Invalid student ID format: $studentId");
    exit;
}

// Start database transaction
$connection->begin_transaction();

try {
    // Check if student exists in archive
    $checkStmt = $connection->prepare("SELECT COUNT(*) FROM archive_student_profiles WHERE student_id = ?");
    $checkStmt->bind_param("s", $studentId);
    $checkStmt->execute();
    $checkStmt->bind_result($studentExists);
    $checkStmt->fetch();
    $checkStmt->close();
    
    if (!$studentExists) {
        $connection->rollback();
        $_SESSION['error_message'] = "Archived student profile not found";
        header('Location: view_archived_profiles.php');
        error_log("Student ID $studentId not found in archive database");
        exit;
    }
    
    // Check if student already exists in active profiles (prevent duplicates)
    $activeCheckStmt = $connection->prepare("SELECT COUNT(*) FROM student_profiles WHERE student_id = ?");
    $activeCheckStmt->bind_param("s", $studentId);
    $activeCheckStmt->execute();
    $activeCheckStmt->bind_result($alreadyActive);
    $activeCheckStmt->fetch();
    $activeCheckStmt->close();
    
    if ($alreadyActive) {
        $connection->rollback();
        $_SESSION['error_message'] = "Student profile already exists in active records";
        header('Location: view_archived_profiles.php');
        error_log("Student ID $studentId already exists in active records");
        exit;
    }
    
    // Get all column names from the student_profiles table to ensure proper column matching
    $columnsQuery = "SHOW COLUMNS FROM student_profiles";
    $columnsResult = $connection->query($columnsQuery);
    $columns = [];
    
    while ($column = $columnsResult->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    $columnsList = implode(", ", $columns);
    error_log("Using columns for restore: " . $columnsList);
    
    // Insert into student_profiles table
    $insertQuery = $connection->prepare("
        INSERT INTO student_profiles ($columnsList)
        SELECT $columnsList 
        FROM archive_student_profiles 
        WHERE student_id = ?
    ");
    $insertQuery->bind_param("s", $studentId);
    $insertSuccess = $insertQuery->execute();
    $errorMsg = $insertQuery->error;
    $insertQuery->close();
    
    if (!$insertSuccess) {
        $connection->rollback();
        $_SESSION['error_message'] = "Failed to restore student profile: " . $errorMsg;
        header('Location: view_archived_profile.php?student_id=' . $studentId);
        error_log("Failed to insert student ID $studentId into active profiles: " . $errorMsg);
        exit;
    }
    
    error_log("Successfully restored student ID $studentId to student_profiles");
    
    // Delete from archive table
    $deleteQuery = $connection->prepare("DELETE FROM archive_student_profiles WHERE student_id = ?");
    $deleteQuery->bind_param("s", $studentId);
    $deleteSuccess = $deleteQuery->execute();
    $errorMsg = $deleteQuery->error;
    $deleteQuery->close();
    
    if (!$deleteSuccess) {
        $connection->rollback();
        $_SESSION['error_message'] = "Failed to remove profile from archive: " . $errorMsg;
        header('Location: view_archived_profile.php?student_id=' . $studentId);
        error_log("Failed to delete student ID $studentId from archive: " . $errorMsg);
        exit;
    }
    
    // If everything succeeded, commit the transaction
    $connection->commit();
    $_SESSION['success_message'] = "Student profile successfully restored";
    error_log("Successfully restored student ID $studentId");
    header('Location: view_profiles.php');
    exit;
    
} catch (Exception $e) {
    $connection->rollback();
    error_log("Exception during restoration: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
    header('Location: view_archived_profile.php?student_id=' . $studentId);
    exit;
}

error_log("Student profile restore process completed at " . date('Y-m-d H:i:s'));
?>