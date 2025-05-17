<?php
session_start();
include '../db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: login.php");
    exit();
}

// Main query for displaying reports remains the same
$query = "SELECT p.*, g.name AS guard_name,
          GROUP_CONCAT(DISTINCT CONCAT(psv.student_id, ':', psv.student_name) SEPARATOR '|') AS involved_students,
          GROUP_CONCAT(DISTINCT CONCAT(piw.witness_type, ':', piw.witness_id, ':', piw.witness_name) SEPARATOR '|') AS witnesses
          FROM pending_incident_reports p
          LEFT JOIN tbl_guard g ON p.guard_id = g.id
          LEFT JOIN pending_student_violations psv ON p.id = psv.pending_report_id
          LEFT JOIN pending_incident_witnesses piw ON p.id = piw.pending_report_id
          WHERE p.status = 'Pending'
          GROUP BY p.id
          ORDER BY p.created_at DESC";
$result = $connection->query($query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_id = $_POST['report_id'];
    
    try {
        $response = handleEscalation($connection, $report_id);
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("Error in incident reports handler: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
    exit();
}

function handleEscalation($connection, $report_id) {
    $connection->begin_transaction();
    
    try {
        // Generate new report ID
        $new_id = generateIncidentReportId($connection);
        
        // Move report to final tables
        if (!moveReportToFinal($connection, $report_id, $new_id)) {
            throw new Exception("Failed to move report to final tables");
        }

        // Update pending report status
        $stmt = $connection->prepare("UPDATE pending_incident_reports SET status = 'Escalated' WHERE id = ?");
        $stmt->bind_param("i", $report_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update report status");
        }
        $stmt->close();

        // Get guard information
        $query = "SELECT g.email, g.name AS guard_name, p.date_reported, g.id AS guard_id
                 FROM pending_incident_reports p
                 JOIN tbl_guard g ON p.guard_id = g.id
                 WHERE p.id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $guard_data = $result->fetch_assoc();
        $stmt->close();

        if (!$guard_data) {
            throw new Exception("Guard data not found");
        }

        // Add notification
        $message = "The Incident Report submitted on {$guard_data['date_reported']} has been escalated to CEIT Guidance Facilitator. New report ID: $new_id. Kindly check your Dashboard. Thanks";
        $stmt = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link) VALUES ('guard', ?, ?, 'view_incident_reports.php')");
        $stmt->bind_param("is", $guard_data['guard_id'], $message);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert notification");
        }
        $stmt->close();

        $connection->commit();

        // Attempt to send email notification
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ceitguidanceoffice@gmail.com';
            $mail->Password = 'qapb ebhc owts ioel';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('ceitguidanceoffice@gmail.com', 'CEIT Guidance Office');
            $mail->addAddress($guard_data['email'], $guard_data['guard_name']);
            $mail->isHTML(true);
            $mail->Subject = "Incident Report Escalated";
            $mail->Body = $message;
            
            $mail_sent = $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            $mail_sent = false;
        }

        return [
            'status' => 'success',
            'message' => 'Report escalated to facilitator' . (!$mail_sent ? ' but notification email could not be sent' : '')
        ];

    } catch (Exception $e) {
        $connection->rollback();
        throw $e;
    }
}

function moveReportToFinal($connection, $report_id, $new_id) {
    try {
        // First, fetch and insert the main report
        $report_query = "SELECT * FROM pending_incident_reports WHERE id = ?";
        $stmt = $connection->prepare($report_query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Insert into incident_reports (keeping this part the same)
        $stmt = $connection->prepare("INSERT INTO incident_reports (id, date_reported, place, description, reported_by, reporters_id, file_path, status, reported_by_type)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("ssssssss", $new_id, $report['date_reported'], $report['place'], $report['description'], 
                         $report['reported_by'], $report['guard_id'], $report['file_path'], $report['reported_by_type']);
        $stmt->execute();
        $stmt->close();

        // Modified violations handling
        $violation_query = "SELECT * FROM pending_student_violations WHERE pending_report_id = ?";
        $stmt = $connection->prepare($violation_query);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $violations_result = $stmt->get_result();
        $stmt->close();

        $violation_stmt = $connection->prepare("INSERT INTO student_violations 
            (student_id, incident_report_id, violation_date, status, student_name, student_course, student_year_level) 
            VALUES (?, ?, ?, 'Pending', ?, ?, ?)");

   while ($violation = $violations_result->fetch_assoc()) {
    error_log("Processing violation - Raw data: " . json_encode($violation));
    
    // Always start with the original name from pending violations
    $student_name = trim($violation['student_name']); // Get the original name
    $student_id = $violation['student_id'];
    $student_course = null;
    $student_year_level = null;

    error_log("Initial student_name from pending: '$student_name'");

    // Check if this is a CEIT student
    if ($student_id) {
        $student_details = getStudentDetails($connection, $student_id);
        if ($student_details) {
            // This is a CEIT student - use their database details
            $student_name = $student_details['first_name'] . ' ' . $student_details['last_name'];
            $student_course = $student_details['course_name'];
            $student_year_level = $student_details['year_level'];
            error_log("Found CEIT student - Updated name: '$student_name'");
        } else {
            // Not a CEIT student - use the original name from pending_violations
            $student_id = null;
            // Keep $student_name as is - do not modify it
            error_log("Non-CEIT student - Keeping original name: '$student_name'");
        }
    }

    // Important validation
    if (empty($student_name)) {
        error_log("ERROR: Student name is empty for violation: " . json_encode($violation));
        throw new Exception("Student name cannot be empty. Original data: " . json_encode($violation));
    }

    try {
        // Disable the trigger temporarily to prevent it from overwriting our student_name
        $connection->query("DROP TRIGGER IF EXISTS before_student_violation_insert");
        
        $violation_stmt->bind_param("ssssss", 
            $student_id,
            $new_id,
            $report['date_reported'],
            $student_name,  // This should have either CEIT name or original pending name
            $student_course,
            $student_year_level
        );

        if (!$violation_stmt->execute()) {
            error_log("Failed to insert violation: " . $violation_stmt->error);
            throw new Exception("Failed to insert violation: " . $violation_stmt->error);
        }
        error_log("Successfully inserted violation for student: '$student_name'");
        
        // Recreate the trigger after insertion
        $connection->query("
        CREATE TRIGGER before_student_violation_insert BEFORE INSERT ON student_violations
        FOR EACH ROW
        BEGIN
            DECLARE student_fullname VARCHAR(100);
            DECLARE student_course_name VARCHAR(100);
            DECLARE student_year VARCHAR(20);
            
            IF NEW.student_id IS NOT NULL THEN
                SELECT 
                    CONCAT(ts.first_name, ' ', ts.last_name),
                    c.name,
                    s.year_level
                INTO 
                    student_fullname,
                    student_course_name,
                    student_year
                FROM tbl_student ts
                LEFT JOIN sections s ON ts.section_id = s.id
                LEFT JOIN courses c ON s.course_id = c.id
                WHERE ts.student_id = NEW.student_id;
                
                SET NEW.student_name = student_fullname;
                SET NEW.student_course = student_course_name;
                SET NEW.student_year_level = student_year;
            END IF;
        END
        ");

    } catch (Exception $e) {
        error_log("Error during violation insert: " . $e->getMessage());
        throw $e;
    }
}
$violation_stmt->close();

       // Modified witness handling
        $stmt = $connection->prepare("SELECT * FROM pending_incident_witnesses WHERE pending_report_id = ?");
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $witnesses_result = $stmt->get_result();
        $stmt->close();

        $witness_stmt = $connection->prepare("INSERT INTO incident_witnesses 
            (incident_report_id, witness_type, witness_id, witness_name, 
             witness_student_name, witness_course, witness_year_level, witness_email) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        while ($witness = $witnesses_result->fetch_assoc()) {
            $witness_id = null;
            $witness_student_name = $witness['witness_name']; // Default to provided name
            $witness_course = null;
            $witness_year_level = null;
            $witness_email = null;

            if ($witness['witness_type'] === 'student') {
                // Try to get CEIT student details if a student ID is provided
                if (!empty($witness['witness_id'])) {
                    $student_details = getStudentDetails($connection, $witness['witness_id']);
                    if ($student_details) {
                        // This is a CEIT student
                        $witness_id = $witness['witness_id'];
                        $witness_student_name = $student_details['first_name'] . ' ' . $student_details['last_name'];
                        $witness_course = $student_details['course_name'];
                        $witness_year_level = $student_details['year_level'];
                    }
                }
            } else if ($witness['witness_type'] === 'staff') {
                $witness_email = $witness['witness_email'];
            }

            $witness_stmt->bind_param("ssssssss", 
                $new_id,
                $witness['witness_type'],
                $witness_id,           // Will be NULL for non-CEIT witnesses
                $witness['witness_name'],
                $witness_student_name,
                $witness_course,       // Will be NULL for non-CEIT witnesses
                $witness_year_level,   // Will be NULL for non-CEIT witnesses
                $witness_email
            );
            
            if (!$witness_stmt->execute()) {
                throw new Exception("Failed to insert into incident_witnesses: " . $witness_stmt->error);
            }
        }
        $witness_stmt->close();
        return true;

    } catch (Exception $e) {
        error_log("Error in moveReportToFinal: " . $e->getMessage());
        throw $e;
    }
}

function generateIncidentReportId($connection) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    $academicYear = ($currentMonth >= 9) ? $currentYear : $currentYear - 1;
    $nextYear = $academicYear + 1;
    $academicYearShort = substr($academicYear, 2) . '-' . substr($nextYear, 2);

    $query = "SELECT MAX(CAST(SUBSTRING_INDEX(id, '-', -1) AS UNSIGNED)) as max_seq 
              FROM incident_reports 
              WHERE id LIKE 'CEIT-{$academicYearShort}-%'";
    $result = $connection->query($query);
    $row = $result->fetch_assoc();
    $nextSeq = ($row['max_seq'] ?? 0) + 1;

    return sprintf("CEIT-%s-%04d", $academicYearShort, $nextSeq);
}

function getStudentDetails($connection, $student_id) {
    $query = "SELECT s.first_name, s.last_name, c.name AS course_name, 
              sec.year_level, sec.section_no, a.name AS adviser_name
              FROM tbl_student s
              LEFT JOIN sections sec ON s.section_id = sec.id
              LEFT JOIN courses c ON sec.course_id = c.id
              LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
              WHERE s.student_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $student['year_and_section'] = $student['year_level'] . " - Section " . $student['section_no'];
        return $student;
    }
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Incident Reports - Dean View</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0d693e, #004d4d);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            padding: 40px;
            margin: 50px auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            overflow-x: hidden;
        }
        h2 {
            color: var(--primary-color);
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            text-align: center;
        }
        /* Table Styles */
.table {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.table th {
    background-color: #009E60;
    color: white;
    border: none;
}

.table td {
    vertical-align: middle;
}


    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85em;
        font-weight: 500;
    }

    .status-pending { background-color: #ffd700; color: #000; }
    .status-processing { background-color: #87ceeb; color: #000; }
    .status-meeting { background-color: #98fb98; color: #000; }
    .status-resolved { background-color: #90EE90; color: #000; }
    .status-rejected { background-color: #ff6b6b; color: #fff; }

    .btn-custom {
        background-color: var(--primary-color);
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        color: white;
        transition: all 0.3s;
    }

    .btn-custom:hover {
        background-color: var(--hover-color);
        transform: translateY(-1px);
    }

    .filters-section {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .search-box {
        position: relative;
    }

    .search-box input {
        padding-left: 35px;
        border-radius: 20px;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .checkbox-custom {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    

    @media (max-width: 768px) {
        .container {
            padding: 15px;
            margin: 20px auto;
        }
        
        .table-responsive {
            border-radius: 8px;
        }

        .stats-card {
            margin-bottom: 15px;
        }
    }
/* Back Button*/
.modern-back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #2EDAA8;
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(46, 218, 168, 0.15);
    letter-spacing: 0.3px;
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(46, 218, 168, 0.15);
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
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
    * Mobile-specific styles */
@media (max-width: 767px) {
    h1 {
        font-size: 1.5rem;
        margin: 10px 0;
        padding-bottom: 10px;
        text-align: left;
    }

    .row {
        margin: 0;
        display: block;
    }

    .col-md-6 {
        padding: 0;
        margin-bottom: 15px;
        width: 100%;
    }

    .details-card {
        padding: 12px;
        margin-bottom: 12px;
        border-radius: 8px;
    }

    p {
        margin: 8px 0;
        padding: 5px 0;
        font-size: 0.9rem;
        line-height: 1.4;
        display: flex;
        flex-direction: column;
    }

    .label {
        min-width: auto;
        margin-bottom: 4px;
        color: var(--primary-color);
        font-weight: 600;
        font-size: 0.85rem;
    }
    .btn-primary {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        font-size: 0.9rem;
        border-radius: 6px;
    }

    /* Improve touch targets */
    a, button {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    /* Status badges for mobile */
    .status-badge {
        padding: 4px 8px;
        font-size: 0.8rem;
        margin-top: 5px;
        display: inline-block;
    }
}
    </style>
</head>
<body>
<div class="container mt-5">
<a href="dean_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
    
    
    <h2>Pending Incident Reports</h2>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date Reported</th>
                    <th>Involved Students</th>
                    <th>Witnesses</th>
                    <th>Description</th>
                    <th>Reported By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['date_reported']); ?></td>
                        <td>
                            <?php 
                            $involved_students = explode('|', $row['involved_students']);
                            foreach ($involved_students as $student) {
                                if (empty($student)) continue;
                                list($student_id, $student_name) = explode(':', $student);
                                $student_details = getStudentDetails($connection, $student_id);
                                if ($student_details) {
                                    echo "<b>ID:</b> " . htmlspecialchars($student_id) . "<br>";
                                    echo "<b>Name:</b> " . htmlspecialchars($student_details['first_name'] . ' ' . $student_details['last_name']) . "<br>";
                                    echo "<b>Course:</b> " . htmlspecialchars($student_details['course_name']) . "<br>";
                                    echo "<b>Year & Section:</b> " . htmlspecialchars($student_details['year_and_section']) . "<br>";
                                    echo "<b>Adviser:</b> " . htmlspecialchars($student_details['adviser_name']) . "<br><br>";
                                } else {
                                    echo "<b>ID:</b> " . htmlspecialchars($student_id) . " (No student record found)<br>";
                                    echo "<b>Name: </b> " . htmlspecialchars($student_name) . "<br><br>";
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $witnesses = explode('|', $row['witnesses']);
                            foreach ($witnesses as $witness) {
                                if (empty($witness)) continue;
                                list($type, $id, $name) = explode(':', $witness);
                                echo "<b>Type:</b> " . htmlspecialchars($type) . "<br>";
                                echo "<b>ID:</b> " . htmlspecialchars($id) . "<br>";
                                echo "<b>Name:</b> " . htmlspecialchars($name) . "<br><br>";
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['guard_name']); ?></td>
                        <td>
                            <button onclick="confirmEscalation(<?php echo $row['id']; ?>)" class="btn btn-primary btn-sm">Escalate to Facilitator</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmEscalation(reportId) {
    Swal.fire({
        title: 'Escalate to Facilitator?',
        text: 'Are you sure you want to escalate this report to the facilitator?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, escalate it!'
    }).then((result) => {
        if (result.isConfirmed) {
            submitEscalation(reportId);
        }
    });
}

function submitEscalation(reportId) {
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we process your request.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: window.location.href,
        type: 'POST',
        data: { report_id: reportId },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: response.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
            console.error("Response Text: ", jqXHR.responseText);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while processing your request. Please check the console for more details.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}
</script>
</body>
</html>

