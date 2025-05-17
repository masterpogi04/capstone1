<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guard') {
    header("Location: ../login.php");
    exit();
}

$incident_id = $_GET['id'] ?? 0;

// Modified query to fetch all involved students and witnesses
$query = "
    SELECT 
        pir.*,
        GROUP_CONCAT(
            DISTINCT 
            CASE 
                WHEN psv.student_id IS NOT NULL THEN 
                    CONCAT(psv.student_name, ' (ID: ', psv.student_id, ')')
                ELSE 
                    CONCAT(psv.student_name, ' (No ID)')
            END
            ORDER BY psv.student_name 
            SEPARATOR '\n\n'
        ) as student_details,
        GROUP_CONCAT(
            DISTINCT 
            CASE 
                WHEN piw.witness_type = 'student' THEN
                    CASE 
                        WHEN piw.witness_id IS NOT NULL THEN 
                            CONCAT(piw.witness_name, ' (ID: ', piw.witness_id, ')')
                        ELSE 
                            CONCAT(piw.witness_name, ' (No ID)')
                    END
                WHEN piw.witness_type = 'staff' THEN
                    CONCAT(piw.witness_name, ' (Staff - ', COALESCE(piw.witness_email, 'No email'), ')')
                ELSE 
                    piw.witness_name
            END
            ORDER BY piw.witness_name 
            SEPARATOR '\n\n'
        ) as witnesses
    FROM pending_incident_reports pir
    LEFT JOIN pending_student_violations psv ON pir.id = psv.pending_report_id
    LEFT JOIN pending_incident_witnesses piw ON pir.id = piw.pending_report_id
    WHERE pir.id = ?
    GROUP BY pir.id
";


$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}

$stmt->bind_param("i", $incident_id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();

if (!$incident) {
    die("Incident report not found.");
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
            <?php 
            if (!empty($incident['student_details'])) {
                $students = explode("\n\n", $incident['student_details']);
                foreach ($students as $index => $student) {
                    echo htmlspecialchars($student);
                    if ($index < count($students) - 1) {
                        echo ",<br>";
                    }
                }
            } else {
                echo "No students involved";
            }
            ?>
        </span> 
        </p>

        <!-- Witnesses Section -->
        <p>
        <span class="label">Witness/es:</span> 
        <span class="multi-line-content">
            <?php 
            if (!empty($incident['witnesses'])) {
                $witnesses = explode("\n\n", $incident['witnesses']);
                foreach ($witnesses as $index => $witness) {
                    echo htmlspecialchars($witness);
                    if ($index < count($witnesses) - 1) {
                        echo ",<br>";
                    }
                }
            } else {
                echo "No witnesses recorded";
            }
            ?>
        </span>
        </p>
                <p><span class="label">Reported By:</span> <?php echo htmlspecialchars($incident['reported_by']); ?></p>
                <p><span class="label">Status:</span> <?php echo htmlspecialchars($incident['status']); ?></p>
            </div>
            <div class="col-md-6">
                <?php if (!empty($incident['file_path'])): ?>
                    <h4>Uploaded Image:</h4>
                    <img src="<?php echo htmlspecialchars($incident['file_path']); ?>" alt="Incident Image" class="incident-image img-fluid">
                <?php else: ?>
                    <p>No image uploaded for this incident.</p>
                <?php endif; ?>
            </div>
        </div>
        <a href="view_submitted_incident_reports_guard.php" class="btn btn-primary mt-3">Back to List</a>
    </div>
</body>
</html>