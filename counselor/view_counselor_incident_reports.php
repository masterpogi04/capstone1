<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: ../login.php");
    exit();
}

// Check database connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// Fetch escalated incident reports with student information
$query = "SELECT 
            ir.*, 
            GROUP_CONCAT(DISTINCT sv.student_id) as student_ids,
            GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name)) AS student_names,
            GROUP_CONCAT(DISTINCT s.email) AS student_emails,
            GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
            c.name as course_name,
            sec.year_level
          FROM incident_reports ir
          LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
          LEFT JOIN tbl_student s ON sv.student_id = s.student_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
          LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
          WHERE ir.status = 'Escalated'
          GROUP BY ir.id
          ORDER BY ir.date_reported DESC";

$result = $connection->query($query);

if ($result === false) {
    die("Error in query: " . $connection->error);
}

$reports = $result->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escalated Incident Reports - Counselor View</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
     <style>
        body {
            background: linear-gradient(135deg, #0d693e, #004d4d);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: #333;
        }
        .container {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin-top: 50px;
            margin-bottom: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #0d693e;
            border-bottom: 2px solid #0d693e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        dt {
            font-weight: bold;
            color: #0d693e;
        }
        dd {
            margin-bottom: 15px;
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
            padding: 10px 20px;
        }
        .btn-secondary:hover {
            background-color: #E76F51;
            border-color: #E76F51;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="counselor_homepage.php" class="btn btn-secondary mb-4">
            <i class="fas fa-arrow-left"></i> Back to Homepage
        </a>
        <h2 class="mb-4">Escalated Incident Reports</h2>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($reports)): ?>
            <div class="alert alert-info" role="alert">
                No escalated incident reports found.
            </div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Date Reported</th>
                        <th>Student Name(s)</th>
                        <th>Course & Year</th>
                        <th>Description</th>
                        <th>Witnesses</th>
                        <th>Reported By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($row['date_reported']))); ?></td>
                            <td><?php echo htmlspecialchars($row['student_names']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_name'] . ' - ' . $row['year_level']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['witnesses']); ?></td>
                            <td><?php echo htmlspecialchars($row['reported_by']); ?></td>
                            <td>
                                <a href="view_report_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>