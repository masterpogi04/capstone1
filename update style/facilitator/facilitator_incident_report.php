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
            --primary: #0f6a1a;
            --primary-hover: #218838;
            --header: #ff9042;
            --header-hover: #ff7d1a;
            --background: #f8f9fa;
            --border-color: #e9ecef;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background:  linear-gradient(135deg, #0d693e, #004d4d);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(to right, var(--header), var(--header-hover));
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header-title {
            font-size: 1.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
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


        .btn-custom {
            background: linear-gradient(to right, var(--primary), var(--primary-hover));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(15, 106, 26, 0.15);
        }

        .search-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .search-input-group {
            display: flex;
            gap: 1rem;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 0 1px #e9ecef;
            margin-top: 1.5rem;
        }

        .table thead th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-group .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            margin: 0 0.25rem;
        }

        .btn-info {
            background: #17a2b8;
            border: none;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .footer {
            background: var(--header);
            color: white;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }

            .search-input-group {
                flex-direction: column;
            }

            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-group .btn {
                width: 100%;
                margin: 0.25rem 0;
            }

            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

    <div class="content-wrapper">
       

        <div class="content">
        <div class="action-buttons">
            <a href="facilitator_homepage.php" class="modern-back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
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
            url: 'get_student_info-facilitator.php',
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