<?php
// view_all_minutes.php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Check if an incident report ID is provided
if (!isset($_GET['id'])) {
    die("No incident report ID provided.");
}

$incident_report_id = $connection->real_escape_string($_GET['id']);

// Function to create notifications
function createNotification($connection, $user_type, $user_id, $message, $link) {
    $query = "INSERT INTO notifications (user_type, user_id, message, link) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $user_type, $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

// Function to settle minutes
function settleMinutes($connection, $report_id, $meeting_minutes) {
    $connection->begin_transaction();
    try {
        // Verify that there are actual meeting minutes before settling
        $check_minutes = "SELECT COUNT(*) as minutes_count 
                         FROM meetings 
                         WHERE incident_report_id = ? 
                         AND meeting_minutes IS NOT NULL 
                         AND TRIM(meeting_minutes) != ''";
        $check_stmt = $connection->prepare($check_minutes);
        $check_stmt->bind_param("s", $report_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $minutes_count = $result->fetch_assoc()['minutes_count'];

        if ($minutes_count == 0) {
            throw new Exception("Cannot settle case without any meeting minutes recorded.");
        }

        // Update incident report status
        $update_query = "UPDATE incident_reports SET 
                        resolution_notes = ?,
                        status = 'Settled',
                        resolution_status = 'Resolved',
                        approval_date = NOW()
                        WHERE id = ?";
        $stmt = $connection->prepare($update_query);
        $stmt->bind_param("ss", $meeting_minutes, $report_id);
        $stmt->execute();

        // Update student_violations table
        $update_violations = "UPDATE student_violations SET status = 'Settled' 
                            WHERE incident_report_id = ?";
        $stmt = $connection->prepare($update_violations);
        $stmt->bind_param("s", $report_id);
        $stmt->execute();

        // Create notifications
        $notify_query = "SELECT sv.student_id, a.id as adviser_id
                        FROM student_violations sv 
                        JOIN tbl_student s ON sv.student_id = s.student_id
                        JOIN sections sec ON s.section_id = sec.id
                        JOIN tbl_adviser a ON sec.adviser_id = a.id
                        WHERE sv.incident_report_id = ?";
        $notify_stmt = $connection->prepare($notify_query);
        $notify_stmt->bind_param("s", $report_id);
        $notify_stmt->execute();
        $notify_result = $notify_stmt->get_result();
        $notify_info = $notify_result->fetch_assoc();

        if ($notify_info) {
            createNotification($connection, 'student', $notify_info['student_id'], 
                             "Your incident report has been marked as settled.",
                             "view_student_incident_reports.php?id=" . $report_id);
            
            createNotification($connection, 'adviser', $notify_info['adviser_id'],
                             "An incident report for your student has been marked as settled.",
                             "view_student_incident_reports.php?id=" . $report_id);
        }

        $connection->commit();
        return ['success' => true, 'message' => 'Case settled successfully'];
    } catch (Exception $e) {
        $connection->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Fetch incident report details
$incident_query = "SELECT ir.*, 
                   s.first_name, s.last_name, 
                   sv.status as violation_status, 
                   c.name as course_name,
                   GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses
                   FROM incident_reports ir
                   JOIN student_violations sv ON ir.id = sv.incident_report_id
                   JOIN tbl_student s ON sv.student_id = s.student_id
                   LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
                   LEFT JOIN sections sec ON s.section_id = sec.id
                   LEFT JOIN courses c ON sec.course_id = c.id
                   WHERE ir.id = ?
                   GROUP BY ir.id";

$incident_stmt = $connection->prepare($incident_query);
$incident_stmt->bind_param("s", $incident_report_id);
$incident_stmt->execute();
$incident_result = $incident_stmt->get_result();
$incident = $incident_result->fetch_assoc();

// Modify the meetings query to only fetch meetings with minutes
$meetings_query = "SELECT m.*,
                   ROW_NUMBER() OVER (PARTITION BY m.incident_report_id 
                                    ORDER BY m.meeting_date ASC) as calculated_sequence
                   FROM meetings m
                   WHERE m.incident_report_id = ?
                   AND (m.meeting_minutes IS NOT NULL AND m.meeting_minutes != '')
                   ORDER BY m.meeting_date DESC";

$meetings_stmt = $connection->prepare($meetings_query);
$meetings_stmt->bind_param("s", $incident_report_id);
$meetings_stmt->execute();
$meetings_result = $meetings_stmt->get_result();

// Count meetings with actual minutes
$minutes_count = $meetings_result->num_rows;
$meetings = [];
while ($meeting = $meetings_result->fetch_assoc()) {
    $meetings[] = $meeting;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Meeting Minutes</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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
        .incident-details {
            background-color: #f4f4f4;
            border-radius: 10px;
            padding: 20px;
        }
        .meeting-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 15px;
        }
        .btn-secondary {
            background-color: #F4A261;
            border-color: #F4A261;
            color: #fff;
            padding: 10px 20px;
        }
        .card-header {
            background-color: #0d693e;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }
        .edit-btn {
            float: right;
            margin-left: 10px;
            padding: 0;
            background: none;
            border: none;
        }
        .edit-btn:hover {
            opacity: 0.8;
        }
        .edit-btn:focus {
            box-shadow: none;
        }
        .save-btn, .cancel-btn {
            display: none;
            float: right;
            margin-left: 10px;
        }
        .edit-mode textarea, .edit-mode input {
            width: 100%;
            margin-bottom: 10px;
        }
        .edit-controls {
            margin-top: 10px;
            text-align: right;
        }
        .settle-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .settle-btn:hover {
            background-color: #218838;
        }
        .settle-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .swal2-popup {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
    <a href="view_approved_reports.php" class="btn btn-secondary mb-4">
        <i class="fas fa-arrow-left"></i> Back to Incident Reports
    </a>

    <h1>View Meeting Minutes</h1>

    <!-- Incident Report Details Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>Incident Report Details</h3>
        </div>
        <div class="incident-details">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Report ID:</strong> <?php echo htmlspecialchars($incident['id']); ?></p>
                    <p><strong>Student:</strong> <?php echo htmlspecialchars($incident['first_name'] . ' ' . $incident['last_name']); ?></p>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($incident['course_name']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Date Reported:</strong> <?php echo htmlspecialchars($incident['date_reported']); ?></p>
                    <p><strong>Witnesses:</strong> <?php echo htmlspecialchars($incident['witnesses'] ?? 'No witnesses'); ?></p>
                    <p><strong>Violation Description:</strong> <?php echo htmlspecialchars($incident['description']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Meetings Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">Meeting Records</h3>
            <?php if ($minutes_count > 0): ?>
                <a href="generate_minutes_pdf.php?id=<?php echo $incident_report_id; ?>" class="btn btn-success">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($meetings)): ?>
                <?php foreach ($meetings as $meeting): ?>
                    <div class="card mb-3 border-success" id="meeting-<?php echo $meeting['id']; ?>">
                        <div class="card-header text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    Meeting #<?php echo htmlspecialchars($meeting['calculated_sequence']); ?> - 
                                    <?php echo date('F j, Y, g:i A', strtotime($meeting['meeting_date'])); ?>
                                </h5>
                                <div>
                                    <button class="btn btn-link btn-sm edit-btn" onclick="toggleEdit(<?php echo $meeting['id']; ?>)">
                                        <i class="fas fa-edit fa-lg text-white"></i>
                                    </button>
                                    <button class="btn btn-success btn-sm save-btn" onclick="saveMeeting(<?php echo $meeting['id']; ?>)" style="display: none;">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                    <button class="btn btn-warning btn-sm cancel-btn" onclick="cancelEdit(<?php echo $meeting['id']; ?>)" style="display: none;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="form-<?php echo $meeting['id']; ?>" class="meeting-form">
                                <input type="hidden" name="meeting_id" value="<?php echo $meeting['id']; ?>">
                                
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"><strong>Venue:</strong></label>
                                    <div class="col-sm-9">
                                        <span class="display-value"><?php echo htmlspecialchars($meeting['venue']); ?></span>
                                        <input type="text" class="form-control edit-field" name="venue" 
                                               value="<?php echo htmlspecialchars($meeting['venue']); ?>" style="display: none;">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"><strong>Persons Present:</strong></label>
                                    <div class="col-sm-9">
                                        <span class="display-value">
                                            <?php 
                                                $attendees = $meeting['persons_present'] ? json_decode($meeting['persons_present'], true) : [];
                                                echo htmlspecialchars($attendees ? implode(', ', $attendees) : '');
                                            ?>
                                        </span>
                                        <input type="text" class="form-control edit-field" name="persons_present" 
                                               value="<?php echo htmlspecialchars($attendees ? implode(', ', $attendees) : ''); ?>" 
                                               style="display: none;">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"><strong>Minutes:</strong></label>
                                    <div class="col-sm-9">
                                        <span class="display-value"><?php echo nl2br(htmlspecialchars($meeting['meeting_minutes'])); ?></span>
                                        <textarea class="form-control edit-field" name="meeting_minutes" 
                                                  rows="5" style="display: none;"><?php echo htmlspecialchars($meeting['meeting_minutes']); ?></textarea>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label"><strong>Prepared By:</strong></label>
                                    <div class="col-sm-9">
                                        <p><?php echo htmlspecialchars($meeting['prepared_by']); ?></p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-center mt-4">
                    <button type="button" class="settle-btn" onclick="settleCase()">
                        <i class="fas fa-check-circle"></i> Mark Case as Settled
                    </button>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No meeting minutes have been recorded yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function saveMeeting(meetingId) {
            const form = $(`#form-${meetingId}`);
            const formData = new FormData(form[0]);

            const saveBtn = form.find('.save-btn');
            const originalSaveBtn = saveBtn.html();
            saveBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
            saveBtn.prop('disabled', true);

            $.ajax({
                url: 'update_meeting.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = typeof response === 'object' ? response : JSON.parse(response);
                        
                        if (result.success) {
                            const meetingCard = $(`#meeting-${meetingId}`);
                            const venue = formData.get('venue');
                            const persons = formData.get('persons_present');
                            const minutes = formData.get('meeting_minutes');
                            
                            meetingCard.find('[name="venue"]').prev('.display-value').html(venue);
                            meetingCard.find('[name="persons_present"]').prev('.display-value').html(persons);
                            meetingCard.find('[name="meeting_minutes"]').prev('.display-value').html(minutes.replace(/\n/g, '<br>'));
                            
                            meetingCard.find('[name="venue"]').val(venue);
                            meetingCard.find('[name="persons_present"]').val(persons);
                            meetingCard.find('[name="meeting_minutes"]').val(minutes);
                            
                            cancelEdit(meetingId);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Meeting details updated successfully!',
                                confirmButtonColor: '#0d693e'
                            });
                        } else {
                            throw new Error(result.message || 'Unknown error occurred');
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Error processing response: ' + e.message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', {xhr, status, error});
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Failed to connect to server. Please try again.',
                        confirmButtonColor: '#dc3545'
                    });
                },
                complete: function() {
                    saveBtn.html(originalSaveBtn);
                    saveBtn.prop('disabled', false);
                }
            });
        }

        function settleCase() {
            Swal.fire({
                title: 'Settle Case',
                text: 'Are you sure you want to mark this case as settled? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, settle case',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const latestMeeting = document.querySelector('.meeting-form:first-of-type textarea[name="meeting_minutes"]');
                    const latestMinutes = latestMeeting ? latestMeeting.value : '';
                    
                    const formData = new FormData();
                    formData.append('action', 'settle');
                    formData.append('report_id', '<?php echo $incident_report_id; ?>');
                    formData.append('meeting_minutes', latestMinutes);

                    const settleBtn = document.querySelector('.settle-btn');
                    const originalText = settleBtn.innerHTML;
                    settleBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    settleBtn.disabled = true;

                    fetch('settle_case.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Case has been successfully settled.',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                window.location.href = 'settled_incident_reports.php';
                            });
                        } else {
                            throw new Error(data.message || 'Failed to settle case');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to settle case: ' + error.message,
                            confirmButtonColor: '#dc3545'
                        });
                        settleBtn.innerHTML = originalText;
                        settleBtn.disabled = false;
                    });
                }
            });
        }

        function toggleEdit(meetingId) {
            const meetingCard = $(`#meeting-${meetingId}`);
            const editBtn = meetingCard.find('.edit-btn');
            const saveBtn = meetingCard.find('.save-btn');
            const cancelBtn = meetingCard.find('.cancel-btn');
            
            // Toggle buttons
            editBtn.hide();
            saveBtn.show();
            cancelBtn.show();
            
            // Toggle fields
            meetingCard.find('.display-value').hide();
            meetingCard.find('.edit-field').show();
        }

        function cancelEdit(meetingId) {
            const meetingCard = $(`#meeting-${meetingId}`);
            const editBtn = meetingCard.find('.edit-btn');
            const saveBtn = meetingCard.find('.save-btn');
            const cancelBtn = meetingCard.find('.cancel-btn');
            
            // Toggle buttons
            editBtn.show();
            saveBtn.hide();
            cancelBtn.hide();
            
            // Toggle fields
            meetingCard.find('.display-value').show();
            meetingCard.find('.edit-field').hide();
        }

        function settleCase() {
            if (confirm('Are you sure you want to mark this case as settled? This action cannot be undone.')) {
                const latestMeeting = document.querySelector('.meeting-form:first-of-type textarea[name="meeting_minutes"]');
                const latestMinutes = latestMeeting ? latestMeeting.value : '';
                
                const formData = new FormData();
                formData.append('action', 'settle');
                formData.append('report_id', '<?php echo $incident_report_id; ?>');
                formData.append('meeting_minutes', latestMinutes);

                // Disable settle button and show loading state
                const settleBtn = document.querySelector('.settle-btn');
                const originalText = settleBtn.innerHTML;
                settleBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                settleBtn.disabled = true;

                fetch('settle_case.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Case has been successfully settled.');
                        window.location.href = 'settled_incident_reports.php';
                    } else {
                        throw new Error(data.message || 'Failed to settle case');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error settling case: ' + error.message);
                    // Restore button state
                    settleBtn.innerHTML = originalText;
                    settleBtn.disabled = false;
                });
            }
        }
    </script>
</body>
</html>