<?php
session_start();
include "../db.php";

// Check if the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: login.php");
    exit();
}

// Check if database connection is established
if (!isset($connection) || $connection->connect_error) {
    die("Database connection failed: " . ($connection->connect_error ?? "Unknown error"));
}

$message = '';
$messageType = '';
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if (isset($_GET['check_student_id'])) {
    $student_id = $_GET['check_student_id'];
    
    // Check for active students in ANY section
    $check_sql = "SELECT student_id FROM tbl_student WHERE student_id = ? AND status = 'active'";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    $response = array('exists' => false, 'message' => '');
    
    if ($result->num_rows > 0) {
        $response['exists'] = true;
        $response['message'] = 'This student ID is already active in the system.';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// For form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $first_name = strtoupper(trim($_POST['first_name']));
    $middle_name = strtoupper(trim($_POST['middle_name']));
    $last_name = strtoupper(trim($_POST['last_name']));
    $gender = strtoupper(trim($_POST['gender']));
    
    // First check for active students in ANY section
    $check_sql = "SELECT student_id FROM tbl_student WHERE student_id = ? AND status = 'active'";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        $message = "Error: This student ID is already active in the system.";
        $messageType = "error";
    } else {
        // Check for disabled student in ANY section
        $check_disabled_sql = "SELECT * FROM tbl_student WHERE student_id = ? AND status = 'disabled'";
        $check_disabled_stmt = $connection->prepare($check_disabled_sql);
        $check_disabled_stmt->bind_param("s", $student_id);
        $check_disabled_stmt->execute();
        $disabled_result = $check_disabled_stmt->get_result();

        if ($disabled_result->num_rows > 0) {
            // Update the existing disabled student record with new section and reactivate
            $update_sql = "UPDATE tbl_student SET 
                          status = 'active', 
                          first_name = ?, 
                          middle_name = ?, 
                          last_name = ?, 
                          gender = ?,
                          section_id = ?
                          WHERE student_id = ? AND status = 'disabled'";
            $update_stmt = $connection->prepare($update_sql);
            $update_stmt->bind_param("sssssi", $first_name, $middle_name, $last_name, $gender, $section_id, $student_id);
            
            if ($update_stmt->execute()) {
                $message = "Student account reactivated successfully.";
                $messageType = "success";
            } else {
                $message = "Error updating student account: " . $update_stmt->error;
                $messageType = "error";
            }
            $update_stmt->close();
        } else {
            // Create new student record only if no disabled record exists
            $email = null;
            $password = null;
            $status = 'active';

            $sql = "INSERT INTO tbl_student (student_id, first_name, middle_name, last_name, email, password, section_id, gender, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ssssssiss", $student_id, $first_name, $middle_name, $last_name, $email, $password, $section_id, $gender, $status);
                
                if ($stmt->execute()) {
                    $message = "Student account created successfully.";
                    $messageType = "success";
                } else {
                    $message = "Error creating student account: " . $stmt->error;
                    $messageType = "error";
                }
                $stmt->close();
            }
        }
        $check_disabled_stmt->close();
    }
    $check_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Student</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">   
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
          :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --text-color: #2c3e50;
            --input-background: #fff;
            --input-border: #bdc3c7;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
        }

        body{

            background: linear-gradient(135deg, #0d693e, #004d4d);

        }

        .main-content {
        padding: 20px;
        justify-content: center;
        align-items: center;
        display: flex;
        margin-left: 250px; /* This creates the offset space on the left */
    
    }
           
        .form-container {
        max-width: 600px;
        width: 90%;
        padding: 2rem;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        margin-right: 20%; 
    }
        .form-container h2 {
        color: #003366;
        margin-bottom: 1.5rem;
        text-align: center;
        font-size: 1.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        padding-bottom: 0.5rem;
    }

        .form-group {
            margin-bottom: 1.2rem; 
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem; 
            font-weight: bold;
            color: #003366;
            transition: color 0.3s ease;
            font-size: 0.9rem; 
        }

        .form-control {
            width: 100%;
            padding: 0.6rem; 
            border: 2px solid #ccc;
            border-radius: 6px; 
            font-size: 0.9rem; 
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .btn-primary {
            background-color: #4a90e2;
            color: white;
            padding: 0.6rem 1.2rem; 
            border: none;
            border-radius: 30px; 
            cursor: pointer;
            font-size: 0.9rem; 
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            font-weight: 600;
        }
        @media (max-width: 992px) {
            .form-container {
                max-width: 90%;
                padding: 1.8rem;
            }
        }

        @media (max-width: 768px) {
            .form-container {
                max-width: 95%;
                padding: 1.5rem;
                margin: 1rem auto;
            }

            .form-container h2 {
                font-size: 1.5rem;
                margin-bottom: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1rem;
            }

            .form-container h2 {
                font-size: 1.3rem;
                margin-bottom: 1rem;
            }

            .btn-primary {
                font-size: 0.8rem;
                padding: 0.5rem 1rem;
            }
        }

        label {
            margin-top: 15px;
            font-weight: 600;
            color: var(--text-color);
        }

        input, select {
            margin-top: 5px;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid var(--input-border);
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        input[type="submit"] {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            padding: 12px;
            font-size: 18px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: var(--secondary-color);
        }

        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 4px;
            font-weight: 600;
        }

        .gender-group {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .gender-group label {
            margin-right: 20px;
            display: flex;
            align-items: center;
        }

        .gender-group input[type="radio"] {
            margin-right: 5px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
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
        .save-button{
            background-color: #4a90e2;
            color: white;
            padding: 0.7rem 1rem; /* Increased padding for a weightier look */
            border: none;
            border-radius: 25px; /* Slightly increased roundness */
            cursor: pointer;
            font-size: 1.1rem; /* Slightly larger font size */
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px; /* Increased letter spacing */
            position: relative;
            overflow: hidden;
            font-weight: 400; /* Increased font weight */
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); /* Added a subtle shadow for depth */
            width: auto; /* Allow button to size based on content */
            min-width: 400px; /* Ensure a minimum width */
        }

         .save-button:hover {
            background-color: #3a7bc8;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

         .save-button:active {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

         .save-button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 3px;
            background: rgba(255,255,255,0.7);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        . .save-button:focus:not(:active)::after {
            animation: ripple 1s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 1;
            }
            20% {
                transform: scale(25, 25);
                opacity: 1;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
             .save-button {
                padding: 0.8rem 1.8rem;
                font-size: 1rem;
                min-width: 180px;
            }
        }

        @media (max-width: 480px) {
            .save-button {
                padding: 0.7rem 1.5rem;
                font-size: 0.9rem;
                min-width: 160px;
            }
        }
        /* Responsive Breakpoints */
        @media screen and (max-width: 768px) {
                .form-container {
                    width: 90%;
                    padding: 1.5rem;
                }

                .btn-primary {
                    max-width: 100%;
                    padding: 12px 24px;
                    font-size: 15px;
                }

                .form-container label {
                    font-size: 15px;
                }

                .form-container select,
                .form-container input {
                    padding: 10px;
                    font-size: 15px;
                }
            }

            @media screen and (max-width: 480px) {
                .form-container {
                    width: 95%;
                    padding: 1rem;
                }

                .academic-year-group {
                    flex-direction: column;
                    gap: 8px;
                }

                .academic-year-group span {
                    text-align: center;
                }

                .btn-primary {
                    padding: 10px 20px;
                    font-size: 14px;
                }

                .form-container label {
                    font-size: 14px;
                }

                .form-container select,
                .form-container input {
                    padding: 8px;
                    font-size: 14px;
                    margin: 6px 0 16px;
                }
            }

            /* Touch Device Optimizations */
            @media (hover: none) {
                .form-container select,
                .form-container input {
                    font-size: 16px;
                }

                .btn-primary {
                    -webkit-tap-highlight-color: transparent;
                }

                .btn-primary:active {
                    transform: translateY(1px);
                }
            }

            /* Ensure form is responsive when sidebar is collapsed */
            @media screen and (max-width: 992px) {
                .main-content {
                    margin-left: 0;
                    padding: 15px;
                }
                
                .form-container {
                    margin: 1rem auto;
                }
            }

            /* Dropdown specific styles */
        .form-select {
            width: 100%;
            padding: 0.6rem;
            border: 2px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
            color: #003366;
            appearance: none; /* Removes default browser styling */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23003366' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        .form-select:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .form-select:hover {
            border-color: #4a90e2;
        }

        /* Style for the dropdown options */
        .form-select option {
            padding: 10px;
            background-color: #ffffff;
            color: #003366;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-select {
                font-size: 0.85rem;
                padding: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .form-select {
                font-size: 0.8rem;
                padding: 0.4rem;
            }
        }
</style>
</head>
<body>
<div class="main-content">
        <div class="form-container">
        <a href="section_details.php?section_id=<?php echo $section_id; ?>" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span></a>
        <h2 class="text-center">Add Student</h2>
        <form method="post" action="" id="studentForm">
            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
            
            <div class="form-group">
                <label for="student_id">Student Number:</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required pattern="\d{9}" maxlength="9" title="Please enter exactly 9 digits">
            </div>

            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required pattern="[A-Za-z ]+" title="Only letters are allowed">
            </div>

            <div class="form-group">
                <label for="middle_name">Middle Initial:</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name" maxlength="1" pattern="[A-Za-z]" title="Single letter only">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" class="form-control" id="last_name" name="last_name" required pattern="[A-Za-z ]+" title="Only letters are allowed">
            </div>

           <div class="form-group">
    <label>Sex:</label>
    <div>
    <select class="form-select" name="gender" id="gender" required>
        <option value="" hidden>Select Sex</option>
        <option value="MALE">Male</option>
        <option value="FEMALE">Female</option>
    </select>
</div>
</div>

            <Center><button type="button" class="btn btn-primary save-button" id="submitBtn">Register Student</button></Center>
        </form>
    </div>

<script>
$(document).ready(function() {
    let studentExists = false;

    // Add this new function to handle Enter key
    function setupEnterKeyNavigation() {
        const fields = ['student_id', 'first_name', 'middle_name', 'last_name', 'gender'];
        
        fields.forEach((field, index) => {
            $(`#${field}`).keypress(function(e) {
                // Check if Enter key is pressed (key code 13)
                if (e.which === 13) {
                    e.preventDefault(); // Prevent form submission
                    
                    // If we're not on the last field, focus the next field
                    if (index < fields.length - 1) {
                        $(`#${fields[index + 1]}`).focus();
                    } else {
                        // If we're on the last field, trigger the submit button
                        $('#submitBtn').click();
                    }
                }
            });
        });
    }

    // Call the function to set up Enter key navigation
    setupEnterKeyNavigation();

    // Enhanced student ID validation
    $('#student_id').on('input', function() {
        // Restrict to numbers and max length of 9
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
        const studentId = this.value;
        
        // Clear existing inline error messages
        $('#student_id_message').remove();
        
        // If the field is empty, reset the studentExists flag and exit early
        if (studentId.length === 0) {
            studentExists = false; // Reset the flag
            $('#submitBtn').prop('disabled', false).css('opacity', '1');
            return;
        }
        
        // Only check if we have 9 digits
        if (studentId.length === 9) {
            // Show loading indicator
            const loadingMessage = `<div id="student_id_message" class="text-info" 
                style="margin-top: -15px; margin-bottom: 15px;">
                <i class="fas fa-spinner fa-spin"></i> Checking student ID...
            </div>`;
            $('#student_id').after(loadingMessage);
            
            // Perform the check with a small delay to prevent rapid requests
            clearTimeout(this.timeoutId);
            this.timeoutId = setTimeout(() => {
                $.get(window.location.pathname, {
                    check_student_id: studentId
                })
                .done(function(response) {
                    $('#student_id_message').remove(); // Clear loading message
                    if (response.exists) {
                        studentExists = true;
                        // Show SweetAlert notification for duplicate student ID
                        Swal.fire({
                            title: 'Student Already Registered',
                            text: 'This student ID is already active in the system.',
                            icon: 'error',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Clear the student ID field
                                $('#student_id').val('');
                                $('#student_id').focus();
                            }
                        });
                        $('#submitBtn').prop('disabled', true).css('opacity', '0.6');
                    } else {
                        const message = `<div id="student_id_message" class="text-success" 
                            style="margin-top: -15px; margin-bottom: 15px;">
                            <i class="fas fa-check-circle"></i> Student ID is available
                        </div>`;
                        $('#student_id').after(message);
                        $('#submitBtn').prop('disabled', false).css('opacity', '1');
                    }
                })
                .fail(function() {
                    $('#student_id_message').remove(); // Clear loading message
                    const message = `<div id="student_id_message" class="text-danger" 
                        style="margin-top: -15px; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-circle"></i> Error checking student ID
                    </div>`;
                    $('#student_id').after(message);
                    $('#submitBtn').prop('disabled', true).css('opacity', '0.6');
                });
            }, 300); // 300ms delay before checking
        } else if (studentId.length > 0) {
            // Show validation message for incomplete ID
            const message = `<div id="student_id_message" class="text-warning" 
                style="margin-top: -15px; margin-bottom: 15px;">
                <i class="fas fa-exclamation-triangle"></i> Please enter 9 digits
            </div>`;
            $('#student_id').after(message);
            $('#submitBtn').prop('disabled', true).css('opacity', '0.6');
        }
    });

    // First Name validation - only letters and spaces
    $('#first_name').on('input', function() {
        this.value = this.value.replace(/[^A-Za-z ]/g, '').toUpperCase();
    });

    // Middle Name validation - single letter only
    $('#middle_name').on('input', function() {
        this.value = this.value.replace(/[^A-Za-z]/g, '').slice(0, 1).toUpperCase();
    });

    // Last Name validation - only letters and spaces
    $('#last_name').on('input', function() {
        this.value = this.value.replace(/[^A-Za-z ]/g, '').toUpperCase();
    });

    $('#submitBtn').on('click', function(e) {
        e.preventDefault();

        // Check if student exists before showing confirmation
        if (studentExists) {
            Swal.fire({
                title: 'Error',
                text: 'This student ID is already active in the system.',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Form validation
        const studentId = $('#student_id').val();
        const firstName = $('#first_name').val().trim();
        const lastName = $('#last_name').val().trim();
        const gender = $('#gender').val();

        if (studentId.length !== 9) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please enter a valid 9-digit student ID.',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        if (!firstName || !lastName || !gender) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please fill in all required fields.',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirm Registration',
            text: 'Are you sure you want to register this student?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, register',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#studentForm').submit();
            }
        });
    });

    <?php if($message): ?>
    Swal.fire({
        title: 'Registration Status',
        text: "<?php echo $message; ?>",
        icon: '<?php echo $messageType; ?>',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    }).then((result) => {
        if (result.isConfirmed && '<?php echo $messageType; ?>' === 'success') {
            window.location.href = 'section_details.php?section_id=<?php echo $section_id; ?>';
        }
    });
    <?php endif; ?>
});
</script>
</body>
</html>