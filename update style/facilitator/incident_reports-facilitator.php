<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

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

// Base query with joins
$base_query = "
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN tbl_student ts ON sv.student_id = ts.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student tws ON iw.witness_id = tws.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Add search and filter conditions
$conditions = [];
$params = [$user_id, $user_type];
$param_types = "is";

if ($search) {
    $conditions[] = "(ir.description LIKE ? OR ts.first_name LIKE ? OR ts.last_name LIKE ? OR ir.place LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= "ssss";
}


// Modified department and course filtering
if ($department || $course) {
    $base_query .= " AND EXISTS (
        SELECT 1 
        FROM tbl_student s 
        JOIN sections sec ON s.section_id = sec.id
        JOIN courses c ON sec.course_id = c.id
        WHERE s.student_id = ts.student_id";
    
    if ($department) {
        $base_query .= " AND c.department_id = ?";
        $params[] = $department;
        $param_types .= "i";
    }
    
    if ($course) {
        $base_query .= " AND c.id = ?";
        $params[] = $course;
        $param_types .= "i";
    }
    
    $base_query .= ")";
}

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

// Main query
$query = "
    SELECT DISTINCT ir.*, 
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN ts.student_id IS NOT NULL THEN 
                CONCAT(
                    CONCAT(UPPER(SUBSTRING(ts.first_name, 1, 1)), LOWER(SUBSTRING(ts.first_name, 2))), 
                    ' ', 
                    CASE 
                        WHEN ts.middle_name IS NOT NULL AND ts.middle_name != '' 
                        THEN CONCAT(UPPER(SUBSTRING(ts.middle_name, 1, 1)), '. ') 
                        ELSE ' '
                    END,
                    CONCAT(UPPER(SUBSTRING(ts.last_name, 1, 1)), LOWER(SUBSTRING(ts.last_name, 2)))
                )
            WHEN sv.student_name IS NOT NULL THEN CONCAT(sv.student_name, ' [Inactive]')
            ELSE sv.student_id
        END
    SEPARATOR ', ') as involved_students,
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN iw.witness_type = 'student' THEN
                CASE 
                    WHEN tws.student_id IS NOT NULL THEN 
                        CONCAT(
                            CONCAT(UPPER(SUBSTRING(tws.first_name, 1, 1)), LOWER(SUBSTRING(tws.first_name, 2))), 
                            ' ',
                            CASE 
                                WHEN tws.middle_name IS NOT NULL AND tws.middle_name != '' 
                                THEN CONCAT(UPPER(SUBSTRING(tws.middle_name, 1, 1)), '. ') 
                                ELSE ' '
                            END,
                            CONCAT(UPPER(SUBSTRING(tws.last_name, 1, 1)), LOWER(SUBSTRING(tws.last_name, 2)))
                        )
                    ELSE CONCAT(iw.witness_student_name, ' [Inactive]')
                END
            ELSE iw.witness_name
        END
    SEPARATOR ', ') as witnesses
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
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
    padding-bottom: 15px;
}

/* Form Controls */
.form-control {
    padding: 5px 15px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    width: 100%;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 105, 62, 0.25);
}

/* Table Styles */
.table-responsive {
    margin: 20px 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
    border: none;
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

/* Row hover effect */
tbody tr {
    background-color: white;
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f8f9fa;
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
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

/* Filters Section */
.filters-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.filter-label {
    font-weight: 600;
    color: var(--text-color);
    margin-bottom: 8px;
}

/* Buttons */
.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: var(--hover-color);
    transform: translateY(-1px);
}

/* Pagination */
.pagination {
    justify-content: center;
    margin-top: 20px;
}

.page-link {
    color: var(--primary-color);
    border: 1px solid #dee2e6;
    padding: 8px 12px;
}

.page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Responsive Design */
@media screen and (max-width: 992px) {
    .container {
        width: 95%;
        padding: 20px;
    }

    .col-md-4, .col-md-3, .col-md-2 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 10px;
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

    .table td {
        display: block;
        text-align: right;
        padding: 10px;
        position: relative;
        padding-left: 50%;
    }

    .table td::before {
        content: attr(data-label);
        position: absolute;
        left: 0;
        width: 45%;
        padding-left: 15px;
        font-weight: bold;
        text-align: left;
    }
}
</style>
</head>
<body>

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
    <!-- Your existing styles here -->
</head>
<body>
    <div class="container mt-5">
        <a href="facilitator_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2>My Submitted Incident Reports</h2>
        </div>

        <!-- Filter Form -->
        <form class="mb-10" method="GET">
            <div class="row">
                <!-- Search Box -->
                <div class="col-md-12 mb-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search reports..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
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
                        <option value="DESC" <?php echo ($sort_order == 'DESC') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="ASC" <?php echo ($sort_order == 'ASC') ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="?" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <!-- Table Section -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Place</th>
                        <th>Description</th>
                        <th>Students Involved</th>
                        <th>Witnesses</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Date Reported">
                                <?php echo date('M d, Y g:i A', strtotime($row['date_reported'])); ?>
                            </td>
                            <td data-label="Place">
                                <?php echo htmlspecialchars($row['place']); ?>
                            </td>
                            <td data-label="Description">
                                <?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?>
                            </td>
                            <td data-label="Students Involved">
                                <?php echo htmlspecialchars($row['involved_students']); ?>
                            </td>
                            <td data-label="Witnesses">
                                <?php echo htmlspecialchars($row['witnesses']); ?>
                            </td>
                            <td data-label="Status">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </td>
                            <td data-label="Actions">
                                <a href="incident_reports_details-facilitator.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-primary btn-sm">
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

    // For search input, you might want to add a small delay
    var searchTimeout;
    $('input[name="search"]').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(submitForm, 500); // 500ms delay
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