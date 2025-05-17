<?php
session_start();
include '../db.php';

// Ensure the user is logged in and has a valid user type
$allowed_user_types = ['instructor', 'adviser', 'student', 'facilitator'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], $allowed_user_types)) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$incident_id = $_GET['id'] ?? 0; // Get the incident ID from the URL

// Fetch incident report details
$query = "
    SELECT ir.*, 
           GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name, ' (', s.student_id, ')') SEPARATOR ', ') as involved_students,
           GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
           GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', 
                CASE WHEN a.middle_initial IS NOT NULL AND a.middle_initial != '' 
                     THEN CONCAT(a.middle_initial, '. ') 
                     ELSE '' 
                END,
                a.last_name, ' (', sec.year_level, ' - ', sec.section_no, ')') SEPARATOR ', ') as advisers
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

$stmt->bind_param("s", $incident_id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();

if (!$incident) {
    die("Incident report not found.");
}

// Check if the user has permission to view this report
$has_permission = false;

switch ($user_type) {
    case 'instructor':
        // Instructors can view reports they submitted
        $has_permission = ($incident['reporters_id'] == $user_id);
        break;

    case 'adviser':
        // Advisers can view reports involving their students
        $adviser_query = "
            SELECT 1
            FROM student_violations sv
            JOIN tbl_student s ON sv.student_id = s.student_id
            JOIN sections sec ON s.section_id = sec.id
            WHERE sv.incident_report_id = ? AND sec.adviser_id = ?
            LIMIT 1
        ";
        $adviser_stmt = $connection->prepare($adviser_query);
        $adviser_stmt->bind_param("si", $incident_id, $user_id);
        $adviser_stmt->execute();
        $adviser_result = $adviser_stmt->get_result();
        $has_permission = $adviser_result->num_rows > 0;
        $adviser_stmt->close();
        break;

    case 'student':
        // Students can view reports they're involved in
        $student_query = "
            SELECT 1
            FROM student_violations
            WHERE incident_report_id = ? AND student_id = ?
            LIMIT 1
        ";
        $student_stmt = $connection->prepare($student_query);
        $student_stmt->bind_param("ss", $incident_id, $user_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        $has_permission = $student_result->num_rows > 0;
        $student_stmt->close();
        break;

    case 'facilitator':
        // Facilitators can view all reports
        $has_permission = true;
        break;
}

if (!$has_permission) {
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
        h1 {
            color: #0d693e;
            border-bottom: 2px solid #0d693e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .label {
            font-weight: bold;
            color: #0d693e;
        }
        .btn-primary {
            background-color: #0d693e;
            border-color: #0d693e;
        }
        .btn-primary:hover {
            background-color: #094e2e;
            border-color: #094e2e;
        }
        .incident-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>>
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
        <?php
        // Determine the correct "Back to List" link based on user type
        $back_link = '';
        switch ($user_type) {
            case 'instructor':
                $back_link = 'view_incident_reports.php';
                break;
            case 'adviser':
                $back_link = 'view_submitted_incident_reports-adviser.php';
                break;
            case 'student':
                $back_link = 'student_incident_reports.php';
                break;
            case 'facilitator':
                $back_link = 'incident_reports-facilitator.php';
                break;
        }
        ?>
        <a href="<?php echo $back_link; ?>" class="btn btn-primary mt-3">Back to List</a>
    </div>
</body>
</html>