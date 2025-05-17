<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}
 
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get search, sort, and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Base query for counting total records
$count_query = "
    SELECT COUNT(DISTINCT ir.id) as total 
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s2 ON iw.witness_id = s2.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Base query for fetching records
$query = "
    SELECT ir.*, 
           GROUP_CONCAT(DISTINCT 
               CASE 
                   WHEN sv.student_id IS NOT NULL THEN 
                       CONCAT(
                           UPPER(SUBSTRING(s.first_name, 1, 1)), 
                           LOWER(SUBSTRING(s.first_name, 2)),
                           CASE 
                               WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
                               THEN CONCAT(' ', UPPER(SUBSTRING(s.middle_name, 1, 1)), '. ') 
                               ELSE ' ' 
                           END,
                           UPPER(SUBSTRING(s.last_name, 1, 1)), 
                           LOWER(SUBSTRING(s.last_name, 2)),
                           ' (', sv.student_id, ')'
                       )
                   ELSE 
                       CONCAT(
                           UPPER(SUBSTRING(sv.student_name, 1, 1)),
                           LOWER(SUBSTRING(sv.student_name, 2)),
                           ' (Non-CEIT Student)'
                       )
               END
               SEPARATOR '||') as student_names,
           GROUP_CONCAT(DISTINCT 
               CASE 
                   WHEN iw.witness_type = 'student' AND iw.witness_id IS NOT NULL THEN 
                       CONCAT(
                           UPPER(SUBSTRING(s2.first_name, 1, 1)), 
                           LOWER(SUBSTRING(s2.first_name, 2)),
                           CASE 
                               WHEN s2.middle_name IS NOT NULL AND s2.middle_name != '' 
                               THEN CONCAT(' ', UPPER(SUBSTRING(s2.middle_name, 1, 1)), '. ') 
                               ELSE ' ' 
                           END,
                           UPPER(SUBSTRING(s2.last_name, 1, 1)), 
                           LOWER(SUBSTRING(s2.last_name, 2)),
                           ' (', iw.witness_id, ')'
                       )
                   WHEN iw.witness_type = 'student' AND iw.witness_id IS NULL THEN 
                       CONCAT(
                           UPPER(SUBSTRING(iw.witness_name, 1, 1)),
                           LOWER(SUBSTRING(iw.witness_name, 2)),
                           ' (Non-CEIT Student)'
                       )
                   WHEN iw.witness_type = 'staff' THEN 
                       CONCAT(
                           UPPER(SUBSTRING(iw.witness_name, 1, 1)),
                           LOWER(SUBSTRING(iw.witness_name, 2)),
                           ' (Staff) - ', 
                           COALESCE(iw.witness_email, 'No email'), 
                           ')'
                       )
               END
               SEPARATOR '||') as witnesses
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s2 ON iw.witness_id = s2.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Add search condition
if (!empty($search)) {
    $search_condition = " AND (
        s.first_name LIKE ? OR 
        s.last_name LIKE ? OR 
        sv.student_name LIKE ? OR
        ir.description LIKE ? OR 
        ir.place LIKE ?
    )";
    $count_query .= $search_condition;
    $query .= $search_condition;
}

// Add status filter
if (!empty($status_filter)) {
    $status_condition = " AND ir.status = ?";
    $count_query .= $status_condition;
    $query .= $status_condition;
}

// Add group by and sorting
$query .= " GROUP BY ir.id ORDER BY ir.date_reported " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
$query .= " LIMIT ? OFFSET ?";

// Prepare and execute count query
$count_stmt = $connection->prepare($count_query);
if (!$count_stmt) {
    die("Error preparing count query: " . $connection->error);
}

$count_params = array($user_id, $user_type);
$count_types = "is";

if (!empty($search)) {
    $search_param = "%$search%";
    $count_params = array_merge($count_params, array($search_param, $search_param, $search_param, $search_param, $search_param));
    $count_types .= "sssss";
}

if (!empty($status_filter)) {
    $count_params[] = $status_filter;
    $count_types .= "s";
}

$count_stmt->bind_param($count_types, ...$count_params);
if (!$count_stmt->execute()) {
    die("Error executing count query: " . $count_stmt->error);
}

$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Prepare and execute main query
$stmt = $connection->prepare($query);
if (!$stmt) {
    die("Error preparing main query: " . $connection->error);
}

$params = array($user_id, $user_type);
$types = "is";

if (!empty($search)) {
    $search_param = "%$search%";
    $params = array_merge($params, array($search_param, $search_param, $search_param, $search_param, $search_param));
    $types .= "sssss";
}

if (!empty($status_filter)) {
    $params[] = $status_filter;
    $types .= "s";
}

$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    die("Error executing main query: " . $stmt->error);
}

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submitted Incident Reports - Adviser</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

/* Update/add these CSS styles */
.table td[data-label="Students Involved"],
.table td[data-label="Witnesses"] {
    text-align: left;
    vertical-align: middle;
    white-space: normal;
    min-height: 50px;
    padding: 15px;
}

.table td[data-label="Students Involved"] div,
.table td[data-label="Witnesses"] div {
    display: block;
    text-align: right;
    width: 100%;
    line-height: 1.8;
}

@media screen and (max-width: 768px) {
    .table td[data-label="Students Involved"] div,
    .table td[data-label="Witnesses"] div {
        text-align: right;
    } 
}
</style>
</head>
<body>
    <div class="container">
        <a href="adviser_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2>Submitted Incident Reports</h2>
            <div class="col-md-4">
                <div class="search-container">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                </div>
            </div>
        </div>

        <!-- Search and Filter Form -->
        <form class="mb-4 pt-2" method="GET" action="" id="filterForm">
            <div class="row">
                <div class="col-md-2">
                    <select name="status" id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo strtolower($status_filter) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="For Meeting" <?php echo strtolower($status_filter) === 'for meeting' ? 'selected' : ''; ?>>For Meeting</option>
                        <option value="Settled" <?php echo strtolower($status_filter) === 'settled' ? 'selected' : ''; ?>>Settled</option>
                        <option value="Reschedule" <?php echo strtolower($status_filter) === 'reschedule' ? 'selected' : ''; ?>>Reschedule</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort_order" id="sortOrderFilter" class="form-control">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="?" class="btn btn-secondary">Reset Filters</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Incident Place, <br>Date, Time</th>
                        <th>Description</th>
                        <th>Student/s Involved</th>
                        <th>Witness/es</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
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

                        // Format students into separate lines
                        $students = $row['student_names'];
                        $formatted_students = '';
                        if (!empty($students)) {
                            $student_array = explode('||', $students);
                            $formatted_students = implode(',<br><br>', array_map('htmlspecialchars', $student_array));
                        } else {
                            $formatted_students = 'No students involved';
                        }
                        
                        // Format witnesses into separate lines
                        $witnesses = $row['witnesses'];
                        $formatted_witnesses = '';
                        if (!empty($witnesses)) {
                            $witness_array = explode('||', $witnesses);
                            $formatted_witnesses = implode(',<br><br>', array_map('htmlspecialchars', $witness_array));
                        } else {
                            $formatted_witnesses = 'No witnesses';
                        }
                    ?>
                        <tr>
                            <td data-label="Date Reported">
                                <?php echo $formatted_date; ?><br>
                                <?php echo $formatted_time; ?>
                            </td>
                            <td data-label="Place, Date & Time"><?php echo $formatted_place; ?></td>
                            <td data-label="Description"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . ''; ?></td>
                            <td data-label="Students Involved">
                                <div style="text-align: left; line-height: 1.8;"><?php echo $formatted_students; ?></div>
                            </td>
                            <td data-label="Witnesses">
                                <div style="text-align: left; line-height: 1.8;"><?php echo $formatted_witnesses; ?></div>
                            </td>
                            <td data-label="Status">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </td>
                            <td data-label="Actions">
                                <a href="view_incident_details-adviser.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="no-data-message">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <p class="font-weight-bold">No data available</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?php echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="First">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="Previous">
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
                        <a class="page-link" href="?page=<?php echo $i; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; echo "&search=$search&status=$status_filter&sort_order=$sort_order"; ?>" aria-label="Last">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="text-center mt-3">
            <p>Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries</p>
        </div>
    </div>

<script>
   $(document).ready(function() {
        // Handle automatic filtering when dropdown values change
        $("#statusFilter, #sortOrderFilter").change(function() {
            $("#filterForm").submit();
        });

        // Your existing search functionality
        $("#searchInput").keyup(function() {
            var searchText = $(this).val().toLowerCase();
            var visibleRows = 0;
            
            // First, remove any existing no-results message
            $('.no-search-results').remove();
            
            // Hide the rows that don't match
            $(".table tbody tr:not(.no-search-results)").each(function() {
                var row = $(this);
                var rowText = row.text().toLowerCase();
                
                if (rowText.includes(searchText)) {
                    row.show();
                    visibleRows++;
                } else {
                    row.hide();
                }
            });
            
            // Check if the table is empty or all rows are hidden
            if (visibleRows === 0) {
                $(".table tbody").append(`
                    <tr class="no-search-results">
                        <td colspan="7" class="text-center py-5">
                            <div class="no-data-message">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <p class="font-weight-bold">No data available for your search</p>
                            </div>
                        </td>
                    </tr>
                `);
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