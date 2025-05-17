<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Handle POST request for scheduling/rescheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['report_id'])) {
        $report_id = $_POST['report_id'];
        $meeting_date = $_POST['meeting_date'];
        
        // Determine if this is a reschedule or initial schedule
        $is_rescheduled = isset($_POST['reschedule_meeting']);
        
        // Call scheduleMeeting function
        $result = scheduleMeeting($connection, $report_id, $meeting_date, $is_rescheduled);
        
        // Set session message based on result
        if ($result['status']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
        
        // Redirect back to the same page
        header("Location: schedule_generator.php?report_id=" . $report_id);
        exit();
    }
}

// Handle GET request for scheduling/rescheduling
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];
    
    // Check if a meeting already exists for this report
    $check_meeting_query = "SELECT meeting_date FROM meetings WHERE incident_report_id = ? ORDER BY meeting_date DESC LIMIT 1";
    $stmt = $connection->prepare($check_meeting_query);
    $stmt->bind_param("s", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_meeting = $result->fetch_assoc();
    
    // Prepare data for the form
    $existing_meeting_date = $existing_meeting ? date('Y-m-d\TH:i', strtotime($existing_meeting['meeting_date'])) : null;
}

// Function to create notifications
function createNotification($connection, $user_type, $user_id, $message, $link) {
    $query = "INSERT INTO notifications (user_type, user_id, message, link) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $user_type, $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

function scheduleMeeting($connection, $report_id, $meeting_date, $is_rescheduled = false) {
    // Validate meeting date and time
    $date_obj = new DateTime($meeting_date);
    $day_of_week = $date_obj->format('N');
    $hour = $date_obj->format('G');

    if ($day_of_week > 4 || $hour < 8 || $hour >= 16) {
        return [
            'status' => false,
            'message' => "Meetings can only be scheduled Monday through Thursday between 8:00 AM and 4:00 PM."
        ];
    }

    $connection->begin_transaction();

    try {
        if ($is_rescheduled) {
            // For rescheduling, we'll create a new meeting record
            $venue = 'CEIT GUIDANCE Office';
            $empty_string = '';
            $insert_query = "INSERT INTO meetings (incident_report_id, meeting_date, venue, persons_present, meeting_minutes, location, prepared_by) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($insert_query);
            $stmt->bind_param("sssssss", $report_id, $meeting_date, $venue, $empty_string, $empty_string, $empty_string, $empty_string);
        } else {
            // For new scheduling
            $check_query = "SELECT id FROM meetings WHERE incident_report_id = ? AND meeting_minutes IS NULL";
            $check_stmt = $connection->prepare($check_query);
            $check_stmt->bind_param("s", $report_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing) {
                // Update existing unfinished meeting
                $update_query = "UPDATE meetings SET meeting_date = ? WHERE id = ?";
                $stmt = $connection->prepare($update_query);
                $stmt->bind_param("si", $meeting_date, $existing['id']);
            } else {
                // Create new meeting
                $venue = 'CEIT GUIDANCE Office';
                $empty_string = '';
                $insert_query = "INSERT INTO meetings (incident_report_id, meeting_date, venue, persons_present, meeting_minutes, location, prepared_by) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($insert_query);
                $stmt->bind_param("sssssss", $report_id, $meeting_date, $venue, $empty_string, $empty_string, $empty_string, $empty_string);
            }
        }
        
        $stmt->execute();
        $stmt->close();

        // Update incident report status
        $status = $is_rescheduled ? 'Rescheduled' : 'For Meeting';
        $update_status_query = "UPDATE incident_reports SET status = ? WHERE id = ?";
        $status_stmt = $connection->prepare($update_status_query);
        $status_stmt->bind_param("ss", $status, $report_id);
        $status_stmt->execute();
        $status_stmt->close();

        // Notify student and adviser
        $notify_query = "SELECT sv.student_id, s.email AS student_email, a.id AS adviser_id, a.email AS adviser_email 
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
        $notify_stmt->close();

        $formatted_date = $date_obj->format('F j, Y, g:i A');
        $action = $is_rescheduled ? 'rescheduled' : 'scheduled';
        $message = "A meeting has been {$action} for your incident report on " . $formatted_date;
        $link = "view_student_incident_reports.php?id=" . $report_id;

        createNotification($connection, 'student', $notify_info['student_id'], $message, $link);
        createNotification($connection, 'adviser', $notify_info['adviser_id'], "A meeting has been {$action} for your student's incident report", $link);

        $connection->commit();
        return [
            'status' => true,
            'message' => "Meeting " . ($is_rescheduled ? 'rescheduled' : 'scheduled') . " successfully."
        ];
    } catch (Exception $e) {
        $connection->rollback();
        return [
            'status' => false,
            'message' => "Error scheduling meeting: " . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Meeting</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Global Styles */
body {
    background: linear-gradient(135deg, #0d693e, #004d4d);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    margin: 0;
    color: #333;
}

.container {
    background-color: #ffffff;
    border-radius: 15px;
    padding: 30px;
    margin-top: 50px;
    margin-bottom: 50px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    max-width: 800px;
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

/* Header and Navigation */
.btn-secondary {
    background-color: #F4A261;
    border-color: #F4A261;
    color: #fff;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background-color: #E76F51;
    border-color: #E76F51;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 111, 81, 0.2);
}

.btn-secondary i {
    margin-right: 8px;
}

/* Form Elements */
.form-group {
    margin-bottom: 24px;
}

.form-group label {
    font-weight: 500;
    color: #2D3748;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    padding: 9px 16px;
    font-size: 16px;
    transition: all 0.3s ease;
    width: 100%;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: #0d693e;
    box-shadow: 0 0 0 3px rgba(13, 105, 62, 0.1);
    outline: none;
}

.form-control:disabled,
.form-control[readonly] {
    background-color: #F7FAFC;
    cursor: not-allowed;
}

/* Date and Time Inputs */
input[type="date"],
select {
    height: 48px;
    background-color: white;
}

.row {
    margin: 0 -15px;
    display: flex;
    flex-wrap: wrap;
}

.col-md-6 {
    padding: 0 15px;
    flex: 0 0 50%;
    max-width: 50%;
}

/* Helper Text */
.form-text {
    color: #718096;
    font-size: 14px;
    margin-top: 8px;
}

/* Submit Button */
.btn-primary {
    background-color: #0d693e;
    border: none;
    color: white;
    padding: 14px 28px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 16px;
    transition: all 0.3s ease;
    cursor: pointer;
    width: 100%;
    margin-top: 16px;
}

.btn-primary:hover {
    background-color: #0a5432;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 105, 62, 0.2);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Sweet Alert Customization */
.swal2-popup {
    border-radius: 12px;
    padding: 24px;
}

.swal2-title {
    color: #2D3748;
    font-size: 24px;
}

.swal2-content {
    color: #4A5568;
}

.swal2-actions {
    margin-top: 24px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        margin: 20px;
        padding: 20px;
    }

    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
        margin-bottom: 16px;
    }

    .btn-secondary {
        width: 100%;
        text-align: center;
    }
}

/* Error States */
.is-invalid {
    border-color: #E53E3E;
}

.invalid-feedback {
    color: #E53E3E;
    font-size: 14px;
    margin-top: 4px;
}

/* Animation */
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
   <a href="view_approved_reports.php" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>
        <h2>Schedule Meeting</h2>
        
        <?php if (isset($report_id)): ?>
            <form id="schedulingForm" action="schedule_generator.php" method="POST">
                <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
                <div class="form-group">
                    <label for="meeting_date">Select Date and Time:</label>
                    <div class="row">
                        <div class="col-md-6">
                            <input type="date" class="form-control" id="date_input" required
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <select class="form-control" id="time_input" required>
                                <option value="">Select Time</option>
                                <?php
                                // Generate time slots from 8 AM to 4 PM
                                for ($hour = 8; $hour < 16; $hour++) {
                                    $time = sprintf("%02d:00", $hour);
                                    $time_display = date("h:i A", strtotime($time));
                                    echo "<option value='$time'>$time_display</option>";
                                    
                                    $time = sprintf("%02d:30", $hour);
                                    $time_display = date("h:i A", strtotime($time));
                                    echo "<option value='$time'>$time_display</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="meeting_date" id="combined_datetime">
                    <small class="form-text text-muted">Schedule meetings Monday-Thursday, 8:00 AM - 4:00 PM only</small>
                </div>

                <div class="form-group">
                    <label for="venue">Venue:</label>
                    <input type="text" class="form-control" id="venue" name="venue" 
                           value="CEIT Guidance Office" readonly>
                </div>

                <input type="hidden" name="<?php echo $existing_meeting_date ? 'reschedule_meeting' : 'schedule_meeting'; ?>" value="1">
                <button type="submit" class="btn btn-primary">
                    <?php echo $existing_meeting_date ? 'Reschedule Meeting' : 'Schedule Meeting'; ?>
                </button>
            </form>
        <?php else: ?>
            <p>No report selected. Please go back to the approved reports page.</p>
            <a href="view_approved_reports.php" class="btn btn-secondary">Back to Approved Reports</a>
        <?php endif; ?>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('date_input');
    const timeInput = document.getElementById('time_input');
    const combinedInput = document.getElementById('combined_datetime');

    // Function to check for schedule conflicts
    async function checkScheduleConflict(dateTime) {
        try {
            const response = await fetch('check_schedule_conflict.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `meeting_date=${dateTime}`
            });
            const data = await response.json();
            return data.hasConflict;
        } catch (error) {
            console.error('Error checking schedule:', error);
            return false;
        }
    }

    // Function to disable weekends
    function disableWeekends(date) {
        const day = date.getDay();
        return day !== 0 && day !== 5 && day !== 6; // Returns false for Sun(0), Fri(5), Sat(6)
    }

    // Set min and max time
    dateInput.addEventListener('input', function() {
        const selected = new Date(this.value);
        if (!disableWeekends(selected)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date',
                text: 'Please select a date between Monday and Thursday.',
                timer: 3000,
                timerProgressBar: true
            });
            this.value = ''; // Clear the invalid date
            timeInput.value = ''; // Clear time as well
        }
    });

    // Handle form submission
    document.getElementById('schedulingForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!dateInput.value || !timeInput.value) {
            Swal.fire({
                icon: 'error',
                title: 'Required Fields',
                text: 'Please select both date and time.'
            });
            return;
        }

        // Combine date and time
        const dateTimeStr = `${dateInput.value}T${timeInput.value}`;
        const selectedDateTime = new Date(dateTimeStr);

        if (!disableWeekends(selectedDateTime)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date',
                text: 'Meetings can only be scheduled Monday through Thursday.'
            });
            return;
        }

        // Check for schedule conflicts
        const hasConflict = await checkScheduleConflict(dateTimeStr);
        if (hasConflict) {
            Swal.fire({
                icon: 'error',
                title: 'Schedule Conflict',
                text: 'This time slot is already taken. Please select a different date and time.',
                timer: 3000,
                timerProgressBar: true
            });
            return;
        }

        combinedInput.value = dateTimeStr;

        Swal.fire({
            title: 'Confirm Schedule',
            text: 'Are you sure you want to schedule this meeting?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, schedule it!'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // If there's an existing date/time, populate the fields
    <?php if($existing_meeting_date): ?>
        const existingDate = new Date('<?php echo $existing_meeting_date; ?>');
        dateInput.value = existingDate.toISOString().split('T')[0];
        timeInput.value = existingDate.toTimeString().slice(0, 5);
    <?php endif; ?>

    // Show success/error messages
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $_SESSION['success_message']; ?>',
            showCancelButton: true,
            confirmButtonText: 'Send Notifications',
            cancelButtonText: 'Close',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'compose_meeting_notification.php?report_id=<?php echo $report_id; ?>';
            } else {
                window.location.href = 'view_approved_reports.php';
            }
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $_SESSION['error_message']; ?>',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
});    </script>
</body>
</html>