<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get filter parameters
$involvement_filter = isset($_GET['involvement']) ? $_GET['involvement'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';

// Build WHERE clause for both queries
$where_clause = "WHERE (sv.student_id IS NOT NULL OR iw.witness_id IS NOT NULL)";
if ($involvement_filter) {
    if ($involvement_filter === 'Involved') {
        $where_clause .= " AND sv.student_id IS NOT NULL";
    } elseif ($involvement_filter === 'Witness') {
        $where_clause .= " AND iw.witness_id IS NOT NULL";
    }
}
if ($status_filter) {
    $where_clause .= " AND ir.status = ?";
}

// Count query
$count_query = "
    SELECT COUNT(DISTINCT ir.id) as total_records 
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id AND sv.student_id = ?
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id AND iw.witness_id = ?
    $where_clause
";

$count_stmt = $connection->prepare($count_query);
if ($count_stmt === false) {
    die("Prepare failed: " . $connection->error);
}

if ($status_filter) {
    $count_stmt->bind_param("sss", $student_id, $student_id, $status_filter);
} else {
    $count_stmt->bind_param("ss", $student_id, $student_id);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$row = $count_result->fetch_assoc();
$total_records = $row['total_records'];
$total_pages = ceil($total_records / $records_per_page);

// Main query
$query = "
    SELECT DISTINCT ir.*, 
           CASE 
               WHEN sv.student_id IS NOT NULL THEN 'Involved'
               WHEN iw.witness_id IS NOT NULL THEN 'Witness'
           END as involvement_type
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id AND sv.student_id = ?
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id AND iw.witness_id = ?
    $where_clause
    ORDER BY ir.date_reported " . ($sort_order === 'ASC' ? 'ASC' : 'DESC') . "
    LIMIT ? OFFSET ?
";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

if ($status_filter) {
    $stmt->bind_param("sssii", $student_id, $student_id, $status_filter, $records_per_page, $offset);
} else {
    $stmt->bind_param("ssii", $student_id, $student_id, $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submitted Incident Reports - Instructor</title>
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
        <h2>My Incident Reports</h2>
</div>
        <!-- Filter Form -->
        <form class="mb-4" method="GET" action="">
        <div class="row">
                <div class="col-md-2">
                    <select name="involvement" class="form-control">
                        <option value="">All Involvements</option>
                        <option value="Involved" <?php echo $involvement_filter === 'Involved' ? 'selected' : ''; ?>>Involved</option>
                        <option value="Witness" <?php echo $involvement_filter === 'Witness' ? 'selected' : ''; ?>>Witness</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="for meeting" <?php echo $status_filter === 'for meeting' ? 'selected' : ''; ?>>For Meeting</option>
                        <option value="settled" <?php echo $status_filter === 'settled' ? 'selected' : ''; ?>>Settled</option>
                        <option value="for meeting-counselor" <?php echo $status_filter === 'for meeting-counselor' ? 'selected' : ''; ?>>For Meeting-Counselor</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-control">
                        <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </div>
        </form>

        <table class="table table-striped">
    <thead>
        <tr>
            <th>Date Reported</th>
            <th>Incident Date/Time</th>
            <th>Description</th>
            <th>Involvement</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows == 0): ?>
        <tr>
            <td colspan="6" class="text-center">
                <div class="alert alert-info" role="alert">
                    No incident reports found.
                </div>
            </td>
        </tr>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
               <td data-label="Date Reported"><?php echo date('M j, Y h:i A', strtotime($row['date_reported'])); ?></td>
                <td><?php echo htmlspecialchars($row['place']); ?></td>
                <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                <td><?php echo htmlspecialchars($row['involvement_type']); ?></td>
                <td>
                    <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </span>
                </td>
                <td>
                    <a href="view_student_incident_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&involvement=<?php echo $involvement_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page - 1); ?>&involvement=<?php echo $involvement_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&involvement=<?php echo $involvement_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo ($page + 1); ?>&involvement=<?php echo $involvement_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&involvement=<?php echo $involvement_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort_order; ?>">Last</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</body>
</html>