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

// Function to generate incident report ID
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

// Fetch reporter's name based on user type
if ($reporter_type === 'student') {
    $stmt = $connection->prepare("SELECT first_name, middle_name, last_name FROM tbl_student WHERE student_id = ?");
    if (!$stmt) {
        die("Error preparing student statement: " . $connection->error);
    }
    $stmt->bind_param("s", $reporter_id);
} else {
    $table_name = "tbl_" . $reporter_type;
    $stmt = $connection->prepare("SELECT first_name, middle_name, last_name FROM $table_name WHERE id = ?");
    if (!$stmt) {
        die("Error preparing staff statement: " . $connection->error);
    }
    $stmt->bind_param("i", $reporter_id);
}

$stmt->execute(); 
$result = $stmt->get_result();
$reporter = $result->fetch_assoc();

// Format reporter's full name
$reporter_name = $reporter['first_name'];
if (!empty($reporter['middle_name'])) {
    $reporter_name .= ' ' . $reporter['middle_name'] . '.';
}
$reporter_name .= ' ' . $reporter['last_name'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currentTimestamp = date('Y-m-d H:i:s', time());
    $dateReported = $_POST['dateReported'];
    $place = $_POST['place'];
    $description = $_POST['description'];
    $reportedBy = $reporter_name;
    $reportedByType = $_SESSION['user_type'];
    
    // Generate the new formatted ID
    $incident_report_id = generateIncidentReportId($connection);
    
    // Handle file upload
    $uploadFile = null;
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == 0) {
        // Check file size - 5MB maximum
        $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($_FILES['fileUpload']['size'] > $maxFileSize) {
            echo json_encode([
                'success' => false,
                'message' => 'File size must be less than 5MB.'
            ]);
            exit;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png']; 
        if (!in_array($_FILES['fileUpload']['type'], $allowedTypes)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid file type. Only images (JPEG, PNG) are allowed.'
                ]);
                exit;
            }
        
        $uploadDir = '../../uploads/incident_reports_proof/';
        $uploadFile = $uploadDir . basename($_FILES['fileUpload']['name']);
        
        // Ensure the upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFile)) {
            $uploadFile = '../../uploads/incident_reports_proof/' . basename($_FILES['fileUpload']['name']);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to upload file.'
            ]);
            exit;
        }
    }
    
    // Insert into incident_reports table
    $stmt = $connection->prepare("INSERT INTO incident_reports (id, date_reported, place, description, reported_by, reporters_id, reported_by_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing insert statement: " . $connection->error);
    }
    
    $stmt->bind_param("ssssssss", $incident_report_id, $dateReported, $place, $description, $reportedBy, $reporter_id, $reportedByType, $uploadFile);
    
    if ($stmt->execute()) {
        // Insert notifications for all facilitators
        $notify_facilitators = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link) 
                                                    SELECT 'facilitator', id, ?, ? 
                                                    FROM tbl_facilitator 
                                                    WHERE status = 'active'");
        if (!$notify_facilitators) {
            die("Error preparing notification statement: " . $connection->error);
        }
        
        // Just mention "Student" without specific name
        $notification_message = "New incident report submitted by Student";
        $notification_link = "view_facilitator_incident_reports.php?id=" . $incident_report_id;
        
        $notify_facilitators->bind_param("ss", $notification_message, $notification_link);
        if (!$notify_facilitators->execute()) {
            die("Error inserting facilitator notifications: " . $notify_facilitators->error);
        }
        
        // Handle persons involved
        $stmt_violation = $connection->prepare("INSERT INTO student_violations (student_id, incident_report_id, violation_date, status, student_name) VALUES (?, ?, ?, 'pending', ?)");

        foreach ($_POST['personsInvolvedId'] as $index => $studentId) {
            $studentName = $_POST['personsInvolved'][$index];
            if (!empty($studentName)) {
                // Check if this student exists in the database
                $check_stmt = $connection->prepare("SELECT student_id, first_name, last_name FROM tbl_student WHERE student_id = ?");
                $check_stmt->bind_param("s", $studentId);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Student exists in database, use their ID
                    $stmt_violation->bind_param("ssss", 
                        $studentId,
                        $incident_report_id,
                        $dateReported,
                        $studentName
                    );
                } else {
                    // Student doesn't exist, set ID to null but keep the name
                    $nullId = null;
                    $stmt_violation->bind_param("ssss", 
                        $nullId,
                        $incident_report_id,
                        $dateReported,
                        $studentName
                    );
                }
                $stmt_violation->execute();
            }
        }

        // Handle witnesses
        $stmt_witness = $connection->prepare("INSERT INTO incident_witnesses (incident_report_id, witness_type, witness_id, witness_name, witness_email) VALUES (?, ?, ?, ?, ?)");

        foreach ($_POST['witnessType'] as $index => $witnessType) {
            $witnessName = $_POST['witnesses'][$index];
            if (!empty($witnessName)) {
                $witnessId = null;
                $witnessEmail = null;

                if ($witnessType === 'student') {
                    // Check if this witness exists in the database
                    if (!empty($_POST['witnessId'][$index])) {
                        $check_stmt = $connection->prepare("SELECT student_id FROM tbl_student WHERE student_id = ?");
                        $check_stmt->bind_param("s", $_POST['witnessId'][$index]);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        // Only use the ID if the student exists
                        $witnessId = ($check_result->num_rows > 0) ? $_POST['witnessId'][$index] : null;
                    }
                } else if ($witnessType === 'staff') {
                    $witnessEmail = isset($_POST['witnessEmail'][$index]) ? $_POST['witnessEmail'][$index] : null;
                }
                
                $stmt_witness->bind_param("sssss",
                    $incident_report_id,
                    $witnessType,
                    $witnessId,
                    $witnessName,
                    $witnessEmail
                );
                $stmt_witness->execute();
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Incident report submitted successfully.',
            'report_id' => $incident_report_id
        ]);
        exit;
    }
}


// Get current date and time for the form
$currentDateTime = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report Submission</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style> 

     :root {
    --primary-color: #1A6E47;
    --primary-hover: #145A3A;
    --secondary-color: #F4A261;
    --secondary-hover: #E76F51;
    --background-color: #f5f7f9;
    --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}
body {
        background: linear-gradient(135deg,  #0d693e,#004d4d);
        min-height: 100vh;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: var(--text-color);
        margin: 0;
        padding: 0;
    }


/* Main Content */
.main-content {
    min-height: 100vh;
}

.container {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 900px; /* Added this line to limit width */
            width: 90%; /* Added this line to make it responsive */
            margin-left: auto; /* Added for center alignment */
            margin-right: auto; /* Added for center alignment */
}

/* Form Elements */
.form-group {
    margin-bottom: 1.5rem;
    position: relative;
}

.form-control {
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    padding: 5px 11px;
    transition: var(--transition);
    font-size: 0.95rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(26, 110, 71, 0.1);
    outline: none;
}

.form-group label {
    color: var(--primary-color);
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.95rem;
}

/* Buttons */
.btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: var(--transition);
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
            background-color: #1A6E47;
            border-color: #1A6E47;
            padding: 10px 20px;
        }

.btn-primary:hover {
    background-color: #145A3A;
    border-color: #145A3A;
}
.btn-secondary {
    background-color: #F4A261;
    border-color: #F4A261;
    color: #fff;
    padding: 10px 20px;
}

.btn-secondary:hover {
    background-color: #E76F51;
    border-color: #E76F51;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
    transform: translateY(-1px);
}

/* Person Involved & Witness Entries */
.person-involved-entry, .witness-entry {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    border: 1px solid #e1e5e9;
    transition: var(--transition);
}

.person-involved-entry:hover, .witness-entry:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* File Upload */
.form-control-file {
    padding: 0.5rem;
    border: 2px dashed #e1e5e9;
    border-radius: 8px;
    width: 100%;
    cursor: pointer;
}

.form-control-file:hover {
    border-color: var(--primary-color);
}

/* Headings */
h2 {
    color: var(--primary-color);
    font-weight: 600;
    position: relative;
    padding-bottom: 1rem;
    margin-bottom: 2rem;
}

h2:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    border-radius: 3px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 60px 20px 20px;
    }
    
    .container {
        padding: 1.5rem;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
        margin-bottom: 0.5rem;
    }
}

/* Animation for new entries */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.person-involved-entry, .witness-entry {
    animation: slideDown 0.3s ease-out;
}

/* Icons */
.fas, .far {
    color: white;
    margin-right: 0.5rem;
}
.arrow{
    color:black;
}

/* Select Styling */
select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%231A6E47' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    padding-right: 2.5rem;
}

/* Readonly Input Styling */
input[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

/* Textarea Styling */
textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.alert {
    border-radius: 10px;
    margin-bottom: 25px;
    padding: 16px 20px;
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
        padding-top: 70px;
    }

    .container {
        padding: 25px;
    }

    .btn {
        width: 100%;
        margin-bottom: 10px;
    }

    h2 {
        font-size: 1.5rem;
    }
}
/* Back Button*/
.modern-back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #2EDAA8;
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(46, 218, 168, 0.15);
    letter-spacing: 0.3px;
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(46, 218, 168, 0.15);
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}

.swal2-title {
    border-bottom: none !important;  /* Remove the bottom border */
    padding-bottom: 0 !important;    /* Remove any bottom padding that might create space */
    margin-bottom: 0.5em !important; /* Maintain proper spacing */
}

/* If the line still persists, you might need to override any inherited styles */
.swal2-popup h2.swal2-title::after,
.swal2-popup h2.swal2-title::before {
    display: none !important;
}

    </style>

</head>
<body>

    <main class="main-content">
    <div class="container">
        <a href="student_homepage.php" class="modern-back-button">
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
                placeholder="Student ID" 
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
                <label for="fileUpload"><i class="fas fa-file-upload"></i> Upload File or Picture:</label>
               <input type="file" class="form-control-file" id="fileUpload" name="fileUpload" accept=".jpg,.jpeg,.png">
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </div>
        </form>
    </div>

<script type="text/javascript" src="incident_report_form.js"></script>

</body>
</html>