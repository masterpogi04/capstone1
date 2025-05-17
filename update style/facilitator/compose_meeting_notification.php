<?php
session_start();
include '../db.php';
// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}
$report_id = isset($_GET['report_id']) ? $_GET['report_id'] : null;
// Modified query to use the new column names
$query = "SELECT 
    ir.id as report_id,
    ir.date_reported,
    ir.description,
    s.student_id,
    s.first_name as student_fname,
    s.last_name as student_lname,
    sp.contact_number,
    sp.email as student_email,
    a.email as adviser_email,
    CONCAT(a.first_name, ' ', COALESCE(a.middle_initial, ''), ' ', a.last_name) as adviser_name,
    a.last_name as adviser_surname,
    m.meeting_date,
    m.venue
    FROM incident_reports ir
    JOIN student_violations sv ON ir.id = sv.incident_report_id
    JOIN tbl_student s ON sv.student_id = s.student_id
    LEFT JOIN student_profiles sp ON s.student_id = sp.student_id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
    LEFT JOIN meetings m ON ir.id = m.incident_report_id
    WHERE ir.id = ?
    ORDER BY m.meeting_date DESC LIMIT 1";
$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $connection->error);
}
$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
if (!$data) {
    die("Report not found");
}
// Format meeting date and time for display
$meeting_datetime = new DateTime($data['meeting_date']);
$formatted_date = $meeting_datetime->format('F j, Y');
$formatted_time = $meeting_datetime->format('g:i A');
// Adviser email template
$default_message = "Mr. / Ms. {$data['adviser_surname']},\n\n";
$default_message .= "I hope this email finds you well. This is to inform you that your advisee, {$data['student_fname']} {$data['student_lname']}, ";
$default_message .= "has a scheduled meeting at the Guidance Office.\n\n";
$default_message .= "Meeting Details:\n";
$default_message .= "Date: {$formatted_date}\n";
$default_message .= "Time: {$formatted_time}\n";
$default_message .= "Venue: {$data['venue']}\n\n";
$default_message .= "Please ensure that the student is informed and will attend this important meeting.\n\n";
$default_message .= "Best regards,\nCEIT Guidance Office";
// Student email template
$student_email_message = "Mr./Ms. {$data['student_lname']},\n\n";
$student_email_message .= "This is to inform you that you have a scheduled meeting at the Guidance Office.\n\n";
$student_email_message .= "Meeting Details:\n";
$student_email_message .= "Date: {$formatted_date}\n";
$student_email_message .= "Time: {$formatted_time}\n";
$student_email_message .= "Venue: {$data['venue']}\n\n";
$student_email_message .= "Please ensure to attend this important meeting.\n\n";
$student_email_message .= "Best regards,\nCEIT Guidance Office";
// SMS template (shorter version)
$sms_message = "Greetings! Mr. / Ms. {$data['student_lname']}, you have a scheduled meeting at CEIT Guidance Office on {$formatted_date} at {$formatted_time}. Please be on time.";
// Add this after fetching the data
if (empty($data['meeting_date'])) {
    $_SESSION['error_message'] = "No active meeting found for this report.";
    header("Location: view_approved_reports.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose Meeting Notification</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Global Styles */
body {
    background: linear-gradient(135deg, #0d693e, #004d4d);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #333;
    line-height: 1.6;
}

.container {
    background-color: #ffffff;
    border-radius: 16px;
    padding: 2rem;
    margin-top: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    max-width: 1000px;
}

/* Header and Navigation */
.btn-secondary {
    background-color: #F4A261;
    border: none;
    color: #fff;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary:hover {
    background-color: #E76F51;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 111, 81, 0.2);
}

/* Information Cards */
.notification-details {
    background-color: #f8fafc;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.notification-details:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transform: translateY(-2px);
}

.notification-details h4 {
    color: #1a365d;
    font-size: 1.25rem;
    margin-bottom: 1rem;
    font-weight: 600;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
}

/* Status Badges */
.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge.has-contact {
    background-color: #0d693e;
    color: white;
}

.status-badge.no-contact {
    background-color: #dc2626;
    color: white;
}

.status-badge i {
    font-size: 0.875rem;
}

/* Form Elements */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #4a5568;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.975rem;
    transition: all 0.2s ease;
    resize: vertical;
}

.form-control:focus {
    border-color: #0d693e;
    box-shadow: 0 0 0 3px rgba(13, 105, 62, 0.1);
    outline: none;
}

/* Notification Sections */
.notification-section {
    background-color: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.notification-section h4 {
    color: #1a365d;
    font-size: 1.25rem;
    margin-bottom: 1rem;
}
/* Back Button */
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
/* Buttons */
.btn-primary {
    background-color: #0d693e;
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    background-color: #0a5432;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 105, 62, 0.2);
}

.btn-primary i {
    font-size: 1rem;
}

/* Helper Text */
.form-text {
    color: #718096;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

/* Sweet Alert Customization */
.swal2-popup {
    border-radius: 16px;
    padding: 2rem;
}

.swal2-title {
    color: #1a365d !important;
    font-size: 1.5rem !important;
}

.swal2-html-container {
    color: #4a5568 !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        margin: 1rem;
        padding: 1.5rem;
    }
    
    .notification-section {
        padding: 1rem;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
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

.notification-details {
    animation: fadeIn 0.3s ease-out backwards;
}

.notification-section {
    animation: fadeIn 0.3s ease-out backwards;
}
    </style>
</head>
<body>
    <div class="container">
    <div class="row mb-4">
    <div class="col">
        <a href="schedule_generator.php?report_id=<?php echo $report_id; ?>" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        </a>
    </div>
</div>

        <h2 class="mb-4">Compose Meeting Notification</h2>

        <div class="notification-details">
            <h4>Student Information</h4>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($data['student_fname'] . ' ' . $data['student_lname']); ?></p>
            <p>
                <strong>Contact:</strong> 
                <?php if (!empty($data['contact_number'])): ?>
                    <span class="status-badge has-contact">
                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($data['contact_number']); ?>
                    </span>
                <?php else: ?>
                    <span class="status-badge no-contact">No contact number available</span>
                <?php endif; ?>
            </p>
            <p>
                <strong>Email:</strong>
                <?php if (!empty($data['student_email'])): ?>
                    <span class="status-badge has-contact">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($data['student_email']); ?>
                    </span>
                <?php else: ?>
                    <span class="status-badge no-contact">No email available</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="notification-details">
            <h4>Adviser Information</h4>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($data['adviser_name']); ?></p>
            <p>
                <strong>Email:</strong>
                <?php if (!empty($data['adviser_email'])): ?>
                    <span class="status-badge has-contact">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($data['adviser_email']); ?>
                    </span>
                <?php else: ?>
                    <span class="status-badge no-contact">No email available</span>
                <?php endif; ?>
            </p>
        </div>

        <form id="notificationForm" action="send_meeting_notification.php" method="POST">
            <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
            
            <?php if (!empty($data['adviser_email'])): ?>
                <div class="notification-section mb-4">
                    <h4>Adviser Email Notification</h4>
                    <div class="form-group">
                        <label>Email to: <?php echo htmlspecialchars($data['adviser_email']); ?></label>
                        <textarea class="form-control" name="adviser_message" rows="8" required><?php echo htmlspecialchars($default_message); ?></textarea>
                        <button type="button" class="btn btn-primary mt-2" onclick="sendNotification('adviser')">
                            <i class="fas fa-envelope"></i> Send to Adviser
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($data['student_email'])): ?>
                <div class="notification-section mb-4">
                    <h4>Student Email Notification</h4>
                    <div class="form-group">
                        <label>Email to: <?php echo htmlspecialchars($data['student_email']); ?></label>
                        <textarea class="form-control" name="student_email_message" rows="8"><?php echo htmlspecialchars($student_email_message); ?></textarea>
                        <button type="button" class="btn btn-primary mt-2" onclick="sendNotification('student_email')">
                            <i class="fas fa-envelope"></i> Send Email to Student
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($data['contact_number'])): ?>
                <div class="notification-section mb-4">
                    <h4>Student SMS Notification</h4>
                    <div class="form-group">
                        <label>SMS to: <?php echo htmlspecialchars($data['contact_number']); ?></label>
                        <textarea class="form-control" name="sms_message" rows="4"><?php echo htmlspecialchars($sms_message); ?></textarea>
                        <small class="form-text text-muted">SMS messages should be brief and concise</small>
                        <button type="button" class="btn btn-primary mt-2" onclick="sendNotification('sms')">
                            <i class="fas fa-sms"></i> Send SMS to Student
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>

   <script>
function sendNotification(type) {
    Swal.fire({
        title: 'Send Notification?',
        text: 'Are you sure you want to send this notification?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, send it!',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const formData = new FormData(document.getElementById('notificationForm'));
            formData.append('notification_type', type);
            
            return fetch('send_meeting_notification.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to send notification');
                }
                return data;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Success!',
                text: 'Notification sent successfully',
                icon: 'success'
            });
        }
    }).catch(error => {
        Swal.fire({
            title: 'Error!',
            text: error.message,
            icon: 'error'
        });
    });
}
</script>
</body>
</html>