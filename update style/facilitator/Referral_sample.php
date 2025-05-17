<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Get facilitator name
$facilitator_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$facilitator = $result->fetch_assoc();
$facilitator_name = trim($facilitator['first_name'] . ' ' . $facilitator['middle_initial'] . ' ' . $facilitator['last_name']);

// Get counselor name
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_counselor LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$counselor = $result->fetch_assoc();
$counselor_name = trim($counselor['first_name'] . ' ' . $counselor['middle_initial'] . ' ' . $counselor['last_name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $student_id = $_POST['student_id'];
    
    // Get the student info again to ensure data integrity
    $stmt = $connection->prepare("SELECT first_name, middle_name, last_name FROM tbl_student WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    
    $first_name = $student['first_name'];
    $middle_name = $student['middle_name'];
    $last_name = $student['last_name'];
    
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

    $sql = "INSERT INTO referrals (date, student_id, first_name, middle_name, last_name, course_year, 
            reason_for_referral, violation_details, other_concerns, faculty_name, acknowledged_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("sssssssssss", 
        $date, 
        $student_id,
        $first_name, 
        $middle_name, 
        $last_name, 
        $course_year, 
        $reason_for_referral, 
        $violation_details, 
        $other_concerns, 
        $facilitator_name, 
        $counselor_name
    );
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Form</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    :root {
    --primary-color: #0d693e;
    --secondary-color: #004d4d;
    --accent-color: #2EDAA8;
    --header-color: #ff9f1c;
    --text-color: #2c3e50;
    --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

body {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: var(--text-color);
    margin: 0;
    padding: 0;
    padding-top: 80px;
}

.header {
    background-color: var(--header-color);
    padding: 15px;
    text-align: center;
    font-size: 28px;
    font-weight: bold;
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

/* Back Button */
.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: var(--accent-color);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(46, 218, 168, 0.15);
}

.btn-secondary:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
}

/* Form Container */
.form-container {
    background-color: rgba(255, 255, 255, 0.98);
    padding: 50px;
    border-radius: 15px;
    box-shadow: var(--shadow);
    margin-bottom: 30px;
}

/* Form Elements */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: var(--text-color);
    display: block;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: var(--transition);
    background-color: #fff;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(13, 105, 62, 0.1);
}

.form-control[readonly] {
    background-color: #f8f9fa;
    border-color: #e0e0e0;
}

/* Radio Buttons */
.form-check {
    margin-bottom: 15px;
    padding-left: 30px;
    position: relative;
}

.form-check-input {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
}

.form-check-label {
    color: var(--text-color);
    font-weight: 500;
    margin-left: 0;
    cursor: pointer;
}

/* Section Headers */
p {
    font-size: 16px;
    color: var(--text-color);
    margin: 20px 0 10px;
    font-weight: 500;
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
/* Submit Button */
.btn-primary {
    background-color: var(--primary-color);
    color: white;
    padding: 12px 30px;
    border-radius: 25px;
    border: none;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: var(--transition);
    display: block;
    width: 200px;
    margin: 30px auto 0;
    text-align: center;
}

.btn-primary:hover {
    background-color: #0a5832;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(13, 105, 62, 0.2);
}

/* Additional Fields */
#violationDetailsGroup,
#otherConcernsGroup {
    margin-top: 10px;
    margin-left: 30px;
    padding: 10px;
    border-left: 3px solid var(--primary-color);
    background-color: #f8f9fa;
    border-radius: 0 8px 8px 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    .form-container {
        padding: 20px;
    }
    
    .btn-primary {
        width: 100%;
    }
}

/* Sweet Alert Customization */
.swal2-popup {
    border-radius: 15px;
    padding: 20px;
}

.swal2-title {
    color: var(--text-color) !important;
    font-size: 24px !important;
}

.swal2-confirm {
    background-color: var(--primary-color) !important;
    border-radius: 25px !important;
    padding: 12px 30px !important;
}
    </style>
</head>
<body>
    <div class="header">REFERRAL FORM</div>
    <div class="container">
  
        <div class="form-container">
        <a href="guidanceservice.html" class="modern-back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <form method="POST" action="" id="referralForm">
                <div class="form-group">
                    <label class="form-label" for="date">Date:</label>
                    <input type="date" class="form-control" id="date" name="date" required>
                </div>

                <div class="form-group">
                    <label  class="form-label" for="student_id">Student ID:</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" required>
                </div>

                <p>To the GUIDANCE COUNSELOR:</p>
                <p>This is to refer the student,</p>
                <div class="form-group">
                    <input type="text" class="form-control" name="student_name" id="student_name" placeholder="Student Name" readonly required>
                    <br>
                    <input type="text" class="form-control" name="course_year" id="course_year" placeholder="Course/Year" readonly required>
                    to your office for counselling.
                </div>

                <p>Reason for referral, please select one:</p>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="academicConcern" name="referralReason" value="academicConcern" required>
                    <label class="form-label" for="academicConcern">Academic concern</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="behavioralMaladjustment" name="referralReason" value="behavioralMaladjustment">
                    <label class="form-label" for="behavioralMaladjustment">Behaviour maladjustment</label>
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="violation" name="referralReason" value="violation">
                    <label class="form-label" for="violation">Violation to school rules</label>
                </div>
                <div class="form-group" id="violationDetailsGroup" style="display: none;">
                    <input type="text" class="form-control" name="violation_details" id="violationDetails" placeholder="Specify violation">
                </div>
                <div class="form-check">
                    <input type="radio" class="form-check-input" id="otherConcerns" name="referralReason" value="otherConcerns">
                    <label class="form-label" for="otherConcerns">Other concerns</label>
                </div>
                <div class="form-group" id="otherConcernsGroup" style="display: none;">
                    <input type="text" class="form-control" name="other_concerns" id="otherConcernsDetails" placeholder="Specify other concerns">
                </div>

                <p>Thank you.</p>
                <div class="form-group">
                    <label>Signature over printed name of Faculty/Employee:</label>
                    <input type="text" class="form-control" name="faculty_name" value="<?php echo htmlspecialchars($facilitator_name); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Acknowledged by:</label>
                    <input type="text" class="form-control" name="acknowledged_by" value="<?php echo htmlspecialchars($counselor_name); ?>" readonly>
                </div>
                <button type="submit" class="btn btn-primary">SUBMIT</button>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Set today's date and max date
            var today = new Date().toISOString().split('T')[0];
            $('#date').val(today).attr('max', today);

            // Student ID lookup using existing get_student_info-facilitator.php
            $('#student_id').on('blur', function() {
                var studentId = $(this).val();
                if (studentId) {
                    $.ajax({
                        url: 'referral_get_student_info.php',
                        method: 'POST',
                        data: { student_id: studentId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                var fullName = response.name;
                                $('#student_name').val(fullName);
                                $('#course_year').val(response.year_course);
                            } else {
                                Swal.fire({
                                    title: 'Student Not Found',
                                    text: 'Please check the student ID.',
                                    icon: 'warning',
                                    confirmButtonColor: '#3085d6'
                                });
                                $('#student_name, #course_year').val('');
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Error fetching student information.',
                                icon: 'error',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
                }
            });

            // Handle reason for referral radio buttons
            $('input[name="referralReason"]').change(function() {
                var selectedReason = $(this).val();
                $('#violationDetailsGroup, #otherConcernsGroup').hide();
                $('#violationDetails, #otherConcernsDetails').prop('required', false);

                if (selectedReason === 'violation') {
                    $('#violationDetailsGroup').show();
                    $('#violationDetails').prop('required', true);
                } else if (selectedReason === 'otherConcerns') {
                    $('#otherConcernsGroup').show();
                    $('#otherConcernsDetails').prop('required', true);
                }
            });

            // Add form submission handler
            $('#referralForm').on('submit', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Submit Referral',
                    text: 'Are you sure you want to submit this referral?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, submit it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: $(this).attr('action'),
                            type: 'POST',
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Referral has been submitted successfully.',
                                        icon: 'success',
                                        confirmButtonColor: '#3085d6'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Failed to submit referral. Please try again.',
                                        icon: 'error',
                                        confirmButtonColor: '#3085d6'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'An error occurred. Please try again.',
                                    icon: 'error',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        });
                    }
                });
            });
        });
</script>
</body>
</html>