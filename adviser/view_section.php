<?php 
//view_section
ob_start();
session_start();
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');
include "../db.php";

// Check if the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

// Check if database connection is established
if (!isset($connection) || $connection->connect_error) {
    die("Database connection failed: " . ($connection->connect_error ?? "Unknown error"));
}

$adviser_id = $_SESSION['user_id'];
$message = '';

function formatYearLevel($yearLevel) {
    $yearLevel = trim($yearLevel);
    
    // Create mapping for year levels
    $yearMapping = [
        'First Year' => '1',
        'Second Year' => '2',
        'Third Year' => '3',
        'Fourth Year' => '4',
        'Fifth Year' => '5',
        // Add reverse mappings
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5'
    ];
    
    return $yearMapping[$yearLevel] ?? $yearLevel;
}

function calculateNewAcademicYear($currentYear, $oldYearLevel, $newYearLevel) {
    error_log("Calculating academic year:");
    error_log("Current Year: " . $currentYear);
    error_log("Old Level: " . $oldYearLevel);
    error_log("New Level: " . $newYearLevel);

    // Get current year dynamically
    $baseStartYear = (int)date('Y');
    $baseEndYear = $baseStartYear + 1;
    $baseYear = $baseStartYear . ' - ' . $baseEndYear;

    // Map year levels to numeric values
    $yearValues = [
        'First Year' => 1,
        'Second Year' => 2,
        'Third Year' => 3,
        'Fourth Year' => 4,
        'Fifth Year' => 5,

        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5
    ];

    // Get numeric values
    $oldValue = $yearValues[$oldYearLevel] ?? 0;
    $newValue = $yearValues[$newYearLevel] ?? 0;
    
    // If moving to Irregular, return base year
    if ($newValue === 0) {
        error_log("Irregular year detected, returning base year: " . $baseYear);
        return $baseYear;
    }

    // If coming from Irregular, calculate from base year
    if ($oldValue === 0) {
        // Calculate offset from base year (2nd year)
        $yearOffset = $newValue - 2;
        $startYear = $baseStartYear + $yearOffset;
        $endYear = $baseEndYear + $yearOffset;
    } else {
        // Normal year level change
        $currentYear = preg_replace('/\s+/', '', $currentYear);
        if (!preg_match('/^(\d{4})-(\d{4})$/', $currentYear, $matches)) {
            return $currentYear;
        }
        $startYear = intval($matches[1]);
        $endYear = intval($matches[2]);
        
        // Calculate year difference
        $yearDifference = $newValue - $oldValue;
        $startYear += $yearDifference;
        $endYear += $yearDifference;
    }

    // Format with spaces for display
    $newYear = $startYear . ' - ' . $endYear;
    error_log("New academic year: " . $newYear);
    return $newYear;
}

function reactivateSection($connection, $adviser_id, $department_id, $course_id, $year_level, $section_no, $academic_year) {
    $stmt = $connection->prepare("
        SELECT id 
        FROM sections 
        WHERE adviser_id = ? 
        AND department_id = ? 
        AND course_id = ? 
        AND year_level = ? 
        AND section_no = ? 
        AND academic_year = ? 
        AND status = 'disabled'
        ORDER BY id DESC
        LIMIT 1");
    
    $stmt->bind_param("iiisss", 
        $adviser_id, 
        $department_id, 
        $course_id, 
        $year_level, 
        $section_no, 
        $academic_year
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    $section = $result->fetch_assoc();
    $stmt->close();
    
    if ($section) {
        $update_stmt = $connection->prepare("
            UPDATE sections 
            SET status = 'active' 
            WHERE id = ?");
        $update_stmt->bind_param("i", $section['id']);
        $update_stmt->execute();
        $update_stmt->close();
        return $section['id'];
    }
    
    return null;
}

function updateStudentSections($connection, $old_section_id, $new_section_id) {
    // Update all active students from old section to new section
    $update_students_stmt = $connection->prepare("
        UPDATE tbl_student 
        SET section_id = ?
        WHERE section_id = ? 
        AND status = 'active'");
        
    $update_students_stmt->bind_param("ii", 
        $new_section_id,
        $old_section_id
    );
    
    if (!$update_students_stmt->execute()) {
        throw new Exception("Failed to update student sections: " . $update_students_stmt->error);
    }
    $update_students_stmt->close();
}

function checkForDuplicateSection($connection, $adviser_id, $department_id, $course_id, $year_level, $section_no, $academic_year, $current_section_id) {
    $stmt = $connection->prepare("
        SELECT id 
        FROM sections 
        WHERE adviser_id = ? 
        AND department_id = ? 
        AND course_id = ? 
        AND year_level = ? 
        AND section_no = ? 
        AND academic_year = ? 
        AND id != ? 
        AND status = 'active'");
    
    $stmt->bind_param("iiisssi", 
        $adviser_id, 
        $department_id, 
        $course_id, 
        $year_level, 
        $section_no, 
        $academic_year,
        $current_section_id
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    $duplicate = $result->fetch_assoc();
    $stmt->close();
    
    return $duplicate ? true : false;
}

// Handle edit request for section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    error_log("POST Data: " . print_r($_POST, true));  // Add this line
    if (isset($_POST['section_id']) && isset($_POST['year_level'])) {
        $section_id = $_POST['section_id'];
        $new_year_level = trim($_POST['year_level']);
        
        try {
            $connection->begin_transaction();
            
            // Get current section details
            $verify_stmt = $connection->prepare("
                SELECT s.*, d.name AS department_name, c.name AS course_name 
                FROM sections s
                LEFT JOIN departments d ON d.id = s.department_id
                LEFT JOIN courses c ON c.id = s.course_id
                WHERE s.id = ? AND s.adviser_id = ? AND s.status = 'active'");
            $verify_stmt->bind_param("ii", $section_id, $adviser_id);
            $verify_stmt->execute();
            $section = $verify_stmt->get_result()->fetch_assoc();
            $verify_stmt->close();
            
            if (!$section) {
                throw new Exception("Section not found or you don't have permission to edit it.");
            }


            // Calculate new academic year
            $new_academic_year = calculateNewAcademicYear(
                $section['academic_year'],
                $section['year_level'],
                $new_year_level
            );

            // Add this to your edit handler section right after calculating new academic year
            error_log("Old Year Level: " . $section['year_level']);
            error_log("New Year Level: " . $new_year_level);
            error_log("Current Academic Year: " . $section['academic_year']);
            error_log("Calculated New Academic Year: " . $new_academic_year);
            
            // Try to reactivate existing section
            $reactivated_section_id = reactivateSection(
                $connection,
                $adviser_id,
                $section['department_id'],
                $section['course_id'],
                $new_year_level,
                $section['section_no'],
                $new_academic_year
            );

            if (!$reactivated_section_id) {
                // Check for active duplicate
                $hasDuplicate = checkForDuplicateSection(
                    $connection,
                    $adviser_id,
                    $section['department_id'],
                    $section['course_id'],
                    $new_year_level,
                    $section['section_no'],
                    $new_academic_year,
                    $section_id
                );
                
                if ($hasDuplicate) {
                    throw new Exception("A section with these details already exists.");
                }

                // Create new section
                $insert_stmt = $connection->prepare("
                    INSERT INTO sections 
                    (adviser_id, department_id, course_id, year_level, section_no, academic_year, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')");
                
                $insert_stmt->bind_param("iiisss", 
                    $adviser_id,
                    $section['department_id'],
                    $section['course_id'],
                    $new_year_level,
                    $section['section_no'],
                    $new_academic_year
                );
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to create new section: " . $insert_stmt->error);
                }
                
                $new_section_id = $insert_stmt->insert_id;
                $insert_stmt->close();
            } else {
                $new_section_id = $reactivated_section_id;
            }

            updateStudentSections($connection, $section_id, $new_section_id);
        
        // Disable old section
        $disable_section_stmt = $connection->prepare("
            UPDATE sections 
            SET status = 'disabled'
            WHERE id = ?");
        $disable_section_stmt->bind_param("i", $section_id);
        if (!$disable_section_stmt->execute()) {
            throw new Exception("Failed to disable old section");
        }
        $disable_section_stmt->close();

        $connection->commit();
        $_SESSION['success_message'] = "Section successfully updated";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        
    } catch (Exception $e) {
        $connection->rollback();
        error_log("Transaction rolled back. Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
}

// Handle delete request for section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    ob_clean();
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    
    if (!isset($_POST['section_id'])) {
        echo json_encode(['success' => false, 'message' => 'Section ID is required']);
        exit;
    }

    $section_id = intval($_POST['section_id']);
    
    try {
        // Start transaction
        $connection->begin_transaction();
        
        // Verify section exists and belongs to adviser
        $verify_stmt = $connection->prepare("SELECT id FROM sections WHERE id = ? AND adviser_id = ?");
        $verify_stmt->bind_param("ii", $section_id, $adviser_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            throw new Exception("Section not found or you don't have permission to modify it.");
        }
        $verify_stmt->close();

        // Get count of students in the section
        $check_students_stmt = $connection->prepare("
            SELECT COUNT(*) as student_count 
            FROM tbl_student 
            WHERE section_id = ? AND status = 'active'");
        $check_students_stmt->bind_param("i", $section_id);
        $check_students_stmt->execute();
        $student_count = $check_students_stmt->get_result()->fetch_assoc()['student_count'];
        $check_students_stmt->close();

        // Disable the section
        $update_section_stmt = $connection->prepare("
            UPDATE sections 
            SET status = 'disabled' 
            WHERE id = ? AND adviser_id = ?");
        $update_section_stmt->bind_param("ii", $section_id, $adviser_id);
        
        if (!$update_section_stmt->execute()) {
            throw new Exception("Failed to update section status: " . $update_section_stmt->error);
        }
        $update_section_stmt->close();

        // Disable all active students in the section and clear their emails
        $update_students_stmt = $connection->prepare("
    UPDATE tbl_student 
    SET status = 'disabled'
    WHERE section_id = ? 
    AND status = 'active'");
$update_students_stmt->bind_param("i", $section_id);
                
        if (!$update_students_stmt->execute()) {
            throw new Exception("Failed to update student status: " . $update_students_stmt->error);
        }
        $update_students_stmt->close();

        // Commit transaction
        $connection->commit();
        
        $message = "Section has been removed from active list";
        if ($student_count > 0) {
            $message .= " along with $student_count student" . ($student_count > 1 ? "s" : "");
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        $connection->rollback();
        error_log("Error updating section and student status: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => "Error: " . $e->getMessage()
        ]);
    }
    exit;
}

// Fetch active sections query
$sql = "SELECT s.*, d.name AS department_name, c.name AS course_name, 
        COUNT(CASE WHEN st.status = 'active' THEN st.student_id END) as student_count 
        FROM sections s
        LEFT JOIN tbl_student st ON s.id = st.section_id
        LEFT JOIN departments d ON d.id = s.department_id
        LEFT JOIN courses c ON c.id = s.course_id
        WHERE s.adviser_id = ? AND s.status = 'active'
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
    'Fifth Year'
];

// Success message display
if (isset($_SESSION['success_message'])) {
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '" . addslashes($_SESSION['success_message']) . "'
        });
    </script>";
    unset($_SESSION['success_message']);
}

// Error message display
if (isset($_SESSION['error_message'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '" . addslashes($_SESSION['error_message']) . "'
        });
    </script>";
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#009E60">
    <title>View Sections</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            --primary1-color: #0d693e;
            --secondary1-color: #004d4d;
            }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            padding: 20px;
            color: var(--text-color);
            background: linear-gradient(135deg, var(--primary1-color), var(--secondary1-color));
           
        }

         
        .main-content {
            margin-left: var(--sidebar-width);
            transition: var(--transition);
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
        }

        /* Page Title */
        h2 {
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin: 5px 0 30px;
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
            border-collapse: collapse;
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
            text-align: center;
            border: 1px solid var(--primary-color);
        }

        td {
            padding: 12px 15px;
            vertical-align: middle;
            border: 1px solid var(--border-color);
            font-size: 14px;
            text-align: center;
        }

        tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Back Button */
        .modern-back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--secondary-color);
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
            box-shadow: var(--shadow-sm);
        }

        .modern-back-button:hover {
            background-color: var(--secondary-dark);
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

        /* Button Styles */
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            margin: 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s ease;
        }

        .btn-group {
            display: flex;
            gap: 4px;
            align-items: center;
            justify-content: center;
        }

        .btn-group .btn {
            width: 35px;
            height: 35px;
            padding: 0;
            margin: 0 3px;
        }

        .btn-group .btn i {
            font-size: 14px;
        }

        /* Button Colors */
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

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        /* Form Controls */
        .form-control {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
        }

        select.form-control {
            height: 15%;
            padding: 5px;
            cursor: pointer;
            margin-bottom: 5px;
        }

        .search-container {
            width: 30%;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .input-group .btn {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 4px 0 0 4px;
            transition: all 0.3s ease;
        }

        .input-group .btn:hover {
            background-color: var(--primary-dark);
        }

        .input-group .btn i {
            font-size: 14px;
        }

        #searchInput {
            font-size: 14px;
            transition: all 0.3s ease;

        }


        /* Mobile responsiveness */
        @media screen and (max-width: 768px) {
            .search-container {
                max-width: 100%;
            }
        }

        /* Media Queries */
        @media screen and (max-width: 1200px) {
            .main-content {
                margin-left: 200px;
            }
            
            .container {
                width: 95%;
            }
        }

        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .search-container {
                max-width: 100%;
            }

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
            }

            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                text-align: right;
                border-bottom: 1px solid #eee;
            }

            td::before {
                content: attr(data-label);
                font-weight: 600;
                text-align: left;
                padding-right: 10px;
                text-transform: uppercase;
                font-size: 12px;
                min-width: 120px;
            }

            td[data-label="Actions"] {
                justify-content: center;
            }

            .btn-group .btn {
                width: 40px;
                height: 40px;
            }
            
            .btn-group .btn i {
                font-size: 16px;
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

        .swal2-title {
    border-bottom: none !important;  /* Remove the bottom border */
    padding-bottom: 0 !important;    /* Remove any bottom padding that might create space */
    margin-bottom: 0.5em !important; /* Maintain proper spacing */
}

    /* If the line still persists, you might need to override any inherited styles */
    .swal2-popup h2.swal2-title::after,
    .swal2-popup h2.swal2-title::before {
        display: none !important;
    }

    /* Add to your existing CSS */
.search-filter-container {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.search-container {
    width: 30%;
    border: 1px solid #ced4da;
    border-radius: 4px;
    margin-bottom: 0; /* Override any existing margin */
}

.reset-btn {
    height: 38px;
    display: flex;
    align-items: center;
}

/* For mobile responsiveness */
@media screen and (max-width: 768px) {
    .search-filter-container {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .search-container {
        width: 70%;
    }
}
</style>

<body>
<?php
if (isset($_SESSION['error_message'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '" . addslashes($_SESSION['error_message']) . "'
        });
    </script>";
    unset($_SESSION['error_message']);
}
?>
<div class="header">
    </div>
    <div class="main-content">
    
    <div class="container">
        <a href="adviser_homepage.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
        </a>
        <h2>Your Advisees</h2>


        <?php if (count($sections) > 0): ?>
        <div class="search-filter-container">
            <div class="search-container">
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>
            <a href="?" class="btn btn-secondary reset-btn">Reset Filters</a>
            <a style="margin-top: 0px; margin-bottom:0; padding: 8px; font-size: 13px; text-align:right" href="add_section.php" class="modern-back-button btn btn-primary">
                <span>+ Add Section</span>
            </a>
        </div>
    <div>
    
    </div>
    <div class="table-responsive table-striped">
      <table id="sectionsTable">
        <thead>
            <tr>
                <th>Department</th>
                <th>Course</th>
                <th>Year - Section</th>
                <th>Academic Year</th>
                <th>Number of <br>Students</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sections as $section): ?>
                <tr id="section-<?php echo $section['id']; ?>">
                    <td data-label="Department"><?php echo htmlspecialchars($section['department_name']); ?></td>
                    <td data-label="Course"><?php echo htmlspecialchars($section['course_name']); ?></td>
                    <td data-label="Year and Section">
                        <span class="year-level-text">
                            <?php echo formatYearLevel($section['year_level']) . ' - ' . htmlspecialchars($section['section_no']); ?>
                        </span>
                        <form method="post" class="edit-year-form" style="display: none;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                            <div class="year-level-select">
                                <select name="year_level" class="form-control">
                                    <?php foreach ($yearLevels as $yearLevel): ?>
                                        <option value="<?php echo $yearLevel; ?>" 
                                            <?php echo $section['year_level'] === $yearLevel ? 'selected' : ''; ?>>
                                            <?php echo formatYearLevel($yearLevel) . ' - ' . htmlspecialchars($section['section_no']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="btn-group">
                                <button type="submit" class="btn btn-success btn-sm mt-2">Save</button>
                                <button type="button" class="btn btn-secondary btn-sm mt-2 cancel-edit">Cancel</button>
                            </div>
                        </form>
                    </td>
                    <td data-label="Academic Year"><?php echo htmlspecialchars($section['academic_year']); ?></td>
                    <td data-label="Number of Students"><?php echo $section['student_count']; ?></td>
                                        <td data-label="Actions">
                        <div class="btn-group">
                            <?php if (strtolower($section['year_level']) !== 'irregular'): ?>
                            <form>
                            <button class="btn btn-info btn-sm edit-btn" title="Edit">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            </form>
                            <?php endif; ?>
                            <form>
                            <a href="section_details.php?section_id=<?php echo $section['id']; ?>" 
                               class="btn btn-primary btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            </form>
                            <!-- Replace the existing delete form with this -->
                            <form method="post" action="" class="delete-form">
                                <button type="submit" class="btn btn-danger btn-sm p-0 m-0" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                            </form>
                        </div>
                    </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="no-search-results" style="display:none; padding: 20px; justify-content: center;" class="no-sections">No sections match your search criteria.</div>
        <?php else: ?>
            <p class="no-sections">No added sections in your account.</p>
        <?php endif; ?>
    </div>

    <script>
// Add this to your existing script section
$(document).ready(function() {
    // Handle edit year form submission
    $('.edit-year-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const sectionId = form.find('input[name="section_id"]').val();
        const yearLevel = form.find('select[name="year_level"]').val();
        
        Swal.fire({
            title: 'Updating section...',
            text: 'Please wait',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit form via AJAX
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                // Reload page to show updated data
                window.location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Update request failed:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update section. Please try again.'
                });
            }
        });
    });
});

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

    $("#searchInput").keyup(function() {
    var searchText = $(this).val().toLowerCase();
    var visibleRows = 0;
    
    $("#sectionsTable tbody tr").each(function() {
        var row = $(this);
        var rowText = row.text().toLowerCase();
        var yearSectionCell = row.find('td[data-label="Year and Section"]');
        var yearSectionText = yearSectionCell.find('.year-level-text').text().toLowerCase();
        
        // Get all text content from other cells
        var otherCellsText = row.find('td').not('[data-label="Year and Section"]').text().toLowerCase();
        
        // Format the year-section text to match possible search patterns
        var formattedYearSection = yearSectionText.replace(/\s+/g, ''); // Remove spaces
        var searchPattern = searchText.replace(/\s+/g, ''); // Remove spaces from search text
        
        // Check if search text matches any cell content or the formatted year-section
        if (otherCellsText.includes(searchText) || 
            yearSectionText.includes(searchText) || 
            formattedYearSection.includes(searchPattern)) {
            row.show();
        } else {
            row.hide();
        }

        if (rowText.includes(searchText)) {
            row.show();
            visibleRows++;
        } else {
            row.hide();
        }
    });

    if (visibleRows === 0 && searchText !== '') {
        $("#no-search-results").show();
    } else {
        $("#no-search-results").hide();
    }
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

// Replace the existing delete form handler with this updated version
$(document).ready(function() {
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const sectionId = form.find('input[name="section_id"]').val();
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the section and all associated students. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    beforeSend: function() {
                        // Show loading state
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message,
                                showConfirmButton: true
                            }).then((result) => {
                                // Remove the table row
                                form.closest('tr').fadeOut(400, function() {
                                    $(this).remove();
                                    // Check if table is empty
                                    if ($('#sectionsTable tbody tr').length === 0) {
                                        $('.table-responsive').html('<p class="no-sections">No added sections in your account.</p>');
                                    }
                                });
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to delete section'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete request failed:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to process delete request. Please try again.'
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
