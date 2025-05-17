<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection file
include "../db.php";

// Check if database connection is established
if (!isset($connection)) {
    die("Database connection variable (\$connection) is not set. Check your db.php file.");
}

if ($connection instanceof mysqli) {
    if ($connection->connect_error) {
        die("Database connection failed: " . $connection->connect_error);
    }
} else {
    die("Invalid database connection object. Expected mysqli instance.");
}

include "adviser_sidebar.php";

// Check if the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: login.php");
    exit();
}

// Function to get departments
function getDepartments() {
    global $connection;
    $stmt = $connection->prepare("SELECT * FROM departments ORDER BY name");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get courses by department
function getCoursesByDepartment($department_id) {
    global $connection;
    $stmt = $connection->prepare("SELECT * FROM courses WHERE department_id = ? ORDER BY name");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST request received: " . print_r($_POST, true));

    if (isset($_POST['department']) && !isset($_POST['add_section'])) {
        // This is a department selection, just update the courses
        $selectedDepartment = $_POST['department'];
        $courses = getCoursesByDepartment($selectedDepartment);
    } elseif (isset($_POST['add_section'])) {
        // This is the final form submission to add a section
        $department_id = $_POST['department'];
        $course_id = $_POST['course'];
        $year_level = $_POST['year_level'];
        $section_no = $_POST['section_no'];
        $academic_year_start = $_POST['academic_year_start'];
        $academic_year_end = $_POST['academic_year_end'];
        $academic_year = $academic_year_start . ' to ' . $academic_year_end;
        $adviser_id = $_SESSION['user_id'];

        // Generate the new section ID format
        $year = substr($academic_year_start, -2);
        $random_numbers = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
        $new_section_id = $year . $random_numbers;

        $sql = "INSERT INTO sections (id, department_id, course_id, year_level, section_no, academic_year, adviser_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($sql);
        
        if ($stmt === false) {
            $message = "Error preparing statement: " . $connection->error;
            error_log($message);
        } else {
            $stmt->bind_param("siisssi", $new_section_id, $department_id, $course_id, $year_level, $section_no, $academic_year, $adviser_id);

            if ($stmt->execute()) {
                $message = "Section added successfully!";
                error_log($message);
            } else {
                $message = "Error executing statement: " . $stmt->error;
                error_log($message);
            }
            $stmt->close();
        }
    }
}

$departments = getDepartments();

// Fetch courses if a department is selected
$selectedDepartment = isset($_POST['department']) ? $_POST['department'] : (isset($_GET['department']) ? $_GET['department'] : null);
$courses = $selectedDepartment ? getCoursesByDepartment($selectedDepartment) : [];

if (!empty($message)) {
    error_log("Message set: " . $message);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Section</title>
    <link rel="stylesheet" type="text/css" href="adviser_styles.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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

        .success {
            background-color: var(--success-color);
            color: white;
        }

        .error {
            background-color: var(--error-color);
            color: white;
        }

        .academic-year {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .academic-year input {
            flex: 1;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
        }
        .btn-primary {
            background-color: #4a90e2;
            color: white;
            padding: 0.9rem 2rem; 
            border: none;
            border-radius: 25px; 
            cursor: pointer;
            font-size: 1.1rem; 
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px; 
            position: relative;
            overflow: hidden;
            font-weight: 600; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            width: auto; 
            min-width: 500px;
        }

        .btn-primary:hover {
            background-color: #3a7bc8;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-primary::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255,255,255,0.7);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }

        .btn-primary:focus:not(:active)::after {
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

        @media (max-width: 768px) {
            .btn-primary {
                padding: 0.8rem 1.8rem;
                font-size: 1rem;
                min-width: 180px;
            }
        }

        @media (max-width: 480px) {
            .btn-primary {
                padding: 0.7rem 1.5rem;
                font-size: 0.9rem;
                min-width: 160px;
            }
        }
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23003366' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }
        @media (max-width: 768px) {
            .dashboard-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .form-control,
            .btn-primary {
                font-size: 16px; 
            }
        }

    </style>
<body>
<div class="header">
        <h1>CAVITE STATE UNIVERSITY-MAIN</h1>
    </div>
    <?php include 'adviser_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="form-container">
            <h2>Add New Section</h2>
            <form method="post" action="" id="addSectionForm">
               <label for="department">Department:</label>
                <select name="department" id="department" required onchange="this.form.submit()">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $selectedDepartment == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="course">Program:</label>
                <select name="course" id="course" required>
                    <option value="">Select Programs</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="year_level">Year Level:</label>
                <select name="year_level" id="year_level" required>
                    <option value="">Select Year Level</option>
                    <option value="First Year">First Year</option>
                    <option value="Second Year">Second Year</option>
                    <option value="Third Year">Third Year</option>
                    <option value="Fourth Year">Fourth Year</option>
                    <option value="Fifth Year">Fifth Year</option>
                    <option value="Irregular">Irregular</option>
                </select>
                <br>
                <label for="section_no">Section no:</label>
                <input type="text" name="section_no" id="section_no" required oninput="validateSectionNo(this)" pattern="\d*" inputmode="numeric">
                <br>
                <label for="academic_year_start">Academic Year:</label>
                <div style="display: flex; gap: 10px;">
                    <input type="number" name="academic_year_start" required min="2000" max="2099" step="1" value="<?php echo date('Y'); ?>" style="width: 45%;">
                    <span style="align-self: center;">to</span>
                    <input type="number" name="academic_year_end" required min="2000" max="2099" step="1" value="<?php echo date('Y') + 1; ?>" style="width: 45%;">
                </div>


                <center><button type="button" id="submitBtn" class="btn btn-primary">Add Section</button></center>
            </form>
        </div>
        <br><br>
        <div class="footer">
            <p>Contact: (123) 456-7890 | Email: info@cvsu.edu.ph | © 2024 Cavite State University. All rights reserved.</p>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addSectionForm');
    const submitBtn = document.getElementById('submitBtn');
    const sectionNoInput = document.getElementById('section_no');

    // Handle department change
    document.getElementById('department').addEventListener('change', function() {
        form.submit();
    });

    // Handle form submission
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Check if all required fields are filled
        if (form.checkValidity()) {
            console.log('Form is valid. Form data:', new FormData(form));
            Swal.fire({
                title: 'Confirm Addition',
                text: "Are you sure you want to add this section?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, add it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('User confirmed. Submitting form...');
                    // Add a hidden input to indicate this is the final submission
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'add_section';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            });
        } else {
            console.log('Form is invalid');
            form.reportValidity();
        }
    });

    // Additional validation for section number
        sectionNoInput.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            if (!/[0-9]/.test(char)) {
                e.preventDefault();
            }
        });


    <?php if (!empty($message)): ?>
    Swal.fire({
        title: '<?php echo strpos($message, 'Error') !== false ? 'Error' : 'Success'; ?>',
        text: '<?php echo addslashes($message); ?>',
        icon: '<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>',
        confirmButtonText: 'OK'
    });
    <?php endif; ?>
});
</script>
</body>
</html>