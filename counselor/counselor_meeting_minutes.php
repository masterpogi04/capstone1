<?php
session_start();
include '../db.php';

// Check if the user is logged in as a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: login.php");
    exit();
}

$report_id = isset($_GET['id']) ? $_GET['id'] : '';
$is_view_only = isset($_GET['view']) && $_GET['view'] === 'true';
$success_message = '';
$error_message = '';

// Function to create notifications
function createNotification($connection, $user_type, $user_id, $message, $link) {
    $query = "INSERT INTO notifications (user_type, user_id, message, link) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $user_type, $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

// Fetch incident report details with counseling-specific information
if ($report_id) {
    $query = "SELECT ir.*, 
              GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name)) as student_names,
              GROUP_CONCAT(DISTINCT s.student_id) as student_ids,
              c.name as course_name,
              m.meeting_date,
              sec.year_level
              FROM incident_reports ir 
              JOIN student_violations sv ON ir.id = sv.incident_report_id
              JOIN tbl_student s ON sv.student_id = s.student_id
              LEFT JOIN sections sec ON s.section_id = sec.id
              LEFT JOIN courses c ON sec.course_id = c.id
              LEFT JOIN meetings m ON ir.id = m.incident_report_id
              WHERE ir.id = ?
              GROUP BY ir.id";
              
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $incident = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_view_only) {
    $notes = $_POST['resolution_notes'];
    $report_id = $_POST['report_id'];
    
    $connection->begin_transaction();
    
    try {
        // Update resolution notes and mark as resolved by counselor
        $update_query = "UPDATE incident_reports SET 
                        resolution_notes = ?,
                        status = 'Resolved by Counselor',
                        resolution_status = 'Resolved'
                        WHERE id = ?";
        $stmt = $connection->prepare($update_query);
        $stmt->bind_param("ss", $notes, $report_id);
        $stmt->execute();

        // Update student_violations table
        $update_violations = "UPDATE student_violations SET status = 'Resolved by Counselor' 
                            WHERE incident_report_id = ?";
        $stmt = $connection->prepare($update_violations);
        $stmt->bind_param("s", $report_id);
        $stmt->execute();

        // Create notifications for student and adviser
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

        while ($notify_info = $notify_result->fetch_assoc()) {
            // Notify student
            $student_message = "Your counseling session has been completed and the case has been resolved.";
            createNotification($connection, 'student', $notify_info['student_id'], $student_message, "view_student_incident_reports.php?id=" . $report_id);
            
            // Notify adviser
            $adviser_message = "The counseling session for your student has been completed and the case has been resolved.";
            createNotification($connection, 'adviser', $notify_info['adviser_id'], $adviser_message, "view_student_incident_reports.php?id=" . $report_id);
        }

        $connection->commit();
        $success_message = "Counseling minutes saved successfully and case marked as resolved.";
        
        // Redirect back to the meetings page after short delay
        header("refresh:2;url=view_counselor_meeting_reports.php");
    } catch (Exception $e) {
        $connection->rollback();
        $error_message = "Error saving counseling minutes: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_view_only ? 'View Counseling Minutes' : 'Add Counseling Minutes'; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d693e, #004d4d);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .back-button {
            background-color: #F4A261;
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-button:hover {
            background-color: #E76F51;
            text-decoration: none;
            color: white;
        }

        h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 30px;
        }

        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #0d693e;
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }

        .form-control {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
        }

        .btn-primary {
            background-color: #0d693e;
            border: none;
            padding: 10px 20px;
        }

        .btn-primary:hover {
            background-color: #094e2e;
        }

        dt {
            color: #0d693e;
            font-weight: bold;
        }

        .alert-info {
            background-color: #e8f4f8;
            border-color: #b8e7f3;
            color: #0c5460;
        }

        /* Custom styling for the readonly textarea in view mode */
        textarea[readonly] {
            background-color: #f8f9fa;
            cursor: default;
            resize: none;
        }

        /* Resolution notes status styling */
        .resolution-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="view_counselor_meeting_reports.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Counseling Reports
        </a>

        <h1><?php echo $is_view_only ? 'View Counseling Minutes' : 'Add Counseling Minutes'; ?></h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($incident)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3><?php echo $is_view_only ? 'Counseling Session Details' : 'Add Counseling Minutes'; ?></h3>
                </div>
                <div class="card-body">
                    <h5 class="mb-4">Incident Report Information</h5>
                    <dl class="row">
                        <dt class="col-sm-3">Report ID:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['id']); ?></dd>

                        <dt class="col-sm-3">Student(s):</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['student_names']); ?></dd>

                        <dt class="col-sm-3">Course & Year:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['course_name'] . ' - ' . $incident['year_level']); ?></dd>

                        <dt class="col-sm-3">Counseling Schedule:</dt>
                        <dd class="col-sm-9">
                            <?php echo $incident['meeting_date'] ? date('F j, Y, g:i A', strtotime($incident['meeting_date'])) : 'Not scheduled'; ?>
                        </dd>

                        <dt class="col-sm-3">Violation:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['description']); ?></dd>
                    </dl>

                    <form method="POST" action="">
                        <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report_id); ?>">
                        
                        <div class="form-group">
                            <label for="resolution_notes"><strong>Counseling Session Notes:</strong></label>
                            <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="10" 
                                <?php echo $is_view_only ? 'readonly' : 'required'; ?>
                                placeholder="Enter counseling session notes, recommendations, and action plans..."
                            ><?php echo isset($incident['resolution_notes']) ? htmlspecialchars($incident['resolution_notes']) : ''; ?></textarea>
                        </div>

                        <?php if (!$is_view_only): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Note: Submitting these counseling minutes will mark this case as resolved by the counselor.
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Minutes & Resolve Case
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">No incident report found.</div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>