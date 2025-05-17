<?php
session_start();
include '../db.php';

// Check if the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Check if the student has any pending requests
$check_stmt = $connection->prepare("SELECT COUNT(*) FROM document_requests WHERE student_id = ? AND status = 'Pending'");
$check_stmt->bind_param("i", $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$pending_count = $check_result->fetch_row()[0];

if ($pending_count > 0) {
    $error_message = "You already have a pending request. Please wait for it to be processed before submitting a new one.";
} else {
    // Fetch student details
    $stmt = $connection->prepare("
        SELECT s.first_name, s.last_name, s.student_id, s.gender, s.section_id, s.email,
               d.name AS department, c.name AS course
        FROM tbl_student s
        JOIN sections sec ON s.section_id = sec.id
        JOIN departments d ON sec.department_id = d.id
        JOIN courses c ON sec.course_id = c.id
        WHERE s.student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $documentRequest = htmlspecialchars(trim($_POST['documentRequest']));
        $purpose = $_POST['purpose'] === 'Others' ? 'Others: ' . htmlspecialchars(trim($_POST['otherPurpose'])) : htmlspecialchars(trim($_POST['purpose']));
        $idPresented = $_POST['idPresented'] === 'Others' ? 'Others: ' . htmlspecialchars(trim($_POST['otherIdPresented'])) : htmlspecialchars(trim($_POST['idPresented']));
        $requestDate = date('Y-m-d'); // Current date
        $email = $student['email']; // Get email from student details

        // Generate a unique request_id
        $request_id = uniqid('REQ_', true);

        // Insert into database
        $sql = "INSERT INTO document_requests (request_id, student_id, first_name, last_name, student_number, gender, department, course, document_request, purpose, id_presented, request_time, status, contact_email) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";

        $stmt = $connection->prepare($sql);
        $stmt->bind_param("sisssssssssss", $request_id, $student_id, $student['first_name'], $student['last_name'], $student['student_id'], $student['gender'], $student['department'], $student['course'], $documentRequest, $purpose, $idPresented, $requestDate, $email);
        
        if ($stmt->execute()) {
            $success_message = "Your document request has been submitted successfully!";
        } else {
            $error_message = "Error submitting request: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request for Certificate of Good Moral Character</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>

       body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fa;
    color: #2d3748;
    line-height: 1.6;
    }
    .header {
    background-color: #ff9042;
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .header h1 {
    font-size: 2.2rem;
    font-weight: 600;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }


        .form-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        .form-content {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 30px;
            width: 800px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-content h2 {
            color: #1e4d92;
            font-size: 1.5rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 7px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #1e92d1;
            box-shadow: 0 0 0 0.2rem rgba(30, 146, 209, 0.25);
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
            cursor: default;
        }

        .form-check {
            margin-bottom: 10px;
        }

        .form-check-input {
            margin-top: 0.3rem;
        }

        .form-check-label {
            font-weight: 400;
            color: #495057;
        }

        .btn {
            padding: 12px 80px;
            font-size: 14px;
            gap: 5px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-success {
            background-color: #1e92d1;
            border-color: #1e92d1;
        }

        .btn-success:hover {
            background-color: #1a7eb5;
            border-color: #1a7eb5;
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
            transform: translateY(-2px);
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

        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .hidden {
            display: none;
        }

        /* Custom select styling */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23555' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        /* Animation for form appearance */
        .form-content {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <center><h1>REQUEST FOR DOCUMENT</h1></center>
    </div>

    <div class="container mt-4">
    <div class="form-container">
        <div class="form-content">
        <a href="student_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <h2><b>Directions: </b> Review your information and fill out the required fields.</h2>
                <form id="documentRequestForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                   <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" class="form-control" value="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="sex">Sex:</label>
                    <input type="text" id="sex" class="form-control" value="<?php echo htmlspecialchars($student['gender']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="studentId">Student I.D. Number:</label>
                    <input type="text" id="studentId" class="form-control" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
                </div>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                    <div class="form-group">
                    <label for="documentRequest">Document Request*</label>
                    <select id="documentRequest" name="documentRequest" class="form-control" required onchange="toggleOtherDocumentInput()">
                        <option value="">Select document</option>
                        <option value="Good Moral">Good Moral</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group" id="otherDocumentGroup" style="display: none;">
                    <label for="otherDocument">Specify Other Document*</label>
                    <input type="text" id="otherDocument" name="otherDocument" class="form-control">
                </div>
                <div class="form-group">
                    <label for="purpose">Purpose:</label>
                    <select id="purpose" name="purpose" class="form-control" required onchange="toggleOtherPurpose()">
                        <option value="">Select Purpose</option>
                        <option value="Scholarship Application">Scholarship Application</option>
                        <option value="Employment">Employment</option>
                        <option value="Transfer to Another School">Transfer to Another School</option>
                        <option value="Board Examination">Board Examination</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div id="otherPurposeField" class="form-group hidden">
                    <label for="otherPurpose">Specify Other Purpose:</label>
                    <input type="text" id="otherPurpose" name="otherPurpose" class="form-control">
                </div>
                <div class="form-group">
                    <label>I.D. to present in Claiming:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="idPresented" id="schoolId" value="School ID" required onclick="toggleOtherId()">
                        <label class="form-check-label" for="schoolId">School ID</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="idPresented" id="alumniId" value="Alumni ID" required onclick="toggleOtherId()">
                        <label class="form-check-label" for="alumniId">Alumni ID</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="idPresented" id="otherId" value="Others" required onclick="toggleOtherId()">
                        <label class="form-check-label" for="otherId">Others</label>
                    </div>
                </div>
                <div id="otherIdField" class="form-group hidden">
                    <label for="otherIdPresented">Specify Other ID:</label>
                    <input type="text" id="otherIdPresented" name="otherIdPresented" class="form-control">
                </div>

                    <div class="form-actions d-flex justify-content-around mb-3">
                        <button type="submit" class="btn btn-success">Submit</button>
                        <button type="reset" class="btn btn-danger">Reset</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
         function toggleOtherDocumentInput() {
            var documentRequest = document.getElementById('documentRequest');
            var otherDocumentGroup = document.getElementById('otherDocumentGroup');
            if (documentRequest.value === 'Other') {
                otherDocumentGroup.style.display = 'block';
            } else {
                otherDocumentGroup.style.display = 'none';
            }
        }

        function toggleOtherPurpose() {
            var purposeSelect = document.getElementById('purpose');
            var otherPurposeField = document.getElementById('otherPurposeField');
            if (purposeSelect.value === 'Others') {
                otherPurposeField.classList.remove('hidden');
            } else {
                otherPurposeField.classList.add('hidden');
            }
        }

        function toggleOtherId() {
            var otherIdField = document.getElementById('otherIdField');
            if (document.getElementById('otherId').checked) {
                otherIdField.classList.remove('hidden');
            } else {
                otherIdField.classList.add('hidden');
            }
        }

        document.getElementById('documentRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate other document if 'Other' is selected
            if (document.getElementById('documentRequest').value === 'Other' && document.getElementById('otherDocument').value.trim() === '') {
                Swal.fire('Error', 'Please specify the other document.', 'error');
                return;
            }
            
            // Validate other purpose if 'Others' is selected
            if (document.getElementById('purpose').value === 'Others' && document.getElementById('otherPurpose').value.trim() === '') {
                Swal.fire('Error', 'Please specify the other purpose.', 'error');
                return;
            }
            
            // Validate other ID if 'Others' is selected
            if (document.getElementById('otherId').checked && document.getElementById('otherIdPresented').value.trim() === '') {
                Swal.fire('Error', 'Please specify the other ID presented.', 'error');
                return;
            }

            Swal.fire({
                title: 'Confirm Submission',
                text: 'Are you sure you want to submit this document request?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        <?php if (isset($success_message)): ?>
        Swal.fire({
            title: 'Success!',
            text: '<?php echo $success_message; ?>',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'student_homepage.php';
            }
        });
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo $error_message; ?>',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        <?php endif; ?>
    </script>
</body>
</html>