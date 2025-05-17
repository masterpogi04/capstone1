<?php
session_start();
include '../db.php'; // Ensure database connection is established

// Ensure the user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'instructor') {
    header("Location: ../login.php");
    exit();
}

// Get the instructor's ID from the session
$instructor_id = $_SESSION['user_id'];

// Fetch the instructor's details from the database
$stmt = $connection->prepare("SELECT first_name, last_name, profile_picture FROM tbl_instructor WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error); // Output the error message
}
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $name = $admin['first_name'] . ' ' . $admin['last_name'];  // Concatenate first and last name
    $profile_picture = $admin['profile_picture'];
} else  {
    die("Instructor not found.");
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CEIT - Guidance Office Help & Support</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

    <style>
:root {
    --primary-color: #003366;
    --secondary-color: #4a90e2;
    --background-color: #f4f7fa;
    --text-color: #333;
    --border-color: #d1d9e6;
    --faq-bg-color: #ffffff;
    --faq-hover-color: #e8f0fe;
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}


.main-content {
    margin-left: 250px; /* Adjust based on your sidebar width */
    padding: 2rem;
}

.dashboard-container {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: var(--box-shadow);
    padding: 2rem;
    margin-bottom: 2rem;
}

.dashboard-container h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--secondary-color);
    padding-bottom: 0.5rem;
}

.dashboard-container h3 {
    color: var(--secondary-color);
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.faq-section {
    background-color: var(--faq-bg-color);
    border-radius: 8px;
    overflow: hidden;
}

.faq-item {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
}

.faq-item:last-child {
    border-bottom: none;
}



.faq-question {
    font-weight: bold;
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.faq-item ol, .faq-item ul {
    padding-left: 1.5rem;
}

.faq-item li {
    margin-bottom: 0.5rem;
}



/* Responsive adjustments */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }

    .dashboard-container {
        padding: 1.5rem;
    }
}
/* Large screens (1200px and up) */
@media screen and (min-width: 1200px) {
    .dashboard-container {
        max-width: 1140px;
        margin: 0 auto 2rem auto;
    }
}

/* Medium screens (992px to 1199px) */
@media screen and (max-width: 1199px) {
    .main-content {
        margin-left: 220px;
        padding: 1.5rem;
    }
}

/* Tablet screens (768px to 991px) */
@media screen and (max-width: 991px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }

    .menu-toggle {
        display: block;
    }

    .header h1 {
        font-size: 1.2rem;
    }

    .dashboard-container {
        padding: 1.5rem;
    }

    .faq-item {
        padding: 1.25rem;
    }
}

/* Mobile screens (up to 767px) */
@media screen and (max-width: 767px) {
    .header h1 {
        font-size: 1rem;
    }

    .dashboard-container {
        padding: 1rem;
    }

    .dashboard-container h2 {
        font-size: 1.5rem;
    }

    .dashboard-container h3 {
        font-size: 1.25rem;
    }

    .faq-item {
        padding: 1rem;
    }

    .faq-question {
        font-size: 1rem;
    }

    .faq-item ol, .faq-item ul {
        padding-left: 1.25rem;
    }

    .footer {
        padding: 0.75rem;
        font-size: 0.8rem;
    }
}

/* Small mobile screens (up to 480px) */
@media screen and (max-width: 480px) {
    .main-content {
        padding: 0.75rem;
    }

    .dashboard-container {
        padding: 0.75rem;
    }

    .faq-item {
        padding: 0.75rem;
    }

    .header h1 {
        font-size: 0.9rem;
    }
}

</style>

<body>
    <div class="header">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
        <h1>CEIT - GUIDANCE OFFICE</h1>
    </div>
    <?php include 'instructor_sidebar.php'; ?>
    
    <div class="main-content">
        
        <div class="container">
    <div class="dashboard-container">
        <h2>CEIT | Help & Support</h2>
       
        <h3>Frequently Asked Questions (FAQs)</h3>
        <div class="faq-section">
            <div class="faq-item">
                <p class="faq-question">1. How do I reset my password?</p>
                <p>To reset your password, please follow these steps:</p>
                <ol>
                    <li>Navigate to the login page</li>
                    <li>Click on the "Forgot Password" link</li>
                    <li>Enter your registered email address</li>
                    <li>Follow the instructions sent to your email to complete the password reset process</li>
                </ol>
            </div>
            
            <div class="faq-item">
                <p class="faq-question">2. How do I update my profile information?</p>
                <p>To update your profile information:</p>
                <ol>
                    <li>Log into your account</li>
                    <li>Click "My Profile" on your Sidebar</li>
                    <li>Make the necessary changes to your profile</li>
                    <li>Click "Update Profile" to confirm your updates</li>
                </ol>
            </div>
            
            <div class="faq-item">
                <p class="faq-question">3. How do I contact support?</p>
                <p>Our support team is available to assist you through the following channels:</p>
                <ul>
                    <li>Email: support@cvsu.edu.ph</li>
                    <li>Phone: (123) 456-7890</li>
                    <li>Office Hours: Monday to Friday, 8:00 AM to 5:00 PM</li>
                </ul>
            </div>
        </div>

    </div>
</div>
<footer class="footer">
      <p>Contact: (123) 456-7890 | Email: info@cvsu.edu.ph | © 2024 Cavite State University. All rights reserved.</p>
        </footer>

    <script>
        $(document).ready(function() {
            $('.faq-question').click(function() {
                $(this).next('.faq-answer').slideToggle();
            });
        });
    </script>
</body>
</html>