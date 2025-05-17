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
    (
        SELECT GROUP_CONCAT(
            CASE 
                WHEN student_id IS NOT NULL AND student_course IS NOT NULL THEN
                    CONCAT(
                        student_name,
                        ' (',
                        student_course,
                        ' - ',
                        student_year_level,
                        ' Section ',
                        SUBSTRING_INDEX(section_name, ' Section ', -1),
                        CASE 
                            WHEN adviser_name IS NOT NULL 
                            THEN CONCAT(' | Adviser: ', adviser_name)
                            ELSE ''
                        END,
                        ')'
                    )
                ELSE
                    CONCAT(
                        student_name,
                        ' (Non-CEIT Student)'
                    )
            END 
            SEPARATOR '|||'
        )
        FROM student_violations
        WHERE incident_report_id = ir.id
    ) AS involved_students,
    (
        SELECT GROUP_CONCAT(
            CASE 
                WHEN witness_type = 'staff' THEN 
                    CONCAT(witness_name, ' (Staff) - ', COALESCE(witness_email, 'No email provided'))
                WHEN witness_type = 'student' AND witness_course IS NOT NULL THEN
                    CONCAT(
                        witness_name,
                        ' (',
                        witness_course,
                        ' - ',
                        witness_year_level,
                        ' Section ',
                        CASE
                            WHEN section_name IS NOT NULL 
                            THEN SUBSTRING_INDEX(section_name, ' Section ', -1)
                            ELSE 'Unknown'
                        END,
                        CASE 
                            WHEN adviser_name IS NOT NULL 
                            THEN CONCAT(' | Adviser: ', adviser_name)
                            ELSE ''
                        END,
                        ')'
                    )
                ELSE 
                    CONCAT(witness_name, ' (Non-CEIT Student)')
            END
            SEPARATOR '|||'
        )
        FROM incident_witnesses
        WHERE incident_report_id = ir.id
    ) AS witnesses,
    (
        SELECT GROUP_CONCAT(DISTINCT 
            adviser_name
            SEPARATOR '|||'
        )
        FROM student_violations
        WHERE incident_report_id = ir.id AND adviser_name IS NOT NULL
    ) AS advisers
FROM incident_reports ir
WHERE ir.id = ?";

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
    --separator-color: #d1d5db;
    --card-bg: #f8f9fa;
    --shadow: rgba(0, 0, 0, 0.1);
}
body {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: var(--text-color);
    margin: 0;
    padding: 0;
    line-height: 1.6;
}

.container {
    background-color: rgba(255, 255, 255, 0.98);
    border-radius: 12px;
    padding: 1rem;
    margin: 2.5rem auto;
    box-shadow: 0 8px 24px var(--shadow);
     max-width: 1000px; /* Added this line to limit width */
            width: 90%; /* Added this line to make it responsive */
            margin-left: auto; /* Added for center alignment */
            margin-right: auto; /* Added for center alignment */
}

h1 {
    font-weight: 700;
    font-size: 2rem;
    text-align: center;
    margin: 5px 0 30px;
    padding-bottom: 15px;
    border-bottom: 3px solid var(--primary-dark);
    text-transform: uppercase;
    letter-spacing: 1.5px;
    border-bottom: 3px solid var(--primary-color);
    text-align: center;
    letter-spacing: 0.5px;
    padding-top: 30px;
}

.details-card {
    background-color: var(--card-bg);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px var(--shadow);
    border: 1px solid var(--border-color);
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin: -1rem;
    gap: 1.5rem;
}

.col-md-6 {
    flex: 1;
    min-width: 300px;
    padding: 1rem;
}

.label {
    font-weight: 600;
    color: var(--primary-color);
    display: inline-block;
    margin-right: 1rem;
    min-width: 160px;
    padding: 0.5rem 0;
    position: relative;
}

.label::after {
    content: ':';
    position: absolute;
    right: 0.5rem;
}

p {
    margin: 0 0 1.25rem 0;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
    display: flex-start;
    align-items: baseline;
}

p:last-child {
    margin-bottom: 0;
}

.incident-image {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin-top: 1rem;
    box-shadow: 0 4px 12px var(--shadow);
    border: 2px solid #fff;
}

.image-container {
    background-color: var(--card-bg);
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1.5rem;
}

h4 {
    color: var(--primary-color);
    margin: 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-weight: 500;
    letter-spacing: 0.3px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px var(--shadow);
    margin-top: 1.5rem;
    margin-left: 20px;
}

.btn-primary:hover {
    background-color: var(--hover-color);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px var(--shadow);
}

/* Status Badges with improved spacing */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    margin-left: 1rem;
}

.status-pending { 
    background-color: #ffeeba;
    color: #856404;
}

.status-processing { 
    background-color: #bee5eb;
    color: #0c5460;
}

.status-meeting { 
    background-color: #c3e6cb;
    color: #155724;
}

.status-resolved { 
    background-color: #d4edda;
    color: #155724;
}

.status-rejected { 
    background-color: #f8d7da;
    color: #721c24;
}

/* Improved spacing for lists */
ul, ol {
    margin: 0;
    padding-left: 1.5rem;
}

li {
    margin-bottom: 0.5rem;
}

/* Responsive Design with better spacing */
@media (max-width: 992px) {
    .container {
        margin: 1.5rem;
        padding: 1.5rem;
    }

    .row {
        gap: 1rem;
    }

    .col-md-6 {
        min-width: 100%;
    }

    .label {
        min-width: 140px;
    }
}

@media (max-width: 768px) {
    .container {
        margin: 1rem;
        padding: 1rem;
    }

    h1 {
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
    }

    .details-card {
        padding: 1rem;
    }

    .label {
        min-width: 120px;
        margin-right: 0.75rem;
    }

    p {
        padding: 0.5rem 0;
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .container {
        margin: 0.75rem;
        padding: 0.75rem;
    }

    h1 {
        font-size: 1.5rem;
        padding: 0.75rem 0;
    }

    .row {
        margin: -0.5rem;
    }

    .col-md-6 {
        padding: 0.5rem;
    }

    .label {
        min-width: 100px;
    }

    .btn-primary {
        width: 100%;
        margin-top: 1rem;
    }
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin: -1rem;
    gap: 1.5rem;
    position: relative;
}

/* Add vertical line between columns */
.row::after {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    height: 100%;
    width: 1px;
    background-color: var(--separator-color);
    transform: translateX(-50%);
}

.col-md-6 {
    flex: 1;
    min-width: 300px;
    padding: 1rem;
}

/* Modified label and text separation */
p {
    margin: 0 0 1.25rem 0;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: baseline;
    position: relative;
}

.label {
    font-weight: 600;
    color: var(--primary-color);
    display: inline-block;
    margin-right: 1.5rem;
    min-width: 160px;
    padding: 0.5rem 0;
    position: relative;
    flex-shrink: 0;
}

/* Add vertical line after label */
.label::after {
    content: '';
    position: absolute;
    right: -0.75rem;
    top: 50%;
    transform: translateY(-50%);
    height: 70%;
    width: 1px;
    background-color: var(--separator-color);
}

/* Mobile responsiveness updates */
@media (max-width: 992px) {
    .row::after {
        display: none; /* Remove vertical line on mobile */
    }
    
    .col-md-6 {
        min-width: 100%;
    }

    /* Add horizontal line between sections on mobile */
    .col-md-6:first-child {
        border-bottom: 1px solid var(--separator-color);
        padding-bottom: 2rem;
        margin-bottom: 2rem;
    }
}

@media (max-width: 768px) {
    .label {
        min-width: 140px;
    }

    .label::after {
        height: 60%;
    }
}

@media (max-width: 576px) {
    .label {
        min-width: 120px;
    }

    p {
        flex-direction: column;
    }

    .label::after {
        display: none;
    }

    /* Add horizontal separator for mobile view */
    .label {
        border-bottom: 1px solid var(--separator-color);
        margin-bottom: 0.5rem;
        padding-bottom: 0.25rem;
    }
}

span{
    margin-left: 40px;
}

.multi-line-content {
    display: inline-block;
    line-height: 1.8;
    padding: 0.5rem 0;
    flex-grow: 1; /* Allow content to grow */
    margin-left: 1px; /* Maintain consistent spacing with other content */
}

.multi-line-content br {
    content: "";
    display: block;
    margin: 4px 0; /* Reduced margin for tighter spacing */
}

/* Responsive adjustments */
@media (max-width: 576px) {
    p {
        flex-direction: column;
    }

    .multi-line-content {
        margin-left: 20px;
        width: 100%;
        padding-top: 0;
    }

    .label {
        margin-bottom: 0.5rem;
    }
}

.multi-line-content {
    display: block;
}
.multi-line-content br + br {
    content: "";
    display: block;
    margin-bottom: 1em;
}
    
</style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Incident Report Details</h1>
        <div class="row">
        <div class="col-md-6">
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
                $back_link = 'view_submitted_incident_reports.php.php';
                break;
            case 'facilitator':
                $back_link = 'facilitator_incident_reports.php';
                break;
        }
        ?>
        <a href="<?php echo $back_link; ?>" class="btn btn-primary mt-3">Back to List</a>
    </div>
</body>
</html>