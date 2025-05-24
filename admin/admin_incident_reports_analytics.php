<?php
session_start();
include '../db.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch admin details
$admin_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, last_name FROM tbl_admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $admin_name = $admin['first_name'] . ' ' . $admin['last_name'];
} else {
    $admin_name = "admin";
}
$stmt->close();

// Get earliest incident report year from database
$earliest_year_query = "SELECT MIN(YEAR(date_reported)) as earliest_year FROM incident_reports WHERE date_reported IS NOT NULL";
$earliest_year_result = $connection->query($earliest_year_query);
$earliest_year = null;

if ($earliest_year_result && $row = $earliest_year_result->fetch_assoc()) {
    $earliest_year = $row['earliest_year'];
}

// Calculate current academic year
$currentMonth = (int)date('n');
$currentYear = (int)date('Y');
$currentAcademicYear = ($currentMonth < 6) ? $currentYear - 1 : $currentYear;

// If no incidents yet, use current academic year as earliest
if (!$earliest_year) {
    $earliest_year = $currentAcademicYear;
} else {
    // Adjust earliest year to academic year if needed
    $earliest_date_query = "SELECT MIN(date_reported) as earliest_date FROM incident_reports WHERE date_reported IS NOT NULL";
    $earliest_date_result = $connection->query($earliest_date_query);
    if ($earliest_date_result && $row = $earliest_date_result->fetch_assoc()) {
        $earliest_date = new DateTime($row['earliest_date']);
        $earliest_month = (int)$earliest_date->format('n');
        $earliest_year = ($earliest_month < 6) ? $earliest_year - 1 : $earliest_year;
    }
}

// Get all departments
$dept_query = "SELECT * FROM departments WHERE status = 'active' ORDER BY name";
$departments = $connection->query($dept_query);

// Get selected filters
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';
$selected_course = isset($_GET['course']) ? $_GET['course'] : '';
$current_status = isset($_GET['status']) ? $_GET['status'] : 'Pending';
$selected_academic_year = isset($_GET['academic_year']) ? (int)$_GET['academic_year'] : $currentAcademicYear;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Get courses for the selected department
$courses = [];
if ($selected_department) {
    $course_query = "SELECT * FROM courses WHERE department_id = ? AND status = 'active' ORDER BY name";
    $stmt = $connection->prepare($course_query);
    $stmt->bind_param("i", $selected_department);
    $stmt->execute();
    $courses = $stmt->get_result();
}

// Function to get departments with incident report counts
function getDepartmentsWithReportCounts($connection, $status = 'Pending', $academic_year = null, $start_date = null, $end_date = null) {
    $whereConditions = ["ir.is_archived = 0"];
    $params = [];
    $types = "";
    
    // Add status filter
    if ($status !== 'all') {
        $whereConditions[] = "ir.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Add academic year filter
    if ($academic_year) {
        $start_academic_year = $academic_year . '-06-01';
        $end_academic_year = ($academic_year + 1) . '-05-31';
        $whereConditions[] = "ir.date_reported BETWEEN ? AND ?";
        $params[] = $start_academic_year;
        $params[] = $end_academic_year;
        $types .= "ss";
    }
    
    // Add date range filters
    if ($start_date) {
        $whereConditions[] = "DATE(ir.date_reported) >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $whereConditions[] = "DATE(ir.date_reported) <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    $query = "SELECT d.id, d.name, COUNT(DISTINCT ir.id) as report_count
              FROM departments d
              LEFT JOIN courses c ON d.id = c.department_id
              LEFT JOIN sections s ON c.id = s.course_id
              LEFT JOIN student_violations sv ON sv.section_id = s.id
              LEFT JOIN incident_reports ir ON sv.incident_report_id = ir.id
              WHERE $whereClause
              GROUP BY d.id, d.name
              ORDER BY COUNT(DISTINCT ir.id) DESC";
    
    $stmt = $connection->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $departments = [];
    
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    
    return $departments;
}

// Function to get courses with incident report counts for a specific department
function getCoursesWithReportCounts($connection, $department_id, $status = 'Pending', $academic_year = null, $start_date = null, $end_date = null) {
    $whereConditions = ["c.department_id = ?", "ir.is_archived = 0"];
    $params = [$department_id];
    $types = "i";
    
    // Add status filter
    if ($status !== 'all') {
        $whereConditions[] = "ir.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    // Add academic year filter
    if ($academic_year) {
        $start_academic_year = $academic_year . '-06-01';
        $end_academic_year = ($academic_year + 1) . '-05-31';
        $whereConditions[] = "ir.date_reported BETWEEN ? AND ?";
        $params[] = $start_academic_year;
        $params[] = $end_academic_year;
        $types .= "ss";
    }
    
    // Add date range filters
    if ($start_date) {
        $whereConditions[] = "DATE(ir.date_reported) >= ?";
        $params[] = $start_date;
        $types .= "s";
    }
    
    if ($end_date) {
        $whereConditions[] = "DATE(ir.date_reported) <= ?";
        $params[] = $end_date;
        $types .= "s";
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    $query = "SELECT c.id, c.name, COUNT(DISTINCT ir.id) as report_count
              FROM courses c
              LEFT JOIN sections s ON c.id = s.course_id
              LEFT JOIN student_violations sv ON sv.section_id = s.id
              LEFT JOIN incident_reports ir ON sv.incident_report_id = ir.id
              WHERE $whereClause
              GROUP BY c.id, c.name
              ORDER BY COUNT(DISTINCT ir.id) DESC";
    
    $stmt = $connection->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = [];
    
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    return $courses;
}

// Get today's date for max date in date pickers
$today = date('Y-m-d');

// Fetch data based on filters
$department_data = getDepartmentsWithReportCounts($connection, $current_status, $selected_academic_year, $start_date, $end_date);
$course_data = [];

if ($selected_department) {
    $course_data = getCoursesWithReportCounts($connection, $selected_department, $current_status, $selected_academic_year, $start_date, $end_date);
    
    // Get selected department name
    foreach ($department_data as $dept) {
        if ($dept['id'] == $selected_department) {
            $selected_department_name = $dept['name'];
            $selected_department_count = $dept['report_count'];
            break;
        }
    }
}

// Calculate total reports
$total_reports = 0;
foreach ($department_data as $dept) {
    $total_reports += $dept['report_count'];
}

// Convert data for JavaScript charts
$chart_departments = [];
$chart_report_counts = [];
$dept_colors = [];

foreach ($department_data as $index => $dept) {
    // Get department acronym
    $parts = explode('(', $dept['name']);
    $acronym = '';
    if (count($parts) > 1) {
        $acronym = trim($parts[1], ')');
    } else {
        $words = explode(' ', $dept['name']);
        foreach ($words as $word) {
            if (ctype_upper(substr($word, 0, 1))) {
                $acronym .= substr($word, 0, 1);
            }
        }
    }
    
    if (!empty($acronym)) {
        $chart_departments[] = $acronym;
    } else {
        $chart_departments[] = substr($dept['name'], 0, 10) . '...'; // Fallback
    }
    
    $chart_report_counts[] = $dept['report_count'];
    
    // Generate color for department
    $hue = (140 + ($index * 30)) % 360; // Start with green (140) and spread
    $dept_colors[] = "hsla($hue, 70%, 45%, 0.9)";
}

// Convert course data for JavaScript charts 
$chart_courses = [];
$chart_course_counts = [];
$course_colors = [];

foreach ($course_data as $index => $course) {
    // Truncate course name if too long
    $chart_courses[] = strlen($course['name']) > 25 ? substr($course['name'], 0, 22) . '...' : $course['name'];
    $chart_course_counts[] = $course['report_count'];
    
    // Generate color for course (using a blue palette)
    $hue = (200 + ($index * 20)) % 360; // Start with blue (200) and spread
    $course_colors[] = "hsla($hue, 70%, 45%, 0.9)";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports Analytics - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    :root {
        --primary-color: #008F57;
        --primary-dark: #007346;
        --primary-light: #4CAF50;
        --secondary-color: #2c3e50;
        --accent-color: #3498db;
        --light-bg: #f8f9fa;
        --light-border: #e3e3e3;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        --card-radius: 12px;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background-color: #f5f5f5;
        min-height: 100vh;
    }

    .dashboard-container {
        padding: 20px;
        margin-bottom: 60px;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .dashboard-header h2 {
        color: var(--secondary-color);
        margin: 0;
        font-weight: 600;
    }

    /* Back button styling */
    .modern-back-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: var(--primary-color);
        color: white;
        padding: 8px 16px;
        border-radius: 25px;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.25s ease;
        border: none;
        box-shadow: 0 2px 5px rgba(0, 143, 87, 0.2);
    }

    .modern-back-button:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 143, 87, 0.3);
        color: white;
        text-decoration: none;
    }

    /* Tabs styling */
    .dashboard-tabs {
        margin-bottom: 25px;
        border-bottom: 2px solid var(--light-border);
    }

    .dashboard-tabs .nav-link {
        color: var(--secondary-color);
        border: none;
        padding: 10px 20px;
        font-weight: 500;
        border-radius: 0;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .dashboard-tabs .nav-link i {
        font-size: 1.1rem;
    }

    .dashboard-tabs .nav-link:hover {
        background-color: rgba(0, 143, 87, 0.1);
        border-color: transparent;
    }

    .dashboard-tabs .nav-link.active {
        color: var(--primary-color);
        background-color: transparent;
        border-bottom: 3px solid var(--primary-color);
        margin-bottom: -2px;
    }

    /* Summary cards styling */
    .summary-cards {
        margin-bottom: 30px;
    }

    .analytics-card {
        background-color: white;
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        border: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
        margin-bottom: 25px;
    }

    .analytics-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .analytics-card .card-header {
        background-color: var(--primary-color);
        color: white;
        padding: 15px 20px;
        border: none;
    }

    .analytics-card .card-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .analytics-card .card-body {
        padding: 20px;
    }

    /* Summary info styling */
    .summary-info {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .info-item .label {
        color: #6c757d;
        font-size: 0.9rem;
    }

    .info-item .value {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--secondary-color);
    }

    .status-badge {
        font-size: 0.9rem !important;
        padding: 5px 10px;
        border-radius: 50px;
        display: inline-block;
        text-align: center;
    }

    .status-badge.pending {
        background-color: #ffc107;
        color: #212529;
    }

    .status-badge.for-meeting {
        background-color: #17a2b8;
        color: white;
    }

    .status-badge.settled {
        background-color: #28a745;
        color: white;
    }

    .status-badge.all {
        background-color: #6c757d;
        color: white;
    }

    /* Filter form styling */
    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .filter-form .form-control {
        border-radius: 8px;
        border: 1px solid var(--light-border);
        padding: 10px 12px;
        transition: all 0.2s ease;
    }

    .filter-form .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(0, 143, 87, 0.25);
    }

    .filter-form .input-group-text {
        background-color: var(--light-bg);
        border-color: var(--light-border);
        color: var(--secondary-color);
    }

    .filter-form .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }

    /* Chart container styling */
    .chart-container {
        height: 300px;
        position: relative;
        margin-bottom: 25px;
    }

    /* Table styling */
    .analytics-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .analytics-table th {
        background-color: var(--light-bg);
        padding: 12px 15px;
        font-weight: 600;
        color: var(--secondary-color);
        border-bottom: 2px solid var(--light-border);
        text-transform: uppercase;
        font-size: 0.85rem;
    }

    .analytics-table td {
        padding: 12px 15px;
        border-bottom: 1px solid var(--light-border);
        vertical-align: middle;
    }

    .analytics-table tr:hover {
        background-color: rgba(0, 143, 87, 0.05);
    }

    .count-badge {
        display: inline-block;
        background-color: var(--primary-light);
        color: white;
        padding: 5px 10px;
        border-radius: 50px;
        font-weight: 600;
        min-width: 40px;
        text-align: center;
    }

    /* View courses button */
    .view-courses {
        border-color: var(--primary-color);
        color: var(--primary-color);
        transition: all 0.2s ease;
    }

    .view-courses:hover:not(:disabled) {
        background-color: var(--primary-color);
        color: white;
    }

    .view-courses:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }

    /* Back link in course view */
    .back-link {
        color: var(--primary-color);
        margin-right: 15px;
        font-size: 1.2rem;
        transition: transform 0.2s ease;
    }

    .back-link:hover {
        transform: translateX(-3px);
        color: var(--primary-dark);
        text-decoration: none;
    }

    /* No data message */
    .no-data-message {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 50px 20px;
        text-align: center;
        color: #6c757d;
    }

    .no-data-message i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #adb5bd;
    }

    .no-data-message p {
        font-size: 1.1rem;
        margin: 0;
    }

    /* Export buttons */
    .export-actions {
        margin-top: 30px;
    }

    .export-btn {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        font-weight: 500;
        padding: 10px 20px;
        transition: all 0.2s ease;
    }

    .export-btn:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .dashboard-tabs .nav-link {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .chart-container {
            height: 250px;
        }

        .info-item .value {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 576px) {
        .summary-info {
            grid-template-columns: 1fr;
        }

        .analytics-table th,
        .analytics-table td {
            padding: 10px;
        }
    }
</style>
</head>


<body>
<div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <a href="admin_dashboard.php" class="modern-back-button">
                    <i class="fas fa-arrow-left"></i> Back to User Analytics
                </a>
                <h2>Incident Reports Analytics</h2>
            </div>

            <!-- Status Tabs -->
            <ul class="nav nav-tabs dashboard-tabs" id="statusTabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_status == 'Pending' ? 'active' : ''; ?>" 
                       href="#" data-status="Pending">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_status == 'For Meeting' ? 'active' : ''; ?>" 
                       href="#" data-status="For Meeting">
                        <i class="fas fa-users"></i> For Meeting
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_status == 'Settled' ? 'active' : ''; ?>" 
                       href="#" data-status="Settled">
                        <i class="fas fa-check-circle"></i> Settled
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_status == 'all' ? 'active' : ''; ?>" 
                       href="#" data-status="all">
                        <i class="fas fa-list"></i> All
                    </a>
                </li>
            </ul>

            <!-- Summary Cards -->
            <div class="row summary-cards">
                <div class="col-md-6 mb-4">
                    <div class="analytics-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-pie"></i>
                                Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="summary-info">
                                <div class="info-item">
                                    <span class="label">Total Reports:</span>
                                    <span class="value"><?php echo $total_reports; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Status:</span>
                                    <span class="value status-badge <?php echo strtolower($current_status); ?>">
                                        <?php echo $current_status == 'all' ? 'All' : $current_status; ?>
                                    </span>
                                </div>
                                <?php if($selected_academic_year): ?>
                                <div class="info-item">
                                    <span class="label">Academic Year:</span>
                                    <span class="value"><?php echo $selected_academic_year.'-'.($selected_academic_year+1); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if($start_date && $end_date): ?>
                                <div class="info-item">
                                    <span class="label">Date Range:</span>
                                    <span class="value"><?php echo date('M d, Y', strtotime($start_date)).' to '.date('M d, Y', strtotime($end_date)); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="analytics-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-filter"></i>
                                Filter Reports
                            </h5>
                        </div>
                        <div class="card-body">
                            <form id="filterForm" method="get" class="filter-form">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <select name="academic_year" id="academicYear" class="form-control">
                                            <option value="">All Academic Years</option>
                                            <?php for($year = $currentAcademicYear; $year >= $earliest_year; $year--): 
                                                $yearLabel = $year . '-' . ($year + 1);
                                            ?>
                                            <option value="<?php echo $year; ?>" <?php echo $selected_academic_year == $year ? 'selected' : ''; ?>>
                                                <?php echo $yearLabel; ?>
                                            </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <select name="department" id="departmentFilter" class="form-control">
                                            <option value="">All Departments</option>
                                            <?php 
                                            $departments->data_seek(0);
                                            while($dept = $departments->fetch_assoc()):
                                            ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo $selected_department == $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <select name="course" id="courseFilter" class="form-control" <?php echo empty($selected_department) ? 'disabled' : ''; ?>>
                                            <option value="">All Courses</option>
                                            <?php if($courses): while($course = $courses->fetch_assoc()): ?>
                                            <option value="<?php echo $course['id']; ?>" <?php echo $selected_course == $course['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($course['name']); ?>
                                            </option>
                                            <?php endwhile; endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <button type="button" id="resetFilters" class="btn btn-secondary w-100">
                                            <i class="fas fa-sync-alt"></i> Reset Filters
                                        </button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">From</span>
                                            </div>
                                            <input type="date" name="start_date" id="startDate" class="form-control" value="<?php echo $start_date; ?>" max="<?php echo $today; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">To</span>
                                            </div>
                                            <input type="date" name="end_date" id="endDate" class="form-control" value="<?php echo $end_date; ?>" max="<?php echo $today; ?>">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                                <!-- Hidden field to preserve status -->
                                <input type="hidden" name="status" id="statusField" value="<?php echo $current_status; ?>">
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department View (default view) -->
            <div id="departmentView" class="<?php echo $selected_department ? 'd-none' : ''; ?>">
                <div class="analytics-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-building"></i>
                            Department Report Distribution
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($department_data) || array_sum($chart_report_counts) == 0): ?>
                        <div class="no-data-message">
                            <i class="fas fa-info-circle"></i>
                            <p>No incident reports found with the current filters.</p>
                        </div>
                        <?php else: ?>
                        <div class="chart-container">
                            <canvas id="departmentChart"></canvas>
                        </div>
                        <div class="table-responsive mt-4">
                            <table class="table analytics-table">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Incident Reports</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($department_data as $dept): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                        <td>
                                            <span class="count-badge"><?php echo $dept['report_count']; ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary view-courses" 
                                                    data-dept-id="<?php echo $dept['id']; ?>"
                                                    <?php echo $dept['report_count'] == 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-eye"></i> View Courses
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Course View (when department is selected) -->
            <div id="courseView" class="<?php echo $selected_department ? '' : 'd-none'; ?>">
                <div class="analytics-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <a href="#" id="backToDepartments" class="back-link">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <?php echo isset($selected_department_name) ? htmlspecialchars($selected_department_name) : ''; ?>
                            <span class="badge badge-primary"><?php echo isset($selected_department_count) ? $selected_department_count : 0; ?> Reports</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if(empty($course_data) || array_sum($chart_course_counts) == 0): ?>
                        <div class="no-data-message">
                            <i class="fas fa-info-circle"></i>
                            <p>No incident reports found for this department with the current filters.</p>
                        </div>
                        <?php else: ?>
                        <div class="chart-container">
                            <canvas id="courseChart"></canvas>
                        </div>
                        <div class="table-responsive mt-4">
                            <table class="table analytics-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Incident Reports</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($course_data as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['name']); ?></td>
                                        <td>
                                            <span class="count-badge"><?php echo $course['report_count']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Export buttons -->
            <div class="export-actions mt-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-success btn-block export-btn" onclick="exportData('pdf')">
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button class="btn btn-success btn-block export-btn" onclick="exportData('csv')">
                            <i class="fas fa-file-csv"></i> Export as CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>Contact number | Email | Copyright</p>
    </footer>

   <script>
// JavaScript for incident_reports_analytics.php

document.addEventListener('DOMContentLoaded', function() {
    // Chart configurations
    let departmentChartInstance = null;
    let courseChartInstance = null;

    // Initialize department chart if data exists
    initDepartmentChart();
    
    // Initialize course chart if on course view
    if (!document.getElementById('courseView').classList.contains('d-none')) {
        initCourseChart();
    }

    // Status tabs click handling
    document.querySelectorAll('#statusTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            document.querySelectorAll('#statusTabs .nav-link').forEach(t => {
                t.classList.remove('active');
            });
            this.classList.add('active');
            
            // Update hidden status field
            const status = this.getAttribute('data-status');
            document.getElementById('statusField').value = status;
            
            // Submit the form
            document.getElementById('filterForm').submit();
        });
    });

    // Department filter change event
    document.getElementById('departmentFilter').addEventListener('change', function() {
        const departmentId = this.value;
        const courseSelect = document.getElementById('courseFilter');
        
        // Reset and disable course select if no department selected
        if (!departmentId) {
            courseSelect.innerHTML = '<option value="">All Courses</option>';
            courseSelect.disabled = true;
            return;
        }
        
        // Enable course select and load courses
        courseSelect.disabled = true; // Temporarily disable while loading
        
        // Fetch courses for selected department
        fetch(`get_courses-incident_analytics.php?department_id=${departmentId}`)
            .then(response => response.json())
            .then(courses => {
                courseSelect.innerHTML = '<option value="">All Courses</option>';
                
                courses.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.name;
                    courseSelect.appendChild(option);
                });
                
                courseSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading courses:', error);
                courseSelect.disabled = false;
            });
    });

    // View courses button click event
    document.querySelectorAll('.view-courses').forEach(button => {
        button.addEventListener('click', function() {
            const deptId = this.getAttribute('data-dept-id');
            if (deptId) {
                document.getElementById('departmentFilter').value = deptId;
                document.getElementById('filterForm').submit();
            }
        });
    });

    // Back to departments link click event
    document.getElementById('backToDepartments').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('departmentFilter').value = '';
        document.getElementById('courseFilter').value = '';
        document.getElementById('filterForm').submit();
    });

    // Reset filters button click event
    document.getElementById('resetFilters').addEventListener('click', function() {
        document.getElementById('departmentFilter').value = '';
        document.getElementById('courseFilter').value = '';
        document.getElementById('courseFilter').disabled = true;
        document.getElementById('academicYear').value = '';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('filterForm').submit();
    });

    // Date input validation
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    startDateInput.addEventListener('change', function() {
        if (endDateInput.value && this.value > endDateInput.value) {
            endDateInput.value = this.value;
        }
    });
    
    endDateInput.addEventListener('change', function() {
        if (startDateInput.value && this.value < startDateInput.value) {
            startDateInput.value = this.value;
        }
    });

    // Department Chart initialization
    function initDepartmentChart() {
        const chartCanvas = document.getElementById('departmentChart');
        if (!chartCanvas) return;

        const chartDepartments = <?php echo json_encode($chart_departments); ?>;
        const chartCounts = <?php echo json_encode($chart_report_counts); ?>;
        const departmentColors = <?php echo json_encode($dept_colors); ?>;
        
        if (chartDepartments.length === 0 || chartCounts.reduce((a, b) => a + b, 0) === 0) {
            return;
        }

        // Destroy existing chart if it exists
        if (departmentChartInstance) {
            departmentChartInstance.destroy();
        }

        departmentChartInstance = new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: chartDepartments,
                datasets: [{
                    label: 'Incident Reports',
                    data: chartCounts,
                    backgroundColor: departmentColors,
                    borderColor: departmentColors.map(color => color.replace('0.9', '1')),
                    borderWidth: 1,
                    borderRadius: 5,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2c3e50',
                        bodyColor: '#2c3e50',
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        caretSize: 7,
                        cornerRadius: 6,
                        displayColors: true,
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                return `Reports: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 12
                            },
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Course Chart initialization
    function initCourseChart() {
        const chartCanvas = document.getElementById('courseChart');
        if (!chartCanvas) return;

        const chartCourses = <?php echo json_encode($chart_courses); ?>;
        const chartCounts = <?php echo json_encode($chart_course_counts); ?>;
        const courseColors = <?php echo json_encode($course_colors); ?>;
        
        if (chartCourses.length === 0 || chartCounts.reduce((a, b) => a + b, 0) === 0) {
            return;
        }

        // Destroy existing chart if it exists
        if (courseChartInstance) {
            courseChartInstance.destroy();
        }

        courseChartInstance = new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: chartCourses,
                datasets: [{
                    label: 'Incident Reports',
                    data: chartCounts,
                    backgroundColor: courseColors,
                    borderColor: courseColors.map(color => color.replace('0.9', '1')),
                    borderWidth: 1,
                    borderRadius: 5,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        titleColor: '#2c3e50',
                        bodyColor: '#2c3e50',
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        caretSize: 7,
                        cornerRadius: 6,
                        displayColors: true,
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                return `Reports: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 12
                            },
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            },
                            callback: function(value, index) {
                                // Truncate long labels for readability
                                const label = this.getLabelForValue(value);
                                return label.length > 15 ? label.substr(0, 15) + '...' : label;
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Export data function
    window.exportData = function(format) {
        const status = document.getElementById('statusField').value;
        const department = document.getElementById('departmentFilter').value;
        const course = document.getElementById('courseFilter').value;
        const academicYear = document.getElementById('academicYear').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        const url = `export_incident_analytics.php?format=${format}&status=${encodeURIComponent(status)}&department=${encodeURIComponent(department)}&course=${encodeURIComponent(course)}&academic_year=${encodeURIComponent(academicYear)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
        
        window.open(url, '_blank');
    };
});
</script>
</body>
</html>