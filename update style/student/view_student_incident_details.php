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

// Updated query to use correct column names for the adviser table
$query = "
    SELECT ir.*,
           CASE 
               WHEN sv.student_id IS NOT NULL THEN 'Involved'
               WHEN iw.witness_id IS NOT NULL THEN 'Witness'
           END as involvement_type,
           GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name, ' (', s.student_id, ')') SEPARATOR ', ') as involved_students,
           GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
           GROUP_CONCAT(DISTINCT CONCAT(a.first_name, ' ', a.last_name, ' (', sec.year_level, ' - ', sec.section_no, ')') SEPARATOR ', ') as advisers
    FROM incident_reports ir
    LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id 
    LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
    WHERE ir.id = ?
    AND (sv.student_id = ? OR iw.witness_id = ?)
    GROUP BY ir.id
";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}

$stmt->bind_param("sss", $incident_id, $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();

if (!$incident) {
    die("Incident report not found or you don't have permission to view it.");
}

$stmt->bind_param("sss", $incident_id, $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$incident = $result->fetch_assoc();

if (!$incident) {
    die("Incident report not found or you don't have permission to view it.");
}

?>

<!DOCTYPE html>
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
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .details-section {
            grid-column: 1;
        }

        .image-container {
            grid-column: 2;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            align-self: start;
            position: sticky;
            top: 20px;
        }

        .incident-image {
            max-width: 100%;
            max-height: 300px;
            width: auto;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .details-section, .image-container {
                grid-column: 1;
            }
        }
    </style>
</head>
<body>
 <div class="container">
    <div class="details-section">
        <h1 class="mb-4">Incident Report Details</h1>
        <p><span class="label">Date Reported:</span> <?php echo htmlspecialchars($incident['date_reported']); ?></p>
        <p><span class="label">Place of Occurrence:</span> <?php echo htmlspecialchars($incident['place']); ?></p>
                <p><span class="label">Description:</span> <?php echo htmlspecialchars($incident['description']); ?></p>
                <p><span class="label">Students Involved:</span> <?php echo htmlspecialchars($incident['involved_students']); ?></p>
                <p><span class="label">Witnesses:</span> <?php echo htmlspecialchars($incident['witnesses']); ?></p>
                <p><span class="label">Advisers:</span> <?php echo htmlspecialchars($incident['advisers']); ?></p>
                <p><span class="label">Reported By:</span> <?php echo htmlspecialchars($incident['reported_by']); ?></p>
                <p><span class="label">Status:</span> <?php echo htmlspecialchars($incident['status']); ?></p>
                
    </div>
    
    <div class="image-container">
        <?php if (!empty($incident['file_path'])): ?>
            <h4>Uploaded Image:</h4>
            <img src="<?php echo htmlspecialchars($incident['file_path']); ?>" alt="Incident Image" class="incident-image">
        <?php else: ?>
            <p>No image uploaded for this incident.</p>
        <?php endif; ?>
    </div>
    <a href="view_student_incident_reports.php" class="btn btn-primary mt-3">Back to List</a>
</div>
        
    
</body>
</html>