<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$report_id = $_GET['id'] ?? '';

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
        SEPARATOR ',<br><br>'
    ) AS student_names,
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
                    SUBSTRING_INDEX(sv.section_name, ' Section ', -1),
                    CASE 
                        WHEN sv.adviser_name IS NOT NULL 
                        THEN CONCAT(' | Adviser: ', sv.adviser_name)
                        ELSE ''
                    END,
                    ')'
                )
            ELSE 
                CONCAT(iw.witness_name, ' (Non-CEIT Student)')
        END
        SEPARATOR ',<br><br>'
    ) AS witness_list,
    GROUP_CONCAT(DISTINCT sv.adviser_name SEPARATOR ',<br><br>') AS advisers,
    ir.reported_by,
    ir.reported_by_type
FROM archive_incident_reports ir
LEFT JOIN archive_student_violations sv ON ir.id = sv.incident_report_id
LEFT JOIN archive_incident_witnesses iw ON ir.id = iw.incident_report_id 
WHERE ir.id = ?
GROUP BY ir.id";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();

$report = null;
$students = [];
$advisers = [];

if ($result->num_rows > 0) {
    $report = $result->fetch_assoc();
    
    // Process student names (they're already concatenated by GROUP_CONCAT)
    if (!empty($report['student_names'])) {
        $students = explode(',<br><br>', $report['student_names']);
    }
    
    // Process witness list (it's already concatenated by GROUP_CONCAT)
    if (!empty($report['witness_list'])) {
        $witnesses = explode(',<br><br>', $report['witness_list']);
    }
    
    // Process advisers (they're already concatenated by GROUP_CONCAT)
    if (!empty($report['advisers'])) {
        $advisers = explode(',', $report['advisers']);
    }
}

if (!$report) {
    die("Report not found.");
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Incident Report Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="incident_details.css">
</head> 
<body> 
 
    <div class="container">
            <a href="archive_reports.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Archive Reports
    </a> 
        <h1 class="mb-4">Incident Report Details</h1>
        <div class="row">
            <div class="col-md-6">
                <p>
                <span class="label">Date Reported:</span> 
                <?php 
                $date_time = new DateTime($report['date_reported']);
                echo $date_time->format('F j, Y') . ' at ' . $date_time->format('g:i A'); 
                ?>
                </p>
                <p>
                <span class="label">Place of Occurrence:</span> 
                <span class="multi-line-content">
                    <?php 
                    $place_parts = explode(' - ', $report['place']);
                    if (count($place_parts) > 1) {
                        echo htmlspecialchars($place_parts[0]) . ',<br>' . 
                             str_replace(' at ', '<br>at ', htmlspecialchars($place_parts[1]));
                    } else {
                        echo htmlspecialchars($report['place']);
                    }
                    ?>
                </span>
                </p>

                <p><span class="label">Description:</span> <?php echo htmlspecialchars($report['description']); ?></p>
                <p>
                <span class="label">Student/s Involved:</span> 
                <span class="multi-line-content">
                    <?php 
                    if (!empty($report['student_names'])) {
                        echo $report['student_names'];
                    } else {
                        echo "No students recorded";
                    }
                    ?>
                </span>
                </p>
                <p>
                <span class="label">Witness/es:</span> 
                <span class="multi-line-content">
                    <?php 
                    if (!empty($report['witness_list'])) {
                        echo $report['witness_list'];
                    } else {
                        echo "No witnesses recorded";
                    }
                    ?>
                </span>
                </p>
                <p>
                <span class="label">Adviser/s:</span> 
                <span class="multi-line-content">
                    <?php 
                    if (!empty($report['advisers'])) {
                        echo $report['advisers'];
                    } else {
                        echo "No adviser assigned";
                    }
                    ?>
                </span>
                </p>
                <p><span class="label">Reported By:</span> <?php echo htmlspecialchars($report['reported_by']); ?></p>
                <p><span class="label">Status:</span> <?php echo htmlspecialchars($report['status']); ?></p>
            </div>
            <div class="col-md-6">
                <?php if (!empty($report['file_path'])): ?>
                    <h4>Uploaded Image:</h4>
                    <img src="<?php echo htmlspecialchars($report['file_path']); ?>" alt="Incident Image" class="incident-image">
                <?php else: ?>
                    <p>No image uploaded for this incident.</p>
                <?php endif; ?>
            </div>
        
        <form id="statusUpdateForm">
            <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
            <input type="hidden" name="new_status" value="For Meeting">

        </form>
    </div>

    
    
</body>
</html>