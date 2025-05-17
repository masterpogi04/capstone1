<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guard') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get search, sort, and filter parameters
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$involvement_filter = isset($_GET['involvement']) ? $_GET['involvement'] : '';

// Helper function to convert names to proper case
function convertToProperCase($name) {
    // Split the name and any annotations
    $parts = explode(' - (', $name, 2);
    $namePart = $parts[0];
    $annotationPart = isset($parts[1]) ? ' - (' . $parts[1] : '';
    
    // Convert the name to proper case, keeping initials capitalized
    $words = explode(' ', $namePart);
    $properName = '';
    
    foreach ($words as $word) {
        // If word contains periods (like initials), keep as is
        if (strpos($word, '.') !== false) {
            $properName .= $word . ' ';
        } else {
            $properName .= ucfirst(strtolower($word)) . ' ';
        }
    }
    
    return trim($properName) . $annotationPart;
}

// Count total records for pagination
$count_query = "SELECT COUNT(DISTINCT pir.id) as total 
                FROM pending_incident_reports pir
                LEFT JOIN pending_student_violations psv ON pir.id = psv.pending_report_id
                WHERE pir.guard_id = ?";

// Add search filter if search term is provided
$count_params = [$user_id];
$count_types = "i";

if (!empty($search_term)) {
    $count_query .= " AND (
        pir.place LIKE CONCAT('%', ?, '%') OR 
        pir.description LIKE CONCAT('%', ?, '%') OR 
        EXISTS (SELECT 1 FROM pending_student_violations WHERE pending_report_id = pir.id AND student_name LIKE CONCAT('%', ?, '%')) OR
        EXISTS (SELECT 1 FROM pending_incident_witnesses WHERE pending_report_id = pir.id AND witness_name LIKE CONCAT('%', ?, '%')) OR
        pir.status LIKE CONCAT('%', ?, '%')
    )";
    $count_params = array_merge($count_params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
    $count_types .= "sssss";
}

// Add status filter
if (!empty($status_filter)) {
    $count_query .= " AND pir.status = ?";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

$count_stmt = $connection->prepare($count_query);
$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Modified query to show all involved students
$query = "
    SELECT 
        pir.*,
        GROUP_CONCAT(
            DISTINCT 
            CASE 
                WHEN psv.student_id IS NOT NULL THEN 
                    CONCAT(psv.student_name, ' - (', psv.student_id, ')')
                ELSE 
                    CONCAT(psv.student_name, ' (No ID)')
            END
            ORDER BY psv.student_name 
            SEPARATOR ',\n\n'
        ) as student_details,
        GROUP_CONCAT(
            DISTINCT 
            CASE 
                WHEN piw.witness_type = 'student' THEN
                    CASE 
                        WHEN piw.witness_id IS NOT NULL THEN 
                            CONCAT(piw.witness_name, ' - (', piw.witness_id, ')')
                        ELSE 
                            CONCAT(piw.witness_name, ' - (No ID)')
                    END
                WHEN piw.witness_type = 'staff' THEN
                    CONCAT(piw.witness_name, ' (Staff) - ', COALESCE(piw.witness_email, 'No email'), '')
                ELSE 
                    piw.witness_name
            END
            ORDER BY piw.witness_name 
            SEPARATOR ',\n\n'
        ) as witnesses
    FROM pending_incident_reports pir
    LEFT JOIN pending_student_violations psv ON pir.id = psv.pending_report_id
    LEFT JOIN pending_incident_witnesses piw ON pir.id = piw.pending_report_id
    WHERE pir.guard_id = ?";

// Add search filter if search term is provided
if (!empty($search_term)) {
    $query .= " AND (
        pir.place LIKE CONCAT('%', ?, '%') OR 
        pir.description LIKE CONCAT('%', ?, '%') OR 
        psv.student_name LIKE CONCAT('%', ?, '%') OR
        piw.witness_name LIKE CONCAT('%', ?, '%') OR
        pir.status LIKE CONCAT('%', ?, '%')
    )";
}

// Add status filter
if (!empty($status_filter)) {
    $query .= " AND pir.status = ?";
}

$query .= " GROUP BY pir.id ORDER BY pir.date_reported " . $sort_order . " LIMIT ? OFFSET ?";

// Prepare and execute the query with the appropriate parameters
$stmt = $connection->prepare($query);

// Create array of parameters and their types
$params = [$user_id];
$types = "i";

if (!empty($search_term)) {
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term, $search_term]);
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
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submitted Pending Incident Reports - Guard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
     
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
.table th:nth-child(1), /* Date Reported */
.table td:nth-child(1) {
    width: 15%;
    padding: 20px;
}

.table th:nth-child(2), /* Date Reported */
.table td:nth-child(2) {
    width: 12%;
}

/* Place, Date & Time */
.table td:nth-child(3) {
    width: 20%;
    text-align: left;
    white-space: normal;
    min-width: 220px;
}

.table th:nth-child(3){
    width: 10%;
    padding: 20px;
}

/* Description - making it wider */
.table th:nth-child(4),
.table td:nth-child(4) {
    width: 10%;
    padding: 20px;
}

.table th:nth-child(5), /* Involvement */
.table td:nth-child(5) {
    width: 10%;
    padding: 20px;
}

.table th:nth-child(6), /* Status */
.table td:nth-child(6) {
    width: 10%;
    padding: 20px;
}

.table th:nth-child(7), /* Action */
.table td:nth-child(7) {
    width: 8%;
    padding: 20px;
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
td[data-label="Witness/es"] {
    width: 150px;
}

td[data-label="Status"] {
    width: 100px;
    text-align: center;
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

/* No Records Message */
.no-records-message {
    text-align: center;
    padding: 40px 20px;
    background-color: #f9f9f9;
    border-radius: 10px;
    margin: 20px 0;
    font-size: 18px;
    color: #666;
    border: 1px dashed #ccc;
}

.no-records-message i {
    display: block;
    font-size: 48px;
    margin-bottom: 15px;
    color: #999;
}

/* Pagination styling */
.pagination {
    justify-content: center;
    margin-top: 20px;
}

.pagination .page-item .page-link {
    color: #009E60;
    border-color: #ddd;
    background-color: #fff;
    transition: all 0.2s ease;
}

.pagination .page-item.active .page-link {
    background-color: #009E60;
    border-color: #009E60;
    color: white;
}

.pagination .page-item .page-link:hover {
    background-color: #e9f9f2;
    color: #009E60;
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
        width: auto;
        padding: 8px 12px;
        text-align: center;
        border-radius: 6px;
    }
    
    /* Adjust filter form in mobile view */
    .filter-controls .row {
        flex-direction: column;
    }
    
    .filter-controls .col-md-1,
    .filter-controls .col-md-1 {
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
}

/* Touch Device Optimizations */
@media (hover: none) {
    .form-control {
        min-height: 44px;
    }

    .table td {
        padding: 12px 15px;
    }
}

/* Update/add these CSS styles */
.table td[data-label="Students Involved"],
.table td[data-label="Witness/es"] {
    text-align: left;
    vertical-align: middle;
    white-space: normal;
    min-height: 50px;
    padding: 15px;
}

.table td[data-label="Students Involved"] div,
.table td[data-label="Witness/es"] div {
    display: inline-block;
    text-align: left;
    width: 100%;
    line-height: 1.8;
}

@media screen and (max-width: 768px) {
    .table td[data-label="Students Involved"] div,
    .table td[data-label="Witness/es"] div {
        text-align: right;
    }
    
    .table td[data-label="Description"],
    .table td[data-label="Place, Date & Time"] {
        text-align: right;
    }
}
.no-data-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 50px 20px;
    background-color: #f8f9fa;
    border-radius: 10px;
    margin: 30px 0;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.no-data-icon {
    font-size: 70px;
    color: #a0a0a0;
    margin-bottom: 20px;
}

.no-data-text {
    font-size: 18px;
    color: #5c5c5c;
    font-weight: 500;
    text-align: center;
}

.no-data-subtext {
    font-size: 14px;
    color: #7d7d7d;
    text-align: center;
    margin-top: 5px;
}
.header-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding-bottom: 10px;
}

.page-title {
    margin: 0;
    min-width: 200px;
}

.search-container {
    width: 100%;
    max-width: 300px;
}

.filter-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-item {
    min-width: 150px;
}

/* Mobile responsiveness fixes */
@media screen and (max-width: 768px) {
    .header-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .page-title {
        text-align: center;
        margin-bottom: 15px;
    }
    
    .search-container {
        max-width: 100%;
        width: 100%;
    }
    
    .filter-controls {
        flex-direction: column;
    }
    
    .filter-item {
        width: 100%;
    }
}

@media screen and (max-width: 768px) {
    /* This makes your td elements align content properly in mobile view */
    .table td {
        display: flex;
        justify-content: space-between; /* This creates space between the label and the value */
        align-items: center; /* This vertically centers the content */
        text-align: left; /* This ensures all text starts from the left */
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
    }
    
    /* This styles the data-label (your th equivalent in mobile) */
    .table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #444;
        margin-right: 10px; /* Add some space between label and value */
        text-align: left; /* Align header text to the left */
        width: 40%; /* Give the label a fixed width */
        flex-shrink: 0; /* Prevent the label from shrinking */
    }
    
    /* This targets the actual data in the cell */
    .table td > * {
        text-align: right; /* Align all content to the right */
        width: 60%; /* Give the content appropriate width */
    }
    
    /* Fix for Students Involved and Witnesses cells which have div wrappers */
    .table td[data-label="Student/s Involved"] div {
        text-align: right;
        width: 30%;
        margin-left: auto;
    }
    .table td[data-label="Witness/es"] div {
        text-align: right;
        width: 40%;
        margin-left: auto; /* This pushes the content to the right */
    }
    
    /* Fix specifically for Description and Place cells */
    .table td[data-label="Description"],
    .table td[data-label="Place, Date & Time"] {
        text-align: left; /* The cell itself remains left aligned */
    }
    
    /* But the actual content should be right aligned */
    .table td[data-label="Description"] > *,
    .table td[data-label="Place, Date & Time"] > * {
        text-align: right;
    }
}
</style>

</head>
<body>
    <div class="container mt-5">
        <a href="guard_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <div class="header-container mb-4" style="border-bottom: 3px solid #004d4d;">
            <h2 class="page-title">Submitted Incident Report</h2>
            <div class="search-container">
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>
        </div>

        <!-- Search and Filter Controls - Simplified with auto-submit -->
        <div class="filter-controls">
            <div class="filter-item">
                <select id="statusFilter" class="form-control">
                    <option value="">All Status</option>
                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Escalated" <?php echo $status_filter === 'Escalated' ? 'selected' : ''; ?>>Escalated</option>
                </select>
            </div>
            
            <div class="filter-item">
                <select id="sortOrder" class="form-control">
                    <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest</option>
                    <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest</option>
                </select>
            </div>
            <a href="?" class="btn btn-secondary">Reset Filters</a>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date Reported</th>
                            <th>Incident Place,<br>Date & Time</th>
                            <th>Description</th>
                            <th>Student/s Involved</th>
                            <th>Witness/es</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
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
                        <td data-label="Place, Date & Time"><?php echo $formatted_place; ?></td>
                        <td data-label="Description"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                        <td data-label="Student/s Involved">
                            <?php  
                            if (!empty($row['student_details'])) {
                                echo '<div class="student-list" style="white-space: pre-line;">';
                                $students = explode(",\n\n", $row['student_details']);
                                $formatted_students = array();
                                foreach ($students as $student) {
                                    $formatted_students[] = convertToProperCase($student);
                                }
                                echo htmlspecialchars(implode(",\n\n", $formatted_students));
                                echo '</div>';
                            } else {
                                echo 'No students involved';
                            }
                            ?>
                        </td>
                        <td data-label="Witness/es">
                            <?php 
                            if (!empty($row['witnesses'])) {
                                echo '<div style="white-space: pre-line; text-align: left;">';
                                $witnesses = explode(",\n\n", $row['witnesses']);
                                $formatted_witnesses = array();
                                foreach ($witnesses as $witness) {
                                    $formatted_witnesses[] = convertToProperCase($witness);
                                }
                                echo htmlspecialchars(implode(",\n\n", $formatted_witnesses));
                                echo '</div>';
                            } else {
                                echo 'No witnesses';
                            }
                            ?>
                        </td> 
                        <td data-label="Status"><?php echo htmlspecialchars($row['status']); ?></td>
                        <td data-label="Actions">
                            <a href="view_incident_details_guard.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
<div id="noSearchResults" style="display: none;">
    <div class="no-data-container">
        <i class="fas fa-search no-data-icon"></i>
        <div class="no-data-text">No matching incident reports found</div>
        <div class="no-data-subtext">Try adjusting your search criteria or filters</div>
    </div>
</div>
            </div>
            
        <?php else: ?>
            <!-- No Records Found message -->
            <div class="no-data-container">
                <?php if (!empty($search_term) || !empty($status_filter)): ?>
                    <i class="fas fa-search no-data-icon"></i>
                    <div class="no-data-text">No matching incident reports found</div>
                    <div class="no-data-subtext">Try adjusting your search criteria or filters</div>
                <?php else: ?>
                    <i class="fas fa-folder-open no-data-icon"></i>
                    <div class="no-data-text">No incident reports found</div>
                    <div class="no-data-subtext">No reports have been submitted yet</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['sort_order']) ? '&sort_order=' . urlencode($_GET['sort_order']) : ''; ?>">&laquo;&laquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['sort_order']) ? '&sort_order=' . urlencode($_GET['sort_order']) : ''; ?>">&laquo;</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['sort_order']) ? '&sort_order=' . urlencode($_GET['sort_order']) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['sort_order']) ? '&sort_order=' . urlencode($_GET['sort_order']) : ''; ?>">&raquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['sort_order']) ? '&sort_order=' . urlencode($_GET['sort_order']) : ''; ?>">&raquo;&raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
        
        <?php if ($result->num_rows > 0): ?>
        <div class="text-center mt-3">
            <p>Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries</p>
        </div>
        <?php endif; ?>
    </div> 

  <script>
    $(document).ready(function() {
         $("#searchInput").keyup(function() {
        var searchText = $(this).val().toLowerCase();
        var visibleRows = 0;
        
        $(".table tbody tr").each(function() {
            var row = $(this);
            
            // Get text from all relevant cells
            var dateReported = row.find('td[data-label="Date Reported"]').text().toLowerCase();
            var placeDateTime = row.find('td[data-label="Place, Date & Time"]').text().toLowerCase();
            var description = row.find('td[data-label="Description"]').text().toLowerCase();
            var studentsInvolved = row.find('td[data-label="Student/s Involved"]').text().toLowerCase();
            var witnesses = row.find('td[data-label="Witness/es"]').text().toLowerCase();
            var status = row.find('td[data-label="Status"]').text().toLowerCase();
            
            // Combine all searchable content
            var rowContent = dateReported + ' ' + placeDateTime + ' ' + 
                           description + ' ' + studentsInvolved + ' ' + 
                           witnesses + ' ' + status;
            
            // Remove extra spaces and format text
            var formattedContent = rowContent.replace(/\s+/g, ' ').trim();
            var searchPattern = searchText.replace(/\s+/g, ' ').trim();
            
            // Show/hide row based on search match
            if (formattedContent.includes(searchPattern)) {
                row.show();
                visibleRows++;
            } else {
                row.hide();
            }
        });
        
        // Show/hide table and no results message based on search results
        if (visibleRows === 0 && searchText !== '') {
            $(".table-responsive").hide();
            $("#noSearchResults").show();
        } else {
            $(".table-responsive").show();
            $("#noSearchResults").hide();
        }
    });
        
        // Auto-submit on select change for filters
        $("#statusFilter, #sortOrder").on('change', function() {
            updateURL();
        });
        
        // Client-side filtering for immediate feedback
        function filterTableRows(searchText) {
            searchText = searchText.toLowerCase();
            let visibleRows = 0;
            
            $("table tbody tr").each(function() {
                const rowText = $(this).text().toLowerCase();
                const isVisible = rowText.indexOf(searchText) > -1;
                $(this).toggle(isVisible);
                if (isVisible) {
                    visibleRows++;
                }
            });
            
            // Toggle no data message
            if (visibleRows === 0 && $("table tbody tr").length > 0) {
                $(".table-responsive").hide();
                $("#noDataContainer").show();
            } else {
                $(".table-responsive").show();
                $("#noDataContainer").hide();
            }
        }
        
        // Update URL and reload page with new filters
        function updateURL() {
            const searchText = $("#searchInput").val();
            const status = $("#statusFilter").val();
            const sortOrder = $("#sortOrder").val();
            
            let url = window.location.pathname + '?';
            let params = [];
            
            if (searchText) params.push('search=' + encodeURIComponent(searchText));
            if (status) params.push('status=' + encodeURIComponent(status));
            if (sortOrder) params.push('sort_order=' + encodeURIComponent(sortOrder));
            
            // Keep current page if available
            const urlParams = new URLSearchParams(window.location.search);
            const currentPage = urlParams.get('page');
            if (currentPage && currentPage > 1) {
                params.push('page=' + currentPage);
            }
            
            window.location.href = url + params.join('&');
        }
        
        // Handle browser back/forward buttons
        $(window).on('popstate', function() {
            location.reload();
        });
        
        // Add touch device detection and optimization
        function isTouchDevice() {
            return 'ontouchstart' in window || navigator.maxTouchPoints;
        }
        
        if (isTouchDevice()) {
            $('body').addClass('touch-device');
            
            // Make filter selections more touch-friendly
            $('.form-control').css('padding', '10px 15px');
        }
    });
    </script>
</body>
</html>