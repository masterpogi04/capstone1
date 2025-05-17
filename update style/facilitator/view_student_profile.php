<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_GET['student_id'] ?? null;
$department_id = $_GET['department_id'] ?? null;
$course_id = $_GET['course_id'] ?? null;
$year_level = $_GET['year_level'] ?? null;
$section_id = $_GET['section_id'] ?? null;

$studentNotFound = false;

if (!$student_id) {
    header("Location: view_profiles.php");
    exit();
}

// Fetch student details
$stmt = $connection->prepare("
    SELECT sp.*, s.department_id, s.course_id, d.name as department_name, c.name as course_name, s.section_no
    FROM student_profiles sp
    JOIN tbl_student ts ON sp.student_id = ts.student_id
    JOIN sections s ON ts.section_id = s.id
    JOIN departments d ON s.department_id = d.id
    JOIN courses c ON s.course_id = c.id
    WHERE sp.student_id = ?
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $studentNotFound = true;
}

// Fetch facilitator name
$facilitator_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$facilitator = $result->fetch_assoc();

// Construct full name from components
$facilitator_name = trim($facilitator['first_name'] . ' ' . 
    ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
    $facilitator['last_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
   
</head>
<style>
         body {
            font-family: Arial, sans-serif;
            background-color: #FAF3E0;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #F4A261;
            color: black;
            padding: 10px;
            text-align: left;
            font-size: 24px;
            font-weight: bold;
        }
        .welcome-banner {
            background-color: #1A6E47;
            color: white;
            padding: 20px;
            text-align: left;
            font-size: 36px;
            font-weight: bold;
        }
        .content {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #1A6E47;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            color: #004d4d;
        }
        .readonly-input {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
       .btn-back {
            align-self: flex-start;
            background-color: #F4A261;
            color: black;
            border: none;
            padding: 10px 20px;
            font-size: 18px;
            cursor: pointer;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .btn-back:hover {
            background-color: #e76f51;
        }
        .footer {
            background-color: #F4A261;
            color: black;
            text-align: center;
            padding: 10px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        .signature-container {
            margin-top: 20px;
            text-align: center;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
        }
        .signature-image {
            max-width: 300px;
            max-height: 100px;
            margin-bottom: 10px;
        }
        .editable-input {
            background-color: #fff;
            cursor: text;
        }
        .edit-buttons {
            margin-top: 20px;
            text-align: right;
        }
        .edit-buttons button {
            margin-left: 10px;
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            width: 100%;
            height: 100%;
            overflow: hidden;
            outline: 0;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-dialog {
            position: relative;
            width: auto;
            margin: 0.5rem;
            pointer-events: none;
            max-width: 500px;
            margin: 1.75rem auto;
        }
        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0,0,0,.2);
            border-radius: 0.3rem;
            outline: 0;
        }
        .modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            border-top-left-radius: calc(0.3rem - 1px);
            border-top-right-radius: calc(0.3rem - 1px);
        }
        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }
    </style>
<body>
    <div class="header">
        CEIT - GUIDANCE OFFICE
        <i class="fas fa-bell float-right" style="font-size:24px;"></i>
    </div>

    <br>
    <a href="view_select_sections.php?department_id=<?php echo urlencode($department_id); ?>&course_id=<?php echo urlencode($course_id); ?>&year_level=<?php echo urlencode($year_level); ?>&section_id=<?php echo urlencode($section_id); ?>" class="btn btn-back">
        <i class="fas fa-arrow-left"></i> Back to Student List
    </a>
    <div class="content">
        <?php if (!$studentNotFound): ?>
            <h1>Student Profile Form For Inventory</h1>

           <h2>Personal Information</h2>
                <div class="form-group">
                <label>Full Name:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?>" readonly>
                </div>
            <div class="form-group">
                <label>Student ID:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Department:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['department_name']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Course:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['course_name']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Permanent Address:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['permanent_address'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Current Address:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['current_address'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Contact Number:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['contact_number'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Gender:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['gender']); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Birthdate:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['birthdate'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Age:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['age'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Civil Status:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['civil_status'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Spouse's Name:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['spouse_name'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Spouse's Occupation:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['spouse_occupation'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Religion:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['religion'] ?? ''); ?>" readonly>
            </div>

            <h2>Educational Information</h2>
            <div class="form-group">
                <label>Year Level:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Semester First Enrolled:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['semester_first_enrolled'] ?? ''); ?>" readonly>
            </div>

            <h2>Family Background</h2>
            <div class="form-group">
                <label>Father's Name:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Father's Contact Number:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['father_contact'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Father's Occupation:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['father_occupation'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Mother's Name:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Mother's Contact:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['mother_contact'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Mother's Occupation:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['mother_occupation'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Guardian's Name:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Guardian's Relationship:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_relationship'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Guardian's Contact:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_contact'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Guardian's Occupation:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_occupation'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Number of Siblings:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['siblings'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Birth Order:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['birth_order'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Family Income:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['family_income'] ?? ''); ?>" readonly>
            </div>

            <h2>Educational Background</h2>
            <div class="form-group">
                <label>Elementary:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['elementary'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Secondary:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['secondary'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Transferee:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['transferees'] ?? ''); ?>" readonly>
            </div>

            <h2>Career Information</h2>
            <div class="form-group">
                <label>Course Factors:</label>
                <textarea class="form-control readonly-input" rows="3" readonly><?php echo htmlspecialchars($student['course_factors'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Career Concerns:</label>
                <textarea class="form-control readonly-input" rows="3" readonly><?php echo htmlspecialchars($student['career_concerns'] ?? ''); ?></textarea>
            </div>

            <h2>Medical History</h2>
            <div class="form-group">
                <label>Medications:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['medications'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Medical Conditions:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['medical_conditions'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Suicide Attempt:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['suicide_attempt'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Suicide Reason:</label>
                <textarea class="form-control readonly-input" rows="3" readonly><?php echo htmlspecialchars($student['suicide_reason'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Problems:</label>
                <textarea class="form-control readonly-input" rows="3" readonly><?php echo htmlspecialchars($student['problems'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Family Problems:</label>
                <textarea class="form-control readonly-input" rows="3" readonly><?php echo htmlspecialchars($student['family_problems'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Fitness Activity:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['fitness_activity'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Fitness Frequency:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['fitness_frequency'] ?? ''); ?>" readonly>
            </div>
            <div class="form-group">
                <label>Stress Level:</label>
                <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['stress_level'] ?? ''); ?>" readonly>
            </div>

            <!-- Signature Section -->
            <div class="signature-container">
                <h3>Student Signature</h3>
                <p>I hereby attest that all information stated above is true and correct.</p>
                <?php if (!empty($student['signature_path'])): ?>
                    <img src="<?php echo htmlspecialchars($student['signature_path']); ?>" alt="Student Signature" class="signature-image">
                <?php else: ?>
                    <p>No signature available</p>
                <?php endif; ?>
                <p class="student-name">
                    <?php
                    $middleInitial = !empty($student['middle_name']) ? strtoupper(substr($student['middle_name'], 0, 1)) . '.' : '';
                    $formattedName = strtoupper($student['first_name'] . ' ' . $middleInitial . ' ' . $student['last_name']);
                    echo htmlspecialchars($formattedName);
                    ?>
                </p>
            </div>

            <div class="edit-buttons">
                <a href="edit_personal_info.php?student_id=<?php echo $student_id; ?>" class="btn btn-primary">Edit Profile</a>
                <a href="view_student_profile-generate_pdf.php?student_id=<?php echo $student_id; ?>" class="btn btn-primary" target="_blank">Export to PDF</a>
            </div>

        <?php else: ?>
            <div id="errorModal" class="modal fade show" tabindex="-1" role="dialog" style="display: block;">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Error</h5>
                        </div>
                        <div class="modal-body">
                            <p>Student Profile not found. Redirecting to SELECT SECTION NUMBER page in <span id="countdown">5</span> seconds...</p>
                        </div>
                    </div>
                </div>
            </div>
       <?php endif; ?>
    </div>

    <div class="footer">
        Contact number | Email | Copyright
    </div>

    <?php if ($studentNotFound): ?>
    <div id="errorModal" class="modal fade show" tabindex="-1" role="dialog" style="display: block;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Error</h5>
                </div>
                <div class="modal-body">
                    <p>Student Profile not found. Redirecting to SELECT SECTION NUMBER page in <span id="countdown">5</span> seconds...</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // JavaScript for countdown and redirect
        document.addEventListener('DOMContentLoaded', function() {
            var seconds = 5;
            var countdownElement = document.getElementById('countdown');
            var intervalId = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(intervalId);
                    var redirectUrl = 'view_select_sections.php?' +
                        'department_id=<?php echo urlencode($department_id); ?>&' +
                        'course_id=<?php echo urlencode($course_id); ?>&' +
                        'year_level=<?php echo urlencode($year_level); ?>&' +
                        'section_id=<?php echo urlencode($section_id); ?>';
                    window.location.href = redirectUrl;
                }
            }, 1000);
        });
    </script>
</body>
</html>