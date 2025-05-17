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
        $graduatedCount = 0;
        
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
            
            // First, backup all reports involving these students (regardless of status)
            $placeholders = implode(',', array_fill(0, count($studentNumbers), '?'));
            $types = str_repeat('s', count($studentNumbers));
            
            // Backup all reports where these students are involved as violators
            $reportQuery = "SELECT DISTINCT ir.id 
                          FROM incident_reports ir
                          JOIN student_violations sv ON sv.incident_report_id = ir.id
                          WHERE sv.student_id IN ($placeholders)";
            
            $stmt = $connection->prepare($reportQuery);
            $stmt->bind_param($types, ...$studentNumbers);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $allReports = [];
            while ($row = $result->fetch_assoc()) {
                $allReports[] = $row['id'];
            }
            
            error_log("Found " . count($allReports) . " reports involving these students");
            
            // Backup all reports and their related data
            foreach ($allReports as $reportId) {
                // Backup incident report if not already backed up
                $checkQuery = "SELECT id FROM backup_incident_reports WHERE id = ?";
                $checkStmt = $connection->prepare($checkQuery);
                $checkStmt->bind_param("s", $reportId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows == 0) {
                    $backupReportQuery = "INSERT INTO backup_incident_reports SELECT * FROM incident_reports WHERE id = ?";
                    $stmt = $connection->prepare($backupReportQuery);
                    $stmt->bind_param("s", $reportId);
                    if (!$stmt->execute()) {
                        error_log("Error backing up report {$reportId}: " . $connection->error);
                    }
                }
                
                // Backup student violations
                $getViolationsQuery = "SELECT id FROM student_violations WHERE incident_report_id = ?";
                $stmt = $connection->prepare($getViolationsQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $violationsResult = $stmt->get_result();
                
                while ($violation = $violationsResult->fetch_assoc()) {
                    $checkQuery = "SELECT id FROM backup_student_violations WHERE id = ?";
                    $checkStmt = $connection->prepare($checkQuery);
                    $checkStmt->bind_param("s", $violation['id']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows == 0) {
                        $backupViolationQuery = "INSERT INTO backup_student_violations SELECT * FROM student_violations WHERE id = ?";
                        $stmt = $connection->prepare($backupViolationQuery);
                        $stmt->bind_param("s", $violation['id']);
                        if (!$stmt->execute()) {
                            error_log("Error backing up violation {$violation['id']}: " . $connection->error);
                        }
                    }
                }
                
                // Backup witnesses
                $getWitnessesQuery = "SELECT id FROM incident_witnesses WHERE incident_report_id = ?";
                $stmt = $connection->prepare($getWitnessesQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $witnessesResult = $stmt->get_result();
                
                while ($witness = $witnessesResult->fetch_assoc()) {
                    $checkQuery = "SELECT id FROM backup_incident_witnesses WHERE id = ?";
                    $checkStmt = $connection->prepare($checkQuery);
                    $checkStmt->bind_param("s", $witness['id']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows == 0) {
                        $backupWitnessQuery = "INSERT INTO backup_incident_witnesses SELECT * FROM incident_witnesses WHERE id = ?";
                        $stmt = $connection->prepare($backupWitnessQuery);
                        $stmt->bind_param("s", $witness['id']);
                        if (!$stmt->execute()) {
                            error_log("Error backing up witness {$witness['id']}: " . $connection->error);
                        }
                    }
                }
            }
            
            // Identify students with ALL reports settled or referred (no pending cases)
            $settledStudentsQuery = "SELECT DISTINCT sv.student_id
                                   FROM student_violations sv
                                   WHERE sv.student_id IN ($placeholders)
                                   AND NOT EXISTS (
                                       SELECT 1 FROM student_violations sv2
                                       JOIN incident_reports ir ON sv2.incident_report_id = ir.id
                                       WHERE sv2.student_id = sv.student_id
                                       AND ir.status NOT IN ('settled', 'referred')
                                   )";
            
            $stmt = $connection->prepare($settledStudentsQuery);
            $stmt->bind_param($types, ...$studentNumbers);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $settledStudents = [];
            while ($row = $result->fetch_assoc()) {
                $settledStudents[] = $row['student_id'];
            }
            
            $graduatedCount = count($settledStudents);
            error_log("Found " . $graduatedCount . " students with all reports settled/referred");
            
            // Only mark students as graduated if ALL their reports are settled or referred
            if ($graduatedCount > 0) {
                $placeholders = implode(',', array_fill(0, $graduatedCount, '?'));
                $types = str_repeat('s', $graduatedCount);
                
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
            
            // Find reports eligible for archiving:
            // 1. Report status is settled or referred
            // 2. All violators are marked as Graduated
            // 3. No involved students have any other reports that aren't settled/referred
            $archiveQuery = "SELECT DISTINCT ir.id 
                             FROM incident_reports ir
                             WHERE ir.status IN ('settled', 'referred')
                             AND NOT EXISTS (
                                 SELECT 1 FROM student_violations sv 
                                 WHERE sv.incident_report_id = ir.id 
                                 AND sv.student_course != 'Graduated'
                             )
                             AND NOT EXISTS (
                                 SELECT 1 FROM student_violations sv2
                                 JOIN incident_reports ir2 ON sv2.incident_report_id = ir2.id
                                 WHERE sv2.student_id IN (
                                     SELECT student_id FROM student_violations WHERE incident_report_id = ir.id
                                 )
                                 AND ir2.status NOT IN ('settled', 'referred')
                             )";
            
            $stmt = $connection->prepare($archiveQuery);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $reportsToArchive = [];
            while ($row = $result->fetch_assoc()) {
                $reportsToArchive[] = $row['id'];
            }
            
            error_log("Reports to archive: " . count($reportsToArchive));
            
            // Archive eligible reports
            foreach ($reportsToArchive as $reportId) {
                $archiveSuccess = true;
                
                // Check if report already exists in archive
                $checkQuery = "SELECT id FROM archive_incident_reports WHERE id = ?";
                $checkStmt = $connection->prepare($checkQuery);
                $checkStmt->bind_param("s", $reportId);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // Generate new ID if report already exists in archive
                    $newId = null;
                    
                    if (preg_match('/(.*-)(\d+)$/', $reportId, $matches)) {
                        $prefix = $matches[1];
                        $number = intval($matches[2]);
                        
                        for ($i = 1; $i <= 100; $i++) {
                            $candidateId = $prefix . str_pad($number + $i, strlen($matches[2]), '0', STR_PAD_LEFT);
                            
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
                        $errorCount++;
                        continue;
                    }
                    
                    // Archive with new ID
                    $columnQuery = "SHOW COLUMNS FROM incident_reports";
                    $result = $connection->query($columnQuery);
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
                        $archiveSuccess = false;
                    }
                    
                    // Archive violations with new report ID
                    if ($archiveSuccess) {
                        $columnQuery = "SHOW COLUMNS FROM student_violations";
                        $result = $connection->query($columnQuery);
                        $columns = [];
                        $nonIdColumns = [];
                        while ($row = $result->fetch_assoc()) {
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
                            $archiveSuccess = false;
                        }
                    }
                    
                    // Archive witnesses with new report ID
                    if ($archiveSuccess) {
                        $columnQuery = "SHOW COLUMNS FROM incident_witnesses";
                        $result = $connection->query($columnQuery);
                        $columns = [];
                        $nonIdColumns = [];
                        while ($row = $result->fetch_assoc()) {
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
                            $archiveSuccess = false;
                        }
                    }
                } else {
                    // Archive with original ID
                    $archiveReportQuery = "INSERT INTO archive_incident_reports SELECT * FROM incident_reports WHERE id = ?";
                    $stmt = $connection->prepare($archiveReportQuery);
                    $stmt->bind_param("s", $reportId);
                    if (!$stmt->execute()) {
                        $archiveSuccess = false;
                    }
                    
                    if ($archiveSuccess) {
                        $archiveViolationsQuery = "INSERT INTO archive_student_violations SELECT * FROM student_violations WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($archiveViolationsQuery);
                        $stmt->bind_param("s", $reportId);
                        if (!$stmt->execute()) {
                            $archiveSuccess = false;
                        }
                    }
                    
                    if ($archiveSuccess) {
                        $archiveWitnessesQuery = "INSERT INTO archive_incident_witnesses SELECT * FROM incident_witnesses WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($archiveWitnessesQuery);
                        $stmt->bind_param("s", $reportId);
                        if (!$stmt->execute()) {
                            $archiveSuccess = false;
                        }
                    }
                }
                
                // Delete from original tables if archive was successful
                if ($archiveSuccess) {
                    try {
                        // Delete witnesses first
                        $deleteWitnessesQuery = "DELETE FROM incident_witnesses WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($deleteWitnessesQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        
                        // Delete violations
                        $deleteViolationsQuery = "DELETE FROM student_violations WHERE incident_report_id = ?";
                        $stmt = $connection->prepare($deleteViolationsQuery);
                        $stmt->bind_param("s", $reportId);
                        $stmt->execute();
                        
                        // Delete report
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
            
            // Set appropriate session message based on results
            if ($successCount > 0) {
                $message = "Successfully archived $successCount reports. ";
                if ($errorCount > 0) {
                    $message .= "$errorCount reports failed to archive. ";
                }
                $message .= "$graduatedCount students were marked as Graduated. All data was backed up.";
                $_SESSION['success'] = $message;
                header("Location: archive_reports.php?alert=success");
            } else if ($graduatedCount > 0) {
                $_SESSION['info'] = "$graduatedCount students were marked as Graduated but no reports were archived. All data was backed up.";
                header("Location: archive_reports.php?alert=info");
            } else {
                $_SESSION['info'] = "No students met the criteria for graduation (all reports settled/referred). No reports were archived. All data was backed up.";
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