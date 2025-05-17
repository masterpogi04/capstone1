<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

$adviser_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';

// Build base WHERE clause to get students from sections advised by this adviser
$where_clause = "WHERE sec.adviser_id = ?";

// Add filters
if ($status_filter) {
    $where_clause .= " AND r.status = ?";
}

if ($course_filter) {
    $where_clause .= " AND sec.course_name LIKE ?";
}

if ($year_filter) {
    $where_clause .= " AND sec.year_level = ?";
}

if ($search) {
    $where_clause .= " AND (
        s.first_name LIKE ? OR 
        s.middle_name LIKE ? OR 
        s.last_name LIKE ? OR 
        s.student_id LIKE ? OR
        r.reason_for_referral LIKE ?
    )";
}

// Count query to get total records
$count_query = "
    SELECT COUNT(*) as total_records 
    FROM referrals r
    JOIN tbl_student s ON r.student_id = s.student_id
    JOIN sections sec ON s.section_id = sec.id
    $where_clause
";

$count_stmt = $connection->prepare($count_query);
if ($count_stmt === false) {
    die("Prepare failed: " . $connection->error);
}

// Bind parameters for count query
$param_types = "i"; // i for adviser_id
$param_values = array($adviser_id);

if ($status_filter) {
    $param_types .= "s";
    $param_values[] = $status_filter;
}

if ($course_filter) {
    $param_types .= "s";
    $param_values[] = "%$course_filter%";
}

if ($year_filter) {
    $param_types .= "s";
    $param_values[] = $year_filter;
}

if ($search) {
    $param_types .= "sssss";
    $search_param = "%$search%";
    $param_values[] = $search_param;
    $param_values[] = $search_param;
    $param_values[] = $search_param;
    $param_values[] = $search_param;
    $param_values[] = $search_param;
}

// Dynamically bind parameters for count query
$count_stmt->bind_param($param_types, ...$param_values);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$row = $count_result->fetch_assoc();
$total_records = $row['total_records'];
$total_pages = ceil($total_records / $records_per_page);

// Main query to get referrals
$query = "
    SELECT r.id, r.date, r.reason_for_referral, r.status, r.faculty_name, 
           CONCAT(
               CONCAT(UPPER(SUBSTRING(LOWER(s.first_name), 1, 1)), LOWER(SUBSTRING(s.first_name, 2))), ' ',
               CASE 
                   WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
                   THEN CONCAT(UPPER(SUBSTRING(LOWER(s.middle_name), 1, 1)), '. ') 
                   ELSE '' 
               END,
               CONCAT(UPPER(SUBSTRING(LOWER(s.last_name), 1, 1)), LOWER(SUBSTRING(s.last_name, 2)))
           ) as student_name,
           s.student_id as student_id_number,
           CONCAT(sec.course_name, ' - ', sec.year_level) as course_year,
           sec.section_no
    FROM referrals r
    JOIN tbl_student s ON r.student_id = s.student_id
    JOIN sections sec ON s.section_id = sec.id
    $where_clause
    ORDER BY r.date " . ($sort_order === 'ASC' ? 'ASC' : 'DESC') . "
    LIMIT ? OFFSET ?
";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

// Bind parameters for main query (add limit and offset)
$param_types .= "ii";
$param_values[] = $records_per_page;
$param_values[] = $offset;

// Dynamically bind parameters for main query
$stmt->bind_param($param_types, ...$param_values);

$stmt->execute();
$result = $stmt->get_result();

// Get list of courses and year levels for filter dropdowns
$courses_query = "
    SELECT DISTINCT sec.course_name
    FROM sections sec
    WHERE sec.adviser_id = ?
    ORDER BY sec.course_name
";

$courses_stmt = $connection->prepare($courses_query);
$courses_stmt->bind_param("i", $adviser_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

$years_query = "
    SELECT DISTINCT sec.year_level
    FROM sections sec
    WHERE sec.adviser_id = ?
    ORDER BY FIELD(sec.year_level, 'First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Irregular')
";

$years_stmt = $connection->prepare($years_query);
$years_stmt->bind_param("i", $adviser_id);
$years_stmt->execute();
$years_result = $years_stmt->get_result();

// Calculate showing range for display
$showing_start = min($total_records, $offset + 1);
$showing_end = min($total_records, $offset + $records_per_page);
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Referrals - Adviser View</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
:root {
    --primary-color: #0d693e;
    --secondary-color: #004d4d;
    --accent-color: #F4A261;
    --hover-color: #094e2e;
    --text-color: #2c3e50;
    --btn-color: #0d693e;
    --btn-hover-color: #094e2e;
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

.form-control {
    padding: 5px 15px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    width: 100%;
    transition: all 0.3s ease;
}

.row {
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
}

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

td {
    padding: 12px 15px;
    vertical-align: middle;
    border: 0.1px solid #e0e0e0;
    font-size: 14px;
    text-align: center;
    background-color: transparent;
}

tbody tr:last-child td:first-child {
    border-bottom-left-radius: 10px;
}

tbody tr:last-child td:last-child {
    border-bottom-right-radius: 10px;
}

tbody tr {
    background-color: white;
    transition: background-color 0.2s ease;
}

.table th,
.table td {
    padding: 12px 15px;
    vertical-align: middle;
    font-size: 14px;
    text-align: center;
}

.actions-cell {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.no-records-container {
    text-align: center;
    padding: 40px 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
    border: 1px dashed #dee2e6;
}

.no-records-icon {
    font-size: 48px;
    color: #6c757d;
    margin-bottom: 15px;
}

.no-records-text {
    font-size: 18px;
    color: #495057;
    font-weight: 500;
}

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

.btn-primary {
    background-color: var(--btn-color) !important;
    border-color: var(--btn-color) !important;
    color: white;
}

.btn-primary:hover, 
.btn-primary:focus, 
.btn-primary:active {
    background-color: var(--btn-hover-color) !important;
    border-color: var(--btn-hover-color) !important;
}

.btn-primary.btn-sm {
    background-color: var(--btn-color) !important;
    border-color: var(--btn-color) !important;
}

.btn-primary.btn-sm:hover {
    background-color: var(--btn-hover-color) !important;
    border-color: var(--btn-hover-color) !important;
}

.btn-secondary {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    color: white;
}

.btn-secondary:hover, 
.btn-secondary:focus, 
.btn-secondary:active {
    background-color: #5a6268 !important;
    border-color: #5a6268 !important;
}

.page-item.active .page-link {
    background-color: var(--btn-color) !important;
    border-color: var(--btn-color) !important;
}

.page-link {
    color: var(--btn-color) !important;
}

.page-link:hover {
    color: var(--btn-hover-color) !important;
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
}

.status-pending {
    background-color: #ffe58f;
    color: #ad6800;
}

.status-done {
    background-color: #b7eb8f;
    color: #135200;
}

.filter-label {
    font-weight: 600;
    color: var(--primary-color);
    margin-bottom: 5px;
    display: block;
}

.search-container {
    position: relative;
}

.search-container .form-control {
    padding-left: 35px;
}

.search-icon {
    position: absolute;
    left: 10px;
    top: 10px;
    color: #6c757d;
}

.records-info {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
    text-align: right;
}

.pagination {
    margin-top: 20px;
}

.reset-btn-container {
    display: flex;
    justify-content: flex-end;
    margin-top: 30px;
}

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

    .btn-primary, .btn-secondary {
        width: 100%;
    }
    
    .reset-btn-container {
        margin-top: 15px;
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
        text-align: right;
    }

    .table td::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 14px;
        color: #444;
        padding-right: 15px;
        flex: 1;
        white-space: nowrap;
        text-align: left;
    }

    .table td:last-child {
        border-bottom: none;
    }

    td[data-label="Date"] {
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
    
    .no-records-container {
        padding: 30px 15px;
    }
    
    .no-records-icon {
        font-size: 36px;
    }
    
    .no-records-text {
        font-size: 16px;
    }
    
    .records-info {
        text-align: center;
        margin-top: 10px;
        margin-bottom: 10px;
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
    
    .row {
        margin-right: -5px;
        margin-left: -5px;
    }
    
    .col-md-2, .col-md-3 {
        padding: 0 5px;
        margin-bottom: 8px;
    }
    
    .form-control {
        font-size: 13px;
    }
}

@media (hover: none) {
    .form-control, 
    .btn-primary,
    .btn-primary.btn-sm,
    .btn-secondary {
        min-height: 44px;
    }

    .table td {
        padding: 12px 15px;
    }
}

.no-records-icon i {
    opacity: 0.8;
}

@media screen and (max-width: 576px) {
    .no-records-container {
        padding: 20px 10px;
    }
    
    .no-records-icon {
        font-size: 32px;
    }
    
    .no-records-text {
        font-size: 14px;
    }
}
</style>
</head>
<body>
<div class="container mt-5">
        <div class="d-flex justify-content-start align-items-center mb-4">
            <a href="adviser_homepage.php" class="modern-back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2>Student Referrals</h2>
            <div class="col-md-4">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                </div>
            </div>
        </div>
        
        <!-- Filter Form - Modified to auto-submit -->
        <form id="filterForm" class="mb-4" method="GET" action="">
            <div class="row">
                <div class="col-md-2">
                    <label class="filter-label">Status</label>
                    <select name="status" id="statusFilter" class="form-control auto-submit">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Done" <?php echo $status_filter === 'Done' ? 'selected' : ''; ?>>Done</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Course</label>
                    <select name="course" id="courseFilter" class="form-control auto-submit">
                        <option value="">All Courses</option>
                        <?php while ($course = $courses_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($course['course_name']); ?>" <?php echo $course_filter === $course['course_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Year Level</label>
                    <select name="year" id="yearFilter" class="form-control auto-submit">
                        <option value="">All Year Levels</option>
                        <?php while ($year = $years_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($year['year_level']); ?>" <?php echo $year_filter === $year['year_level'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($year['year_level']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Sort By</label>
                    <select name="sort" id="sortFilter" class="form-control auto-submit">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest</option>
                    </select>
                </div>
                <div class="reset-btn-container col-md-2">
                <a href="?" class="btn btn-secondary">Reset Filters</a>
            </div>
            </div>
            
            <!-- Reset Filters button -->
            
            
            <!-- Hidden search field for form submission -->
            <input type="hidden" name="search" id="searchFormField" value="<?php echo htmlspecialchars($search); ?>">
        </form>

       <!-- Table with responsive wrapper -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Course/Year/Section</th>
                        <th>Reason for Referral</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if ($result->num_rows == 0): ?>
                    <tr id="noRecordsRow">
                        <td colspan="7">
                            <div class="no-records-container">
                                <div class="no-records-icon">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="no-records-text">No referrals found for your students</div>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Date"><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                            <td data-label="Student ID"><?php echo htmlspecialchars($row['student_id_number']); ?></td>
                            <td data-label="Student Name"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td data-label="Course/Year/Section">
                                <?php echo htmlspecialchars($row['course_year']) . ' - ' . htmlspecialchars($row['section_no']); ?>
                            </td>
                            <td data-label="Reason"><?php echo htmlspecialchars($row['reason_for_referral']); ?></td>
                            <td data-label="Status">
                                <span class="status-badge <?php echo $row['status'] === 'Pending' ? 'status-pending' : 'status-done'; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td data-label="Actions">
                                <a href="view_referral_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Records Info -->
        <div class="records-info">
            <?php if ($total_records > 0): ?>
                Showing <?php echo $showing_start; ?> - <?php echo $showing_end; ?> of <?php echo $total_records; ?> records
            <?php else: ?>
                No records found
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&status=<?php echo $status_filter; ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>&sort=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>&sort=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>&sort=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>&sort=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&status=<?php echo $status_filter; ?>&course=<?php echo urlencode($course_filter); ?>&year=<?php echo urlencode($year_filter); ?>&sort=<?php echo $sort_order; ?>&search=<?php echo urlencode($search); ?>">Last</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

   <script>
$(document).ready(function() {
    // Handle auto-submit when dropdown values change
    $(".auto-submit").on("change", function() {
        $("#filterForm").submit();
    });
    
    // Handle real-time search without page reload
    $("#searchInput").on("input", function() {
        var searchText = $(this).val().toLowerCase();
        $("#searchFormField").val(searchText); // Update hidden field for any server-side needs
        
        var visibleRows = 0;
        
        // Remove any existing "no records" message first
        $("#noRecordsRow").remove();
        
        // Filter through each row (excluding the noRecordsRow)
        $("#tableBody tr:not(#noRecordsRow)").each(function() {
            var rowText = $(this).text().toLowerCase();
            var shouldShow = rowText.indexOf(searchText) > -1;
            
            if (shouldShow) {
                $(this).show();
                visibleRows++;
            } else {
                $(this).hide();
            }
        });
        
        // If no visible rows, add the "no records" message
        if (visibleRows === 0) {
            $("#tableBody").append(`
                <tr id="noRecordsRow">
                    <td colspan="7">
                        <div class="no-records-container">
                            <div class="no-records-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="no-records-text">No matching records found</div>
                        </div>
                    </td>
                </tr>
            `);
            
            // Update the records info text
            $(".records-info").text("No records found");
        } else {
            // Restore the original records info text if there are visible records
            // We can't restore the exact count since we're client-side filtering,
            // so just show the visible count
            $(".records-info").text("Showing " + visibleRows + " records");
        }
    });

    // Add touch device detection
    function isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints;
    }

    // Adjust for touch devices
    if (isTouchDevice()) {
        $('.btn').addClass('touch-device');
    }
});
    </script>
</body>
</html>