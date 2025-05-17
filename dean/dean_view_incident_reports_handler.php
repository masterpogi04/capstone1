<?php
session_start();
include '../db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: ../login.php");
    exit();
}

// Main query for displaying reports remains the same
// Main query
$query = "SELECT p.*, 
          CONCAT(g.first_name, ' ', COALESCE(g.middle_initial, ''), ' ', g.last_name) AS guard_name,
          GROUP_CONCAT(DISTINCT CONCAT(
              COALESCE(psv.student_id, 'NULL'), ':', 
              psv.student_name, ':', 
              COALESCE(psv.student_course, ''), ':', 
              COALESCE(psv.student_year_level, ''), ':', 
              COALESCE(psv.section_name, '')
          ) SEPARATOR '|') AS involved_students,
          GROUP_CONCAT(DISTINCT CONCAT(
              piw.witness_type, ':', 
              COALESCE(piw.witness_id, 'NULL'), ':', 
              piw.witness_name, ':', 
              COALESCE(piw.witness_course, ''), ':',
              COALESCE(piw.witness_year_level, ''), ':',
              COALESCE(piw.section_name, '')
          ) SEPARATOR '|') AS witnesses
          FROM pending_incident_reports p
          LEFT JOIN tbl_guard g ON p.guard_id = g.id
          LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
          LEFT JOIN pending_incident_witnesses piw ON p.id = piw.pending_report_id
          WHERE p.status = 'Pending'
          GROUP BY p.id
          ORDER BY p.created_at DESC";
$result = $connection->query($query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_id = $_POST['report_id'];
    
    try {
        $response = handleEscalation($connection, $report_id);
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("Error in incident reports handler: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    exit();
}

function handleEscalation($connection, $report_id) {
    $connection->begin_transaction();
    
    try {
        // Generate new report ID
        $new_id = generateIncidentReportId($connection);
        
        // Move report to final tables
        if (!moveReportToFinal($connection, $report_id, $new_id)) {
            throw new Exception("Failed to move report to final tables");
        }

        // Update pending report status
        $stmt = $connection->prepare("UPDATE pending_incident_reports SET status = 'Escalated' WHERE id = ?");
        $stmt->bind_param("i", $report_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update report status");
        }
        $stmt->close();

        // Get guard information
       $query = "SELECT g.email, 
                 CONCAT(g.first_name, ' ', COALESCE(g.middle_initial, ''), ' ', g.last_name) AS guard_name,
                 p.date_reported, g.id AS guard_id
                 FROM pending_incident_reports p
                 JOIN tbl_guard g ON p.guard_id = g.id
                 WHERE p.id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $guard_data = $result->fetch_assoc();
        $stmt->close();

        if (!$guard_data) {
            throw new Exception("Guard data not found");
        }

       // Add notification for the guard
        $message = "Your Incident Report submitted on " . date('F j, Y', strtotime($guard_data['date_reported'])) . 
                  " has been escalated to CEIT Guidance Facilitator by the Dean. New report ID: $new_id";
        $stmt = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link, is_read, created_at) 
                                    VALUES ('guard', ?, ?, 'view_submitted_incident_reports_guard.php', 0, NOW())");
        $stmt->bind_param("is", $guard_data['guard_id'], $message);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert notification");
        }
        $stmt->close();

        // Add notification for all facilitators
        $facilitator_message = "Escalated incident report from CEIT Dean's office. Report ID: $new_id";
        $facilitator_link = "view_facilitator_incident_reports.php?id=" . $new_id;
        
        $notify_facilitators = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link, is_read, created_at) 
                                                   SELECT 'facilitator', id, ?, ?, 0, NOW() 
                                                   FROM tbl_facilitator 
                                                   WHERE status = 'active'");
        $notify_facilitators->bind_param("ss", $facilitator_message, $facilitator_link);
        if (!$notify_facilitators->execute()) {
            throw new Exception("Failed to insert facilitator notifications");
        }
        $notify_facilitators->close();

        $connection->commit();

        // Attempt to send email notification
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ceitguidanceoffice@gmail.com';
            $mail->Password = 'qapb ebhc owts ioel';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('ceitguidanceoffice@gmail.com', 'CEIT Guidance Office');
            $mail->addAddress($guard_data['email'], $guard_data['guard_name']);
            $mail->isHTML(true);
            $mail->Subject = "Incident Report Escalated";
            $mail->Body = $message;
            
            $mail_sent = $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            $mail_sent = false;
        }

        return [
            'status' => 'success',
            'message' => 'Report escalated to facilitator' . (!$mail_sent ? ' but notification email could not be sent' : '')
        ];

    } catch (Exception $e) {
        $connection->rollback();
        throw $e;
    }
}

function moveReportToFinal($connection, $report_id, $new_id) {
    try {
        // First, fetch and insert the main report
        $report_query = "SELECT * FROM pending_incident_reports WHERE id = ?";
        $stmt = $connection->prepare($report_query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Insert into incident_reports
        $stmt = $connection->prepare("INSERT INTO incident_reports (id, date_reported, place, description, reported_by, reporters_id, file_path, status, reported_by_type)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("ssssssss", $new_id, $report['date_reported'], $report['place'], $report['description'], 
                         $report['reported_by'], $report['guard_id'], $report['file_path'], $report['reported_by_type']);
        $stmt->execute();
        $stmt->close();

        // Get student violations
        $violation_query = "SELECT * FROM pending_student_violations WHERE pending_report_id = ?";
        $stmt = $connection->prepare($violation_query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $violations_result = $stmt->get_result();
        $stmt->close();

        // Insert student violations
        $violation_stmt = $connection->prepare("INSERT INTO student_violations 
            (student_id, incident_report_id, violation_date, status, student_name, student_course, student_year_level, section_id, section_name, adviser_id, adviser_name) 
            VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?)");

        while ($violation = $violations_result->fetch_assoc()) {
            // Use only data from pending tables
            $student_name = trim($violation['student_name']);
            $student_id = $violation['student_id'];
            $student_course = $violation['student_course'];
            $student_year_level = $violation['student_year_level'];

            // Validation
            if (empty($student_name)) {
                throw new Exception("Student name cannot be empty. Original data: " . json_encode($violation));
            }

            // Disable trigger to prevent overwriting
            $connection->query("DROP TRIGGER IF EXISTS before_student_violation_insert");
            
            $violation_stmt->bind_param("ssssssisis", 
                $student_id,
                $new_id,
                $report['date_reported'],
                $student_name,
                $student_course,
                $student_year_level,
                $violation['section_id'],
                $violation['section_name'],
                $violation['adviser_id'],
                $violation['adviser_name']
            );

            if (!$violation_stmt->execute()) {
                throw new Exception("Failed to insert violation: " . $violation_stmt->error);
            }
            
            // Restore trigger with a simpler version that doesn't overwrite our fields
            $connection->query("
            CREATE TRIGGER before_student_violation_insert BEFORE INSERT ON student_violations
            FOR EACH ROW
            BEGIN
                -- Only set certain fields if they're empty and student_id exists
                IF NEW.student_id IS NOT NULL AND (NEW.student_name IS NULL OR NEW.student_name = '') THEN
                    SELECT CONCAT(ts.first_name, ' ', ts.last_name)
                    INTO @student_fullname
                    FROM tbl_student ts
                    WHERE ts.student_id = NEW.student_id;
                    
                    SET NEW.student_name = @student_fullname;
                END IF;
            END
            ");
        }
        $violation_stmt->close();

        // Get witnesses
        $stmt = $connection->prepare("SELECT * FROM pending_incident_witnesses WHERE pending_report_id = ?");
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $witnesses_result = $stmt->get_result();
        $stmt->close();

        // Insert witnesses
        $witness_stmt = $connection->prepare("INSERT INTO incident_witnesses 
            (incident_report_id, witness_type, witness_id, witness_name, 
            witness_student_name, witness_course, witness_year_level, witness_email,
            section_id, section_name, adviser_id, adviser_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        while ($witness = $witnesses_result->fetch_assoc()) {
            // Use only data from pending tables
            $witness_id = $witness['witness_id'];
            $witness_name = $witness['witness_name'];
            $witness_course = $witness['witness_course'];
            $witness_year_level = $witness['witness_year_level'];
            $witness_email = $witness['witness_email'];
            
            $witness_stmt->bind_param("ssssssssisis", 
                $new_id,
                $witness['witness_type'],
                $witness_id,
                $witness_name,
                $witness_name, // witness_student_name is same as witness_name
                $witness_course,
                $witness_year_level,
                $witness_email,
                $witness['section_id'],
                $witness['section_name'],
                $witness['adviser_id'],
                $witness['adviser_name']
            );
            
            if (!$witness_stmt->execute()) {
                throw new Exception("Failed to insert into incident_witnesses: " . $witness_stmt->error);
            }
        }
        $witness_stmt->close();
        return true;

    } catch (Exception $e) {
        error_log("Error in moveReportToFinal: " . $e->getMessage());
        throw $e;
    }
}

function generateIncidentReportId($connection) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    $academicYear = ($currentMonth >= 9) ? $currentYear : $currentYear - 1;
    $nextYear = $academicYear + 1;
    $academicYearShort = substr($academicYear, 2) . '-' . substr($nextYear, 2);

    // Generate cryptographically secure random 8-digit number
    $maxAttempts = 5; // Safety limit for recursion
    return attemptGenerateId($connection, $academicYearShort, $maxAttempts);
}

function attemptGenerateId($connection, $academicYearShort, $attemptsLeft) {
    if ($attemptsLeft <= 0) {
        throw new Exception("Failed to generate unique ID after multiple attempts");
    }

    // Generate 8-digit random number (00000000 to 99999999)
    $randomNumber = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);

    $id = "CEIT-{$academicYearShort}-{$randomNumber}";

    // Check for duplicates
    $query = "SELECT id FROM incident_reports WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Extremely rare case - try again
        return attemptGenerateId($connection, $academicYearShort, $attemptsLeft - 1);
    }

    return $id;
}

function getStudentDetails($connection, $student_id) {
    // Debug output
    error_log("Attempting to get details for student ID: " . $student_id);
    
    // Modified query to match your database structure
    $query = "SELECT 
                ts.first_name,
                ts.last_name,
                c.name AS course_name,
                s.year_level,
                s.section_no,
                CONCAT(a.first_name, ' ', a.last_name) AS adviser_name
              FROM tbl_student ts
              LEFT JOIN sections s ON ts.section_id = s.id
              LEFT JOIN courses c ON s.course_id = c.id
              LEFT JOIN tbl_adviser a ON s.adviser_id = a.id
              WHERE ts.student_id = ?";
    
    // Debug the query
    error_log("Query: " . $query);
    
    $stmt = $connection->prepare($query);
    
    // Check if prepare was successful
    if ($stmt === false) {
        error_log("Prepare failed: " . $connection->error);
        return null;
    }
    
    $stmt->bind_param("s", $student_id);
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return null;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $student['year_and_section'] = $student['year_level'] . " - Section " . $student['section_no'];
        $stmt->close();
        return $student;
    }
    
    $stmt->close();
    return null;
}

/**
 * Converts a name to proper case with special handling
 * for middle initials and name prefixes
 */
function toProperCase($name) {
    // Handle empty names
    if (empty($name)) {
        return $name;
    }
    
    // Convert to lowercase first
    $name = strtolower($name);
    
    // Split by spaces
    $nameParts = explode(' ', $name);
    foreach ($nameParts as &$part) {
        if (empty($part)) continue;
        
        // Handle special cases for middle initials like "C." or "C"
        if (strlen($part) == 1 || (strlen($part) == 2 && $part[1] == '.')) {
            $part = strtoupper($part[0]) . (strlen($part) == 2 ? '.' : '');
            continue;
        }
        
        // Handle prefixes like Mc/Mac, de, etc.
        if (strpos($part, 'mc') === 0) {
            $part = 'Mc' . ucfirst(substr($part, 2));
            continue;
        }
        
        if (strpos($part, 'mac') === 0) {
            $part = 'Mac' . ucfirst(substr($part, 3));
            continue;
        }
        
        if (in_array($part, ['de', 'la', 'del', 'los', 'san', 'santa'])) {
            $part = $part; // Keep lowercase for these prefixes
            continue;
        }
        
        // Regular ucfirst for most parts
        $part = ucfirst($part);
        
        // Handle apostrophe names like O'Reilly
        if (strpos($part, '\'') !== false) {
            $subParts = explode('\'', $part);
            if (isset($subParts[1]) && !empty($subParts[1])) {
                $subParts[1] = ucfirst($subParts[1]);
                $part = implode('\'', $subParts);
            }
        }
    }
    
    return implode(' ', $nameParts);
}

function formatStudentDisplay($student_data, $connection) {
    // Split the data into components
    $parts = explode(':', $student_data);
    $student_id = $parts[0];
    $student_name = $parts[1];
    $student_course = isset($parts[2]) && !empty($parts[2]) ? $parts[2] : '';
    $student_year_level = isset($parts[3]) && !empty($parts[3]) ? $parts[3] : '';
    $section_name = isset($parts[4]) && !empty($parts[4]) ? $parts[4] : '';
    
    // Format the name using our proper case function
    $display = toProperCase($student_name);
    
    // If student_id is NULL, mark as External Entity
    if ($student_id === 'NULL' || empty($student_id)) {
        return $display . " (Non - CEIT Student)";
    }

    if (empty($student_course) && empty($student_year_level) && empty($section_name)) {
        return $display . " (Non - CEIT Student)";
    }

    // Build a simple display with course and year level
    $details = [];
    
    if (!empty($student_course)) {
        $details[] = $student_course;
    }
    
    if (!empty($student_year_level)) {
        $details[] = $student_year_level;
    }
    
    if (!empty($section_name) && !str_contains($section_name, $student_year_level)) {
        // Only add section if it doesn't already contain the year level
        $details[] = $section_name;
    }
    
    if (!empty($details)) {
        return $display . " (" . implode(" - ", $details) . ")";
    }

    // Default fallback
    return $display;
}

function formatWitnessDisplay($witness_data) {
    // Split the data into components
    $parts = explode(':', $witness_data);
    $witness_type = $parts[0] ?? '';
    $witness_id = $parts[1] ?? '';
    $witness_name = $parts[2] ?? '';
    $witness_course = isset($parts[3]) && !empty($parts[3]) ? $parts[3] : '';
    $witness_year_level = isset($parts[4]) && !empty($parts[4]) ? $parts[4] : '';
    $section_name = isset($parts[5]) && !empty($parts[5]) ? $parts[5] : '';
    
    // Format the name using our proper case function
    $display = toProperCase($witness_name);
    
    if ($witness_type === 'student') {
        // Build details for student witnesses
        $details = [];
        
        if (!empty($witness_course)) {
            $details[] = $witness_course;
        }
        
        if (!empty($witness_year_level)) {
            $details[] = $witness_year_level;
        }
        
        if (!empty($section_name) && !str_contains($section_name, $witness_year_level)) {
            // Only add section if it doesn't already contain the year level
            $details[] = $section_name;
        }

        if (empty($student_course) && empty($student_year_level) && empty($section_name)) {
            return $display . " (Non - CEIT Student)";
        }
        
        if (!empty($details)) {
            return $display . " (" . implode(" - ", $details) . ")";
        } else if ($witness_id === 'NULL' || empty($witness_id)) {
            return $display . " (Non - CEIT Student)";
        } else {
            return $display . " (Student)";
        }
    } else if ($witness_type === 'staff') {
        return $display . " (Staff)";
    } else {
        return $display;
    }
}
?>