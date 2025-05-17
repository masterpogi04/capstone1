<?php
session_start();
include '../db.php';

// Check if the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

function createNotification($connection, $user_type, $user_id, $message, $link) {
    $query = "INSERT INTO notifications (user_type, user_id, message, link) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $user_type, $user_id, $message, $link);
    return $stmt->execute();
}

// Get facilitator details
$facilitator_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$facilitator = $result->fetch_assoc();
$facilitator_name = trim($facilitator['first_name'] . ' ' . $facilitator['middle_initial'] . ' ' . $facilitator['last_name']);

// Get counselor details with concatenated name
$counselor_query = "SELECT 
    CONCAT(first_name, ' ', COALESCE(middle_initial, ''), ' ', last_name) AS full_name 
    FROM tbl_counselor LIMIT 1";
$counselor_result = $connection->query($counselor_query);
$counselor = $counselor_result->fetch_assoc();
$counselor_name = $counselor ? trim($counselor['full_name']) : 'Guidance Counselor';

if (!isset($_GET['report_id'])) {
    echo "No incident report selected.";
    exit();
}

$report_id = $connection->real_escape_string($_GET['report_id']);

// Check which students have already been referred
$referred_students_query = "SELECT student_id FROM referrals WHERE incident_report_id = ?";
$stmt = $connection->prepare($referred_students_query);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$referred_result = $stmt->get_result();
$referred_students = [];
while ($row = $referred_result->fetch_assoc()) {
    $referred_students[] = $row['student_id'];
}

// Fetch students involved in this incident report who haven't been referred yet
$students_query = "SELECT sv.student_id, s.first_name, s.middle_name, s.last_name, 
                          c.name as course, s.section_id, sec.year_level,
                          CONCAT(s.first_name, ' ', s.last_name) as full_name,
                          sv.student_name, sv.student_course, sv.student_year_level
                   FROM student_violations sv
                   LEFT JOIN tbl_student s ON sv.student_id = s.student_id
                   LEFT JOIN sections sec ON s.section_id = sec.id
                   LEFT JOIN courses c ON sec.course_id = c.id
                   WHERE sv.incident_report_id = ? 
                   AND (sv.student_id NOT IN (
                       SELECT IFNULL(student_id, '') FROM referrals 
                       WHERE incident_report_id = ?
                   ) OR sv.student_id IS NULL)";

$stmt = $connection->prepare($students_query);
$stmt->bind_param("ss", $report_id, $report_id);
$stmt->execute();
$students_result = $stmt->get_result();

// Fetch incident report details
$incident_query = "SELECT * FROM incident_reports WHERE id = ?";
$stmt = $connection->prepare($incident_query);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$incident_result = $stmt->get_result();
$incident_details = $incident_result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $student_id = $_POST['student_id'] !== '' ? $_POST['student_id'] : null;
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $course_year = $_POST['course_year'];
    $reason_for_referral = $_POST['referralReason'];
    $violation_details = '';
    $other_concerns = '';

    switch ($reason_for_referral) {
        case 'academicConcern':
            $reason_for_referral = 'Academic concern';
            break;
        case 'behavioralMaladjustment':
            $reason_for_referral = 'Behavior maladjustment';
            break;
        case 'violation':
            $reason_for_referral = 'Violation to school rules';
            $violation_details = $_POST['violation_details'];
            break;
        case 'otherConcerns':
            $reason_for_referral = 'Other concern';
            $other_concerns = $_POST['other_concerns'];
            break;
    }

    $sql = "INSERT INTO referrals (date, student_id, first_name, middle_name, last_name, 
            course_year, reason_for_referral, violation_details, other_concerns, 
            faculty_name, acknowledged_by, incident_report_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ssssssssssss", 
        $date, $student_id, $first_name, $middle_name, $last_name,
        $course_year, $reason_for_referral, $violation_details, $other_concerns,
        $facilitator_name, $_POST['acknowledged_by'], $report_id
    );
    
    if ($stmt->execute()) {
        $referral_id = $connection->insert_id;

        // Get adviser information for the student if student_id exists
        if ($student_id) {
            $adviser_query = "SELECT a.id as adviser_id
                             FROM tbl_student s
                             JOIN sections sec ON s.section_id = sec.id
                             JOIN tbl_adviser a ON sec.adviser_id = a.id
                             WHERE s.student_id = ?";
            $adviser_stmt = $connection->prepare($adviser_query);
            $adviser_stmt->bind_param("s", $student_id);
            $adviser_stmt->execute();
            $adviser_result = $adviser_stmt->get_result();
            $adviser_info = $adviser_result->fetch_assoc();

            // Create notification for student
            createNotification(
                $connection,
                'student',
                $student_id,
                "Your incident report has been referred to the Guidance Counselor.",
                "view_referral_details.php?id=" . $referral_id
            );

            // Create notification for adviser if exists
            if (isset($adviser_info['adviser_id'])) {
                createNotification(
                    $connection,
                    'adviser',
                    $adviser_info['adviser_id'],
                    "An incident report for your student has been referred to the Guidance Counselor.",
                    "view_referral_details.php?id=" . $referral_id
                );
            }
        }

        // Notify all counselors
        $counselor_query = "SELECT id FROM tbl_counselor";
        $counselor_result = $connection->query($counselor_query);
        while ($counselor = $counselor_result->fetch_assoc()) {
            createNotification(
                $connection,
                'counselor',
                $counselor['id'],
                "A new incident report has been referred to you.",
                "view_referral_details.php?id=" . $referral_id
            );
        }

        // Check if all students have been referred
        $check_query = "SELECT COUNT(*) as total, 
                       (SELECT COUNT(*) FROM referrals WHERE incident_report_id = ?) as referred
                       FROM student_violations WHERE incident_report_id = ?";
        $check_stmt = $connection->prepare($check_query);
        $check_stmt->bind_param("ss", $report_id, $report_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        
        if ($check_result['total'] == $check_result['referred']) {
            $update_stmt = $connection->prepare("UPDATE incident_reports SET status = 'Referred' WHERE id = ?");
            $update_stmt->bind_param("s", $report_id);
            $update_stmt->execute();
        }
        
        echo '<script type="text/javascript">
            alert("Referral submitted successfully!");
            window.location.href = "view_approved_reports.php";
        </script>';
    } else {
        echo '<script type="text/javascript">
            alert("Error submitting referral.");
        </script>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Form - Incident Report</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    /* Global Styles */
body {
    background: linear-gradient(135deg, #0d693e, #004d4d);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    margin: 0;
    padding-top: 80px;
    color: #2d3748;
    line-height: 1.6;
}

/* Header Styles */
.header {
    background-color: #ff9042;
    padding: 1rem;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 600;
    position: fixed;
    right: 0;
    top: 0;
    width: 100%;
    color: white;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Container Styles */
.container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.form-container {
    background-color: #ffffff;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    animation: slideUp 0.3s ease-out;
}

/* Form Section Styles */
.form-group {
    margin-bottom: 1.5rem;
    animation: fadeIn 0.3s ease-out;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #2d3748;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #0d693e;
    box-shadow: 0 0 0 3px rgba(13, 105, 62, 0.1);
    outline: none;
}

.form-control:disabled,
.form-control[readonly] {
    background-color: #f7fafc;
    cursor: not-allowed;
}

/* Select Styles */
select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1em;
    padding-right: 2.5rem;
}

/* Radio Button Styles */
.form-check {
    margin-bottom: 0.75rem;
    padding-left: 1.75rem;
    position: relative;
}

.form-check-input {
    position: absolute;
    left: 0;
    top: 0.25rem;
    margin: 0;
}

.form-check-label {
    margin-bottom: 0;
    cursor: pointer;
}
/* Back Button */
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
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}

/* Button Styles */
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
    font-size: 1rem;
}

.btn-primary {
    background-color: #0d693e;
    border: none;
    color: white;
}

.btn-primary:hover {
    background-color: #0a5432;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 105, 62, 0.2);
}

/* Detail Section Styles */
h3 {
    color: #1a365d;
    margin-bottom: 1.5rem;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
}

/* Additional Input Groups */
#violationDetailsGroup,
#otherConcernsGroup {
    margin-top: 0.75rem;
    margin-left: 1.75rem;
    animation: fadeIn 0.3s ease-out;
}

/* Animations */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Responsive Styles */
@media (max-width: 768px) {
    body {
        padding-top: 70px;
    }

    .header {
        font-size: 1.25rem;
        padding: 0.75rem;
    }

    .form-container {
        padding: 1.5rem;
        margin: 1rem;
    }

    .btn {
        width: 100%;
    }

    .form-group {
        margin-bottom: 1rem;
    }
}

/* Sweet Alert Customization */
.swal2-popup {
    border-radius: 16px;
    padding: 2rem;
}

.swal2-title {
    color: #1a365d !important;
    font-size: 1.5rem !important;
}

.swal2-content {
    color: #4a5568 !important;
}

.swal2-actions {
    margin-top: 1.5rem !important;
}

/* Form Validation Styles */
.form-control.is-invalid {
    border-color: #e53e3e;
    padding-right: 2.5rem;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23e53e3e' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23e53e3e' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1rem;
}
    </style>
</head>
<body>
    <div class="header">REFERRAL FORM - INCIDENT REPORT</div>
    <div class="container">
        <div class="form-container">
        <a href="view_approved_reports.php" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>
            <h3>Incident Report Details</h3>
            <p><strong>Incident Description:</strong> <?php echo htmlspecialchars($incident_details['description']); ?></p>
            
            <?php if ($students_result->num_rows > 0): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="student_select">Select Student to Refer:</label>
                        <select id="student_select" class="form-control" required>
                            <option value="">Choose a student</option>
                            <?php 
                            $students_result->data_seek(0);
                            while ($student = $students_result->fetch_assoc()): 
                                $display_name = $student['first_name'] ? 
                                    $student['first_name'] . ' ' . $student['last_name'] :
                                    $student['student_name'];
                            ?>
                                <option value="<?php echo htmlspecialchars($student['student_id'] ?? 'null'); ?>">
                                    <?php echo htmlspecialchars($display_name); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="referralForm" style="display: none;">
                        <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">
                        <input type="hidden" name="student_id" id="student_id">
                        <input type="hidden" name="first_name" id="first_name">
                        <input type="hidden" name="middle_name" id="middle_name">
                        <input type="hidden" name="last_name" id="last_name">

                        <div class="form-group">
                            <label>Student Name:</label>
                            <input type="text" class="form-control" id="student_name" readonly>
                        </div>

                        <div class="form-group">
                            <label>Course/Year:</label>
                            <input type="text" class="form-control" id="course_year" name="course_year" readonly>
                        </div>

                        <div class="form-group">
                            <label>Reason for Referral:</label>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="academicConcern" name="referralReason" value="academicConcern" required>
                                <label class="form-check-label" for="academicConcern">Academic concern</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="behavioralMaladjustment" name="referralReason" value="behavioralMaladjustment">
                                <label class="form-check-label" for="behavioralMaladjustment">Behavior maladjustment</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="violation" name="referralReason" value="violation">
                                <label class="form-check-label" for="violation">Violation to school rules</label>
                            </div>
                            <div class="form-group" id="violationDetailsGroup" style="display: none;">
                                <input type="text" class="form-control" name="violation_details" id="violationDetails" placeholder="Specify violation">
                            </div>
                            <div class="form-check">
                                <input type="radio" class="form-check-input" id="otherConcerns" name="referralReason" value="otherConcerns">
                                <label class="form-check-label" for="otherConcerns">Other concerns</label>
                            </div>
                            <div class="form-group" id="otherConcernsGroup" style="display: none;">
                                <input type="text" class="form-control" name="other_concerns" id="otherConcernsDetails" placeholder="Specify other concerns">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Faculty Name:</label>
                            <input type="text" class="form-control" name="faculty_name" value="<?php echo htmlspecialchars($facilitator_name); ?>" readonly>
                        </div>

                      <div class="form-group">
                            <label>Acknowledged by:</label>
                            <input type="text" class="form-control" name="acknowledged_by" value="<?php echo htmlspecialchars($counselor_name); ?>" readonly>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit Referral</button>
                    </div>
                </form>
            <?php else: ?>
                <p>All students in this incident report have already been referred or no students are associated with this report.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Students data from PHP
        const studentsData = {
            <?php 
            $students_result->data_seek(0);
            while ($student = $students_result->fetch_assoc()): 
                $student_key = $student['student_id'] ?? 'null';
                $course_year = $student['course'] ? 
                    $student['course'] . ' - ' . $student['year_level'] :
                    $student['student_course'] . ' - ' . $student['student_year_level'];
            ?>
            "<?php echo $student_key; ?>": {
                first_name: "<?php echo htmlspecialchars($student['first_name'] ?? $student['student_name']); ?>",
                middle_name: "<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>",
                last_name: "<?php echo htmlspecialchars($student['last_name'] ?? ''); ?>",
                course_year: "<?php echo htmlspecialchars($course_year); ?>"
            },
            <?php endwhile; ?>
        };

        // Handle reason for referral radio buttons
        const referralReasons = document.getElementsByName('referralReason');
        const violationDetailsGroup = document.getElementById('violationDetailsGroup');
        const otherConcernsGroup = document.getElementById('otherConcernsGroup');
        
        referralReasons.forEach(function(reason) {
            reason.addEventListener('change', function() {
                violationDetailsGroup.style.display = this.value === 'violation' ? 'block' : 'none';
                otherConcernsGroup.style.display = this.value === 'otherConcerns' ? 'block' : 'none';
            });
        });

        // Student selection handler
        const studentSelect = document.getElementById('student_select');
        studentSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedStudent = studentsData[this.value === 'null' ? 'null' : this.value];
                if (selectedStudent) {
                    document.getElementById('student_id').value = this.value === 'null' ? '' : this.value;
                    document.getElementById('first_name').value = selectedStudent.first_name;
                    document.getElementById('middle_name').value = selectedStudent.middle_name;
                    document.getElementById('last_name').value = selectedStudent.last_name;
                    document.getElementById('student_name').value = 
                        `${selectedStudent.first_name} ${selectedStudent.middle_name} ${selectedStudent.last_name}`.trim();
                    document.getElementById('course_year').value = selectedStudent.course_year;
                    document.getElementById('referralForm').style.display = 'block';
                }
            } else {
                document.getElementById('referralForm').style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>