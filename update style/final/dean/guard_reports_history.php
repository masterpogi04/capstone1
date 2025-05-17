<?php
session_start();
include '../db.php';

// Check if user is logged in as dean
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: login.php");
    exit();
}

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause for both count and main query
$where_conditions = "WHERE p.status = 'Escalated'";
$params = [];

if (!empty($search)) {
    $search_term = '%' . $connection->real_escape_string($search) . '%';
    $where_conditions .= " AND (
        p.place LIKE ? OR 
        p.description LIKE ? OR 
        CONCAT(g.first_name, ' ', g.last_name) LIKE ? OR
        psv.student_id LIKE ? OR 
        psv.student_name LIKE ?
    )";
    $params = array_fill(0, 5, $search_term);
}

if (!empty($date_from) && !empty($date_to)) {
    $where_conditions .= " AND DATE(p.date_reported) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
}

// Count total records
$count_query = "SELECT COUNT(DISTINCT p.id) as total 
                FROM pending_incident_reports p
                LEFT JOIN tbl_guard g ON p.guard_id = g.id
                LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
                $where_conditions";

$stmt = $connection->prepare($count_query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt->close();

// Main query for fetching records
$query = "SELECT p.*, 
          CONCAT(g.first_name, ' ', COALESCE(g.middle_initial, ''), ' ', g.last_name) AS guard_name,
          GROUP_CONCAT(DISTINCT CONCAT(psv.student_id, ': ', psv.student_name) SEPARATOR '| ') AS involved_students,
          GROUP_CONCAT(DISTINCT CONCAT(piw.witness_type, ': ', piw.witness_name) SEPARATOR '| ') AS witnesses
          FROM pending_incident_reports p
          LEFT JOIN tbl_guard g ON p.guard_id = g.id
          LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
          LEFT JOIN pending_incident_witnesses piw ON p.id = piw.pending_report_id
          $where_conditions
          GROUP BY p.id
          ORDER BY p.date_reported DESC
          LIMIT ?, ?";

// Add pagination parameters
$params[] = $offset;
$params[] = $records_per_page;

$stmt = $connection->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params) - 2) . 'ii'; // Add integer types for LIMIT parameters
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalated Guard Reports History</title>
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
    background: #009E60;
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
    color: var(--primary-color);
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--primary-color);
    text-align: center;
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
    padding: 12px 15px;
}

.table td {
    vertical-align: middle;
    padding: 12px 15px;
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
    letter-spacing: 0.3px;
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 8px 16px;
    border-radius: 5px;
    color: white;
    transition: all 0.3s;
}

.btn-primary:hover {
    background-color: var(--hover-color);
    transform: translateY(-1px);
}

/* Status Badges */
.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: 500;
    display: inline-block;
}

.status-pending { background-color: #ffd700; color: #000; }
.status-processing { background-color: #87ceeb; color: #000; }
.status-meeting { background-color: #98fb98; color: #000; }
.status-resolved { background-color: #90EE90; color: #000; }
.status-rejected { background-color: #ff6b6b; color: #fff; }

/* Mobile Responsive */
@media screen and (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 20px auto;
    }

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
    }

    h2 {
        font-size: 1.5rem;
    }

    .btn-primary {
        width: 100%;
        margin: 5px 0;
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
        
        <h2>Escalated Guard Reports History</h2>

        <!-- Search Form -->
        <form action="" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search reports..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($date_from); ?>"
                           placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($date_to); ?>"
                           placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date Reported</th>
                            <th>Place</th>
                            <th>Description</th>
                            <th>Students Involved</th>
                            <th>Witnesses</th>
                            <th>Reported By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('Y-m-d h:i A', strtotime($row['date_reported'])); ?></td>
                                <td><?php echo htmlspecialchars($row['place']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td>
                                    <?php
                                    $students = explode('|', $row['involved_students']);
                                    foreach ($students as $student) {
                                        if (!empty(trim($student))) {
                                            echo htmlspecialchars(trim($student)) . "<br>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $witnesses = explode('|', $row['witnesses']);
                                    foreach ($witnesses as $witness) {
                                        if (!empty(trim($witness))) {
                                            echo htmlspecialchars(trim($witness)) . "<br>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['guard_name']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info" role="alert">
                No incident reports found.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>