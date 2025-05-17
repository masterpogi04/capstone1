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
            error_log("Starting archive process for " . count($studentNumbers) . " students");
            
            // First, identify which students have ALL their reports settled
            $placeholders = implode(',', array_fill(0, count($studentNumbers), '?'));
            $types = str_repeat('s', count($studentNumbers));
            
            // Get students who have ALL their reports settled
            $settledStudentsQuery = "SELECT DISTINCT sv.student_id
                                   FROM student_violations sv
                                   WHERE sv.student_id IN ($placeholders)
                                   AND NOT EXISTS (
                                       SELECT 1 FROM student_violations sv2
                                       JOIN incident_reports ir ON sv2.incident_report_id = ir.id
                                       WHERE sv2.student_id = sv.student_id
                                       AND ir.status != 'settled'
                                   )";
            
            $stmt = $connection->prepare($settledStudentsQuery);
            $stmt->bind_param($types, ...$studentNumbers);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $settledStudents = [];
            while ($row = $result->fetch_assoc()) {
                $settledStudents[] = $row['student_id'];
            }
            
            error_log("Found " . count($settledStudents) . " students with all reports settled");
            
            // Only mark students as graduated if ALL their reports are settled
            if (!empty($settledStudents)) {
                $placeholders = implode(',', array_fill(0, count($settledStudents), '?'));
                $types = str_repeat('s', count($settledStudents));
                
                $updateViolationsQuery = "UPDATE student_violations 
                                         SET student_course = 'Graduated', 
                                             student_year_level = 'Graduated', 
                                             section_name = 'Graduated'
                                         WHERE student_id IN ($placeholders)";
                $stmt = $connection->prepare($updateViolationsQuery);
                $stmt->bind_param($types, ...$settledStudents);
                $stmt->execute();
                error_log("Marked " . $stmt->affected_rows . " violations as Graduated");
            }
            
            // Find all reports where ALL involved students are in $settledStudents
            // AND all violators in the report are marked as Graduated
            // AND the report itself is settled
            $archiveQuery = "SELECT DISTINCT ir.id 
                             FROM incident_reports ir
                             WHERE ir.status = 'settled'
                             AND NOT EXISTS (
                                 SELECT 1 FROM student_violations sv 
                                 WHERE sv.incident_report_id = ir.id 
                                 AND sv.student_course != 'Graduated'
                             )
                             AND NOT EXISTS (
                                 -- Check if any student in this report has other non-settled reports
                                 SELECT 1 FROM student_violations sv2
                                 JOIN incident_reports ir2 ON sv2.incident_report_id = ir2.id
                                 WHERE sv2.student_id IN (
                                     SELECT student_id FROM student_violations WHERE incident_report_id = ir.id
                                 )
                                 AND ir2.status != 'settled'
                             )";
            
            $stmt = $connection->prepare($archiveQuery);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $reportsToArchive = [];
            while ($row = $result->fetch_assoc()) {
                $reportsToArchive[] = $row['id'];
            }
            
            error_log("Reports to archive: " . count($reportsToArchive));
            
            // Now archive each eligible report
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
                    
                    if (preg_match('/(.*-)(\d+)$/', $reportId, $matches)) {
                        $prefix = $matches[1];
                        $number = intval($matches[2]);
                        
                        for ($i = 1; $i <= 100; $i++) {
                            $candidateNumber = $number + $i;
                            $paddedNumber = str_pad($candidateNumber, strlen($matches[2]), '0', STR_PAD_LEFT);
                            $candidateId = $prefix . $paddedNumber;
                            
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
                    
                    // Archive with new ID
                    $getColumnsQuery = "SHOW COLUMNS FROM incident_reports";
                    $result = $connection->query($getColumnsQuery);
                    $columns = [];
                    while ($row = $result->fetch_assoc()) {
                        if ($row['Field'] !== 'id') {
                            $columns[] = $row['Field'];
                        }
                    }
                    
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
                    
                    if ($archiveSuccess) {
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
                    
                    if ($archiveSuccess) {
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
                    $archiveReportQuery = "INSERT INTO archive_incident_reports 
                                          SELECT * FROM incident_reports WHERE id = ?";
                    
                    $stmt = $connection->prepare($archiveReportQuery);
                    $stmt->bind_param("s", $reportId);
                    if (!$stmt->execute()) {
                        error_log("Error archiving report: " . $connection->error);
                        $archiveSuccess = false;
                    }
                    
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
                        $deleteWitnessesQuery = "DELETE FROM incident_witnesses WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($deleteWitnessesQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        
                        $deleteViolationsQuery = "DELETE FROM student_violations WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($deleteViolationsQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        
                        $deleteReportQuery = "DELETE FROM incident_reports WHERE id = ?";
                        $stmt = $connection->prepare($deleteReportQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        
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
                $_SESSION['info'] = count($settledStudents) . " students had all reports settled and were marked as Graduated. No eligible reports were found for archiving.";
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