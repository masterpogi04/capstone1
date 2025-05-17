<?php
session_start();
include '../db.php';

// Ensure the user is logged in as a instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get search, sort, and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'date_reported';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';

// Validate sort column
$allowed_columns = ['date_reported', 'place', 'description', 'status'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'date_reported';
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page; 

// Base query - simplified like in the second file
$base_query = "
    FROM incident_reports ir
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Initialize parameters and types
$params = array($user_id, $user_type);
$types = "is"; 

// Add search condition if present
if (!empty($search)) {
    $search_condition = " AND (ir.description LIKE ? OR ir.place LIKE ? OR EXISTS (
        SELECT 1 FROM student_violations sv WHERE sv.incident_report_id = ir.id AND sv.student_name LIKE ?
    ))";
    $base_query .= $search_condition;
    $search_param = "%$search%";
    $params = array_merge($params, array($search_param, $search_param, $search_param));
    $types .= "sss";
}

// Department filtering - direct approach as in the second file
if (!empty($department_filter)) {
    // First get all course names in this department
    $dept_courses_query = "SELECT name FROM courses WHERE department_id = ?";
    $dept_courses_stmt = $connection->prepare($dept_courses_query);
    $dept_courses_stmt->bind_param("i", $department_filter);
    $dept_courses_stmt->execute();
    $dept_courses_result = $dept_courses_stmt->get_result();
    
    // Build an array of course names
    $course_names = [];
    while ($row = $dept_courses_result->fetch_assoc()) {
        $course_names[] = "'" . $connection->real_escape_string($row['name']) . "'";
    }
    
    // Use the course names to filter incident reports
    if (!empty($course_names)) {
        $course_list = implode(',', $course_names);
        $base_query .= " AND ir.id IN (
            SELECT DISTINCT incident_report_id 
            FROM student_violations 
            WHERE student_course IN ($course_list)
        )";
    }
}

// Course filtering - direct approach as in the second file
if (!empty($course_filter)) {
    $course_query = "SELECT name FROM courses WHERE id = ?";
    $course_stmt = $connection->prepare($course_query);
    $course_stmt->bind_param("i", $course_filter);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $course_row = $course_result->fetch_assoc();
    
    if ($course_row) {
        $course_name = $course_row['name'];
        $base_query .= " AND ir.id IN (
            SELECT DISTINCT incident_report_id 
            FROM student_violations 
            WHERE student_course = ?
        )";
        $params[] = $course_name;
        $types .= "s";
    }
}

// Count total records
$count_query = "SELECT COUNT(DISTINCT ir.id) as total " . $base_query;
$count_stmt = $connection->prepare($count_query);
if ($count_stmt === false) {
    die('Prepare failed for count query: ' . $connection->error);
}

if (!$count_stmt->bind_param($types, ...$params)) {
    die('Binding parameters failed for count query: ' . $count_stmt->error);
}

if (!$count_stmt->execute()) {
    die('Execute failed for count query: ' . $count_stmt->error);
}

$count_result = $count_stmt->get_result();
if ($count_result === false) {
    die('Getting result failed for count query: ' . $count_stmt->error);
}

$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Helper function to convert names to proper case
function convertToProperCase($name) {
    // Split the name into parts
    $parts = explode(' ', $name);
    $properName = [];
    
    foreach ($parts as $part) {
        // Check for middle initial with period (like "C.")
        if (strlen($part) === 2 && substr($part, -1) === '.') {
            $properName[] = strtoupper($part);
        } else {
            $properName[] = ucfirst(strtolower($part));
        }
    }
    
    return implode(' ', $properName);
}

// Main query with simplified subqueries for student names only
$query = "
    SELECT DISTINCT ir.*, 
    (
        SELECT GROUP_CONCAT(student_name SEPARATOR '||')
        FROM student_violations
        WHERE incident_report_id = ir.id
    ) as student_names,
    (
        SELECT GROUP_CONCAT(witness_name SEPARATOR '||')
        FROM incident_witnesses
        WHERE incident_report_id = ir.id
    ) as witnesses
    " . $base_query . "
    GROUP BY ir.id
    ORDER BY " . $sort_column . " " . $sort_order . "
    LIMIT ? OFFSET ?
";

// Add pagination parameters for main query
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

// Execute main query with error handling
$stmt = $connection->prepare($query);
if ($stmt === false) {
    die('Prepare failed for main query: ' . $connection->error);
}

if (!$stmt->bind_param($types, ...$params)) {
    die('Binding parameters failed for main query: ' . $stmt->error);
}

if (!$stmt->execute()) {
    die('Execute failed for main query: ' . $stmt->error);
}

$result = $stmt->get_result();
if ($result === false) {
    die('Getting result failed for main query: ' . $stmt->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submitted Incident Reports - Instructor</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<style>
    :root {
        --primary-color: #0d693e;
        --secondary-color: #004d4d;
        --text-color: #2c3e50;
    }
    
    body {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        min-height: 100vh;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: var(--text-color);
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .container {
        background-color: rgba(255, 255, 255, 0.98);
        border-radius: 15px;
        padding: 20px;
        margin: 20px auto;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 1200px;
    }
    
    @media (max-width: 768px) {
        .container {
            padding: 15px;
            margin: 15px;
            border-radius: 10px;
        }
        
        body {
            align-items: flex-start;
            padding-top: 10px;
        }
    }

    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    /* Search and Filter Section */
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }

    .search-box input {
        padding-left: 35px;
        border-radius: 20px;
        width: 100%;
        padding: 10px;
        border: 1px solid #ced4da;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .filters-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .filters-section .row {
        margin-bottom: 15px;
    }

    /* Action Buttons */
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

    .btn-delete {
        background-color: #e74c3c;
    }

    .btn-delete:hover {
        background-color: #c0392b;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    /* No special styling for status - removed badge styles */

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

    /* Pagination */
    .pagination {
        margin-top: 20px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .page-link {
        color: #009E60;
        border: 1px solid #dee2e6;
        margin: 0 2px;
        min-width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .page-item.active .page-link {
        background-color: #009E60;
        border-color: #009E60;
    }

    .page-link:hover {
        color: #006E42;
        background-color: #e9ecef;
    }

    @media (max-width: 576px) {
        .pagination {
            gap: 2px;
        }
        
        .page-link {
            padding: 0.25rem 0.5rem;
            min-width: 30px;
            height: 30px;
            font-size: 0.875rem;
        }
    }

    h2 {
        font-weight: 700;
        font-size: 2rem;
        text-align: center;
        margin: 15px 0 30px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    @media (max-width: 768px) {
        h2 {
            font-size: 1.5rem;
            margin: 10px 0 20px;
        }
    }

    /* Search and Filter Form Styles */
    .mb-4 {
        margin-bottom: 1.5rem !important;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
    }

    @media (max-width: 768px) {
        .filter-control, #resetFilters {
            margin-bottom: 10px;
        }
        
        .d-flex.justify-content-between {
            flex-direction: column;
            align-items: center !important;
        }
        
        .d-flex.justify-content-between .col-md-4 {
            width: 100%;
            margin-top: 10px;
        }
        
        .search-container {
            width: 100%;
        }
    }

    /* Responsive table styles */
    .table-responsive {
        margin: 20px 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        padding: 0.5px;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
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

    /* Responsive table for mobile */
    @media (max-width: 992px) {
        .table thead {
            display: none;
        }
        
        .table, .table tbody, .table tr, .table td {
            display: block;
            width: 100%;
        }
        
        .table tr {
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .table td {
            position: relative;
            padding-left: 50%;
            text-align: right;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table td:before {
            content: attr(data-label);
            position: absolute;
            left: 12px;
            width: 45%;
            padding-right: 10px;
            font-weight: 600;
            text-align: left;
            color: #555;
        }
        
        .table td:last-child {
            border-bottom: none;
        }
        
        .actions-cell {
            display: flex;
            justify-content: flex-end;
        }
    }

    /* Cell styles for desktop */
    td {
        padding: 12px 15px;
        vertical-align: middle;
        border: 0.1px solid #e0e0e0;
        font-size: 14px;
        text-align: center;
        background-color: transparent;
    }

    /* Table column widths for desktop */
    @media (min-width: 993px) {
        .table th:nth-child(1), 
        .table td:nth-child(1) {
            width: 15%;
            padding: 20px;
        }

        .table th:nth-child(2), 
        .table td:nth-child(2) {
            width: 12%;
            padding: 20px;
        }

        .table td:nth-child(3) {
            width: 30%;
            text-align: left;
            white-space: normal;
            min-width: 250px;
        }

        .table th:nth-child(3) {
            width: 10%;
            padding: 20px;
        }

        .table td:nth-child(4) {
            width: 15%;
        }

        .table th:nth-child(4) {
            width: 10%;
            padding: 20px;
        }

        .table th:nth-child(5), 
        .table td:nth-child(5) {
            width: 10%;
            padding: 20px;
        }

        .table th:nth-child(6), 
        .table td:nth-child(6) {
            width: 10%;
            padding: 20px;
        }

        .table th:nth-child(7), 
        .table td:nth-child(7) {
            width: 8%;
            padding: 20px;
        }
    }

    /* Actions cell specific styling */
    .actions-cell {
        display: flex;
        justify-content: center;
        gap: 8px;
    }
    
    /* Filter responsiveness */
    @media (max-width: 768px) {
        .filter-row > div {
            margin-bottom: 10px;
            width: 100%;
        }
        
        #resetFilters {
            width: 100%;
        }
    }
    .no-data-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 50px 20px;
        background-color: #f8f9fa;
        border-radius: 10px;
        margin: 30px 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
    }

    .no-data-icon {
        font-size: 70px;
        color: #a0a0a0;
        margin-bottom: 20px;
    }

    .no-data-text {
        font-size: 18px;
        color: #5c5c5c;
        font-weight: 500;
        text-align: center;
    }

    .no-data-subtext {
        font-size: 14px;
        color: #7d7d7d;
        text-align: center;
        margin-top: 5px;
    }
</style>
</head>
<body>
    <div class="container">
        <a href="instructor_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2>Submitted Incident Report</h2>
            <div class="col-md-4">
                <div class="search-container">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                </div>
            </div>
        </div>

        <!-- Search and Filter Form -->
        <form class="mb-4" method="GET" action="">
            <div class="row filter-row">
                <div class="col-md-3 col-sm-6">
                    <select id="department" name="department" class="form-control filter-control">
                        <option value="">All Departments</option>
                        <?php 
                        $dept_query = "SELECT DISTINCT id, name FROM departments ORDER BY name";
                        $dept_result = $connection->query($dept_query);
                        while ($dept = $dept_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($dept['id']); ?>" 
                                    <?php echo ($department_filter == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <select id="course" name="course" class="form-control filter-control">
                        <option value="">All Courses</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select id="sort_order" name="sort_order" class="form-control filter-control">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                </div>
                <div class="col-md-1 col-sm-6">
                    <button type="button" class="btn btn-secondary" id="resetFilters">Reset</button>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Incident Place, <br>Date & Time</th>
                        <th>Description</th>
                        <th>Student/s Involved</th>
                        <th>Witness/es</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Date Reported"><?php echo date('M j, Y h:i A', strtotime($row['date_reported'])); ?></td>
                            <td data-label="Incident Place, Date & Time">
                            <?php 
                                $place_string = $row['place'];
                                $parts = explode(' - ', $place_string);
                                if (count($parts) > 1) {
                                    $location = htmlspecialchars($parts[0]);
                                    $datetime_parts = explode(' at ', $parts[1]);
                                    
                                    echo $location . ',<br>';
                                    if (count($datetime_parts) > 1) {
                                        echo htmlspecialchars($datetime_parts[0]) . '<br>at ' . htmlspecialchars($datetime_parts[1]);
                                    } else {
                                        echo htmlspecialchars($parts[1]);
                                    }
                                } else {
                                    echo htmlspecialchars($place_string);
                                }
                            ?>
                            </td>
                            <td data-label="Description"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                            <td data-label="Students Involved">
                                <?php 
                                if (!empty($row['student_names'])) {
                                    $students = explode('||', $row['student_names']);
                                    foreach ($students as $student) {
                                        // Convert student name to proper case
                                        echo htmlspecialchars(convertToProperCase($student)) . "<br><br>";
                                    }
                                } else {
                                    echo "No students involved";
                                }
                                ?>
                            </td>
                            <td data-label="Witnesses">
                                <?php 
                                if (!empty($row['witnesses'])) {
                                    $witnesses = explode('||', $row['witnesses']);
                                    foreach ($witnesses as $witness) {
                                        // Convert witness name to proper case
                                        echo htmlspecialchars(convertToProperCase($witness)) . "<br><br>";
                                    }
                                } else {
                                    echo "No witnesses";
                                }
                                ?>
                            </td>
                            <td data-label="Status">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </td>
                            <td data-label="Actions">
                                <a href="view_incident_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        </tbody>
                        </table>
                        <!-- No data container when no records are found -->
                        <div class="no-data-container">
                            <i class="fas fa-folder-open no-data-icon"></i>
                            <div class="no-data-text">No incident reports found</div>
                            <?php if (!empty($search) || !empty($department_filter) || !empty($course_filter)): ?>
                                <div class="no-data-subtext">Try adjusting your search criteria or filters</div>
                            <?php else: ?>
                                <div class="no-data-subtext">No reports have been submitted yet</div>
                            <?php endif; ?>
                        </div>
                        <?php return; // Exit early to avoid rendering pagination when no data ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Empty results container for AJAX searches -->
            <div id="noDataContainer" class="no-data-container" style="display: none;">
                <i class="fas fa-search no-data-icon"></i>
                <div class="no-data-text">No matching incident reports found</div>
                <div class="no-data-subtext">Try adjusting your search criteria or filters</div>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?php echo "&search=$search&sort_order=$sort_order&department=$department_filter&course=$course_filter"; ?>" aria-label="First">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; echo "&search=$search&sort_order=$sort_order&department=$department_filter&course=$course_filter"; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; echo "&search=$search&sort_order=$sort_order&department=$department_filter&course=$course_filter"; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; echo "&search=$search&sort_order=$sort_order&department=$department_filter&course=$course_filter"; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; echo "&search=$search&sort_order=$sort_order&department=$department_filter&course=$course_filter"; ?>" aria-label="Last">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="text-center mt-3">
            <p>Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries</p>
        </div>
    </div>

<script>
$(document).ready(function() {
    // Function to load courses and update content
    function loadCourses(departmentId, selectedCourse = '') {
        if (!departmentId) {
            let courseSelect = $('#course');
            courseSelect.empty();
            courseSelect.append('<option value="">All Courses</option>');
            filterResults();
            return;
        }

        $.ajax({
            url: 'get_courses.php',
            type: 'POST',
            data: { department_id: departmentId },
            success: function(response) {
                let courses = JSON.parse(response);
                let courseSelect = $('#course');
                courseSelect.empty();
                courseSelect.append('<option value="">All Courses</option>');
                
                courses.forEach(function(course) {
                    let selected = (course.id == selectedCourse) ? 'selected' : '';
                    courseSelect.append(`<option value="${course.id}" ${selected}>${course.name}</option>`);
                });
                
                if (selectedCourse) {
                    courseSelect.val(selectedCourse);
                }
                filterResults();
            },
            error: function(xhr, status, error) {
                console.error("Error loading courses:", error);
                $('#course').empty().append('<option value="">All Courses</option>');
                filterResults();
            }
        });
    }

    // Function to filter results
    function filterResults() {
        let department = $('#department').val();
        let course = $('#course').val();
        let sortOrder = $('#sort_order').val();
        let search = $('#searchInput').val();
        let page = new URLSearchParams(window.location.search).get('page') || 1;

        $.ajax({
            url: window.location.pathname,
            type: 'GET',
            data: {
                department: department,
                course: course,
                sort_order: sortOrder,
                search: search,
                page: page,
                ajax: true
            },
            success: function(response) {
                let parser = new DOMParser();
                let doc = parser.parseFromString(response, 'text/html');
                
                // Check if no data is found
                if ($(doc).find('tbody tr').length === 0 || $(doc).find('.no-data-container').length > 0) {
                    $('.table').hide();
                    $('.pagination').hide();
                    $('.mt-3').hide();
                    
                    // Show no data container with appropriate message
                    $('#noDataContainer').show();
                    
                    // Update the subtext based on whether filters/search are active
                    if (search || department || course) {
                        $('#noDataContainer .no-data-subtext').text('Try adjusting your search criteria or filters');
                    } else {
                        $('#noDataContainer .no-data-subtext').text('No reports have been submitted yet');
                    }
                } else {
                    $('.table').show();
                    $('.pagination').show();
                    $('.mt-3').show();
                    $('#noDataContainer').hide();
                    
                    $('tbody').html($(doc).find('tbody').html());
                    $('.pagination').html($(doc).find('.pagination').html());
                    $('.mt-3').html($(doc).find('.mt-3').html());
                }

                // Update URL without page reload
                let url = new URL(window.location);
                url.searchParams.set('department', department || '');
                url.searchParams.set('course', course || '');
                url.searchParams.set('sort_order', sortOrder);
                if (search) {
                    url.searchParams.set('search', search);
                } else {
                    url.searchParams.delete('search');
                }
                window.history.pushState({}, '', url);
            }
        });
    }

    // Handle search input with immediate filtering
    $("#searchInput").keyup(function() {
        var searchText = $(this).val().toLowerCase();
        
        // Check if there are any table rows that match the search
        let visibleRows = 0;
        
        $(".table tbody tr").each(function() {
            var row = $(this);
            var rowContent = '';
            
            row.find('td').each(function() {
                rowContent += $(this).text().toLowerCase() + ' ';
            });
            
            var formattedContent = rowContent.replace(/\s+/g, ' ').trim();
            var searchPattern = searchText.replace(/\s+/g, ' ').trim();
            
            if (formattedContent.includes(searchPattern)) {
                row.show();
                visibleRows++;
            } else {
                row.hide();
            }
        });
        
        // Show or hide no data message
        if (visibleRows === 0 && searchText !== '') {
            $('.table').hide();
            $('.pagination').hide();
            $('.mt-3').hide();
            
            $('#noDataContainer').show();
            $('#noDataContainer .no-data-text').text('No matching incident reports found');
            $('#noDataContainer .no-data-subtext').text('Try adjusting your search criteria or filters');
        } else {
            $('.table').show();
            $('.pagination').show();
            $('.mt-3').show();
            $('#noDataContainer').hide();
        }
    });

    // Handle department change
    $('#department').change(function() {
        let selectedDepartment = $(this).val();
        loadCourses(selectedDepartment);
    });

    // Handle course and sort order changes
    $('#course, #sort_order').change(function() {
        filterResults();
    });

    // Reset button functionality
    $('#resetFilters').click(function(e) {
        e.preventDefault();
        window.location.href = window.location.pathname;
    });

    // Handle pagination clicks
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        let href = $(this).attr('href');
        let url = new URL(href, window.location.origin);
        let page = url.searchParams.get('page') || 1;
        
        let currentUrl = new URL(window.location);
        currentUrl.searchParams.set('page', page);
        filterResults();
        
        window.history.pushState({}, '', currentUrl);
    });

    // Set initial search value from URL
    let urlParams = new URLSearchParams(window.location.search);
    let searchValue = urlParams.get('search');
    if (searchValue) {
        $('#searchInput').val(searchValue);
    }

    // Initial load of courses if department is selected
    let selectedDepartment = $('#department').val();
    let selectedCourse = '<?php echo $course_filter; ?>';
    if (selectedDepartment) {
        loadCourses(selectedDepartment, selectedCourse);
    }
});
</script>
</body>
</html>