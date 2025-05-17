<?php
session_start();
include '../db.php'; // Ensure database connection is established
// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
// Get the admin's ID from the session
$admin_id = $_SESSION['user_id'];
// Fetch the admin's details from the database
$stmt = $connection->prepare("SELECT first_name, last_name, profile_picture FROM tbl_admin WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error); // Output the error message
}
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $name = $admin['first_name'] . ' ' . $admin['last_name'];  // Concatenate first and last name
    $profile_picture = $admin['profile_picture'];
} else {
    die("Admin not found.");
}
// Fetch user counts for each table
$user_tables = ['tbl_counselor', 'tbl_facilitator', 'tbl_adviser', 'tbl_instructor', 'tbl_dean', 'tbl_student', 'tbl_guard'];
$user_counts = [];
foreach ($user_tables as $table) {
    $count_query = "SELECT COUNT(*) as count FROM $table";
    $count_result = mysqli_query($connection, $count_query);
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $user_counts[$table] = $count_row['count'];
    } else {
        $user_counts[$table] = 0;
    }
}

$chartData = array_map(function($table, $count) {
    return [
        'name' => ucwords(str_replace('tbl_', '', $table)),
        'count' => $count
    ];
}, array_keys($user_counts), array_values($user_counts));

// Function to get departments with incident report counts
function getDepartmentsWithReportCounts($connection, $start_date = null, $end_date = null) {
    $date_condition = "";
    if ($start_date && $end_date) {
        $date_condition = " AND ir.date_reported BETWEEN ? AND ?";
    }
    
    $query = "SELECT d.id, d.name, COUNT(DISTINCT ir.id) as report_count
              FROM departments d
              LEFT JOIN courses c ON d.id = c.department_id
              LEFT JOIN sections s ON c.id = s.course_id
              LEFT JOIN student_violations sv ON sv.section_id = s.id
              LEFT JOIN incident_reports ir ON sv.incident_report_id = ir.id
              WHERE d.status = 'active' $date_condition
              GROUP BY d.id, d.name
              ORDER BY COUNT(DISTINCT ir.id) DESC";
              
    $stmt = $connection->prepare($query);
    
    if ($start_date && $end_date) {
        $stmt->bind_param("ss", $start_date, $end_date);
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
function getCoursesWithReportCounts($connection, $department_id, $start_date = null, $end_date = null) {
    $date_condition = "";
    if ($start_date && $end_date) {
        $date_condition = " AND ir.date_reported BETWEEN ? AND ?";
    }
    
    $query = "SELECT c.id, c.name, COUNT(DISTINCT ir.id) as report_count
              FROM courses c
              LEFT JOIN sections s ON c.id = s.course_id
              LEFT JOIN student_violations sv ON sv.section_id = s.id
              LEFT JOIN incident_reports ir ON sv.incident_report_id = ir.id
              WHERE c.department_id = ? AND c.status = 'active' $date_condition
              GROUP BY c.id, c.name
              ORDER BY COUNT(DISTINCT ir.id) DESC";
              
    $stmt = $connection->prepare($query);
    
    if ($start_date && $end_date) {
        $stmt->bind_param("iss", $department_id, $start_date, $end_date);
    } else {
        $stmt->bind_param("i", $department_id);
    }
    
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

// Initialize data - we'll get this via AJAX later
$departments = getDepartmentsWithReportCounts($connection);
$department_id = null;
$courses = [];
$selected_department = null;
$start_date = null;
$end_date = null;

// Get all departments for dropdown
$all_departments_query = "SELECT id, name FROM departments WHERE status = 'active' ORDER BY name";
$all_departments_result = $connection->query($all_departments_query);
$all_departments = [];

if ($all_departments_result) {
    while ($row = $all_departments_result->fetch_assoc()) {
        $all_departments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">


</head>
<style>
   /* Analytics Dashboard Styling */
:root {
    --primary-light: #b2fba5;
    --primary: #a6e999;
    --primary-medium: #99d88e;
    --primary-dark: #8dc682;
    --accent: #80b577;
    --deep: #74a36b;
    --text-dark: #2c3e50;
    --text-light: #ffffff;
    --shadow: 0 8px 24px rgba(116, 163, 107, 0.2);
}

/* Analytics Cards Container */
.analytics-section {
    margin-bottom: 2rem;
}

.analytics-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}

.analytics-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(149, 157, 165, 0.3);
}

/* Card Header Styling */
.analytics-card .card-header {
    background: #008F57;
    color: white;
    padding: 1.5rem;
    border: none;
}

.analytics-card .card-title {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.analytics-card .card-title i {
    font-size: 1.5rem;
}

/* Table Styling */
.analytics-table {
    margin: 0;
    width: 100%;
}

.analytics-table thead th {
    background: #cbf5dd;
    color: var(--text-dark);
    font-weight: 600;
    padding: 1.25rem;
    border: none;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}

.analytics-table tbody tr {
    transition: background-color 0.2s ease;
}

.analytics-table tbody tr:nth-child(even) {
    background-color: rgba(67, 97, 238, 0.05);
}

.analytics-table tbody tr:hover {
    background-color: rgba(67, 97, 238, 0.1);
}

.analytics-table td {
    padding: 1.25rem;
    font-size: 1rem;
    color: var(--text-dark);
    border-bottom: 1px solid var(--grid-line);
}


.analytics-table td:last-child {
    font-weight: 600;
    color: var(--chart-primary);
}

/* Chart Styling */
.chart-container {
    padding: 1.5rem;
    position: relative;
}

canvas#userChart {
    max-height: 350px !important;
    margin: 1rem 0;
}

/* Custom Legend Styling */
.chart-legend {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(67, 97, 238, 0.05);
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #2c3e50;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.count-badge {
    color: black;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}


/* Responsive Design */
@media (max-width: 768px) {
    .analytics-card {
        margin-bottom: 1.5rem;
    }

    .analytics-table td, 
    .analytics-table th {
        padding: 1rem;
    }

    canvas#userChart {
        max-height: 300px !important;
    }

    .chart-legend {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
}

/* Chart Tooltip Customization */
.chart-tooltip {
    background: rgba(255, 255, 255, 0.98) !important;
    backdrop-filter: blur(4px);
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    padding: 0.75rem !important;
    border: 1px solid rgba(67, 97, 238, 0.1) !important;
}

.chart-container {
    height: 400px !important;  /* Fixed height */
    padding: 1.5rem;
    position: relative;
}
/* Department Reports Analytics Styling */
.back-to-departments {
    cursor: pointer;
    transition: transform 0.2s;
    font-size: 1.2rem;
}

.back-to-departments:hover {
    transform: translateX(-3px);
    color: var(--primary-medium);
}

.view-courses {
    transition: all 0.2s;
}

.view-courses:hover {
    background-color: var(--primary-medium);
    color: white;
}

/* Date Filter Form */
#reportFilterForm {
    background-color: rgba(67, 97, 238, 0.05);
    padding: 1.25rem;
    border-radius: 8px;
}

#reportFilterForm label {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text-dark);
}

#reportFilterForm .form-control,
#reportFilterForm .form-select {
    border: 1px solid rgba(67, 97, 238, 0.2);
    border-radius: 6px;
    padding: 0.6rem 1rem;
    transition: all 0.2s;
}

#reportFilterForm .form-control:focus,
#reportFilterForm .form-select:focus {
    border-color: var(--primary-medium);
    box-shadow: 0 0 0 3px rgba(166, 233, 153, 0.25);
}

#reportFilterForm .btn-primary {
    background-color: var(--primary-medium);
    border: none;
    padding: 0.6rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.2s;
}

#reportFilterForm .btn-primary:hover {
    background-color: var(--deep);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(116, 163, 107, 0.25);
}

/* Chart styling for department reports */
#departmentChart, 
#courseChart {
    max-height: 350px !important;
}

/* Animation for view transition */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

#departmentView, 
#courseView {
    animation: fadeIn 0.3s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #reportFilterForm {
        padding: 1rem;
    }
    
    #departmentChart, 
    #courseChart {
        max-height: 300px !important;
    }
    
    .back-to-departments {
        font-size: 1rem;
    }
}
/* Dashboard Tabs Styling */
.dashboard-tabs {
    border-bottom: 2px solid var(--primary-medium);
    margin-bottom: 1.5rem;
}

.dashboard-tabs .nav-link {
    border: none;
    border-radius: 0;
    font-weight: 600;
    color: var(--text-dark);
    padding: 0.75rem 1.5rem;
    transition: all 0.2s ease;
    position: relative;
}

.dashboard-tabs .nav-link::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -2px;
    height: 3px;
    background-color: transparent;
    transition: background-color 0.2s ease;
}

.dashboard-tabs .nav-link.active {
    color: var(--primary-dark);
    background-color: transparent;
    border-color: transparent;
}

.dashboard-tabs .nav-link.active::after {
    background-color: var(--primary-dark);
}

.dashboard-tabs .nav-link:hover {
    border-color: transparent;
    background-color: rgba(128, 181, 119, 0.1);
}

.dashboard-tabs .nav-link i {
    margin-right: 0.5rem;
}

/* Tab Content Animation */
.tab-pane.fade {
    transition: opacity 0.15s linear;
}

.tab-pane.fade.show {
    opacity: 1;
}

/* Responsive Adjustments for Tabs */
@media (max-width: 768px) {
    .dashboard-tabs .nav-link {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .dashboard-tabs {
        flex-direction: column;
    }
    
    .dashboard-tabs .nav-item {
        width: 100%;
    }
    
    .dashboard-tabs .nav-link {
        text-align: left;
        border-radius: 0;
    }
    
    .dashboard-tabs .nav-link::after {
        bottom: 0;
        right: auto;
        width: 3px;
        height: 100%;
        top: 0;
    }
}
#loadingIndicator {
    position: relative;
    min-height: 200px;
}

#loadingIndicator .spinner-border {
    width: 3rem;
    height: 3rem;
}

.filter-control {
    transition: all 0.3s ease;
    background-color: #fff;
}

.filter-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(153, 216, 142, 0.25);
    border-color: var(--primary-medium);
}

#resetFilters {
    background-color: #6c757d;
    border: none;
    transition: all 0.3s ease;
}

#resetFilters:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}

.department-row, .course-row {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.department-row:hover, .course-row:hover {
    background-color: rgba(153, 216, 142, 0.1);
}

.no-data-message {
    text-align: center;
    padding: 40px 0;
    color: #6c757d;
    font-style: italic;
}

/* Fade animation for view switching */
.fadeIn {
    animation: fadeInAnim 0.4s ease forwards;
}

.fadeOut {
    animation: fadeOutAnim 0.2s ease forwards;
}

@keyframes fadeInAnim {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOutAnim {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(-10px); }
}

</style>
<body>
<div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'admin_sidebar.php'; ?> <!-- Include sidebar with admin-specific links -->
    <main class="main-content">
    <!-- Tab navigation -->
    <ul class="nav nav-tabs dashboard-tabs mb-4" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="user-analytics-tab" data-bs-toggle="tab" data-bs-target="#user-analytics" type="button" role="tab" aria-controls="user-analytics" aria-selected="true">
                <i class="bi bi-people-fill me-2"></i>User Analytics
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="incident-reports-tab" data-bs-toggle="tab" data-bs-target="#incident-reports" type="button" role="tab" aria-controls="incident-reports" aria-selected="false">
                <i class="bi bi-building-fill me-2"></i>Incident Reports
            </button>
        </li>
    </ul>
    
    <!-- Tab content -->
    <div class="tab-content" id="dashboardTabsContent">
        <!-- User Analytics Tab -->
        <div class="tab-pane fade show active" id="user-analytics" role="tabpanel" aria-labelledby="user-analytics-tab">
            <div class="row analytics-section">
                <div class="col-md-6">
                    <div class="analytics-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-people-fill"></i>
                                User Analytics
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="analytics-table">
                                    <thead>
                                        <tr>
                                            <th>User Type</th>
                                            <th>Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_counts as $table => $count): ?>
                                            <tr>
                                                <td><?php echo ucwords(str_replace('tbl_', '', $table)); ?></td>
                                                <td> <span class="count-badge"><?php echo $count; ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="analytics-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-bar-chart-fill"></i>
                                User Distribution
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="userChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Incident Reports Tab -->
            <div class="tab-pane fade" id="incident-reports" role="tabpanel" aria-labelledby="incident-reports-tab">
                <div class="row analytics-section">
                    <div class="col-md-12">
                        <div class="analytics-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="bi bi-building-fill"></i>
                                    Department Incident Reports
                                </h3>
                            </div>
                            <div class="card-body">
                                <!-- Date Filter Form - Now using on-change events -->
                                <div id="reportFilterForm" class="mb-4">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-2">
                                            <label for="start_date" class="form-label">Date From</label>
                                            <input type="date" class="form-control filter-control" id="start_date" name="start_date" 
                                                max="<?php echo $today; ?>" value="<?php echo $start_date ?? ''; ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="end_date" class="form-label">Date To</label>
                                            <input type="date" class="form-control filter-control" id="end_date" name="end_date" 
                                                max="<?php echo $today; ?>" value="<?php echo $end_date ?? ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="department_id" class="form-label">Department</label>
                                            <select class="form-select filter-control" id="department_id" name="department_id">
                                                <option value="">All Departments</option>
                                                <?php foreach ($all_departments as $dept): ?>
                                                <option value="<?php echo $dept['id']; ?>">
                                                    <?php echo htmlspecialchars($dept['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="status_filter" class="form-label">Status</label>
                                            <select class="form-select filter-control" id="status_filter" name="status_filter">
                                                <option value="">All Statuses</option>
                                                <option value="Pending">Pending</option>
                                                <option value="For Meeting">For Meeting</option>
                                                <option value="Rescheduled">Rescheduled</option>
                                                <option value="Settled">Settled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" id="resetFilters" class="btn btn-secondary w-100">
                                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading indicator -->
                                <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading data...</p>
                                </div>

                                <!-- Main Content -->
                                <div id="reportContent">
                                    <!-- Content will be loaded via AJAX -->
                                    <div id="departmentView">
                                        <!-- Department Chart -->
                                        <div class="chart-container">
                                            <canvas id="departmentChart"></canvas>
                                        </div>
                                        
                                        <!-- Department Table -->
                                        <div class="table-responsive mt-4">
                                            <table class="analytics-table" id="departmentTable">
                                                <thead>
                                                    <tr>
                                                        <th>Department</th>
                                                        <th>Incident Reports</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Will be populated via AJAX -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div id="courseView" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4 class="mb-0">
                                                <i class="bi bi-arrow-left-circle me-2 back-to-departments"></i>
                                                <span id="selectedDepartmentName"></span>
                                            </h4>
                                            <span class="badge bg-info fs-6"><span id="departmentReportCount">0</span> Reports</span>
                                        </div>
                                        
                                        <!-- Course Chart -->
                                        <div class="chart-container">
                                            <canvas id="courseChart"></canvas>
                                        </div>
                                        
                                        <!-- Course Table -->
                                        <div class="table-responsive mt-4">
                                            <table class="analytics-table" id="courseTable">
                                                <thead>
                                                    <tr>
                                                        <th>Course</th>
                                                        <th>Incident Reports</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Will be populated via AJAX -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</main>
    <footer class="footer">
        <p>Contact number | Email | Copyright</p>
    </footer>

    <script src="dashboard_handler.js"></script>
</body>
</html>