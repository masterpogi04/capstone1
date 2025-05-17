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

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if ($section_id === 0) {
    die("No valid section ID provided.");
}

// Handle delete action
if (isset($_POST['delete_student'])) {
    $student_id = intval($_POST['student_id']);
    $delete_sql = "DELETE FROM tbl_student WHERE student_id = ? AND section_id = ?";
    $delete_stmt = $connection->prepare($delete_sql);
    if ($delete_stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    $delete_stmt->bind_param("ii", $student_id, $section_id);
    if (!$delete_stmt->execute()) {
        die("Execute failed: " . $delete_stmt->error);
    }
    $delete_stmt->close();
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?section_id=" . $section_id);
    exit();
}

// Handle edit action
if (isset($_POST['edit_student'])) {
    $student_id = intval($_POST['student_id']);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $password = $_POST['password'];
    
    $edit_sql = "UPDATE tbl_student SET first_name = ?, last_name = ?, middle_name = ?, email = ?, gender = ?";
    $params = [$first_name, $last_name, $middle_name, $email, $gender];
    
    if (!empty($password)) {
        $edit_sql .= ", password = ?";
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $params[] = $hashed_password;
    }
    
    $edit_sql .= " WHERE student_id = ? AND section_id = ?";
    $params[] = $student_id;
    $params[] = $section_id;
    
    $edit_stmt = $connection->prepare($edit_sql);
    
    if ($edit_stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    
    $edit_stmt->bind_param(str_repeat('s', count($params)), ...$params);
    
    if (!$edit_stmt->execute()) {
        die("Execute failed: " . $edit_stmt->error);
    }
    
    $edit_stmt->close();
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF'] . "?section_id=" . $section_id);
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
$sql = "SELECT * FROM tbl_student WHERE section_id = ?";

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
    <link rel="stylesheet" type="text/css" href="adviser_styles.css">
    <title>Section Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    </head>
        <style>

            .main-content {
                margin-left: 250px;
                padding: 40px;
                padding-top: 30px;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                background-color: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }


            h2, h3 {
                color: #2c3e50;
                font-family: 'Century Gothic', Arial, sans-serif;
            }

            h2 {
                font-size: 2.5em;
                margin-bottom: 30px;
                text-align: center;
            }

            h3 {
                font-size: 1.8em;
                margin-top: 30px;
                margin-bottom: 20px;
            }


           /* Table Styles */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 20px;
    background-color: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

    th,td {
        padding: 7px 12px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    

th {
    background: #009E60;
    color: #ffffff;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-size: 14px;
}

tr:nth-child(even) {
    background-color: #f8f9fa;
}

tr {
    transition: all 0.3s ease;
}

tr:hover {
    background-color: #e9ecef;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.filter-form {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f8f9fa; /* Light gray background */
    border-radius: 2px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filter-form label {
    margin-right: 10px;
    font-weight: bold;
    color: #495057;
}

.filter-form select {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: #ffffff; /* White background for select */
    color: #495057;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.filter-form select:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.filter-form button {
    padding: 8px 15px;
    background-color: #007bff;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-size: 14px;
    font-weight: bold;
}

.filter-form button:hover {
    background-color: #0056b3;
}

/* Enhanced styles for better visual hierarchy */
.sort-container {
    display: flex;
    align-items: center;
   
}

.gender-container {
    display: flex;
    align-items: center;
}

/* Responsive design */
@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .sort-container, .gender-container {
        margin-bottom: 10px;
    }

    .filter-form button {
        width: 100%;
    }
}

             /* Button Styles */
        .btn-edit, .btn-delete {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            transition: all 0.3s ease;
            margin-right: 10px;
            border: none;
        }

        .btn-edit {
            background-color: #3498db;
        }

        .btn-edit:hover {
            background-color: #2980b9;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-delete  {
            background-color: #e74c3c;
        }

        .btn-delete:hover {
            background-color: #c0392b;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }


            .modal {
                display: none;
                position: fixed;
                z-index: 1;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
            }

            .modal-content {
                background-color: #fefefe;
                margin: 10% auto;
                padding: 30px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            }

            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                transition: color 0.3s ease;
            }

            .close:hover,
            .close:focus {
                color: #000;
            }

            #editForm {
                display: flex;
                flex-direction: column;
            }

            #editForm label {
                margin-top: 15px;
                font-weight: bold;
            }

            #editForm input[type="text"],
            #editForm input[type="email"],
            #editForm select {
                width: 100%;
                padding: 10px;
                margin-top: 5px;
                margin-bottom: 15px;
                border: 1px solid #ced4da;
                border-radius: 4px;
                box-sizing: border-box;
            }

            #editForm button {
                background-color: #007bff;
                color: white;
                padding: 12px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                align-self: flex-start;
                transition: background-color 0.3s ease;
            }

            #editForm button:hover {
                background-color: #0056b3;
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

          
        .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

h3 {
    margin: 0; /* Remove default margin to align properly */
}

.top-buttons {
    display: flex;
    align-items: center;
}

.settings-dropdown {
    position: relative;
    display: inline-block;
}

.settings-icon {
    color: #FFB347;
    font-size: 24px;
    cursor: pointer;
    transition: color 0.3s ease;
}

.settings-icon:hover {
    color: #FFA500;
}

.settings-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
    border-radius: 4px;
}

.settings-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.settings-content a:hover {
    background-color: #f1f1f1;
}

.show {
    display: block;
}

.search-bar {
    margin-top: 20px;
}

.search-bar input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .top-buttons {
        margin-top: 10px;
    }

    .search-bar {
        width: 100%;
    }
}
/* Top Buttons and Settings Container */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.top-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Settings Dropdown Styling */
.settings-dropdown {
    position: relative;
    display: inline-block;
}

.settings-icon {
    color: #FFB347;
    font-size: 24px;
    cursor: pointer;
    transition: color 0.3s ease;
    padding: 8px;
    border-radius: 50%;
    background-color: rgba(255, 179, 71, 0.1);
}

.settings-icon:hover {
    color: #FFA500;
    background-color: rgba(255, 179, 71, 0.2);
}

.settings-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: #ffffff;
    min-width: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
    border-radius: 8px;
    margin-top: 5px;
    border: 1px solid rgba(0,0,0,0.1);
}

.settings-content a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    transition: all 0.3s ease;
    font-size: 14px;
}

.settings-content a:hover {
    background-color: #f8f9fa;
    color: #000;
}

.show {
    display: block;
}

/* Touch Device Optimizations */
@media (hover: none) {
    .settings-icon {
        padding: 12px;
        font-size: 28px;
    }

    .settings-content a {
        padding: 16px 20px;
        font-size: 16px;
    }
}

/* Responsive Breakpoints */
@media screen and (max-width: 1200px) {
    .settings-content {
        min-width: 180px;
    }
}

@media screen and (max-width: 992px) {
    .section-header {
        padding: 0 10px;
    }
}

@media screen and (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .top-buttons {
        align-self: flex-end;
    }

    .settings-content {
        right: 0;
        min-width: 220px;
    }
}

@media screen and (max-width: 576px) {
    .settings-icon {
        font-size: 22px;
        padding: 8px;
    }

    .settings-content {
        position: fixed;
        top: auto;
        bottom: 0;
        right: 0;
        left: 0;
        width: 100%;
        min-width: 100%;
        border-radius: 15px 15px 0 0;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
    }

    .settings-content a {
        text-align: center;
        padding: 16px;
        border-bottom: 1px solid #eee;
    }

    .settings-content a:last-child {
        border-bottom: none;
        padding-bottom: 25px;
    }
}
             .save-button{
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
            .table-responsive::-webkit-scrollbar {
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #009E60;
    border-radius: 4px;
}

@media screen and (max-width: 768px) {
    .container {
        height: calc(100vh - 80px);
        padding: 15px;
    }
}

           /* Responsive Breakpoints */
@media screen and (max-width: 1400px) {
    .container {
        width: 98%;
    }
}

@media screen and (max-width: 1200px) {
    .main-content {
        margin-left: 250px;
        padding: 30px 20px;
    }
    
    h2 {
        font-size: 1.75rem;
    }
    
    th, td {
        padding: 12px 10px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 12px;
        min-width: 70px;
    }

    .modal-content {
        width: 90%;
        max-width: 600px;
    }
}

@media screen and (max-width: 992px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
    
    table {
        display: block;
        overflow-x: auto;
    }
    
    td, th {
        min-width: 120px;
        padding: 12px 10px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 12px;
        min-width: 70px;
    }

    .filter-form {
        flex-direction: column;
        gap: 10px;
    }

    .sort-container, .gender-container {
        width: 100%;
    }
}

@media screen and (max-width: 768px) {
    .container {
        padding: 15px;
        width: 100%;
    }
    
    h2 {
        font-size: 1.5rem;
        margin: 10px 0 20px;
    }
    
    td, th {
        padding: 10px 8px;
        font-size: 13px;
    }
    
    .btn {
        padding: 5px 10px;
        font-size: 11px;
        min-width: 60px;
    }
    
    .action-buttons, .edit-actions {
        flex-direction: column;
        gap: 5px;
    }

    .modal-content {
        width: 95%;
        margin: 5% auto;
        padding: 20px;
    }

    .save-button {
        min-width: 200px;
        font-size: 0.9rem;
    }

    .filter-form select, .filter-form button {
        width: 100%;
    }
}

@media screen and (max-width: 576px) {
    .main-content {
        padding: 15px 10px;
    }
    
    .container {
        padding: 10px;
    }
    
    h2 {
        font-size: 1.25rem;
    }
    
    td, th {
        padding: 8px 6px;
        font-size: 12px;
        min-width: 100px;
    }
    
    .btn {
        padding: 4px 8px;
        font-size: 10px;
        min-width: 50px;
    }

    .modern-back-button {
        padding: 6px 12px;
        font-size: 0.85rem;
    }

    .actions-cell {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .btn-edit, .btn-delete {
        width: 100%;
        margin: 2px 0;
    }
}

/* Touch Device Optimizations */
@media (hover: none) {
    .btn {
        padding: 10px 16px;
        font-size: 14px;
        min-width: 80px;
    }
    
    td, th {
        padding: 15px 10px;
    }
    
    select.form-control {
        height: 40px;
    }

    .btn-edit, .btn-delete {
        padding: 12px 20px;
    }

    .filter-form select, .filter-form button {
        height: 44px;
    }
}

    </style>
</head>
<body>
   <div class="main-content">
   <div class="table-responsive">
        <div class="container">
        <a href="view_section.php" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back</span>
</a>

            <h2>Section Details</h2>
            <h3><?php echo htmlspecialchars($section['course_name'] . ' - ' . $section['year_level'] . " - Section " . $section['section_no']); ?></h3>
            <p>Academic Year: <?php echo htmlspecialchars($section['academic_year']); ?></p>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
        
            <div class="section-header">
    <h3>Students</h3>
    <div class="top-buttons">
        <div class="settings-dropdown">
            <i onclick="toggleSettings()" class="fas fa-cog settings-icon"></i>
            <div id="settingsDropdown" class="settings-content">
                <a href="register_student.php?section_id=<?php echo $section_id; ?>">Register Student</a>
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
                <select name="sort" id="sort">
                    <option value="asc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'asc' ? 'selected' : ''; ?>>A-Z</option>
                    <option value="desc" <?php echo isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'selected' : ''; ?>>Z-A</option>
                </select>
                <label for="gender">Filter by gender:</label>
                <select name="gender" id="gender">
                    <option value="">All</option>
                    <option value="Male" <?php echo isset($_GET['gender']) && $_GET['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo isset($_GET['gender']) && $_GET['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
                <button type="submit">Apply Filters</button>
            </form>
            <?php
            // Move the student fetching code here
            $sql = "SELECT * FROM tbl_student WHERE section_id = ?";

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
                            <th>Gender</th>
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
                                    <button class="btn btn-edit" 
                                        data-id="<?php echo $student['student_id']; ?>"
                                        data-firstname="<?php echo htmlspecialchars($student['first_name']); ?>"
                                        data-lastname="<?php echo htmlspecialchars($student['last_name']); ?>"
                                        data-middlename="<?php echo htmlspecialchars($student['middle_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                        data-gender="<?php echo htmlspecialchars($student['gender']); ?>"
                                        onclick="openEditModal(this)">Edit</button>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                        <button type="submit" name="delete_student" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this student?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No students found matching the current filters.</p>
            <?php endif; ?>
            
        </div>
    </div>

    <div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Student</h2>
        <form method="post" id="editForm">
            <input type="hidden" id="edit_student_id" name="student_id">
            <label for="edit_first_name">First Name:</label>
            <input type="text" id="edit_first_name" name="first_name" required><br>
            <label for="edit_last_name">Last Name:</label>
            <input type="text" id="edit_last_name" name="last_name" required><br>
            <label for="edit_middle_name">Middle Name:</label>
            <input type="text" id="edit_middle_name" name="middle_name"><br>
            <label for="edit_email">Email:</label>
            <input type="email" id="edit_email" name="email" required><br>
            <label for="edit_password">Password:</label>
            <input type="password" id="edit_password" name="password"><br>
            <div class="gender-group">
    <label>Gender:</label>
    <div class="radio-group">
        <div class="radio-item">
            <input type="radio" id="edit_gender_male" name="gender" value="Male" required>
            <label for="edit_gender_male">Male</label>
        </div>
        <div class="radio-item">
            <input type="radio" id="edit_gender_female" name="gender" value="Female" required>
            <label for="edit_gender_female">Female</label>
        </div>
    </div>
</div>
            <center><button type="submit" name="edit_student" class="save-button">Save Changes</button></center>
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
        var firstName = button.getAttribute('data-firstname');
        var lastName = button.getAttribute('data-lastname');
        var middleName = button.getAttribute('data-middlename');
        var email = button.getAttribute('data-email');
        var gender = button.getAttribute('data-gender');

        document.getElementById("edit_student_id").value = id;
        document.getElementById("edit_first_name").value = firstName;
        document.getElementById("edit_last_name").value = lastName;
        document.getElementById("edit_middle_name").value = middleName;
        document.getElementById("edit_email").value = email;
        document.getElementById("edit_password").value = ''; // Clear password field
        
        // Set the correct radio button for gender
        if (gender === "Male") {
            document.getElementById("edit_gender_male").checked = true;
        } else if (gender === "Female") {
            document.getElementById("edit_gender_female").checked = true;
        }

        originalFormData = new FormData(editForm);
        modal.style.display = "block";
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
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById('studentSearch');
            filter = input.value.toUpperCase();
            table = document.querySelector('table');
            tr = table.getElementsByTagName('tr');

            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName('td')[1]; // Index 1 is for the Name column
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        });
    </script>
</body>
</html>