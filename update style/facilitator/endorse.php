<?php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Fetch distinct referral reasons
$stmt = $connection->query("SELECT DISTINCT reason_for_referral FROM referrals");
$referralReasons = [];
while($row = $stmt->fetch_assoc()) {
    $referralReasons[] = $row['reason_for_referral'];
}

$filterReason = isset($_GET['filter_reason']) ? $_GET['filter_reason'] : '';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Prepare base query
$sql = "";
$params = [];
$types = "";

if ($filterReason) {
    $sql = "SELECT r1.* FROM (
            SELECT incident_report_id, MIN(date) as date, 
                   GROUP_CONCAT(CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name) SEPARATOR ', ') as names,
                   MIN(reason_for_referral) as reason_for_referral,
                   GROUP_CONCAT(violation_details SEPARATOR '; ') as violation_details,
                   GROUP_CONCAT(other_concerns SEPARATOR '; ') as other_concerns,
                   NULL as id
            FROM referrals 
            WHERE incident_report_id IS NOT NULL AND reason_for_referral = ?
            GROUP BY incident_report_id
            UNION ALL
            SELECT NULL as incident_report_id, date, 
                   CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name) as names,
                   reason_for_referral, violation_details, other_concerns, id
            FROM referrals 
            WHERE incident_report_id IS NULL AND reason_for_referral = ?
        ) r1 ORDER BY date " . $sortOrder;
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("ss", $filterReason, $filterReason);
} else {
    $sql = "SELECT r1.* FROM (
            SELECT incident_report_id, MIN(date) as date, 
                   GROUP_CONCAT(CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name) SEPARATOR ', ') as names,
                   MIN(reason_for_referral) as reason_for_referral,
                   GROUP_CONCAT(violation_details SEPARATOR '; ') as violation_details,
                   GROUP_CONCAT(other_concerns SEPARATOR '; ') as other_concerns,
                   NULL as id
            FROM referrals 
            WHERE incident_report_id IS NOT NULL
            GROUP BY incident_report_id
            UNION ALL
            SELECT NULL as incident_report_id, date, 
                   CONCAT(first_name, ' ', IFNULL(middle_name,''), ' ', last_name) as names,
                   reason_for_referral, violation_details, other_concerns, id
            FROM referrals 
            WHERE incident_report_id IS NULL
        ) r1 ORDER BY date " . $sortOrder;
    
    $stmt = $connection->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
$referrals = $result->fetch_all(MYSQLI_ASSOC);
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
        text-align: left;
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
/* Filter section styles */
.filters-section {
    
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
  
}

.filters-row {
    display: flex;
    gap: 20px;
    align-items: flex-end;
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

.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    background-color: white;
    transition: all 0.3s ease;
}

.filter-group select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(13, 105, 62, 0.1);
}

/* Apply Filters button */
.btn-apply-filters {
    padding: 8px 20px;
    height: 38px;
    background-color: #2EDAA8;
    border: none;
    border-radius: 6px;
    color: white;
    font-weight: 500;
    transition: all 0.2s ease;
    min-width: 120px;
}

.btn-apply-filters:hover {
    background-color: #28C498;
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

/* Responsive adjustments */
@media screen and (max-width: 768px) {
    .filters-row {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-group {
        max-width: 100%;
    }
    
    .btn-apply-filters {
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
    </style>
</head>
<body>

<div class="container mt-5">
<a href="guidanceservice.html" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>
        <div class="d-flex justify-content-between align-items-center mb-4"style="border-bottom: 3px solid #004d4d;">
       <h2>STUDENT REFERRALS</h2>
</div>
<div class="filters-section">
           <form method="GET">
           <div class="filters-row">
           <div class="filter-group">
                   <label for="filter_reason">Filter by Reason:</label>
                   <select name="filter_reason" id="filter_reason" class="form-control">
                       <option value="">All Reasons</option>
                       <?php foreach ($referralReasons as $reason): ?>
                           <option value="<?= htmlspecialchars($reason) ?>" <?= $filterReason === $reason ? 'selected' : '' ?>>
                               <?= htmlspecialchars($reason) ?>
                           </option>
                       <?php endforeach; ?>
                   </select>
               </div>
               <div class="filter-group">
                   <label for="sort_order">Sort Order:</label>
                   <select name="sort_order" id="sort_order" class="form-control">
                       <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Newest to Oldest</option>
                       <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Oldest to Newest</option>
                   </select>
               </div>
              
               <button type="submit" class="btn btn-primary">Apply Filters</button>
           </form>
       </div>
                       </div>

     
           <table class="table table-striped">
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
                       <td><?= htmlspecialchars($referral['date']) ?></td>
                       <td>
                           <?php if (isset($referral['names'])): ?>
                               <?= htmlspecialchars($referral['names']) ?>
                           <?php else: ?>
                               <?= htmlspecialchars($referral['first_name'] . ' ' . 
                                   ($referral['middle_name'] ? $referral['middle_name'] . ' ' : '') . 
                                   $referral['last_name']) ?>
                           <?php endif; ?>
                       </td>
                       <td>
                           <?= htmlspecialchars($referral['reason_for_referral']) ?>
                           <?php if (!empty($referral['violation_details'])): ?>
                               <br>
                               <strong>Specific Violation:</strong> <?= htmlspecialchars($referral['violation_details']) ?>
                           <?php endif; ?>
                           <?php if (!empty($referral['other_concerns'])): ?>
                               <br>
                               <strong>Specific Concerns:</strong> <?= htmlspecialchars($referral['other_concerns']) ?>
                           <?php endif; ?>
                       </td>
                      
                        <td>
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
       </div>
   </div>
   <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
   <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>