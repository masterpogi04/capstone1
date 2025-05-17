<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
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

// Function to check student violations
function checkStudentViolations($student_number, $connection) {
    $query = "SELECT ir.id, ir.description, ir.status, ir.date_reported 
              FROM incident_reports ir
              JOIN student_violations sv ON ir.id = sv.incident_report_id
              WHERE sv.student_id = ? 
              AND (ir.status != 'Settled' AND ir.status != 'Resolved')";
              
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $student_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $violations = array();
    while($row = $result->fetch_assoc()) {
        $violations[] = $row;
    }
    
    return $violations;
}

// Function to send notification
function sendNotification($student_id, $status, $document_type) {
    global $connection;
    $message = "Your request for $document_type has been $status.";
    $sql = "INSERT INTO notifications (user_id, user_type, message, is_read) VALUES (?, 'student', ?, 0)";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ss", $student_id, $message);
    $stmt->execute();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    
    // First get the student number for the request
    $fetch_student = $connection->prepare("SELECT student_number, document_request FROM document_requests WHERE request_id = ?");
    $fetch_student->bind_param("s", $request_id);
    $fetch_student->execute();
    $student_result = $fetch_student->get_result();
    $student_data = $student_result->fetch_assoc();
    
    // Check for violations if trying to approve
    if ($status === 'Approved') {
        $violations = checkStudentViolations($student_data['student_number'], $connection);
        if (!empty($violations)) {
            echo json_encode([
                'error' => true,
                'message' => 'Cannot approve request. Student has pending violations.',
                'violations' => $violations
            ]);
            exit;
        }
    }
    
    // If no violations or not approving, proceed with update
    $update_stmt = $connection->prepare("UPDATE document_requests SET status = ? WHERE request_id = ?");
    $update_stmt->bind_param("ss", $status, $request_id);
    
    if ($update_stmt->execute()) {
        sendNotification($student_data['student_number'], $status, $student_data['document_request']);
        
        if ($status === 'Approved') {
            echo json_encode(['success' => true, 'redirect' => "approved_request.php?id=$request_id"]);
        } elseif ($status === 'Rejected') {
            echo json_encode(['success' => true, 'redirect' => "rejected_request.php?id=$request_id"]);
        } else {
            echo json_encode(['success' => true]);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'Failed to update status']);
    }
    exit;
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $ids_to_delete = $_POST['delete'];
    
    $delete_stmt = $connection->prepare("DELETE FROM document_requests WHERE request_id = ?");
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($ids_to_delete as $id) {
        $delete_stmt->bind_param("s", $id);
        
        if ($delete_stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $delete_stmt->close();
    
    if ($success_count > 0) {
        $_SESSION['message'] = "Successfully deleted $success_count request(s).";
        if ($error_count > 0) {
            $_SESSION['message'] .= " Failed to delete $error_count request(s).";
        }
    } else {
        $_SESSION['message'] = "Failed to delete any requests.";
    }
}

// Get filter values
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$time_filter = isset($_GET['time_filter']) ? $_GET['time_filter'] : '';
$document_filter = isset($_GET['document_filter']) ? $_GET['document_filter'] : '';
// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // items per page
$offset = ($page - 1) * $perPage;

// Count query for pagination
$countQuery = "SELECT COUNT(*) as total FROM document_requests dr WHERE 1=1";
$countParams = [];
$countTypes = "";

if (!empty($department_filter)) {
    $countQuery .= " AND department = ?";
    $countParams[] = $department_filter;
    $countTypes .= "s";
}
if (!empty($course_filter)) {
    $countQuery .= " AND course = ?";
    $countParams[] = $course_filter;
    $countTypes .= "s";
}
if (!empty($status_filter)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status_filter;
    $countTypes .= "s";
}
if (!empty($time_filter)) {
    switch ($time_filter) {
        case 'today':
            $countQuery .= " AND DATE(dr.request_time) = CURDATE()";
            break;
        case 'this_week':
            $countQuery .= " AND YEARWEEK(dr.request_time) = YEARWEEK(CURDATE())";
            break;
        case 'this_month':
            $countQuery .= " AND MONTH(dr.request_time) = MONTH(CURDATE()) AND YEAR(dr.request_time) = YEAR(CURDATE())";
            break;
    }
}
if (!empty($document_filter)) {
    $countQuery .= " AND document_request = ?";
    $countParams[] = $document_filter;
    $countTypes .= "s";
}

$countStmt = $connection->prepare($countQuery);
if ($countStmt === false) {
    die("Prepare failed: " . $connection->error);
}

if (!empty($countTypes)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Calculate record range being displayed
$start_record = ($page - 1) * $perPage + 1;
$end_record = min($start_record + $perPage - 1, $totalRows);

// Adjust start_record when there are no records
if ($totalRows == 0) {
    $start_record = 0;
}

// Force at least 1 page even if no records
if ($totalPages < 1) {
    $totalPages = 1;
}

// Modified base query to include violation check
$query = "SELECT dr.*, 
          CASE 
              WHEN EXISTS (
                  SELECT 1 
                  FROM student_violations sv 
                  JOIN incident_reports ir ON sv.incident_report_id = ir.id 
                  WHERE sv.student_id = dr.student_number 
                  AND (ir.status != 'Settled' AND ir.status != 'Resolved')
              ) THEN 'Yes' 
              ELSE 'No' 
          END as has_violations
          FROM document_requests dr 
          WHERE 1=1";

// Add filter conditions
if (!empty($department_filter)) {
    $query .= " AND dr.department = ?";
}
if (!empty($course_filter)) {
    $query .= " AND dr.course = ?";
}
if (!empty($status_filter)) {
    $query .= " AND dr.status = ?";
}
if (!empty($time_filter)) {
    switch ($time_filter) {
        case 'today':
            $query .= " AND DATE(dr.request_time) = CURDATE()";
            break;
        case 'this_week':
            $query .= " AND YEARWEEK(dr.request_time) = YEARWEEK(CURDATE())";
            break;
        case 'this_month':
            $query .= " AND MONTH(dr.request_time) = MONTH(CURDATE()) AND YEAR(dr.request_time) = YEAR(CURDATE())";
            break;
    }
}
if (!empty($document_filter)) {
    $query .= " AND dr.document_request = ?";
}

$query .= " ORDER BY dr.request_time DESC LIMIT ? OFFSET ?";

// Prepare and execute the statement
$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

// Bind parameters if filters are set
$types = "";
$params = array();

if (!empty($department_filter)) {
    $types .= "s";
    $params[] = $department_filter;
}
if (!empty($course_filter)) {
    $types .= "s";
    $params[] = $course_filter;
}
if (!empty($status_filter)) {
    $types .= "s";
    $params[] = $status_filter;
}
if (!empty($document_filter)) {
    $types .= "s";
    $params[] = $document_filter;
}

// Add LIMIT and OFFSET parameters
$types .= "ii";
$params[] = $perPage;
$params[] = $offset;

if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);

// Modified count query to match the main query structure
$countQuery = "SELECT COUNT(*) as total FROM document_requests dr WHERE 1=1";
$countParams = [];
$countTypes = "";

if (!empty($department_filter)) {
    $countQuery .= " AND department = ?";
    $countParams[] = $department_filter;
    $countTypes .= "s";
}
if (!empty($course_filter)) {
    $countQuery .= " AND course = ?";
    $countParams[] = $course_filter;
    $countTypes .= "s";
}
if (!empty($status_filter)) {
    $countQuery .= " AND status = ?";
    $countParams[] = $status_filter;
    $countTypes .= "s";
}
if (!empty($document_filter)) {
    $countQuery .= " AND document_request = ?";
    $countParams[] = $document_filter;
    $countTypes .= "s";
}

$countStmt = $connection->prepare($countQuery);
if ($countStmt === false) {
    die("Prepare failed: " . $connection->error);
}

if (!empty($countTypes)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);
// Fetch departments and courses
$dept_query = "SELECT DISTINCT department FROM document_requests";
$dept_result = $connection->query($dept_query);
$departments = $dept_result->fetch_all(MYSQLI_ASSOC);

$course_query = "SELECT DISTINCT department, course FROM document_requests ORDER BY department, course";
$course_result = $connection->query($course_query);
$courses = $course_result->fetch_all(MYSQLI_ASSOC);

// Organize courses by department
$courses_by_dept = [];
foreach ($courses as $course) {
    $courses_by_dept[$course['department']][] = $course['course'];
}

// Fetch unique document types
$doc_query = "SELECT DISTINCT document_request FROM document_requests ORDER BY document_request";
$doc_result = $connection->query($doc_query);
$document_types = $doc_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilitator Dashboard - Document Requests</title>
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

/* Header Section */
.d-flex {
    border-bottom: 3px solid #004d4d;
}

h2 {
    font-weight: 700;
    font-size: 2rem;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
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

/* Search Box Styles */
.search-container {
    position: relative;
    width: 300px;
}

#searchInput {
    padding-left: 35px;
    border-radius: 20px;
    border: 1px solid #ced4da;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

#searchInput:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 105, 62, 0.25);
    border-color: #0d693e;
    background-color: #fff;
}

.search-container::before {
    content: "\f002";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 1;
}

/* Filter Form */
.filter-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-form .form-control {
    flex: 1;
    min-width: 150px;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #ced4da;
}

.reset-btn {
    padding: 8px 15px;
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.reset-btn:hover {
    background-color: #5a6268;
    transform: translateY(-1px);
}

/* Results Counter */
.results-counter {
    font-size: 0.9rem;
    color: #6c757d;
    text-align: right;
    margin-bottom: 10px;
}

/* Table Styles */
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
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
    text-align: center;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}

th:first-child { border-top-left-radius: 10px; }
th:last-child { border-top-right-radius: 10px; }

/* Table Cells */
td {
    padding: 12px 15px;
    vertical-align: middle;
    border: 0.1px solid #e0e0e0;
    font-size: 14px;
    text-align: center;
    background-color: transparent;
}

/* Row Styling */
tbody tr {
    background-color: white;
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f8f9fa;
}

/* No Results Message */
#noResultsRow td {
    padding: 20px;
    text-align: center;
    color: #6c757d;
    font-style: italic;
}

/* Status Colors */
.status-pending { background-color: #ffd700; color: #000; }
.status-processing { background-color: #87ceeb; color: #000; }
.status-meeting { background-color: #98fb98; color: #000; }
.status-rejected { background-color: #ff6b6b; color: #fff; }
.status-approved { background-color: #90EE90; color: #000; }

/* Action Buttons */
.btn-update {
    padding: 6px 12px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.btn-update:hover {
    background-color: #2980b9;
    transform: translateY(-1px);
}

.delete-btn {
    margin-top: 20px;
    background-color: #e74c3c;
    color: white;
    padding: 8px 16px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.delete-btn:hover {
    background-color: #c0392b;
}

/* Generate Report Button */
.generate-report-btn {
    background-color: #17a2b8;
    color: white;
    padding: A8px 16px;
    border-radius: 5px;
    align-items: center;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.generate-report-btn:hover {
    background-color: #138496;
    transform: translateY(-1px);
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .search-container {
        width: 100%;
        margin-top: 15px;
    }
    
    .d-flex {
        flex-direction: column;
        align-items: stretch;
    }
    
    h2 {
        text-align: center;
    }
}

@media (max-width: 768px) {
    .container {
        margin: 20px;
        padding: 15px;
    }
    
    .filter-form {
        flex-direction: column;
    }
    
    .filter-form .form-control {
        width: 100%;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
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
    <div class="form-container">
                    <a href="guidanceservice.html" class="modern-back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        <div class="action-buttons">
            <a href="facilitator_generate_reports.php" class="btn generate-report-btn">
                <i class="fas fa-file-alt"></i> Generate Report
            </a>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2>STUDENT DOCUMENT REQUEST</h2>
            <div class="search-container">
                <input type="text" id="searchInput" class="form-control" placeholder="Search..." aria-label="Search">
            </div>
        </div>
    </div>
    
    <div class="form-content">
        <!-- Updated filter form with auto-filter class -->
        <div class="filter-form">
            <select name="department" id="department" class="form-control auto-filter">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $dept['department'] === $department_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="course" id="course" class="form-control auto-filter">
                <option value="">All Courses</option>
            </select>
            <select name="status" id="status" class="form-control auto-filter">
                <option value="">All Statuses</option>
                <option value="Pending" <?php echo 'Pending' === $status_filter ? 'selected' : ''; ?>>Pending</option>
                <option value="Processing" <?php echo 'Processing' === $status_filter ? 'selected' : ''; ?>>Processing</option>
                <option value="Approved" <?php echo 'Approved' === $status_filter ? 'selected' : ''; ?>>Approved</option>
                <option value="Rejected" <?php echo 'Rejected' === $status_filter ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <select name="time_filter" id="time_filter" class="form-control auto-filter">
                <option value="">All Time</option>
                <option value="today" <?php echo 'today' === $time_filter ? 'selected' : ''; ?>>Today</option>
                <option value="this_week" <?php echo 'this_week' === $time_filter ? 'selected' : ''; ?>>This Week</option>
                <option value="this_month" <?php echo 'this_month' === $time_filter ? 'selected' : ''; ?>>This Month</option>
            </select>
            <select name="document_filter" id="document_filter" class="form-control auto-filter">
                <option value="">All Documents</option>
                <?php foreach ($document_types as $doc): ?>
                    <option value="<?php echo htmlspecialchars($doc['document_request']); ?>" <?php echo $doc['document_request'] === $document_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($doc['document_request']); ?></option>
                <?php endforeach; ?>
            </select>
            <a href="?" class="btn btn-secondary">Reset Filters</a>
        </div>
        
        <form id="deleteForm" method="POST">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Request ID</th>
                            <th>Student Name</th>
                            <th>Student Number</th>
                            <th>Department</th>
                            <th>Course</th>
                            <th>Document</th>
                            <th>Purpose</th>
                            <th>Contact Email</th>
                            <th>Requested Date</th>
                            <th>Status</th>
                            <th>Has Violations</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><input type="checkbox" name="delete[]" value="<?php echo $request['request_id']; ?>"></td>
                            <td><?php echo htmlspecialchars($request['request_id']); ?></td>
                            <td><?php echo htmlspecialchars(toProperCase($request['first_name'] . ' ' . $request['last_name'])); ?></td>
                            <td><?php echo htmlspecialchars($request['student_number']); ?></td>
                            <td><?php echo htmlspecialchars($request['department']); ?></td>
                            <td><?php echo htmlspecialchars($request['course']); ?></td>
                            <td><?php echo htmlspecialchars($request['document_request']); ?></td>
                            <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                            <td><?php echo htmlspecialchars($request['contact_email']); ?></td>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($request['request_time']))); ?></td>
                            <td class="status-<?php echo strtolower($request['status']); ?>"><?php echo htmlspecialchars($request['status']); ?></td>
                            <td style="color: <?php echo $request['has_violations'] === 'Yes' ? 'red' : 'green'; ?>">
                                <?php echo htmlspecialchars($request['has_violations']); ?>
                            </td>
                            <td>
                                <button type="button" onclick="updateStatus('<?php echo $request['request_id']; ?>')" class="btn btn-update btn-sm">
                                    <i class="fas fa-edit"></i> Update
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Pagination info and navigation -->
<div class="pagination-container" id="pagination_container">
    <div class="pagination-info" id="pagination_info">
        <?php if ($totalRows > 0): ?>
            Showing <?php echo $start_record; ?> - <?php echo $end_record; ?> out of <?php echo $totalRows; ?> records
        <?php else: ?>
            No records found
        <?php endif; ?>
    </div>
    
    <nav aria-label="Page navigation">
        <ul class="pagination" id="pagination">
            <?php 
            // Maximum pages to show (not counting next/last)
            $max_visible_pages = 3;
            
            // Calculate starting page based on current page
            $start_page = max(1, min($page - floor($max_visible_pages/2), $totalPages - $max_visible_pages + 1));
            $end_page = min($start_page + $max_visible_pages - 1, $totalPages);
            
            // Adjust if we're showing fewer than max pages
            if ($end_page - $start_page + 1 < $max_visible_pages) {
                $start_page = max(1, $end_page - $max_visible_pages + 1);
            }
            
            // Display numbered pages
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&department=<?php echo urlencode($department_filter); ?>&course=<?php echo urlencode($course_filter); ?>&status=<?php echo urlencode($status_filter); ?>&time_filter=<?php echo urlencode($time_filter); ?>&document_filter=<?php echo urlencode($document_filter); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <!-- Next page (») -->
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&department=<?php echo urlencode($department_filter); ?>&course=<?php echo urlencode($course_filter); ?>&status=<?php echo urlencode($status_filter); ?>&time_filter=<?php echo urlencode($time_filter); ?>&document_filter=<?php echo urlencode($document_filter); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">&raquo;</span>
                </li>
            <?php endif; ?>
            
            <!-- Last page (»») -->
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&department=<?php echo urlencode($department_filter); ?>&course=<?php echo urlencode($course_filter); ?>&status=<?php echo urlencode($status_filter); ?>&time_filter=<?php echo urlencode($time_filter); ?>&document_filter=<?php echo urlencode($document_filter); ?>" aria-label="Last">
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
            <button type="submit" class="btn delete-btn">
                <i class="fas fa-trash-alt"></i> Delete Selected
            </button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var coursesByDept = <?php echo json_encode($courses_by_dept); ?>;
    var selectedDepartment = "<?php echo $department_filter; ?>";
    var selectedCourse = "<?php echo $course_filter; ?>";
    var totalPages = <?php echo $totalPages; ?>;
    var currentPage = <?php echo $page; ?>;
    var totalRows = <?php echo $totalRows; ?>;
    var startRecord = <?php echo $start_record; ?>;
    var endRecord = <?php echo $end_record; ?>;
    
    // Function to update course options based on department selection
    function updateCourseOptions() {
        var department = $('#department').val();
        var courseSelect = $('#course');
        courseSelect.empty().append('<option value="">All Courses</option>');

        if (department && coursesByDept[department]) {
            $.each(coursesByDept[department], function(i, course) {
                courseSelect.append($('<option>', {
                    value: course,
                    text: course,
                    selected: (course === selectedCourse)
                }));
            });
        }
    }
    
    // Auto-filter on any select change with page reload
    $('.auto-filter').change(function() {
        // For server-side filtering, we'll reload the page with the filter parameters
        var department = $('#department').val();
        var course = $('#course').val();
        var status = $('#status').val();
        var timeFilter = $('#time_filter').val();
        var documentFilter = $('#document_filter').val();
        
        window.location.href = '?page=1&department=' + encodeURIComponent(department) + 
                              '&course=' + encodeURIComponent(course) + 
                              '&status=' + encodeURIComponent(status) + 
                              '&time_filter=' + encodeURIComponent(timeFilter) + 
                              '&document_filter=' + encodeURIComponent(documentFilter);
    });
    
    // Client-side search functionality
    $('#searchInput').on('input', function() {
        var searchText = $(this).val().toLowerCase();
        if (searchText.length > 0) {
            applyClientSideSearch(searchText);
        } else {
            // Reset the display to show current page data
            resetClientSideSearch();
        }
    });
    
    // Apply client-side search without page reload
    function applyClientSideSearch(searchText) {
        var visibleCount = 0;
        
        // Show/hide rows based on search text
        $('table tbody tr').each(function() {
            var row = $(this);
            if (row.attr('id') === 'noResultsRow') return; // Skip no results message row
            
            // Search text match (check in multiple columns)
            var textMatch = 
                row.find('td:eq(1)').text().toLowerCase().includes(searchText) || // Request ID
                row.find('td:eq(2)').text().toLowerCase().includes(searchText) || // Student Name
                row.find('td:eq(3)').text().toLowerCase().includes(searchText) || // Student Number
                row.find('td:eq(4)').text().toLowerCase().includes(searchText) || // Department
                row.find('td:eq(5)').text().toLowerCase().includes(searchText) || // Course
                row.find('td:eq(6)').text().toLowerCase().includes(searchText) || // Document
                row.find('td:eq(7)').text().toLowerCase().includes(searchText);   // Purpose
            
            if (textMatch) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });
        
        // If no rows are visible, show a message
        if (visibleCount === 0) {
            if ($('#noResultsRow').length === 0) {
                $('table tbody').append('<tr id="noResultsRow"><td colspan="13" class="text-center">No matching records found</td></tr>');
            } else {
                $('#noResultsRow').show();
            }
        } else {
            $('#noResultsRow').hide();
        }
        
        // Update pagination info for client-side search
        $('#pagination_info').text(`Showing 1 - ${visibleCount} out of ${visibleCount} filtered records`);
        
        // Show a simplified pagination for client-side search
        updatePaginationForClientSide(visibleCount);
    }
    
    // Reset client-side search to show server-side pagination
    function resetClientSideSearch() {
        // Remove no results message if exists
        $('#noResultsRow').remove();
        
        // Show all rows on current page
        $('table tbody tr').show();
        
        // Reset pagination info to server values
        $('#pagination_info').text(`Showing ${startRecord} - ${endRecord} out of ${totalRows} records`);
        
        // Reset pagination to server-side values
        resetPaginationToServerSide();
    }
    
    // Update pagination for client-side filtering
    function updatePaginationForClientSide(visibleCount) {
        const pagination = $('#pagination');
        pagination.empty();
        
        // For client-side filtering, we just show a simple "1" since all results are on one page
        const pageItem = $('<li>').addClass('page-item active');
        const pageLink = $('<a>').addClass('page-link').attr('href', '#').text('1');
        pageItem.append(pageLink);
        pagination.append(pageItem);
        
        // Add disabled next/last buttons
        const nextItem = $('<li>').addClass('page-item disabled');
        const nextLink = $('<span>').addClass('page-link').html('&raquo;');
        nextItem.append(nextLink);
        pagination.append(nextItem);
        
        const lastItem = $('<li>').addClass('page-item disabled');
        const lastLink = $('<span>').addClass('page-link').html('&raquo;&raquo;');
        lastItem.append(lastLink);
        pagination.append(lastItem);
    }
    
    // Reset pagination to server-side values
    function resetPaginationToServerSide() {
        const pagination = $('#pagination');
        pagination.empty();
        
        // Maximum pages to show
        const maxVisiblePages = 3;
        
        // Calculate starting page
        const startPage = Math.max(1, Math.min(currentPage - Math.floor(maxVisiblePages/2), totalPages - maxVisiblePages + 1));
        const endPage = Math.min(startPage + maxVisiblePages - 1, totalPages);
        
        // Add page numbers
        for (let i = startPage; i <= endPage; i++) {
            const pageItem = $('<li>').addClass('page-item');
            if (i === currentPage) pageItem.addClass('active');
            
            const pageLink = $('<a>').addClass('page-link')
                .attr('href', `?page=${i}&department=${encodeURIComponent(selectedDepartment)}&course=${encodeURIComponent(selectedCourse)}&status=${encodeURIComponent($('#status').val())}&time_filter=${encodeURIComponent($('#time_filter').val())}&document_filter=${encodeURIComponent($('#document_filter').val())}`)
                .text(i);
                
            pageItem.append(pageLink);
            pagination.append(pageItem);
        }
        
        // Next button
        const nextItem = $('<li>').addClass('page-item');
        if (currentPage >= totalPages) nextItem.addClass('disabled');
        
        const nextLink = currentPage >= totalPages ? 
            $('<span>').addClass('page-link').html('&raquo;') :
            $('<a>').addClass('page-link')
                .attr('href', `?page=${currentPage + 1}&department=${encodeURIComponent(selectedDepartment)}&course=${encodeURIComponent(selectedCourse)}&status=${encodeURIComponent($('#status').val())}&time_filter=${encodeURIComponent($('#time_filter').val())}&document_filter=${encodeURIComponent($('#document_filter').val())}`)
                .html('&raquo;');
                
        nextItem.append(nextLink);
        pagination.append(nextItem);
        
        // Last button
        const lastItem = $('<li>').addClass('page-item');
        if (currentPage >= totalPages) lastItem.addClass('disabled');
        
        const lastLink = currentPage >= totalPages ?
            $('<span>').addClass('page-link').html('&raquo;&raquo;') :
            $('<a>').addClass('page-link')
                .attr('href', `?page=${totalPages}&department=${encodeURIComponent(selectedDepartment)}&course=${encodeURIComponent(selectedCourse)}&status=${encodeURIComponent($('#status').val())}&time_filter=${encodeURIComponent($('#time_filter').val())}&document_filter=${encodeURIComponent($('#document_filter').val())}`)
                .html('&raquo;&raquo;');
                
        lastItem.append(lastLink);
        pagination.append(lastItem);
    }
    
    // Initial course population
    $('#department').change(updateCourseOptions);
    updateCourseOptions();
    
    // Set the selected department and trigger change to populate courses
    if (selectedDepartment) {
        $('#department').val(selectedDepartment).trigger('change');
    }
    
    // Select all checkbox functionality
    $('#selectAll').click(function() {
        $('input[name="delete[]"]').prop('checked', this.checked);
    });

    // Submit delete form with confirmation
    $('#deleteForm').submit(function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Show message if set
    <?php if (isset($_SESSION['message'])): ?>
    Swal.fire({
        title: 'Info',
        text: '<?php echo $_SESSION['message']; ?>',
        icon: 'info'
    });
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
});

// Modified updateStatus function to handle violations
function updateStatus(requestId) {
    Swal.fire({
        title: 'Update Status',
        input: 'select',
        inputOptions: {
            'Pending': 'Pending',
            'Processing': 'Processing',
            'Approved': 'Approved',
            'Rejected': 'Rejected'
        },
        showCancelButton: true,
        confirmButtonText: 'Update',
        showLoaderOnConfirm: true,
        preConfirm: (status) => {
            return $.ajax({
                url: '<?php echo $_SERVER['PHP_SELF']; ?>',
                method: 'POST',
                data: {
                    action: 'update_status',
                    request_id: requestId,
                    status: status
                },
                dataType: 'json'
            }).then(response => {
                if (response.error) {
                    if (response.violations) {
                        // Create a formatted HTML list of violations
                        let violationHtml = `
                            <div class="text-left">
                                <p>${response.message}</p>
                                <ul class="violation-list" style="padding-left: 20px; text-align: left;">`;
                        
                        response.violations.forEach(v => {
                            violationHtml += `<li>${v.description} (${v.date_reported})</li>`;
                        });
                        
                        violationHtml += `</ul></div>`;
                        
                        // Use Swal.fire directly instead of throwing an error
                        Swal.fire({
                            title: 'Cannot Approve',
                            html: violationHtml,
                            icon: 'error'
                        });
                        return false; // Prevent the promise from resolving
                    }
                    throw new Error(response.message);
                }
                return response;
            }).catch(error => {
                if (error !== false) { // Only show validation message for other errors
                    Swal.showValidationMessage(`${error}`);
                }
                return false;
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            if (result.value.redirect) {
                window.location.href = result.value.redirect;
            } else {
                Swal.fire({
                    title: 'Success!',
                    text: 'Status updated successfully',
                    icon: 'success'
                }).then(() => {
                    location.reload();
                });
            }
        }
    });
}
</script>
</body>
</html>