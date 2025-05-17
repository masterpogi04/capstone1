<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: ../login.php");
    exit();
}

function createNotification($connection, $user_type, $user_id, $message, $link) {
    $stmt = $connection->prepare("INSERT INTO notifications (user_type, user_id, message, link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user_type, $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

$report_id = $_GET['id'] ?? '';

$query = "SELECT ir.*, 
            GROUP_CONCAT(DISTINCT sv.student_id) as student_ids,
            GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name)) AS student_names,
            GROUP_CONCAT(DISTINCT s.email) AS student_emails,
            GROUP_CONCAT(DISTINCT iw.witness_name SEPARATOR ', ') as witnesses,
            sec.section_no, c.name AS course_name, d.name AS department_name,
            a.id AS adviser_id, a.name AS adviser_name, a.email AS adviser_email
          FROM incident_reports ir
          LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
          LEFT JOIN tbl_student s ON sv.student_id = s.student_id
          LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
          LEFT JOIN departments d ON c.department_id = d.id
          LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
          WHERE ir.id = ?
          GROUP BY ir.id";

$stmt = $connection->prepare($query);
if (!$stmt) die("Error preparing statement: " . $connection->error);

$stmt->bind_param("s", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
if (!$report) die("Report not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['new_status'];
    $connection->begin_transaction();
    
    try {
        $update_stmt = $connection->prepare("UPDATE incident_reports SET status = ? WHERE id = ?");
        if (!$update_stmt) throw new Exception($connection->error);
        
        $update_stmt->bind_param("ss", $new_status, $report_id);
        if (!$update_stmt->execute()) throw new Exception($update_stmt->error);

        if ($new_status === 'For Meeting-counselor') {
            $delete_stmt = $connection->prepare("DELETE FROM meetings WHERE incident_report_id = ?");
            if (!$delete_stmt) throw new Exception($connection->error);
            
            $delete_stmt->bind_param("s", $report_id);
            if (!$delete_stmt->execute()) throw new Exception($delete_stmt->error);
        }

        $student_ids = explode(',', $report['student_ids']);
        foreach ($student_ids as $student_id) {
            if (!empty($student_id)) {
                createNotification(
                    $connection, 
                    'student', 
                    $student_id, 
                    "Your incident report has been updated to: " . $new_status,
                    "view_student_incident_reports.php?id=" . $report_id
                );
            }
        }

        if (!empty($report['adviser_id'])) {
            createNotification(
                $connection,
                'adviser',
                $report['adviser_id'],
                "An incident report for your student has been updated to: " . $new_status,
                "view_student_incident_reports.php?id=" . $report_id
            );
        }

        $connection->commit();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;

    } catch (Exception $e) {
        $connection->rollback();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Report Details - Counselor View</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="view_counselor_incident_reports.php" class="btn btn-secondary mb-4">
            <i class="fas fa-arrow-left"></i> Back to Escalated Incident Reports
        </a>
        
        <h2>Incident Report Details</h2>

                <dl class="row">
            <dt class="col-sm-3">Report ID:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['id']); ?></dd>
            
            <dt class="col-sm-3">Date Reported:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($report['date_reported']))); ?></dd>
            
            <dt class="col-sm-3">Student Name(s):</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['student_names']); ?></dd>
            
            <dt class="col-sm-3">Student Email(s):</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['student_emails']); ?></dd>
            
            <dt class="col-sm-3">Section:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['section_no']); ?></dd>
            
            <dt class="col-sm-3">Course:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['course_name']); ?></dd>
            
            <dt class="col-sm-3">Department:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['department_name']); ?></dd>
            
            <dt class="col-sm-3">Adviser:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['adviser_name']); ?></dd>
            
            <dt class="col-sm-3">Description:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['description']); ?></dd>
            
            <dt class="col-sm-3">Witnesses:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['witnesses']); ?></dd>
            
            <dt class="col-sm-3">Reported By:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['reported_by']); ?></dd>
            
            <dt class="col-sm-3">Current Status:</dt>
            <dd class="col-sm-9"><?php echo htmlspecialchars($report['status']); ?></dd>
        </dl>
        
        <form id="updateStatusForm" class="mb-4">
            <div class="form-group">
                <label for="new_status"><strong>Update Status:</strong></label>
                <select class="form-control" id="new_status" name="new_status">
                    <option value="For Meeting-counselor" <?php echo ($report['status'] == 'For Meeting-counselor') ? 'selected' : ''; ?>>For Meeting-counselor</option>
                    <option value="Reject" <?php echo ($report['status'] == 'Reject') ? 'selected' : ''; ?>>Reject</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Status
            </button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#updateStatusForm').on('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to update the status of this incident report?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d693e',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData(this);
                    formData.append('update_status', '1');
                    
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Status has been updated successfully.',
                                    icon: 'success',
                                    confirmButtonColor: '#0d693e'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Something went wrong.',
                                    icon: 'error',
                                    confirmButtonColor: '#d33'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while processing your request.',
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