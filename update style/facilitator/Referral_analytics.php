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

// Get student details if student_id is provided
$student_details = null;
if (isset($_GET['student_id'])) {
    $stmt = $connection->prepare("
        SELECT ts.first_name, ts.middle_name, ts.last_name, s.year_level, c.name as course
        FROM tbl_student ts
        JOIN sections s ON ts.section_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE ts.student_id = ?
    ");
    $stmt->bind_param("s", $_GET['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_details = $result->fetch_assoc();
}

// At the top of the file after existing student_details code
$first_name = isset($_GET['firstName']) ? $_GET['firstName'] : ($student_details['first_name'] ?? '');
$middle_name = isset($_GET['middleName']) ? $_GET['middleName'] : ($student_details['middle_name'] ?? '');
$last_name = isset($_GET['lastName']) ? $_GET['lastName'] : ($student_details['last_name'] ?? '');
// At the top of the file after existing variable assignments
$course_name = isset($_GET['course']) ? $_GET['course'] : ($student_details ? htmlspecialchars($student_details['course'] . ' - ' . $student_details['year_level']) : '');
$referral_reason = isset($_GET['reason']) ? $_GET['reason'] : '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $student_id = $_POST['student_id']; // Add this line
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

    $faculty_name = $_POST['faculty_name'];
    $acknowledged_by = $_POST['acknowledged_by'];

    // Modify the SQL query to include student_id
    $sql = "INSERT INTO referrals (date, student_id, first_name, middle_name, last_name, course_year, 
            reason_for_referral, violation_details, other_concerns, faculty_name, acknowledged_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("sssssssssss", 
        $date, $student_id, $first_name, $middle_name, $last_name, $course_year,
        $reason_for_referral, $violation_details, $other_concerns, $faculty_name, $acknowledged_by
    );
    
    if ($stmt->execute()) {
        $referral_id = $connection->insert_id;

        // Create notification for student if student_id exists
        if ($student_id) {
            createNotification(
                $connection,
                'student',
                $student_id,
                "You have been referred to the Guidance Counselor.",
                "view_referral_details.php?id=" . $referral_id
            );

            // Get adviser information for the student
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

            // Create notification for adviser if exists
            if (isset($adviser_info['adviser_id'])) {
                createNotification(
                    $connection,
                    'adviser',
                    $adviser_info['adviser_id'],
                    "Your student has been referred to the Guidance Counselor.",
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
                "A new student referral has been submitted.",
                "view_referral_details.php?id=" . $referral_id
            );
        }

        echo '<script type="text/javascript">';
        echo 'alert("Referral submitted successfully!");';
        echo 'window.location.href = "view_medical_history_analytics.php";';
        echo '</script>';
    } else {
        echo '<script type="text/javascript">';
        echo 'alert("Error submitting referral.");';
        echo '</script>';
    }
}

$first_name = isset($_GET['firstName']) ? $_GET['firstName'] : '';
$middle_name = isset($_GET['middleName']) ? $_GET['middleName'] : '';
$last_name = isset($_GET['lastName']) ? $_GET['lastName'] : '';
$referral_reason = isset($_GET['reason']) ? $_GET['reason'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Form</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
    body {
        background: linear-gradient(to right, #0d693e, #004d4d);
        min-height: 100vh;
        font-family: Arial, sans-serif;
        padding-top: 60px;
    }
    .header {
        background-color: #ff9f1c;
        padding: 10px;
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        position: fixed;
        right: 0;
        top: 0;
        width: 100%;
        color: white;
        z-index: 1000;
    }
    .back-btn {
        position: fixed;
        top: 10px;
        left: 10px;
        background-color: #6c757d;
        border: none;
        border-radius: 20px;
        padding: 5px 15px;
        font-weight: bold;
        color: white;
        z-index: 1001;
    }
    .form-container {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .form-label {
        font-weight: bold;
    }
    .form-control {
        border: none;
        border-bottom: 1px solid #ccc;
        border-radius: 0;
        margin-bottom: 10px;
        width: 100%;
        display: inline-block;
    }
    .form-check-label {
        margin-left: 30px;
    }
    .btn-submit {
        background-color: #007bff;
        border: none;
        border-radius: 20px;
        padding: 10px 20px;
        font-weight: bold;
        color: white;
    }
    </style>
</head>
<body>
    <div class="header">REFERRAL FORM</div>
    <div class="container">
        <a href="<?php echo isset($_GET['student_id']) ? 'view_medical_history_analytics.php' : 'guidanceservice.html'; ?>" class="btn btn-secondary mb-3">Back</a>
        <div class="form-container">
            <form method="POST" action="">
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
                <div class="form-group">
                    <label for="date" class="form-label">Date:</label>
                    <input type="date" class="form-control" id="date" name="date" required>
                </div>
                <p>To the GUIDANCE COUNSELOR:</p>
                <p>This is to refer the student,
                    <input type="text" class="form-control" style="width: 200px;" name="first_name" 
    value="<?php echo htmlspecialchars($first_name); ?>" 
    placeholder="First name" required <?php echo isset($student_details) || isset($_GET['firstName']) ? 'readonly' : ''; ?>>
<input type="text" class="form-control" style="width: 200px;" name="middle_name" 
    value="<?php echo htmlspecialchars($middle_name); ?>" 
    placeholder="Middle name" <?php echo isset($student_details) || isset($_GET['middleName']) ? 'readonly' : ''; ?>>
<input type="text" class="form-control" style="width: 200px;" name="last_name" 
    value="<?php echo htmlspecialchars($last_name); ?>" 
    placeholder="Last name" required <?php echo isset($student_details) || isset($_GET['lastName']) ? 'readonly' : ''; ?>> <br>
                    <input type="text" class="form-control" style="width: 300px;" name="course_year" 
    value="<?php echo htmlspecialchars($course_name); ?>" 
    placeholder="Course/year" required <?php echo isset($student_details) || isset($_GET['course']) ? 'readonly' : ''; ?>>
                    to your office for counselling.
                </p>

                <p>Reason for referral, please select one:</p>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="academicConcern" name="referralReason" value="academicConcern" required>
                    <label class="form-check-label" for="academicConcern">Academic concern</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="behavioralMaladjustment" name="referralReason" value="behavioralMaladjustment">
                    <label class="form-check-label" for="behavioralMaladjustment">Behaviour maladjustment</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="violation" name="referralReason" value="violation">
                    <label class="form-check-label" for="violation">Violation to school rules</label>
                </div>
                <div class="form-group" id="violationDetailsGroup" style="display: none;">
                    <input type="text" class="form-control" style="width: 300px;" name="violation_details" id="violationDetails" placeholder="Specify violation">
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="otherConcerns" name="referralReason" value="otherConcerns">
                    <label class="form-check-label" for="otherConcerns">Other concerns</label>
                </div>
                <div class="form-group" id="otherConcernsGroup" style="display: none;">
                    <input type="text" class="form-control" style="width: 300px;" name="other_concerns" id="otherConcernsDetails" placeholder="Specify other concerns">
                </div>
                <p>Thank you.</p>
                
                <div class="form-group">
                    <label for="facultyName" class="form-label">Signature over printed name of Faculty/Employee:</label>
                    <input type="text" class="form-control" id="facultyName" name="faculty_name" 
                        value="<?php echo htmlspecialchars($facilitator_name); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="acknowledgedBy" class="form-label">Acknowledged by:</label>
                    <input type="text" class="form-control" id="acknowledgedBy" name="acknowledged_by" 
                        value="Guidance Counselor" readonly>
                </div>
                <button type="submit" class="btn btn-submit">SUBMIT</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var dateInput = document.getElementById('date');
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = today.getFullYear();

        today = yyyy + '-' + mm + '-' + dd;
        dateInput.value = today;
        dateInput.setAttribute('max', today);

        dateInput.addEventListener('input', function() {
            var selectedDate = new Date(this.value);
            var currentDate = new Date(today);
            
            if (selectedDate > currentDate) {
                this.value = today;
                alert("You cannot select a future date. The date has been reset to today.");
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
    const reason = urlParams.get('reason');
    
    if (reason) {
        // Select "Other concerns" radio button
        const otherConcernsRadio = document.getElementById('otherConcerns');
        if (otherConcernsRadio) {
            otherConcernsRadio.click(); // This will trigger the change event
            
            // Show and populate the other concerns field
            const otherConcernsGroup = document.getElementById('otherConcernsGroup');
            const otherConcernsDetails = document.getElementById('otherConcernsDetails');
            
            if (otherConcernsGroup && otherConcernsDetails) {
                otherConcernsGroup.style.display = 'block';
                otherConcernsDetails.value = reason;
                otherConcernsDetails.readOnly = true; // Make it readonly since it's pre-populated
            }
        }
    }

        // Referral reason logic
        var referralReasons = document.getElementsByName('referralReason');
        var violationDetailsGroup = document.getElementById('violationDetailsGroup');
        var otherConcernsGroup = document.getElementById('otherConcernsGroup');
        var violationDetails = document.getElementById('violationDetails');
        var otherConcernsDetails = document.getElementById('otherConcernsDetails');

        referralReasons.forEach(function(reason) {
            reason.addEventListener('change', function() {
                if (this.value === 'violation') {
                    violationDetailsGroup.style.display = 'block';
                    violationDetails.required = true;
                    otherConcernsGroup.style.display = 'none';
                    otherConcernsDetails.required = false;
                } else if (this.value === 'otherConcerns') {
                    otherConcernsGroup.style.display = 'block';
                    otherConcernsDetails.required = true;
                    violationDetailsGroup.style.display = 'none';
                    violationDetails.required = false;
                } else {
                    violationDetailsGroup.style.display = 'none';
                    otherConcernsGroup.style.display = 'none';
                    violationDetails.required = false;
                    otherConcernsDetails.required = false;
                }
            });
        });
    });

    
    </script>
</body>
</html>