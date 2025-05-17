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
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Base query for fetching records
$query = "
    SELECT ir.*, 
           GROUP_CONCAT(DISTINCT sv.student_id) as involved_students,
           GROUP_CONCAT(DISTINCT iw.witness_name) as witnesses,
           GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name)) as student_names
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    WHERE ir.reporters_id = ? AND ir.reported_by_type = ?
";

// Add search condition
if (!empty($search)) {
    $search_condition = " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR ir.description LIKE ? OR ir.place LIKE ?)";
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
$count_params = array($user_id, $user_type);
$count_types = "is";

if (!empty($search)) {
    $search_param = "%$search%";
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

// Prepare and execute main query
$stmt = $connection->prepare($query);
$params = array($user_id, $user_type);
$types = "is";

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

    .stats-card {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        transition: transform 0.2s;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stats-card h3 {
        font-size: 2rem;
        margin: 0;
    }

    .stats-card p {
        margin: 5px 0 0;
        opacity: 0.9;
    }

   /* Table Styles */
.table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.table th {
    background-color: #009E60;
    color: white;
    border: none;
}

.table td {
    vertical-align: middle;
}


    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85em;
        font-weight: 500;
    }

    .status-pending { background-color: #ffd700; color: #000; }
    .status-processing { background-color: #87ceeb; color: #000; }
    .status-meeting { background-color: #98fb98; color: #000; }
    .status-resolved { background-color: #90EE90; color: #000; }
    .status-rejected { background-color: #ff6b6b; color: #fff; }

    .btn-custom {
        background-color: var(--primary-color);
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        color: white;
        transition: all 0.3s;
    }

    .btn-custom:hover {
        background-color: var(--hover-color);
        transform: translateY(-1px);
    }

    .filters-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .search-box {
        position: relative;
    }

    .search-box input {
        padding-left: 35px;
        border-radius: 20px;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .checkbox-custom {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    

    @media (max-width: 768px) {
        .container {
            padding: 15px;
            margin: 20px auto;
        }
        
        .table-responsive {
            border-radius: 8px;
        }

        .stats-card {
            margin-bottom: 15px;
        }
    }
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
/* Medium Devices (≥992px and <1200px) */
@media screen and (max-width: 1200px) {
    .main-content {
        margin-left: 200px;
    }

    .container {
        width: 95%;
        padding: 20px;
    }

    table {
        font-size: 14px;
    }
}

/* Small Devices (≥768px and <992px) */
@media screen and (max-width: 992px) {
    .main-content {
        margin-left: 0;
        padding: 15px;
    }

    table {
        display: block;
        overflow-x: auto;
    }

    .filter-form {
        flex-direction: column;
        gap: 10px;
    }

    .filter-form select,
    .filter-form button {
        width: 100%;
        margin: 5px 0;
    }

    .section-header {
        padding: 0 10px;
    }
}

/* Extra Small Devices (<768px) */
@media screen and (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 10px;
        width: auto;
    }

    h2 {
        font-size: 1.5rem;
    }

    h3 {
        font-size: 1.2rem;
    }

    table {
        border: 0;
    }

    table thead {
        display: none;
    }

    table tr {
        margin-bottom: 15px;
        display: block;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    table td {
        display: block;
        text-align: right;
        padding: 8px 10px;
        position: relative;
        border-bottom: 1px solid #eee;
    }

    table td:last-child {
        border-bottom: 0;
    }

    table td:before {
        content: attr(data-label);
        float: left;
        font-weight: bold;
    }

    .actions-cell {
        display: flex;
        flex-direction: column;
        gap: 5px;
        text-align: center;
    }

    .btn-edit, .btn-delete {
        width: 100%;
        margin: 2px 0;
    }

    .search-bar input {
        width: 100%;
    }

    .modal-content {
        width: 95%;
        margin: 5% auto;
        padding: 15px;
    }

    .save-button {
        min-width: 200px;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .top-buttons {
        width: 100%;
        justify-content: flex-end;
        margin-top: 10px;
    }
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

.form-control {
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #ced4da;
    width: 100%;
    transition: all 0.3s ease;
}

/* Table Responsive Styles */
.table-responsive {
    display: block;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
}

/* Responsive Breakpoints */
@media screen and (max-width: 992px) {
    .col-md-4, .col-md-3, .col-md-2 {
        flex: 0 0 100%;
        max-width: 100%;
        padding: 0 15px;
        margin-bottom: 10px;
    }

    .btn-primary {
        width: 100%;
    }

    .table thead {
        display: none;
    }

    .table, .table tbody, .table tr, .table td {
        display: block;
        width: 100%;
    }

    .table tr {
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
    }

    .table td {
        text-align: right;
        padding: 12px 10px;
        position: relative;
        border-bottom: 1px solid #eee;
    }

    .table td:last-child {
        border-bottom: none;
    }

    .table td::before {
        content: attr(data-label);
        float: left;
        font-weight: bold;
        color: #555;
    }

    /* Adjust specific columns for better mobile display */
    td:nth-child(3) { /* Description column */
        white-space: normal;
        min-height: 60px;
    }
}

@media screen and (max-width: 768px) {
    .form-control {
        font-size: 14px;
        padding: 12px;
    }

    .btn-primary {
        padding: 12px;
        font-size: 14px;
    }

    .table td {
        padding: 15px 10px;
    }

    .table td::before {
        margin-right: 10px;
    }
}

@media screen and (max-width: 576px) {
    .row {
        margin-right: -10px;
        margin-left: -10px;
    }

    .col-md-4, .col-md-3, .col-md-2 {
        padding: 0 10px;
    }

    .table td {
        font-size: 13px;
    }
}

/* Touch Device Optimizations */
@media (hover: none) {
    .form-control, .btn-primary {
        min-height: 44px;
    }

    .table td {
        padding: 15px;
    }
}
/* Very Small Devices */
@media screen and (max-width: 576px) {
    .container {
        padding: 10px;
    }

    h2 {
        font-size: 1.25rem;
    }

    .settings-content {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        min-width: 100%;
        border-radius: 15px 15px 0 0;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
    }

    .settings-content a {
        text-align: center;
        padding: 16px;
        border-bottom: 1px solid #eee;
    }

    .settings-content a:last-child {
        border-bottom: none;
        padding-bottom: 25px;
    }

    .modern-back-button {
        padding: 6px 12px;
        font-size: 0.85rem;
    }
}

/* Touch Device Optimizations */
@media (hover: none) {
    .btn-edit, .btn-delete,
    .filter-form select,
    .filter-form button {
        padding: 12px 16px;
        min-height: 44px;
    }

    select.form-control {
        height: 44px;
    }

    .settings-icon {
        padding: 12px;
    }
}
/* Table Card View for Mobile */
@media screen and (max-width: 768px) {
    .table tr {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        padding: 8px;
        display: block;
    }

    .table td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        text-align: left;
        min-height: 50px;
        line-height: 1.5;
    }

    .table td::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 14px;
        color: #444;
        padding-right: 15px;
        text-align: left;
        flex: 1;
        /* Ensure label stays on same line */
        white-space: nowrap;
    }

    .table td > span,
    .table td > a {
        flex: 2;
        text-align: right;
        word-break: break-word;
    }

    /* Specific field styling */
    td[data-label="Date Reported"] {
        font-weight: 500;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
    }

    td[data-label="Place, Date & Time"] {
        white-space: pre-line;
        line-height: 1.6;
    }

    td[data-label="Description"] {
        min-height: 60px;
    }

    td[data-label="Students Involved"],
    td[data-label="Witnesses"] {
        white-space: pre-line;
        line-height: 1.4;
    }

    td[data-label="Status"] {
        background: #f8f9fa;
    }

    td[data-label="Actions"] {
        border-bottom: none;
        border-radius: 0 0 8px 8px;
    }

    /* Button styling in mobile view */
    .btn-primary.btn-sm {
        width: 100%;
        padding: 10px;
        text-align: center;
        border-radius: 6px;
    }

    /* Last cell without border */
    .table td:last-child {
        border-bottom: none;
    }
}

/* Extra small devices (phones) */
@media screen and (max-width: 576px) {
    .table td {
        padding: 10px 12px;
        font-size: 13px;
    }

    .table td::before {
        font-size: 13px;
    }

    /* Add more spacing between cards */
    .table tr {
        margin-bottom: 15px;
    }
}

/* Touch device optimizations */
@media (hover: none) {
    .table td {
        min-height: 44px;
        padding: 12px 15px;
    }
    
    .btn-primary.btn-sm {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

</style>
</head>
<body>
    <div class="container mt-5">
        <a href="adviser_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        <h2>My Submitted Incident Reports</h2>

        <!-- Search and Filter Form -->
        <form class="mb-4" method="GET" action="">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="For Meeting" <?php echo $status_filter === 'For Meeting' ? 'selected' : ''; ?>>For Meeting</option>
                        <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="sort_order" class="form-control">
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
                <td data-label="Date Reported"><?php echo htmlspecialchars($row['date_reported']); ?></td>
                <td data-label="Place, Date & Time"><?php echo htmlspecialchars($row['place']); ?></td>
                <td data-label="Description"><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                <td data-label="Students Involved"><?php echo htmlspecialchars($row['student_names'] ?: $row['involved_students']); ?></td>
                <td data-label="Witnesses"><?php echo htmlspecialchars($row['witnesses']); ?></td>
                <td data-label="Status">
                        <?php 
                        $status_class = '';
                        switch($row['status']) {
                            case 'Pending':
                                $status_class = 'text-warning';
                                break;
                            case 'Processing':
                                $status_class = 'text-primary';
                                break;
                            case 'For Meeting':
                                $status_class = 'text-info';
                                break;
                            case 'Resolved':
                                $status_class = 'text-success';
                                break;
                            case 'Rejected':
                                $status_class = 'text-danger';
                                break;
                        }
                        ?>
                        <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                    </td>
                    <td data-label="Actions">
                        <a href="view_incident_details-adviser.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
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
</body>
</html>