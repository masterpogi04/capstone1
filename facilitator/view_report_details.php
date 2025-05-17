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
FROM incident_reports ir
LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id 
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

// Function to notify student and adviser and instructor
function notifyParties($connection, $report_id, $status) {
    // First, get the report details to check who reported it
    $report_query = "SELECT id, reporters_id, reported_by_type, reported_by 
                    FROM incident_reports 
                    WHERE id = ?";
    $report_stmt = $connection->prepare($report_query);
    $report_stmt->bind_param("s", $report_id);
    $report_stmt->execute();
    $report_result = $report_stmt->get_result();
    $report_data = $report_result->fetch_assoc();
    $report_stmt->close();

    // Notify the person who reported the incident based on their role
    if (!empty($report_data['reporters_id'])) {
        $reporter_message = "The status of an incident report you submitted has been updated to: $status";
        $reporter_link = "";
        
        // Set appropriate link based on reporter type
        switch($report_data['reported_by_type']) {
            case 'instructor':
                $reporter_link = "view_incident_reports.php?id=" . $report_id;
                break;
            case 'facilitator':
                $reporter_link = "view_facilitator_incident_reports.php";
                break;
            case 'adviser':
                $reporter_link = "view_submitted_incident_reports-adviser.php";
                break;
            case 'student':
                $reporter_link = "view_student_incident_reports.php?id=" . $report_id;
                break;
            case 'guard':
                $reporter_link = "view_submitted_incident_reports_guard.php";
                break;
            default:
                $reporter_link = "view_incident_reports.php";
        }
        
        $notification_query = "INSERT INTO notifications (user_type, user_id, message, link, is_read) 
                             VALUES (?, ?, ?, ?, 0)";
        
        $notify_stmt = $connection->prepare($notification_query);
        $notify_stmt->bind_param("ssss", $report_data['reported_by_type'], $report_data['reporters_id'], $reporter_message, $reporter_link);
        $notify_stmt->execute();
        $notify_stmt->close();
    }

    // Get student IDs for notifications
    $student_query = "SELECT student_id FROM student_violations WHERE incident_report_id = ? AND student_id IS NOT NULL";
    $student_stmt = $connection->prepare($student_query);
    $student_stmt->bind_param("s", $report_id);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    // Notify students
    while($student = $student_result->fetch_assoc()) {
        if(!empty($student['student_id'])) {
            $student_message = "Your incident report has been updated to: $status";
            $student_link = "view_student_incident_reports.php?id=" . $report_id;
            
            $notification_query = "INSERT INTO notifications (user_type, user_id, message, link, is_read) 
                                 VALUES ('student', ?, ?, ?, 0)";
            
            $notify_stmt = $connection->prepare($notification_query);
            $notify_stmt->bind_param("sss", $student['student_id'], $student_message, $student_link);
            $notify_stmt->execute();
            $notify_stmt->close();
        }
    }
    $student_stmt->close();

    // Get adviser IDs for notifications
    $adviser_query = "SELECT DISTINCT adviser_id FROM student_violations 
                     WHERE incident_report_id = ? AND adviser_id IS NOT NULL AND adviser_id != '0'";
    $adviser_stmt = $connection->prepare($adviser_query);
    $adviser_stmt->bind_param("s", $report_id);
    $adviser_stmt->execute();
    $adviser_result = $adviser_stmt->get_result();
    
    // Notify advisers
    while($adviser = $adviser_result->fetch_assoc()) {
        if(!empty($adviser['adviser_id']) && $adviser['adviser_id'] != '0') {
            $adviser_message = "An incident report for your student has been updated to: $status";
            $adviser_link = "view_student_incident_reports.php?id=" . $report_id;
            
            $notification_query = "INSERT INTO notifications (user_type, user_id, message, link, is_read) 
                                 VALUES ('adviser', ?, ?, ?, 0)";
            
            $notify_stmt = $connection->prepare($notification_query);
            $notify_stmt->bind_param("sss", $adviser['adviser_id'], $adviser_message, $adviser_link);
            $notify_stmt->execute();
            $notify_stmt->close();
        }
    }
    $adviser_stmt->close();
}

if (isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    $connection->begin_transaction();

    try {
        // Update incident_reports table
        $update_query = "UPDATE incident_reports SET status = ?, approval_date = NOW(), facilitator_id = ? WHERE id = ?";
        $update_stmt = $connection->prepare($update_query);
        $update_stmt->bind_param("sis", $new_status, $_SESSION['user_id'], $report_id);
        $update_stmt->execute();

        // Update student_violations table
        $update_violation_query = "UPDATE student_violations SET status = ? WHERE incident_report_id = ?";
        $update_violation_stmt = $connection->prepare($update_violation_query);
        $update_violation_stmt->bind_param("ss", $new_status, $report_id);
        $update_violation_stmt->execute();

        // Send notifications
        notifyParties($connection, $report_id, $new_status);

        $connection->commit();
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit();
    } catch (Exception $e) {
        $connection->rollback();
        error_log("Failed to update status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="incident_details.css">
</head> 
<body> 
 
    <div class="container">
            <a href="view_facilitator_incident_reports.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Reports
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
            
            <div class="action-buttons">
                <button type="button" class="btn btn-meeting" id="setForMeetingBtn" 
                        <?php echo ($report['status'] === 'For Meeting' || $report['status'] === 'Settled') ? 'disabled' : ''; ?>
                        title="<?php echo ($report['status'] === 'Settled') ? 'Cannot set meeting for settled incidents' : 
                                    (($report['status'] === 'For Meeting') ? 'Meeting is already set' : ''); ?>">
                    <i class="fas fa-calendar-check"></i>
                    Set for<br>Meeting
                </button>
                
                <a href="view_report_details-generate_pdf.php?id=<?php echo $report_id; ?>"  target="_blank"  class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i>
                    Generate<br> PDF
                </a>
                
                <a href="view_approved_reports.php" class="btn btn-info">
                    <i class="fas fa-calendar-alt"></i>
                    View<br>Meeting/s
                </a>
            </div>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#setForMeetingBtn').click(function() {
            Swal.fire({
                title: 'Set for Meeting',
                text: 'Are you sure you want to set this incident report for meeting?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#e67e22',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, set for meeting',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: {
                            update_status: true,
                            new_status: 'For Meeting',
                            report_id: $('input[name="report_id"]').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    html: '<i class="fas fa-check-circle" style="color: #0d693e; font-size: 60px; margin-bottom: 15px;"></i><br>Report has been set for meeting.',
                                    showConfirmButton: true,
                                    confirmButtonColor: '#0d693e',
                                    icon: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to update status.',
                                    icon: 'error',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while updating the status.',
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    });
                }
            });
        });
    });
    </script>
</body>
</html>