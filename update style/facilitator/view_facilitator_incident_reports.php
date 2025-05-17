<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build conditions
$conditions = ["ir.status = 'pending'"];
$params = [];
$param_types = "";

// Handle date filter
switch ($date_filter) {
    case 'today':
        $conditions[] = "DATE(ir.date_reported) = CURDATE()";
        break;
    case 'last_week':
        $conditions[] = "ir.date_reported >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
        break;
    case 'last_month':
        $conditions[] = "ir.date_reported >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
}

// Handle department and course filters
if ($department) {
    $conditions[] = "c.department_id = ?";
    $params[] = $department;
    $param_types .= "i";
}

if ($course_filter !== 'all') {
    $conditions[] = "c.id = ?";
    $params[] = $course_filter;
    $param_types .= "i";
}

// Handle reporter filter
if ($reporter_filter !== 'all') {
    $conditions[] = "ir.reported_by_type = ?";
    $params[] = $reporter_filter;
    $param_types .= "s";
}

// Handle search
if (!empty($search)) {
    $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR ir.description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $param_types .= "sss";
}

// Combine conditions
$where_clause = implode(" AND ", $conditions);

// Count total records
$count_query = "SELECT COUNT(DISTINCT ir.id) as total 
                FROM incident_reports ir
                LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
                LEFT JOIN tbl_student s ON sv.student_id = s.student_id
                LEFT JOIN sections sec ON s.section_id = sec.id
                LEFT JOIN courses c ON sec.course_id = c.id
                WHERE $where_clause";

$count_stmt = $connection->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query
$query = "SELECT ir.*, 
            GROUP_CONCAT(DISTINCT CONCAT(
                CONCAT(UPPER(SUBSTRING(LOWER(s.first_name), 1, 1)), LOWER(SUBSTRING(s.first_name, 2))),
                ' ',
                CASE 
                    WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
                    THEN CONCAT(UPPER(SUBSTRING(s.middle_name, 1, 1)), '. ') 
                    ELSE ''
                END,
                CONCAT(UPPER(SUBSTRING(LOWER(s.last_name), 1, 1)), LOWER(SUBSTRING(s.last_name, 2)))
            )) AS student_names,
            GROUP_CONCAT(DISTINCT CONCAT(c.name, ' - ', sec.year_level, ' - Section ', sec.section_no)) AS course_info,
            ir.reported_by,
            ir.reported_by_type
          FROM incident_reports ir
          LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
          LEFT JOIN tbl_student s ON sv.student_id = s.student_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
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
  /* Variables */
:root {
    --primary-color: #0d693e;
    --secondary-color: #004d4d;
    --accent-color: #2EDAA8;
    --text-color: #2c3e50;
    --border-radius: 12px;
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.1);
    --transition: all 0.2s ease;
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
    border-radius: var(--border-radius);
    padding: 30px;
    margin: 20px auto;
    box-shadow: var(--shadow-md);
}

/* Header Styles */
h2 {
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--primary-color);
}

/* Back Button */
.modern-back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: var(--accent-color);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
    margin-bottom: 25px;
    box-shadow: var(--shadow-sm);
}

.modern-back-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(46, 218, 168, 0.2);
    color: white;
    text-decoration: none;
}

/* Filter Section */
.filter-container {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: var(--border-radius);
    margin-bottom: 30px;
    box-shadow: var(--shadow-sm);
}

.filter-container label {
    font-weight: 500;
    color: var(--text-color);
    margin-bottom: 8px;
    display: block;
}

.filter-container .form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 8px 12px;
    transition: var(--transition);
}

.filter-container .form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 105, 62, 0.25);
}

/* Table Styles */
.table-responsive {
    margin: 20px 0;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.table {
    margin-bottom: 0;
    width: 100%;
    background-color: white;
}

.table thead th {
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

.table td {
    padding: 15px;
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
    font-size: 14px;
    text-align: center;
}

/* Striped rows */
.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Column Widths */
.table td:nth-child(1) { width: 15%; } /* Date */
.table td:nth-child(2) { width: 20%; } /* Students */
.table td:nth-child(3) { width: 20%; } /* Course */
.table td:nth-child(4) { 
    width: 25%; 
    text-align: left;
    white-space: normal;
} /* Description */
.table td:nth-child(5) { width: 10%; } /* Reported By */
.table td:nth-child(6) { width: 10%; } /* Status */

/* Action Buttons */
.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    transition: var(--transition);
}

.btn-primary:hover {
    background-color: #0a5432;
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

/* Pagination */
.pagination {
    margin-top: 30px;
    justify-content: center;
}

.page-link {
    color: var(--primary-color);
    border: 1px solid #dee2e6;
    padding: 8px 16px;
    transition: var(--transition);
}

.page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.page-link:hover {
    color: var(--primary-color);
    background-color: #e9ecef;
    border-color: #dee2e6;
}

/* Responsive Adjustments */
@media (max-width: 1200px) {
    .container {
        padding: 20px;
        margin: 10px;
    }
    
    .table td:nth-child(4) {
        min-width: 200px;
    }
}

@media (max-width: 768px) {
    body {
        padding: 10px;
    }
    
    .container {
        padding: 15px;
    }
    
    h2 {
        font-size: 1.5rem;
    }
    
    .filter-container .row > div {
        margin-bottom: 15px;
    }
    
    .table thead {
        display: none;
    }
    
    .table, .table tbody, .table tr, .table td {
        display: block;
        width: 100%;
    }
    
    .table tr {
        margin-bottom: 15px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    
    .table td {
        text-align: left;
        padding: 10px;
        position: relative;
        padding-left: 50%;
    }
    
    .table td:before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        width: 45%;
        font-weight: 600;
    }
}

</style>
</head>
<body>
    <div class="container mt-5">
    <a href="guidanceservice.html" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>
        <h2>Pending Incident Reports</h2>

<div class="filter-container">
    <form action="" method="GET" class="mb-3">
        <div class="row align-items-end">
            <!-- Search Bar - First Item -->
            <div class="col-md-3">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
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


        <div class="row mt-2">
            <div class="col-md-12">
                <a href="?" class="btn btn-secondary">Reset Filters</a>
            </div>
        </div>
    </form>
</div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date Reported</th>
                    <th>Students Involved</th>
                    <th>Course - Year - Section</th>
                    <th>Description</th>
                    <th>Reported By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['date_reported'])); ?></td>
                        <td><?php echo htmlspecialchars($row['student_names']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_info']); ?></td>
                        <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                        <td>
                            <?php 
                            echo htmlspecialchars($row['reported_by']) . 
                                 ' (' . ucfirst(htmlspecialchars($row['reported_by_type'])) . ')'; 
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <a href="view_report_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&date_filter=<?php echo $date_filter; ?>&status_filter=<?php echo $status_filter; ?>&course_filter=<?php echo $course_filter; ?>&reporter_filter=<?php echo $reporter_filter; ?>&search=<?php echo urlencode($search); ?>" aria-label="First">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_filter=<?php echo $date_filter; ?>&status_filter=<?php echo $status_filter; ?>&course_filter=<?php echo $course_filter; ?>&reporter_filter=<?php echo $reporter_filter; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
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
                        <a class="page-link" href="?page=<?php echo $i; ?>&date_filter=<?php echo $date_filter; ?>&status_filter=<?php echo $status_filter; ?>&course_filter=<?php echo $course_filter; ?>&reporter_filter=<?php echo $reporter_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&date_filter=<?php echo $date_filter; ?>&status_filter=<?php echo $status_filter; ?>&course_filter=<?php echo $course_filter; ?>&reporter_filter=<?php echo $reporter_filter; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&date_filter=<?php echo $date_filter; ?>&status_filter=<?php echo $status_filter; ?>&course_filter=<?php echo $course_filter; ?>&reporter_filter=<?php echo $reporter_filter; ?>&search=<?php echo urlencode($search); ?>" aria-label="Last">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

  <script>
$(document).ready(function() {
    // Function to submit form
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