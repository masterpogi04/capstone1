<?php
session_start();
include '../db.php';
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Check if request ID is provided
if (!isset($_GET['id'])) {
    header("Location: facilitator_homepage.php");
    exit();
}

$request_id = $_GET['id'];

// Fetch facilitator's name
$facilitator_id = $_SESSION['user_id'];
$facilitator_stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
if ($facilitator_stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$facilitator_stmt->bind_param("i", $facilitator_id);
$facilitator_stmt->execute();
$facilitator_result = $facilitator_stmt->get_result();
$facilitator = $facilitator_result->fetch_assoc();

// Combine the name parts
$facilitator_name = $facilitator['first_name'];
if (!empty($facilitator['middle_initial'])) {
    $facilitator_name .= ' ' . $facilitator['middle_initial'];
}
$facilitator_name .= ' ' . $facilitator['last_name'];

// Fetch request details
$stmt = $connection->prepare("SELECT * FROM document_requests WHERE request_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}
$stmt->bind_param("s", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    header("Location: facilitator_homepage.php");
    exit();
}

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $request['contact_email'];
    $subject = "Document Request Rejected";
    $message = nl2br($_POST['email_body']);

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ceitguidanceoffice@gmail.com';
        $mail->Password   = 'qapb ebhc owts ioel';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('ceitguidanceoffice@gmail.com', 'CEIT Guidance Office');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        $_SESSION['message'] = "Rejection notification sent successfully.";
    } catch (Exception $e) {
        $_SESSION['message'] = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
    }
    header("Location: facilitator_requested_documents.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejected Request Details</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
   <style>
        
        .header {
         background-color:white;
        padding: 10px;
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        color: #1b651b;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
    letter-spacing: 0.3px;
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}
        body {
        background: linear-gradient(to right, #0d693e, #004d4d);
        min-height: 100vh;
        font-family: 'Arial', sans-serif;
        color: #333;
    }

    .container {
        background-color: #ffffff;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    h1, h2 {
        color: #0d693e;
        font-weight: bold;
    }

    .card {
        border: none;
        margin-bottom: 30px;
    }

    .card-header {
        background-color: #0d693e;
        color: white;
        font-weight: bold;
    }

    .card-body p {
        margin-bottom: 10px;
    }

    .form-group label {
        font-weight: bold;
        color: #0d693e;
    }

    textarea.form-control {
        border: 1px solid #ced4da;
        border-radius: 5px;
    }

    .button-container {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        border: none;
        border-radius: 5px;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #0d693e;
        color: white;
    }

    .btn-primary:hover {
        background-color: #094e2e;
    }

    .btn-secondary {
        background-color: #ff9f1c;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #e08c19;
    }

    @media (max-width: 768px) {
        .button-container {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
</style>
</head>
<body>
    <div class="header">
        STUDENT DOCUMENT REQUEST 
    </div>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Rejected Request Details</h1>
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Request ID: <?php echo htmlspecialchars($request['request_id']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Student Name:</strong> <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></p>
                        <p><strong>Student Number:</strong> <?php echo htmlspecialchars($request['student_number']); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($request['department']); ?></p>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($request['course']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Document Requested:</strong> <?php echo htmlspecialchars($request['document_request']); ?></p>
                        <p><strong>Contact Email:</strong> <?php echo htmlspecialchars($request['contact_email']); ?></p>
                        <p><strong>Request Time:</strong> <?php echo htmlspecialchars($request['request_time']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="text-center mt-5 mb-4">Send Rejection Notification</h2>
        <form action="" method="POST" class="email-form">
            <div class="form-group">
                <label for="email_body">Email Body:</label>
                <textarea class="form-control" id="email_body" name="email_body" rows="10">
                Dear <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>,

                We regret to inform you that your document request (ID: <?php echo htmlspecialchars($request['request_id']); ?>) has been rejected.

                Document Requested: <?php echo htmlspecialchars($request['document_request']); ?>

                Reason for Rejection:
                You still have an unsettled student violation. Kindly check your account.

                If you have any questions or need clarification, please don't hesitate to contact our office.

                Best regards,
                <?php echo htmlspecialchars($facilitator_name); ?><br>
                College of Engineering and Information Technology - Guidance Office</textarea>
            </div>
            <div class="button-container">
                <button type="submit" class="btn btn-primary">Send Email</button>
                <button type="button" onclick="window.location.href='facilitator_requested_documents.php'" class="btn modern-back-button">Back to Requested Documents</button>
            </div>
        </form>
    </div>

    <script>
    <?php if (isset($_SESSION['message'])): ?>
    Swal.fire({
        title: 'Info',
        text: '<?php echo $_SESSION['message']; ?>',
        icon: 'info'
    });
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    </script>
</body>
</html>