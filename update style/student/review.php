<?php
session_start();
include '../db.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['student_profile'])) {
    header("Location: student_homepage.php");
    exit;
}

$profile = $_SESSION['student_profile'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_submission'])) {
    // Set a flag in the session to indicate the profile is complete
    $_SESSION['profile_completed'] = true;
    
    // Redirect to a confirmation page
    header("Location: confirmation.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="student_profile_form.css">
    <title>Review Your Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="header">
        <h1>Student Profile Form for Inventory</h1>
    </div>
    <div class="container mt-5">
        <h2>Review Your Profile</h2>
        <div class="card">
            <div class="card-body">
                <!-- Personal Information Section -->
                <h3>Personal Information</h3>
                <?php
                $personal_info = ['last_name', 'first_name', 'middle_name', 'gender', 'birthdate', 'age', 'birthplace', 'nationality', 'religion', 'civilStatus'];
                foreach ($personal_info as $key):
                    if (isset($profile[$key])):
                ?>
                    <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo htmlspecialchars($profile[$key]); ?></p>
                <?php 
                    endif;
                endforeach;
                ?>

                <!-- Contact Information Section -->
                <h3>Contact Information</h3>
                <?php
                $contact_info = ['contactNumber', 'email'];
                foreach ($contact_info as $key):
                    if (isset($profile[$key])):
                ?>
                    <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo htmlspecialchars($profile[$key]); ?></p>
                <?php 
                    endif;
                endforeach;
                ?>

                <!-- Address Information Section -->
                <h3>Address Information</h3>
                <?php
                $address_info = ['province', 'city', 'barangay', 'zipcode', 'country', 'PermanentAddress', 'currentAddress'];
                foreach ($address_info as $key):
                    if (isset($profile[$key])):
                ?>
                    <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo htmlspecialchars($profile[$key]); ?></p>
                <?php 
                    endif;
                endforeach;
                ?>

                <!-- Family Background Section -->
                <h3>Family Background</h3>
                <?php
                $family_info = ['fatherName', 'motherName', 'siblings', 'birthOrder', 'familyIncome'];
                foreach ($family_info as $key):
                    if (isset($profile[$key])):
                ?>
                    <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo htmlspecialchars($profile[$key]); ?></p>
                <?php 
                    endif;
                endforeach;
                ?>

                <!-- Educational Information Section -->
                <h3>Educational Information</h3>
                <?php
                $educational_info = ['course', 'year_level', 'student_id', 'semester'];
                foreach ($educational_info as $key):
                    if (isset($profile[$key])):
                ?>
                    <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo htmlspecialchars($profile[$key]); ?></p>
                <?php 
                    endif;
                endforeach;
                ?>

                <!-- Educational Background Section -->
                <h3>Educational Background</h3>
                <?php
                $educational_background = ['elementary', 'secondary', 'transferee'];
                foreach ($educational_background as $key):
                    if (isset($profile[$key])):
                ?>
                    <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> <?php echo htmlspecialchars($profile[$key]); ?></p>
                <?php 
                    endif;
                endforeach;
                ?>

                <!-- Career Information Section -->
                <h3>Career Information</h3>
                <?php
                $career_info = ['course_factors', 'career_concerns'];
                foreach ($career_info as $key):
                    if (isset($profile[$key])):
                ?>
                    <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> 
                    <?php 
                        if (is_array($profile[$key])) {
                            echo htmlspecialchars(implode(", ", $profile[$key]));
                        } else {
                            echo htmlspecialchars($profile[$key]);
                        }
                    ?>
                    </p>
                <?php 
                    endif;
                endforeach;
                ?>

                <!-- Medical Information Section -->
                <h3>Medical Information</h3>
                <?php
                $medical_info = ['medications', 'conditions', 'other_conditions', 'allergy', 'scoliosis', 'suicide', 'suicide_reason', 'problems', 'family_problems', 'fitness', 'fitness_specify', 'fitness_frequency', 'stress'];
                foreach ($medical_info as $key):
                    if (isset($profile[$key])): // Removed !empty() check
                        if (is_array($profile[$key])):
                ?>
                            <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> 
                                <?php echo htmlspecialchars(implode(", ", $profile[$key])); ?>
                            </p>
                <?php   else: ?>
                            <p><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong> 
                                <?php echo htmlspecialchars($profile[$key]); ?>
                            </p>
                <?php 
                        endif;
                    endif;
                endforeach;
                ?>

                <!-- Signature Section -->
                <h3>Signature</h3>
                <?php if (isset($profile['signature_path'])): ?>
                    <img src="<?php echo htmlspecialchars($profile['signature_path']); ?>" alt="Digital Signature" style="max-width: 400px; border: 1px solid #000;">
                <?php else: ?>
                    <p>No signature provided.</p>
                <?php endif; ?>

            </div>
        </div>
        <form method="POST">
            <button type="submit" name="confirm_submission" class="btn btn-primary mt-3">Confirm and Submit</button>
        </form>
        <a href="personal_info.php" class="btn btn-secondary mt-3">Make Changes</a>
    </div>
</body>
</html>