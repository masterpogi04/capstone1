<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
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
$perPage = 10; //here we can edit, how many content to display
$offset = ($page - 1) * $perPage;

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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
    text-align: center;
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

.filter-form button {
    padding: 8px 15px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Table Styles */
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
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

/* Status Colors */
.status-pending { background-color: #ffd700; color: #000; }
.status-processing { background-color: #87ceeb; color: #000; }
.status-meeting { background-color: #98fb98; color: #000; }
.status-rejected { background-color: #ff6b6b; color: #fff; }

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

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.pagination a, .pagination span {
    color: #009E60;
    padding: 8px 12px;
    text-decoration: none;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background-color: #009E60;
    color: white;
}

.pagination .active {
    background-color: #009E60;
    color: white;
    border-color: #009E60;
}

/* Responsive Adjustments */
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
}

/* Generate Report Button */
.generate-report-btn {
    background-color: #17a2b8;
    color: white;
    padding: 8px 16px;
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
</style>
</head>
<body>
<div class="container mt-5">
    <div class="form-container">
        <div class="action-buttons">
            <a href="guidanceservice.html" class="modern-back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <a href="facilitator_generate_reports.php" class="btn generate-report-btn">
                <i class="fas fa-file-alt"></i> Generate Report
            </a>
            </a><div class="d-flex justify-content-between align-items-center mb-4"style="border-bottom: 3px solid #004d4d;">
        <h2>STUDENT DOCUMENT REQUEST<h2>
    </div>
        </div>
    </div>
        <div class="form-content">
           <form class="filter-form" method="GET">
                <select name="department" id="department" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $dept['department'] === $department_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department']); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="course" id="course" class="form-control">
                    <option value="">All Courses</option>
                </select>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo 'Pending' === $status_filter ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo 'Processing' === $status_filter ? 'selected' : ''; ?>>Processing</option>
                    <option value="Approved" <?php echo 'Approved' === $status_filter ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo 'Rejected' === $status_filter ? 'selected' : ''; ?>>Rejected</option>
                </select>
                <select name="time_filter" class="form-control">
                    <option value="">All Time</option>
                    <option value="today" <?php echo 'today' === $time_filter ? 'selected' : ''; ?>>Today</option>
                    <option value="this_week" <?php echo 'this_week' === $time_filter ? 'selected' : ''; ?>>This Week</option>
                    <option value="this_month" <?php echo 'this_month' === $time_filter ? 'selected' : ''; ?>>This Month</option>
                </select>
                <select name="document_filter" class="form-control">
                    <option value="">All Documents</option>
                    <?php foreach ($document_types as $doc): ?>
                        <option value="<?php echo htmlspecialchars($doc['document_request']); ?>" <?php echo $doc['document_request'] === $document_filter ? 'selected' : ''; ?>><?php echo htmlspecialchars($doc['document_request']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
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
                                <th>Request Time</th>
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
                                <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($request['department']); ?></td>
                                <td><?php echo htmlspecialchars($request['course']); ?></td>
                                <td><?php echo htmlspecialchars($request['document_request']); ?></td>
                                <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($request['contact_email']); ?></td>
                                <td><?php echo htmlspecialchars($request['request_time']); ?></td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
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
                </div>
                <button type="submit" class="btn delete-btn">
                    <i class="fas fa-trash-alt"></i> Delete Selected
                </button>
            </form>
        </div>
        
        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1<?php echo isset($_GET['department']) ? '&department='.$_GET['department'] : ''; ?><?php echo isset($_GET['course']) ? '&course='.$_GET['course'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['time_filter']) ? '&time_filter='.$_GET['time_filter'] : ''; ?><?php echo isset($_GET['document_filter']) ? '&document_filter='.$_GET['document_filter'] : ''; ?>">&laquo; First</a>
                <a href="?page=<?php echo $page-1; ?><?php echo isset($_GET['department']) ? '&department='.$_GET['department'] : ''; ?><?php echo isset($_GET['course']) ? '&course='.$_GET['course'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['time_filter']) ? '&time_filter='.$_GET['time_filter'] : ''; ?><?php echo isset($_GET['document_filter']) ? '&document_filter='.$_GET['document_filter'] : ''; ?>">&laquo;</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo isset($_GET['department']) ? '&department='.$_GET['department'] : ''; ?><?php echo isset($_GET['course']) ? '&course='.$_GET['course'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['time_filter']) ? '&time_filter='.$_GET['time_filter'] : ''; ?><?php echo isset($_GET['document_filter']) ? '&document_filter='.$_GET['document_filter'] : ''; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo isset($_GET['department']) ? '&department='.$_GET['department'] : ''; ?><?php echo isset($_GET['course']) ? '&course='.$_GET['course'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['time_filter']) ? '&time_filter='.$_GET['time_filter'] : ''; ?><?php echo isset($_GET['document_filter']) ? '&document_filter='.$_GET['document_filter'] : ''; ?>">&raquo;</a>
                <a href="?page=<?php echo $totalPages; ?><?php echo isset($_GET['department']) ? '&department='.$_GET['department'] : ''; ?><?php echo isset($_GET['course']) ? '&course='.$_GET['course'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['time_filter']) ? '&time_filter='.$_GET['time_filter'] : ''; ?><?php echo isset($_GET['document_filter']) ? '&document_filter='.$_GET['document_filter'] : ''; ?>">Last &raquo;</a>
            <?php endif; ?>
        </div>
    </div>

   <script>
    $(document).ready(function() {
        var coursesByDept = <?php echo json_encode($courses_by_dept); ?>;
        var selectedDepartment = "<?php echo $department_filter; ?>";
        var selectedCourse = "<?php echo $course_filter; ?>";

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

        $('#department').change(updateCourseOptions);

        // Initial course population
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
                            let violationText = 'Violations found:\n';
                            response.violations.forEach(v => {
                                violationText += `- ${v.description} (${v.date_reported})\n`;
                            });
                            throw new Error(response.message + '\n\n' + violationText);
                        }
                        throw new Error(response.message);
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
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