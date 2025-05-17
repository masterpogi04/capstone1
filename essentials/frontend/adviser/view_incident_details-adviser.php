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
           GROUP_CONCAT(DISTINCT CONCAT(a.name, ' (', sec.year_level, ' - ', sec.section_no, ')') SEPARATOR ', ') as advisers
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
    <style>
    :root {
        --primary-color: #0d693e;
        --secondary-color: #004d4d;
        --accent-color: #F4A261;
        --hover-color: #094e2e;
        --text-color: #2c3e50;
        --border-color: #e0e0e0;
    }

    body {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        min-height: 100vh;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: var(--text-color);
        margin: 0;
        padding: 0;
    }

    .container {
        background-color: rgba(255, 255, 255, 0.98);
        border-radius: 15px;
        padding: 40px;
        margin: 50px auto;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        max-width: 1200px;
    }

    h1 {
        color: var(--primary-color);
        font-size: 2.2rem;
        font-weight: 600;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--primary-color);
        text-align: center;
    }

    .details-card {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease;
    }

    .details-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .label {
        font-weight: 600;
        color: var(--primary-color);
        display: inline-block;
        margin-right: 8px;
        min-width: 150px;
    }

    p {
        margin-bottom: 15px;
        line-height: 1.6;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .incident-image {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        margin-top: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease;
    }

    .incident-image:hover {
        transform: scale(1.02);
    }

    .image-container {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
    }

    h4 {
        color: var(--primary-color);
        margin-bottom: 20px;
        font-size: 1.4rem;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .btn-primary:hover {
        background-color: var(--hover-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .row {
        margin: 0 -15px;
    }

    .col-md-6 {
        padding: 0 15px;
    }

    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 0.9em;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending { background-color: #ffd700; color: #000; }
    .status-processing { background-color: #87ceeb; color: #000; }
    .status-meeting { background-color: #98fb98; color: #000; }
    .status-resolved { background-color: #90EE90; color: #000; }
    .status-rejected { background-color: #ff6b6b; color: #fff; }

    /* Responsive Design */
    @media (max-width: 768px) {
        .container {
            margin: 20px;
            padding: 20px;
        }

        h1 {
            font-size: 1.8rem;
        }

        .label {
            min-width: 120px;
        }

        .btn-primary {
            width: 100%;
            margin-top: 20px;
        }

        .row {
            margin: 0;
        }

        .col-md-6 {
            padding: 0;
        }

        .details-card {
            padding: 15px;
        }
    }
    </style>
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