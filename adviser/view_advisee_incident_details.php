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
           GROUP_CONCAT(DISTINCT 
               CASE 
                   WHEN sv.student_id IS NOT NULL THEN CONCAT(s.first_name, ' ', s.last_name, ' (', s.student_id, ')')
                   ELSE CONCAT(sv.student_name, ' (Non-CEIT Student)')
               END
               SEPARATOR ',<br><br>'
           ) as involved_students,
           GROUP_CONCAT(DISTINCT 
               CASE 
                   WHEN iw.witness_type = 'student' AND iw.witness_id IS NOT NULL THEN 
                       CONCAT(s2.first_name, ' ', s2.last_name, ' (', iw.witness_id, ')')
                   WHEN iw.witness_type = 'student' AND iw.witness_id IS NULL THEN 
                       CONCAT(iw.witness_name, ' (Non-CEIT Student)')
                   WHEN iw.witness_type = 'staff' THEN 
                       CONCAT(iw.witness_name, ' (Staff - ', COALESCE(iw.witness_email, 'No email'), ')')
               END
               SEPARATOR ',<br><br>'
           ) as witnesses,
           GROUP_CONCAT(DISTINCT CONCAT(
               a.first_name, 
               CASE 
                   WHEN a.middle_initial IS NOT NULL AND a.middle_initial != '' 
                   THEN CONCAT(' ', a.middle_initial, '. ')
                   ELSE ' '
               END,
               a.last_name,
               ' (', sec.year_level, ' - ', sec.section_no, ')'
           ) SEPARATOR ',<br><br>') as advisers
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s2 ON iw.witness_id = s2.student_id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
    WHERE ir.id = ?
    GROUP BY ir.id"; 

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
        // Advisers can view reports involving their students or reports they submitted
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
        $has_permission = $adviser_result->num_rows > 0 || ($incident['reporters_id'] == $user_id && $incident['reported_by_type'] == 'adviser');
        $adviser_stmt->close();
        if (!$has_permission) {
            error_log("Adviser permission check failed. incident_id: " . $incident_id . ", user_id: " . $user_id);
        }
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
  
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="incident_details.css">
    
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Incident Report Details</h1>
        <div class="row">
            <div class="col-md-6">
                <p>
                <span class="label">Date Reported:</span> 
                <?php 
                $date_time = new DateTime($incident['date_reported']);
                echo $date_time->format('F j, Y') . ' at ' . $date_time->format('g:i A'); 
                // This will output like: December 15, 2024 at 2:30 PM
                ?>
                </p>
                <p>
                <span class="label">Place of Occurrence:</span> 
                <span class="multi-line-content">
                    <?php 
                    $place_parts = explode(' - ', $incident['place']);
                    if (count($place_parts) > 1) {
                        echo htmlspecialchars($place_parts[0]) . ',<br>' . 
                             str_replace(' at ', '<br>at ', htmlspecialchars($place_parts[1]));
                    } else {
                        echo htmlspecialchars($incident['place']);
                    }
                    ?>
                </span>
                </p>

                <p><span class="label">Description:</span> <?php echo htmlspecialchars($incident['description']); ?></p>
                <p>
                <span class="label">Student/s Involved:</span> 
                <span class="multi-line-content">
                    <?php echo $incident['involved_students']; ?>
                </span>
                </p>
                <p>
                <span class="label">Witness/es:</span> 
                <span class="multi-line-content">
                    <?php echo $incident['witnesses']; ?>
                </span>
                </p>
                <p>
                <span class="label">Adviser/s:</span> 
                <span class="multi-line-content">
                    <?php echo $incident['advisers']; ?>
                </span>
                </p>
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
                $back_link = 'view_student_incident_reports.php';
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