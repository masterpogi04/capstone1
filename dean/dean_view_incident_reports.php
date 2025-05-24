<?php 
require_once 'dean_view_incident_reports_handler.php';

// Check if this is an AJAX request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Pagination settings
    $records_per_page = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page); // Ensure page is at least 1
    $offset = ($page - 1) * $records_per_page;

    // Search parameter
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Sort parameter (default is newest first)
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';

    // Modify the query to include pagination, search, and sorting
    $count_query = "SELECT COUNT(*) as total FROM (
        SELECT p.id
        FROM pending_incident_reports p
        LEFT JOIN tbl_guard g ON p.guard_id = g.id
        LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
        LEFT JOIN pending_incident_witnesses piw ON p.id = piw.pending_report_id
        WHERE p.status = 'Pending'";

    // Add search condition if search term is provided
    if (!empty($search)) {
        $search_term = '%' . $connection->real_escape_string($search) . '%';
        $count_query .= " AND (
            p.description LIKE '$search_term' OR
            p.date_reported LIKE '$search_term' OR
            p.place LIKE '$search_term' OR
            psv.student_name LIKE '$search_term' OR 
            psv.student_id LIKE '$search_term' OR
            psv.student_course LIKE '$search_term' OR
            psv.student_year_level LIKE '$search_term' OR
            psv.section_name LIKE '$search_term' OR
            piw.witness_name LIKE '$search_term' OR
            piw.witness_id LIKE '$search_term' OR
            piw.witness_type LIKE '$search_term' OR
            piw.witness_course LIKE '$search_term' OR
            piw.witness_year_level LIKE '$search_term' OR
            piw.section_name LIKE '$search_term' OR
            CONCAT(g.first_name, ' ', COALESCE(g.middle_initial, ''), ' ', g.last_name) LIKE '$search_term'
        )";
    }

    $count_query .= " GROUP BY p.id) as subquery";

    $count_result = $connection->query($count_query);
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row['total'] ?? 0;
    $total_pages = ceil($total_records / $records_per_page);

    // Main query for records
    $query = "SELECT p.*, 
              CONCAT(g.first_name, ' ', COALESCE(g.middle_initial, ''), ' ', g.last_name) AS guard_name,
              GROUP_CONCAT(DISTINCT CONCAT(
                  COALESCE(psv.student_id, 'NULL'), ':', 
                  psv.student_name, ':', 
                  COALESCE(psv.student_course, ''), ':', 
                  COALESCE(psv.student_year_level, ''), ':', 
                  COALESCE(psv.section_name, '')
              ) SEPARATOR '|') AS involved_students,
              GROUP_CONCAT(DISTINCT CONCAT(
                  piw.witness_type, ':', 
                  COALESCE(piw.witness_id, 'NULL'), ':', 
                  piw.witness_name, ':', 
                  COALESCE(piw.witness_course, ''), ':',
                  COALESCE(piw.witness_year_level, ''), ':',
                  COALESCE(piw.section_name, '')
              ) SEPARATOR '|') AS witnesses
              FROM pending_incident_reports p
              LEFT JOIN tbl_guard g ON p.guard_id = g.id
              LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
              LEFT JOIN pending_incident_witnesses piw ON p.id = piw.pending_report_id
              WHERE p.status = 'Pending'";

    // Add search condition if search term is provided
    if (!empty($search)) {
        $search_term = '%' . $connection->real_escape_string($search) . '%';
        $query .= " AND (
            p.description LIKE '$search_term' OR
            p.date_reported LIKE '$search_term' OR
            p.place LIKE '$search_term' OR
            psv.student_name LIKE '$search_term' OR 
            psv.student_id LIKE '$search_term' OR
            psv.student_course LIKE '$search_term' OR
            psv.student_year_level LIKE '$search_term' OR
            psv.section_name LIKE '$search_term' OR
            piw.witness_name LIKE '$search_term' OR
            piw.witness_id LIKE '$search_term' OR
            piw.witness_type LIKE '$search_term' OR
            piw.witness_course LIKE '$search_term' OR
            piw.witness_year_level LIKE '$search_term' OR
            piw.section_name LIKE '$search_term' OR
            CONCAT(g.first_name, ' ', COALESCE(g.middle_initial, ''), ' ', g.last_name) LIKE '$search_term'
        )";
    }

    $query .= " GROUP BY p.id
              ORDER BY p.date_reported " . ($sort == 'asc' ? 'ASC' : 'DESC') . "
              LIMIT $offset, $records_per_page";

    $result = $connection->query($query);
    
    $tableHTML = '';
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tableHTML .= '<tr>';
            $tableHTML .= '<td>' . date('F j, Y g:i A', strtotime($row['date_reported'])) . '</td>';
            
            // Involved Students
            $tableHTML .= '<td>';
            $involved_students = explode('|', $row['involved_students']);
            $total_students = count(array_filter($involved_students));
            $current_count = 0;
            
            foreach ($involved_students as $student) {
                if (empty($student)) continue;
                
                $current_count++;
                $tableHTML .= formatStudentDisplay($student, $connection);
                
                if ($current_count < $total_students) {
                    $tableHTML .= ", <br><br>";
                } else {
                    $tableHTML .= "<br>";
                }
            }
            $tableHTML .= '</td>';
            
            // Witnesses
            $tableHTML .= '<td>';
            $witnesses = explode('|', $row['witnesses']);
            $total_witnesses = count(array_filter($witnesses));
            $current_count = 0;

            if ($total_witnesses > 0) {
                foreach ($witnesses as $witness) {
                    if (empty($witness)) continue;
                    
                    $current_count++;
                    $tableHTML .= formatWitnessDisplay($witness);
                    
                    if ($current_count < $total_witnesses) {
                        $tableHTML .= ", <br><br>";
                    } else {
                        $tableHTML .= "<br>";
                    }
                }
            } else {
                $tableHTML .= 'No witness';
            }
            $tableHTML .= '</td>';
            
            // Description, Guard Name, Action
            $tableHTML .= '<td>' . htmlspecialchars($row['description']) . '</td>';
            $tableHTML .= '<td>' . htmlspecialchars($row['guard_name']) . '</td>';
            $tableHTML .= '<td>';
            $tableHTML .= '<button onclick="confirmEscalation(' . $row['id'] . ')" class="btn btn-primary btn-sm">Escalate to Facilitator</button>';
            $tableHTML .= '</td>';
            $tableHTML .= '</tr>';
        }
    } else {
        $tableHTML = '<tr><td colspan="6" class="no-records-found">No records found</td></tr>';
    }
    
    // Pagination HTML
    $paginationHTML = '';
    if ($total_pages > 1) {
        $paginationHTML .= '<nav aria-label="Page navigation">';
        $paginationHTML .= '<ul class="pagination justify-content-center">';
        
        // Previous buttons
        if ($page > 1) {
            $paginationHTML .= '<li class="page-item">';
            $paginationHTML .= '<a class="page-link" href="javascript:void(0)" onclick="loadPage(1)" aria-label="First">';
            $paginationHTML .= '<span aria-hidden="true">&laquo;&laquo;</span>';
            $paginationHTML .= '</a>';
            $paginationHTML .= '</li>';
            $paginationHTML .= '<li class="page-item">';
            $paginationHTML .= '<a class="page-link" href="javascript:void(0)" onclick="loadPage(' . ($page - 1) . ')" aria-label="Previous">';
            $paginationHTML .= '<span aria-hidden="true">&laquo;</span>';
            $paginationHTML .= '</a>';
            $paginationHTML .= '</li>';
        }
        
        // Page numbers
        $visible_pages = 5;
        $half = floor($visible_pages / 2);
        
        // Calculate start and end page numbers to display
        $start_page = max(1, $page - $half);
        $end_page = min($total_pages, $start_page + $visible_pages - 1);
        
        // Adjust start page if we're near the end
        if ($end_page - $start_page + 1 < $visible_pages) {
            $start_page = max(1, $end_page - $visible_pages + 1);
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active = ($i == $page) ? 'active' : '';
            $paginationHTML .= '<li class="page-item ' . $active . '">';
            $paginationHTML .= '<a class="page-link" href="javascript:void(0)" onclick="loadPage(' . $i . ')">' . $i . '</a>';
            $paginationHTML .= '</li>';
        }
        
        // Next buttons
        if ($page < $total_pages) {
            $paginationHTML .= '<li class="page-item">';
            $paginationHTML .= '<a class="page-link" href="javascript:void(0)" onclick="loadPage(' . ($page + 1) . ')" aria-label="Next">';
            $paginationHTML .= '<span aria-hidden="true">&raquo;</span>';
            $paginationHTML .= '</a>';
            $paginationHTML .= '</li>';
            $paginationHTML .= '<li class="page-item">';
            $paginationHTML .= '<a class="page-link" href="javascript:void(0)" onclick="loadPage(' . $total_pages . ')" aria-label="Last">';
            $paginationHTML .= '<span aria-hidden="true">&raquo;&raquo;</span>';
            $paginationHTML .= '</a>';
            $paginationHTML .= '</li>';
        }
        
        $paginationHTML .= '</ul>';
        $paginationHTML .= '</nav>';
    }
    
    // Return JSON response
    echo json_encode([
        'tableHTML' => $tableHTML,
        'paginationHTML' => $paginationHTML,
        'total_records' => $total_records,
        'current_page' => $page,
        'total_pages' => $total_pages
    ]);
    
    exit;
}

// Handle the escalation action via AJAX
if(isset($_POST['report_id'])) {
    // Your existing escalation code here
    // This code should be in dean_view_incident_reports_handler.php
    exit;
}

// Initial values for non-AJAX request
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
?>

<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Incident Reports - Dean View</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
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


/* Search and Filter Form Styles */
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
        padding:20px;
    }

    .table th:nth-child(2), /* Date Reported */
    .table td:nth-child(2) {
        padding:20px;
    }

    .table th:nth-child(3), /* Place, Date & Time */
    .table td:nth-child(3) {
    }

   /* Description - making it wider */
    .table td:nth-child(4) {
        text-align: left;
        white-space: normal;
        min-width: 250px;
    }

    .table th:nth-child(4){
        padding:20px;
    }

    .table th:nth-child(5), /* Involvement */
    .table td:nth-child(5) {
        padding:20px;
    }

    .table th:nth-child(6), /* Status */
    .table td:nth-child(6) {
        padding:20px;
    }

    .table th:nth-child(7), /* Action */
    .table td:nth-child(7) {
        padding:20px;
    }


/* Actions cell specific styling */
.actions-cell {
    display: flex;
    justify-content: center;
    gap: 8px;
}


.status-pending { background-color: #ffd700; color: #000; }
.status-processing { background-color: #87ceeb; color: #000; }
.status-meeting { background-color: #98fb98; color: #000; }
.status-resolved { background-color: #90EE90; color: #000; }
.status-rejected { background-color: #ff6b6b; color: #fff; }

/* New styles for search and filter components */
.search-and-filter-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.search-container {
    position: relative;
    width: 300px;
}

.search-container input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border-radius: 10px;
    border: 1px solid #ced4da;
    font-size: 14px;
    transition: all 0.3s;
}

.search-container input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 158, 96, 0.25);
    border-color: #009E60;
}

.search-container .search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.sort-container {
    align-items: center;
}

.sort-container label {
    margin-right: 10px;
    font-weight: 500;
}

.sort-container select {
    padding: 8px 15px;
    border-radius: 20px;
    border: 1px solid #ced4da;
    background-color: white;
    font-size: 14px;
    cursor: pointer;
    outline: none;
}

.sort-container select:focus {
    box-shadow: 0 0 0 2px rgba(0, 158, 96, 0.25);
    border-color: #009E60;
}

.no-records-found {
    text-align: center;
    padding: 20px;
    font-size: 16px;
    color: #6c757d;
    background-color: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
}

/* Mobile Responsive */
@media screen and (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 20px auto;
    }

    /* Responsive table styles */
    .table-responsive .table {
        display: block;
        width: 100%;
    }
    
    .table-responsive thead {
        display: none;
    }
    
    .table-responsive tbody {
        display: block;
        width: 100%;
    }
    
    .table-responsive tr {
        display: block;
        width: 100%;
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 0;
        background-color: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .table-responsive td {
        width: 100%;
        padding: 10px 15px;
        text-align: left;
        border: none;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table-responsive td:last-child {
        border-bottom: none;
    }
    
    .table-responsive td:before {
        content: attr(data-label);
        width: 40%;
        font-weight: 600;
        margin-right: 10px;
    }
    
    .search-and-filter-container {
        flex-direction: column;
        gap: 15px;
    }
    
    .search-container {
        width: 100%;
    }
    
    .sort-container {
        width: 100%;
        justify-content: space-between;
    }
    
    h2 {
        font-size: 1.5rem;
    }
}

/* Improved Mobile Responsive Table Styles */
@media screen and (max-width: 768px) {
    /* Container adjustments */
    .container {
        padding: 15px;
        margin: 15px auto;
        width: 95%;
    }
    
    /* Table structure changes for mobile */
    .table-responsive {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }
    
    .table-responsive .table {
        display: block;
        width: 100%;
    }
    
    .table-responsive thead {
        display: none; /* Hide the header row */
    }
    
    .table-responsive tbody {
        display: block;
        width: 100%;
    }
    
    .table-responsive tr {
        display: block;
        width: 100%;
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        background-color: #fff;
    }
    
    .table-responsive td {
        display: flex;
        width: 100%;
        padding: 12px 15px;
        text-align: left;
        border: none;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        align-items: center;
    }
    
    .table-responsive td:last-child {
        border-bottom: none;
    }
    
    /* Create a data-label attribute that shows before each cell */
    .table-responsive td:before {
        content: attr(data-label);
        width: 40%;
        font-weight: 600;
        color: #333;
        margin-right: 10px;
        flex-shrink: 0;
    }
    
    /* Better spacing for mobile button layout */
    .table-responsive td:last-child {
        justify-content: flex-start;
    }
    
    /* Fix button sizes on mobile */
    .table-responsive .btn {
        padding: 8px 12px;
        font-size: 13px;
        white-space: nowrap;
    }
    
    /* Search and filter for mobile */
    .search-and-filter-container {
        flex-direction: column;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .search-container {
        width: 100%;
    }
    
    .sort-container {
        width: 100%;
        display: flex;
        align-items: center;
    }
    
    .sort-container label {
        width: 80px;
    }
    
    .sort-container select {
        flex-grow: 1;
    }
    
    /* Pagination for mobile */
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .pagination .page-item {
        margin-bottom: 5px;
    }
    
    /* Ensure description text wraps properly */
    .table-responsive td:nth-child(4) {
        white-space: normal;
        word-break: break-word;
    }
    
    /* Fix for the records indicator */
    #recordsIndicator {
        margin-top: 10px;
        text-align: center;
    }
}
    </style>
</head>
<body>
<div class="container mt-5">
    <a href="dean_homepage.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        <span>Back</span>
    </a>
    
    <h2>Pending Incident Reports</h2>
    
    <!-- Search and Filter Section -->
<div class="search-and-filter-container">
    <div class="search-container">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchInput" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
    </div>
    
    <div class="sort-container">
        <label for="sortOrder">Sort by:</label>
        <select id="sortOrder">
            <option value="desc" <?php echo $sort == 'desc' ? 'selected' : ''; ?>>Newest</option>
            <option value="asc" <?php echo $sort == 'asc' ? 'selected' : ''; ?>>Oldest</option>
        </select>
        <a href="?" class="btn btn-secondary ml-2">Reset Filters</a>
    </div>
</div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date Reported</th>
                    <th>Involved Student/s</th>
                    <th>Witness/es</th>
                    <th>Description</th>
                    <th>Reported By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <!-- Table content will be loaded via AJAX -->
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
<div id="paginationContainer">
    <!-- Pagination will be loaded via AJAX -->
</div>

<!-- Records Indicator -->
<div id="recordsIndicator" class="text-center mt-2 mb-3" style="color: #555; font-size: 14px; font-weight: 500;">
    <!-- Records count will be loaded via AJAX -->
</div>

</div>

<script>
let currentPage = <?php echo $page; ?>;
let currentSearch = '<?php echo htmlspecialchars($search); ?>';
let currentSort = '<?php echo $sort; ?>';
let searchTimer; // For debouncing search input

// Load data on page load
$(document).ready(function() {
    loadData();
    
    // Add event listeners with debounce for search
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            currentSearch = $('#searchInput').val().trim();
            currentPage = 1; // Reset to first page on search
            loadData();
        }, 100); // Wait 300ms after user stops typing
    });
    
    $('#sortOrder').on('change', function() {
        currentSort = $(this).val();
        loadData();
    });
});

// Function to load data via AJAX
// Function to load data via AJAX
function loadData() {
    $.ajax({
        url: window.location.pathname,
        type: 'GET',
        data: {
            page: currentPage,
            search: currentSearch,
            sort: currentSort
        },
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            // Update the table and pagination
            $('#tableBody').html(response.tableHTML);
            $('#paginationContainer').html(response.paginationHTML);
            
            // Update records indicator
            const startRecord = response.total_records > 0 ? (response.current_page - 1) * 10 + 1 : 0;
            const endRecord = Math.min(response.current_page * 10, response.total_records);
            $('#recordsIndicator').html(`Showing ${startRecord} to ${endRecord} of ${response.total_records} records`);
            
            // Make the table responsive for mobile
            makeTableResponsive();
            
            // Update browser URL without reloading the page
            updateBrowserUrl();
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while loading data.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

// Function to load a specific page
function loadPage(page) {
    currentPage = page;
    loadData();
}

// Function to update browser URL without page reload
function updateBrowserUrl() {
    const url = new URL(window.location.href);
    url.searchParams.set('page', currentPage);
    url.searchParams.set('search', currentSearch);
    url.searchParams.set('sort', currentSort);
    
    // Update URL without reloading the page
    window.history.pushState({}, '', url.toString());
}

// Function to make the table responsive on mobile
function makeTableResponsive() {
    if (window.innerWidth <= 768) {
        // Add data-label attribute to each td based on the th content
        $('table thead th').each(function(index) {
            const thText = $(this).text().trim();
            $('table tbody tr').each(function() {
                $(this).find('td').eq(index).attr('data-label', thText);
            });
        });
    }
}

// Function to handle window resize for responsive design
$(window).resize(function() {
    makeTableResponsive();
});

function confirmEscalation(reportId) {
    Swal.fire({
        title: 'Escalate to Facilitator?',
        text: 'Are you sure you want to escalate this report to the facilitator?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, escalate it!'
    }).then((result) => {
        if (result.isConfirmed) {
            submitEscalation(reportId);
        }
    });
}

function submitEscalation(reportId) {
    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: { report_id: reportId },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    loadData(); // Reload the data instead of page refresh
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
            console.error("Response Text: ", jqXHR.responseText);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while processing your request.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}
</script>
</body>
</html>