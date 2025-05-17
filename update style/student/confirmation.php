<?php
session_start();

// Check if the user has been redirected here after successful submission
if (!isset($_SESSION['profile_submitted'])) {
    header("Location: student_homepage.php");
    exit;
}

// Clear the session variable
unset($_SESSION['profile_submitted']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Submission Confirmation</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #0d693e, #004d4d);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .confirmation-container {
            max-width: 600px;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .header {
            background-color: #F4A261;
            color: black;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            margin: -30px -30px 20px -30px;
        }
        .confirmation-icon {
            font-size: 64px;
            color: #28a745;
        }
        .btn-custom {
            background-color: #0f6a1a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        .btn-custom:hover {
            background-color: #0d5817;
            color: white;
        }
    </style>
</head>
<body>
    <div class="confirmation-container text-center">
        <div class="header">
            <h2 class="mb-0">Profile Submission Confirmation</h2>
        </div>
        <i class="fas fa-check-circle confirmation-icon mb-4"></i>
        <h3 class="mb-4">Profile Submitted Successfully!</h3>
        <p class="lead">Thank you for submitting your student profile. Your information has been recorded in our system.</p>
        <p>If you need to make any changes or have any questions, please contact your College Guidance Office.</p>
        <a href="student_dashboard.php" class="btn-custom">
            <i class="fas fa-home"></i> Return to Dashboard
        </a>
    </div>
</body>
</html>