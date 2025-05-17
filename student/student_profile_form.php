<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Check if the student has already submitted a completed profile
// Only count records with permanent profile IDs (starting with 'Stu_pro_')
$stmt = $connection->prepare("SELECT COUNT(*) FROM student_profiles WHERE student_id = ? AND profile_id LIKE 'Stu_pro_%'");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_count = $result->fetch_row()[0];

$current_page = isset($_GET['page']) ? $_GET['page'] : 'personal_info';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile Inventory</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="student_profile_form.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
       .header {
    background-color: #1b651b;
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header h1 {
    text-align: center;
    margin: 0;
    font-size: 2rem;
    font-weight: 600;
}
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Profile Form for Inventory</h1>
    </div>

    <div class="container mt-4">
    <a href="student_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        <?php if ($profile_count > 0): ?>
            <div class="alert-custom text-center">
                <h4><i class="fas fa-exclamation-circle"></i> Profile Already Submitted</h4>
                <p>You have already submitted your student profile. You cannot submit a new one.</p>
                <p>If you need to make any changes or have any questions, please proceed to the your College Guidance Office to handle this request.</p>
                <a href="student_homepage.php" class="btn-custom">
                    <i class="fas fa-arrow-left"></i> Return to Homepage
                </a>
            </div>
        <?php else: ?>
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <a class="navbar-brand" href="#">Student Profile Form</a>
                <div class="navbar-nav ml-auto">
                    <a class="nav-item nav-link <?php echo $current_page == 'personal_info' ? 'active' : ''; ?>" href="?page=personal_info">Personal Info</a>
                    <a class="nav-item nav-link <?php echo $current_page == 'family_background' ? 'active' : ''; ?>" href="?page=family_background">Family Background</a>
                    <a class="nav-item nav-link <?php echo $current_page == 'educational_career' ? 'active' : ''; ?>" href="?page=educational_career">Educational & Career</a>
                    <a class="nav-item nav-link <?php echo $current_page == 'medical_history' ? 'active' : ''; ?>" href="?page=medical_history">Medical History</a>
                </div>
            </nav>

            <?php
            switch($current_page) {
                case 'personal_info':
                    include 'personal_info.php';
                    break;
                case 'family_background':
                    include 'family_background.php';
                    break;
                case 'educational_career':
                    include 'educational_career.php';
                    break;
                case 'medical_history':
                    include 'medical_history.php';
                    break;
                default:
                    include 'personal_info.php';
            }
            ?>
        <?php endif; ?>
    </div>

    <script>
        let currentSection = 1;

        function showSection(section) {
            document.querySelectorAll('.form-section').forEach((el, index) => {
                el.classList.remove('active');
                if (index === section - 1) {
                    el.classList.add('active');
                }
            });
        }

        function nextSection() {
            currentSection++;
            if (currentSection > document.querySelectorAll('.form-section').length) {
                currentSection = document.querySelectorAll('.form-section').length;
            }
            showSection(currentSection);
        }

        function previousSection() {
            currentSection--;
            if (currentSection < 1) {
                currentSection = 1;
            }
            showSection(currentSection);
        }

        document.getElementById('birthdate').addEventListener('change', function() {
            const birthdate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthdate.getFullYear();
            const monthDiff = today.getMonth() - birthdate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            document.getElementById('age').value = age;
        });

        // Other JavaScript as needed
        function initMap() {
            // Center the map on the Philippines
            var philippines = {lat: 13.4125, lng: 122.5621};
            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 6,
                center: philippines
            });
        }
    </script>
</body>
</html>