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

$adviser_id = $_SESSION['user_id'];
$message = '';

// Helper function to standardize year level format
function formatYearLevel($yearLevel) {
    return ucwords(strtolower(trim($yearLevel)));
}

// Handle edit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (isset($_POST['section_id']) && isset($_POST['year_level'])) {
        $section_id = $_POST['section_id'];
        $year_level = trim($_POST['year_level']); // Trim any whitespace
        
        try {
            // Log the incoming values for debugging
            error_log("Updating section ID: " . $section_id);
            error_log("New year level: " . $year_level);
            
            // First verify if the section exists for this adviser
            $verify_stmt = $connection->prepare("SELECT id FROM sections WHERE id = ? AND adviser_id = ?");
            $verify_stmt->bind_param("ii", $section_id, $adviser_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            
            if ($verify_result->num_rows > 0) {
                // Section belongs to adviser, proceed with update
                $update_stmt = $connection->prepare("UPDATE sections SET year_level = ? WHERE id = ?");
                $update_stmt->bind_param("si", $year_level, $section_id);
                
                if ($update_stmt->execute()) {
                    $message = "Section year level successfully updated to " . $year_level;
                } else {
                    throw new Exception("Failed to update year level: " . $update_stmt->error);
                }
                
                $update_stmt->close();
            } else {
                throw new Exception("Section not found or you don't have permission to edit it.");
            }
            
            $verify_stmt->close();
            
        } catch (Exception $e) {
            error_log("Error updating year level: " . $e->getMessage());
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (isset($_POST['section_id'])) {
        $section_id = $_POST['section_id'];
        
        $connection->begin_transaction();

        try {
            $delete_students_stmt = $connection->prepare("DELETE FROM tbl_student WHERE section_id = ?");
            $delete_students_stmt->bind_param("i", $section_id);
            $delete_students_stmt->execute();
            $affected_students = $delete_students_stmt->affected_rows;
            $delete_students_stmt->close();

            $delete_section_stmt = $connection->prepare("DELETE FROM sections WHERE id = ? AND adviser_id = ?");
            $delete_section_stmt->bind_param("ii", $section_id, $adviser_id);
            $delete_section_stmt->execute();
            
            if ($delete_section_stmt->affected_rows > 0) {
                $connection->commit();
                $message = "Section successfully deleted. $affected_students student(s) were also removed.";
            } else {
                throw new Exception("No section found or you do not have permission to delete this section.");
            }
            
            $delete_section_stmt->close();
        } catch (Exception $e) {
            $connection->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch sections
$sql = "SELECT s.*, d.name AS department_name, c.name AS course_name, COUNT(st.student_id) as student_count 
        FROM sections s
        LEFT JOIN tbl_student st ON s.id = st.section_id
        LEFT JOIN departments d ON d.id = s.department_id
        LEFT JOIN courses c ON c.id = s.course_id
        WHERE s.adviser_id = ?
        GROUP BY s.id";

$stmt = $connection->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $connection->error);
}

$stmt->bind_param("i", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();
$sections = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Define year level options
$yearLevels = [
    'First Year',
    'Second Year',
    'Third Year',
    'Fourth Year',
    'Fifth Year',
    'Irregular'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#009E60">
    <title>View Sections</title>
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
   :root {
    --primary-color: #009E60;
    --primary-dark: #00674b;
    --secondary-color: #2EDAA8;
    --secondary-dark: #28C498;
    --text-color: #333;
    --border-color: #ddd;
    --shadow-sm: 0 2px 8px rgba(46, 218, 168, 0.15);
    --shadow-md: 0 4px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    line-height: 1.5;
    color: var(--text-color);
}


.main-content {
    margin-left: 250px;
    padding: 20px;
    transition: var(--transition);
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: var(--shadow-md);
}

/* Page Title */
h2 {
    color: var(--primary-dark);
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    padding-bottom: 15px;
    border-bottom: 3px solid var(--primary-dark);
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

/* Table Styles */
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: white;
    border-radius: 8px;
    box-shadow: var(--shadow-md);
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

thead {
    background-color: var(--primary-color);
}

th {
    color: white;
    font-weight: 600;
    text-transform: uppercase;
    padding: 15px;
    font-size: 14px;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

td {
    padding: 12px 15px;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
}

tr:last-child td {
    border-bottom: none;
}

tbody tr:hover {
    background-color: #f8f9fa;
}

/* Large Screens (Above 1200px) */
@media screen and (min-width: 1201px) {
    .table-responsive {
        padding: 0;
    }
    
    td, th {
        padding: 15px 20px;
    }
}

/* Medium Screens (992px - 1200px) */
@media screen and (max-width: 1200px) {
    .table-responsive {
        margin: 15px 0;
    }
    
    td, th {
        padding: 12px 15px;
    }
}

/* Small Screens (768px - 991px) */
@media screen and (max-width: 991px) {
    td, th {
        padding: 10px 12px;
        font-size: 13px;
    }
    
    .btn {
        padding: 6px 10px;
        font-size: 12px;
    }
}

/* Mobile Screens (Below 768px) */
@media screen and (max-width: 767px) {
    .table-responsive {
        border: none;
        box-shadow: none;
    }

    table, thead, tbody, tr, th, td {
        display: block;
    }

    thead {
        display: none;
    }

    /* Convert rows to cards */
    tr {
        margin-bottom: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        background: white;
        position: relative;
        overflow: hidden;
    }

    /* Style cells as flex containers */
    td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        text-align: right;
        min-height: 50px;
    }

    td:last-child {
        border-bottom: none;
    }

    /* Add labels for mobile view */
    td::before {
        content: attr(data-label);
        font-weight: 600;
        text-align: left;
        padding-right: 10px;
        color: #555;
        text-transform: uppercase;
        font-size: 12px;
    }

    /* Special handling for actions column */
    td[data-label="Actions"] {
        padding: 15px;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-start;
    }

    td[data-label="Actions"] .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        width: 100%;
    }

    td[data-label="Actions"] .btn {
        flex: 1;
        min-width: calc(33.333% - 8px);
        margin: 0;
    }
}

/* Extra Small Screens (Below 576px) */
@media screen and (max-width: 575px) {
    tr {
        margin-bottom: 12px;
    }

    td {
        padding: 10px 12px;
        font-size: 13px;
    }

    td::before {
        font-size: 11px;
    }

    td[data-label="Actions"] {
        padding: 12px;
    }

    td[data-label="Actions"] .btn-group {
        flex-direction: column;
    }

    td[data-label="Actions"] .btn {
        width: 100%;
        margin: 0;
    }
}

/* Touch Device Optimizations */
@media (hover: none) {
    td, th {
        padding: 15px;
    }

    .btn {
        min-height: 44px;
        padding: 12px 20px;
    }

    td[data-label="Actions"] .btn {
        padding: 12px 15px;
    }
}

/* Print Styles */
@media print {
    .table-responsive {
        box-shadow: none;
    }

    th {
        background-color: #f8f9fa !important;
        color: black !important;
        border-bottom: 2px solid #ddd;
    }

    td[data-label="Actions"] {
        display: none;
    }

    tr {
        page-break-inside: avoid;
    }
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

//* Button Base Styles */
.btn {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 500;
    text-transform: none;
    letter-spacing: normal;
    border: none;
    margin: 2px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: auto;
    transition: background-color 0.2s ease;
}

/* Button Group Container */
.btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}

/* Specific Button Styles */
.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

.btn-danger {
    background-color: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background-color: #c0392b;
}

.btn-success {
    background-color: #2ecc71;
    color: white;
}

.btn-success:hover {
    background-color: #27ae60;
}

.btn-secondary {
    background-color: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background-color: #7f8c8d;
}

/* Remove transform on hover */
.btn:hover {
    transform: none;
    box-shadow: none;
}

/* Mobile Optimizations */
@media screen and (max-width: 768px) {
    td[data-label="Actions"] {
        flex-direction: column;
        align-items: stretch;
    }

    td[data-label="Actions"] .btn-group {
        flex-direction: row;
        justify-content: flex-start;
    }

    .btn {
        padding: 8px 12px;
        font-size: 14px;
        width: auto;
    }
}

/* Touch Device Optimizations */
@media (hover: none) {
    .btn {
        min-height: 36px;
        padding: 8px 16px;
    }
    
    td[data-label="Actions"] .btn-group {
        gap: 8px;
    }
}

/* Action Cell Specific Styles */
td[data-label="Actions"] {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding: 8px 12px;
}

td[data-label="Actions"] form {
    margin: 0;
}

/* Edit Form Buttons */
.edit-year-form .btn-group {
    margin-top: 8px;
    justify-content: flex-start;
}

/* Form Controls */
.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #009E60;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 158, 96, 0.1);
}

/* Select Input */
select.form-control {
    width: 100%;
    height: 15%;
    padding: 5px;
    font-size: 14px;
    color: #333;
    background-color: #fff;
    border: 1px solid #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 5px;
}

/* Edit Actions */
.edit-actions {
    display: flex;
    gap: 8px;
    margin-top: 5px;
}


/* Mobile-First Table Layout */
@media screen and (max-width: 768px) {
    table {
        display: block;
    }

    thead {
        display: none;
    }

    tr {
        display: block;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
    }

    td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    td:before {
        content: attr(data-label);
        font-weight: 600;
        margin-right: 10px;
    }

    td:last-child {
        border-bottom: none;
    }
}

/* Responsive Breakpoints */
@media screen and (max-width: 1200px) {
    .main-content {
        margin-left: 200px;
    }
    
    .container {
        width: 95%;
    }
}
@media screen and (max-width: 992px) {
    .container {
        width: 100%;
        padding: 15px;
    }
    
    h2 {
        font-size: 1.8rem;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 13px;
    }
}
@media screen and (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }

    /* Table Mobile Layout */
    table, thead, tbody, tr, th, td {
        display: block;
    }

    thead {
        display: none;
    }

    tr {
        margin-bottom: 15px;
        border-radius: 8px;
        box-shadow: var(--shadow-sm);
        background: white;
    }

    td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: right;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }

    td::before {
        content: attr(data-label);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
    }


    .btn {
        padding: 8px 20px;
        font-size: 13px;
    }
}

@media screen and (max-width: 576px) {
    .container {
        padding: 10px;
    }

    h2 {
        font-size: 1.5rem;
        margin-bottom: 15px;
    }

    .modern-back-button {
        padding: 8px 12px;
        font-size: 14px;
    }

    .btn {
        padding: 6px 10px;
        font-size: 12px;
    }
}
/* Form Controls */
.form-control {
    width: 100%;
    padding: 10px;
    border: 2px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
    transition: var(--transition);
}

.form-control:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 158, 96, 0.1);
}


/* Print Styles */
@media print {
    .table-responsive {
        box-shadow: none;
    }

    th {
        background-color: #f8f9fa !important;
        color: black !important;
        border-bottom: 2px solid #ddd;
    }

    td[data-label="Actions"] {
        display: none;
    }

    tr {
        page-break-inside: avoid;
    }
}
    </style>


<body>
<div class="header">
        <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'adviser_sidebar.php'; ?>
    <div class="main-content">
    
    <div class="container">
        <a href="adviser_homepage.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
        </a>
        <h2>Your Sections</h2>
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
                <?php if (strpos($message, 'Error') !== false): ?>
                    <br>Please try again or contact support if the issue persists.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (count($sections) > 0): ?>
            <div class="table-responsive">
            <table id="sectionsTable">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Section No</th>
                        <th>Academic Year</th>
                        <th>Number of Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $section): ?>
                        <tr id="section-<?php echo $section['id']; ?>">
                        <td data-label="Department"><?php echo htmlspecialchars($section['department_name']); ?></td>
                        <td data-label="Course"><?php echo htmlspecialchars($section['course_name']); ?></td>
                        <td data-label="Year Level">
                                <span class="year-level-text"><?php echo htmlspecialchars($section['year_level']); ?></span>
                                <form method="post" class="edit-year-form" style="display: none;">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                    <div class="year-level-select">
                                    <select name="year_level" class="form-control">
                                        <?php foreach ($yearLevels as $yearLevel): ?>
                                            <option value="<?php echo $yearLevel; ?>" 
                                                <?php echo formatYearLevel($section['year_level']) === $yearLevel ? 'selected' : ''; ?>>
                                                <?php echo $yearLevel; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    </div>
                                    
                                    <div class="btn-group">
                                    <button type="submit" class="btn btn-success btn-sm mt-2">Save</button>
                                    <button type="button" class="btn btn-secondary btn-sm mt-2 cancel-edit">Cancel</button>
                                </form>
                            </td>
                            <td data-label="Section No"><?php echo htmlspecialchars($section['section_no']); ?></td>
                            <td data-label="Academic Year"><?php echo htmlspecialchars($section['academic_year']); ?></td>
                            <td data-label="Number of Students"><?php echo $section['student_count']; ?></td>
                            <td data-label="Actions">
                            <div class="btn-group">
                                <button class="btn btn-info btn-sm edit-btn">Edit</button>
                                <a href="section_details.php?section_id=<?php echo $section['id']; ?>" 
                                   class="btn btn-primary btn-sm">View</a>
                                <form method="post" style="display:inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this section? This action cannot be undone and will also delete all associated students.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-sections">No added sections in your account.</p>
        <?php endif; ?>
    </div>

    <script>
    $(document).ready(function() {
        // Handle edit button click
        $('.edit-btn').click(function() {
            var row = $(this).closest('tr');
            row.find('.year-level-text').hide();
            row.find('.edit-year-form').show();
            $(this).hide();
        });

        // Handle cancel button click
        $('.cancel-edit').click(function() {
            var row = $(this).closest('tr');
            row.find('.year-level-text').show();
            row.find('.edit-year-form').hide();
            row.find('.edit-btn').show();
        });
    });

    $(document).ready(function() {
    // Handle edit button click
    $('.edit-btn').click(function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        row.find('.year-level-text').hide();
        row.find('.edit-year-form').show();
        $(this).hide();
    });

    // Handle cancel button click
    $('.cancel-edit').click(function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        row.find('.year-level-text').show();
        row.find('.edit-year-form').hide();
        row.find('.edit-btn').show();
    });

    // Add touch device detection
    function isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints;
    }

    // Adjust button sizes for touch devices
    if (isTouchDevice()) {
        $('.btn').addClass('touch-device');
    }
});
    </script>
</body>
</html>