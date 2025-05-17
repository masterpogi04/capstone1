<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$incident_id = $_GET['id'] ?? '';

// Fetch incident report details with the working query
$query = "
    SELECT ir.*, 
           GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name, ' (', s.student_id, ')') SEPARATOR ', ') as involved_students,
           GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
           GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name, ' (', sec.year_level, ' - ', sec.section_no, ')') SEPARATOR ', ') as advisers
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
    WHERE ir.id = ?
    GROUP BY ir.id
";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}

// Bind and execute the query
$stmt->bind_param("s", $incident_id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();

if (!$incident) {
    die("Incident report not found.");
}

// Now check if the student has permission to view this report
$permission_query = "
    SELECT 1 
    FROM student_violations sv 
    LEFT JOIN incident_witnesses iw ON iw.incident_report_id = sv.incident_report_id
    WHERE sv.incident_report_id = ? 
    AND (sv.student_id = ? OR iw.witness_id = ? OR EXISTS (
        SELECT 1 FROM incident_reports 
        WHERE id = ? AND reporters_id = ? AND reported_by_type = 'student'
    ))
    LIMIT 1
";

$permission_stmt = $connection->prepare($permission_query);
$permission_stmt->bind_param("sssss", $incident_id, $student_id, $student_id, $incident_id, $student_id);
$permission_stmt->execute();
$permission_result = $permission_stmt->get_result();

if ($permission_result->num_rows === 0) {
    die("You don't have permission to view this report.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="incident_detail.css">
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Incident Report Details</h1>
        <div class="row">
            <div class="col-md-6">
                <p><span class="label">Date Reported:</span> <?php echo htmlspecialchars($incident['date_reported']); ?></p>
                <p><span class="label">Place of Occurrence:</span> <?php echo htmlspecialchars($incident['place']); ?></p>
                <p><span class="label">Description:</span> <?php echo htmlspecialchars($incident['description']); ?></p>
                <p><span class="label">Students Involved:</span> <?php echo htmlspecialchars($incident['involved_students']); ?></p>
                <p><span class="label">Witnesses:</span> <?php echo htmlspecialchars($incident['witnesses']); ?></p>
                <p><span class="label">Advisers:</span> <?php echo htmlspecialchars($incident['advisers']); ?></p>
                <p><span class="label">Reported By:</span> <?php echo htmlspecialchars($incident['reported_by']); ?></p>
                <p><span class="label">Status:</span> <?php echo htmlspecialchars($incident['status']); ?></p>
            </div>
            <div class="col-md-6">
                <?php if (!empty($incident['file_path'])): ?>
                    <h4>Uploaded Image:</h4>
                    <img src="<?php echo htmlspecialchars($incident['file_path']); ?>" alt="Incident Image" class="incident-image">
                <?php else: ?>
                    <p>No image uploaded for this incident.</p>
                <?php endif; ?>
            </div>
        </div>
        <a href="view_submitted_incident_reports.php" class="btn btn-primary mt-3">Back to List</a>
    </div>
</body>
</html>