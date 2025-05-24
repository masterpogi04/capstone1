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
    // First, check if this report exists in the archive
    $checkArchiveQuery = "SELECT * FROM archive_incident_reports WHERE id = ?";
    $checkArchiveStmt = $connection->prepare($checkArchiveQuery);
    $checkArchiveStmt->bind_param("s", $reportId);
    $checkArchiveStmt->execute();
    $archiveResult = $checkArchiveStmt->get_result();
    
    if ($archiveResult->num_rows === 0) {
        $_SESSION['error'] = "Report not found in the archive.";
        header("Location: archive_reports.php?alert=error");
        exit();
    }
    
    // Now retrieve data from backup tables, not archive tables
    // Get the report data from backup_incident_reports
    $getBackupReportQuery = "SELECT * FROM backup_incident_reports WHERE id = ?";
    $getBackupReportStmt = $connection->prepare($getBackupReportQuery);
    $getBackupReportStmt->bind_param("s", $reportId);
    $getBackupReportStmt->execute();
    $backupReportResult = $getBackupReportStmt->get_result();
    
    if ($backupReportResult->num_rows === 0) {
        $_SESSION['error'] = "Report not found in backup tables.";
        header("Location: archive_reports.php?alert=error");
        exit();
    }
    
    $backupData = $backupReportResult->fetch_assoc();
    
    // Check if there's already a report with this ID in the active tables
    $checkActiveQuery = "SELECT id FROM incident_reports WHERE id = ?";
    $checkActiveStmt = $connection->prepare($checkActiveQuery);
    $checkActiveStmt->bind_param("s", $reportId);
    $checkActiveStmt->execute();
    $activeResult = $checkActiveStmt->get_result();
    
    $newReportId = $reportId;
    
    if ($activeResult->num_rows > 0) {
        // There's already a report with this ID in the active tables
        // Generate a new ID by incrementing the numeric part
        if (preg_match('/(.*-)(\d+)$/', $reportId, $matches)) {
            // Extract prefix and number
            $prefix = $matches[1];
            $number = intval($matches[2]);
            $newIdFound = false;
            
            // Try up to 100 new IDs
            for ($i = 1; $i <= 100; $i++) {
                $candidateNumber = $number + $i;
                $paddedNumber = str_pad($candidateNumber, strlen($matches[2]), '0', STR_PAD_LEFT);
                $candidateId = $prefix . $paddedNumber;
                
                // Check if this ID is available
                $checkQuery = "SELECT id FROM incident_reports WHERE id = ?";
                $checkStmt = $connection->prepare($checkQuery);
                $checkStmt->bind_param("s", $candidateId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows === 0) {
                    $newReportId = $candidateId;
                    $newIdFound = true;
                    break;
                }
            }
            
            // If we couldn't find a suitable ID using incrementation, use timestamp fallback
            if (!$newIdFound) {
                $newReportId = $reportId . "-recovered-" . time();
            }
        } else {
            // If no numeric pattern detected, append a number
            for ($i = 1; $i <= 100; $i++) {
                $candidateId = $reportId . "-" . $i;
                
                $checkQuery = "SELECT id FROM incident_reports WHERE id = ?";
                $checkStmt = $connection->prepare($checkQuery);
                $checkStmt->bind_param("s", $candidateId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows === 0) {
                    $newReportId = $candidateId;
                    break;
                }
            }
            
            // If we still don't have a unique ID, use timestamp
            if ($newReportId === $reportId) {
                $newReportId = $reportId . "-recovered-" . time();
            }
        }
        
        // Log the new ID for debugging
        error_log("Recovering report $reportId with new ID: $newReportId");
    }
    
    // Insert data from backup tables to the active tables
    
    // Insert into incident_reports
    $insertReportQuery = "INSERT INTO incident_reports (
                            id, date_reported, place, description, reported_by, 
                            reporters_id, reported_by_type, file_path, created_at, 
                            status, approval_date, facilitator_id, resolution_status, 
                            resolution_notes, is_archived
                          ) VALUES (
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?
                          )";
    
    $insertStmt = $connection->prepare($insertReportQuery);
    $insertStmt->bind_param(
        "ssssssssssssssi",
        $newReportId,
        $backupData['date_reported'],
        $backupData['place'],
        $backupData['description'],
        $backupData['reported_by'],
        $backupData['reporters_id'],
        $backupData['reported_by_type'],
        $backupData['file_path'],
        $backupData['created_at'],
        $backupData['status'],
        $backupData['approval_date'],
        $backupData['facilitator_id'],
        $backupData['resolution_status'],
        $backupData['resolution_notes'],
        $backupData['is_archived']
    );
    
    if (!$insertStmt->execute()) {
        throw new Exception("Failed to restore incident report: " . $connection->error);
    }
    
    // Get violations from backup
    $getViolationsQuery = "SELECT * FROM backup_student_violations WHERE incident_report_id = ?";
    $getViolationsStmt = $connection->prepare($getViolationsQuery);
    $getViolationsStmt->bind_param("s", $reportId);
    $getViolationsStmt->execute();
    $violationsResult = $getViolationsStmt->get_result();
    
    // Insert violations
    while ($violation = $violationsResult->fetch_assoc()) {
        $insertViolationQuery = "INSERT INTO student_violations (
                                  student_id, incident_report_id, violation_date, 
                                  status, student_name, student_course, student_year_level, 
                                  is_archived, section_id, section_name, adviser_id, adviser_name
                                ) VALUES (
                                  ?, ?, ?, 
                                  ?, ?, ?, ?, 
                                  ?, ?, ?, ?, ?
                                )";
        
        $insertStmt = $connection->prepare($insertViolationQuery);
        $insertStmt->bind_param(
            "sssssssissis",
            $violation['student_id'],
            $newReportId,
            $violation['violation_date'],
            $violation['status'],
            $violation['student_name'],
            $violation['student_course'],
            $violation['student_year_level'],
            $violation['is_archived'],
            $violation['section_id'],
            $violation['section_name'],
            $violation['adviser_id'],
            $violation['adviser_name']
        );
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to restore student violation: " . $connection->error);
        }
    }
    
    // Get witnesses from backup
    $getWitnessesQuery = "SELECT * FROM backup_incident_witnesses WHERE incident_report_id = ?";
    $getWitnessesStmt = $connection->prepare($getWitnessesQuery);
    $getWitnessesStmt->bind_param("s", $reportId);
    $getWitnessesStmt->execute();
    $witnessesResult = $getWitnessesStmt->get_result();
    
    // Insert witnesses
    while ($witness = $witnessesResult->fetch_assoc()) {
        $insertWitnessQuery = "INSERT INTO incident_witnesses (
                                incident_report_id, witness_type, witness_id, 
                                witness_name, witness_student_name, witness_course, 
                                witness_year_level, witness_email, section_id, 
                                section_name, adviser_id, adviser_name
                              ) VALUES (
                                ?, ?, ?, 
                                ?, ?, ?, 
                                ?, ?, ?, 
                                ?, ?, ?
                              )";
        
        $insertStmt = $connection->prepare($insertWitnessQuery);
        $insertStmt->bind_param(
            "ssssssssisss",
            $newReportId,
            $witness['witness_type'],
            $witness['witness_id'],
            $witness['witness_name'],
            $witness['witness_student_name'],
            $witness['witness_course'],
            $witness['witness_year_level'],
            $witness['witness_email'],
            $witness['section_id'],
            $witness['section_name'],
            $witness['adviser_id'],
            $witness['adviser_name']
        );
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to restore witness: " . $connection->error);
        }
    }
    
    // After successfully restoring, delete from both archive and backup tables
    
    // Delete from archive_incident_witnesses
    $deleteArchiveWitnessesQuery = "DELETE FROM archive_incident_witnesses WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteArchiveWitnessesQuery);
    $deleteStmt->bind_param("s", $reportId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete witnesses from archive: " . $connection->error);
    }
    
    // Delete from archive_student_violations
    $deleteArchiveViolationsQuery = "DELETE FROM archive_student_violations WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteArchiveViolationsQuery);
    $deleteStmt->bind_param("s", $reportId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete violations from archive: " . $connection->error);
    }
    
    // Delete from archive_incident_reports
    $deleteArchiveReportQuery = "DELETE FROM archive_incident_reports WHERE id = ?";
    $deleteStmt = $connection->prepare($deleteArchiveReportQuery);
    $deleteStmt->bind_param("s", $reportId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete report from archive: " . $connection->error);
    }
    
    // Now delete from backup tables
    
    // Delete from backup_incident_witnesses
    $deleteBackupWitnessesQuery = "DELETE FROM backup_incident_witnesses WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteBackupWitnessesQuery);
    $deleteStmt->bind_param("s", $reportId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete witnesses from backup: " . $connection->error);
    }
    
    // Delete from backup_student_violations
    $deleteBackupViolationsQuery = "DELETE FROM backup_student_violations WHERE incident_report_id = ?";
    $deleteStmt = $connection->prepare($deleteBackupViolationsQuery);
    $deleteStmt->bind_param("s", $reportId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete violations from backup: " . $connection->error);
    }
    
    // Delete from backup_incident_reports
    $deleteBackupReportQuery = "DELETE FROM backup_incident_reports WHERE id = ?";
    $deleteStmt = $connection->prepare($deleteBackupReportQuery);
    $deleteStmt->bind_param("s", $reportId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete report from backup: " . $connection->error);
    }
    
    // Commit the transaction
    $connection->commit();
    
    if ($newReportId !== $reportId) {
        $_SESSION['success'] = "Report recovered successfully with new ID: " . $newReportId;
    } else {
        $_SESSION['success'] = "Report recovered successfully.";
    }
    
    header("Location: archive_reports.php?alert=success");
    
} catch (Exception $e) {
    // Rollback the transaction if an error occurred
    $connection->rollback();
    $_SESSION['error'] = "Error recovering report: " . $e->getMessage();
    header("Location: archive_reports.php?alert=error");
}
?>