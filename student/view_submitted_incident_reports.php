<?php
session_start();
include '../db.php'; 

// Ensure the user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type']; 

// Initialize variables
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get sort order from URL parameter, default to DESC if not set
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
// Validate sort order to prevent SQL injection
$sort_order = ($sort_order === 'ASC') ? 'ASC' : 'DESC';

// Get status filter from URL parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Count total records for pagination
$count_query = "
    SELECT COUNT(*) as total 
    FROM incident_reports 
    WHERE reporters_id = ? AND reported_by_type = ?
";

// Add status filter to count query if status is selected
$params = [$user_id, $user_type];
$types = "is";
if (!empty($status_filter)) {
    $count_query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add search to count query if provided
if (!empty($search)) {
    $count_query .= " AND (
        id LIKE CONCAT('%', ?, '%') OR
        place LIKE CONCAT('%', ?, '%') OR
        description LIKE CONCAT('%', ?, '%') OR
        status LIKE CONCAT('%', ?, '%')
    )";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "ssss";
}

$count_stmt = $connection->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query for incident reports
$query = "
SELECT ir.*,
       GROUP_CONCAT(DISTINCT 
           CASE 
               WHEN sv.student_id IS NOT NULL AND s.first_name IS NOT NULL THEN 
                   CONCAT(s.first_name, ' ', s.last_name, ' (', s.student_id, ')')
               WHEN sv.student_name IS NOT NULL THEN 
                   CONCAT(sv.student_name, ' (Non-CEIT Student)')
               WHEN s.student_id IS NULL THEN 
                   CONCAT(s.first_name, ' ', s.last_name, ' (Non-CEIT Student)')
           END
           ORDER BY 
               CASE 
                   WHEN sv.student_id IS NOT NULL THEN 1 
                   ELSE 2 
               END
           SEPARATOR ',<br><br>'
       ) as involved_students,
       GROUP_CONCAT(DISTINCT 
           CASE 
               WHEN iw.witness_type = 'student' AND iw.witness_id IS NOT NULL THEN 
                   CONCAT(s2.first_name, ' ', s2.last_name, ' (', s2.student_id, ')')
               WHEN iw.witness_type = 'student' AND iw.witness_id IS NULL THEN 
                   CONCAT(iw.witness_name, ' (Non-CEIT Student)')
               WHEN iw.witness_type = 'staff' THEN 
                   CONCAT(iw.witness_name, ' (Staff - ', COALESCE(iw.witness_email, 'No email'), ')')
           END
           SEPARATOR ',<br><br>'
       ) as witnesses,
       GROUP_CONCAT(DISTINCT CONCAT(
           a.first_name, 
           CASE 
               WHEN a.middle_initial IS NOT NULL AND a.middle_initial != '' 
               THEN CONCAT(' ', a.middle_initial, '. ')
               ELSE ' '
           END,
           a.last_name,
           ' (', sec.year_level, ' - ', sec.section_no, ')'
       ) SEPARATOR ',<br><br>') as advisers
FROM incident_reports ir
LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
LEFT JOIN tbl_student s ON sv.student_id = s.student_id
LEFT JOIN sections sec ON s.section_id = sec.id
LEFT JOIN courses c ON sec.course_id = c.id
LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
LEFT JOIN tbl_student s2 ON iw.witness_id = s2.student_id
LEFT JOIN sections sec2 ON s2.section_id = sec2.id
LEFT JOIN courses c2 ON sec2.course_id = c2.id
LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
WHERE ir.reporters_id = ?
AND ir.reported_by_type = ?";

// Add status filter if selected
if (!empty($status_filter)) {
    $query .= " AND ir.status = ?";
}

// Add search filter if not empty
if (!empty($search)) {
    $query .= " AND (
        ir.id LIKE CONCAT('%', ?, '%') OR
        ir.place LIKE CONCAT('%', ?, '%') OR
        ir.description LIKE CONCAT('%', ?, '%') OR
        ir.status LIKE CONCAT('%', ?, '%') OR
        sv.student_name LIKE CONCAT('%', ?, '%') OR
        iw.witness_name LIKE CONCAT('%', ?, '%')
    )";
}

// Add GROUP BY and ORDER BY clauses
$query .= " GROUP BY ir.id ORDER BY ir.date_reported $sort_order LIMIT ? OFFSET ?";

// Parameters for the query
$params = [$user_id, $user_type];
$types = "is";

// Add status parameter if filter is applied
if (!empty($status_filter)) {
    $params[] = $status_filter;
    $types .= "s";
}

// Add search parameters if search is applied
if (!empty($search)) {
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= "ssssss";
}

// Add limit and offset parameters
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $connection->prepare($query);

if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

// Calculate showing range for display
$showing_start = min($total_records, $offset + 1);
$showing_end = min($total_records, $offset + $records_per_page);

// Function to convert text to proper case
function toProperCase($string) {
    // Split the string by spaces, dashes, and other word separators
    $words = preg_split('/\s+|-|\/|\./', $string);
    $properWords = [];
    
    foreach ($words as $word) {
        if (empty($word)) continue;
        
        // Skip handling parentheses content
        if (preg_match('/\([^)]+\)/', $word)) {
            $properWords[] = $word;
            continue;
        }
        
        // Convert to lowercase first, then uppercase the first letter
        $properWords[] = ucfirst(strtolower($word));
    }
    
    // Recombine words, preserving original separators
    $result = $string;
    foreach ($properWords as $i => $word) {
        // Replace original word with proper case version
        $pattern = '/\b' . preg_quote($words[$i], '/') . '\b/';
        $result = preg_replace($pattern, $word, $result, 1);
    }
    
    return $result;
}

// Function to format names to proper case while preserving special formats
function formatNames($nameStr) {
    if (empty($nameStr)) return '';
    
    // Split by the separator
    $names = explode(',<br><br>', $nameStr);
    $formattedNames = [];
    
    foreach ($names as $name) {
        // Extract parts inside parentheses to preserve them
        preg_match('/^(.*?)(\s*\([^)]+\)\s*)$/', $name, $matches);
        
        if (!empty($matches)) {
            $nameOnly = $matches[1];
            $parenthesesPart = $matches[2];
            
            // Format the name part only
            $formattedName = toProperCase($nameOnly) . $parenthesesPart;
        } else {
            // If no parentheses, just format the whole name
            $formattedName = toProperCase($name);
        }
        
        $formattedNames[] = $formattedName;
    }
    
    return implode(',<br><br>', $formattedNames);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Incident Reports</title>
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
            --btn-color: #0d693e; /* Changed from blue to green */
            --btn-hover-color: #094e2e; /* Darker green for hover */
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
            }

             /* Place, Date & Time */
             
            .table td:nth-child(3) {
                width: 20%;
                text-align: left;
                white-space: normal;
                min-width: 200px;
            }

            .table th:nth-child(3){
                 width: 15%;
                padding:20px;
            }

           /* Description - making it wider */
           .table th:nth-child(4),
            .table td:nth-child(4) {
                width: 15%;
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
            text-align: center;
        }

        td[data-label="Actions"] {
            width: 120px;
            text-align: center;
        }

        /* No Records Found Styling - Improved */
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
            margin-bottom: 10px;
        }

        .no-records-subtext {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
        }

        /* Green Button Styles - Replacing blue buttons */
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

        .btn-primary.btn-sm, 
        .btn-primary.btn-sm-2 {
            background-color: var(--btn-color) !important;
            border-color: var(--btn-color) !important;
        }

        .btn-primary.btn-sm:hover,
        .btn-primary.btn-sm-2:hover {
            background-color: var(--btn-hover-color) !important;
            border-color: var(--btn-hover-color) !important;
        }

        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            color: #fff;
            background-color: #5a6268;
            border-color: #545b62;
        }

        .btn-outline-secondary {
            color: var(--text-color);
            border-color: #ced4da;
        }

        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            color: var(--text-color);
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
                white-space: normal;
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

            .btn-primary.btn-sm, 
            .btn-primary.btn-sm-2 {
                width: 100%;
                padding: 10px;
                text-align: center;
                border-radius: 6px;
            }
            
            /* Improve no records display for mobile */
            .no-records-container {
                padding: 30px 15px;
            }
            
            .no-records-icon {
                font-size: 36px;
            }
            
            .no-records-text {
                font-size: 16px;
            }
            
            .no-records-subtext {
                font-size: 13px;
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
            
            /* Filter form improvements for very small screens */
            .row {
                margin-right: -5px;
                margin-left: -5px;
            }
            
            .col-md-2 {
                padding: 0 5px;
                margin-bottom: 8px;
            }
            
            .form-control {
                font-size: 13px;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) {
            .form-control, 
            .btn-primary,
            .btn-primary.btn-sm,
            .btn-primary.btn-sm-2 {
                min-height: 44px;
            }

            .table td {
                padding: 12px 15px;
            }
        }

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
            display: inline-block;
            text-align: left;
            width: 100%;
            line-height: 1.8;
        }

        @media screen and (max-width: 768px) {
            .table td[data-label="Students Involved"] div,
            .table td[data-label="Witnesses"] div {
                text-align: right;
            }
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

        @media screen and (max-width: 768px) {
            .records-info {
                text-align: center;
                margin-top: 10px;
                margin-bottom: 10px;
            }
        }
</style>
</head>
<body>
    <div class="container mt-5">
        <a href="student_homepage.php" class="modern-back-button">
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
                    <select name="status" class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="For Meeting" <?php echo $status_filter === 'For Meeting' ? 'selected' : ''; ?>>For Meeting</option>
                        <option value="Referred" <?php echo $status_filter === 'Referred' ? 'selected' : ''; ?>>Referred</option>
                        <option value="Settled" <?php echo $status_filter === 'Settled' ? 'selected' : ''; ?>>Settled</option>
                    </select> 
                </div> 
                <div class="col-md-2">
                    <select name="sort_order" class="form-control" id="sortOrder">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="?" class="btn btn-secondary">Reset Filters</a>
                </div>
                <!-- Preserve search parameter -->
                <?php if (!empty($search)): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
            </div>
        </form>

        <?php if ($result->num_rows > 0): ?>
   <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Incident Place - <br>Date, Time</th>
                        <th>Description</th>
                        <th>Student/s <br>Involved</th>
                        <th>Witness/es</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
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
            
                        // Format students into proper case
                        $formatted_students = '';
                        if (!empty($row['involved_students'])) {
                            $formatted_students = formatNames($row['involved_students']);
                        } else {
                            $formatted_students = 'No students involved';
                        }
                            
                        // Format witnesses into proper case
                        $formatted_witnesses = '';
                        if (!empty($row['witnesses'])) {
                            $formatted_witnesses = formatNames($row['witnesses']);
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
                            <a href="view_incident_details-student.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm-2"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
    <div class="no-records-container">
        <div class="no-records-icon">
            <i class="fas fa-folder-open"></i>
        </div>
        <div class="no-records-text">No incident reports found</div>
        <?php if (!empty($search) || !empty($status_filter)): ?>
        <div class="no-records-subtext">Try adjusting your search or filter criteria</div>
        <a href="view_submitted_incident_reports.php" class="btn btn-outline-secondary mt-2">Clear all filters</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Hidden No Results Template (for search function) -->
    <div id="noSearchResults" style="display: none;">
    <div class="no-records-container">
        <div class="no-records-icon">
            <i class="fas fa-search"></i>
        </div>
        <div class="no-records-text">No data available</div>
    </div>
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
    <?php if ($total_records > 0): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1<?php echo !empty($search) ? "&search=$search" : ""; echo !empty($status_filter) ? "&status=$status_filter" : ""; echo "&sort_order=$sort_order"; ?>">First</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; echo !empty($search) ? "&search=$search" : ""; echo !empty($status_filter) ? "&status=$status_filter" : ""; echo "&sort_order=$sort_order"; ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; echo !empty($search) ? "&search=$search" : ""; echo !empty($status_filter) ? "&status=$status_filter" : ""; echo "&sort_order=$sort_order"; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; echo !empty($search) ? "&search=$search" : ""; echo !empty($status_filter) ? "&status=$status_filter" : ""; echo "&sort_order=$sort_order"; ?>">Next</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $total_pages; echo !empty($search) ? "&search=$search" : ""; echo !empty($status_filter) ? "&status=$status_filter" : ""; echo "&sort_order=$sort_order"; ?>">Last</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    </div>

<script>
$(document).ready(function() {
    // Search functionality
    $("#searchInput").keyup(function() {
        var searchText = $(this).val().toLowerCase();
        var visibleRows = 0;
        
        $(".table tbody tr").each(function() {
            if (!$(this).is("#noRecordsRow") && !$(this).is("#noSearchResultsRow")) { // Skip the no records rows
                var row = $(this);
                
                // Get text from all relevant cells
                var dateReported = row.find('td[data-label="Date Reported"]').text().toLowerCase();
                var incidentDate = row.find('td[data-label="Place, Date & Time"]').text().toLowerCase();
                var description = row.find('td[data-label="Students Involved"]').text().toLowerCase();
                var involvement = row.find('td[data-label="Witnesses"]').text().toLowerCase();
                var status = row.find('td[data-label="Status"]').text().toLowerCase();
                
                // Combine all searchable content
                var rowContent = dateReported + ' ' + incidentDate + ' ' + 
                               description + ' ' + involvement + ' ' + status;
                
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
            }
        });
        
        // Show no results message if nothing matches
        if (visibleRows === 0 && searchText !== '') {
            $("#noSearchResultsRow").remove(); // Remove any existing no results row
            $("#tableBody").append('<tr id="noSearchResultsRow"><td colspan="7">' + 
                $("#noSearchResults").html() + '</td></tr>');
        } else if (visibleRows > 0 || searchText === '') {
            $("#noSearchResultsRow").remove();
        }
    });
});
</script>
</body>
</html>