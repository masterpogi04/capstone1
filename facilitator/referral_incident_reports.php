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

// Get incident report details
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

        // Check how many students remain to be referred
        $remaining_query = "SELECT COUNT(*) as remaining FROM student_violations sv
                           WHERE sv.incident_report_id = ?
                           AND NOT EXISTS (
                               SELECT 1 FROM referrals r 
                               WHERE (r.student_id = sv.student_id OR r.first_name = sv.student_name)
                               AND r.incident_report_id = sv.incident_report_id
                           )";
        $stmt = $connection->prepare($remaining_query);
        $stmt->bind_param("s", $report_id);
        $stmt->execute();
        $remaining_result = $stmt->get_result()->fetch_assoc();
        $remaining = $remaining_result['remaining'];

        // Replace this section in your code (around line 100-120)
if ($remaining > 0) {
    // For remaining students, just redirect immediately with a PHP header
    $redirect_url = "referral_incident_reports.php?report_id=$report_id";
    
    // Store success message in session to display after redirect
    $_SESSION['success_message'] = "Referral submitted successfully! There are $remaining more students to refer.";
    
    // Redirect immediately
    header("Location: $redirect_url");
    exit();
} else {
    // Update incident report status if all students referred
    $update_stmt = $connection->prepare("UPDATE incident_reports SET status = 'Referred' WHERE id = ?");
    $update_stmt->bind_param("s", $report_id);
    $update_stmt->execute();
    
    // Instead of redirecting, just set a success message and let the page reload
    $_SESSION['success_message'] = "All referrals submitted successfully!";
    
    // Redirect back to the same page, which will show the "All students referred" section
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
    } else {
        echo '<script type="text/javascript">
            Swal.fire({
                title: "Error!",
                text: "Error submitting referral.",
                icon: "error",
                confirmButtonText: "OK"
            });
        </script>';
    }
}

// Get list of unreferred students (prioritizing CEIT students)
$unreferred_query = "SELECT sv.student_id, 
                    COALESCE(CONCAT(s.first_name, ' ', s.last_name), sv.student_name) as display_name,
                    sv.student_name,
                    CASE WHEN sv.student_id IS NOT NULL THEN 1 ELSE 0 END as is_ceit,
                    COALESCE(CONCAT(c.name, ' - ', sec.year_level), 
                            CONCAT(sv.student_course, ' - ', sv.student_year_level)) as course_year,
                    COALESCE(s.first_name, sv.student_name) as first_name,
                    COALESCE(s.middle_name, '') as middle_name,
                    COALESCE(s.last_name, '') as last_name
                    FROM student_violations sv
                    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
                    LEFT JOIN sections sec ON s.section_id = sec.id
                    LEFT JOIN courses c ON sec.course_id = c.id
                    WHERE sv.incident_report_id = ?
                    AND NOT EXISTS (
                        SELECT 1 FROM referrals r 
                        WHERE (r.student_id = sv.student_id OR r.first_name = sv.student_name)
                        AND r.incident_report_id = sv.incident_report_id
                    )
                    ORDER BY is_ceit DESC, display_name ASC";
$stmt = $connection->prepare($unreferred_query);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$students_result = $stmt->get_result();

// Check if all students have been referred
$check_query = "SELECT 
    (SELECT COUNT(*) FROM student_violations WHERE incident_report_id = ?) as total_students,
    (SELECT COUNT(*) FROM referrals WHERE incident_report_id = ?) as total_referrals";
$check_stmt = $connection->prepare($check_query);
$check_stmt->bind_param("ss", $report_id, $report_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();

$all_referred = $check_result['total_students'] <= $check_result['total_referrals'];

// Get the most recent referral details to pre-populate the form
$existing_referral = null;
if (!$all_referred) {
    $existing_referral_query = "
        SELECT reason_for_referral, violation_details, other_concerns 
        FROM referrals 
        WHERE incident_report_id = ? 
        ORDER BY id DESC 
        LIMIT 1";
    $stmt = $connection->prepare($existing_referral_query);
    $stmt->bind_param("s", $report_id);
    $stmt->execute();
    $existing_referral = $stmt->get_result()->fetch_assoc();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
        background: linear-gradient(135deg, #0d693e, #004d4d);
        min-height: 100vh;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        margin: 0;
        padding-top: 80px;
        color: #2d3748;
        line-height: 1.6;
    }
    .header {
        background-color:white;
        padding: 15px;
        text-align: center;
        font-size: 28px;
        font-weight: bold;
        color: #1b651b;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
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
    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1em;
        padding-right: 2.5rem;
    }
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
    h3 {
        color: #1a365d;
        margin-bottom: 1.5rem;
        font-weight: 600;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 0.5rem;
    }
    #violationDetailsGroup,
    #otherConcernsGroup {
        margin-top: 0.75rem;
        margin-left: 1.75rem;
        animation: fadeIn 0.3s ease-out;
    }
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
        <?php 
// Check for success message in session
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
        ' . htmlspecialchars($_SESSION['success_message']) . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
    
    // Clear the message so it doesn't show again on refresh
    unset($_SESSION['success_message']);
}
?>
        <div class="form-container">
                    <a href="view_approved_reports.php" class="modern-back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <h3>Incident Report Details</h3>
            <p><strong>Incident Description:</strong> <?php echo htmlspecialchars($incident_details['description']); ?></p>
            
            <?php if (!$all_referred && $students_result->num_rows > 0): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="student_select">Select Student to Refer:</label>
                        <select id="student_select" class="form-control" required>
                            <option value="">Choose a student</option>
                            <?php 
                            $has_unreferred_ceit = false;
                            $students_result->data_seek(0);
                            while ($student = $students_result->fetch_assoc()): 
                                if ($student['is_ceit']) {
                                    $has_unreferred_ceit = true;
                                    ?>
                                    <option value="<?php echo htmlspecialchars($student['student_id']); ?>"
                                        data-firstname="<?php echo htmlspecialchars($student['first_name']); ?>"
                                        data-middlename="<?php echo htmlspecialchars($student['middle_name']); ?>"
                                        data-lastname="<?php echo htmlspecialchars($student['last_name']); ?>"
                                        data-courseyear="<?php echo htmlspecialchars($student['course_year']); ?>">
                                        <?php echo htmlspecialchars($student['display_name']); ?>
                                    </option>
                                    <?php 
                                }
                            endwhile;
                            
                            // Show non-CEIT students only if no CEIT students remain
                            if (!$has_unreferred_ceit) {
                                $students_result->data_seek(0);
                                while ($student = $students_result->fetch_assoc()):
                                    if (!$student['is_ceit']) {
                                        ?>
                                        <option value="null"
                                            data-firstname="<?php echo htmlspecialchars($student['student_name']); ?>"
                                            data-middlename=""
                                            data-lastname=""
                                            data-courseyear="Non-CEIT Student">
                                            <?php echo htmlspecialchars($student['student_name']); ?>
                                        </option>
                                        <?php 
                                    }
                                endwhile;
                            }
                            ?>
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
            <?php elseif ($all_referred): ?>
                <div class="alert alert-success">
                    All students in this incident report have been referred.
                </div>
                <a href="view_approved_reports.php" class="btn btn-primary">Finish</a>
            <?php else: ?>
                <p>No students available for referral in this incident report.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const existingReferral = <?php echo json_encode($existing_referral); ?>;
        
        // Handle reason for referral radio buttons
        const referralReasons = document.getElementsByName('referralReason');
        const violationDetailsGroup = document.getElementById('violationDetailsGroup');
        const otherConcernsGroup = document.getElementById('otherConcernsGroup');
        
        referralReasons.forEach(function(reason) {
            reason.addEventListener('change', function() {
                violationDetailsGroup.style.display = this.value === 'violation' ? 'block' : 'none';
                otherConcernsGroup.style.display = this.value === 'otherConcerns' ? 'block' : 'none';
                
                // Clear the details fields if the reason is changed
                if (this.value !== 'violation') {
                    document.getElementById('violationDetails').value = '';
                }
                if (this.value !== 'otherConcerns') {
                    document.getElementById('otherConcernsDetails').value = '';
                }
            });
        });

        // Student selection handler
        const studentSelect = document.getElementById('student_select');
        studentSelect.addEventListener('change', function() {
            if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                
                // Set the hidden fields
                document.getElementById('student_id').value = this.value === 'null' ? '' : this.value;
                document.getElementById('first_name').value = selectedOption.getAttribute('data-firstname');
                document.getElementById('middle_name').value = selectedOption.getAttribute('data-middlename');
                document.getElementById('last_name').value = selectedOption.getAttribute('data-lastname');
                
                // Display the visible fields
                document.getElementById('student_name').value = 
                    selectedOption.getAttribute('data-firstname') + ' ' + 
                    (selectedOption.getAttribute('data-middlename') ? selectedOption.getAttribute('data-middlename') + ' ' : '') + 
                    selectedOption.getAttribute('data-lastname');
                document.getElementById('course_year').value = selectedOption.getAttribute('data-courseyear');
                
                // Show the form
                document.getElementById('referralForm').style.display = 'block';
                
                // Populate referral details if available
                if (existingReferral) {
                    // Find and check the appropriate radio button
                    referralReasons.forEach(reason => {
                        switch(existingReferral.reason_for_referral) {
                            case 'Academic concern':
                                if (reason.value === 'academicConcern') reason.checked = true;
                                break;
                            case 'Behavior maladjustment':
                                if (reason.value === 'behavioralMaladjustment') reason.checked = true;
                                break;
                            case 'Violation to school rules':
                                if (reason.value === 'violation') {
                                    reason.checked = true;
                                    violationDetailsGroup.style.display = 'block';
                                    document.getElementById('violationDetails').value = existingReferral.violation_details || '';
                                }
                                break;
                            case 'Other concern':
                                if (reason.value === 'otherConcerns') {
                                    reason.checked = true;
                                    otherConcernsGroup.style.display = 'block';
                                    document.getElementById('otherConcernsDetails').value = existingReferral.other_concerns || '';
                                }
                                break;
                        }
                    });
                }
            } else {
                document.getElementById('referralForm').style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>