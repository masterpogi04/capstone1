<?php
//view_approved_reports.php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Function to convert text to proper case
function toProperCase($name) {
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

$search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'meeting_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$filter_schedule = isset($_GET['filter_schedule']) ? $_GET['filter_schedule'] : '';
$filter_course = isset($_GET['filter_course']) ? $connection->real_escape_string($_GET['filter_course']) : '';

// Pagination parameters
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Modified base query to group concat students
$base_query = "FROM incident_reports ir 
               LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
               LEFT JOIN tbl_student s ON sv.student_id = s.student_id
               LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
               LEFT JOIN sections sec ON s.section_id = sec.id
               LEFT JOIN courses c ON sec.course_id = c.id
               WHERE (ir.status = 'For Meeting' OR ir.status = 'Approved' OR ir.status = 'Rescheduled')";

// Add search condition
if (!empty($search)) {
    $base_query .= " AND (s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR ir.description LIKE '%$search%')";
}

if ($filter_schedule === 'scheduled') {
    $base_query .= " AND EXISTS (SELECT 1 FROM meetings m WHERE m.incident_report_id = ir.id)";
} elseif ($filter_schedule === 'unscheduled') {
    $base_query .= " AND NOT EXISTS (SELECT 1 FROM meetings m WHERE m.incident_report_id = ir.id)";
}

if (!empty($filter_course)) {
    $base_query .= " AND c.name = '$filter_course'";
}

// Count total records for pagination
$count_query = "SELECT COUNT(DISTINCT ir.id) as total " . $base_query;
$result_count = $connection->query($count_query);
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Calculate the record range being displayed
$start_record = ($page - 1) * $records_per_page + 1;
$end_record = min($start_record + $records_per_page - 1, $total_records);

// Adjust start_record when there are no records
if ($total_records == 0) {
    $start_record = 0;
}

// Force at least 1 page even if no records
if ($total_pages < 1) {
    $total_pages = 1;
}

// Modified main query to include GROUP_CONCAT for students
$query = "SELECT ir.*, 
          GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as students,
          GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as course_names,
          GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
          ir.description, 
          (SELECT meeting_date FROM meetings WHERE incident_report_id = ir.id ORDER BY meeting_date DESC LIMIT 1) as meeting_date,
          (SELECT COUNT(*) FROM meetings 
           WHERE incident_report_id = ir.id 
           AND meeting_minutes IS NOT NULL 
           AND TRIM(meeting_minutes) != '') as meeting_minutes_count " . 
          $base_query . 
          " GROUP BY ir.id ORDER BY $sort $order " .
          "LIMIT $records_per_page OFFSET $offset";

$result = $connection->query($query);

if ($result === false) {
    die("Query failed: " . $connection->error);
}

// Fetch all courses for the filter dropdown
$course_query = "SELECT DISTINCT name FROM courses ORDER BY name";
$course_result = $connection->query($course_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports for Meeting</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    justify-content: center;
    align-items: center;
    display: flex;
}

.container {
    background-color: rgba(255, 255, 255, 0.98);
    border-radius: 15px;
    padding: 30px;
    margin: 50px auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

/* Modern Back Button */
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

/* Heading Styles */
h1, h2 {
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    border-bottom: 3px solid #004d4d;
    padding-bottom: 15px;
}

/* Search and Filter Form */
.search-filter-form {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.form-control {
    border-radius: 20px;
    padding: 8px 15px;
    border: 1px solid #ced4da;
}

/* Table Styles */
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 0.5px;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

/* Table Header */
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

thead th:first-child {
    border-top-left-radius: 10px;
}

thead th:last-child {
    border-top-right-radius: 10px;
}

/* Table Cells */
td {
    padding: 12px 15px;
    vertical-align: middle;
    border: 0.1px solid #e0e0e0;
    font-size: 14px;
    text-align: center;
}

/* No data message inside table */
.no-data-row td {
    text-align: center;
    padding: 20px;
    font-style: italic;
    color: #6c757d;
    background-color: #f8f9fa;
}

.no-data-icon {
    display: block;
    font-size: 24px;
    margin-bottom: 10px;
    color: #6c757d;
}

/* Action Buttons */
.btn {
    border-radius: 15px;
    padding: 8px 15px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    transition: all 0.3s ease;
    margin: 2px;
}

.btn-primary {
    background-color: #3498db;
    border-color: #3498db;
}

.btn-primary:hover {
    background-color: #2980b9;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-success {
    background-color: #2ecc71;
    border-color: #2ecc71;
}

.btn-success:hover {
    background-color: #27ae60;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-warning {
    background-color: #f1c40f;
    border-color: #f1c40f;
    color: #fff;
}

.btn-warning:hover {
    background-color: #f39c12;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.btn-info {
    background-color: #009E60;
    border-color: #009E60;
    color: #fff;
}

.btn-info:hover {
    background-color: #008050;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Alert styles for no data */
.alert {
    border-radius: 10px;
    padding: 15px;
    margin-top: 20px;
}

.alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
}

/* Responsive Design */
@media (max-width: 992px) {
    .container {
        padding: 15px;
        margin: 15px;
    }
    
    thead th, td {
        font-size: 13px;
        padding: 10px 8px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 11px;
    }
}
/* Responsive Design */
@media (max-width: 768px) {
    .container {
        margin: 20px;
        padding: 15px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 11px;
    }
    
    td {
        padding: 8px 10px;
    }
}
@media (max-width: 768px) {
    .container {
        padding: 12px;
        margin: 10px;
    }
    
    h2 {
        font-size: 1.5rem;
    }
    
    table.mobile-optimized td {
        display: block;
        text-align: right;
        padding-left: 50%;
        position: relative;
        border-bottom: 1px solid #eee;
    }
    
    table.mobile-optimized td:before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        width: 45%;
        text-align: left;
        font-weight: bold;
    }
    
    table.mobile-optimized thead {
        display: none;
    }
    
    table.mobile-optimized tr {
        display: block;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding-left: 0;
    }
    
    .btn {
        width: 100%;
        margin: 2px 0;
    }
    
    .filter-row {
        flex-direction: column;
    }
    
    .filter-row .filter-item {
        width: 100%;
    }
    
    .modern-back-button {
        margin-bottom: 15px;
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .container {
        padding: 10px;
        margin: 5px;
    }
    
    h2 {
        font-size: 1.3rem;
        margin: 10px 0 20px;
    }
    
    .search-filter-form {
        padding: 10px;
    }
    
    .form-control {
        font-size: 13px;
    }
    
    .filter-toggle-btn {
        width: 100%;
        margin-bottom: 10px;
    }
}

/* Toggle for filters on mobile */
.filter-collapse {
    display: none;
}

/* Card-like view for mobile */
.report-card {
    display: none;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 15px;
    padding: 15px;
} 

.report-card-item {
    margin-bottom: 8px;
    display: flex;
}

.report-card-label {
    font-weight: bold;
    width: 120px;
    color: #555;
}

.report-card-value {
    flex: 1;
}

.report-card-actions {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

/* No data for card view */
.no-data-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 15px;
    padding: 25px 15px;
    text-align: center;
    color: #6c757d;
}

.no-data-card i {
    font-size: 24px;
    display: block;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .table-view {
        display: none;
    }
    
    .card-view {
        display: block;
    }
    
    .report-card {
        display: block;
    }
}

/* Search highlight */
.highlight {
    background-color: #ffff99;
    padding: 2px;
    border-radius: 2px;
}

/* No results message */
#no-results-message {
    display: none;
    text-align: center;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
    color: #6c757d;
    font-size: 16px;
}
.pagination-info {
    text-align: center;
    margin-top: 15px;
    margin-bottom: 10px;
    font-size: 14px;
    color: #555;
}

.pagination-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 20px;
    margin-bottom: 20px;
}

.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
}

.page-item {
    margin: 0 2px;
}

.page-item .page-link {
    display: flex;
    justify-content: center;
    align-items: center;
    min-width: 40px;
    height: 40px;
    color: #333;
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #ddd;
    transition: all 0.3s;
}

.page-item.active .page-link {
    background-color: #0d693e;
    color: white;
    border-color: #0d693e;
}

.page-item .page-link:hover:not(.active) {
    background-color: #f5f5f5;
}

.page-item.disabled .page-link {
    color: #ccc;
    pointer-events: none;
    cursor: default;
}
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="guidanceservice.html" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Guidance Services</span>
        </a>
        <div>
            <h2 style="border-bottom: 3px solid #004d4d; text-align: center;">Incident Reports for Meeting</h2>
           
            <form id="filterForm" action="" method="GET" class="mb-4 search-filter-form">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search student or violation" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="filter_schedule" name="filter_schedule" class="form-control">
                            <option value="">All Schedules</option>
                            <option value="scheduled" <?php echo $filter_schedule === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="unscheduled" <?php echo $filter_schedule === 'unscheduled' ? 'selected' : ''; ?>>Unscheduled</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="filter_course" name="filter_course" class="form-control">
                            <option value="">All Courses</option>
                            <?php 
                            // Reset the course result pointer to the beginning
                            if ($course_result) {
                                $course_result->data_seek(0);
                                while ($course = $course_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($course['name']); ?>" <?php echo $filter_course === $course['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['name']); ?>
                                </option>
                            <?php 
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="sort" name="sort" class="form-control">
                            <option value="meeting_date" <?php echo $sort === 'meeting_date' ? 'selected' : ''; ?>>Meeting Date</option>
                            <option value="date_reported" <?php echo $sort === 'date_reported' ? 'selected' : ''; ?>>Date Reported</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select id="order" name="order" class="form-control">
                            <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="?" class="btn btn-secondary">Reset Filters</a>
                    </div>
                </div>
            </form>
            
            <!-- No results message for client-side filtering -->
            <div id="no-results-message" class="alert alert-info">
                <i class="fas fa-search"></i> No results match your search criteria.
            </div>
            
            <!-- Table view (for desktop) -->
            <div class="table-view table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date Reported</th>
                            <th>Student(s)</th>
                            <th>Course(s)</th>
                            <th>Violation</th>
                            <th>Witness/es</th>
                            <th>Meeting Date</th>
                            <th>Resolution Note/s</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="reportTableBody">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="data-row">
                                <td data-label="Date Reported"><?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($row['date_reported']))); ?></td>
                                <td data-label="Student(s)"><?php 
                                    // Process student names with proper case
                                    if (!empty($row['students'])) {
                                        $student_array = explode(', ', $row['students']);
                                        $processed_students = array_map('toProperCase', $student_array);
                                        echo htmlspecialchars(implode(', ', $processed_students));
                                    } else {
                                        echo 'No students specified';
                                    }
                                ?></td>
                                <td data-label="Course(s)"><?php echo htmlspecialchars($row['course_names']); ?></td>
                                <td data-label="Violation"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td data-label="Witness/es"><?php 
                                    // Process witnesses with proper case if they exist
                                    if (!empty($row['witnesses'])) {
                                        $witness_array = explode(', ', $row['witnesses']);
                                        $processed_witnesses = array_map('toProperCase', $witness_array);
                                        echo htmlspecialchars(implode(', ', $processed_witnesses));
                                    } else {
                                        echo '';
                                    }
                                ?></td>
                                <td data-label="Meeting Date"><?php echo $row['meeting_date'] ? htmlspecialchars(date('F j, Y, g:i A', strtotime($row['meeting_date']))) : 'Not scheduled'; ?></td>
                                <td data-label="Resolution Note/s">
                                    <?php if ($row['meeting_minutes_count'] > 0): ?>
                                        <button class="btn btn-info btn-sm" onclick="window.location.href='view_all_minutes.php?id=<?php echo $row['id']; ?>'">
                                            <i class="fas fa-eye"></i> View All Minutes (<?php echo $row['meeting_minutes_count']; ?> Meetings)
                                        </button>
                                    <?php else: ?>
                                        <?php if ($row['meeting_date']): ?>
                                            No minutes recorded yet
                                        <?php else: ?>
                                            No meeting scheduled
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Action">
                                    <a href="schedule_generator.php?report_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                        <?php echo $row['meeting_date'] ? 'Reschedule Meeting' : 'Schedule Meeting'; ?>
                                    </a>
                                    <button class="btn btn-success btn-sm" onclick="window.location.href='add_meeting_minutes.php?id=<?php echo $row['id']; ?>'">
                                        <i class="fas fa-plus"></i> Add Minutes
                                    </button>
                                    <form action="referral_incident_reports.php" method="GET" style="display: inline;">
                                        <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            Refer to Counselor
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- No data message inside the table -->
                        <tr class="no-data-row">
                            <td colspan="8">
                                <i class="fas fa-info-circle no-data-icon"></i>
                                No incident reports found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                </table>
            </div>
            
            <!-- Card View (for mobile screens) -->
            <div class="card-view">
                <?php
                // Reset the result set pointer to the beginning
                if ($result && $result->num_rows > 0) {
                    $result->data_seek(0);
                    while ($row = $result->fetch_assoc()):
                ?>
                    <div class="report-card data-card">
                        <div class="report-card-item">
                            <div class="report-card-label">Student(s):</div>
                            <div class="report-card-value"><?php 
                                // Process student names with proper case
                                if (!empty($row['students'])) {
                                    $student_array = explode(', ', $row['students']);
                                    $processed_students = array_map('toProperCase', $student_array);
                                    echo htmlspecialchars(implode(', ', $processed_students));
                                } else {
                                    echo 'No students specified';
                                }
                            ?></div>
                        </div>
                        <div class="report-card-item">
                            <div class="report-card-label">Course(s):</div>
                            <div class="report-card-value"><?php echo htmlspecialchars($row['course_names']); ?></div>
                        </div>
                        <div class="report-card-item">
                            <div class="report-card-label">Date Reported:</div>
                            <div class="report-card-value"><?php echo htmlspecialchars(date('F j, Y, g:i A', strtotime($row['date_reported']))); ?></div>
                        </div>
                        <div class="report-card-item">
                            <div class="report-card-label">Violation:</div>
                            <div class="report-card-value"><?php echo htmlspecialchars($row['description']); ?></div>
                        </div>
                        <div class="report-card-item">
                            <div class="report-card-label">Witness/es:</div>
                            <div class="report-card-value"><?php 
                                // Process witnesses with proper case if they exist
                                if (!empty($row['witnesses'])) {
                                    $witness_array = explode(', ', $row['witnesses']);
                                    $processed_witnesses = array_map('toProperCase', $witness_array);
                                    echo htmlspecialchars(implode(', ', $processed_witnesses));
                                } else {
                                    echo '';
                                }
                            ?></div>
                        </div>
                        <div class="report-card-item">
                            <div class="report-card-label">Meeting Date:</div>
                            <div class="report-card-value"><?php echo $row['meeting_date'] ? htmlspecialchars(date('F j, Y, g:i A', strtotime($row['meeting_date']))) : 'Not scheduled'; ?></div>
                        </div>
                        <div class="report-card-item">
                            <div class="report-card-label">Minutes:</div>
                            <div class="report-card-value">
                                <?php if ($row['meeting_minutes_count'] > 0): ?>
                                    <?php echo $row['meeting_minutes_count']; ?> meetings recorded
                                <?php else: ?>
                                    <?php echo $row['meeting_date'] ? 'No minutes recorded yet' : 'No meeting scheduled'; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="report-card-actions">
                            <?php if ($row['meeting_minutes_count'] > 0): ?>
                                <a href="view_all_minutes.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> View Minutes
                                </a>
                            <?php endif; ?>
                            <a href="schedule_generator.php?report_id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                <?php echo $row['meeting_date'] ? 'Reschedule' : 'Schedule'; ?>
                            </a>
                            <a href="add_meeting_minutes.php?id=<?php echo $row['id']; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Add Minutes
                            </a>
                            <form action="referral_incident_reports.php" method="GET" style="display: inline;">
                                <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    Refer to Counselor
                                </button>
                            </form>
                        </div>
                    </div>
                <?php
                    endwhile;
                } else {
                    // No data message for card view
                    echo '<div class="no-data-card">
                            <i class="fas fa-info-circle"></i>
                            No incident reports found.
                          </div>';
                }
                ?>
            </div>
        </div>
        <!-- Pagination info and navigation -->
<div class="pagination-container">
    <div class="pagination-info">
        <?php if ($total_records > 0): ?>
            Showing <?php echo $start_record; ?> - <?php echo $end_record; ?> out of <?php echo $total_records; ?> records
        <?php else: ?>
            No records found
        <?php endif; ?>
    </div>
    
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php 
            // Maximum pages to show (not counting next/last)
            $max_visible_pages = 3;
            
            // Calculate starting page based on current page
            $start_page = max(1, min($page - floor($max_visible_pages/2), $total_pages - $max_visible_pages + 1));
            $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
            
            // Adjust if we're showing fewer than max pages
            if ($end_page - $start_page + 1 < $max_visible_pages) {
                $start_page = max(1, $end_page - $max_visible_pages + 1);
            }
            
            // Display numbered pages
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter_schedule=<?php echo urlencode($filter_schedule); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <!-- Next page (») -->
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter_schedule=<?php echo urlencode($filter_schedule); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">&raquo;</span>
                </li>
            <?php endif; ?>
            
            <!-- Last page (»») -->
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&filter_schedule=<?php echo urlencode($filter_schedule); ?>&filter_course=<?php echo urlencode($filter_course); ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" aria-label="Last">
                        <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">&raquo;&raquo;</span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
    </div>


<script>
$(document).ready(function() {
    // Automatically submit form when dropdown selections change
    $('#filter_schedule, #filter_course, #sort, #order').on('change', function() {
        $('#filterForm').submit();
    });
    
    // Client-side search functionality
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        
        // Skip filtering for very short search terms
        if (searchTerm.length < 2) {
            $('.data-row, .data-card').show();
            $('.no-data-row, .no-data-card').show();
            $('.search-no-results-row, .search-no-results-card').remove();
            $('#no-results-message').hide();
            $('.pagination-container').show();
            return;
        }
        
        let visibleCount = 0;
        
        // Filter table rows
        $('.data-row').each(function() {
            const row = $(this);
            const rowText = row.text().toLowerCase();
            
            if (rowText.includes(searchTerm)) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });
        
        // Filter mobile cards
        $('.data-card').each(function() {
            const card = $(this);
            const cardText = card.text().toLowerCase();
            
            if (cardText.includes(searchTerm)) {
                card.show();
                visibleCount++;
            } else {
                card.hide();
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
            // Hide pagination for no results
            $('.pagination-container').hide();
            
            // Hide the regular no-data message
            $('.no-data-row:not(.search-no-results-row), .no-data-card:not(.search-no-results-card)').hide();
            
            // Check if we already have a search-no-results row in the table
            if ($('.search-no-results-row').length === 0) {
                // Create a dynamic no search results message inside the table
                $('#reportTableBody').append(
                    '<tr class="search-no-results-row no-data-row">' +
                    '<td colspan="8">' +
                    '<i class="fas fa-search no-data-icon"></i>' +
                    'No results found for "' + searchTerm + '"' +
                    '</td>' +
                    '</tr>'
                );
                
                // Also add a message for mobile card view
                $('.card-view').append(
                    '<div class="search-no-results-card no-data-card">' +
                    '<i class="fas fa-search"></i>' +
                    'No results found for "' + searchTerm + '"' +
                    '</div>'
                );
            } else {
                // Just update the existing messages with the current search term
                $('.search-no-results-row td').html(
                    '<i class="fas fa-search no-data-icon"></i>' +
                    'No results found for "' + searchTerm + '"'
                );
                $('.search-no-results-card').html(
                    '<i class="fas fa-search"></i>' +
                    'No results found for "' + searchTerm + '"'
                );
                
                // Display the search-no-results messages
                $('.search-no-results-row, .search-no-results-card').show();
            }
            
            // Hide the original outside message
            $('#no-results-message').hide();
        } else {
            // Show pagination and update info
            $('.pagination-container').show();
            $('.pagination-info').text(`Showing 1 - ${visibleCount} out of ${visibleCount} filtered records`);
            
            // Remove any search-no-results messages
            $('.search-no-results-row, .search-no-results-card').remove();
            
            // Hide the original outside message
            $('#no-results-message').hide();
            
            // Hide the regular no-data message too
            $('.no-data-row:not(.search-no-results-row), .no-data-card:not(.search-no-results-card)').hide();
        }
        
        // Highlight search terms in the visible rows/cards
        highlightSearchTerm();
    });
    
    // Restore full functionality when search is cleared
    $('#searchInput').on('keyup', function() {
        if ($(this).val().trim() === '') {
            // Show pagination when search is cleared
            $('.pagination-container').show();
            
            // Reset pagination info to server-side values
            const start = <?php echo $start_record; ?>;
            const end = <?php echo $end_record; ?>;
            const total = <?php echo $total_records; ?>;
            
            if (total > 0) {
                $('.pagination-info').text(`Showing ${start} - ${end} out of ${total} records`);
            } else {
                $('.pagination-info').text('No records found');
            }
        }
    });
    
    // Enter key in search submits the form
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#filterForm').submit();
        }
    });
    
    // Initialize responsive tables for mobile
    function initMobileView() {
        if ($(window).width() <= 768) {
            $('.table').addClass('mobile-optimized');
            
            $('table.mobile-optimized tbody tr td').each(function() {
                // Skip "no data" rows
                if (!$(this).parent().hasClass('no-data-row')) {
                    const label = $(this).closest('table').find('thead th').eq($(this).index()).text();
                    $(this).attr('data-label', label);
                }
            });
            
            $('.table-view').hide();
            $('.card-view').show();
            $('.report-card').show();
        } else {
            $('.table-view').show();
            $('.card-view').hide();
        }
    }
    
    // Initialize on load
    initMobileView();
    
    // Re-initialize on window resize
    $(window).resize(function() {
        initMobileView();
    });
    
    // Highlight search terms
    function highlightSearchTerm() {
        const searchTerm = $('#searchInput').val().trim();
        if (searchTerm.length > 1) {
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            
            $('.data-row td, .report-card-value').each(function() {
                const cell = $(this);
                const originalText = cell.text();
                
                if (originalText.toLowerCase().includes(searchTerm.toLowerCase())) {
                    cell.html(originalText.replace(regex, '<span class="highlight">$1</span>'));
                }
            });
        }
    }
    
    // Run highlight if there's a search term
    if ($('#searchInput').val().trim().length > 1) {
        highlightSearchTerm();
    }
});
</script>

</body>
</html>