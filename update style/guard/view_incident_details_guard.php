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
            DISTINCT CONCAT(
                'ID: ', psv.student_id, 
                ' - ', psv.student_name
            ) 
            ORDER BY psv.student_name 
            SEPARATOR '\n'
        ) as student_details,
        GROUP_CONCAT(
            DISTINCT piw.witness_name 
            ORDER BY piw.witness_name 
            SEPARATOR '\n'
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
    <style>
        body {
            background-color: #f0f5f0;
            font-family: Arial, sans-serif;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            margin-top: 50px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0d693e;
            border-bottom: 2px solid #0d693e;
            padding-bottom: 10px;
        }
        .incident-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Incident Report Details</h1>
        <div class="row">
            <div class="col-md-6">
                <p><span class="label">Date Reported:</span> <?php echo htmlspecialchars($incident['date_reported']); ?></p>
                <p><span class="label">Place, Date & Time of Incident:</span> <?php echo htmlspecialchars($incident['place']); ?></p>
                <p><span class="label">Description:</span> <?php echo htmlspecialchars($incident['description']); ?></p>
                
                <!-- Students Involved Section -->
                <p><span class="label">Students Involved:</span></p>
                <div style="white-space: pre-line; margin-left: 20px;">
                    <?php 
                    if (!empty($incident['student_details'])) {
                        echo htmlspecialchars($incident['student_details']);
                    } else {
                        echo 'No students involved';
                    }
                    ?>
                </div>

                <!-- Witnesses Section -->
                <p><span class="label">Witnesses:</span></p>
                <div style="white-space: pre-line; margin-left: 20px;">
                    <?php 
                    if (!empty($incident['witnesses'])) {
                        echo htmlspecialchars($incident['witnesses']);
                    } else {
                        echo 'No witnesses';
                    }
                    ?>
                </div>

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