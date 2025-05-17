<?php
session_start();
include '../db.php';

// Check if the user is logged in as a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: login.php");
    exit();
}

// Function to create notifications
function createNotification($connection, $user_type, $user_id, $message, $link) {
    $query = "INSERT INTO notifications (user_type, user_id, message, link) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $user_type, $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

// Handle form submission for scheduling/rescheduling meetings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_meeting']) || isset($_POST['reschedule_meeting'])) {
        $report_id = $_POST['report_id'];
        $meeting_date = $_POST['meeting_date'];
        $is_rescheduled = isset($_POST['reschedule_meeting']);

        // Validate meeting time (8 AM - 4 PM)
        $meeting_hour = date('H', strtotime($meeting_date));
        if ($meeting_hour < 8 || $meeting_hour > 16) {
            $_SESSION['error_message'] = "Meeting time must be between 8:00 AM and 4:00 PM.";
            header("Location: view_counselor_meeting_reports.php");
            exit();
        }

        $connection->begin_transaction();

        try {
            if ($is_rescheduled) {
                $update_query = "UPDATE meetings SET meeting_date = ?, location = 'Cavite State University - Guidance Office' WHERE incident_report_id = ?";
                $stmt = $connection->prepare($update_query);
                $stmt->bind_param("ss", $meeting_date, $report_id);
            } else {
                $insert_query = "INSERT INTO meetings (incident_report_id, meeting_date, location) VALUES (?, ?, 'Cavite State University - Guidance Office')
                                ON DUPLICATE KEY UPDATE meeting_date = VALUES(meeting_date)";
                $stmt = $connection->prepare($insert_query);
                $stmt->bind_param("ss", $report_id, $meeting_date);
            }
            $stmt->execute();
            $stmt->close();

            // Update incident report status
            $status = $is_rescheduled ? 'Rescheduled-counselor' : 'For Meeting-counselor';
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

            $action = $is_rescheduled ? 'rescheduled' : 'scheduled';
            $message = "A counseling meeting has been {$action} for your incident report on " . date('F j, Y, g:i A', strtotime($meeting_date));
            $link = "View_student_incident_reports.php?id=" . $report_id;

            createNotification($connection, 'student', $notify_info['student_id'], $message, $link);
            createNotification($connection, 'adviser', $notify_info['adviser_id'], "A counseling meeting has been {$action} for your student's incident report", $link);

            $connection->commit();
            $_SESSION['success_message'] = "Meeting successfully {$action}.";
        } catch (Exception $e) {
            $connection->rollback();
            $_SESSION['error_message'] = "Error scheduling meeting: " . $e->getMessage();
        }

        header("Location: view_counselor_meeting_reports.php");
        exit();
    }
}

// Handle search, sorting, and filtering
$search = isset($_GET['search']) ? $connection->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'meeting_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$filter_schedule = isset($_GET['filter_schedule']) ? $_GET['filter_schedule'] : '';
$filter_course = isset($_GET['filter_course']) ? $connection->real_escape_string($_GET['filter_course']) : '';

$query = "SELECT ir.*, sv.status as violation_status, s.first_name, s.last_name, 
          GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
          ir.description, m.meeting_date, m.location, ir.resolution_notes, c.name as course_name,
          sec.year_level
          FROM incident_reports ir 
          JOIN student_violations sv ON ir.id = sv.incident_report_id
          JOIN tbl_student s ON sv.student_id = s.student_id
          LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
          LEFT JOIN meetings m ON ir.id = m.incident_report_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
          WHERE (ir.status = 'For Meeting-counselor' OR ir.status = 'Rescheduled-counselor')";

if (!empty($search)) {
    $query .= " AND (s.first_name LIKE '%$search%' OR s.last_name LIKE '%$search%' OR ir.description LIKE '%$search%')";
}

if ($filter_schedule === 'scheduled') {
    $query .= " AND m.meeting_date IS NOT NULL";
} elseif ($filter_schedule === 'unscheduled') {
    $query .= " AND m.meeting_date IS NULL";
}

if (!empty($filter_course)) {
    $query .= " AND c.name = '$filter_course'";
}

$query .= " GROUP BY ir.id ORDER BY $sort $order";

$result = $connection->query($query);

if ($result === false) {
    die("Query failed: " . $connection->error);
}

// Fetch all courses for the filter dropdown
$course_query = "SELECT DISTINCT name FROM courses ORDER BY name";
$course_result = $connection->query($course_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counseling Meetings - Incident Reports</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
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
        .search-filter-container {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="counselor_homepage.php" class="btn btn-secondary mb-4">
            <i class="fas fa-arrow-left"></i> Back to Homepage
        </a>
        <h1>Counseling Meetings - Incident Reports</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" role="alert">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Form -->
        <form action="" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search student or violation" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <select name="filter_schedule" class="form-control">
                        <option value="">All Schedules</option>
                        <option value="scheduled" <?php echo $filter_schedule === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="unscheduled" <?php echo $filter_schedule === 'unscheduled' ? 'selected' : ''; ?>>Unscheduled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="filter_course" class="form-control">
                        <option value="">All Courses</option>
                        <?php while ($course = $course_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($course['name']); ?>" <?php echo $filter_course === $course['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sort" class="form-control">
                        <option value="meeting_date" <?php echo $sort === 'meeting_date' ? 'selected' : ''; ?>>Meeting Date</option>
                        <option value="date_reported" <?php echo $sort === 'date_reported' ? 'selected' : ''; ?>>Date Reported</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="order" class="form-control">
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </div>
        </form>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Date Reported</th>
                    <th>Student</th>
                    <th>Course & Year</th>
                    <th>Violation</th>
                    <th>Witnesses</th>
                    <th>Meeting Schedule</th>
                    <th>Resolution Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars(date('F j, Y', strtotime($row['date_reported']))); ?></td>
                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['course_name'] . ' - ' . $row['year_level']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['witnesses']); ?></td>
                        <td><?php echo $row['meeting_date'] ? htmlspecialchars(date('F j, Y, g:i A', strtotime($row['meeting_date']))) : 'Not scheduled'; ?></td>
                        <td>
                            <?php if (!empty($row['resolution_notes'])): ?>
                                <button class="btn btn-info btn-sm" onclick="window.location.href='counselor_meeting_minutes.php?id=<?php echo $row['id']; ?>'">
                                    View Minutes
                                </button>
                            <?php else: ?>
                                No notes yet
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#updateModal<?php echo $row['id']; ?>">
                                <?php echo $row['meeting_date'] ? 'Reschedule Meeting' : 'Schedule Meeting'; ?>
                            </button>
                            <button class="btn btn-info btn-sm" onclick="window.location.href='counselor_meeting_minutes.php?id=<?php echo $row['id']; ?>'">
                                Create Minutes of Meeting
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Modal for scheduling/rescheduling meeting -->
                    <div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="updateModalLabel">
                                        <?php echo $row['meeting_date'] ? 'Reschedule Counseling Meeting' : 'Schedule Counseling Meeting'; ?>
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form action="" method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="<?php echo $row['meeting_date'] ? 'reschedule_meeting' : 'schedule_meeting'; ?>" value="1">
                                        <div class="form-group">
                                            <label for="meeting_date">Meeting Date and Time (8:00 AM - 4:00 PM only):</label>
                                            <input type="datetime-local" class="form-control" name="meeting_date" required 
                                                min="<?php echo date('Y-m-d\TH:i'); ?>" 
                                                value="<?php echo $row['meeting_date'] ? date('Y-m-d\TH:i', strtotime($row['meeting_date'])) : ''; ?>">
                                            <small class="form-text text-muted">Note: Meeting schedule must be between 8:00 AM and 4:00 PM only</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="location">Location:</label>
                                            <input type="text" class="form-control" name="location" value="Cavite State University - Guidance Office" readonly>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Add client-side validation for meeting time
        document.querySelectorAll('input[type="datetime-local"]').forEach(function(input) {
            input.addEventListener('change', function() {
                const selectedTime = new Date(this.value);
                const hours = selectedTime.getHours();
                
                if (hours < 8 || hours >= 16) {
                    alert('Please select a time between 8:00 AM and 4:00 PM');
                    this.value = '';
                }
            });
        });
    </script>
</body>
</html>