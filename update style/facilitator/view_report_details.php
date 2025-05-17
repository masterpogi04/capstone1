<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$report_id = $_GET['id'] ?? '';

// Fetch report details along with involved students and their advisers
   $query = "
    SELECT ir.*, 
           sv.student_id,
           sv.student_name,
           s.first_name, 
           s.last_name,
           c.name AS course_name,
           a.id AS adviser_id, 
           CONCAT(a.first_name, ' ', 
                CASE 
                    WHEN a.middle_initial IS NOT NULL AND a.middle_initial != '' 
                    THEN CONCAT(a.middle_initial, '. ') 
                    ELSE '' 
                END,
                a.last_name) AS adviser_name
    FROM incident_reports ir
    JOIN student_violations sv ON ir.id = sv.incident_report_id
    LEFT JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN courses c ON sec.course_id = c.id
    LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
    WHERE ir.id = ?
";

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

while ($row = $result->fetch_assoc()) {
    if (!$report) {
        $report = $row;
    }
    
    if ($row['student_id'] && $row['first_name'] && $row['last_name']) {
        // For CEIT students - keep all details
        $students[$row['student_id']] = [
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'course' => $row['course_name']
        ];
    } else {
        // For non-CEIT students - just store the name
        $students[] = ['name' => $row['student_name']];
    }
    
    if ($row['adviser_id']) {
        $advisers[$row['adviser_id']] = $row['adviser_name'];
    }
}

if (!$report) {
    die("Report not found.");
}

// Function to notify student and adviser
function notifyParties($connection, $report_id, $status, $students, $advisers) {
    foreach ($students as $student_id => $student_name) {
        $student_message = "Your incident report has been updated to: $status";
        $student_link = "view_student_incident_reports.php?id=" . $report_id;
        
        $notification_query = "INSERT INTO notifications (user_type, user_id, message, link, is_read) 
                             VALUES ('student', ?, ?, ?, 0)";
        
        $notify_stmt = $connection->prepare($notification_query);
        $notify_stmt->bind_param("sss", $student_id, $student_message, $student_link);
        $notify_stmt->execute();
        $notify_stmt->close();
    }

    foreach ($advisers as $adviser_id => $adviser_name) {
        $adviser_message = "An incident report for your student has been updated to: $status";
        $adviser_link = "view_student_incident_reports.php?id=" . $report_id;
        
        $notification_query = "INSERT INTO notifications (user_type, user_id, message, link, is_read) 
                             VALUES ('adviser', ?, ?, ?, 0)";
        
        $notify_stmt = $connection->prepare($notification_query);
        $notify_stmt->bind_param("sss", $adviser_id, $adviser_message, $adviser_link);
        $notify_stmt->execute();
        $notify_stmt->close();
    }
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
        notifyParties($connection, $report_id, $new_status, $students, $advisers);

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
    <style>
       /* Variables */
:root {
    --primary: #0d693e;
    --primary-dark: #094e2e;
    --secondary: #e67e22;
    --secondary-dark: #d35400;
    --background: linear-gradient(135deg, #0d693e, #004d4d);
    --white: #ffffff;
    --gray-100: #f8f9fa;
    --gray-200: #e9ecef;
    --gray-300: #dee2e6;
    --text-primary: #2d3748;
    --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.1);
    --radius-lg: 15px;
    --radius-md: 10px;
    --radius-sm: 8px;
}

/* Global Styles */
body {
    background: var(--background);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    margin: 0;
    color: var(--text-primary);
    line-height: 1.6;
}

.container {
    background-color: var(--white);
    border-radius: var(--radius-lg);
    padding: 2.5rem;
    margin-top: 80px !important;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-md);
    position: relative;
    max-width: 1200px;
}

/* Header Styles */
h2 {
    color: var(--primary);
    font-size: 2rem;
    font-weight: 600;
    padding-bottom: 1rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid var(--primary);
    letter-spacing: 0.5px;
}


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
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}
/* Definition List Styles */
.row {
    margin: 0 -15px;
}

dt {
    font-weight: 600;
    color: var(--primary);
    padding: 0.75rem 1rem;
    background-color: var(--gray-100);
    border-radius: var(--radius-sm);
    margin-bottom: 0.5rem;
}

dd {
    padding: 0.75rem 1rem;
    margin-bottom: 1.5rem;
    background-color: var(--white);
   
    border-radius: var(--radius-sm);
}

/* List Styles */
ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

ul li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-200);
}

ul li:last-child {
    border-bottom: none;
}

/* Badge Styles */
.badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.9rem;
}

.badge-warning {
    background-color: #ffeaa7;
    color: #d35400;
}

.badge-info {
    background-color: #81ecec;
    color: #00838f;
}

/* Image Container */
.incident-image-container {
    max-width: 400px;
    margin: 2rem 0;
    border-radius: var(--radius-md);
    overflow: hidden;
    box-shadow: var(--shadow-md);
}

.incident-image {
    width: 100%;
    height: auto;
    display: block;
    transition: transform 0.3s ease;
}

.incident-image:hover {
    transform: scale(1.02);
}

/* Action Buttons */
.action-buttons {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: var(--radius-sm);
    display: inline-flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
    border: none;
}

.btn-meeting {
    background-color: var(--secondary);
    color: var(--white);
}

.btn-meeting:hover:not(:disabled) {
    background-color: var(--secondary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.2);
}

.btn-primary {
    background-color: var(--primary);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 105, 62, 0.2);
}

.btn-info {
    background-color: #0088cc;
    color: var(--white);
}

.btn-info:hover {
    background-color: #006699;
    color: var(--white);
}

/* Disabled Button States */
.btn:disabled {
    background-color: var(--gray-300);
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 1.5rem;
        margin: 60px 1rem 1rem 1rem !important;
    }

    h2 {
        font-size: 1.5rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    dt, dd {
        padding: 0.5rem;
    }

    .back-nav {
        top: 10px;
        left: 10px;
        padding: 0.5rem 1rem;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.container {
    animation: fadeIn 0.3s ease-out;
}
    </style>
</head>
<body>

    <div class="container mt-5">
    <a href="view_facilitator_incident_reports.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Reports
    </a>
        <h2>Incident Report Details</h2>
        <dl class="row">
            <dt class="col-sm-3">Report ID:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['id']); ?></dd>

            <dt class="col-sm-3">Date Reported:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['date_reported']); ?></dd>

            <dt class="col-sm-3">Place, Date & Time of Incident:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['place']); ?></dd>
            
            <dt class="col-sm-3">Students Involved:</dt>
            <dd class="col-sm-9">
                <ul>
                    <?php foreach ($students as $student): ?>
                        <li><?php echo htmlspecialchars($student['name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </dd>
            
            <dt class="col-sm-3">Advisers:</dt>
            <dd class="col-sm-9">
                <ul>
                    <?php foreach ($advisers as $id => $name): ?>
                        <li><?php echo htmlspecialchars($name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </dd>
            
            <dt class="col-sm-3">Description:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['description']); ?></dd>
            
            <dt class="col-sm-3">Current Status:</dt>
            <dd class="col-sm-9">
                <span class="badge badge-<?php echo $report['status'] === 'For Meeting' ? 'warning' : 'info'; ?>">
                    <?php echo htmlspecialchars($report['status']); ?>
                </span>
            </dd>
        </dl>
        
        <?php if (!empty($report['file_path'])): ?>
            <h4>Uploaded Image:</h4>
            <div class="incident-image-container">
                <img src="<?php echo htmlspecialchars($report['file_path']); ?>" alt="Incident Image" class="incident-image">
            </div>
        <?php else: ?>
            <p>No image uploaded for this incident.</p>
        <?php endif; ?>
        
        <form id="statusUpdateForm">
            <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
            <input type="hidden" name="new_status" value="For Meeting">
            
            <div class="action-buttons">
                <button type="button" class="btn btn-meeting" id="setForMeetingBtn" 
                        <?php echo ($report['status'] === 'For Meeting' || $report['status'] === 'Settled') ? 'disabled' : ''; ?>
                        title="<?php echo ($report['status'] === 'Settled') ? 'Cannot set meeting for settled incidents' : 
                                    (($report['status'] === 'For Meeting') ? 'Meeting is already set' : ''); ?>">
                    <i class="fas fa-calendar-check"></i>
                    Set for Meeting
                </button>
                
                <a href="view_report_details-generate_pdf.php?id=<?php echo $report_id; ?>"  target="_blank"  class="btn btn-primary">
                    <i class="fas fa-file-pdf"></i>
                    Generate PDF
                </a>
                
                <a href="view_approved_reports.php" class="btn btn-info">
                    <i class="fas fa-calendar-alt"></i>
                    View Meetings
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
                                    text: 'Report has been set for meeting.',
                                    icon: 'success',
                                    confirmButtonColor: '#0d693e'
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