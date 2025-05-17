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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Count total records for pagination
$count_query = "
    SELECT COUNT(*) as total 
    FROM incident_reports 
    WHERE reporters_id = ? AND reported_by_type = ?
";
$count_stmt = $connection->prepare($count_query);
$count_stmt->bind_param("is", $user_id, $user_type);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch incident reports submitted by this student
$query = "
    SELECT ir.*,
           GROUP_CONCAT(DISTINCT 
               CONCAT(
                   CONCAT(UPPER(SUBSTRING(s.first_name, 1, 1)), LOWER(SUBSTRING(s.first_name, 2))), 
                   ' ',
                   IF(s.middle_name IS NOT NULL AND s.middle_name != '', 
                      CONCAT(UPPER(SUBSTRING(s.middle_name, 1, 1)), '. '),
                      ''
                   ),
                   CONCAT(UPPER(SUBSTRING(s.last_name, 1, 1)), LOWER(SUBSTRING(s.last_name, 2)))
               )
           ) as student_names,
           GROUP_CONCAT(DISTINCT 
               CASE 
                   WHEN iw.witness_type = 'student' THEN 
                       CONCAT(
                           CONCAT(UPPER(SUBSTRING(s2.first_name, 1, 1)), LOWER(SUBSTRING(s2.first_name, 2))), 
                           ' ',
                           IF(s2.middle_name IS NOT NULL AND s2.middle_name != '', 
                              CONCAT(UPPER(SUBSTRING(s2.middle_name, 1, 1)), '. '),
                              ''
                           ),
                           CONCAT(UPPER(SUBSTRING(s2.last_name, 1, 1)), LOWER(SUBSTRING(s2.last_name, 2)))
                       )
                   ELSE iw.witness_name 
               END
           ) as witnesses
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s2 ON iw.witness_id = s2.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
    GROUP BY ir.id
    ORDER BY ir.date_reported DESC
    LIMIT ? OFFSET ?
";

$stmt = $connection->prepare($query);

if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("isii", $user_id, $user_type, $records_per_page, $offset);

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
        <a href="student_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        <div class="d-flex justify-content-between align-items-center mb-4"style="border-bottom: 3px solid #004d4d;">
        <h2>My Submitted Incident Reports</h2>
</div>
<form class="mb-4" method="GET" action="">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date Reported</th>
                    <th>Place, Date & Time of Incident</th>
                    <th>Description</th>
                    <th>Students Involved</th>
                    <th>Witnesses</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Date Reported"><?php echo date('M j, Y h:i A', strtotime($row['date_reported'])); ?></td>
                        <td><?php 
                            // Format the place text with proper noun capitalization
                            $places = explode('-', $row['place'], 2);
                            $location = ucwords(strtolower(trim($places[0])));
                            echo htmlspecialchars($location);
                            if (isset($places[1])) {
                                echo ' - ' . htmlspecialchars(trim($places[1]));
                            }
                        ?></td>
                        <td><?php 
                            // Capitalize first letter of description
                            $description = ucfirst(strtolower(substr($row['description'], 0, 50)));
                            echo htmlspecialchars($description) . (strlen($row['description']) > 50 ? '...' : ''); 
                        ?></td>
                        <td><?php 
                            // Student names are already formatted in the query
                            echo htmlspecialchars($row['student_names']); 
                        ?></td>
                        <td><?php 
                            // Witness names are already formatted in the query
                            echo htmlspecialchars($row['witnesses']); 
                        ?></td>
                        <td><?php 
                            // Capitalize status
                            echo ucfirst(strtolower($row['status'])); 
                        ?></td>
                        <td>
                            <a href="view_incident_details-student.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
        </table>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1" aria-label="First">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
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
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>" aria-label="Last">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>
</html>