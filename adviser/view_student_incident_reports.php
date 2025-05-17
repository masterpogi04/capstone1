<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

$adviser_id = $_SESSION['user_id'];

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get search, sort, and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$involvement_filter = isset($_GET['involvement']) ? $_GET['involvement'] : ''; 

// Base query
$query = "
    SELECT DISTINCT ir.id, 
       DATE_FORMAT(ir.date_reported, '%M %d, %Y<br>%h:%i %p') as date_reported,
       ir.place, ir.description, ir.status,
       s.student_id, 
       CONCAT(
           UPPER(SUBSTRING(s.first_name, 1, 1)), 
           LOWER(SUBSTRING(s.first_name, 2)),
           CASE 
               WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
               THEN CONCAT(' ', UPPER(SUBSTRING(s.middle_name, 1, 1)), '. ') 
               ELSE ' ' 
           END,
           UPPER(SUBSTRING(s.last_name, 1, 1)), 
           LOWER(SUBSTRING(s.last_name, 2))
       ) as formatted_name,
       GROUP_CONCAT(DISTINCT 
           CONCAT(
               UPPER(SUBSTRING(other_s.first_name, 1, 1)), 
               LOWER(SUBSTRING(other_s.first_name, 2)),
               CASE 
                   WHEN other_s.middle_name IS NOT NULL AND other_s.middle_name != '' 
                   THEN CONCAT(' ', UPPER(SUBSTRING(other_s.middle_name, 1, 1)), '. ') 
                   ELSE ' ' 
               END,
               UPPER(SUBSTRING(other_s.last_name, 1, 1)), 
               LOWER(SUBSTRING(other_s.last_name, 2))
           )
       ) as other_students,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM student_violations sv2 
               WHERE sv2.incident_report_id = ir.id 
               AND sv2.student_id = s.student_id
           )
           AND EXISTS (
               SELECT 1 FROM incident_witnesses iw2 
               WHERE iw2.incident_report_id = ir.id 
               AND iw2.witness_id = s.student_id
           ) THEN 'Involved & Witness'
           WHEN EXISTS (
               SELECT 1 FROM student_violations sv2 
               WHERE sv2.incident_report_id = ir.id 
               AND sv2.student_id = s.student_id
           ) THEN 'Involved'
           WHEN EXISTS (
               SELECT 1 FROM incident_witnesses iw2 
               WHERE iw2.incident_report_id = ir.id 
               AND iw2.witness_id = s.student_id
           ) THEN 'Witness'
       END as involvement_type
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    JOIN tbl_student s ON (sv.student_id = s.student_id OR iw.witness_id = s.student_id)
    LEFT JOIN (
        SELECT DISTINCT ir2.id, s2.student_id, s2.first_name, s2.middle_name, s2.last_name
        FROM incident_reports ir2
        LEFT JOIN student_violations sv2 ON ir2.id = sv2.incident_report_id
        LEFT JOIN incident_witnesses iw2 ON ir2.id = iw2.incident_report_id
        JOIN tbl_student s2 ON (sv2.student_id = s2.student_id OR iw2.witness_id = s2.student_id)
        JOIN sections sec2 ON s2.section_id = sec2.id
        WHERE sec2.adviser_id = ?
    ) other_s ON ir.id = other_s.id AND s.student_id != other_s.student_id
    JOIN sections sec ON s.section_id = sec.id
    WHERE sec.adviser_id = ?
";

// Add search condition
if (!empty($search)) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR ir.description LIKE ? OR ir.id LIKE ?)";
}

// Add status filter
if (!empty($status_filter)) {
    $query .= " AND ir.status = ?";
}

// Add involvement filter
if (!empty($involvement_filter)) {
    if ($involvement_filter === 'Involved') {
        $query .= " AND EXISTS (SELECT 1 FROM student_violations sv2 WHERE sv2.incident_report_id = ir.id AND sv2.student_id = s.student_id)
                    AND NOT EXISTS (SELECT 1 FROM incident_witnesses iw2 WHERE iw2.incident_report_id = ir.id AND iw2.witness_id = s.student_id)";
    } elseif ($involvement_filter === 'Witness') {
        $query .= " AND EXISTS (SELECT 1 FROM incident_witnesses iw2 WHERE iw2.incident_report_id = ir.id AND iw2.witness_id = s.student_id)
                    AND NOT EXISTS (SELECT 1 FROM student_violations sv2 WHERE sv2.incident_report_id = ir.id AND sv2.student_id = s.student_id)";
    } elseif ($involvement_filter === 'Involved & Witness') {
        $query .= " AND EXISTS (SELECT 1 FROM student_violations sv2 WHERE sv2.incident_report_id = ir.id AND sv2.student_id = s.student_id)
                    AND EXISTS (SELECT 1 FROM incident_witnesses iw2 WHERE iw2.incident_report_id = ir.id AND iw2.witness_id = s.student_id)";
    }
}

$query .= " GROUP BY ir.id, s.student_id";
$query .= " ORDER BY ir.date_reported " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');
$query .= " LIMIT ? OFFSET ?";

// Prepare statement and bind parameters
$stmt = $connection->prepare($query);

// Initialize parameters array with both adviser_ids
$params = array($adviser_id, $adviser_id);
$types = "ii";

if (!empty($search)) {
    $search_param = "%$search%";
    $params = array_merge($params, array($search_param, $search_param, $search_param, $search_param));
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $params[] = $status_filter;
    $types .= "s";
}

$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Count total records for pagination
$count_query = "
    SELECT COUNT(DISTINCT ir.id) as total 
    FROM incident_reports ir
    JOIN student_violations sv ON ir.id = sv.incident_report_id
    JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id AND iw.witness_id = s.student_id
    LEFT JOIN (
        SELECT DISTINCT ir2.id, s2.first_name, s2.last_name
        FROM incident_reports ir2
        LEFT JOIN student_violations sv2 ON ir2.id = sv2.incident_report_id
        LEFT JOIN incident_witnesses iw2 ON ir2.id = iw2.incident_report_id
        JOIN tbl_student s2 ON (sv2.student_id = s2.student_id OR iw2.witness_id = s2.student_id)
        JOIN sections sec2 ON s2.section_id = sec2.id
        WHERE sec2.adviser_id = ?
    ) other_s ON ir.id = other_s.id AND CONCAT(s.first_name, ' ', s.last_name) != CONCAT(other_s.first_name, ' ', other_s.last_name)
    JOIN sections sec ON s.section_id = sec.id
    WHERE sec.adviser_id = ?
";

if (!empty($search)) {
    $count_query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR ir.description LIKE ? OR ir.id LIKE ?)";
}

if (!empty($status_filter)) {
    $count_query .= " AND ir.status = ?";
}

if (!empty($involvement_filter)) {
    if ($involvement_filter === 'Involved') {
        $count_query .= " AND EXISTS (SELECT 1 FROM student_violations sv2 WHERE sv2.incident_report_id = ir.id AND sv2.student_id = s.student_id)
                    AND NOT EXISTS (SELECT 1 FROM incident_witnesses iw2 WHERE iw2.incident_report_id = ir.id AND iw2.witness_id = s.student_id)";
    } elseif ($involvement_filter === 'Witness') {
        $count_query .= " AND EXISTS (SELECT 1 FROM incident_witnesses iw2 WHERE iw2.incident_report_id = ir.id AND iw2.witness_id = s.student_id)
                    AND NOT EXISTS (SELECT 1 FROM student_violations sv2 WHERE sv2.incident_report_id = ir.id AND sv2.student_id = s.student_id)";
    } elseif ($involvement_filter === 'Involved & Witness') {
        $count_query .= " AND EXISTS (SELECT 1 FROM student_violations sv2 WHERE sv2.incident_report_id = ir.id AND sv2.student_id = s.student_id)
                    AND EXISTS (SELECT 1 FROM incident_witnesses iw2 WHERE iw2.incident_report_id = ir.id AND iw2.witness_id = s.student_id)";
    }
}

$count_stmt = $connection->prepare($count_query);

// Initialize count parameters array with both adviser_ids
$count_params = array($adviser_id, $adviser_id);
$count_types = "ii";

if (!empty($search)) {
    $count_params = array_merge($count_params, array($search_param, $search_param, $search_param, $search_param));
    $count_types .= "ssss";
}

if (!empty($status_filter)) {
    $count_params[] = $status_filter;
    $count_types .= "s";
}

$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Check if this is an AJAX request
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// If it's an AJAX request, return only the table content
if ($is_ajax) {
    ob_start();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            ?>
            <tr>
                <td data-label="Student Name">
                    <?php 
                    echo htmlspecialchars($row['formatted_name']);
                    
                    // Only show ellipsis if there are other students
                    if (!empty($row['other_students'])) {
                        $other_students = explode(',', $row['other_students']);
                        if (count($other_students) > 0) {
                            echo '<br><span class="text-muted">...</span>';
                        }
                    }
                    ?>
                </td>
                <td data-label="Date Reported"><?php echo $row['date_reported']; ?></td>
                <td data-label="Incident Date/Time">
                <?php 
                    $place_string = $row['place'];
                    $parts = explode(' - ', $place_string);
                    if (count($parts) > 1) {
                        $location = htmlspecialchars($parts[0]);
                        $datetime_parts = explode(' at ', $parts[1]);
                        
                        echo $location . ',<br>';
                        if (count($datetime_parts) > 1) {
                            echo htmlspecialchars($datetime_parts[0]) . '<br>at ' . htmlspecialchars($datetime_parts[1]);
                        } else {
                            echo htmlspecialchars($parts[1]);
                        }
                    } else {
                        echo htmlspecialchars($place_string);
                    }
                ?>
                </td>
                <td data-label="Description"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . ''; ?></td>
                <td data-label="Involvement status"><?php echo htmlspecialchars($row['involvement_type']); ?></td>
                <td data-label="Status"><?php echo htmlspecialchars($row['status']); ?></td>
                <td data-label="Action">
                    <a href="view_advisee_incident_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="background-color: #009E60; color: white;"><i class="fas fa-eye"></i></a>
                </td>
            </tr>
            <?php
        }
    } else {
        ?>
        <tr class="no-data-row">
            <td colspan="7" class="text-center py-5">
                <div class="no-data-message">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <p class="font-weight-bold">No data available</p>
                </div>
            </td>
        </tr>
        <?php
    }
    
    $html = ob_get_clean();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'showing_start' => ($offset + 1),
            'showing_end' => min($offset + $records_per_page, $total_records)
        ]
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Incident Reports</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include Bootstrap JS for mobile responsiveness -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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


h2{
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 15px 0 30px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
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
        width: 15%;
    }

   /* Description - making it wider */
    .table td:nth-child(4) {
        width: 30%;
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

    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="adviser_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2 class="mb-3 mb-md-0">Advisee Incident Report</h2>
            <div class="col-12 col-md-4 px-0">
                <div class="search-container">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Form -->
        <form id="filterForm" class="mb-4" method="GET" action="">
            <div class="row">
                <!-- Hidden input for search value from the search box -->
                <input type="hidden" name="search" id="searchParam" value="<?php echo htmlspecialchars($search); ?>">
                
                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <select name="status" id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo strtolower($status_filter) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="For Meeting" <?php echo strtolower($status_filter) === 'for meeting' ? 'selected' : ''; ?>>For Meeting</option>
                        <option value="Settled" <?php echo strtolower($status_filter) === 'settled' ? 'selected' : ''; ?>>Settled</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <select name="involvement" id="involvementFilter" class="form-control">
                        <option value="">All Involvement</option>
                        <option value="Involved" <?php echo $involvement_filter === 'Involved' ? 'selected' : ''; ?>>Involved</option>
                        <option value="Witness" <?php echo $involvement_filter === 'Witness' ? 'selected' : ''; ?>>Witness</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 mb-2 mb-md-0">
                    <select name="sort_order" id="sortOrder" class="form-control">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 mb-2 mb-md-0">
                    <div class="d-flex">
                        <button type="button" class="btn btn-secondary" id="resetFilters">Reset Filter</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-container">
            <div class="loader" id="tableLoader"></div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Date Reported</th>
                            <th>Incident Place, <br>Date, Time</th>
                            <th>Description</th>
                            <th>Involvement</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="incidentTableBody">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td data-label="Student Name">
                                    <?php 
                                    echo htmlspecialchars($row['formatted_name']);
                                    
                                    // Only show ellipsis if there are other students
                                    if (!empty($row['other_students'])) {
                                        $other_students = explode(',', $row['other_students']);
                                        if (count($other_students) > 0) {
                                            echo '<br><span class="text-muted">...</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td data-label="Date Reported"><?php echo $row['date_reported']; ?></td>
                                <td data-label="Incident Date/Time">
                                <?php 
                                    $place_string = $row['place'];
                                    $parts = explode(' - ', $place_string);
                                    if (count($parts) > 1) {
                                        $location = htmlspecialchars($parts[0]);
                                        $datetime_parts = explode(' at ', $parts[1]);
                                        
                                        echo $location . ',<br>';
                                        if (count($datetime_parts) > 1) {
                                            echo htmlspecialchars($datetime_parts[0]) . '<br>at ' . htmlspecialchars($datetime_parts[1]);
                                        } else {
                                            echo htmlspecialchars($parts[1]);
                                        }
                                    } else {
                                        echo htmlspecialchars($place_string);
                                    }
                                ?>
                                </td>
                                <td data-label="Description"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . ''; ?></td>
                                <td data-label="Involvement status"><?php echo htmlspecialchars($row['involvement_type']); ?></td>
                                <td data-label="Status"><?php echo htmlspecialchars($row['status']); ?></td>
                                <td data-label="Action">
                                    <a href="view_advisee_incident_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" style="background-color: #009E60; color: white;"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr class="no-data-row">
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
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center flex-wrap" id="paginationContainer">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" data-page="1">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" data-page="<?php echo $page - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="javascript:void(0)" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" data-page="<?php echo $page + 1; ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" data-page="<?php echo $total_pages; ?>">Last</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="text-center mt-3" id="paginationInfo">
            <p>Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries</p>
        </div>
    </div>

<script>
$(document).ready(function() {
    // Variables to store current state
    let currentPage = <?php echo $page; ?>;
    let searchTerm = "<?php echo addslashes($search); ?>";
    let statusFilter = "<?php echo addslashes($status_filter); ?>";
    let involvementFilter = "<?php echo addslashes($involvement_filter); ?>";
    let sortOrder = "<?php echo addslashes($sort_order); ?>";
    
    // Function to update the table with AJAX
    function updateTable() {
        // Show loading indicator
        $("#tableLoader").show();
        
        // Prepare data for AJAX request
        const data = {
            search: searchTerm,
            status: statusFilter,
            involvement: involvementFilter,
            sort_order: sortOrder,
            page: currentPage
        };
        
        // Add X-Requested-With header to identify as AJAX request
        $.ajax({
            url: window.location.pathname,
            type: "GET",
            data: data,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                // Update table content
                $("#incidentTableBody").html(response.html);
                
                // Update pagination
                updatePagination(response.pagination);
                
                // Hide loading indicator
                $("#tableLoader").hide();
            },
            error: function() {
                // Hide loading indicator and show error message
                $("#tableLoader").hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong! Please try again.'
                });
            }
        });
    }
    
    // Function to update the pagination
    function updatePagination(pagination) {
        // Update current page
        currentPage = pagination.current_page;
        
        // Generate pagination HTML
        let paginationHtml = '';
        
        // First and Previous buttons
        if (currentPage > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" data-page="1">First</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}">Previous</a>
                </li>
            `;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(pagination.total_pages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Next and Last buttons
        if (currentPage < pagination.total_pages) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" data-page="${currentPage + 1}">Next</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" data-page="${pagination.total_pages}">Last</a>
                </li>
            `;
        }
        
        // Update pagination container
        $("#paginationContainer").html(paginationHtml);
        
        // Update pagination info
        $("#paginationInfo").html(`
            <p>Showing ${pagination.showing_start} to ${pagination.showing_end} of ${pagination.total_records} entries</p>
        `);
        
        // Reattach click events to pagination links
        attachPaginationEvents();
    }
    
    // Function to attach click events to pagination links
    function attachPaginationEvents() {
        $("#paginationContainer .page-link").click(function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data("page"));
            updateTable();
        });
    }
    
    // Real-time search as you type (no delay)
    $("#searchInput").on('input', function() {
        searchTerm = $(this).val();
        $("#searchParam").val(searchTerm);
        currentPage = 1; // Reset to first page on new search
        updateTable();
    });
    
    // Handle filter changes
    $("#statusFilter, #involvementFilter, #sortOrder").change(function() {
        statusFilter = $("#statusFilter").val();
        involvementFilter = $("#involvementFilter").val();
        sortOrder = $("#sortOrder").val();
        currentPage = 1; // Reset to first page on new filter
        updateTable();
    });
    
    // Reset filters button
    $("#resetFilters").click(function() {
        // Reset all filter values
        $("#searchInput").val("");
        $("#searchParam").val("");
        $("#statusFilter").val("");
        $("#involvementFilter").val("");
        $("#sortOrder").val("DESC");
        
        // Update variables
        searchTerm = "";
        statusFilter = "";
        involvementFilter = "";
        sortOrder = "DESC";
        currentPage = 1;
        
        // Update table
        updateTable();
    });
    
    // Attach initial pagination events
    attachPaginationEvents();
    
    // Handle mobile responsiveness
    function adjustTableForMobile() {
        if (window.innerWidth < 768) {
            $('.table').addClass('mobile-view');
        } else {
            $('.table').removeClass('mobile-view');
        }
    }
    
    // Run on page load and resize
    adjustTableForMobile();
    $(window).resize(adjustTableForMobile);
});
</script>
</body>
</html>