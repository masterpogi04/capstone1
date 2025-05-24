<?php
session_start();
date_default_timezone_set('Asia/Manila');
include '../db.php';

// Ensure the user is logged in as a guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guard') {
    header("Location: ../login.php");
    exit();  
}

$guard_id = $_SESSION['user_id'];
$reportedByType = $_SESSION['user_type'];

// Generate a new incident report ID at the start
function generateIncidentReportId($connection) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    $academicYear = ($currentMonth >= 9) ? $currentYear : $currentYear - 1;
    $nextYear = $academicYear + 1;
    $academicYearShort = substr($academicYear, 2) . '-' . substr($nextYear, 2);

    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(id, '-', -1) AS UNSIGNED)) as max_seq 
              FROM incident_reports 
              WHERE id LIKE 'CEIT-{$academicYearShort}-%'";
    $result = $connection->query($query);
    if (!$result) {
        die("Error in sequence query: " . $connection->error);
    }
    $row = $result->fetch_assoc();
    $nextSeq = ($row['max_seq'] ?? 0) + 1;

    return sprintf("CEIT-%s-%04d", $academicYearShort, $nextSeq);
}

// Fetch guard's name
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_guard WHERE id = ?");
if (!$stmt) {
    die("Error preparing statement: " . $connection->error);
}

$stmt->bind_param("i", $guard_id);
$stmt->execute();
$result = $stmt->get_result();
$guard = $result->fetch_assoc();

// Check if first_name and last_name exist
if (empty($guard['first_name']) || empty($guard['last_name'])) {
    // Store message in session
    $_SESSION['profile_update_required'] = "Please update your profile with your first name and last name before submitting incident reports.";
    header("Location: guard_myprofile.php");
    exit();
}

// Concatenate name with middle initial
$guard_name = $guard['first_name'];
if (!empty($guard['middle_initial'])) {
    $guard_name .= ' ' . $guard['middle_initial'] . '.';
}
$guard_name .= ' ' . $guard['last_name'];

function handleDuplicateNames(&$namesArray) {
    $nameCounts = [];
    
    foreach ($namesArray as $key => $name) {
        // Skip empty names
        if (empty($name)) continue;
        
        // Convert to uppercase for standardized comparison
        $upperName = strtoupper(trim($name));
        
        // Count this name
        if (!isset($nameCounts[$upperName])) {
            $nameCounts[$upperName] = 1;
        } else {
            // This is a duplicate, increment the counter
            $nameCounts[$upperName]++;
            // Add the counter in parentheses
            $namesArray[$key] = "$name ({$nameCounts[$upperName]})";
        }
    }
    
    return $namesArray;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        error_log("Starting form submission process");
        
        // Initialize flag for CEIT student validation
        $has_valid_ceit_student = false;
        
        // Validate that at least one involved person is a CEIT student
        if (isset($_POST['personsInvolvedId']) && is_array($_POST['personsInvolvedId'])) {
            foreach ($_POST['personsInvolvedId'] as $studentId) {
                if (!empty($studentId)) {
                    // Check if student exists in tbl_student
                    $stmt_check = $connection->prepare("SELECT student_id FROM tbl_student WHERE student_id = ?");
                    $stmt_check->bind_param("s", $studentId);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows > 0) {
                        $has_valid_ceit_student = true;
                        break;  // Found at least one valid student, can stop checking
                    }
                }
            }
        }
        
        if (!$has_valid_ceit_student) {
            echo json_encode(['success' => false, 'message' => 'At least one CEIT student must be involved in the incident report.']);
            exit();
        }
        
        $connection->begin_transaction();

        $currentTimestamp = date('Y-m-d H:i:s', time());
        $dateReported = $currentTimestamp;
        $place = $_POST['place'];
        $description = $_POST['description'];
        $reportedBy = $guard_name;
        
        $incident_report_id = generateIncidentReportId($connection);
        
        // Handle file upload
        $uploadFile = null;
        if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == 0) {
            // Check file type
            $allowedTypes = ['image/jpeg', 'image/png'];
            $fileType = $_FILES['fileUpload']['type'];
            
            // Check file size (5MB = 5 * 1024 * 1024 bytes)
            $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Only JPEG and PNG image files are allowed.");
            }
            
            if ($_FILES['fileUpload']['size'] > $maxFileSize) {
                throw new Exception("File size exceeds the maximum limit of 5MB.");
            }
            
            $uploadDir = '../../uploads/incident_reports_proof/';
            $uploadFile = $uploadDir . basename($_FILES['fileUpload']['name']);
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFile)) {
                $uploadFile = '../../uploads/incident_reports_proof/' . basename($_FILES['fileUpload']['name']);
            } else {
                throw new Exception("Failed to upload file.");
            }
        }        
        if (isset($_POST['personsInvolved'])) {
            handleDuplicateNames($_POST['personsInvolved']);
        }
        
        // Handle duplicate names for witnesses
        if (isset($_POST['witnesses'])) {
            handleDuplicateNames($_POST['witnesses']);
        }

        // Insert into pending_incident_reports table
        // First, find primary student ID (first valid CEIT student)
        $primary_student_id = '';  // Initialize as empty string
        foreach ($_POST['personsInvolvedId'] as $studentId) {
            if (!empty($studentId)) {
                $stmt_check = $connection->prepare("SELECT student_id FROM tbl_student WHERE student_id = ?");
                $stmt_check->bind_param("s", $studentId);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    $primary_student_id = $studentId;  // Store the first valid student ID
                    break;
                }
            }
        }
        
        // Ensure primary_student_id is not empty for the main report
        if (empty($primary_student_id)) {
            throw new Exception("No valid CEIT student ID found for the main report.");
        }
        
        // Insert the main incident report
        $stmt = $connection->prepare("INSERT INTO pending_incident_reports (guard_id, student_id, date_reported, place, description, reported_by, reported_by_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing insert statement: " . $connection->error);
        }
        
        $stmt->bind_param("isssssss", $guard_id, $primary_student_id, $dateReported, $place, $description, $reportedBy, $reportedByType, $uploadFile);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting incident report: " . $stmt->error);
        }
        
        // Get the newly created report ID
        $pending_report_id = $stmt->insert_id;

        // Insert student violations
        $stmt_violation = $connection->prepare("
            INSERT INTO pending_student_violations 
            (pending_report_id, student_id, student_name, student_course, student_year_level, section_id, section_name, adviser_id, adviser_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($_POST['personsInvolvedId'] as $index => $studentId) {
            error_log("Processing student ID: " . $studentId);
            $studentName = $_POST['personsInvolved'][$index];
            
            // Only proceed if we have a name (required field)
            if (!empty($studentName)) {
                // Initialize student_id_param as NULL by default
                $student_id_param = !empty($studentId) ? $studentId : null;
                $student_course = null;
                $student_year_level = null;
                $section_id = null;
                $section_name = null;
                $adviser_id = null;
                $adviser_name = null;
                
                // If we have a valid student ID, fetch additional information
                if (!empty($student_id_param)) {
                    // First try to get information directly from sections table
                    $student_info_query = $connection->prepare("
                        SELECT 
                            ts.student_id,
                            CONCAT(ts.first_name, ' ', ts.last_name) as full_name,
                            c.name as student_course,
                            s.year_level as student_year_level,
                            ts.section_id as section_id,
                            CONCAT(c.name, ' - ', s.year_level, ' Section ', s.section_no) as section_name,
                            s.adviser_id as adviser_id,
                            CONCAT(a.first_name, 
                                CASE 
                                    WHEN a.middle_initial IS NOT NULL THEN CONCAT(' ', a.middle_initial, '. ')
                                    ELSE ' '
                                END,
                                a.last_name) as adviser_name
                        FROM tbl_student ts
                        JOIN sections s ON ts.section_id = s.id
                        JOIN courses c ON s.course_id = c.id
                        LEFT JOIN tbl_adviser a ON s.adviser_id = a.id
                        WHERE ts.student_id = ? 
                        AND ts.status = 'active'
                    ");
                    $student_info_query->bind_param("s", $student_id_param);
                    $student_info_query->execute();
                    $student_info_result = $student_info_query->get_result();
                    
                    if ($student_info_result->num_rows > 0) {
                        $student_info = $student_info_result->fetch_assoc();
                        $student_course = $student_info['student_course'];
                        $student_year_level = $student_info['student_year_level'];
                        $section_id = $student_info['section_id'];
                        $section_name = $student_info['section_name'];
                        $adviser_id = $student_info['adviser_id'];
                        $adviser_name = $student_info['adviser_name'];
                    }
                }
                
                // Insert the record with all student information
                $stmt_violation->bind_param("issssssis", 
                    $pending_report_id, 
                    $student_id_param, 
                    $studentName, 
                    $student_course, 
                    $student_year_level,
                    $section_id, 
                    $section_name, 
                    $adviser_id, 
                    $adviser_name
                );
                                        
                if (!$stmt_violation->execute()) {
                    throw new Exception("Error inserting student record: " . $stmt_violation->error);
                }
                error_log("DEBUG - Before binding: year_level = " . ($student_year_level ? $student_year_level : "NULL"));
error_log("DEBUG - Binding params: " . json_encode([
    'pending_report_id' => $pending_report_id,
    'student_id' => $student_id_param,
    'student_name' => $studentName,
    'student_course' => $student_course,
    'student_year_level' => $student_year_level,
    'section_id' => $section_id,
    'section_name' => $section_name,
    'adviser_id' => $adviser_id,
    'adviser_name' => $adviser_name
]));

error_log("DEBUG - Insert result: " . ($stmt_violation->affected_rows > 0 ? "Success" : "Failed"));
            }
        }

        // For witnesses
        $stmt_witness = $connection->prepare("
            INSERT INTO pending_incident_witnesses 
            (pending_report_id, witness_type, witness_id, witness_name, witness_email, 
             witness_course, witness_year_level, section_id, section_name, adviser_id, adviser_name) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($_POST['witnessType'] as $index => $witnessType) {
            $witnessName = '';
            if ($witnessType === 'student') {
                $witnessName = $_POST['witnesses'][$index];
            } else if ($witnessType === 'staff') {
                $witnessName = $_POST['staffWitnessName'][$index];
            }
            
            if (!empty($witnessName)) {
                $witnessId = null;
                $witnessEmail = null;
                $witness_course = null;
                $witness_year_level = null;
                $section_id = null;
                $section_name = null;
                $adviser_id = null;
                $adviser_name = null;

                if ($witnessType === 'student' && !empty($_POST['witnessId'][$index])) {
                    // Try to get student info if they exist in our system
                    $witness_info_query = $connection->prepare("
                        SELECT 
                            ts.student_id,
                            CONCAT(ts.first_name, ' ', 
                                CASE 
                                    WHEN ts.middle_name IS NOT NULL AND ts.middle_name != '' 
                                    THEN CONCAT(ts.middle_name, ' ') 
                                    ELSE ''
                                END,
                                ts.last_name) as student_full_name,
                            c.name as witness_course,
                            s.year_level as witness_year_level,
                            ts.section_id as section_id,
                            CONCAT(c.name, ' - ', s.year_level, ' Section ', s.section_no) as section_name,
                            s.adviser_id as adviser_id,
                            CONCAT(a.first_name, 
                                CASE 
                                    WHEN a.middle_initial IS NOT NULL THEN CONCAT(' ', a.middle_initial, '. ')
                                    ELSE ' '
                                END,
                                a.last_name) as adviser_name
                        FROM tbl_student ts
                        JOIN sections s ON ts.section_id = s.id
                        JOIN courses c ON s.course_id = c.id
                        LEFT JOIN tbl_adviser a ON s.adviser_id = a.id
                        WHERE ts.student_id = ? 
                        AND ts.status = 'active'
                    ");
                    
                    $witness_info_query->bind_param("s", $_POST['witnessId'][$index]);
                    $witness_info_query->execute();
                    $witness_info_result = $witness_info_query->get_result();
                    
                    if ($witness_info_result->num_rows > 0) {
                        // CEIT student found - use their information
                        $witness_info = $witness_info_result->fetch_assoc();
                        $witnessId = $witness_info['student_id'];
                        $witness_course = $witness_info['witness_course'];
                        $witness_year_level = $witness_info['witness_year_level'];
                        $section_id = $witness_info['section_id'];
                        $section_name = $witness_info['section_name'];
                        $adviser_id = $witness_info['adviser_id'];
                        $adviser_name = $witness_info['adviser_name'];
                    }
                } else if ($witnessType === 'staff') {
                    $witnessEmail = isset($_POST['witnessEmail'][$index]) ? $_POST['witnessEmail'][$index] : null;
                }
                
                $stmt_witness->bind_param("issssssssis",
                    $pending_report_id,
                    $witnessType,
                    $witnessId,
                    $witnessName,
                    $witnessEmail,
                    $witness_course,
                    $witness_year_level,
                    $section_id,
                    $section_name,
                    $adviser_id,
                    $adviser_name
                );
                
                if (!$stmt_witness->execute()) {
                    throw new Exception("Error inserting witness record: " . $stmt_witness->error);
                }
            }
        }
        
        // Add notification for all deans
        $notification_message = "A new incident report has been submitted by " . $guard_name . " that requires your review.";
        $notification_link = "dean_view_incident_reports.php";

        // Fetch all active dean IDs
        $get_deans = $connection->prepare("SELECT id FROM tbl_dean WHERE status = 'active'");
        if (!$get_deans->execute()) {
            throw new Exception("Error fetching dean IDs: " . $get_deans->error);
        }
        $dean_result = $get_deans->get_result();

        // Prepare the notification statement once
        $stmt_notification = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link, is_read, created_at) VALUES ('dean', ?, ?, ?, 0, NOW())");

        // Insert notification for each dean
        while ($dean = $dean_result->fetch_assoc()) {
            $stmt_notification->bind_param("iss", $dean['id'], $notification_message, $notification_link);
            if (!$stmt_notification->execute()) {
                throw new Exception("Error inserting notification for dean ID {$dean['id']}: " . $stmt_notification->error);
            }
        }
        
        $connection->commit();
        
        echo json_encode([ 
            'success' => true,
            'message' => 'Incident report submitted successfully'
        ]);
        exit;
        
    } catch (Exception $e) { 
        $connection->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
}

$currentDateTime = (new DateTime())->format('Y-m-d H:i:s.u');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report Submission - Guard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="incident_form.css">
</head>

<style>
        .hidden {
            display: none !important;
        }
    </style>

<body>
<div class="container mt-5">
    <a href="guard_homepage.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
        </a>
        <h2 class="text-center mb-4">INCIDENT REPORT FORM - GUARD</h2>
     
           

        <form id="incidentReportForm" method="POST" enctype="multipart/form-data">
            <!-- Place of Incident -->
           <div class="form-group">
                <label for="incidentPlace">
                    <i class="fas fa-map-marker-alt"></i> Place of Incident
                    <i class="fas fa-info-circle text-info ml-1" 
                       data-toggle="tooltip" 
                       data-placement="right" 
                       title="Examples: CEIT Building Room 201, CED Laboratory 1, Near Bleachers Gate1, CON Corridor Ground Floor, etc.">
                            </i>
                        </label>
                        <input type="text" 
                            class="form-control" 
                            id="incidentPlace" 
                            name="incidentPlace" 
                            placeholder="Please specify the exact location of the incident" 
                        required>
            </div>

            
            <div class="form-group">
                <label for="incidentDate"><i class="far fa-calendar-alt"></i> Date of Incident:</label>
                <input type="date" class="form-control" id="incidentDate" name="incidentDate" required>
            </div>

            
            <div class="form-group">
                <label for="incidentTime"><i class="far fa-clock"></i> Time of Incident:</label>
                <input type="time" class="form-control" id="incidentTime" name="incidentTime" required>
            </div>

            
            <div class="form-group">
                <label for="place"><i class="fas fa-info-circle"></i> Place, Date & Time of Incident:</label>
                <input type="text" class="form-control" id="place" name="place" readonly>
            </div>


           <div class="form-group">
                <label><i class="fas fa-users"></i> Person/s Involved:</label>
                <div id="personsInvolvedContainer">
                    <div class="person-involved-entry">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control mb-2" 
                                    name="personsInvolvedId[]" 
                                    placeholder="Student ID (Optional)" 
                                    onkeyup="validateStudentIdInput(this)" 
                                    onchange="fetchStudentInfo(this)">
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control mb-2" 
                                    name="personsInvolved[]" 
                                    placeholder="Name" 
                                     oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase()"
                                    required>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addPersonInvolved()">
                    <i class="fas fa-plus"></i> Add Person
                </button>
            </div>
            <div class="form-group">
            <label><i class="fas fa-eye"></i> Witness/es:</label>
            <div id="witnessesContainer">
                <div class="witness-entry">
                    <select class="form-control mb-2" name="witnessType[]" onchange="toggleWitnessFields(this)">
                        <option value="">Select Witness Type</option>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                    </select>
                
                <!-- Student Fields - Hidden by default -->
                    <div class="row student-fields hidden">
                        <div class="col-md-4">
                            <input type="text" class="form-control mb-2" 
                                name="witnessId[]" 
                                placeholder="Student ID (Optional)"
                                onkeyup="validateStudentIdInput(this)" 
                                onchange="fetchWitnessInfo(this)">
                        </div>
                        <div class="col-md-8">
                            <input type="text" class="form-control mb-2" name="witnesses[]" placeholder="Student Name" oninput="this.value = this.value.toUpperCase()">
                        </div>
                    </div>

                    <!-- Staff Fields - Hidden by default -->
                    <div class="row staff-fields hidden">
                        <div class="col-md-6">
                            <input type="text" class="form-control mb-2" 
                            name="staffWitnessName[]" 
                            placeholder="Staff Name"
                             oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase()" 
                            >
                        </div>
                        <div class="col-md-6">
                            <input type="email" class="form-control mb-2" 
                                name="witnessEmail[]" 
                                placeholder="Staff Email" >
                        </div>
                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addWitnessField()">
                                <i class="fas fa-plus"></i> Add Witness
                            </button>
                        </div>
                        
            <div class="form-group">
                <label for="description"><i class="fas fa-file-alt"></i> Brief Description of the Incident/Offense:</label>
                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
            </div>

            <div class="form-group">
                <label for="reportedBy"><i class="fas fa-user"></i> Reported by:</label>
                <input type="text" class="form-control" id="reportedBy" name="reportedBy" value="<?php echo htmlspecialchars($guard_name); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="fileUpload"><i class="fas fa-file-upload"></i> Upload Picture:</label>
                <input type="file" class="form-control-file" id="fileUpload" name="fileUpload" accept="image/jpeg,image/png">
                <small class="text-muted">Only JPEG or PNG files, maximum 5MB</small>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </div>
        </form>
    </div>

<script> 

function toggleWitnessFields(select) {
    const witnessEntry = select.closest('.witness-entry');
    const studentFields = witnessEntry.querySelector('.student-fields');
    const staffFields = witnessEntry.querySelector('.staff-fields');
    
    // Hide all fields first
    studentFields.classList.add('hidden');
    staffFields.classList.add('hidden');
    
    // Reset all fields
    witnessEntry.querySelectorAll('input').forEach(input => {
        input.value = '';
        
        input.disabled = true;
    });
    
    if (select.value === 'student') {
        studentFields.classList.remove('hidden');
        witnessEntry.querySelector('input[name="witnessId[]"]').disabled = false;
        witnessEntry.querySelector('input[name="witnessId[]"]').required = true;
        witnessEntry.querySelector('input[name="witnesses[]"]').required = true;
    } else if (select.value === 'staff') {
        staffFields.classList.remove('hidden');
        staffFields.querySelectorAll('input').forEach(input => {
            input.disabled = false;
           
        });
    }
}

function addWitnessField() {
    const container = document.getElementById('witnessesContainer');
    const div = document.createElement('div');
    div.className = 'witness-entry';
    div.innerHTML = `
        <select class="form-control mb-2" name="witnessType[]" onchange="toggleWitnessFields(this)" required>
            <option value="">Select Witness Type</option>
            <option value="student">Student</option>
            <option value="staff">Staff</option>
        </select>
        
        <!-- Student Fields - Hidden by default -->
        <div class="row student-fields hidden">
            <div class="col-md-4">
                <input type="text" class="form-control mb-2" 
                    name="witnessId[]" 
                    placeholder="Student ID"
                    onkeyup="validateStudentIdInput(this)" 
                    onchange="fetchWitnessInfo(this)">
            </div>
            <div class="col-md-8">
                <input type="text" class="form-control mb-2" 
                    name="witnesses[]" 
                    placeholder="Student Name"
                    oninput="this.value = this.value.toUpperCase()">
            </div>
        </div>
        
        <!-- Staff Fields - Hidden by default -->
        <div class="row staff-fields hidden">
            <div class="col-md-6">
                <input type="text" class="form-control mb-2" 
                    name="staffWitnessName[]" 
                    placeholder="Staff Name"
                    oninput="this.value = this.value.toUpperCase()" 
                    required>
            </div>
            <div class="col-md-6">
                <input type="email" class="form-control mb-2" 
                    name="witnessEmail[]" 
                    placeholder="Staff Email" required>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
}

$(document).ready(function() {
    // Check for success message from PHP session
    <?php if (isset($_SESSION['report_submitted']) && $_SESSION['report_submitted']): ?>
        Swal.fire({
            title: 'Success!',
            text: 'Incident report has been submitted successfully.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            <?php unset($_SESSION['report_submitted']); ?>
        });
    <?php endif; ?>

    // Set max date to today
    const today = new Date().toISOString().split('T')[0];
    $('#incidentDate').attr('max', today);

    // Initial time restrictions
    updateTimeRestrictions();

    // Update time restrictions when date changes
    $('#incidentDate').on('change', function() {
        updateTimeRestrictions();
        // Reset time when date changes
        $('#incidentTime').val('');
    });

    // Time input handler
    $('#incidentTime').on('input', function() {
        const selectedDate = $('#incidentDate').val();
        const selectedTime = $(this).val();
        
        if (selectedDate === today) {
            const now = new Date();
            const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            
            if (selectedTime > currentTime) {
                $(this).val('');
                Swal.fire({
                    title: 'Invalid Time',
                    text: 'You cannot select a future time for today\'s date.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            }
        }
        updateCombinedField();
    });

    // Update combined field when any relevant input changes
    $('#incidentPlace, #incidentDate, #incidentTime').on('change', function() {
        updateCombinedField();
    });

    //form validation
   $('#incidentReportForm').on('submit', async function(e) {
    e.preventDefault();

    // Enable all disabled fields temporarily for form submission
    const disabledFields = $(this).find(':disabled').removeAttr('disabled');
    
    const studentIds = [];
    let hasValidStudent = false;

    // Collect all non-empty student IDs from persons involved
    $('input[name="personsInvolvedId[]"]').each(function() {
        if ($(this).val()) {
            studentIds.push($(this).val());
        }
    });

    // Rest of your validation code...

    // If validation passes, show confirmation dialog
    Swal.fire({
        title: 'Confirm Submission',
        text: 'Are you sure you want to submit this incident report?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, submit it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(this);
            
            // Re-disable the fields after getting their values
            disabledFields.attr('disabled', 'disabled');
            
            $.ajax({
                url: $(this).attr('action') || window.location.href,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: result.message || 'Incident report has been submitted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                $('#incidentReportForm')[0].reset();
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: result.message || 'An error occurred while submitting the report.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        console.log('Response:', response); // Add for debugging
                        Swal.fire({
                            title: 'Error',
                            text: 'An unexpected error occurred.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.log('Status:', status);
                    console.log('Response:', xhr.responseText);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while submitting the report.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        } else {
            // Re-disable the fields if submission is cancelled
            disabledFields.attr('disabled', 'disabled');
        }
    });
});

});

function updateTimeRestrictions() {
    const dateInput = document.getElementById('incidentDate');
    const timeInput = document.getElementById('incidentTime');
    const selectedDate = dateInput.value;
    const now = new Date();
    const today = now.toISOString().split('T')[0];

    // If selected date is today, restrict time input
    if (selectedDate === today) {
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeInput.setAttribute('max', `${hours}:${minutes}`);
        
        // If current time selection is later than current time, reset it
        if (timeInput.value > `${hours}:${minutes}`) {
            timeInput.value = '';
        }
    } else {
        timeInput.removeAttribute('max');
    }
}

function updateCombinedField() {
    const place = $('#incidentPlace').val();
    const date = $('#incidentDate').val();
    const time = $('#incidentTime').val();
    
    if (place && date && time) {
        const selectedDate = new Date(date + 'T' + time);
        const formattedDate = selectedDate.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        });
        $('#place').val(`${place} - ${formattedDate}`);
    }
}

function validateStudentId(studentId) {
    return new Promise((resolve) => {
        if (!studentId) {
            resolve(true);
            return;
        }

        $.ajax({
            url: 'check_student.php',
            method: 'POST',
            data: { student_id: studentId },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (!result.exists) {
                        Swal.fire({
                            title: 'Invalid Student ID',
                            text: 'This student is not part of the CEIT Population. Please remove this entry or provide a valid CEIT student ID.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                    resolve(result.exists);
                } catch (e) {
                    resolve(false);
                }
            },
            error: function() {
                resolve(false);
            }
        });
    });
}

function addPersonInvolved() {
    var container = document.getElementById('personsInvolvedContainer');
    var div = document.createElement('div');
    div.className = 'person-involved-entry';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <input type="text" class="form-control mb-2" 
                    name="personsInvolvedId[]" 
                    placeholder="Student ID (Optional)" 
                    onkeyup="validateStudentIdInput(this)" 
                    onchange="fetchStudentInfo(this)">
            </div>
            <div class="col-md-8">
                <input type="text" class="form-control mb-2" 
                    name="personsInvolved[]" 
                    placeholder="Name" 
                    oninput="this.value = this.value.toUpperCase()"
                    required>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
}


// Also update the toggleStudentIdField function to clear the name when switching types:
function toggleStudentIdField(select) {
    const witnessEntry = select.closest('.witness-entry');
    const idField = witnessEntry.querySelector('input[name="witnessId[]"]');
    const nameField = witnessEntry.querySelector('input[name="witnesses[]"]');
    
    idField.value = ''; // Clear the ID field
    nameField.value = ''; // Clear the name field
    idField.disabled = select.value !== 'student';
}


function toggleWitnessFields(select) {
    const witnessEntry = select.closest('.witness-entry');
    const studentFields = witnessEntry.querySelector('.student-fields');
    const staffFields = witnessEntry.querySelector('.staff-fields');
    
    // Hide all fields first
    studentFields.classList.add('hidden');
    staffFields.classList.add('hidden');
    
    // Reset all fields
    witnessEntry.querySelectorAll('input').forEach(input => {
        input.value = '';
        input.required = false;
        input.disabled = false; // Don't disable by default
    });
    
    if (select.value === 'student') {
        studentFields.classList.remove('hidden');
        const studentNameInput = witnessEntry.querySelector('input[name="witnesses[]"]');
        studentNameInput.required = true;  // Only name is required
    } else if (select.value === 'staff') {
        staffFields.classList.remove('hidden');
        staffFields.querySelectorAll('input').forEach(input => {
            input.disabled = false;
            input.required = true;
        });
    }
}

function fetchWitnessInfo(input) {
    const witnessEntry = input.closest('.witness-entry');
    const witnessType = witnessEntry.querySelector('select[name="witnessType[]"]').value;
    
    if (witnessType !== 'student') return;
    
    const studentId = input.value;
    const nameInput = witnessEntry.querySelector('input[name="witnesses[]"]');
    
    if (isDuplicate(studentId, 'studentId', input)) {
        Swal.fire({
            title: 'Duplicate Entry',
            text: 'This Student ID has already been used. Please use a different ID.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        input.value = '';
        nameInput.value = '';
        nameInput.disabled = false;  // Enable name input when clearing
        return;
    }
    
    // Enable name field by default
    nameInput.disabled = false;
    
    // Clear and enable name if ID is empty
    if (!studentId) {
        nameInput.value = '';
        return;
    }
    
    // If ID is not complete (9 digits), clear name and enable field
    if (studentId.length < 9) {
        nameInput.value = '';
        return;
    }

    // Query database only when we have a complete student ID
    $.ajax({
        url: 'get_student_info.php',
        method: 'POST',
        data: { student_id: studentId },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    nameInput.value = result.name;
                    nameInput.disabled = true;  // Disable only when valid student found
                } else {
                    Swal.fire({
                    title: 'Student Not Found',
                    text: 'Student ID not found. You may manually enter the student\'s information.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
                    nameInput.value = '';
                    nameInput.disabled = false;  // Enable if student not found
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                nameInput.disabled = false;
            }
        },
        error: function() {
            console.error('Error fetching student info');
            nameInput.value = '';
            nameInput.disabled = false;
        }
    });
}


// Add this function to check for duplicates
function isDuplicate(value, inputType, currentInput) {
    // If value is empty, it's not a duplicate
    if (!value || value.trim() === '') {
        return false;
    }

    let isDuplicate = false;
    
    // Only check for student ID duplicates
    if (inputType === 'studentId') {
        // Check in persons involved
        $('input[name="personsInvolvedId[]"]').each(function() {
            if ($(this).val() === value && this !== currentInput) {
                isDuplicate = true;
                return false; // break the loop
            }
        });
        
        // Also check in witness student IDs
        if (!isDuplicate) {
            $('input[name="witnessId[]"]').each(function() {
                if ($(this).val() === value && this !== currentInput) {
                    isDuplicate = true;
                    return false;
                }
            });
        }
    }
    
    return isDuplicate;
}

function fetchStudentInfo(input) {
    const studentId = input.value;
    const nameInput = input.closest('.person-involved-entry').querySelector('input[name="personsInvolved[]"]');
    
    if (isDuplicate(studentId, 'studentId', input)) {
        Swal.fire({
            title: 'Duplicate Entry',
            text: 'This Student ID has already been used. Please use a different ID.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        input.value = '';
        nameInput.value = '';
        nameInput.disabled = false;
        return;
    }
    
    if (studentId) {
        $.ajax({
            url: 'get_student_info.php',
            method: 'POST',
            data: { student_id: studentId },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        nameInput.value = result.name;
                        nameInput.disabled = true;  // Disable only when valid student found
                    } else {
                        nameInput.value = '';
                        nameInput.disabled = false;  // Enable if student not found
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    nameInput.disabled = false;
                }
            },
            error: function() {
                console.error('Error fetching student info');
                nameInput.disabled = false;
            }
        });
    } else {
        nameInput.value = '';
        nameInput.disabled = false;  // Enable if no student ID
    }
}

function removeEntry(button) {
    button.closest('.person-involved-entry, .witness-entry').remove();
}
 

function validateStudentIdInput(input) {
        let value = input.value.replace(/[^0-9]/g, '');
        if (value.length > 9) {
            value = value.slice(0, 9);
        }
        input.value = value;
    }

    // Add this to your incident_report_form.js file or in a script tag
document.getElementById('fileUpload').addEventListener('change', function() {
    const fileInput = this;
    const fileSize = fileInput.files[0]?.size || 0;
    const fileType = fileInput.files[0]?.type || '';
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png'];
    
    if (fileInput.files.length > 0) {
        if (!allowedTypes.includes(fileType)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Only JPEG and PNG images are allowed'
            });
            fileInput.value = '';
        } else if (fileSize > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Maximum file size is 5MB'
            });
            fileInput.value = '';
        }
    }
});


</script>
</body>
</html>