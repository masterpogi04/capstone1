<?php
session_start();
include '../db.php';
 
// Check if user is logged in and is a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: ../login.php");
    exit();
}

// Get filter parameters
$department = isset($_GET['department']) ? $_GET['department'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Base query for total records - Show only completed referrals
$count_query = "SELECT COUNT(DISTINCT CASE 
                    WHEN r.incident_report_id IS NOT NULL THEN r.incident_report_id 
                    ELSE CONCAT('single_', r.id) 
                END) as total 
                FROM referrals r
                LEFT JOIN tbl_student s ON r.student_id = s.student_id
                LEFT JOIN sections sec ON s.section_id = sec.id
                LEFT JOIN courses c ON sec.course_id = c.id
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE r.status = 'Done'";

// Apply filters to count query
$where_conditions = ["r.status = 'Done'"];
$params = [];
$param_types = "";

if ($department) {
    $where_conditions[] = "d.id = ?";
    $params[] = $department;
    $param_types .= "i";
}

if ($course) {
    $where_conditions[] = "c.id = ?";
    $params[] = $course;
    $param_types .= "i";
}

if ($search) {
    $where_conditions[] = "(r.first_name LIKE ? OR r.last_name LIKE ? OR r.faculty_name LIKE ? OR r.reason_for_referral LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= "ssss";
}

if (!empty($where_conditions)) {
    $count_query .= " AND " . implode(" AND ", $where_conditions);
}

$stmt = $connection->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query to fetch completed referrals
$query = "SELECT 
            r.*, 
            DATE_FORMAT(r.date, '%M %d, %Y') as formatted_date,
            d.name as department_name,
            c.name as course_name,
            CASE 
                WHEN r.reason_for_referral = 'Other concern' THEN CONCAT('Other concern: ', r.other_concerns)
                WHEN r.reason_for_referral LIKE 'Violation to school rules%' THEN CONCAT('Violation: ', COALESCE(r.violation_details, ''))
                ELSE r.reason_for_referral
            END as detailed_reason,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    CONCAT(UPPER(SUBSTRING(r.first_name, 1, 1)), LOWER(SUBSTRING(r.first_name, 2))), ' ',
                    IF(r.middle_name IS NOT NULL AND r.middle_name != '', CONCAT(UPPER(SUBSTRING(r.middle_name, 1, 1)), '. '), ''),
                    CONCAT(UPPER(SUBSTRING(r.last_name, 1, 1)), LOWER(SUBSTRING(r.last_name, 2))),
                    ' (',
                    r.course_year, ')'
                ) SEPARATOR '||'
            ) as student_info,
            MIN(r.id) as first_referral_id
          FROM referrals r
          LEFT JOIN tbl_student s ON r.student_id = s.student_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
          LEFT JOIN departments d ON c.department_id = d.id
          WHERE r.status = 'Done'";

if (!empty($where_conditions)) {
    $query .= " AND " . implode(" AND ", $where_conditions);
} 

// Group by incident_report_id or individual referral id
$query .= " GROUP BY CASE 
                WHEN r.incident_report_id IS NOT NULL THEN r.incident_report_id 
                ELSE r.id 
            END";

// Apply sorting
if ($sort == 'date_asc') {
    $query .= " ORDER BY r.date ASC";
} else {
    $query .= " ORDER BY r.date DESC";
}

$query .= " LIMIT ? OFFSET ?";

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

// Calculate pagination info for display
$start_record = min($offset + 1, $total_records);
$end_record = min($offset + $records_per_page, $total_records);
$pagination_info = "Showing $start_record - $end_record out of $total_records records";

// Get departments for filter
$dept_query = "SELECT * FROM departments ORDER BY name";
$departments = $connection->query($dept_query);

// Get courses if department is selected
$courses = [];
if ($department) {
    $course_query = "SELECT * FROM courses WHERE department_id = ? ORDER BY name";
    $stmt = $connection->prepare($course_query);
    $stmt->bind_param("i", $department);
    $stmt->execute();
    $courses = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Referrals - Counselor</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
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
            padding: 10px 16px;
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
            font-size: 2.5rem;
            text-align: center;
            margin: 5px 0 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-dark);
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
                width: 10%;
                padding:10px;
            }

            .table th:nth-child(2), /* Date Reported */
            .table td:nth-child(2) {
                width: %;
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
                min-width: 100px;
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



            /* Actions cell specific styling */
            .actions-cell {
                display: flex;
                justify-content: center;
                gap: 8px;
            }
            .pagination-info {
                text-align: center;
                margin-bottom: 15px;
                font-size: 0.95rem;
                color: #555;
                background-color: #f8f9fa;
                padding: 8px 15px;
                border-radius: 20px;
                display: inline-block;
            }

            .pagination-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-top: 20px;
            }


</style>
</head>
<body>
    
    <div class="container content-container">
        <div class="row mb-3">
            <div class="col">
                <a href="counselor_homepage.php" class="modern-back-button">  <i class="fas fa-arrow-left"></i> Back to homepage</a>
        <h1>Completed Student Referrals</h1>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <label>Department:</label>
                        <select name="department" class="form-control" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php while ($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label>Course:</label>
                        <select name="course" class="form-control" <?php echo !$department ? 'disabled' : ''; ?>>
                            <option value="">All Courses</option>
                            <?php if ($courses): while ($course_row = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $course_row['id']; ?>" <?php echo $course == $course_row['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course_row['name']); ?>
                                </option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label>Sort by:</label>
                        <select name="sort" class="form-control">
                            <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Descending</option>
                            <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label>Search:</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search...">
                    </div>
                </form>
            </div>
        </div>

        <!-- Referrals Table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student Name</th>
                        <th>Reason for Referral</th>
                        <th>Faculty Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="alert alert-info" style="margin-top: 20px;">
                                    <i class="fas fa-info-circle"></i> There are no completed referrals to display.
                                    <?php 
                                    // Add context to the no records message based on applied filters
                                    $context_message = "No referrals have been completed";
                                    if ($department) $context_message .= " in this department";
                                    if ($course) $context_message .= " for this course";
                                    if ($search) $context_message .= " matching your search";
                                    $context_message .= ".";
                                    echo htmlspecialchars($context_message);
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: 
                        while ($row = $result->fetch_assoc()): 
                            $students = explode('||', $row['student_info']);
                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['formatted_date']); ?></td>
                                <td>
                                    <?php 
                                    $formatted_students = array_map('htmlspecialchars', $students);
                                    echo implode('<br><br>', $formatted_students);
                                    ?>
                                </td>
                                <td class="reason-cell"><?php echo htmlspecialchars($row['detailed_reason']); ?></td>
                                <td><?php echo htmlspecialchars($row['faculty_name']); ?></td>
                                <td>
                                    <a href="generate_referral_pdf.php?id=<?php echo $row['id']; ?>" 
                                       target="_blank" 
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-file-pdf"></i> View PDF
                                    </a>
                                </td>
                            </tr>
                    <?php 
                        endwhile; 
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="pagination-info">
                <?php echo $pagination_info; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <!-- First Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=1&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">First</a>
                        </li>

                        <!-- Previous Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">&laquo;</a>
                        </li>

                        <!-- Page Numbers -->
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">&raquo;</a>
                        </li>

                        <!-- Last Page -->
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">Last</a>
                        </li>
                    </ul>
                </nav>
            <?php else: ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item active">
                            <a class="page-link" href="#">1</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        
        document.querySelector('select[name="department"]').addEventListener('change', function() {
            const courseSelect = document.querySelector('select[name="course"]');
            courseSelect.disabled = !this.value;
            if (!this.value) {
                courseSelect.value = '';
            }
            this.form.submit();
        });

        
        document.querySelectorAll('select[name="sort"], select[name="course"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>