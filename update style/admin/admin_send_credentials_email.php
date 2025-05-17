<?php
session_start();
include '../db.php';
require '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the user is logged in as an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if we have new account data in the session
if (!isset($_SESSION['new_account'])) {
    header("Location: admin_register_accounts.php");
    exit();
}

$account = $_SESSION['new_account'];
$message = '';

// Function to capitalize first letter of each word
function properCase($string) {
    return ucwords(strtolower($string));
}

// Format names properly
$first_name = properCase($account['first_name']);
$last_name = properCase($account['last_name']);
$middle_initial = strtoupper($account['middle_initial']);

// Create full name with middle initial if it exists
$full_name = $middle_initial ? 
    "$first_name $middle_initial. $last_name" : 
    "$first_name $last_name";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create a new PHPMailer instance
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
        $mail->setFrom('your-email@gmail.com', 'CEIT Guidance Office');
        $mail->addAddress($account['email']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to CEIT Guidance Office Circle';
        $mail->Body    = "
        <p>Greetings!</p>
        <p>Welcome to CEIT Guidance Office Circle! You can now log in as {$account['user_type']}, use these credentials:</p>
        <p>Name: {$full_name}</p>
        <p>Username: {$account['username']}</p>
        <p>Email: {$account['email']}</p>
        <p>Password: {$account['password']}</p>
        <p>Thank you!</p>
        <p>Best Regards</p>
        <p>Guidance Office - Admin</p>
        ";

        $mail->send();
        $message = "Email sent successfully to {$account['email']}";
        
        // Clear the session data
        unset($_SESSION['new_account']);
    } catch (Exception $e) {
        $message = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Credentials Email</title>
    <link rel="stylesheet" type="text/css" href="admin_styles.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #FAF3E0;
            font-family: Arial, sans-serif;
        }
        .main-content {
            padding: 20px;
        }
        .form-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 600px;
            margin: 2rem auto;
        }
        .form-container h2 {
            color: #003366;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            font-weight: bold;
            color: #003366;
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-control {
            background-color: #f9f9f9;
            border: 2px solid #ccc;
            border-radius: 6px;
            padding: 0.6rem;
            font-size: 0.9rem;
        }
        .btn-primary {
            background-color: #4a90e2;
            border: none;
            border-radius: 25px;
            padding: 0.9rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #3a7bc8;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .account-info {
            background-color: #f0f8ff;
            border: 1px solid #b0d4ff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .account-info p {
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .account-info strong {
            color: #003366;
        }
    </style>
</head>
<body>
    <div class="header">
        CAVITE STATE UNIVERSITY-MAIN
    </div>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
        <div class="form-container">
            <h2>Send Credentials Email</h2>
            <div class="account-info">
                <p><strong>User Type:</strong> <?php echo htmlspecialchars($account['user_type']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($account['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($account['email']); ?></p>
            </div>
            <form method="post">
                <button type="submit" class="btn btn-primary">Send Credentials Email</button>
            </form>
        </div>
    </div>
    <div class="footer">
        <p>Contact number | Email | Copyright</p>
    </div>

    <script>
    <?php if($message): ?>
    Swal.fire({
        title: 'Email Status',
        text: "<?php echo $message; ?>",
        icon: '<?php echo strpos($message, "successfully") !== false ? "success" : "error"; ?>',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'admin_register_accounts.php';
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>