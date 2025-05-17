<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

$adviser_id = $_SESSION['user_id'];
$referral_id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($referral_id)) {
    die("No referral ID provided.");
}

// Query to get the referral details along with student and section information
// Only allow advisers to view referrals for students in their sections
$query = "
    SELECT r.*, 
           CONCAT(s.first_name, ' ', s.middle_name, ' ', s.last_name) as student_name,
           s.student_id as student_id_number,
           s.gender,
           c.name as course_name,
           sec.year_level,
           sec.section_no,
           sec.academic_year
    FROM referrals r
    JOIN tbl_student s ON r.student_id = s.student_id
    JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN courses c ON sec.course_id = c.id
    WHERE r.id = ? AND sec.adviser_id = ?";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}

$stmt->bind_param("ii", $referral_id, $adviser_id);
$stmt->execute();
$result = $stmt->get_result();
$referral = $result->fetch_assoc();

// If no referral found or the adviser doesn't have permission
if (!$referral) {
    // Try a simplified query to just get the referral details
    // This is useful for displaying an appropriate error message
    $simple_query = "SELECT id, date, reason_for_referral, student_id FROM referrals WHERE id = ?";
    $simple_stmt = $connection->prepare($simple_query);
    $simple_stmt->bind_param("i", $referral_id);
    $simple_stmt->execute();
    $simple_result = $simple_stmt->get_result();
    $simple_referral = $simple_result->fetch_assoc();
    
    if ($simple_referral) {
        die("You don't have permission to view this referral. It belongs to a student who is not in your advisory sections.");
    } else {
        die("Referral not found.");
    }
}

// Get any meeting details related to this referral
$meeting_query = "
    SELECT meeting_date, venue, meeting_minutes, persons_present, location, status
    FROM counselor_meetings 
    WHERE referral_id = ?
    ORDER BY meeting_date DESC";

$meetings = [];
$stmt_meeting = $connection->prepare($meeting_query);
if ($stmt_meeting) {
    $stmt_meeting->bind_param("i", $referral_id);
    $stmt_meeting->execute();
    $meeting_result = $stmt_meeting->get_result();
    while ($meeting = $meeting_result->fetch_assoc()) {
        $meetings[] = $meeting;
    }
}

// Check if follow-up actions table exists
$check_followup_table = "SHOW TABLES LIKE 'followup_actions'";
$followup_table_exists = $connection->query($check_followup_table)->num_rows > 0;

$followup_actions = [];
if ($followup_table_exists) {
    // Get all follow-up actions for this referral
    $followup_query = "
        SELECT fa.id, fa.action_detail, fa.due_date, fa.status, 
               CONCAT(tc.first_name, ' ', tc.middle_initial, ' ', tc.last_name) as assigned_by
        FROM followup_actions fa
        LEFT JOIN tbl_counselor tc ON fa.assigned_by = tc.counselor_id
        WHERE fa.referral_id = ?
        ORDER BY fa.due_date ASC";

    $stmt_followup = $connection->prepare($followup_query);
    if ($stmt_followup) {
        $stmt_followup->bind_param("i", $referral_id);
        $stmt_followup->execute();
        $followup_result = $stmt_followup->get_result();
        while ($action = $followup_result->fetch_assoc()) {
            $followup_actions[] = $action;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Details - Adviser View</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d693e;
            --secondary-color: #004d4d;
            --accent-color: #2EDAA8;
            --text-color: #2c3e50;
            --light-bg: #f8f9fa;
            --border-color: #e2e8f0;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .container {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin: 50px auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--accent-color);
        }

        .referral-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--light-bg);
            border-radius: 10px;
            border-left: 5px solid var(--primary-color);
        }

        .referral-section h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .detail-row {
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
        }

        .detail-label {
            font-weight: 600;
            width: 180px;
            color: #4a5568;
        }

        .detail-value {
            flex: 1;
            min-width: 250px;
        }

        .meeting-section {
            background-color: #fff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .meeting-section h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .reason-box {
            padding: 15px;
            border-radius: 8px;
            background-color: #e6f7ff;
            border-left: 4px solid #1890ff;
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
        }

        .status-pending {
            background-color: #ffe58f;
            color: #ad6800;
        }

        .status-done {
            background-color: #b7eb8f;
            color: #135200;
        }

        .status-scheduled {
            background-color: #91caff;
            color: #0050b3;
        }

        .accordion-card {
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .accordion-header {
            background-color: #f1f5f9;
            padding: 12px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .accordion-header h5 {
            margin: 0;
            font-size: 16px;
            color: var(--primary-color);
        }

        .accordion-body {
            padding: 15px 20px;
            background-color: #fff;
        }

        .action-item {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
        }

        .action-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .action-title {
            font-weight: 600;
            color: var(--primary-color);
        }

        .action-date {
            font-size: 14px;
            color: #718096;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #094e2e;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .referral-section {
                padding: 15px;
            }

            .detail-label, .detail-value {
                width: 100%;
            }

            .detail-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Referral Details <small class="text-muted">(Adviser View)</small></h1>

        <div class="referral-section">
            <h3>Student Information</h3>
            <div class="detail-row">
                <div class="detail-label">Student ID:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['student_id_number']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Student Name:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['student_name']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Gender:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['gender']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Course:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['course_name'] ?? 'Not specified'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Year & Section:</div>
                <div class="detail-value">
                    <?php echo htmlspecialchars($referral['year_level'] . ' - Section ' . $referral['section_no']); ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Academic Year:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['academic_year']); ?></div>
            </div>
        </div>

        <div class="referral-section">
            <h3>Referral Information</h3>
            <div class="detail-row">
                <div class="detail-label">Date Filed:</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($referral['date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge <?php echo $referral['status'] === 'Pending' ? 'status-pending' : 'status-done'; ?>">
                        <?php echo htmlspecialchars($referral['status']); ?>
                    </span>
                </div>
            </div>
            <div class="reason-box">
                <div class="detail-row">
                    <div class="detail-label">Reason for Referral:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($referral['reason_for_referral']); ?></div>
                </div>
                
                <?php if (!empty($referral['violation_details'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Violation Details:</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($referral['violation_details'])); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($referral['other_concerns'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Other Concerns:</div>
                    <div class="detail-value"><?php echo nl2br(htmlspecialchars($referral['other_concerns'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Referred By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['faculty_name']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Acknowledged By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['acknowledged_by'] ?: 'Not yet acknowledged'); ?></div>
            </div>
            <?php if (!empty($referral['incident_report_id'])): ?>
            <div class="detail-row">
                <div class="detail-label">Incident Report:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['incident_report_id']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (count($meetings) > 0): ?>
        <div class="referral-section">
            <h3>Meeting Information</h3>
            
            <?php foreach ($meetings as $index => $meeting): ?>
            <div class="accordion-card">
                <div class="accordion-header" onclick="toggleAccordion(<?php echo $index; ?>)">
                    <h5>
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Meeting on <?php echo date('F j, Y \a\t g:i A', strtotime($meeting['meeting_date'])); ?>
                    </h5>
                    <?php if (isset($meeting['status'])): ?>
                    <span class="status-badge <?php echo $meeting['status'] === 'Scheduled' ? 'status-scheduled' : 'status-done'; ?>">
                        <?php echo htmlspecialchars($meeting['status']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="accordion-body" id="meeting-<?php echo $index; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>">
                    <div class="detail-row">
                        <div class="detail-label">Venue:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($meeting['venue']); ?></div>
                    </div>
                    <?php if (!empty($meeting['location'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Location:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($meeting['location']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($meeting['persons_present'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Persons Present:</div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($meeting['persons_present'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($meeting['meeting_minutes'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Meeting Minutes:</div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($meeting['meeting_minutes'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($followup_actions) > 0): ?>
        <div class="referral-section">
            <h3>Follow-up Actions</h3>
            
            <div class="meeting-section">
                <?php foreach ($followup_actions as $action): ?>
                <div class="action-item">
                    <div class="action-header">
                        <div class="action-title"><?php echo htmlspecialchars($action['action_detail']); ?></div>
                        <span class="status-badge <?php echo $action['status'] === 'Pending' ? 'status-pending' : 'status-done'; ?>">
                            <?php echo htmlspecialchars($action['status']); ?>
                        </span>
                    </div>
                    <div class="action-date">
                        <strong>Due:</strong> <?php echo date('F j, Y', strtotime($action['due_date'])); ?>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Assigned by: <?php echo htmlspecialchars($action['assigned_by']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mt-4">
            <a href="view_referrals.php" class="btn btn-primary">
                <i class="fas fa-arrow-left mr-2"></i> Back to Referrals
            </a>
            
            
        </div>
    </div>

    <script>
        function toggleAccordion(index) {
            const content = document.getElementById(`meeting-${index}`);
            if (content.style.display === "none") {
                content.style.display = "block";
            } else {
                content.style.display = "none";
            }
        }
    </script>
</body>
</html>