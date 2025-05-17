<?php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

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

// Fetch distinct referral reasons
$stmt = $connection->query("SELECT DISTINCT reason_for_referral FROM referrals");
$referralReasons = [];
while($row = $stmt->fetch_assoc()) {
    $referralReasons[] = $row['reason_for_referral'];
}

$filterReason = isset($_GET['filter_reason']) ? $_GET['filter_reason'] : '';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Prepare base query
$sql = "";
$params = [];
$types = "";

// Count total records for pagination
if ($filterReason) {
    $count_sql = "SELECT COUNT(*) as total FROM (
        SELECT incident_report_id 
        FROM referrals 
        WHERE incident_report_id IS NOT NULL AND reason_for_referral = ?
        GROUP BY incident_report_id
        UNION ALL
        SELECT id 
        FROM referrals 
        WHERE incident_report_id IS NULL AND reason_for_referral = ?
    ) as count_table";
    $count_stmt = $connection->prepare($count_sql);
    $count_stmt->bind_param("ss", $filterReason, $filterReason);
} else {
    $count_sql = "SELECT COUNT(*) as total FROM (
        SELECT incident_report_id 
        FROM referrals 
        WHERE incident_report_id IS NOT NULL
        GROUP BY incident_report_id
        UNION ALL
        SELECT id 
        FROM referrals 
        WHERE incident_report_id IS NULL
    ) as count_table";
    $count_stmt = $connection->prepare($count_sql);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Calculate record range being displayed
$start_record = ($page - 1) * $records_per_page + 1;
$end_record = min($start_record + $records_per_page - 1, $total_records);

// Adjust start_record when there are no records
if ($total_records == 0) {
    $start_record = 0;
}

// Force at least 1 page even if no records
if ($total_pages < 1) {
    $total_pages = 1;
}

// Main query with pagination
if ($filterReason) {
    // For grouped incident reports (with incident_report_id)
    $sql = "SELECT r1.* FROM (
            SELECT incident_report_id, MIN(date) as date, 
                   GROUP_CONCAT(CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name, ' (', course_year, ')') SEPARATOR '\n\n') as names,
                   MIN(reason_for_referral) as reason_for_referral,
                   GROUP_CONCAT(violation_details SEPARATOR '; ') as violation_details,
                   GROUP_CONCAT(other_concerns SEPARATOR '; ') as other_concerns,
                   NULL as id
            FROM referrals 
            WHERE incident_report_id IS NOT NULL AND reason_for_referral = ?
            GROUP BY incident_report_id
            UNION ALL
            SELECT NULL as incident_report_id, date, 
                   CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name, ' (', course_year, ')') as names,
                   reason_for_referral, violation_details, other_concerns, id
            FROM referrals 
            WHERE incident_report_id IS NULL AND reason_for_referral = ?
        ) r1 ORDER BY date " . $sortOrder . " LIMIT ? OFFSET ?";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ssii", $filterReason, $filterReason, $records_per_page, $offset);
} else {
    $sql = "SELECT r1.* FROM (
            SELECT incident_report_id, MIN(date) as date, 
                   GROUP_CONCAT(CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name, ' (', course_year, ')') SEPARATOR '\n\n') as names,
                   MIN(reason_for_referral) as reason_for_referral,
                   GROUP_CONCAT(violation_details SEPARATOR '; ') as violation_details,
                   GROUP_CONCAT(other_concerns SEPARATOR '; ') as other_concerns,
                   NULL as id
            FROM referrals 
            WHERE incident_report_id IS NOT NULL
            GROUP BY incident_report_id
            UNION ALL
            SELECT NULL as incident_report_id, date, 
                   CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name, ' (', course_year, ')') as names,
                   reason_for_referral, violation_details, other_concerns, id
            FROM referrals 
            WHERE incident_report_id IS NULL
        ) r1 ORDER BY date " . $sortOrder . " LIMIT ? OFFSET ?";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ii", $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$referrals = $result->fetch_all(MYSQLI_ASSOC);

// Convert the data to JSON for JavaScript
$referralsJson = json_encode($referrals);
$totalPagesJson = json_encode($total_pages);
$currentPageJson = json_encode($page);
$totalRecordsJson = json_encode($total_records);
$startRecordJson = json_encode($start_record);
$endRecordJson = json_encode($end_record);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Student Referrals</title>
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
        width: 30%;
        text-align:center;
        white-space: normal;
        min-width: 250px;
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

td[data-label="Reason for Referral"] {
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
/* Filter section styles */
.filters-section {
    
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
  
}

.filters-row {
    display: flex;
    gap: 20px;
    align-items: flex-end; /* Aligns items at the bottom */
}
.filter-group {
    flex: 1;
    max-width: 300px;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-color);
}

.filter-group select, .filter-group input {
    width: 100%;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    background-color: white;
    transition: all 0.3s ease;
}

.filter-group select:focus, .filter-group input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(13, 105, 62, 0.1);
}

/* Reset button */
.btn-reset {
    padding: 8px 20px;
    height: 38px;
    background-color: #f44336;
    border: none;
    border-radius: 6px;
    color: white;
    font-weight: 500;
    transition: all 0.2s ease;
    min-width: 120px;
}

.btn-reset:hover {
    background-color: #d32f2f;
    transform: translateY(-1px);
}

/* Generate PDF button */
.btn-generate-pdf {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-generate-pdf:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

.btn-generate-pdf i {
    font-size: 16px;
}

/* Table adjustments */
.table td, .table th {
    padding: 15px;
}

.table td:last-child {
    text-align: center;
}

/* No data message */
.no-data {
    text-align: center;
    padding: 30px;
    background-color: #f8f9fa;
    border-radius: 10px;
    font-size: 16px;
    color: #6c757d;
    margin: 20px 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: none;
}

.no-data i {
    font-size: 32px;
    margin-bottom: 10px;
    color: #adb5bd;
}

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .filters-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-group {
        max-width: 100%;
    }
    
    .btn-reset {
        width: 100%;
    }
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
         width: 95%;
        padding: 15px;
        margin: 15px auto;
        border-radius: 10px;
        overflow-x: hidden;
    }

    h2 {
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    .table {
        border: none;
        background: transparent;
        box-shadow: none;
    }

    .table thead {
        display: none; /* Hide header on mobile */
    }

    .table tbody {
        display: block;
    }

    .table thead {
        display: none;
    }

   .table tr {
        display: block;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }


    .table td {
        display: flex;
        flex-direction: column;
        text-align: right;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        min-height: 50px;
        position: relative;
        width: 100% !important;
    }
    


    .table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #444;
        padding-right: 10px;
        text-align: left;
        left: 15px;
        flex: 1;
    }

    .table td:last-child {
        border-bottom: none;
    }
    .table td:nth-child(3) {
        width: 30%;
        text-align:right;
        white-space: normal;
        min-width: 250px;
    }

    td[data-label="Date Reported"] {
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
        font-weight: 500;
    }

     td[data-label="Student Name(s)"] span.student-name {
        text-align: left;
        padding-left: 40%;
    }
     td[data-label="Reason for Referral"] {
        text-align: right;
        padding-left: 40%;
    }
    

    td[data-label="Actions"] {
        border-radius: 0 0 8px 8px;
        justify-content: flex-end;
    }


     .btn-generate-pdf {
        width: auto;
        margin-left: auto;
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

/* Highlight search results */
.highlight {
    background-color: #fff3cd;
    padding: 2px;
    border-radius: 3px;
}

.student-name {
    display: block;
    margin-bottom: 15px;  /* Adds space between each student */
    line-height: 1.4;     /* Improves readability */
}

/* Remove margin from the last student in the list */
.student-name:last-child {
    margin-bottom: 0;
}
</style>
</head>
<body>

<div class="container mt-5">
<a href="guidanceservice.html" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4"style="border-bottom: 3px solid #004d4d;">
       <h2>STUDENT REFERRALS</h2>
</div>
<div class="filters-section">
    <div class="filters-row">
        <div class="filter-group col-md-5">
            <label for="search_input">Search:</label>
            <input type="text" id="search_input" class="form-control" placeholder="Search names, reasons, dates...">
        </div>
        <div class="filter-group col-md-6">
            <label for="filter_reason">Filter by Reason:</label>
            <select id="filter_reason" class="form-control">
                <option value="">All Reasons</option>
                <?php foreach ($referralReasons as $reason): ?>
                    <option value="<?= htmlspecialchars($reason) ?>"><?= htmlspecialchars($reason) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group col-md-6">
            <label for="sort_order">Sort Order:</label>
            <select id="sort_order" class="form-control">
                <option value="DESC">Newest</option>
                <option value="ASC">Oldest</option>
            </select>
        </div>
        <!-- Reset button with no label div to match height -->
        <div class="filter-group col-md-6" style="justify-content: flex-end;">
            <label style="visibility: hidden;">Placeholder</label>
            <a href="?" class="btn btn-secondary">Reset Filters</a>
        </div>
    </div> 
</div>

       <table class="table table-striped" id="referrals_table">
           <thead>
               <tr>
                   <th>Date</th>
                   <th>Student Name(s)</th>
                   <th>Reason for Referral</th>
                   <th>Action</th>
               </tr>
           </thead>
          <tbody>
            <?php foreach ($referrals as $referral): ?>
            <tr>
                <td data-label="Date"><?= htmlspecialchars($referral['date']) ?></td>
                <td data-label="Student Name(s)">
                    <?php if (isset($referral['names'])): ?>
                        <?php
                        // Split the names by newline and create separate spans for each
                        $nameList = explode("\n\n", $referral['names']);
                        foreach ($nameList as $name) {
                            if (trim($name)) {
                                echo '<span class="student-name">' . htmlspecialchars(toProperCase(trim($name))) . '</span>';
                            }
                        }
                        ?>
                    <?php else: ?>
                        <?= htmlspecialchars(toProperCase($referral['first_name'] . ' ' . 
                            ($referral['middle_name'] ? $referral['middle_name'] . ' ' : '') . 
                            $referral['last_name'] . ' (' . $referral['course_year'] . ')')) ?>
                    <?php endif; ?>
                </td>
                <td data-label="Reason for Referral">
                    <?= htmlspecialchars($referral['reason_for_referral']) ?>
                    <?php 
                    // Clean up and deduplicate violation details
                    if (!empty($referral['violation_details'])) {
                        $violations = array_unique(array_map('trim', explode(';', $referral['violation_details'])));
                        $violations = array_filter($violations); // Remove empty values
                        if (!empty($violations)) {
                            $violation = reset($violations); // Get first non-empty value
                            if (!empty($violation)) {
                                echo '<br><strong>Specific Violation:</strong> ' . htmlspecialchars($violation);
                            }
                        }
                    }
                    
                    // Clean up and deduplicate other concerns
                    if (!empty($referral['other_concerns'])) {
                        $concerns = array_unique(array_map('trim', explode(';', $referral['other_concerns'])));
                        $concerns = array_filter($concerns); // Remove empty values
                        if (!empty($concerns)) {
                            $concern = reset($concerns); // Get first non-empty value
                            if (!empty($concern)) {
                                echo '<br><strong>Specific Concerns:</strong> ' . htmlspecialchars($concern);
                            }
                        }
                    }
                    ?>
                </td>
               
                <td data-label="Action">
                    <?php if (isset($referral['incident_report_id'])): ?>
                        <a href="generate_referral_pdf.php?id=<?= $referral['incident_report_id'] ?>" 
                        class="btn-generate-pdf" target="_blank">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </a>
                    <?php else: ?>
                        <a href="generate_referral_pdf.php?id=<?= $referral['id'] ?>" 
                        class="btn-generate-pdf" target="_blank">
                            <i class="fas fa-file-pdf"></i> Generate PDF
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
       </table>
       
       <!-- No data message -->
       <div class="no-data" id="no_data_message">
           <i class="fas fa-search"></i>
           <p>No matching records found</p>
       </div>

       <!-- Pagination info and navigation -->
<div class="pagination-container" id="pagination_container">
    <div class="pagination-info" id="pagination_info">
        <?php if ($total_records > 0): ?>
            Showing <?php echo $start_record; ?> - <?php echo $end_record; ?> out of <?php echo $total_records; ?> records
        <?php else: ?>
            No records found
        <?php endif; ?>
    </div>
    
    <nav aria-label="Page navigation">
        <ul class="pagination" id="pagination">
            <?php 
            // Maximum pages to show (not counting next/last)
            $max_visible_pages = 3;
            
            // Calculate starting page based on current page
            $start_page = max(1, min($page - floor($max_visible_pages/2), $total_pages - $max_visible_pages + 1));
            $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
            
            // Adjust if we're showing fewer than max pages
            if ($end_page - $start_page + 1 < $max_visible_pages) {
                $start_page = max(1, $end_page - $max_visible_pages + 1);
            }
            
            // Display numbered pages
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&filter_reason=<?php echo urlencode($filterReason); ?>&sort_order=<?php echo $sortOrder; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <!-- Next page (») -->
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&filter_reason=<?php echo urlencode($filterReason); ?>&sort_order=<?php echo $sortOrder; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">&raquo;</span>
                </li>
            <?php endif; ?>
            
            <!-- Last page (»») -->
            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $total_pages; ?>&filter_reason=<?php echo urlencode($filterReason); ?>&sort_order=<?php echo $sortOrder; ?>" aria-label="Last">
                        <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">&raquo;&raquo;</span>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
   </div>

<script>
// Store all referrals data
const allReferrals = <?= $referralsJson ?>;
const totalPages = <?= $totalPagesJson ?>;
const currentPage = <?= $currentPageJson ?>;
const totalRecords = <?= $totalRecordsJson ?>;
const startRecord = <?= $startRecordJson ?>;
const endRecord = <?= $endRecordJson ?>;

// Auto-run when document is ready
$(document).ready(function() {
    // Initialize dropdown first
    populateReasonDropdown();
    
    // Set current value in filters
    $('#filter_reason').val("<?= htmlspecialchars($filterReason) ?>");
    $('#sort_order').val("<?= htmlspecialchars($sortOrder) ?>");
    
    // Set up server-side filtering with form submission
    $('#filter_reason').on('change', function() {
        window.location.href = '?page=1&filter_reason=' + encodeURIComponent($(this).val()) + '&sort_order=' + $('#sort_order').val();
    });
    
    $('#sort_order').on('change', function() {
        window.location.href = '?page=' + currentPage + '&filter_reason=' + encodeURIComponent($('#filter_reason').val()) + '&sort_order=' + $(this).val();
    });
    
    // Client-side search only
    $('#search_input').on('input', function() {
        const searchText = $(this).val().toLowerCase().trim();
        if (searchText.length > 0) {
            filterReferralsClientSide(searchText);
        } else {
            // Reset to server-side data
            resetClientSideFilter();
        }
    });
});

// Function to reset all filters (server-side)
function resetFilters() {
    window.location.href = '?';
}

// Function to reset client-side filtering
function resetClientSideFilter() {
    // Refresh the table with original data
    const tbody = $('#referrals_table tbody');
    tbody.empty();
    
    allReferrals.forEach(referral => {
        appendReferralRow(tbody, referral);
    });
    
    // Update pagination info and visibility
    $('#pagination_container').show();
    $('#pagination_info').text(`Showing ${startRecord} - ${endRecord} out of ${totalRecords} records`);
    
    // Hide "no data" message if needed
    if (allReferrals.length > 0) {
        $('#no_data_message').hide();
        $('#referrals_table').show();
    } else {
        $('#referrals_table').hide();
        $('#no_data_message').show();
    }
}

// Function to filter referrals client-side (for search only)
function filterReferralsClientSide(searchText) {
    // Filter the data
    let filteredData = allReferrals.filter(referral => {
        // Search in all text fields
        return (referral.date && referral.date.toLowerCase().includes(searchText)) ||
            (referral.names && referral.names.toLowerCase().includes(searchText)) ||
            (referral.reason_for_referral && referral.reason_for_referral.toLowerCase().includes(searchText)) ||
            (referral.violation_details && referral.violation_details.toLowerCase().includes(searchText)) ||
            (referral.other_concerns && referral.other_concerns.toLowerCase().includes(searchText));
    });
    
    // Clear the table
    const tbody = $('#referrals_table tbody');
    tbody.empty();
    
    // Show "no data" message if needed
    if (filteredData.length === 0) {
        $('#referrals_table').hide();
        $('#no_data_message').show();
        $('#pagination_container').hide();
        return;
    }
    
    // Hide "no data" message and show table
    $('#no_data_message').hide();
    $('#referrals_table').show();
    
    // Update pagination info
    $('#pagination_container').show();
    $('#pagination_info').text(`Showing 1 - ${filteredData.length} out of ${filteredData.length} filtered records`);
    
    // Rebuild pagination for client-side filtering
    buildClientSidePagination(filteredData.length, 1);
    
    // Populate table with filtered data
    filteredData.forEach(referral => {
        appendReferralRow(tbody, referral, searchText);
    });
}

// Function to build client-side pagination display
function buildClientSidePagination(totalItems, currentPage) {
    const pagination = $('#pagination');
    pagination.empty();
    
    // For client-side filtering, we just show a simple "1" since all results are on one page
    const pageItem = $('<li>').addClass('page-item active');
    const pageLink = $('<a>').addClass('page-link').attr('href', '#').text('1');
    pageItem.append(pageLink);
    pagination.append(pageItem);
    
    // Add disabled next/last buttons
    const nextItem = $('<li>').addClass('page-item disabled');
    const nextLink = $('<span>').addClass('page-link').html('&raquo;');
    nextItem.append(nextLink);
    pagination.append(nextItem);
    
    const lastItem = $('<li>').addClass('page-item disabled');
    const lastLink = $('<span>').addClass('page-link').html('&raquo;&raquo;');
    lastItem.append(lastLink);
    pagination.append(lastItem);
}

// Function to append a referral row to the table
function appendReferralRow(tbody, referral, searchText = '') {
    let row = $('<tr>');
    
    // Date cell
    let dateCell = $('<td>').attr('data-label', 'Date').text(referral.date);
    
    // Names cell
    let namesCell = $('<td>').attr('data-label', 'Student Name(s)');
    if (referral.names) {
        // Split the names by newline and format each as a separate block
        const nameList = referral.names.split('\n\n');
        nameList.forEach(name => {
            if (name.trim()) {
                // Using toProperCase function in PHP won't work here since we're in JavaScript
                // So we need to implement proper case in JS directly
                const properName = name.trim().toLowerCase().split(' ').map(part => {
                    // Check if it's a middle initial with period (like "C.")
                    if (part.length === 2 && part.endsWith('.')) {
                        return part.toUpperCase();
                    }
                    return part.charAt(0).toUpperCase() + part.slice(1);
                }).join(' ');
                
                namesCell.append($('<span>').addClass('student-name').text(properName));
            }
        });
    } else if (referral.first_name) {
        // For single referrals
        const fullName = `${referral.first_name} ${referral.middle_name || ''} ${referral.last_name} (${referral.course_year})`;
        const properFullName = fullName.trim().toLowerCase().split(' ').map(part => {
            // Check if it's a middle initial with period (like "C.")
            if (part.length === 2 && part.endsWith('.')) {
                return part.toUpperCase();
            }
            return part.charAt(0).toUpperCase() + part.slice(1);
        }).join(' ');
        
        namesCell.append($('<span>').addClass('student-name').text(properFullName));
    }
    
    // Reason cell
    let reasonCell = $('<td>').attr('data-label', 'Reason for Referral');
    reasonCell.text(referral.reason_for_referral);
    
    // Add violation details if available
    if (referral.violation_details) {
        const violations = [...new Set(referral.violation_details.split(';').map(v => v.trim()))].filter(Boolean);
        if (violations.length > 0) {
            const violation = violations[0];
            reasonCell.append($('<br>'))
               .append($('<strong>').text('Specific Violation: '))
               .append(document.createTextNode(violation));
        }
    }
    
    // Add other concerns if available
    if (referral.other_concerns) {
        const concerns = [...new Set(referral.other_concerns.split(';').map(c => c.trim()))].filter(Boolean);
        if (concerns.length > 0) {
            const concern = concerns[0];
            reasonCell.append($('<br>'))
               .append($('<strong>').text('Specific Concerns: '))
               .append(document.createTextNode(concern));
        }
    }
    
    // Action cell with PDF button
    let actionCell = $('<td>').attr('data-label', 'Action');
    let pdfLink = $('<a>')
        .addClass('btn-generate-pdf')
        .attr('target', '_blank')
        .html('<i class="fas fa-file-pdf"></i> Generate PDF');
        
    if (referral.incident_report_id) {
        pdfLink.attr('href', 'generate_referral_pdf.php?id=' + referral.incident_report_id);
    } else {
        pdfLink.attr('href', 'generate_referral_pdf.php?id=' + referral.id);
    }
    
    actionCell.append(pdfLink);
    
    // Append all cells to the row
    row.append(dateCell, namesCell, reasonCell, actionCell);
    
    // Highlight search terms if any
    if (searchText) {
        highlightText(row, searchText);
    }
    
    // Add the row to the table
    tbody.append(row);
}

// Function to highlight search text
function highlightText(element, searchText) {
    if (!searchText) return;
    
    const highlightSearchText = (node) => {
        if (node.nodeType === 3) { // Text node
            const text = node.nodeValue;
            const lowerText = text.toLowerCase();
            const index = lowerText.indexOf(searchText);
            
            if (index >= 0) {
                const before = text.substring(0, index);
                const match = text.substring(index, index + searchText.length);
                const after = text.substring(index + searchText.length);
                
                const span = document.createElement('span');
                span.className = 'highlight';
                span.textContent = match;
                
                const fragment = document.createDocumentFragment();
                fragment.appendChild(document.createTextNode(before));
                fragment.appendChild(span);
                
                if (after) {
                    const afterNode = document.createTextNode(after);
                    fragment.appendChild(afterNode);
                    highlightSearchText(afterNode);
                }
                
                node.parentNode.replaceChild(fragment, node);
            }
        } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE' && !node.classList.contains('highlight')) {
            Array.from(node.childNodes).forEach(child => highlightSearchText(child));
        }
    };
    
    Array.from(element.get(0).childNodes).forEach(node => highlightSearchText(node));
}

// Function to populate unique reasons in the dropdown
function populateReasonDropdown() {
    // Get unique reasons from the data
    const reasons = [...new Set(allReferrals.map(r => r.reason_for_referral))].filter(Boolean).sort();
    
    // Clear existing options (except the first "All Reasons" option)
    const dropdown = $('#filter_reason');
    dropdown.find('option:not(:first)').remove();
    
    // Add options
    reasons.forEach(reason => {
        dropdown.append($('<option>').val(reason).text(reason));
    });
}
</script>
</body>
</html>