<?php
session_start();
include '../db.php';

// Check if user is logged in and is a counselor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'counselor') {
    header("Location: ../login.php");
    exit();
}

// Get referral ID from URL
$referral_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$referral_id) {
    header("Location: view_referrals.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = 'Done';
    $update_query = "UPDATE referrals SET status = ? WHERE id = ?";
    $stmt = $connection->prepare($update_query);
    $stmt->bind_param("si", $new_status, $referral_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Status updated successfully";
    } else {
        $_SESSION['error_message'] = "Error updating status";
    }
    header("Location: view_referral_details.php?id=" . $referral_id);
    exit();
}

// Fetch referral details with incident report information and involved students
$query = "SELECT r.*, 
          DATE_FORMAT(r.date, '%Y-%m-%d %H:%i:%s') as formatted_date,
          CASE 
              WHEN r.reason_for_referral = 'Other concern' THEN CONCAT('Other concern: ', r.other_concerns)
              WHEN r.reason_for_referral = 'Violation to school rules' THEN CONCAT('Violation: ', r.violation_details)
              ELSE r.reason_for_referral
          END as detailed_reason,
          ir.id as incident_report_id,
          GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name) SEPARATOR ', ') as involved_students
          FROM referrals r
          LEFT JOIN incident_reports ir ON r.incident_report_id = ir.id
          LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
          LEFT JOIN tbl_student s ON sv.student_id = s.student_id
          WHERE r.id = ?
          GROUP BY r.id";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $referral_id);
$stmt->execute();
$result = $stmt->get_result();
$referral = $result->fetch_assoc();

if (!$referral) {
    header("Location: view_referral_details.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Details</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(to right, #0d693e, #004d4d);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            background-color:rgb(248, 246, 244);
            padding: 10px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            position: absolute;
            right: 0;
            top: 0;
            width: 100%;
            color: #1b651b;;
            z-index: 1000;
        }
        
        
        .content-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 60px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .detail-row {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .detail-label {
            font-weight: bold;
            color: #0d693e;
        }
        
        .back-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #5a6268;
            color: white;
        }
        
        .meeting-btn {
            background-color: #0d693e;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            margin-right: 10px;
        }
        
        .meeting-btn:hover {
            background-color: #095030;
            color: white;
        }

        .meeting-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .view-meetings-btn {
            background-color: #ff9f1c;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
        }

        .view-meetings-btn:hover {
            background-color: #e88e0c;
            color: white;
        }

        .buttons-container {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="header">REFERRAL DETAILS</div>
    
    <div class="container content-container">
        <a href="view_referrals_page.php" class="btn back-btn mb-4">Back to Referrals Page</a>

        <div class="detail-row">
            <span class="detail-label">Report ID:</span>
            <span><?php echo htmlspecialchars($referral['id']); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Incident Report ID:</span>
            <span><?php echo htmlspecialchars($referral['incident_report_id'] ?? 'N/A'); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Date Reported:</span>
            <span><?php echo htmlspecialchars($referral['formatted_date']); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Student Name:</span>
            <span><?php echo htmlspecialchars($referral['first_name'] . ' ' . $referral['middle_name'] . ' ' . $referral['last_name']); ?></span>
        </div>

        <?php if ($referral['involved_students']): ?>
        <div class="detail-row">
            <span class="detail-label">Students Involved:</span>
            <span><?php echo htmlspecialchars($referral['involved_students']); ?></span>
        </div>
        <?php endif; ?>

        <div class="detail-row">
            <span class="detail-label">Course/Year:</span>
            <span><?php echo htmlspecialchars($referral['course_year']); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Reason for Referral:</span>
            <span><?php echo htmlspecialchars($referral['detailed_reason']); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Faculty Name:</span>
            <span><?php echo htmlspecialchars($referral['faculty_name']); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Acknowledged By:</span>
            <span><?php echo htmlspecialchars($referral['acknowledged_by']); ?></span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Current Status:</span>
            <span><?php echo htmlspecialchars($referral['status'] ?? 'Pending'); ?></span>
        </div>

            <div class="buttons-container">
            <a href="generate_referral_pdf.php?id=<?php echo $referral_id; ?>" 
               target="_blank" 
               class="btn meeting-btn">
                <i class="fas fa-file-pdf"></i> Generate PDF
            </a>
            <form id="markAsDoneForm" method="POST" style="display: inline;">
                <input type="hidden" name="update_status" value="1">
                <button type="button" 
                        onclick="confirmMarkAsDone()"
                        class="btn meeting-btn" 
                        <?php echo ($referral['status'] === 'Done') ? 'disabled' : ''; ?>>
                    Mark as Done
                </button>
            </form>
            <a href="view_referrals_done.php" class="btn view-meetings-btn">View 'Done' Referrals</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    function confirmMarkAsDone() {
        Swal.fire({
            title: 'Mark as Done?',
            text: 'Are you sure you want to mark this referral as done? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d693e',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, mark as done',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('markAsDoneForm').submit();
            }
        });
    }

    // Success message after form submission
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            title: 'Success!',
            text: '<?php echo $_SESSION['success_message']; ?>',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    // Error message handling
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Error!',
            text: '<?php echo $_SESSION['error_message']; ?>',
            icon: 'error'
        });
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    </script>
</body>
</html>