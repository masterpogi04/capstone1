<?php
session_start();
include '../db.php';

// Set up error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Archive process started at " . date('Y-m-d H:i:s'));

// Basic security check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    error_log("Session validation failed - user not logged in");
    exit;
}

// Validate request
if (!isset($_POST['action']) || $_POST['action'] !== 'archive_students_csv') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    error_log("Invalid request - action parameter missing or incorrect");
    exit;
}

// Validate file upload
if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
    $errorCode = isset($_FILES['csvFile']) ? $_FILES['csvFile']['error'] : 'No file';
    echo json_encode(['success' => false, 'message' => 'File upload error: ' . $errorCode]);
    error_log("File upload error: " . $errorCode);
    exit;
}

// Check file type
if (pathinfo($_FILES['csvFile']['name'], PATHINFO_EXTENSION) !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Please upload a CSV file']);
    error_log("File type error - not a CSV file: " . $_FILES['csvFile']['name']);
    exit;
}

// Log file details
error_log("Processing CSV file: " . $_FILES['csvFile']['name'] . ", size: " . $_FILES['csvFile']['size']);

// Get file content for advanced processing
$fileContent = file_get_contents($_FILES['csvFile']['tmp_name']);

// Remove BOM if present
$bom = pack('H*', 'EFBBBF');
$fileContent = preg_replace("/^$bom/", '', $fileContent);

// Log first 100 characters of file (for debugging)
error_log("File content starts with: " . substr(bin2hex($fileContent), 0, 100));

// Extract numeric sequences that could be student IDs
$studentIds = [];
preg_match_all('/\b\d{6,12}\b/', $fileContent, $matches);

if (!empty($matches[0])) {
    foreach ($matches[0] as $match) {
        $studentIds[] = trim($match);
    }
    error_log("Found " . count($studentIds) . " potential student IDs using regex");
}

// If no IDs found by regex, try CSV parsing with multiple approaches
if (empty($studentIds)) {
    error_log("No IDs found with regex, trying CSV parsing approaches");
    
    // Approach 1: Try standard fgetcsv with comma
    if (($handle = fopen($_FILES['csvFile']['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            foreach ($data as $cell) {
                $cell = trim($cell);
                if (preg_match('/^\d{6,12}$/', $cell)) {
                    $studentIds[] = $cell;
                    error_log("Found ID via comma CSV: $cell");
                }
            }
        }
        fclose($handle);
    }
    
    // Approach 2: Try with semicolon delimiter
    if (empty($studentIds) && ($handle = fopen($_FILES['csvFile']['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            foreach ($data as $cell) {
                $cell = trim($cell);
                if (preg_match('/^\d{6,12}$/', $cell)) {
                    $studentIds[] = $cell;
                    error_log("Found ID via semicolon CSV: $cell");
                }
            }
        }
        fclose($handle);
    }
    
    // Approach 3: Try with tab delimiter
    if (empty($studentIds) && ($handle = fopen($_FILES['csvFile']['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
            foreach ($data as $cell) {
                $cell = trim($cell);
                if (preg_match('/^\d{6,12}$/', $cell)) {
                    $studentIds[] = $cell;
                    error_log("Found ID via tab CSV: $cell");
                }
            }
        }
        fclose($handle);
    }
    
    // Approach 4: Line by line parsing
    if (empty($studentIds)) {
        $lines = explode("\n", $fileContent);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\d{6,12}$/', $line)) {
                $studentIds[] = $line;
                error_log("Found ID via line parsing: $line");
            } elseif (preg_match('/\b(\d{6,12})\b/', $line, $matches)) {
                $studentIds[] = $matches[1];
                error_log("Found ID via line regex: {$matches[1]}");
            }
        }
    }
}

// Remove duplicates
$studentIds = array_unique($studentIds);

if (empty($studentIds)) {
    echo json_encode(['success' => false, 'message' => 'No student IDs found in CSV']);
    error_log("No student IDs found in CSV after all parsing attempts");
    exit;
}

error_log("Final list of student IDs found: " . implode(", ", $studentIds));

// Get existing student IDs from database
$existingIds = [];
$stmt = $connection->prepare("SELECT student_id FROM student_profiles");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existingIds[$row['student_id']] = true;
}
$stmt->close();

error_log("Found " . count($existingIds) . " student IDs in database");

// Match found IDs with existing database IDs
$matchingIds = [];
foreach ($studentIds as $id) {
    if (isset($existingIds[$id])) {
        $matchingIds[] = $id;
        error_log("Matched student ID: $id");
    } else {
        error_log("Student ID not found in database: $id");
    }
}

if (empty($matchingIds)) {
    echo json_encode([
        'success' => false, 
        'message' => 'No matching student IDs found in database',
        'ids_found_in_csv' => $studentIds
    ]);
    error_log("No matching student IDs found in database");
    exit;
}

// Start database transaction
$connection->begin_transaction();
$archived = 0;
$failed = 0;
$errors = [];

try {
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
    
    foreach ($matchingIds as $studentId) {
        error_log("Processing student ID: $studentId");
        
        // Check if already archived
        $archiveCheckStmt = $connection->prepare("SELECT COUNT(*) FROM archive_student_profiles WHERE student_id = ?");
        $archiveCheckStmt->bind_param("s", $studentId);
        $archiveCheckStmt->execute();
        $archiveCheckStmt->bind_result($alreadyArchived);
        $archiveCheckStmt->fetch();
        $archiveCheckStmt->close();
        
        if ($alreadyArchived) {
            $failed++;
            $errors[] = "Student ID $studentId is already archived";
            error_log("Student ID $studentId is already archived - skipping");
            continue;
        }
        
        // Get current student data
        $getDataStmt = $connection->prepare("SELECT * FROM student_profiles WHERE student_id = ?");
        $getDataStmt->bind_param("s", $studentId);
        $getDataStmt->execute();
        $studentData = $getDataStmt->get_result()->fetch_assoc();
        $getDataStmt->close();
        
        if (!$studentData) {
            $failed++;
            $errors[] = "Student ID $studentId exists but data couldn't be retrieved";
            error_log("Failed to retrieve data for student ID $studentId");
            continue;
        }
        
        error_log("Found student data for ID $studentId: " . json_encode(array_slice($studentData, 0, 5))); // Log first 5 fields
        
        // Insert into archive table - CORRECTED APPROACH using explicit column names
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
            $failed++;
            $errors[] = "Failed to insert student ID $studentId: " . $errorMsg;
            error_log("Failed to insert student ID $studentId: " . $errorMsg);
            continue;
        }
        
        error_log("Successfully inserted student ID $studentId into archive_student_profiles");
        
        // Delete from original table
        $deleteQuery = $connection->prepare("DELETE FROM student_profiles WHERE student_id = ?");
        $deleteQuery->bind_param("s", $studentId);
        $deleteSuccess = $deleteQuery->execute();
        $errorMsg = $deleteQuery->error;
        $deleteQuery->close();
        
        if (!$deleteSuccess) {
            $failed++;
            $errors[] = "Failed to delete student ID $studentId from original table: " . $errorMsg;
            error_log("Failed to delete student ID $studentId: " . $errorMsg);
            
            // Rollback this specific insert if delete failed
            $rollbackQuery = $connection->prepare("DELETE FROM archive_student_profiles WHERE student_id = ?");
            $rollbackQuery->bind_param("s", $studentId);
            $rollbackQuery->execute();
            $rollbackQuery->close();
            error_log("Rolled back archive insertion for student ID $studentId");
        } else {
            $archived++;
            error_log("Successfully archived student ID $studentId");
        }
    }
    
    // Complete transaction
    if ($archived > 0) {
        $connection->commit();
        error_log("Transaction committed: $archived students archived, $failed failed");
        echo json_encode([
            'success' => true,
            'archived_count' => $archived,
            'message' => "Successfully archived $archived student profiles" . 
                        ($failed > 0 ? ". $failed profiles could not be archived." : ""),
            'errors' => $errors
        ]);
    } else {
        $connection->rollback();
        error_log("Transaction rolled back: No students successfully archived");
        echo json_encode([
            'success' => false,
            'message' => "No student profiles were archived. No matching profiles found.",
            'errors' => $errors
        ]); 
    }
    
} catch (Exception $e) {
    $connection->rollback();
    error_log("Exception during archiving: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'errors' => $errors
    ]);
}

error_log("Archive process completed at " . date('Y-m-d H:i:s'));
?> 