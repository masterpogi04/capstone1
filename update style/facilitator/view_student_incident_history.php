<?php
session_start();
include '../db.php';

// Check if user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Get student ID from URL
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

if (empty($student_id)) {
    header("Location: view_profiles.php");
    exit();
}

// Get student details
$stmt = $connection->prepare("
    SELECT CONCAT(ts.first_name, ' ', ts.last_name) as full_name,
           s.section_no as section_name,         
           s.course_name,                        
           s.department_name                      
    FROM tbl_student ts
    LEFT JOIN sections s ON ts.section_id = s.id
    WHERE ts.student_id = ?
");

if ($stmt === false) {
    die("Error in prepare statement: " . $connection->error);
}

$stmt->bind_param("i", $student_id);

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$student_result = $stmt->get_result();
$student_info = $student_result->fetch_assoc();

if (!$student_info) {
    die("No student found with ID: " . $student_id);
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Sorting setup
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_reported';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Valid sort columns
$valid_sort_columns = ['date_reported', 'status', 'description', 'involvement_type'];
if (!in_array($sort, $valid_sort_columns)) {
    $sort = 'date_reported';
}

// Fetch total records for pagination
$total_records_query = "
    SELECT COUNT(*) as total FROM (
        SELECT DISTINCT ir.id
        FROM incident_reports ir
        LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
        LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
        WHERE sv.student_id = ? OR iw.witness_id = ?
    ) as count_table";
$stmt = $connection->prepare($total_records_query);
$stmt->bind_param("ss", $student_id, $student_id);
$stmt->execute();
$total_result = $stmt->get_result();
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query to fetch incident reports
$query = "
    SELECT DISTINCT
        ir.id,
        ir.date_reported,
        ir.description,
        ir.status,
        CASE 
            WHEN sv.student_id IS NOT NULL THEN 'Involved'
            WHEN iw.witness_id IS NOT NULL THEN 'Witness'
        END as involvement_type,
        ir.resolution_status,
        ir.resolution_notes
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id AND sv.student_id = ?
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id AND iw.witness_id = ?
    WHERE sv.student_id IS NOT NULL OR iw.witness_id IS NOT NULL
    ORDER BY $sort $order
    LIMIT ? OFFSET ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("ssii", $student_id, $student_id, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Incident History</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d693e, #004d4d);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .student-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .sort-link {
            color: #0d693e;
            text-decoration: none;
        }
        .sort-link:hover {
            color: #094e2e;
            text-decoration: none;
        }
        .btn-back {
            background-color: #F4A261;
            border-color: #F4A261;
            color: white;
        }
        .btn-back:hover {
            background-color: #E76F51;
            border-color: #E76F51;
            color: white;
        }
        .involvement-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .involvement-badge.involved {
            background-color: #dc3545;
            color: white;
        }
        .involvement-badge.witness {
            background-color: #ffc107;
            color: black;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="btn btn-back mb-4">
            <i class="fas fa-arrow-left"></i> Back
        </a>

        <div class="student-info">
            <h2>Student Incident History</h2>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($student_info['full_name']); ?></p>
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student_id); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($student_info['course_name']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($student_info['department_name']); ?></p>
                </div>
            </div>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            <a href="?student_id=<?php echo $student_id; ?>&sort=date_reported&order=<?php echo $sort === 'date_reported' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                                Date Reported 
                                <?php if ($sort === 'date_reported'): ?>
                                    <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Description</th>
                        <th>
                            <a href="?student_id=<?php echo $student_id; ?>&sort=involvement_type&order=<?php echo $sort === 'involvement_type' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                                Involvement 
                                <?php if ($sort === 'involvement_type'): ?>
                                    <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?student_id=<?php echo $student_id; ?>&sort=status&order=<?php echo $sort === 'status' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link">
                                Status 
                                <?php if ($sort === 'status'): ?>
                                    <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Resolution Status</th>
                        <th>Resolution Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('F j, Y, g:i a', strtotime($row['date_reported'])); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <span class="involvement-badge <?php echo strtolower($row['involvement_type']); ?>">
                                    <?php echo htmlspecialchars($row['involvement_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo htmlspecialchars($row['resolution_status']); ?></td>
                            <td><?php echo htmlspecialchars($row['resolution_notes']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?student_id=<?php echo $student_id; ?>&page=1&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?student_id=<?php echo $student_id; ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?student_id=<?php echo $student_id; ?>&page=<?php echo $i; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?student_id=<?php echo $student_id; ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?student_id=<?php echo $student_id; ?>&page=<?php echo $total_pages; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i> No History of Incident Report
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>