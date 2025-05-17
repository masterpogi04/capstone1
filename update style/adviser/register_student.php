<?php
session_start();
include "../db.php";
include "adviser_sidebar.php";

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];

    // Check if the student already exists in any section
    $check_sql = "SELECT * FROM tbl_student WHERE student_id = ?";
    $check_stmt = $connection->prepare($check_sql);
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Student already exists
        $message = "Error: This student ID is already registered in a section.";
        $messageType = "error";
    } else {
        // Student doesn't exist, proceed with registration
        $email = "Not registered";
        $password = "";

        $sql = "INSERT INTO tbl_student (student_id, first_name, middle_name, last_name, email, password, section_id, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssssssis", $student_id, $first_name, $middle_name, $last_name, $email, $password, $section_id, $gender);
            
            if ($stmt->execute()) {
                $message = "Student account created successfully.";
                $messageType = "success";
            } else {
                $message = "Error creating student account: " . $stmt->error;
                $messageType = "error";
            }
            $stmt->close();
        } else {
            $message = "Error preparing statement: " . $connection->error;
            $messageType = "error";
        }
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

        .main-content {
            margin-left: 250px; 
            padding: 20px;
           
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 28px;
        }

        .form-container {
          
            max-width: 650px; 
            margin: 1rem auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 2rem; 
            transition: all 0.3s ease;
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
    </style>
</head>
<body>
<div class="main-content">
        <div class="form-container">
        <a href="section_details.php?section_id=<?php echo $section_id; ?>" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back</span>
</a>
        <h2 class="text-center">Register Student</h2>
        <form method="post" action="" id="studentForm">
            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
            
            <div class="form-group">
                <label for="student_id">Student ID:</label>
                <input type="text" class="form-control" id="student_id" name="student_id" required pattern="\d{9}" maxlength="9" title="Please enter exactly 9 digits">
            </div>

            <div class="form-group">
                <label for="first_name">First Name:</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="middle_name">Middle Name:</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name:</label>
                <input type="text" class="form-control" id="last_name" name="last_name" required>
            </div>

            <div class="form-group">
                <label>Gender:</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="male" value="Male" required>
                        <label class="form-check-label" for="male">Male</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="gender" id="female" value="Female" required>
                        <label class="form-check-label" for="female">Female</label>
                    </div>
                </div>
            </div>

            <Center><button type="button" class="btn btn-primary save-button" id="submitBtn">Register Student</button></Center>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#student_id').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
        });

        $('#submitBtn').on('click', function(e) {
            e.preventDefault();
            if ($('#student_id').val().length !== 9) {
                Swal.fire({
                    title: 'Invalid Input',
                    text: 'Student ID must be exactly 9 digits.',
                    icon: 'error',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            } else {
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
            }
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