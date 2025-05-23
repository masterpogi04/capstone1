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

// Debug to check if POST data is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received: " . print_r($_POST, true));
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = 'Done';
    
    // Get the student_id for this specific referral (for notification purposes)
    $get_student_query = "SELECT student_id FROM referrals WHERE id = ?";
    $stmt = $connection->prepare($get_student_query);
    if ($stmt === false) {
        die('Error preparing statement: ' . $connection->error);
    }
    
    $stmt->bind_param("i", $referral_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $student_id = $row ? $row['student_id'] : null;
    
    // Update ONLY the specific referral that was selected
    $update_query = "UPDATE referrals SET status = ? WHERE id = ?";
    $stmt = $connection->prepare($update_query);
    if ($stmt === false) {
        die('Error preparing statement: ' . $connection->error);
    }
    
    $stmt->bind_param("si", $new_status, $referral_id);
    $result = $stmt->execute();
    
    if ($result) {
        $_SESSION['success_message'] = "Status updated successfully";
        
        // Create a notification for the student (if student_id exists)
        if ($student_id) {
            $notif_query = "INSERT INTO notifications (user_type, user_id, message, link, is_read, created_at) 
                           VALUES ('student', ?, 'Your referral has been marked as done by the counselor.', 'view_student_referrals.php', 0, NOW())";
            $notif_stmt = $connection->prepare($notif_query);
            if ($notif_stmt) {
                $notif_stmt->bind_param("s", $student_id);
                $notif_stmt->execute();
            }
        }
    } else {
        $_SESSION['error_message'] = "Error updating status: " . $connection->error;
    }
    
    // Redirect to refresh the page
    header("Location: view_referral_details.php?id=" . $referral_id);
    exit();
}

// Function to check if student profile exists
function checkStudentProfile($connection, $student_id) {
    if (!$student_id) return false;
    
    $query = "SELECT COUNT(*) as profile_exists FROM student_profiles WHERE student_id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['profile_exists'] > 0;
}

// Fetch referral details with the specific student being referred
$query = "SELECT r.*, 
    DATE_FORMAT(r.date, '%M %d, %Y') as formatted_date,
    CASE 
        WHEN r.reason_for_referral = 'Other concern' THEN CONCAT('Other concern: ', r.other_concerns)
        WHEN r.reason_for_referral = 'Violation to school rules' THEN CONCAT('Violation: ', r.violation_details)
        ELSE r.reason_for_referral
    END as detailed_reason,
    ir.id as incident_report_id,
    -- For CEIT students, use r.student_id directly
    -- For non-CEIT students, we'll use the student info stored in the referral itself
    r.student_id as referred_student_id,
    -- Display student name based on availability
    CASE 
        WHEN r.student_id IS NOT NULL THEN (
            -- For CEIT students with student_id
            SELECT CASE 
                WHEN s.section_id IS NOT NULL THEN
                    CONCAT(
                        CONCAT(UPPER(SUBSTRING(s.first_name, 1, 1)), LOWER(SUBSTRING(s.first_name, 2))),
                        ' ',
                        CASE 
                            WHEN s.middle_name IS NOT NULL AND s.middle_name != '' 
                            THEN CONCAT(UPPER(SUBSTRING(s.middle_name, 1, 1)), '. ') 
                            ELSE ''
                        END,
                        CONCAT(UPPER(SUBSTRING(s.last_name, 1, 1)), LOWER(SUBSTRING(s.last_name, 2))),
                        ' (',
                        s.student_id,
                        ')'
                    )
                ELSE 'Student not found'
            END
            FROM tbl_student s 
            WHERE s.student_id = r.student_id
        )
        ELSE 
            -- For non-CEIT students, use the name fields directly from referrals table
            CONCAT(
                CONCAT(UPPER(SUBSTRING(r.first_name, 1, 1)), LOWER(SUBSTRING(r.first_name, 2))),
                ' ',
                CASE 
                    WHEN r.middle_name IS NOT NULL AND r.middle_name != '' 
                    THEN CONCAT(UPPER(SUBSTRING(r.middle_name, 1, 1)), '. ') 
                    ELSE ''
                END,
                CONCAT(UPPER(SUBSTRING(r.last_name, 1, 1)), LOWER(SUBSTRING(r.last_name, 2))),
                ' (',
                r.course_year,
                ')'
            )
    END as referred_student_name
    FROM referrals r
    LEFT JOIN incident_reports ir ON r.incident_report_id = ir.id
    WHERE r.id = ?";

$stmt = $connection->prepare($query);
if ($stmt === false) {
    die('Error preparing statement: ' . $connection->error);
}

$stmt->bind_param("i", $referral_id);
$stmt->execute();
$result = $stmt->get_result();
$referral = $result->fetch_assoc();

if (!$referral) {
    header("Location: view_referrals_page.php");
    exit();
}

// Check if student profile exists for the referred student
$has_profile = false;
$referred_student_id = $referral['referred_student_id'];
if ($referred_student_id) {
    $has_profile = checkStudentProfile($connection, $referred_student_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

   <style>

                :root {
            --primary-color: #0d693e;
            --secondary-color: #004d4d;
            --accent-color: #F4A261;
            --hover-color: #094e2e;
            --text-color: #2c3e50;
            --border-color: #e0e0e0;
            --separator-color: #d1d5db;
            --card-bg: #f8f9fa;
            --shadow: rgba(0, 0, 0, 0.1);
        }
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            padding: 1rem;
            margin: 2.5rem auto;
            box-shadow: 0 8px 24px var(--shadow);
             max-width: 1000px; 
                    width: 90%; 
                    margin-left: auto; 
                    margin-right: auto;
        } 

        h1 {
            font-weight: 700;
            font-size: 2rem;
            text-align: center;
            margin: 5px 0 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 3px solid var(--primary-color);
            text-align: center;
            letter-spacing: 0.5px;
            padding-top: 30px;
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

        .details-card {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px var(--shadow);
            border: 1px solid var(--border-color);
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -1rem;
            gap: 1.5rem;
        }

        .col-md-6 {
            flex: 1;
            min-width: 300px;
            padding: 1rem;
        }

        .label {
            font-weight: 600;
            color: var(--primary-color);
            display: inline-block;
            margin-right: 1rem;
            min-width: 160px;
            padding: 0.5rem 0;
            position: relative;
        }

        .label::after {
            content: ':';
            position: absolute;
            right: 0.5rem;
        }

        p {
            margin: 0 0 1.25rem 0;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
            display: flex-start;
            align-items: baseline;
        }

        p:last-child {
            margin-bottom: 0;
        }

        .incident-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 1rem;
            box-shadow: 0 4px 12px var(--shadow);
            border: 2px solid #fff;
        }

        .image-container {
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        h4 {
            color: var(--primary-color);
            margin: 1.5rem 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        /* Status Badges with improved spacing */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-left: 1rem;
        }

        .status-pending { 
            background-color: #ffeeba;
            color: #856404;
        }

        .status-processing { 
            background-color: #bee5eb;
            color: #0c5460;
        }

        .status-meeting { 
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-resolved { 
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected { 
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Improved spacing for lists */
        ul, ol {
            margin: 0;
            padding-left: 1.5rem;
        }

        li {
            margin-bottom: 0.5rem;
        }

        /* Responsive Design with better spacing */
        @media (max-width: 992px) {
            .container {
                margin: 1.5rem;
                padding: 1.5rem;
            }

            .row {
                gap: 1rem;
            }

            .col-md-6 {
                min-width: 100%;
            }

            .label {
                min-width: 140px;
            }
        }

        @media (max-width: 768px) {
            .container {
                margin: 1rem;
                padding: 1rem;
            }

            h1 {
                font-size: 1.75rem;
                margin-bottom: 1.5rem;
            }

            .details-card {
                padding: 1rem;
            }

            .label {
                min-width: 120px;
                margin-right: 0.75rem;
            }

            p {
                padding: 0.5rem 0;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .container {
                margin: 0.75rem;
                padding: 0.75rem;
            }

            h1 {
                font-size: 1.5rem;
                padding: 0.75rem 0;
            }

            .row {
                margin: -0.5rem;
            }

            .col-md-6 {
                padding: 0.5rem;
            }

            .label {
                min-width: 100px;
            }

            .btn-primary {
                width: 100%;
                margin-top: 1rem;
            }
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -1rem;
            gap: 1.5rem;
            position: relative;
        }

        /* Add vertical line between columns */
        .row::after {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            height: 100%;
            width: 1px;
            background-color: var(--separator-color);
            transform: translateX(-50%);
        }

        .col-md-6 {
            flex: 1;
            min-width: 300px;
            padding: 1rem;
        }

        /* Modified label and text separation */
        p {
            margin: 0 0 1.25rem 0;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: baseline;
            position: relative;
        }

        .label {
            font-weight: 600;
            color: var(--primary-color);
            display: inline-block;
            margin-right: 1.5rem;
            min-width: 160px;
            padding: 0.5rem 0;
            position: relative;
            flex-shrink: 0;
        }

        /* Add vertical line after label */
        .label::after {
            content: '';
            position: absolute;
            right: -0.75rem;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            width: 1px;
            background-color: var(--separator-color);
        }

        /* Mobile responsiveness updates */
        @media (max-width: 992px) {
            .row::after {
                display: none; /* Remove vertical line on mobile */
            }
            
            .col-md-6 {
                min-width: 100%;
            }

            /* Add horizontal line between sections on mobile */
            .col-md-6:first-child {
                border-bottom: 1px solid var(--separator-color);
                padding-bottom: 2rem;
                margin-bottom: 2rem;
            }
        }

        @media (max-width: 768px) {
            .label {
                min-width: 140px;
            }

            .label::after {
                height: 60%;
            }
        }

        @media (max-width: 576px) {
            .label {
                min-width: 120px;
            }

            p {
                flex-direction: column;
            }

            .label::after {
                display: none;
            }

            /* Add horizontal separator for mobile view */
            .label {
                border-bottom: 1px solid var(--separator-color);
                margin-bottom: 0.5rem;
                padding-bottom: 0.25rem;
            }
        }

        span{
            margin-left: 40px;
        }

        .multi-line-content {
            display: inline-block;
            line-height: 1.8;
            padding: 0.5rem 0;
            flex-grow: 1; /* Allow content to grow */
            margin-left: 1px; /* Maintain consistent spacing with other content */
        }

        .multi-line-content br {
            content: "";
            display: block;
            margin: 4px 0; /* Reduced margin for tighter spacing */
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            p {
                flex-direction: column;
            }

            .multi-line-content {
                margin-left: 20px;
                width: 100%;
                padding-top: 0;
            }

            .label {
                margin-bottom: 0.5rem;
            }
        }

        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            width: 100%;
            padding: 10px;
        }

        .btn {
            padding: 12px 24px;
            font-weight: 500;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.95rem;
            min-width: 140px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            font-size: 15px;
        }

        .btn-meeting {
            background-color: #e69400;
            color: white;
        }


        .btn-primary {
            background-color: #0d693e;
            color: white;
        }

        .btn-primary:hover {
            background-color: #094e2e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 105, 62, 0.2);
            color: white;
        }

        .btn-info {
            background-color: #4A90E2;
            color: white;
        }

        .btn-info:hover {
            background-color: #357ABD;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.2);
            color: white;
        }

        .btn:disabled {
            background-color: #E0E0E0;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
            opacity: 0.7;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                padding: 15px 0;
            }

            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }

        .multi-line-content {
            display: block;
        }
        .multi-line-content br + br {
            content: "";
            display: block;
            margin-bottom: 1em;
        }

</style>
</head>
<body>
    
    <div class="container content-container">
        <a href="view_referrals_page.php" class="modern-back-button">
        <i class="fas fa-arrow-left"></i>Back to Referrals Page</a> 

        <h1 class="mb-4">REFERRAL DETAILS</h1>
        <div class="detail-row">
          <div class="col-md-6">
            <p><span class="label">Date Referred:</span>
            <span class="multi-line-content"><?php echo htmlspecialchars($referral['formatted_date']); ?></span></p>

            <p><span class="label">Student Referred:</span>
            <span class="multi-line-content">
                <?php 
                if (!empty($referral['referred_student_name'])) {
                    echo htmlspecialchars($referral['referred_student_name']);
                } else {
                    echo "No student information available";
                }
                ?>
            </span></p>

            <p><span class="label">Reason for Referral:</span>
            <span class="multi-line-content"><?php echo htmlspecialchars($referral['detailed_reason']); ?></span></p>

            <p><span class="label">Faculty Name:</span>
            <span class="multi-line-content"><?php echo htmlspecialchars($referral['faculty_name']); ?></span></p>

            <p><span class="label">Acknowledged By:</span>
            <span class="multi-line-content"><?php echo htmlspecialchars($referral['acknowledged_by']); ?></span></p>

            <p><span class="label">Current Status:</span>
            <span class="multi-line-content"><?php echo htmlspecialchars($referral['status'] ?? 'Pending'); ?></span></p>
        </div>
    </div>

    <div class="action-buttons">
            <form id="markAsDoneForm" method="POST" style="display: inline;">
                <input type="hidden" name="update_status" value="1">
                <button type="button" 
                        onclick="confirmMarkAsDone()"
                        class="btn btn-info p-4" 
                        <?php echo ($referral['status'] === 'Done') ? 'disabled' : ''; ?>>
                    <i class="fas fa-calendar-check"></i>
                    Mark as Done
                </button>
            </form>

            <?php 
            // Only show profile button for CEIT students (those with student_id)
            if ($referred_student_id && $referral['student_id'] !== null): 
            ?>
                <a href="view_student_profile-generate_pdf.php?student_id=<?php echo htmlspecialchars($referred_student_id); ?>" 
                   target="_blank" class="btn btn-success">
                    <i class="fas fa-user"></i>
                    View Student<br>Profile
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-secondary" disabled>
                    <i class="fas fa-user"></i>
                    No Student<br>Profile Available
                    <br><small>(Non-CEIT Student)</small>
                </button>
            <?php endif; ?>

            <a href="generate_referral_pdf.php?id=<?php echo $referral_id; ?>" 
               target="_blank" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i>
                Generate<br> PDF
            </a>
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