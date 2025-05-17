<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$referral_id = $_GET['id'] ?? '';

// Query to get the referral details
$query = "
    SELECT r.*, 
           c.name as course_name,
           sec.year_level,
           sec.section_no,
           CONCAT(tc.first_name, ' ', tc.middle_initial, ' ', tc.last_name) as counselor_name
    FROM referrals r
    LEFT JOIN tbl_student s ON r.student_id = s.student_id
    LEFT JOIN sections sec ON s.section_id = sec.id
    LEFT JOIN courses c ON sec.course_id = c.id
    LEFT JOIN tbl_counselor tc ON r.acknowledged_by = CONCAT(tc.first_name, ' ', tc.middle_initial, ' ', tc.last_name)
    WHERE r.id = ? AND r.student_id = ?";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}

$stmt->bind_param("is", $referral_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$referral = $result->fetch_assoc();

if (!$referral) {
    die("Referral not found or you don't have permission to view it.");
}

// Get any meeting details related to this referral
$meeting_query = "
    SELECT cm.meeting_date, cm.venue, cm.meeting_minutes, cm.persons_present, cm.location
    FROM counselor_meetings cm
    WHERE cm.referral_id = ?
    ORDER BY cm.meeting_date DESC
    LIMIT 1";

$stmt_meeting = $connection->prepare($meeting_query);
$stmt_meeting->bind_param("i", $referral_id);
$stmt_meeting->execute();
$meeting_result = $stmt_meeting->get_result();
$meeting = $meeting_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Details</title>
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
        <h1>Referral Details</h1>

        <div class="referral-section">
            <h3>Basic Information</h3>
            <div class="detail-row">
                <div class="detail-label">Date:</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($referral['date'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Student:</div>
                <div class="detail-value">
                    <?php echo htmlspecialchars($referral['first_name'] . ' ' . 
                                                ($referral['middle_name'] ? $referral['middle_name'] . ' ' : '') . 
                                                $referral['last_name']); ?>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Course/Year:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['course_year']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge <?php echo $referral['status'] === 'Pending' ? 'status-pending' : 'status-done'; ?>">
                        <?php echo htmlspecialchars($referral['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="referral-section">
            <h3>Referral Details</h3>
            <div class="reason-box">
                <div class="detail-row">
                    <div class="detail-label">Reason for Referral:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($referral['reason_for_referral']); ?></div>
                </div>
                
                <?php if (!empty($referral['violation_details'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Violation Details:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($referral['violation_details']); ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($referral['other_concerns'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Other Concerns:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($referral['other_concerns']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Referred By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['faculty_name']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Acknowledged By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($referral['acknowledged_by']); ?></div>
            </div>
        </div>

        <?php if ($meeting): ?>
        <div class="referral-section">
            <h3>Meeting Information</h3>
            <div class="meeting-section">
                <h4>Scheduled Meeting</h4>
                <div class="detail-row">
                    <div class="detail-label">Date & Time:</div>
                    <div class="detail-value">
                        <?php echo date('F j, Y \a\t g:i A', strtotime($meeting['meeting_date'])); ?>
                    </div>
                </div>
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
        <?php endif; ?>

        <a href="view_student_referrals.php" class="btn btn-primary mt-3">
            <i class="fas fa-arrow-left mr-2"></i> Back to Referrals
        </a>
    </div>
</body>
</html>