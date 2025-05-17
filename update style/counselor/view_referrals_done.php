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
                    r.first_name, ' ',
                    COALESCE(r.middle_name, ''), ' ',
                    r.last_name,
                    ' - ',
                    r.course_year
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
        body {
            background: linear-gradient(to right, #0d693e, #004d4d);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        
        .header {
            background-color:rgb(248, 246, 244);
            padding: 10px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            position: absolute;
            right: 0;
            top: 0;
            width: 100%;
            color: #1b651b;;
            z-index: 1000;
        }
        
        
        .content-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 60px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-top: 20px;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .back-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #5a6268;
            color: white;
        }
        
        .reason-cell {
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
        }
        
        .filter-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-container input {
            padding-right: 30px;
        }
        
        .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination .page-item .page-link {
            color: #0d693e;
            background-color: #fff;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d693e;
            border-color: #0d693e;
            color: #fff;
        }

        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }

        .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
            background-color: #0d693e;
            color: #fff;
            border-color: #0d693e;
        }
    </style>
</head>
<body>
    <div class="header">COMPLETED STUDENT REFERRALS</div>
    
    <div class="container content-container">
        <div class="row mb-3">
            <div class="col">
                <a href="counselor_homepage.php" class="btn btn-secondary">Back</a>
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
                        <th>Course/Year</th>
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
                                    <?php foreach($students as $student): ?>
                                        <div><?php echo htmlspecialchars($student); ?></div>
                                    <?php endforeach; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['course_name'] ?? 'N/A'); ?></td>
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
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=1&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">First</a>
                    </li>
                    
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">&laquo;</a>
                    </li>

                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">&raquo;</a>
                    </li>

                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>">Last</a>
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