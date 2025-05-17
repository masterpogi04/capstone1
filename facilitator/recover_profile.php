<?php
session_start();
include '../db.php';

// Set up error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Recovery process started at " . date('Y-m-d H:i:s'));

// Basic security check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    error_log("Session validation failed - user not logged in");
    exit;
}

// Validate request
if (!isset($_POST['action']) || $_POST['action'] !== 'recover_students_csv') {
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

// Get existing archived student IDs from database
$existingArchivedIds = [];
$stmt = $connection->prepare("SELECT student_id FROM archive_student_profiles");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existingArchivedIds[$row['student_id']] = true;
}
$stmt->close();

error_log("Found " . count($existingArchivedIds) . " archived student IDs in database");

// Match found IDs with existing archived IDs
$matchingIds = [];
foreach ($studentIds as $id) {
    if (isset($existingArchivedIds[$id])) {
        $matchingIds[] = $id;
        error_log("Matched archived student ID: $id");
    } else {
        error_log("Student ID not found in archive database: $id");
    }
}

if (empty($matchingIds)) {
    echo json_encode([
        'success' => false, 
        'message' => 'No matching student IDs found in archive database',
        'ids_found_in_csv' => $studentIds
    ]);
    error_log("No matching student IDs found in archive database");
    exit;
}

// Start database transaction
$connection->begin_transaction();
$recovered = 0;
$failed = 0;
$errors = [];

try {
    // Get all column names EXCEPT is_archived from the archive table to ensure proper column matching
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
        error_log("Processing archived student ID: $studentId");
        
        // Check if already exists in student_profiles
        $checkStmt = $connection->prepare("SELECT COUNT(*) FROM student_profiles WHERE student_id = ?");
        $checkStmt->bind_param("s", $studentId);
        $checkStmt->execute();
        $checkStmt->bind_result($alreadyExists);
        $checkStmt->fetch();
        $checkStmt->close();
        
        if ($alreadyExists) {
            $failed++;
            $errors[] = "Student ID $studentId already exists in student profiles";
            error_log("Student ID $studentId already exists in student profiles - skipping");
            continue;
        }
        
        // Get archived student data
        $getDataStmt = $connection->prepare("SELECT * FROM archive_student_profiles WHERE student_id = ?");
        $getDataStmt->bind_param("s", $studentId);
        $getDataStmt->execute();
        $studentData = $getDataStmt->get_result()->fetch_assoc();
        $getDataStmt->close();
        
        if (!$studentData) {
            $failed++;
            $errors[] = "Archived student ID $studentId exists but data couldn't be retrieved";
            error_log("Failed to retrieve data for archived student ID $studentId");
            continue;
        }
        
        error_log("Found archived student data for ID $studentId: " . json_encode(array_slice($studentData, 0, 5))); // Log first 5 fields
        
        // Insert into student_profiles table - using explicit column names
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
            $failed++;
            $errors[] = "Failed to insert student ID $studentId: " . $errorMsg;
            error_log("Failed to insert student ID $studentId into student_profiles: " . $errorMsg);
            continue;
        }
        
        error_log("Successfully inserted student ID $studentId into student_profiles");
        
        // Delete from archive table
        $deleteQuery = $connection->prepare("DELETE FROM archive_student_profiles WHERE student_id = ?");
        $deleteQuery->bind_param("s", $studentId);
        $deleteSuccess = $deleteQuery->execute();
        $errorMsg = $deleteQuery->error;
        $deleteQuery->close();
        
        if (!$deleteSuccess) {
            $failed++;
            $errors[] = "Failed to delete student ID $studentId from archive table: " . $errorMsg;
            error_log("Failed to delete student ID $studentId from archive_student_profiles: " . $errorMsg);
            
            // Rollback this specific insert if delete failed
            $rollbackQuery = $connection->prepare("DELETE FROM student_profiles WHERE student_id = ?");
            $rollbackQuery->bind_param("s", $studentId);
            $rollbackQuery->execute();
            $rollbackQuery->close();
            error_log("Rolled back recovery insertion for student ID $studentId");
        } else {
            $recovered++;
            error_log("Successfully recovered student ID $studentId");
        }
    }
    
    // Complete transaction
    if ($recovered > 0) {
        $connection->commit();
        error_log("Transaction committed: $recovered students recovered, $failed failed");
        echo json_encode([
            'success' => true,
            'recovered_count' => $recovered,
            'message' => "Successfully recovered $recovered student profiles" . 
                        ($failed > 0 ? ". $failed profiles could not be recovered." : ""),
            'errors' => $errors
        ]);
    } else {
        $connection->rollback();
        error_log("Transaction rolled back: No students successfully recovered");
        echo json_encode([
            'success' => false,
            'message' => "No student profiles were recovered. No matching profiles found in archive.",
            'errors' => $errors
        ]); 
    }
    
} catch (Exception $e) {
    $connection->rollback();
    error_log("Exception during recovery: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'errors' => $errors
    ]);
}

error_log("Recovery process completed at " . date('Y-m-d H:i:s'));
?>