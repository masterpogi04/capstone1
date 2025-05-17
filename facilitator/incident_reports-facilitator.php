<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
} 

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

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

// Get departments and courses for the filter dropdowns
$dept_query = "SELECT * FROM departments ORDER BY name";
$dept_result = $connection->query($dept_query);

// Handle filters and search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';

// Validate sort parameters
$allowed_sort_columns = ['date_reported', 'place', 'status'];
$sort_column = isset($_GET['sort_column']) && in_array($_GET['sort_column'], $allowed_sort_columns) ? $_GET['sort_column'] : 'date_reported';
$sort_order = isset($_GET['sort_order']) && strtoupper($_GET['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Base query - much simpler with no joins until specific condition requires them
$base_query = "
    FROM incident_reports ir
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Add search and filter conditions
$conditions = [];
$params = [$user_id, $user_type];
$param_types = "is";

if ($search) {
    $conditions[] = "(ir.description LIKE ? OR ir.place LIKE ? OR EXISTS (
        SELECT 1 FROM student_violations sv WHERE sv.incident_report_id = ir.id AND sv.student_name LIKE ?
    ))";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $param_types .= "sss";
}

// Department filtering - direct approach
if ($department) {
    // First get all course names in this department
    $dept_courses_query = "SELECT name FROM courses WHERE department_id = ?";
    $dept_courses_stmt = $connection->prepare($dept_courses_query);
    $dept_courses_stmt->bind_param("i", $department);
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
        $conditions[] = "ir.id IN (
            SELECT DISTINCT incident_report_id 
            FROM student_violations 
            WHERE student_course IN ($course_list)
        )";
    }
}

// Course filtering - similarly direct approach
if ($course) {
    $course_query = "SELECT name FROM courses WHERE id = ?";
    $course_stmt = $connection->prepare($course_query);
    $course_stmt->bind_param("i", $course);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $course_row = $course_result->fetch_assoc();
    
    if ($course_row) {
        $course_name = $course_row['name'];
        $conditions[] = "ir.id IN (
            SELECT DISTINCT incident_report_id 
            FROM student_violations 
            WHERE student_course = ?
        )";
        $params[] = $course_name;
        $param_types .= "s";
    }
}

// Add conditions to base query
if (!empty($conditions)) {
    $base_query .= " AND " . implode(" AND ", $conditions);
}

// Count total records
$count_query = "SELECT COUNT(DISTINCT ir.id) as total " . $base_query;
if (!$count_stmt = $connection->prepare($count_query)) {
    die("Prepare failed: " . $connection->error);
}

$count_stmt->bind_param($param_types, ...$params);
if (!$count_stmt->execute()) {
    die("Execute failed: " . $count_stmt->error);
}

$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query with simplified subqueries for student names only
$query = "
    SELECT DISTINCT ir.*, 
    (
        SELECT GROUP_CONCAT(student_name SEPARATOR ',<br><br> ')
        FROM student_violations
        WHERE incident_report_id = ir.id
    ) as involved_students,
    (
        SELECT GROUP_CONCAT(witness_name SEPARATOR ',<br><br> ')
        FROM incident_witnesses
        WHERE incident_report_id = ir.id
    ) as witnesses
    " . $base_query . "
    GROUP BY ir.id
    ORDER BY " . $sort_column . " " . $sort_order . "
    LIMIT ? OFFSET ?
";

if (!$stmt = $connection->prepare($query)) {
    die("Prepare failed: " . $connection->error);
}

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

if (!$stmt->bind_param($param_types, ...$params)) {
    die("Binding parameters failed: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilitator Incident Reports</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    
</head>
<style>
:root {
    --primary-color: #0d693e;
    --secondary-color: #004d4d;
    --accent-color: #F4A261;
    --hover-color: #094e2e;
    --text-color: #2c3e50;
}

body {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: var(--text-color);
    margin: 0;
    padding: 0;
}

.container {
    background-color: rgba(255, 255, 255, 0.98);
    border-radius: 15px;
    padding: 30px;
    margin: 50px auto;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

h2 {
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}

/* Form Styles */
.form-control {
    padding: 5px 15px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    width: 100%;
    transition: all 0.3s ease;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
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
        text-align: left;
    }

    .table th:nth-child(2), /* Date Reported */
    .table td:nth-child(2) {
        width: 12%;
        text-align: left;
    }

     /* Place, Date & Time */
     
    .table td:nth-child(3) {
        width: 30%;
        text-align: left;
        white-space: normal;
        min-width: 200px;
    }

    .table th:nth-child(3){
         width: 10%;
        padding:20px;
    }

   /* Description - making it wider */
   .table th:nth-child(4),
    .table td:nth-child(4) {
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



/* Column-specific widths */
td[data-label="Date Reported"] {
    width: 120px;
}

td[data-label="Place, Date & Time"] {
    width: 200px;
}

td[data-label="Description"] {
    width: 250px;
}

td[data-label="Students Involved"],
td[data-label="Witnesses"] {
    width: 150px;
}

td[data-label="Status"] {
    width: 100px;
    text-align: left;
}

td[data-label="Actions"] {
    width: 120px;
    text-align: center;
}

/* Back Button */
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
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}

/* Responsive Styles */
@media screen and (max-width: 992px) {
    .container {
        width: 95%;
        padding: 20px;
    }

    .col-md-4, .col-md-3, .col-md-2 {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 0 15px;
        margin-bottom: 10px;
    }

    .btn-primary {
        width: 100%;
    }
}

@media screen and (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 20px auto;
    }

    h2 {
        font-size: 1.5rem;
    }

    .table thead {
        display: none;
    }

    .table tr {
        display: block;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .table td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        min-height: 50px;
        line-height: 1.5;
        width: 100% !important;
    }

    .table td::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 14px;
        color: #444;
        padding-right: 15px;
        flex: 1;
        white-space: nowrap;
    }

    .table td:last-child {
        border-bottom: none;
    }

    td[data-label="Date Reported"] {
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
        font-weight: 500;
    }

    td[data-label="Status"] {
        background: #f8f9fa;
    }

    td[data-label="Actions"] {
        border-radius: 0 0 8px 8px;
        justify-content: flex-end;
    }

    .btn-primary.btn-sm {
        width: 100%;
        padding: 10px;
        text-align: center;
        border-radius: 6px;
    }
}

@media screen and (max-width: 576px) {
    .container {
        padding: 10px;
        margin: 10px;
    }

    h2 {
        font-size: 1.25rem;
    }

    .table td {
        padding: 10px 12px;
        font-size: 13px;
    }

    .modern-back-button {
        padding: 6px 12px;
        font-size: 0.85rem;
    }
}


/* Touch Device Optimizations */
@media (hover: none) {
    .form-control, 
    .btn-primary,
    .btn-primary.btn-sm {
        min-height: 44px;
    }

    .table td {
        padding: 12px 15px;
    }
}
 

</style>

<body>
    <div class="container mt-5">
        <a href="facilitator_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2>Submitted Incident Report</h2>
            <div class="col-md-4">
                <div class="search-container">
                    <form id="searchForm" class="m-0">
                        <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search..." 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    </form>
                </div>
            </div>
        </div>
        <!-- Filter Form -->
        <form class="mb-4" method="GET">
            <div class="row">
                <!-- Department Filter -->
                <div class="col-md-3">
                    <select name="department" class="form-control" id="department">
                        <option value="">All Departments</option>
                        <?php while ($dept = $dept_result->fetch_assoc()): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                    <?php echo ($department == $dept['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Course Filter -->
                <div class="col-md-3">
                    <select name="course" class="form-control" id="course">
                        <option value="">All Courses</option>
                        <?php
                        if ($department) {
                            $course_query = "SELECT * FROM courses WHERE department_id = ? ORDER BY name";
                            $course_stmt = $connection->prepare($course_query);
                            $course_stmt->bind_param("i", $department);
                            $course_stmt->execute();
                            $course_result = $course_stmt->get_result();
                            while ($course_row = $course_result->fetch_assoc()) {
                                $selected = ($course == $course_row['id']) ? 'selected' : '';
                                echo "<option value='{$course_row['id']}' {$selected}>" . 
                                     htmlspecialchars($course_row['name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="col-md-3">
                    <select name="sort_order" class="form-control">
                        <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="col-md-3">
                    <a href="?" class="btn btn-secondary">Reset Filters</a>
                </div>
            </div>
        </form>

        <!-- Table Section -->
        <div class="table-responsive table-striped">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Incident Place, <br>Date & Time</th>
                        <th>Description</th>
                        <th>Student/s Involved</th>
                        <th>Witness/es</th>
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
                        ?>
                    <tr>
                        <td data-label="Date Reported">
                            <?php echo $formatted_date; ?><br>
                            <?php echo $formatted_time; ?>
                        </td>
                        <td data-label="Place, Date & Time">
                            <?php echo $formatted_place; ?>
                        </td>
                        <td data-label="Description">
                            <?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?>
                        </td>
                        <td data-label="Students Involved">
                            <?php 
                            if (!empty($row['involved_students'])) {
                                $students = explode(',<br><br> ', $row['involved_students']);
                                $proper_students = array_map('toProperCase', $students);
                                echo implode(',<br><br> ', $proper_students);
                            }
                            ?>
                        </td>
                        <td data-label="Witnesses">
                            <?php 
                            if (!empty($row['witnesses'])) {
                                $witnesses = explode(',<br><br> ', $row['witnesses']);
                                $proper_witnesses = array_map('toProperCase', $witnesses);
                                echo implode(',<br><br> ', $proper_witnesses);
                            }
                            ?>
                        </td>
                        <td data-label="Status">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </td>
                        <td data-label="Actions">
                            <a href="incident_reports_details-facilitator.php?id=<?php echo $row['id']; ?>" 
                               class="btn btn-primary btn-sm-2">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&sort=<?php echo $sort_column; ?>&order=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>&department=<?php echo $department; ?>&course=<?php echo $course; ?>">Last</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script>
    $(document).ready(function() {
    // Function to submit form
    function submitForm() {
        $('form').submit();
    }

    // Submit form when any select or input changes
    $('#department, #course, select[name="sort_column"], select[name="sort_order"], input[name="search"]').on('change', function() {
        submitForm();
    });

// Real-time search with client-side filtering
    $("#searchInput").on('input', function() {
        var searchText = $(this).val().toLowerCase();
        
        $("table tbody tr").each(function() {
            var searchData = $(this).text().toLowerCase();
            $(this).toggle(searchData.includes(searchText));
        });
    });

    // Original department change handler for updating courses
    $('#department').change(function() {
        var departmentId = $(this).val();
        var courseSelect = $('#course');
        
        // Clear current courses
        courseSelect.html('<option value="">All Courses</option>');
        
        if (departmentId) {
            // Fetch courses for selected department using AJAX
            $.ajax({
                url: 'get_courses.php',
                method: 'GET',
                data: { department_id: departmentId },
                success: function(response) {
                    courseSelect.append(response);
                    submitForm(); // Submit form after courses are loaded
                }
            });
        }
    });
});
    </script>
</body>
</html>