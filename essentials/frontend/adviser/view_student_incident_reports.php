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
    SELECT DISTINCT ir.*, s.student_id, s.first_name, s.last_name,
           CASE 
               WHEN sv.student_id IS NOT NULL THEN 'Involved'
               WHEN iw.witness_id IS NOT NULL THEN 'Witness'
           END as involvement_type
    FROM incident_reports ir
    JOIN student_violations sv ON ir.id = sv.incident_report_id
    JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id AND iw.witness_id = s.student_id
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
        $query .= " AND sv.student_id IS NOT NULL";
    } elseif ($involvement_filter === 'Witness') {
        $query .= " AND iw.witness_id IS NOT NULL";
    }
}

// Add sorting
$query .= " ORDER BY ir.date_reported " . ($sort_order === 'ASC' ? 'ASC' : 'DESC');

// Add pagination
$query .= " LIMIT ? OFFSET ?";

// Prepare statement
$stmt = $connection->prepare($query);

// Bind parameters
$params = array($adviser_id);
$types = "i";

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
        $count_query .= " AND sv.student_id IS NOT NULL";
    } elseif ($involvement_filter === 'Witness') {
        $count_query .= " AND iw.witness_id IS NOT NULL";
    }
}

$count_stmt = $connection->prepare($count_query);
$count_params = array($adviser_id);
$count_types = "i";

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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 20px auto;
    }
    
    .table-responsive {
        border-radius: 8px;
    }

    .filters-section {
        padding: 10px;
    }

    .btn-edit, .btn-delete {
        padding: 6px 12px;
        font-size: 11px;
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
        
        <h2>Student Incident Reports</h2>

        <!-- Search and Filter Form -->
        <form class="mb-4" method="GET" action="">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="For Meeting" <?php echo $status_filter === 'For Meeting' ? 'selected' : ''; ?>>For Meeting</option>
                        <option value="Rejected" <?php echo $status_filter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="involvement" class="form-control">
                        <option value="">All Involvements</option>
                        <option value="Involved" <?php echo $involvement_filter === 'Involved' ? 'selected' : ''; ?>>Involved</option>
                        <option value="Witness" <?php echo $involvement_filter === 'Witness' ? 'selected' : ''; ?>>Witness</option>
                    </select>
                </div>
                <div class="col-md-2">
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
                    <th>Student Name</th>
                    <th>Date Reported</th>
                    <th>Incident Date/Time</th>
                    <th>Description</th>
                    <th>Involvement</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['date_reported']); ?></td>
                    <td><?php echo htmlspecialchars($row['place']); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                    <td><?php echo htmlspecialchars($row['involvement_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td>
                        <a href="view_advisee_incident_details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
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
                        <a class="page-link" href="?page=1<?php echo "&search=$search&status=$status_filter&involvement=$involvement_filter&sort_order=$sort_order"; ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; echo "&search=$search&status=$status_filter&involvement=$involvement_filter&sort_order=$sort_order"; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; echo "&search=$search&status=$status_filter&involvement=$involvement_filter&sort_order=$sort_order"; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; echo "&search=$search&status=$status_filter&involvement=$involvement_filter&sort_order=$sort_order"; ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; echo "&search=$search&status=$status_filter&involvement=$involvement_filter&sort_order=$sort_order"; ?>">Last</a>
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