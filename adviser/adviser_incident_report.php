<?php
session_start();
date_default_timezone_set('Asia/Manila');
include '../db.php'; 

// Ensure the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: ../login.php");
    exit();  
}

$reporter_id = $_SESSION['user_id'];
$reporter_type = $_SESSION['user_type'];

// Generate a new incident report ID at the start
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

// Fetch reporter's name
$table_name = "tbl_" . $reporter_type;
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM $table_name WHERE id = ?");
if (!$stmt) {
    die("Error preparing statement: " . $connection->error);
}

$stmt->bind_param("i", $reporter_id);
$stmt->execute();
$result = $stmt->get_result();
$reporter = $result->fetch_assoc();

// Concatenate name with middle initial
$reporter_name = $reporter['first_name'];
if (!empty($reporter['middle_initial'])) {
    $reporter_name .= ' ' . $reporter['middle_initial'] . '.';
}
$reporter_name .= ' ' . $reporter['last_name'];

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
        $connection->begin_transaction();

        $currentTimestamp = date('Y-m-d H:i:s', time());
        $dateReported = $currentTimestamp;
        $place = $_POST['place'];
        $description = $_POST['description'];
        $reportedBy = $reporter_name;
        $reportedByType = $_SESSION['user_type'];
        
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
        
        // Insert into incident_reports table
        $stmt = $connection->prepare("INSERT INTO incident_reports (id, date_reported, place, description, reported_by, reporters_id, reported_by_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error preparing insert statement: " . $connection->error);
        }
        
        $stmt->bind_param("ssssssss", $incident_report_id, $dateReported, $place, $description, $reportedBy, $reporter_id, $reportedByType, $uploadFile);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting incident report: " . $stmt->error);
        }

        // Insert notifications for facilitators
        $notify_facilitators = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link) 
                                                  SELECT 'facilitator', id, ?, ? 
                                                  FROM tbl_facilitator 
                                                  WHERE status = 'active'");
        
        $notification_message = "New incident report submitted by " . ucfirst($reporter_type) . " " . $reporter['first_name'];
        $notification_link = "view_facilitator_incident_reports.php?id=" . $incident_report_id;
        
        $notify_facilitators->bind_param("ss", $notification_message, $notification_link);
        if (!$notify_facilitators->execute()) {
            throw new Exception("Error inserting facilitator notifications: " . $notify_facilitators->error);
        }

        // If the reporter is an adviser, notify other advisers
        if ($reporter_type === 'adviser') {
            $notify_other_advisers = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link) 
                                                       SELECT 'adviser', id, ?, ? 
                                                       FROM tbl_adviser 
                                                       WHERE status = 'active' AND id != ?");
            
            $adviser_notification_message = "New incident report submitted by Adviser " . $reporter['first_name'];
            $adviser_notification_link = "view_student_incident_reports.php?id=" . $incident_report_id;
            
            $notify_other_advisers->bind_param("ssi", $adviser_notification_message, $adviser_notification_link, $reporter_id);
            if (!$notify_other_advisers->execute()) {
                throw new Exception("Error inserting adviser notifications: " . $notify_other_advisers->error);
            }
        }


// Update the prepare statement to include section and adviser information
$stmt_violation = $connection->prepare("INSERT INTO student_violations (
    student_id, 
    incident_report_id, 
    violation_date, 
    status, 
    student_name,
    student_course,
    student_year_level,
    section_id,
    section_name,
    adviser_id,
    adviser_name
) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)");

foreach ($_POST['personsInvolvedId'] as $index => $studentId) {
    error_log("Processing student ID: " . $studentId);
    $studentName = $_POST['personsInvolved'][$index];
    if (!empty($studentName)) {
        // Modified query to get section and adviser information
        $check_stmt = $connection->prepare("
            SELECT 
                ts.student_id, 
                CONCAT(ts.first_name, ' ', ts.last_name) as full_name,
                c.name as student_course,
                s.year_level as student_year_level,
                s.id as section_id,
                CONCAT(c.name, ' - ', s.year_level, ' Section ', s.section_no) as section_name,
                a.id as adviser_id,
                CONCAT(a.first_name, 
                    CASE 
                        WHEN a.middle_initial IS NOT NULL THEN CONCAT(' ', a.middle_initial, '. ')
                        ELSE ' '
                    END,
                    a.last_name) as adviser_name
            FROM (
                SELECT * FROM tbl_student 
                WHERE status = 'active' 
                AND student_id = ?
            ) ts
            JOIN sections s ON ts.section_id = s.id
            JOIN courses c ON s.course_id = c.id
            JOIN tbl_adviser a ON s.adviser_id = a.id
        ");
        
        $check_stmt->bind_param("s", $studentId);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // CEIT student found
            $student_data = $check_result->fetch_assoc();
            $stmt_violation->bind_param("ssssssssss", 
                $student_data['student_id'],
                $incident_report_id,
                $dateReported,
                $student_data['full_name'],
                $student_data['student_course'],
                $student_data['student_year_level'],
                $student_data['section_id'],
                $student_data['section_name'],
                $student_data['adviser_id'],
                $student_data['adviser_name']
            );
            
            // Notify the student's adviser if the reporter is not the adviser
            if ($reporter_type !== 'adviser' || $reporter_id != $student_data['adviser_id']) {
                $notify_adviser = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link) 
                                                      VALUES ('adviser', ?, ?, ?)");
                
                $adviser_message = "Your advisee " . $student_data['full_name'] . " has been involved in an incident report";
                $adviser_link = "view_student_incident_reports.php?id=" . $incident_report_id;
                
                $notify_adviser->bind_param("iss", $student_data['adviser_id'], $adviser_message, $adviser_link);
                $notify_adviser->execute();
            }
        } else {
            // Non-CEIT student - use provided name and null values for section/adviser
            $nullValue = null;
            $stmt_violation->bind_param("ssssssssss", 
                $nullValue,  // student_id
                $incident_report_id,
                $dateReported,
                $studentName,  // use provided name
                $nullValue,    // student_course
                $nullValue,    // student_year_level
                $nullValue,    // section_id
                $nullValue,    // section_name
                $nullValue,    // adviser_id
                $nullValue     // adviser_name
            );
        }
        
        if (!$stmt_violation->execute()) {
            throw new Exception("Error inserting violation: " . $stmt_violation->error);
        }
    }
}

// For witnesses
$stmt_witness = $connection->prepare("INSERT INTO incident_witnesses (
    incident_report_id, 
    witness_type, 
    witness_id, 
    witness_name, 
    witness_email,
    witness_student_name,
    witness_course,
    witness_year_level,
    section_id,
    section_name,
    adviser_id,
    adviser_name
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($_POST['witnessType'] as $index => $witnessType) {
    $witnessName = $_POST['witnesses'][$index];
    if (!empty($witnessName)) {
        $witnessId = null;
        $witnessEmail = null;
        $witnessStudentName = null;
        $witnessCourse = null;
        $witnessYearLevel = null;
        $sectionId = null;
        $sectionName = null;
        $adviserId = null;
        $adviserName = null;

        if ($witnessType === 'student' && !empty($_POST['witnessId'][$index])) {
            // Try to get student info if they exist in our system
            $check_stmt = $connection->prepare("
                SELECT 
                    ts.student_id,
                    CONCAT(ts.first_name, ' ', 
                        CASE 
                            WHEN ts.middle_name IS NOT NULL AND ts.middle_name != '' 
                            THEN CONCAT(ts.middle_name, ' ') 
                            ELSE ''
                        END,
                        ts.last_name) as student_full_name,
                    c.name as course_name,
                    s.year_level,
                    s.id as section_id,
                    CONCAT(c.name, ' - ', s.year_level, ' Section ', s.section_no) as section_name,
                    a.id as adviser_id,
                    CONCAT(a.first_name, 
                        CASE 
                            WHEN a.middle_initial IS NOT NULL THEN CONCAT(' ', a.middle_initial, '. ')
                            ELSE ' '
                        END,
                        a.last_name) as adviser_name
                FROM tbl_student ts
                JOIN sections s ON ts.section_id = s.id
                JOIN courses c ON s.course_id = c.id
                JOIN tbl_adviser a ON s.adviser_id = a.id
                WHERE ts.student_id = ? 
                AND ts.status = 'active'
                LIMIT 1
            ");
            
            $check_stmt->bind_param("s", $_POST['witnessId'][$index]);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // CEIT student found - use their information
                $student_data = $check_result->fetch_assoc();
                $witnessId = $student_data['student_id'];
                $witnessStudentName = $student_data['student_full_name'];
                $witnessCourse = $student_data['course_name'];
                $witnessYearLevel = $student_data['year_level'];
                $sectionId = $student_data['section_id'];
                $sectionName = $student_data['section_name'];
                $adviserId = $student_data['adviser_id'];
                $adviserName = $student_data['adviser_name'];
            } else {
                // Non-CEIT student - just use the provided name
                $witnessId = null;
                $witnessStudentName = $witnessName;
            }
        } else if ($witnessType === 'staff') {
            $witnessEmail = isset($_POST['witnessEmail'][$index]) ? $_POST['witnessEmail'][$index] : null;
        }
        
        $stmt_witness->bind_param("ssssssssssss",
            $incident_report_id,
            $witnessType,
            $witnessId,
            $witnessName,
            $witnessEmail,
            $witnessStudentName,
            $witnessCourse,
            $witnessYearLevel,
            $sectionId,
            $sectionName,
            $adviserId,
            $adviserName
        );
        
        if (!$stmt_witness->execute()) {
            throw new Exception("Error inserting witness: " . $stmt_witness->error);
        }
    }
}
        
        $connection->commit();
        
        echo json_encode([ 
            'success' => true,
            'message' => 'Incident report submitted successfully.',
            'report_id' => $incident_report_id
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
    <title>Adviser Incident Report Submission</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="incident_form.css"> 
</head>
<style>
    body {
        background: linear-gradient(135deg, #0d693e, #004d4d);
        min-height: 100vh;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: var(--text-color);
        margin: 0;
        padding: 0;
    }
    </style>
<body>
    
    <main class="main-content">
    <div class="header1">

        <div class="container">
        <a href="adviser_homepage.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
        </a>
        
        <h2 class="text-center mb-4">INCIDENT REPORT FORM</h2>

        <form id="incidentReportForm" method="POST" enctype="multipart/form-data">
             <!-- Hidden field for Date & Time Reported -->
    <input type="hidden" name="dateReported" value="<?php echo $currentDateTime; ?>">

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
               <!-- For persons involved -->
                <input type="text" 
                    class="form-control mb-2 student-id" 
                    name="personsInvolvedId[]" 
                    placeholder="Student ID (Optional)" 
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);">
                <input type="text" 
                    class="form-control mb-2 student-name" 
                    name="personsInvolved[]" 
                    placeholder="Name" 
                    required 
                     oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();">
                <input type="text" class="form-control mb-2 student-year-course" name="personsInvolvedYearCourse[]" placeholder="Year & Course" readonly>
                <input type="text" class="form-control mb-2" name="personsInvolvedAdviser[]" placeholder="Registration Adviser" readonly>
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
            <select class="form-control mb-2 witness-type" name="witnessType[]" onchange="toggleWitnessFields(this)" required>
                <option value="">Select Witness Type</option>
                <option value="student">Student</option>
                <option value="staff">Staff</option>
            </select> 
            <input type="text" 
                class="form-control mb-2 student-field student-id" 
                name="witnessId[]" 
                placeholder="Student ID (Optional)" 
                style="display:none;"
                oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);">
            <input type="text" 
                class="form-control mb-2 witness-name" 
                name="witnesses[]" 
                placeholder="Name"
                required 
                style="display:none;"
                 oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();">
            <input type="text" class="form-control mb-2 student-field student-year-course" name="witnessesYearCourse[]" placeholder="Year & Course" style="display:none;" readonly>
            <input type="text" class="form-control mb-2 student-field" name="witnessesAdviser[]" placeholder="Registration Adviser" style="display:none;" readonly>
            <input type="email" class="form-control mb-2 staff-field" name="witnessEmail[]" placeholder="Email" style="display:none;">
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
            <input type="text" class="form-control" id="reportedBy" name="reportedBy" value="<?php echo htmlspecialchars($reporter_name); ?>" readonly>
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

<script type="text/javascript" src="adviser_report_form.js"></script>

<script>
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