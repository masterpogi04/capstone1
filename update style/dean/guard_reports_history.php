<?php
session_start();
include '../db.php';

// Check if the user is logged in as a dean
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: login.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows_per_page = 10;
$offset = ($page - 1) * $rows_per_page;

// Filtering and Searching
$filter_view = isset($_GET['filter_view']) ? $_GET['filter_view'] : '';
$filter_guard = isset($_GET['filter_guard']) ? (int)$_GET['filter_guard'] : '';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : 'newest';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Base query
$query = "SELECT p.*, g.name AS guard_name, 
          GROUP_CONCAT(DISTINCT psv.student_id SEPARATOR ', ') AS student_ids,
          GROUP_CONCAT(DISTINCT CONCAT(piw.witness_type, ': ', piw.witness_name) SEPARATOR ', ') AS witnesses
          FROM pending_incident_reports p
          LEFT JOIN tbl_guard g ON p.guard_id = g.id
          LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
          LEFT JOIN pending_incident_witnesses piw ON p.id = piw.pending_report_id
          WHERE 1=1";

// Apply filters
if ($filter_view === 'student_id') {
    $query .= " AND psv.student_id IS NOT NULL";
} elseif ($filter_view === 'description') {
    $query .= " AND p.description != ''";
}

if ($filter_guard) {
    $query .= " AND p.guard_id = $filter_guard";
}

if ($search) {
    $query .= " AND (psv.student_id LIKE '%$search%' OR p.description LIKE '%$search%' OR g.name LIKE '%$search%')";
}

// Group by to avoid duplicates due to the JOIN
$query .= " GROUP BY p.id";

// Apply date sorting
$query .= " ORDER BY p.date_reported " . ($filter_date === 'oldest' ? 'ASC' : 'DESC');

// Add pagination
$query .= " LIMIT $offset, $rows_per_page";

$result = $connection->query($query);

// Get total number of reports for pagination
$total_query = "SELECT COUNT(DISTINCT p.id) as total FROM pending_incident_reports p LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id";
$total_result = $connection->query($total_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $rows_per_page);

// Fetch all guards for the filter dropdown
$guards_query = "SELECT id, name FROM tbl_guard";
$guards_result = $connection->query($guards_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Reports History</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d693e, #004d4d);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #0d693e;
            border-bottom: 2px solid #0d693e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .pagination {
            justify-content: center;
        }
        .btn-primary {
            background-color: #0d693e;
            border-color: #0d693e;
        }
        .btn-primary:hover {
            background-color: #094e2e;
            border-color: #094e2e;
        }
        .btn-secondary {
            background-color: #F4A261;
            border-color: #F4A261;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #E76F51;
            border-color: #E76F51;
        }
        .table {
            background-color: #ffffff;
        }
        .table thead th {
            background-color: #0d693e;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="dean_homepage.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
        
        <h2>Guard Reports History</h2>

        <!-- Filter and Search Form -->
        <form action="" method="GET" class="filter-form">
            <div class="form-row">
                <div class="col-md-3 mb-3">
                    <select name="filter_view" class="form-control">
                        <option value="">View All</option>
                        <option value="student_id" <?php echo $filter_view === 'student_id' ? 'selected' : ''; ?>>Student ID Only</option>
                        <option value="description" <?php echo $filter_view === 'description' ? 'selected' : ''; ?>>Description Only</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <select name="filter_guard" class="form-control">
                        <option value="">All Guards</option>
                        <?php while ($guard = $guards_result->fetch_assoc()): ?>
                            <option value="<?php echo $guard['id']; ?>" <?php echo $filter_guard == $guard['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($guard['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <select name="filter_date" class="form-control">
                        <option value="newest" <?php echo $filter_date === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $filter_date === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-1 mb-3">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </form>

        <!-- Reports Table -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date Reported</th>
                        <th>Place, Date & Time of Incident</th>
                        <th>Description</th>
                        <th>Students Involved</th>
                        <th>Witnesses</th>
                        <th>Status</th>
                        <th>Reported By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['date_reported']); ?></td>
                            <td><?php echo htmlspecialchars($row['place']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_ids']); ?></td>
                            <td><?php echo htmlspecialchars($row['witnesses'] ?: 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo htmlspecialchars($row['guard_name']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>


    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>