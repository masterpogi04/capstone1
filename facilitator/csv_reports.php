<?php
session_start();
include '../db.php';

// Validate user session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile'];
    
    // File upload validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "File upload error: " . $file['error'];
        header("Location: archive_reports.php?alert=error");
        exit();
    }
    
    // File type validation
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExt !== 'csv') {
        $_SESSION['error'] = "Only CSV files are allowed";
        header("Location: archive_reports.php?alert=error");
        exit();
    }
    
    // Process CSV file
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        $successCount = 0;
        $errorCount = 0;
        $studentNumbers = [];
        
        // Read student numbers from CSV
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (!empty($data[0])) {
                $studentNumber = preg_replace('/[^0-9]/', '', $data[0]);
                if (!empty($studentNumber)) {
                    $studentNumbers[] = $studentNumber;
                }
            }
        }
        fclose($handle);
        
        if (empty($studentNumbers)) {
            $_SESSION['error'] = "No valid student numbers found in the CSV";
            header("Location: archive_reports.php?alert=error");
            exit();
        }

        // Start database transaction
        $connection->begin_transaction();
        
        try {
            // Debug message to error log
            error_log("Starting archive process for " . count($studentNumbers) . " students");
            
            // First, find all reports where these students are involved as violators
            $placeholders = implode(',', array_fill(0, count($studentNumbers), '?'));
            
            $reportQuery = "SELECT DISTINCT ir.id 
                          FROM incident_reports ir
                          JOIN student_violations sv ON sv.incident_report_id = ir.id
                          WHERE sv.student_id IN ($placeholders)";
            
            $stmt = $connection->prepare($reportQuery);
            $types = str_repeat('s', count($studentNumbers));
            $stmt->bind_param($types, ...$studentNumbers);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $relevantReports = [];
            while ($row = $result->fetch_assoc()) {
                $relevantReports[] = $row['id'];
            }
            
            error_log("Found " . count($relevantReports) . " relevant reports for these students");
            
            // Backup the relevant reports and their related data first
            foreach ($relevantReports as $reportId) {
                // DIRECT COPY: Check if report already exists in backup
                $checkQuery = "SELECT id FROM backup_incident_reports WHERE id = ?";
                $checkStmt = $connection->prepare($checkQuery);
                $checkStmt->bind_param("s", $reportId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows == 0) {
                    // DIRECT COPY: Insert report into backup_incident_reports
                    $backupReportQuery = "INSERT INTO backup_incident_reports SELECT * FROM incident_reports WHERE id = ?";
                    $stmt = $connection->prepare($backupReportQuery);
                    $stmt->bind_param("s", $reportId);
                    $backupReportSuccess = $stmt->execute();
                    
                    if (!$backupReportSuccess) {
                        error_log("Error backing up report {$reportId}: " . $connection->error);
                    } else {
                        error_log("Backed up report {$reportId} to backup_incident_reports");
                    }
                } else {
                    error_log("Report {$reportId} already exists in backup_incident_reports");
                }
                
                // DIRECT COPY: Student violations - check for existing violations by ID
                $getViolationsQuery = "SELECT id FROM student_violations WHERE incident_report_id = ?";
                $stmt = $connection->prepare($getViolationsQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $violationsResult = $stmt->get_result();
                
                while ($violation = $violationsResult->fetch_assoc()) {
                    // Check if violation already exists in backup
                    $checkQuery = "SELECT id FROM backup_student_violations WHERE id = ?";
                    $checkStmt = $connection->prepare($checkQuery);
                    $checkStmt->bind_param("s", $violation['id']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows == 0) {
                        // DIRECT COPY: Insert violation into backup_student_violations
                        $backupViolationQuery = "INSERT INTO backup_student_violations SELECT * FROM student_violations WHERE id = ?";
                        $stmt = $connection->prepare($backupViolationQuery);
                        $stmt->bind_param("s", $violation['id']);
                        $backupViolationSuccess = $stmt->execute();
                        
                        if (!$backupViolationSuccess) {
                            error_log("Error backing up violation {$violation['id']}: " . $connection->error);
                        }
                    } else {
                        error_log("Violation {$violation['id']} already exists in backup_student_violations");
                    }
                }
                
                // DIRECT COPY: Incident witnesses - check for existing witnesses by ID
                $getWitnessesQuery = "SELECT id FROM incident_witnesses WHERE incident_report_id = ?";
                $stmt = $connection->prepare($getWitnessesQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $witnessesResult = $stmt->get_result();
                
                while ($witness = $witnessesResult->fetch_assoc()) {
                    // Check if witness already exists in backup
                    $checkQuery = "SELECT id FROM backup_incident_witnesses WHERE id = ?";
                    $checkStmt = $connection->prepare($checkQuery);
                    $checkStmt->bind_param("s", $witness['id']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows == 0) {
                        // DIRECT COPY: Insert witness into backup_incident_witnesses
                        $backupWitnessQuery = "INSERT INTO backup_incident_witnesses SELECT * FROM incident_witnesses WHERE id = ?";
                        $stmt = $connection->prepare($backupWitnessQuery);
                        $stmt->bind_param("s", $witness['id']);
                        $backupWitnessSuccess = $stmt->execute();
                        
                        if (!$backupWitnessSuccess) {
                            error_log("Error backing up witness {$witness['id']}: " . $connection->error);
                        }
                    } else {
                        error_log("Witness {$witness['id']} already exists in backup_incident_witnesses");
                    }
                }
                
                error_log("Completed backup for report {$reportId}");
            }
            
            // Now update the student records to mark them as graduated
            $updateViolationsQuery = "UPDATE student_violations 
                                     SET student_course = 'Graduated', 
                                         student_year_level = 'Graduated', 
                                         section_name = 'Graduated'
                                     WHERE student_id IN ($placeholders)";
            $stmt = $connection->prepare($updateViolationsQuery);
            $types = str_repeat('s', count($studentNumbers));
            $stmt->bind_param($types, ...$studentNumbers);
            $stmt->execute();
            error_log("Updated student violations: " . $stmt->affected_rows);

            // Find incident reports eligible for archiving (all violators graduated)
            $archiveQuery = "SELECT DISTINCT ir.id 
               FROM incident_reports ir
               WHERE NOT EXISTS (
                   SELECT 1 FROM student_violations sv 
                   WHERE sv.incident_report_id = ir.id 
                   AND (
                       (sv.student_course IS NOT NULL AND sv.student_course != 'Graduated' AND sv.section_id IS NOT NULL) 
                       OR (sv.student_id IS NOT NULL AND sv.student_course IS NULL AND sv.section_id IS NOT NULL)
                   )
               )
               AND (
                   EXISTS (SELECT 1 FROM student_violations sv WHERE sv.incident_report_id = ir.id)
               )
               AND ir.status = 'settled'";  // Added this condition
            
            $stmt = $connection->prepare($archiveQuery);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $reportsToArchive = [];
            while ($row = $result->fetch_assoc()) {
                $reportsToArchive[] = $row['id'];
            }
            
            error_log("Reports to archive: " . count($reportsToArchive));
            error_log("Report IDs: " . implode(", ", $reportsToArchive));
            
            // Now archive each report
            foreach ($reportsToArchive as $reportId) {
                $archiveSuccess = true;
                
                // Check if this report already exists in the archive
                $checkQuery = "SELECT id FROM archive_incident_reports WHERE id = ?";
                $checkStmt = $connection->prepare($checkQuery);
                $checkStmt->bind_param("s", $reportId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // Report already exists in archive - need to use a new ID
                    error_log("Report {$reportId} already exists in archive, generating new ID");
                    
                    // Generate incremented ID
                    $newId = null;
                    
                    // Extract pattern (e.g., CEIT-24-25-0001)
                    if (preg_match('/(.*-)(\d+)$/', $reportId, $matches)) {
                        $prefix = $matches[1];
                        $number = intval($matches[2]);
                        
                        // Try up to 100 new IDs
                        for ($i = 1; $i <= 100; $i++) {
                            $candidateNumber = $number + $i;
                            $paddedNumber = str_pad($candidateNumber, strlen($matches[2]), '0', STR_PAD_LEFT);
                            $candidateId = $prefix . $paddedNumber;
                            
                            // Check if this ID is available
                            $checkQuery = "SELECT id FROM archive_incident_reports WHERE id = ?";
                            $checkStmt = $connection->prepare($checkQuery);
                            $checkStmt->bind_param("s", $candidateId);
                            $checkStmt->execute();
                            $checkResult = $checkStmt->get_result();
                            
                            if ($checkResult->num_rows == 0) {
                                $newId = $candidateId;
                                break;
                            }
                        }
                    } else {
                        // If no pattern, just append a number
                        for ($i = 1; $i <= 100; $i++) {
                            $candidateId = $reportId . "-" . $i;
                            
                            $checkQuery = "SELECT id FROM archive_incident_reports WHERE id = ?";
                            $checkStmt = $connection->prepare($checkQuery);
                            $checkStmt->bind_param("s", $candidateId);
                            $checkStmt->execute();
                            $checkResult = $checkStmt->get_result();
                            
                            if ($checkResult->num_rows == 0) {
                                $newId = $candidateId;
                                break;
                            }
                        }
                    }
                    
                    if (!$newId) {
                        error_log("Could not generate unique ID for report {$reportId}");
                        $errorCount++;
                        continue;
                    }
                    
                    error_log("Using new ID {$newId} for report {$reportId}");
                    
                    // Archive incident report with new ID
                    // First get all columns from the source table
                    $getColumnsQuery = "SHOW COLUMNS FROM incident_reports";
                    $result = $connection->query($getColumnsQuery);
                    $columns = [];
                    while ($row = $result->fetch_assoc()) {
                        if ($row['Field'] !== 'id') {
                            $columns[] = $row['Field'];
                        }
                    }
                    
                    // Construct the query with new ID
                    $columnList = implode(', ', $columns);
                    $archiveReportQuery = "INSERT INTO archive_incident_reports (id, $columnList)
                                          SELECT ?, $columnList
                                          FROM incident_reports WHERE id = ?";
                    
                    $stmt = $connection->prepare($archiveReportQuery);
                    $stmt->bind_param("ss", $newId, $reportId);
                    if (!$stmt->execute()) {
                        error_log("Error archiving report: " . $connection->error);
                        $archiveSuccess = false;
                    }
                    
                    // Archive student violations with new ID
                    if ($archiveSuccess) {
                        // Get all columns from the source table
                        $getColumnsQuery = "SHOW COLUMNS FROM student_violations";
                        $result = $connection->query($getColumnsQuery);
                        $columns = [];
                        $nonIdColumns = [];
                        while ($row = $result->fetch_assoc()) {
                            $columns[] = $row['Field'];
                            if ($row['Field'] !== 'id' && $row['Field'] !== 'incident_report_id') {
                                $nonIdColumns[] = $row['Field'];
                            }
                        }
                        
                        // Construct the query to keep the original id column
                        $nonIdColumnList = implode(', ', $nonIdColumns);
                        $archiveViolationsQuery = "INSERT INTO archive_student_violations (id, incident_report_id, $nonIdColumnList)
                                                  SELECT id, ?, $nonIdColumnList
                                                  FROM student_violations WHERE incident_report_id = ?";
                        
                        $stmt = $connection->prepare($archiveViolationsQuery);
                        $stmt->bind_param("ss", $newId, $reportId);
                        if (!$stmt->execute()) {
                            error_log("Error archiving violations: " . $connection->error);
                            $archiveSuccess = false;
                        }
                    }
                    
                    // Archive witnesses with new ID
                    if ($archiveSuccess) {
                        // Get all columns from the source table
                        $getColumnsQuery = "SHOW COLUMNS FROM incident_witnesses";
                        $result = $connection->query($getColumnsQuery);
                        $columns = [];
                        $nonIdColumns = [];
                        while ($row = $result->fetch_assoc()) {
                            $columns[] = $row['Field'];
                            if ($row['Field'] !== 'id' && $row['Field'] !== 'incident_report_id') {
                                $nonIdColumns[] = $row['Field'];
                            }
                        }
                        
                        // Construct the query
                        $nonIdColumnList = implode(', ', $nonIdColumns);
                        $archiveWitnessesQuery = "INSERT INTO archive_incident_witnesses (id, incident_report_id, $nonIdColumnList)
                                                 SELECT id, ?, $nonIdColumnList
                                                 FROM incident_witnesses WHERE incident_report_id = ?";
                        
                        $stmt = $connection->prepare($archiveWitnessesQuery);
                        $stmt->bind_param("ss", $newId, $reportId);
                        if (!$stmt->execute()) {
                            error_log("Error archiving witnesses: " . $connection->error);
                            $archiveSuccess = false;
                        }
                    }
                } else {
                    // No duplicate, use original ID
                    // Archive incident report
                    $archiveReportQuery = "INSERT INTO archive_incident_reports 
                                          SELECT * FROM incident_reports WHERE id = ?";
                    
                    $stmt = $connection->prepare($archiveReportQuery);
                    $stmt->bind_param("s", $reportId);
                    if (!$stmt->execute()) {
                        error_log("Error archiving report: " . $connection->error);
                        $archiveSuccess = false;
                    }
                    
                    // Archive student violations
                    if ($archiveSuccess) {
                        $archiveViolationsQuery = "INSERT INTO archive_student_violations 
                                                  SELECT * FROM student_violations WHERE incident_report_id = ?";
                        
                        $stmt = $connection->prepare($archiveViolationsQuery);
                        $stmt->bind_param("s", $reportId);
                        if (!$stmt->execute()) {
                            error_log("Error archiving violations: " . $connection->error);
                            $archiveSuccess = false;
                        }
                    }
                    
                    // Archive witnesses
                    if ($archiveSuccess) {
                        $archiveWitnessesQuery = "INSERT INTO archive_incident_witnesses 
                                                 SELECT * FROM incident_witnesses WHERE incident_report_id = ?";
                        
                        $stmt = $connection->prepare($archiveWitnessesQuery);
                        $stmt->bind_param("s", $reportId);
                        if (!$stmt->execute()) {
                            error_log("Error archiving witnesses: " . $connection->error);
                            $archiveSuccess = false;
                        }
                    }
                }
                
                // Delete from original tables if archive was successful
                if ($archiveSuccess) {
                    try {
                        // Delete witnesses first due to foreign key constraints
                        $deleteWitnessesQuery = "DELETE FROM incident_witnesses WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($deleteWitnessesQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        error_log("Deleted " . $stmt->affected_rows . " witnesses");
                        
                        // Delete violations
                        $deleteViolationsQuery = "DELETE FROM student_violations WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($deleteViolationsQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        error_log("Deleted " . $stmt->affected_rows . " violations");
                        
                        // Delete report
                        $deleteReportQuery = "DELETE FROM incident_reports WHERE id = ?";
                        $stmt = $connection->prepare($deleteReportQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        error_log("Deleted report {$reportId}");
                        
                        $successCount++;
                    } catch (Exception $e) {
                        error_log("Error during deletion: " . $e->getMessage());
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }
            
            $connection->commit();
            
            if ($successCount > 0) {
                $_SESSION['success'] = "Successfully archived $successCount reports. $errorCount reports failed.";
                header("Location: archive_reports.php?alert=success");
            } else if ($errorCount > 0) {
                $_SESSION['error'] = "Failed to archive $errorCount reports.";
                header("Location: archive_reports.php?alert=error");
            } else {
                $_SESSION['info'] = "Students marked as 'Graduated'. No eligible reports were found for archiving.";
                header("Location: archive_reports.php?alert=info");
            }
            
        } catch (Exception $e) {
            $connection->rollback();
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            header("Location: archive_reports.php?alert=error");
        }
    } else {
        $_SESSION['error'] = "Could not open the uploaded file";
        header("Location: archive_reports.php?alert=error");
    }
    
    exit();
}

// If not a POST request or no file uploaded
header("Location: archive_reports.php");
exit();
?>