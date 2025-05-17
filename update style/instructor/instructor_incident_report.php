<?php
session_start();
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
    // Get the current academic year (assuming it starts in September)
    $currentMonth = date('n');
    $currentYear = date('Y');
    $academicYear = ($currentMonth >= 9) ? $currentYear : $currentYear - 1;
    $nextYear = $academicYear + 1;
    $academicYearShort = substr($academicYear, 2) . '-' . substr($nextYear, 2);

    // Get the latest sequential number
    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(id, '-', -1) AS UNSIGNED)) as max_seq 
              FROM incident_reports 
              WHERE id LIKE 'CEIT-{$academicYearShort}-%'";
    $result = $connection->query($query);
    if (!$result) {
        die("Error in sequence query: " . $connection->error);
    }
    $row = $result->fetch_assoc();
    $nextSeq = ($row['max_seq'] ?? 0) + 1;

    // Format the ID: CEIT-YY-YY-XXXX (e.g., CEIT-23-24-0001)
    return sprintf("CEIT-%s-%04d", $academicYearShort, $nextSeq);
}

// Fetch reporter's name based on user type with proper name fields
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dateReported = $_POST['dateReported'];
    $place = $_POST['place'];
    $description = $_POST['description'];
    $reportedBy = $reporter_name; // Use the concatenated name
    $reportedByType = $_SESSION['user_type'];
    
    // Generate the new formatted ID for this incident report
    $incident_report_id = generateIncidentReportId($connection);
    
    // Handle file upload
    $uploadFile = null;
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == 0) {
        $uploadDir = '../../uploads/incident_reports_proof/';
        $uploadFile = $uploadDir . basename($_FILES['fileUpload']['name']);
        
        // Ensure the upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $uploadFile)) {
            $uploadFile = '../../uploads/incident_reports_proof/' . basename($_FILES['fileUpload']['name']);
        } else {
            $error_message = "Failed to upload file.";
        }
    }
    
    // Insert into incident_reports table with the generated ID
    $stmt = $connection->prepare("INSERT INTO incident_reports (id, date_reported, place, description, reported_by, reporters_id, reported_by_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing insert statement: " . $connection->error);
    }
    
    $stmt->bind_param("ssssssss", $incident_report_id, $dateReported, $place, $description, $reportedBy, $reporter_id, $reportedByType, $uploadFile);
    
    if ($stmt->execute()) {
        // Insert student violations with the generated incident report ID
        $stmt_violation = $connection->prepare("INSERT INTO student_violations (student_id, incident_report_id, violation_date, status) VALUES (?, ?, ?, 'pending')");
        if (!$stmt_violation) {
            die("Error preparing violation statement: " . $connection->error);
        }

        foreach ($_POST['personsInvolvedId'] as $studentId) {
            $stmt_violation->bind_param("sss", $studentId, $incident_report_id, $dateReported);
            $stmt_violation->execute();
        }
        
        // Insert witnesses with the generated incident report ID
        $stmt_witness = $connection->prepare("INSERT INTO incident_witnesses (incident_report_id, witness_type, witness_id, witness_name, witness_email) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt_witness) {
            die("Error preparing witness statement: " . $connection->error);
        }

        foreach ($_POST['witnessType'] as $index => $witnessType) {
            $witnessId = ($witnessType === 'student') ? $_POST['witnessId'][$index] : null;
            $witnessName = $_POST['witnesses'][$index];
            $witnessEmail = ($witnessType === 'staff') ? $_POST['witnessEmail'][$index] : null;
            $stmt_witness->bind_param("sssss", $incident_report_id, $witnessType, $witnessId, $witnessName, $witnessEmail);
            $stmt_witness->execute();
        }
        
        echo json_encode(['success' => true, 'message' => 'Incident report submitted successfully.', 'report_id' => $incident_report_id]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Error submitting report. Please try again.']);
        exit;
    }
}

// Get current date and time
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
        background: #009E60;
        min-height: 100vh;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: var(--text-color);
        margin: 0;
        padding: 0;
    }


/* Main Content */
.main-content {
    padding: 80px 40px 40px;
    min-height: 100vh;
}

.container {
        background-color: rgba(255, 255, 255, 0.98);
        border-radius: 15px;
        padding: 40px;
        margin: 50px auto;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
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

    </style>

</head>
<body>
    
    <main class="main-content">
    <div class="header1">

        <div class="container">
        <a href="instructor_homepage.php" class="modern-back-button">
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
                <input type="text" class="form-control mb-2 student-id" name="personsInvolvedId[]" placeholder="Student ID" required>
                <input type="text" class="form-control mb-2 student-name" name="personsInvolved[]" placeholder="Name" readonly>
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
                    <input type="text" class="form-control mb-2 student-field student-id" name="witnessId[]" placeholder="Student ID" style="display:none;">
                    <input type="text" class="form-control mb-2 witness-name" name="witnesses[]" placeholder="Name" readonly>
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
                <input type="file" class="form-control-file" id="fileUpload" name="fileUpload" accept="image/*,.pdf,.doc,.docx">
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </div>
        </form>
    </div>

<script>
function addPersonInvolved() {
    var container = document.getElementById('personsInvolvedContainer');
    var div = document.createElement('div');
    div.className = 'person-involved-entry';
    div.innerHTML = `
        <input type="text" class="form-control mb-2 student-id" name="personsInvolvedId[]" placeholder="Student ID" required>
        <input type="text" class="form-control mb-2 student-name" name="personsInvolved[]" placeholder="Name" readonly>
        <input type="text" class="form-control mb-2 student-year-course" name="personsInvolvedYearCourse[]" placeholder="Year & Course" readonly>
        <input type="text" class="form-control mb-2" name="personsInvolvedAdviser[]" placeholder="Registration Adviser" readonly>
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
    attachStudentIdListener(div.querySelector('.student-id'));
}

function addWitnessField() {
    var container = document.getElementById('witnessesContainer');
    var div = document.createElement('div');
    div.className = 'witness-entry';
    div.innerHTML = `
        <select class="form-control mb-2 witness-type" name="witnessType[]" onchange="toggleWitnessFields(this)" required>
            <option value="">Select Witness Type</option>
            <option value="student">Student</option>
            <option value="staff">Staff</option>
        </select>
        <input type="text" class="form-control mb-2 student-field student-id" name="witnessId[]" placeholder="Student ID" style="display:none;">
        <input type="text" class="form-control mb-2 witness-name" name="witnesses[]" placeholder="Name" readonly>
        <input type="text" class="form-control mb-2 student-field student-year-course" name="witnessesYearCourse[]" placeholder="Year & Course" style="display:none;" readonly>
        <input type="text" class="form-control mb-2 student-field" name="witnessesAdviser[]" placeholder="Registration Adviser" style="display:none;" readonly>
        <input type="email" class="form-control mb-2 staff-field" name="witnessEmail[]" placeholder="Email" style="display:none;">
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
    attachStudentIdListener(div.querySelector('.student-id'));
}

function removeEntry(button) {
    button.closest('.person-involved-entry, .witness-entry').remove();
}

function toggleWitnessFields(select) {
    var entry = select.closest('.witness-entry');
    var studentFields = entry.querySelectorAll('.student-field');
    var staffFields = entry.querySelectorAll('.staff-field');
    var nameField = entry.querySelector('.witness-name');
    
    if (select.value === 'student') {
        studentFields.forEach(field => field.style.display = 'block');
        staffFields.forEach(field => field.style.display = 'none');
        nameField.readOnly = true;
    } else if (select.value === 'staff') {
        studentFields.forEach(field => field.style.display = 'none');
        staffFields.forEach(field => field.style.display = 'block');
        nameField.readOnly = false;
    } else {
        studentFields.forEach(field => field.style.display = 'none');
        staffFields.forEach(field => field.style.display = 'none');
        nameField.readOnly = true;
    }
}

function attachStudentIdListener(input) {
    $(input).on('blur', function() {
        var studentId = $(this).val();
        var entry = $(this).closest('.person-involved-entry, .witness-entry');
        
        $.ajax({
            url: 'get_student_info.php',
            method: 'POST',
            data: { student_id: studentId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    entry.find('.student-name, .witness-name').val(response.name);
                    entry.find('.student-year-course').val(response.year_course);
                    entry.find('[name="personsInvolvedAdviser[]"], [name="witnessesAdviser[]"]').val(response.adviser);
                } else {
                    Swal.fire({
                        title: 'Student Not Found',
                        text: 'No student found with the provided ID. Please check and try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    entry.find('.student-name, .witness-name, .student-year-course, [name="personsInvolvedAdviser[]"], [name="witnessesAdviser[]"]').val('');
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while fetching student information. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
}

function validateTime() {
    var selectedDate = new Date($('#incidentDate').val());
    var selectedTime = $('#incidentTime').val();
    var today = new Date();
    
    // Reset time parts for date comparison
    selectedDate.setHours(0, 0, 0, 0);
    var todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    // If selected date is today, check if time is future
    if (selectedDate.getTime() === todayDate.getTime()) {
        var currentTime = today.toLocaleTimeString('en-US', { hour12: false });
        
        if (selectedTime > currentTime) {
            Swal.fire({
                title: 'Invalid Time',
                text: 'You cannot select a future time for today\'s date.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            $('#incidentTime').val('');
            return false;
        }
    }
    return true;
}

function updateCombinedField() {
    var place = $('#incidentPlace').val();
    var date = $('#incidentDate').val();
    var time = $('#incidentTime').val();
    
    if (place && date && time) {
        if (validateTime()) {
            var formattedDate = new Date(date + 'T' + time).toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
            $('#place').val(place + ' - ' + formattedDate);
        }
    }
}

function setMaxDate() {
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var yyyy = today.getFullYear();

    today = yyyy + '-' + mm + '-' + dd;
    $('#incidentDate').attr('max', today);
}

$(document).ready(function() {
    setMaxDate();
    
    // Attach event listeners to update combined field
    $('#incidentPlace, #incidentDate').on('change', updateCombinedField);
    $('#incidentTime').on('change', function() {
        if (validateTime()) {
            updateCombinedField();
        }
    });

    // Attach listeners to initial fields
    attachStudentIdListener($('.student-id'));

    // Submit form with SweetAlert2 confirmation
    $('#incidentReportForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateTime()) {
            return false;
        }
        
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
                var formData = new FormData(this);
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Submitted!',
                                response.message,
                                'success'
                            ).then(() => {
                                $('#incidentReportForm')[0].reset();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'An error occurred while submitting the report.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>