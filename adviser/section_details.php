<?php
//section details
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

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if ($section_id === 0) {
    die("No valid section ID provided.");
}

if (isset($_POST['delete_student'])) {
    $student_id = intval($_POST['student_id']);
    
    // Check if student has unresolved violations
    $check_violations_sql = "SELECT COUNT(*) as unresolved_count 
                            FROM student_violations 
                            WHERE student_id = ? 
                            AND status NOT IN ('Settled', 'Referred')";
    $check_violations_stmt = $connection->prepare($check_violations_sql);
    if ($check_violations_stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    $check_violations_stmt->bind_param("i", $student_id);
    $check_violations_stmt->execute();
    $result = $check_violations_stmt->get_result();
    $unresolved_count = $result->fetch_assoc()['unresolved_count'];
    $check_violations_stmt->close();

    if ($unresolved_count > 0) {
        $_SESSION['error_message'] = "Cannot delete student because they have unresolved violations (not Settled or Referred).";
        echo "<script>window.location.href = 'section_details.php?section_id=" . $section_id . "';</script>";
        exit();
    }

    $disable_sql = "UPDATE tbl_student SET status = 'disabled' WHERE student_id = ?";
    $disable_stmt = $connection->prepare($disable_sql);
    if ($disable_stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    $disable_stmt->bind_param("i", $student_id);
    if (!$disable_stmt->execute()) {
        die("Execute failed: " . $disable_stmt->error);
    }
    $disable_stmt->close();
    
    $_SESSION['success_message'] = "Student has been successfully disabled.";
    echo "<script>window.location.href = 'section_details.php?section_id=" . $section_id . "';</script>";
    exit();
}


if (isset($_POST['edit_student'])) {
    $student_id = intval($_POST['student_id']);
    $first_name = strtoupper(trim($_POST['first_name']));
    $last_name = strtoupper(trim($_POST['last_name']));
    $middle_name = strtoupper(trim($_POST['middle_name']));
    $gender = strtoupper(trim($_POST['gender']));

    // Check for email availability if email is provided
    if ($email !== null) {
        // Check students table (excluding current student)
        $check_student_sql = "SELECT 1 FROM tbl_student WHERE email = ? AND student_id != ?";
        $check_student_stmt = $connection->prepare($check_student_sql);
        if ($check_student_stmt) {
            $check_student_stmt->bind_param("ss", $email, $student_id);
            $check_student_stmt->execute();
            if ($check_student_stmt->get_result()->num_rows > 0) {
                $_SESSION['edit_status'] = 'error';
                $_SESSION['edit_message'] = 'Email address is not available.';
                $check_student_stmt->close();
                echo "<script>window.location.href = 'section_details.php?section_id=" . $section_id . "';</script>";
                exit();
            }
            $check_student_stmt->close();
        }

        // Check advisers table
        $check_adviser_sql = "SELECT 1 FROM tbl_adviser WHERE email = ?";
        $check_adviser_stmt = $connection->prepare($check_adviser_sql);
        if ($check_adviser_stmt) {
            $check_adviser_stmt->bind_param("s", $email);
            $check_adviser_stmt->execute();
            if ($check_adviser_stmt->get_result()->num_rows > 0) {
                $_SESSION['edit_status'] = 'error';
                $_SESSION['edit_message'] = 'Email address is not available.';
                $check_adviser_stmt->close();
                echo "<script>window.location.href = 'section_details.php?section_id=" . $section_id . "';</script>";
                exit();
            }
            $check_adviser_stmt->close();
        }

        // Check admin table
        $check_admin_sql = "SELECT 1 FROM tbl_admin WHERE email = ?";
        $check_admin_stmt = $connection->prepare($check_admin_sql);
        if ($check_admin_stmt) {
            $check_admin_stmt->bind_param("s", $email);
            $check_admin_stmt->execute();
            if ($check_admin_stmt->get_result()->num_rows > 0) {
                $_SESSION['edit_status'] = 'error';
                $_SESSION['edit_message'] = 'Email address is not available.';
                $check_admin_stmt->close();
                echo "<script>window.location.href = 'section_details.php?section_id=" . $section_id . "';</script>";
                exit();
            }
            $check_admin_stmt->close();
        }

        // Check coordinator table
        $check_coord_sql = "SELECT 1 FROM tbl_dean WHERE email = ?";
        $check_coord_stmt = $connection->prepare($check_coord_sql);
        if ($check_coord_stmt) {
            $check_coord_stmt->bind_param("s", $email);
            $check_coord_stmt->execute();
            if ($check_coord_stmt->get_result()->num_rows > 0) {
                $_SESSION['edit_status'] = 'error';
                $_SESSION['edit_message'] = 'Email address is not available.';
                $check_coord_stmt->close();
                echo "<script>window.location.href = 'section_details.php?section_id=" . $section_id . "';</script>";
                exit();
            }
            $check_coord_stmt->close();
        }

        // Add checks for any other user tables you have
        // Example: staff, teachers, etc.
    }

    // If all email checks pass, proceed with update
    $edit_sql = "UPDATE tbl_student SET first_name = ?, last_name = ?, middle_name = ?, email = ?, gender = ?";
    $params = [$first_name, $last_name, $middle_name, $email, $gender];
    
    $edit_sql .= " WHERE student_id = ? AND section_id = ?";
    $params[] = $student_id;
    $params[] = $section_id;
    
    $edit_stmt = $connection->prepare($edit_sql);
    
    if ($edit_stmt === false) {
        $_SESSION['edit_status'] = 'error';
        $_SESSION['edit_message'] = 'Failed to update student information.';
    } else {
        $edit_stmt->bind_param(str_repeat('s', count($params)), ...$params);
        
        if (!$edit_stmt->execute()) {
            $_SESSION['edit_status'] = 'error';
            $_SESSION['edit_message'] = 'Failed to update student information.';
        } else {
            $_SESSION['edit_status'] = 'success';
            $_SESSION['edit_message'] = 'Student information updated successfully!';
        }
        
        $edit_stmt->close();
    }
    
    echo "<script>window.location.href = 'section_details.php?section_id=" . $section_id . "';</script>";
    exit();
}

// Fetch section details
$sql = "SELECT s.*, d.name AS department_name, c.name AS course_name 
        FROM sections s
        LEFT JOIN departments d ON d.id = s.department_id
        LEFT JOIN courses c ON c.id = s.course_id
        WHERE s.id = ? AND s.adviser_id = ?";
$stmt = $connection->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $connection->error);
}

$stmt->bind_param("ii", $section_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$section = $result->fetch_assoc();
$stmt->close();

if (!$section) {
    die("Section not found or you don't have permission to view it.");
}

// Fetch students in this section with sorting and filtering
$sql = "SELECT * FROM tbl_student WHERE section_id = ? AND status = 'active'";

// Add gender filter if set
if (isset($_GET['gender']) && $_GET['gender'] != '') {
    $sql .= " AND gender = ?";
}

// Add sorting
$sql .= " ORDER BY last_name " . (isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'DESC' : 'ASC');

$stmt = $connection->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $connection->error);
}

if (isset($_GET['gender']) && $_GET['gender'] != '') {
    $stmt->bind_param("is", $section_id, $_GET['gender']);
} else {
    $stmt->bind_param("i", $section_id);
}

$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Section Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" type="text/css" href="section_details.css">
    </head>
<style>
            
            .container {
                        max-width: 1500px;
                        margin: 0 auto;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
                    }
        /* Refined table styles with hover effect */
        .table-responsive {
            margin: 20px 0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 0.5px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 0.1px solid #e0e0e0;
        }

        /* Header styles */
        th:first-child {
            border-top-left-radius: 10px;
        }

        th:last-child {
            border-top-right-radius: 10px;
        }

        thead th {
            background: #009E60;
            color: #ffffff;
            font-weight: 600;
            text-transform: uppercase;
            padding: 15px;
            font-size: 14px;
            letter-spacing: 0.5px;
            white-space: nowrap;
            text-align: center;
        }

        thead th:last-child {
            border-right: none;
        }

        /* Cell styles */
        td {
            padding: 12px 15px;
            vertical-align: middle;
            border-right: 0.1px solid #e0e0e0;
            border-bottom: 0.1px solid #e0e0e0;
            font-size: 14px;
            text-align: center;
            background-color: transparent; /* Changed from white to transparent */
        }

        td:last-child {
            border-right: none;
        }

        /* Bottom rounded corners for last row */
        tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }

        tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }

        /* Row hover effect */
        tbody tr {
            background-color: white; /* Base background color for rows */
            transition: background-color 0.2s ease; /* Smooth transition for hover */
        }

        tbody tr:hover {
            background-color: #f8f9fa; /* Lighter background on hover */
        }

        /* Actions cell specific styling */
        .actions-cell {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

                    body{
            background: linear-gradient(135deg,  #0d693e, #004d4d);
                        }
                    

                    h1{
                        border-bottom: 3px solid #004d4d;
                        margin: 15px 0 30px;
                        padding-bottom: 15px;
                        font-weight: 650;
            font-size: 3rem;
                    }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .modal .form-container {
            max-width: 600px;
            margin: 1.5% auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            position: relative;
        }

        .modal h2 {
            color: #003366;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 0.5rem;
        }

        .modal .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .modal .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: bold;
            color: #003366;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }

        .modal .form-control {
            width: 100%;
            padding: 0.6rem;
            border: 2px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }

        .modal .form-select {
            width: 100%;
            padding: 0.6rem;
            border: 2px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
            color: #003366;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23003366' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        .modal .form-control:focus,
        .modal .form-select:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .modal .save-button {
            background-color: #4a90e2;
            color: white;
            padding: 0.7rem 1rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
            overflow: hidden;
            font-weight: 400;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: auto;
            min-width: 400px;
        }

        .modal .save-button:hover {
            background-color: #3a7bc8;
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .modal .close {
            position: absolute;
            left: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #003366;
            cursor: pointer;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .modal .form-container {
                width: 90%;
                margin: 10% auto;
                padding: 1.5rem;
            }
            
            .modal .save-button {
                min-width: 200px;
                font-size: 0.9rem;
                padding: 0.6rem 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .modal .form-container {
                width: 95%;
                padding: 1rem;
            }

            .modal h2 {
                font-size: 1.5rem;
            }

            .modal .form-control,
            .modal .form-select {
                font-size: 0.85rem;
                padding: 0.5rem;
            }

            .modal .save-button {
                min-width: 180px;
                font-size: 0.8rem;
                padding: 0.5rem 0.8rem;
            }
        }


</style>
</head>
<body>
    <?php
if (isset($_SESSION['error_message'])) {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '" . addslashes($_SESSION['error_message']) . "',
                confirmButtonColor: '#dc3545'
            });
        });
    </script>";
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '" . addslashes($_SESSION['success_message']) . "',
                confirmButtonColor: '#28a745'
            });
        });
    </script>";
    unset($_SESSION['success_message']);
}
?>


   <div class="main-content">
   <div class="table-responsive table-striped">
        <div class="container">
        <a href="view_section.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
    <span>Back</span>
</a>

            <h1 style="text-align: center; padding-bottom:10px">Section Details</h1>
            <h4 style="text-align: center;"><?php echo htmlspecialchars($section['course_name'] . ' - ' . $section['year_level'] . " - Section " . $section['section_no']); ?></h4>
            <p style="text-align: center; font-size:20px">Academic Year: <?php echo htmlspecialchars($section['academic_year']); ?></p>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
        
            <div class="section-header">
                <p></p>
    <div class="top-buttons">
        <div class="settings-dropdown">
            <i onclick="toggleSettings()" style="color:black;"class="fas fa-plus settings-icon"></i>
            <div id="settingsDropdown" class="settings-content">
                <a href="register_student.php?section_id=<?php echo $section_id; ?>">Add Student</a>
                <a href="bulk_upload.php?section_id=<?php echo $section_id; ?>">Bulk Upload</a>
            </div>
        </div>
    </div>
</div>

<div class="search-bar">
    <input type="text" id="studentSearch" placeholder="Search for students...">
</div>

           <form method="get" action="" class="filter-form">
            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
            <label for="sort">Sort by:</label>
            <select style="margin-right: 40px;" name="sort" id="sort" onchange="this.form.submit()">
                <option value="asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'selected' : ''; ?>>A-Z</option>
                <option value="desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'selected' : ''; ?>>Z-A</option>
            </select>
            <label for="gender">Filter by sex:</label>
            <select style="margin-right: 40px;" name="gender" id="gender" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="Male" <?php echo isset($_GET['gender']) && $_GET['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo isset($_GET['gender']) && $_GET['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
            </select>
            <a href="section_details.php?section_id=<?php echo $section_id; ?>" class="btn btn-secondary">Reset Filters</a>
        </form>
            <?php
            // Move the student fetching code here
            $sql = "SELECT * FROM tbl_student WHERE section_id = ? AND status = 'active'";

            // Add gender filter if set
            if (isset($_GET['gender']) && $_GET['gender'] != '') {
                $sql .= " AND gender = ?";
            }

            // Add sorting
            $sql .= " ORDER BY last_name " . (isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'DESC' : 'ASC');

            $stmt = $connection->prepare($sql);

            if ($stmt === false) { 
                die("Error preparing statement: " . $connection->error);
            }

            if (isset($_GET['gender']) && $_GET['gender'] != '') {
                $stmt->bind_param("is", $section_id, $_GET['gender']);
            } else {
                $stmt->bind_param("i", $section_id);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            ?>

            <?php if (count($students) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Sex</th>
                            <th>Registration Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email'] ?? 'Not registered'); ?></td>
                                <td><?php echo htmlspecialchars($student['gender']); ?></td>
                                <td><?php echo $student['email'] ? 'Registered' : 'Not Registered'; ?></td>
                                <td class="actions-cell">
                                    <button class="btn btn-edit" 1
                                        data-id="<?php echo $student['student_id']; ?>"
                                        data-firstname="<?php echo htmlspecialchars($student['first_name']); ?>"
                                        data-lastname="<?php echo htmlspecialchars($student['last_name']); ?>"
                                        data-middlename="<?php echo htmlspecialchars($student['middle_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                        data-gender="<?php echo htmlspecialchars($student['gender']); ?>"
                                        onclick="openEditModal(this)"><i class="fas fa-pencil-alt"></i></button>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <button type="submit" name="delete_student" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this student?')"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No student/s found.</p>
            <?php endif; ?>
            
        </div>
    </div>

    <div id="editModal" class="modal">
    <div class="form-container">
        <span class="close">&times;</span>
        <br>
        <h2>Edit Student Information</h2>
        <form method="post" id="editForm">
            <input type="hidden" id="edit_student_id" name="student_id">
            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
            
            <div class="form-group">
                <label for="edit_first_name">First Name:</label>
                <input type="text" class="form-control" id="edit_first_name" name="first_name" required pattern="[A-Za-z ]+" title="Only letters are allowed">
            </div>

            <div class="form-group">
                <label for="edit_last_name">Last Name:</label>
                <input type="text" class="form-control" id="edit_last_name" name="last_name" required pattern="[A-Za-z ]+" title="Only letters are allowed">
            </div>

            <div class="form-group">
                <label for="edit_middle_name">Middle Initial:</label>
                <input type="text" class="form-control" id="edit_middle_name" name="middle_name" maxlength="1" pattern="[A-Za-z]" title="Single letter only">
            </div>


            <div class="form-group">
                <label>Sex:</label>
                <select class="form-select" name="gender" id="edit_gender" required>
                    <option value="" hidden>Select Sex</option>
                    <option value="MALE">Male</option>
                    <option value="FEMALE">Female</option>
                </select>
            </div>

            <center><button style="border-radius: 25px;" type="submit" name="edit_student" class="btn btn-primary save-button">Save Changes</button></center>
        </form>
    </div>
</div>

<script>
 var modal = document.getElementById("editModal");
        var span = document.getElementsByClassName("close")[0];
        var editForm = document.getElementById("editForm");
        var originalFormData;

function openEditModal(button) {
    var id = button.getAttribute('data-id');
    var firstName = button.getAttribute('data-firstname').toUpperCase();
    var lastName = button.getAttribute('data-lastname').toUpperCase();
    var middleName = button.getAttribute('data-middlename').toUpperCase();
    var gender = button.getAttribute('data-gender').toUpperCase();

    document.getElementById("edit_student_id").value = id;
    document.getElementById("edit_first_name").value = firstName;
    document.getElementById("edit_last_name").value = lastName;
    document.getElementById("edit_middle_name").value = middleName;
    document.getElementById("edit_gender").value = gender;

    originalFormData = new FormData(editForm);
    modal.style.display = "block";

    // Add event listeners for name fields
    const nameFields = ['edit_first_name', 'edit_middle_name', 'edit_last_name'];
    
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        
        field.addEventListener('input', function(e) {
            this.value = this.value.replace(/[0-9]/g, '').toUpperCase();
        });

        field.addEventListener('keypress', function(e) {
            if (/[0-9]/.test(e.key)) {
                e.preventDefault();
            }
        });

        field.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            const sanitizedText = text.replace(/[0-9]/g, '').toUpperCase();
            document.execCommand('insertText', false, sanitizedText);
        });
    });
}


        span.onclick = function() {
    if (isFormChanged()) {
        if (confirm("You have unsaved changes. Are you sure you want to close without saving?")) {
            modal.style.display = "none";
        }
    } else {
        modal.style.display = "none";
    }
}

// Window click handler (clicking outside the modal)
window.onclick = function(event) {
    if (event.target == modal) {
        if (isFormChanged()) {
            if (confirm("You have unsaved changes. Are you sure you want to close without saving?")) {
                modal.style.display = "none";
            }
        } else {
            modal.style.display = "none";
        }
    }
    
    // Settings dropdown handling
    if (!event.target.matches('.settings-icon')) {
        var dropdowns = document.getElementsByClassName("settings-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}

        editForm.onsubmit = function(event) {
            if (!isFormChanged()) {
                event.preventDefault();
                alert("No changes were made.");
            } else if (!confirm("Are you sure you want to save these changes?")) {
                event.preventDefault();
            }
        }

        function isFormChanged() {
            var currentFormData = new FormData(editForm);
            for (var pair of currentFormData.entries()) {
                if (pair[1] !== originalFormData.get(pair[0])) {
                    return true;
                }
            }
            return false;
        }
        
        function toggleSettings() {
            document.getElementById("settingsDropdown").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.settings-icon')) {
                var dropdowns = document.getElementsByClassName("settings-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }

        document.getElementById('studentSearch').addEventListener('keyup', function() {
            var input, filter, table, tr, tdName, tdId, i, txtValueName, txtValueId;
            input = document.getElementById('studentSearch');
            filter = input.value.toUpperCase();
            table = document.querySelector('table');
            tr = table.getElementsByTagName('tr');

            for (i = 0; i < tr.length; i++) {
                tdName = tr[i].getElementsByTagName('td')[1]; // Name column
                tdId = tr[i].getElementsByTagName('td')[0];   // Student ID column
                
                if (tdName && tdId) {
                    txtValueName = tdName.textContent || tdName.innerText;
                    txtValueId = tdId.textContent || tdId.innerText;
                    
                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || 
                        txtValueId.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }

            // Check if all rows are hidden
    var allHidden = true;
    for (i = 1; i < tr.length; i++) { // Start from 1 to skip header row
        if (tr[i].style.display !== 'none') {
            allHidden = false;
            break;
        }
    }

    // Show or hide "no results" message
    var noResults = document.getElementById('noResults');
    if (allHidden) {
        if (!noResults) {
            noResults = document.createElement('p');
            noResults.id = 'noResults';
            noResults.style.textAlign = 'center';
            noResults.style.padding = '20px';
            noResults.innerHTML = 'No student data found.';
            table.parentNode.insertBefore(noResults, table.nextSibling);
        }
        table.style.display = 'none';
    } else {
        if (noResults) {
            noResults.remove();
        }
        table.style.display = '';
    }
        });

       $(document).on('click', '.delete-student-btn', function() {
            const form = $(this).closest('form');
            const studentId = form.find('input[name="student_id"]').val();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently disable the student account!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, disable it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form via AJAX
                    $.ajax({
                        type: 'POST',
                        url: 'section_details.php?section_id=<?php echo $section_id; ?>',
                        data: form.serialize(),
                        success: function(response) {
                            // Reload the page to see changes
                            window.location.reload();
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to disable student. Please try again.'
                            });
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>