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

// Fetch incident report details with improved formatting
$query = "SELECT ir.*, 
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN sv.student_id IS NOT NULL AND sv.student_course IS NOT NULL THEN
                CONCAT(
                    sv.student_name,
                    ' (',
                    sv.student_course,
                    ' - ',
                    sv.student_year_level,
                    ' Section ',
                    SUBSTRING_INDEX(sv.section_name, ' Section ', -1),
                    CASE 
                        WHEN sv.adviser_name IS NOT NULL 
                        THEN CONCAT(' | Adviser: ', sv.adviser_name)
                        ELSE ''
                    END,
                    ')'
                )
            ELSE
                CONCAT(
                    sv.student_name,
                    ' (Non-CEIT Student)'
                )
        END 
        SEPARATOR '|||'
    ) AS involved_students,
    GROUP_CONCAT(DISTINCT 
        CASE 
            WHEN iw.witness_type = 'staff' THEN 
                CONCAT(iw.witness_name, ' (Staff) - ', COALESCE(iw.witness_email, 'No email provided'))
            WHEN iw.witness_type = 'student' AND iw.witness_course IS NOT NULL THEN
                CONCAT(
                    iw.witness_name,
                    ' (',
                    iw.witness_course,
                    ' - ',
                    iw.witness_year_level,
                    ' Section ',
                    CASE
                        WHEN iw.section_name IS NOT NULL 
                        THEN SUBSTRING_INDEX(iw.section_name, ' Section ', -1)
                        ELSE 'Unknown'
                    END,
                    CASE 
                        WHEN iw.adviser_name IS NOT NULL 
                        THEN CONCAT(' | Adviser: ', iw.adviser_name)
                        ELSE ''
                    END,
                    ')'
                )
            ELSE 
                CONCAT(iw.witness_name, ' (Non-CEIT Student)')
        END
        SEPARATOR '|||'
    ) AS witnesses,
    GROUP_CONCAT(DISTINCT 
        CASE
            WHEN sv.adviser_name IS NOT NULL THEN
                CONCAT(
                    sv.adviser_name
                )
            ELSE
                NULL
        END
        SEPARATOR '|||'
    ) AS advisers
FROM incident_reports ir
LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id 
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
        $has_permission = ($incident['reporters_id'] == $user_id && $incident['reported_by_type'] == 'instructor');
        break;

    case 'adviser':
        // Advisers can view reports involving their students
        $adviser_query = "
            SELECT 1
            FROM student_violations sv
            WHERE sv.incident_report_id = ? AND sv.adviser_id = ?
            LIMIT 1
        ";
        $adviser_stmt = $connection->prepare($adviser_query);
        $adviser_stmt->bind_param("si", $incident_id, $user_id);
        $adviser_stmt->execute();
        $adviser_result = $adviser_stmt->get_result();
        $has_permission = $adviser_result->num_rows > 0;
        
        // Also check if the adviser reported it
        if (!$has_permission) {
            $has_permission = ($incident['reporters_id'] == $user_id && $incident['reported_by_type'] == 'adviser');
        }
        
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
        
        // Also check if the student reported it
        if (!$has_permission) {
            $has_permission = ($incident['reporters_id'] == $user_id && $incident['reported_by_type'] == 'student');
        }
        
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

// Process the data to add proper line breaks
if (!empty($incident['involved_students'])) {
    $students = explode('|||', $incident['involved_students']);
    $incident['involved_students_formatted'] = implode(",<br><br>", $students);
} else {
    $incident['involved_students_formatted'] = '';
}

if (!empty($incident['witnesses'])) {
    $witnesses = explode('|||', $incident['witnesses']);
    $incident['witnesses_formatted'] = implode(",<br><br>", $witnesses);
} else {
    $incident['witnesses_formatted'] = '';
}

if (!empty($incident['advisers'])) {
    $advisers = explode('|||', $incident['advisers']);
    $incident['advisers_formatted'] = implode(",<br><br>", $advisers);
} else {
    $incident['advisers_formatted'] = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="incident_details.css">  
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Incident Report Details</h1>
        <div class="row">
            <div class="col-md-6">
                <p>                <span class="label">Date Reported:</span> 
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
                    <?php echo $incident['involved_students_formatted']; ?>
                </span>
                </p>
                
                <p>
                <span class="label">Witness/es:</span> 
                <span class="multi-line-content">
                    <?php echo $incident['witnesses_formatted']; ?>
                </span>
                </p>
                
                <p>
                <span class="label">Adviser/s:</span> 
                <span class="multi-line-content">
                    <?php echo $incident['advisers_formatted']; ?>
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