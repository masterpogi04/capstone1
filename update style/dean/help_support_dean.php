<?php
session_start();
include '../db.php'; // Ensure database connection is established

// Ensure the user is logged in and is an dean
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'dean') {
    header("Location: ../login.php");
    exit();
}

// Get the dean's ID from the session
$dean_id = $_SESSION['user_id'];

// Fetch the dean's details from the database
$stmt = $connection->prepare("SELECT name, profile_picture FROM tbl_dean WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error); // Output the error message
}
$stmt->bind_param("i", $dean_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $dean = $result->fetch_assoc();
    $name = $dean['name'];
    $profile_picture = $dean['profile_picture'];
} else {
    die("dean not found.");
}

mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CEIT - Guidance Office Help & Support</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <style>
    body {
      background-color: #FAF3E0;
      font-family: Arial, sans-serif;
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      background-color: #1A6E47;
      color: white;
      padding-top: 20px;
      width: 250px;
      position: fixed;
      height: 100%;
      overflow-y: auto;
    }
    .sidebar a {
      color: white;
      display: block;
      padding: 15px;
      text-decoration: none;
      transition: background-color 0.3s;
    }
    .sidebar a:hover, .sidebar a.active {
      background-color: #F2A54B;
    }
    .main-content {
      flex: 1;
      margin-left: 250px;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    .header {
      background-color: #F4A261;
      padding: 15px;
      color: black;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .footer {
      background-color: #F4A261;
      padding: 10px;
      color: black;
      text-align: center;
      margin-top: auto;
    }
    .profile-section {
      text-align: center;
      padding: 20px 15px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .profile-section img {
      width: 100px;
      height: 100px;
      border: 3px solid white;
      border-radius: 50%;
      margin-bottom: 10px;
    }
    .profile-section p {
      margin-bottom: 0;
      font-weight: bold;
    }
    .notification-icon {
      font-size: 24px;
      color: #1A6E47;
      cursor: pointer;
      transition: color 0.3s;
    }
    .notification-icon:hover {
      color: #F2A54B;
    }
    .content-area {
      padding: 20px;
      background-color: white;
      margin: 20px;
      border-radius: 5px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .faq-section h3 {
      color: #1A6E47;
      margin-bottom: 20px;
    }
    .faq-section h5 {
      color: #1A6E47;
      margin-top: 15px;
    }
  </style>
</head>
<body>
<div class="header">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h1>CEIT - GUIDANCE OFFICE</h1>
        <i class="fas fa-bell notification-icon" onclick="toggleNotifications()"></i>
    </div>
    <?php include 'dean_sidebar.php'; ?>
    <main class="main-content">
  <div class="main-content">
  
    <main class="content-area">
      <h2>Help & Support</h2>
      <div class="faq-section">
        <h3>Frequently Asked Questions (FAQs)</h3>
        <h5>1. How do I reset my password?</h5>
        <p>To reset your password, go to the login page and click on "Forgot Password". Follow the instructions to reset your password via email.</p>
        
        <h5>2. How do I update my profile information?</h5>
        <p>To update your profile information, go to your account settings by clicking on your profile icon at the top right corner and select "Account Settings". Make the necessary changes and save.</p>
        
        <h5>3. How do I contact support?</h5>
        <p>If you need assistance, you can contact our support team via email at support@cvsu.edu.ph or call us at (123) 456-7890.</p>
      </div>

      
    </main>
    <footer class="footer">
            <p>&copy; 2024 All Rights Reserved</p>
        </footer>

  </div>
</body>
</html>