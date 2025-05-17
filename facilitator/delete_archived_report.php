<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No report ID provided.";
    header("Location: archive_reports.php?alert=error");
    exit();
}

$reportId = $_GET['id'];

// Start transaction
$connection->begin_transaction();

try {
    // Check if the report exists in the archive
    $checkQuery = "SELECT id FROM archive_incident_reports WHERE id = ?";
    $checkStmt = $connection->prepare($checkQuery);
    $checkStmt->bind_param("s", $reportId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Report not found in the archive.";
        header("Location: archive_reports.php?alert=error");
        exit();
    }
    
    // Delete from backup_incident_witnesses
    $deleteBackupWitnessesQuery = "DELETE FROM backup_incident_witnesses WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteBackupWitnessesQuery);
    $deleteStmt->bind_param("s", $reportId);
    $deleteStmt->execute();
    $backupWitnessesDeleted = $deleteStmt->affected_rows;
    
    // Delete from backup_student_violations
    $deleteBackupViolationsQuery = "DELETE FROM backup_student_violations WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteBackupViolationsQuery);
    $deleteStmt->bind_param("s", $reportId);
    $deleteStmt->execute();
    $backupViolationsDeleted = $deleteStmt->affected_rows;
    
    // Delete from backup_meetings
    $deleteBackupMeetingsQuery = "DELETE FROM backup_meetings WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteBackupMeetingsQuery);
    $deleteStmt->bind_param("s", $reportId);
    $deleteStmt->execute();
    $backupMeetingsDeleted = $deleteStmt->affected_rows;
    
    // Delete from backup_incident_reports
    $deleteBackupReportQuery = "DELETE FROM backup_incident_reports WHERE id = ?";
    $deleteStmt = $connection->prepare($deleteBackupReportQuery);
    $deleteStmt->bind_param("s", $reportId);
    $deleteStmt->execute();
    $backupReportDeleted = $deleteStmt->affected_rows;
    
    // Log the backup deletions
    error_log("Deleted from backup tables: $backupReportDeleted reports, $backupViolationsDeleted violations, $backupWitnessesDeleted witnesses, $backupMeetingsDeleted meetings");
    
    // Delete from archive_incident_witnesses
    $deleteWitnessesQuery = "DELETE FROM archive_incident_witnesses WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteWitnessesQuery);
    $deleteStmt->bind_param("s", $reportId);
    $deleteStmt->execute();
    $witnessesDeleted = $deleteStmt->affected_rows;
    
    // Delete from archive_student_violations
    $deleteViolationsQuery = "DELETE FROM archive_student_violations WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteViolationsQuery);
    $deleteStmt->bind_param("s", $reportId);
    $deleteStmt->execute();
    $violationsDeleted = $deleteStmt->affected_rows;
    
    // Delete from archive_incident_reports
    $deleteReportQuery = "DELETE FROM archive_incident_reports WHERE id = ?";
    $deleteStmt = $connection->prepare($deleteReportQuery);
    $deleteStmt->bind_param("s", $reportId);
    $deleteStmt->execute();
    $reportDeleted = $deleteStmt->affected_rows;
    
    // Log the archive deletions
    error_log("Deleted from archive tables: $reportDeleted reports, $violationsDeleted violations, $witnessesDeleted witnesses");
    
    // Commit the transaction
    $connection->commit();
    
    $_SESSION['success'] = "Report permanently deleted successfully from both archive and backup tables.";
    header("Location: archive_reports.php?alert=success");
    
} catch (Exception $e) {
    // Rollback the transaction if an error occurred
    $connection->rollback();
    $_SESSION['error'] = "Error deleting report: " . $e->getMessage();
    header("Location: archive_reports.php?alert=error");
}
?>