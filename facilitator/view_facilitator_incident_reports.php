<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
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

$facilitator_id = $_SESSION['user_id'];

// Get departments for the filter dropdown
$dept_query = "SELECT * FROM departments ORDER BY name";
$dept_result = $connection->query($dept_query);

// Initialize filters
$department = isset($_GET['department']) ? $_GET['department'] : '';
$course_filter = $_GET['course_filter'] ?? 'all';
$date_filter = $_GET['date_filter'] ?? 'all';
$reporter_filter = $_GET['reporter_filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Build conditions
$conditions = ["ir.status = 'pending'"];  // Start with the base condition
$params = [];
$param_types = "";

// Handle date filter
switch ($date_filter) {
    case 'today':
        $conditions[] = "DATE(ir.date_reported) = CURDATE()";
        break;
    case 'last_week':
        // Calculate previous Monday and Sunday
        $currentDayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)
        
        // Calculate days to subtract to get to last Monday
        // If today is Monday (1), go back 7 days, otherwise go back to the start of the week plus 7 days
        $daysToLastMonday = $currentDayOfWeek == 1 ? 7 : $currentDayOfWeek + 6;
        
        // Calculate days to subtract to get to last Sunday
        $daysToLastSunday = $currentDayOfWeek == 7 ? 7 : $currentDayOfWeek;
        
        $lastMonday = date('Y-m-d', strtotime("-$daysToLastMonday days"));
        $lastSunday = date('Y-m-d', strtotime("-$daysToLastSunday days"));
        
        $conditions[] = "DATE(ir.date_reported) BETWEEN '$lastMonday' AND '$lastSunday'";
        break;
    case 'last_month':
        // Gets the previous month's data only
        $conditions[] = "YEAR(ir.date_reported) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) 
                        AND MONTH(ir.date_reported) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)";
        break;
}

// Handle department filter
if ($department) {
    $conditions[] = "EXISTS (
        SELECT 1 FROM courses c 
        WHERE c.department_id = ? 
        AND sv.student_course LIKE CONCAT('%', c.name, '%')
    )";
    $params[] = $department;
    $param_types .= "i";
}

// Handle course filter
if ($course_filter !== 'all') {
    $conditions[] = "EXISTS (
        SELECT 1 FROM courses c 
        WHERE c.id = ? 
        AND sv.student_course LIKE CONCAT('%', c.name, '%')
    )";
    $params[] = $course_filter;
    $param_types .= "i";
}

// Handle reporter filter
if ($reporter_filter !== 'all') {
    $conditions[] = "ir.reported_by_type = ?";
    $params[] = $reporter_filter;
    $param_types .= "s";
}

// Combine conditions
$where_clause = implode(" AND ", $conditions);

// Count total records
$count_query = "SELECT COUNT(DISTINCT ir.id) as total 
                FROM incident_reports ir
                LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
                WHERE $where_clause";

$count_stmt = $connection->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Calculate record range for display
$start_record = ($page - 1) * $records_per_page + 1;
$end_record = min($start_record + $records_per_page - 1, $total_records);

// Adjust when no records
if ($total_records == 0) {
    $start_record = 0;
}

// Force at least 1 page even if no records
if ($total_pages < 1) {
    $total_pages = 1;
}

// Main query
$query = "SELECT ir.*, 
    GROUP_CONCAT(DISTINCT sv.student_name SEPARATOR ',<br><br>') AS student_names,
    GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ',<br><br>') AS witness_list,
    ir.reported_by,
    ir.reported_by_type
FROM incident_reports ir
LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id 
WHERE $where_clause
GROUP BY ir.id
ORDER BY ir.date_reported DESC
LIMIT ? OFFSET ?";

// Add pagination parameters
$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $connection->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Incident Reports - Facilitator View</title>
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
         
/* Base Styles */
body {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: var(--text-color);
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 1400px;
    background-color: rgba(255, 255, 255, 0.98);
    border-radius: 15px;
    padding: 30px;
    margin: 20px auto;
    box-shadow: var(--shadow-md);
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

/* Status Badges */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: 500;
}

.status-pending { background-color: #ffd700; color: #000; }
.status-processing { background-color: #87ceeb; color: #000; }
.status-meeting { background-color: #98fb98; color: #000; }
.status-rejected { background-color: #ff6b6b; color: #fff; }

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
}

.page-link {
    color: #009E60;
    border: 1px solid #dee2e6;
}

.page-item.active .page-link {
    background-color: #009E60;
    border-color: #009E60;
}

.page-link:hover {
    color: #006E42;
    background-color: #e9ecef;
}


h1 {
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 5px 0 30px;
    padding-bottom: 15px;
    border-bottom: 3px solid var(--primary-dark);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    border-bottom: 3px solid var(--primary-color);
    text-align: center;
    letter-spacing: 0.5px;
    padding-top: 30px;
}


* Search and Filter Form Styles */
.mb-4 {
    margin-bottom: 1.5rem !important;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
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
    border: 0.1px solid #e0e0e0;
    font-size: 14px;
    text-align: center;
    background-color: transparent; /* Changed from white to transparent */
}

td:last-child {
    
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



.table th,
    .table td {
        padding: 12px 15px;
        vertical-align: middle;
        font-size: 14px;
        text-align: center;
    }

    /* Set specific widths for each column */
    .table th:nth-child(1), /* Student Name */
    .table td:nth-child(1) {
        width: 15%;
        padding:20px;
    }

    .table th:nth-child(2), /* Date Reported */
    .table td:nth-child(2) {
        width: 12%;
        padding:20px;
    }

    .table th:nth-child(3), /* Place, Date & Time */
    .table td:nth-child(3) {
        width: 20%;
        padding:20px;
    }

   /* Description - making it wider */
    .table td:nth-child(4) {
        width: 25%;
        text-align: left;
        white-space: normal;
        min-width: 250px;
    }

    .table th:nth-child(4){
         width: 10%;
        padding:20px;
    }

    .table th:nth-child(5), /* Involvement */
    .table td:nth-child(5) {
        width: 10%;
        padding:20px;
    }

    .table th:nth-child(6), /* Status */
    .table td:nth-child(6) {
        width: 10%;
        padding:20px;
    }

    .table th:nth-child(7), /* Action */
    .table td:nth-child(7) {
        width: 8%;
        padding:20px;
    }


/* Actions cell specific styling */
.actions-cell {
    display: flex;
    justify-content: center;
    gap: 8px;
}

/* Ensure buttons are stacked on smaller screens */
@media (max-width: 768px) {
  .btn-warning {
    width: 100%;  /* Stacks buttons on top of each other */
    margin-bottom: 10px; /* Adds space between buttons */
  }
  
  /* Align the buttons properly */
  .btn-warning {
    text-align: center; /* Centers text in the button */
  }

  /* Moves the Active Reports button below the Home button */
  .btn-warning {
    order: 2; /* Places it below Home button in mobile view */
  }
}

.nav-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 600;
            padding: 12px 20px;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            background-color: #f8f9fa;
            color: #009E60;
        }

        .nav-tabs .nav-link.active {
            color: #009E60;
            background-color: white;
            border-bottom: 3px solid #009E60;
            font-weight: 700;
        }
</style>
</head>
<body>
    <div class="container mt-5">

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
  <a href="guidanceservice.html" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
  </a>
</div>


        <h1>Incident Reports</h1>
 
<div class="filter-container">
    <form action="" method="GET" class="mb-3">
        <div class="row align-items-end">
            <!-- Search Bar - First Item -->
            <div class="col-md-3">
                <label for="search">Search:</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <!-- Sort Filters -->
            <div class="col-md-2">
                <label for="date_filter">Date:</label>
                <select name="date_filter" id="date_filter" class="form-control">
                    <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Dates</option>
                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="last_week" <?php echo $date_filter === 'last_week' ? 'selected' : ''; ?>>Last Week</option>
                    <option value="last_month" <?php echo $date_filter === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="department">Department:</label>
                <select name="department" id="department" class="form-control">
                    <option value="">All Departments</option>
                    <?php while ($dept = $dept_result->fetch_assoc()): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo ($department == $dept['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="course_filter">Course:</label>
                <select name="course_filter" id="course_filter" class="form-control">
                    <option value="all">All Courses</option>
                    <?php
                    if ($department) {
                        $course_query = "SELECT * FROM courses WHERE department_id = ? ORDER BY name";
                        $course_stmt = $connection->prepare($course_query);
                        $course_stmt->bind_param("i", $department);
                        $course_stmt->execute();
                        $course_result = $course_stmt->get_result();
                        while ($course_row = $course_result->fetch_assoc()) {
                            $selected = ($course_filter == $course_row['id']) ? 'selected' : '';
                            echo "<option value='{$course_row['id']}' {$selected}>" . 
                                 htmlspecialchars($course_row['name']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="reporter_filter">Reported By:</label>
                <select name="reporter_filter" id="reporter_filter" class="form-control">
                    <option value="all" <?php echo $reporter_filter === 'all' ? 'selected' : ''; ?>>All Reporters</option>
                    <option value="facilitator" <?php echo $reporter_filter === 'facilitator' ? 'selected' : ''; ?>>Facilitator</option>
                    <option value="adviser" <?php echo $reporter_filter === 'adviser' ? 'selected' : ''; ?>>Adviser</option>
                    <option value="instructor" <?php echo $reporter_filter === 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                    <option value="student" <?php echo $reporter_filter === 'student' ? 'selected' : ''; ?>>Student</option>
                    <option value="guard" <?php echo $reporter_filter === 'guard' ? 'selected' : ''; ?>>Guard</option>    
                </select>
            </div>


        <div class="row-md-12 pl-3 pt-3">
            <div class="col-mt-12">
                <a href="?" class="btn btn-secondary">Reset Filters</a>
            </div>
        </div>
    </form>
</div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date Reported</th>
                    <th>Student/s Involved</th>
                    <th>Witness/es</th>
                    <th>Description</th>
                    <th>Reported By</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows == 0): ?>
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="alert alert-info" role="alert">
                            No incident reports found.
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        // Format the date reported
                        $date_reported = new DateTime($row['date_reported']);
                        $formatted_date = $date_reported->format('F j, Y');
                        $formatted_time = $date_reported->format('g:i A');
                        
                        // Format the place and incident date/time
                        $place_parts = explode(' - ', $row['place']);
                        $formatted_place = $place_parts[0];
                        if (isset($place_parts[1])) {
                            $datetime_parts = explode(' at ', $place_parts[1]);
                            $formatted_place .= ',<br>' . $datetime_parts[0];
                            if (isset($datetime_parts[1])) {
                                $formatted_place .= ',<br>at ' . $datetime_parts[1];
                            }
                        }
                        
                        // Process student names into proper case
                        $student_names = "";
                        if (!empty($row['student_names'])) {
                            $students_array = explode(',<br><br>', $row['student_names']);
                            foreach ($students_array as $index => $student) {
                                // Split into name and details
                                $name_parts = explode(' (', $student, 2);
                                if (count($name_parts) > 1) {
                                    $student_name = toProperCase($name_parts[0]);
                                    $student_details = '(' . $name_parts[1];
                                    $students_array[$index] = $student_name . ' ' . $student_details;
                                } else {
                                    $students_array[$index] = toProperCase($student);
                                }
                            }
                            $student_names = implode(',<br><br>', $students_array);
                        }
                        
                        // Process witness names into proper case
                        $witness_list = "No Witness";  // Default to "No Witness"
                        if (!empty($row['witness_list'])) {
                            $witnesses_array = explode(',<br><br>', $row['witness_list']);
                            foreach ($witnesses_array as $index => $witness) {
                                // Split into name and details
                                $name_parts = explode(' (', $witness, 2);
                                if (count($name_parts) > 1) {
                                    $witness_name = toProperCase($name_parts[0]);
                                    $witness_details = '(' . $name_parts[1];
                                    $witnesses_array[$index] = $witness_name . ' ' . $witness_details;
                                } else {
                                    $witnesses_array[$index] = toProperCase($witness);
                                }
                            }
                            $witness_list = implode(',<br><br>', $witnesses_array);
                        }
                    ?>
                    <tr>
                        <td data-label="Date Reported"><?php echo $formatted_date; ?><br>
                        <?php echo $formatted_time; ?></td>
                        <td data-label="Student/s Involved"><?php echo $student_names; ?></td>
                        <td data-label="Witness/es"><?php echo $witness_list; ?></td>
                        <td data-label="Description"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                        <td data-label="Reported By">
                            <?php 
                            echo toProperCase(htmlspecialchars($row['reported_by'])) . 
                                 ' (' . ucfirst(htmlspecialchars($row['reported_by_type'])) . ')'; 
                            ?>
                        </td>
                        <td data-label="Status"><?php echo htmlspecialchars($row['status']); ?></td>
                        <td data-label="Action">
                            <a href="view_report_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm-2"><i class="fas fa-eye p-1"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
        </table>
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
                    <a class="page-link" href="?page=<?php echo $i; ?>&department=<?php echo urlencode($department); ?>&date_filter=<?php echo urlencode($date_filter); ?>&course_filter=<?php echo urlencode($course_filter); ?>&reporter_filter=<?php echo urlencode($reporter_filter); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <!-- Next page (») -->
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&department=<?php echo urlencode($department); ?>&date_filter=<?php echo urlencode($date_filter); ?>&course_filter=<?php echo urlencode($course_filter); ?>&reporter_filter=<?php echo urlencode($reporter_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
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
                    <a class="page-link" href="?page=<?php echo $total_pages; ?>&department=<?php echo urlencode($department); ?>&date_filter=<?php echo urlencode($date_filter); ?>&course_filter=<?php echo urlencode($course_filter); ?>&reporter_filter=<?php echo urlencode($reporter_filter); ?>&search=<?php echo urlencode($search); ?>" aria-label="Last">
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

<script>
$(document).ready(function() {
    // Function to perform live search
    function performSearch(searchTerm) {
        searchTerm = searchTerm.toLowerCase();
        
        // Get all table rows except the header
        const tableRows = $('table tbody tr');
        let visibleRowCount = 0;
        
        tableRows.each(function() {
            const row = $(this);
            // Skip the "no results" message row if it exists
            if (row.find('td[colspan="7"]').length > 0) {
                row.remove();
                return;
            }
            
            const text = row.text().toLowerCase();
            
            // Check if any cell in this row contains the search term
            if (text.includes(searchTerm)) {
                row.show();
                visibleRowCount++;
            } else {
                row.hide();
            }
        });
        
        // If no rows are visible after search, show "no records found" message
        if (visibleRowCount === 0) {
            $('table tbody').append(`
                <tr id="no-results-row">
                    <td colspan="7" class="text-center">
                        <div class="alert alert-info" role="alert">
                            No incident reports found.
                        </div>
                    </td>
                </tr>
            `);
            // Update pagination info
            $('.pagination-info').text('No records found for your search');
            // Hide pagination if no results
            $('.pagination').hide();
        } else {
            // Remove any existing "no records" message
            $('#no-results-row').remove();
            // Update pagination info for client-side filtering
            $('.pagination-info').text(`Showing 1 - ${visibleRowCount} out of ${visibleRowCount} filtered records`);
            // Show pagination
            $('.pagination').show();
        }
    }

    // Handle search input
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val();
        performSearch(searchTerm);
    });

    // Restore full pagination info when search is cleared
    $('#searchInput').on('keyup', function() {
        if ($(this).val().trim() === '') {
            // Reset pagination info to server-side values
            const start = <?php echo $start_record; ?>;
            const end = <?php echo $end_record; ?>;
            const total = <?php echo $total_records; ?>;
            
            if (total > 0) {
                $('.pagination-info').text(`Showing ${start} - ${end} out of ${total} records`);
            } else {
                $('.pagination-info').text('No records found');
            }
            // Show pagination
            $('.pagination').show();
        }
    });

    // Function to submit form for other filters
    function submitForm() {
        $('form').submit();
    }

    // Submit form when any select changes
    $('#date_filter, #course_filter, #reporter_filter').on('change', function() {
        submitForm();
    });

    // Handle department change and course loading
    $('#department').change(function() {
        var departmentId = $(this).val();
        var courseSelect = $('#course_filter');
        
        // Clear current courses
        courseSelect.html('<option value="all">All Courses</option>');
        
        if (departmentId) {
            // Fetch courses for selected department
            $.ajax({
                url: 'get_courses.php',
                method: 'GET',
                data: { department_id: departmentId },
                success: function(response) {
                    courseSelect.append(response);
                    submitForm(); // Submit form after courses are loaded
                }
            });
        } else {
            submitForm();
        }
    });

    // For search input, add a small delay
    var searchTimeout;
    $('input[name="search"]').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(submitForm, 500); // 500ms delay
    });
});
</script>
</body>
</html>