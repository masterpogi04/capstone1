<?php
session_start();
include '../db.php';

// Check if the user is logged in as a guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guard') {
    header("Location: login.php");
    exit();
}

$guard_id = $_SESSION['user_id'];

// Fetch guard's name
$stmt = $connection->prepare("SELECT CONCAT(first_name, ' ', COALESCE(middle_initial, ''), ' ', last_name) as name FROM tbl_guard WHERE id = ?");
$stmt->bind_param("i", $guard_id);
$stmt->execute();
$result = $stmt->get_result();
$guard = $result->fetch_assoc();
$guard_name = $guard['name'];
$reportedByType = $_SESSION['user_type'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date_reported = date('Y-m-d H:i:s');
    $place = $_POST['place'];
    $description = $_POST['description'];
    $reported_by = $guard_name;
    
    // Initialize flag for CEIT student validation
    $has_valid_ceit_student = false;
    
    // Validate that at least one involved person is a CEIT student
    foreach ($_POST['personsInvolvedId'] as $studentId) {
        if (!empty($studentId)) {
            // Check if student exists in tbl_student
            $stmt_check = $connection->prepare("SELECT student_id FROM tbl_student WHERE student_id = ?");
            $stmt_check->bind_param("s", $studentId);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $has_valid_ceit_student = true;
                break;
            }
        }
    }
    
    if (!$has_valid_ceit_student) {
        echo json_encode(['success' => false, 'message' => 'At least one CEIT student must be involved in the incident report.']);
        exit();
    }
    
    // Handle file upload
    $file_path = null;
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] == 0) {
        $upload_dir = '../../uploads/incident_reports_proof/';
        $file_name = uniqid() . '_' . $_FILES['fileUpload']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (move_uploaded_file($_FILES['fileUpload']['tmp_name'], $file_path)) {
            $file_path = '../../uploads/incident_reports_proof/' . $file_name;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            exit();
        }
    }
    
    // Start transaction
    $connection->begin_transaction();
    
    try {
        // Insert into pending_incident_reports table
        $stmt = $connection->prepare("INSERT INTO pending_incident_reports (guard_id, date_reported, place, description, reported_by, reported_by_type, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $guard_id, $date_reported, $place, $description, $reported_by, $reportedByType, $file_path);
        
        if ($stmt->execute()) {
            $pending_report_id = $stmt->insert_id;
            
            // Insert involved students with validation
            $stmt_student = $connection->prepare("INSERT INTO pending_student_violations (pending_report_id, student_id, student_name) VALUES (?, ?, ?)");
            foreach ($_POST['personsInvolvedId'] as $index => $studentId) {
                $studentName = $_POST['personsInvolved'][$index];
                
                // Only insert student ID if it exists in tbl_student
                if (!empty($studentId)) {
                    $stmt_check = $connection->prepare("SELECT student_id FROM tbl_student WHERE student_id = ?");
                    $stmt_check->bind_param("s", $studentId);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows > 0) {
                        $stmt_student->bind_param("iss", $pending_report_id, $studentId, $studentName);
                    } else {
                        $stmt_student->bind_param("iss", $pending_report_id, null, $studentName);
                    }
                } else {
                    $stmt_student->bind_param("iss", $pending_report_id, null, $studentName);
                }
                $stmt_student->execute();
            }
            
            // Insert witnesses with validation for student witnesses
            $stmt_witness = $connection->prepare("INSERT INTO pending_incident_witnesses (pending_report_id, witness_type, witness_id, witness_name) VALUES (?, ?, ?, ?)");
            foreach ($_POST['witnessType'] as $index => $witnessType) {
                $witnessId = null;
                if ($witnessType === 'student' && !empty($_POST['witnessId'][$index])) {
                    // Validate student witness ID
                    $stmt_check = $connection->prepare("SELECT student_id FROM tbl_student WHERE student_id = ?");
                    $stmt_check->bind_param("s", $_POST['witnessId'][$index]);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows > 0) {
                        $witnessId = $_POST['witnessId'][$index];
                    }
                }
                
                $witnessName = $_POST['witnesses'][$index];
                $stmt_witness->bind_param("isss", $pending_report_id, $witnessType, $witnessId, $witnessName);
                $stmt_witness->execute();
            }

            // Add notification for the dean
            $notification_message = "A new incident report has been submitted by " . $guard_name . " that requires your review.";
            $notification_link = "dean_view_incident_reports_from-Guards.php";

            // Insert notification for dean
            $stmt_notification = $connection->prepare("INSERT INTO notifications (user_type, message, link, is_read, created_at) VALUES ('dean', ?, ?, 0, NOW())");
            $stmt_notification->bind_param("ss", $notification_message, $notification_link);
            $stmt_notification->execute();
            
            // Commit transaction
            $connection->commit();
            echo json_encode(['success' => true, 'message' => 'Incident report submitted successfully']);
            exit();
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to submit report: ' . $e->getMessage()]);
        exit();
    }
}
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
</head>
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
                                    placeholder="Name" required>
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
                        <select class="form-control mb-2" name="witnessType[]" onchange="toggleStudentIdField(this)" required>
                            <option value="">Select Witness Type</option>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                        </select>
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control mb-2" 
                                    name="witnessId[]" 
                                    placeholder="Student ID (if student)"
                                    onkeyup="validateStudentIdInput(this)" 
                                    onchange="fetchWitnessInfo(this)">
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control mb-2" 
                                    name="witnesses[]" 
                                    placeholder="Name" required>
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

    const studentIds = [];
    let hasValidStudent = false;

    // Collect all non-empty student IDs from persons involved
    $('input[name="personsInvolvedId[]"]').each(function() {
        if ($(this).val()) {
            studentIds.push($(this).val());
        }
    });

    // Validate if at least one CEIT student is involved
    if (studentIds.length > 0) {
        for (const studentId of studentIds) {
            const isValid = await validateStudentId(studentId);
            if (isValid) {
                hasValidStudent = true;
                break; // Exit loop once we find one valid CEIT student
            }
        }
    }

    if (!hasValidStudent && studentIds.length > 0) {
        Swal.fire({
            title: 'Error',
            text: 'At least one CEIT Student must be involved to submit this report.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

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
                                // Reset form and reload
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
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while submitting the report.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
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
                    placeholder="Name" required>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
}

// Previous fetchWitnessInfo function:
function fetchWitnessInfo(input) {
    if (input.closest('.witness-entry').querySelector('select[name="witnessType[]"]').value === 'student') {
        const studentId = input.value;
        const nameInput = input.closest('.witness-entry').querySelector('input[name="witnesses[]"]');
        
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
                        } else {
                            // Clear the name field if student not found
                            nameInput.value = '';
                            // Optionally show an error message
                            Swal.fire({
                                title: 'Student Not Found',
                                text: 'No student found with this ID.',
                                icon: 'warning',
                                confirmButtonText: 'OK'
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        nameInput.value = '';
                    }
                },
                error: function() {
                    console.error('Error fetching student info');
                    nameInput.value = '';
                }
            });
        } else {
            // Clear the name field if student ID is empty
            nameInput.value = '';
        }
    }
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
function fetchWitnessInfo(input) {
    if (input.closest('.witness-entry').querySelector('select[name="witnessType[]"]').value === 'student') {
        const studentId = input.value;
        const nameInput = input.closest('.witness-entry').querySelector('input[name="witnesses[]"]');
        
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
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                },
                error: function() {
                    console.error('Error fetching student info');
                }
            });
        }
    }
}

function removeEntry(button) {
    button.closest('.person-involved-entry, .witness-entry').remove();
}


function validateStudentIdInput(input) {
    // Remove any non-numeric characters
    let value = input.value.replace(/[^0-9]/g, '');
    
    // Limit to 9 digits
    if (value.length > 9) {
        value = value.slice(0, 9);
    }
    
    input.value = value;
}

function fetchStudentInfo(input) {
    const studentId = input.value;
    const nameInput = input.closest('.person-involved-entry').querySelector('input[name="personsInvolved[]"]');
    
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
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            },
            error: function() {
                console.error('Error fetching student info');
            }
        });
    }
}
</script>
</body>
</html>