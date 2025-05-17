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
    // Generate a profile ID
    $profile_id = generateProfileId($connection);
    
    // Update the student's profile with the permanent profile ID
    // This replaces the temporary profile_id set in personal_info.php
    $sql = "UPDATE student_profiles SET profile_id = ? WHERE student_id = ? AND profile_id LIKE 'temp_%'";
    $stmt = $connection->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ss", $profile_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            // Set a flag in the session to indicate the profile is complete
            $_SESSION['profile_completed'] = true;
            $_SESSION['profile_id'] = $profile_id;
            
            // Redirect to a confirmation page
            header("Location: confirmation.php");
            exit;
        } else {
            // Handle database error
            $error = "Error updating profile: " . $stmt->error;
        }
    } else {
        // Handle prepare error
        $error = "Error preparing statement: " . $connection->error;
    }
}

// Function to generate profile ID - keep the same as in personal_info.php
function generateProfileId($connection) {
    $query = "SELECT MAX(CAST(SUBSTRING(profile_id, 9) AS UNSIGNED)) as max_id FROM student_profiles WHERE profile_id LIKE 'Stu_pro_%'";
    $result = $connection->query($query);
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return 'Stu_pro_' . str_pad($next_id, 9, '0', STR_PAD_LEFT);
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
                $family_info = ['fatherName', 'fatherContact', 'fatherOccupation', 'motherName', 'motherContact', 'motherOccupation', 
                               'guardianName', 'guardianRelationship', 'guardianContact', 'guardianOccupation',
                               'siblings', 'birthOrder', 'familyIncome'];
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
                
                <!-- Course Factors -->
                <?php if (isset($profile['course_factors']) || isset($profile['combinedFactors'])): ?>
                    <p><strong>Course Factors:</strong> 
                    <?php 
                        $factors = isset($profile['course_factors']) ? $profile['course_factors'] : 
                                  (isset($profile['combinedFactors']) ? $profile['combinedFactors'] : '');
                                  
                        if (is_array($factors)) {
                            echo htmlspecialchars(implode("; ", $factors));
                        } else {
                            echo htmlspecialchars($factors);
                        }
                    ?>
                    </p>
                <?php endif; ?>
                
                <!-- Career Concerns -->
                <?php if (isset($profile['career_concerns']) || isset($profile['combined_career_concerns']) || isset($profile['combinedCareerConcerns'])): ?>
                    <p><strong>Career Concerns:</strong> 
                    <?php 
                        $concerns = isset($profile['career_concerns']) ? $profile['career_concerns'] : 
                                   (isset($profile['combined_career_concerns']) ? $profile['combined_career_concerns'] : 
                                   (isset($profile['combinedCareerConcerns']) ? $profile['combinedCareerConcerns'] : ''));
                                   
                        if (is_array($concerns)) {
                            echo htmlspecialchars(implode("; ", $concerns));
                        } else {
                            echo htmlspecialchars($concerns);
                        }
                    ?>
                    </p>
                <?php endif; ?>

                <!-- Medical Information Section -->
                <h3>Medical Information</h3>
                
                <!-- Medications -->
                <?php if (isset($profile['medications'])): ?>
                    <p><strong>Medications:</strong> <?php echo htmlspecialchars($profile['medications']); ?></p>
                <?php endif; ?>
                
                <!-- Medical Conditions -->
                <?php if (isset($profile['conditions'])): ?>
                    <p><strong>Medical Conditions:</strong> 
                    <?php 
                        if (is_array($profile['conditions'])) {
                            echo htmlspecialchars(implode("; ", $profile['conditions']));
                        } else {
                            echo htmlspecialchars($profile['conditions']);
                        }
                    ?>
                    </p>
                <?php endif; ?>
                
                <!-- Suicide Information -->
                <?php if (isset($profile['suicide'])): ?>
                    <p><strong>Suicide Attempt:</strong> <?php echo htmlspecialchars($profile['suicide']); ?></p>
                    <?php if (isset($profile['suicide_reason']) && $profile['suicide'] == 'yes'): ?>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($profile['suicide_reason']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Problems -->
                <?php if (isset($profile['problems'])): ?>
                    <p><strong>Personal Problems:</strong> 
                    <?php 
                        if (is_array($profile['problems'])) {
                            echo htmlspecialchars(implode("; ", $profile['problems']));
                        } else {
                            echo htmlspecialchars($profile['problems']);
                        }
                    ?>
                    </p>
                <?php endif; ?>
                
                <!-- Family Problems -->
                <?php if (isset($profile['family_problems']) || isset($profile['fam-problems'])): ?>
                    <p><strong>Family Problems:</strong> 
                    <?php 
                        $famProblems = isset($profile['family_problems']) ? $profile['family_problems'] : 
                                      (isset($profile['fam-problems']) ? $profile['fam-problems'] : '');
                                      
                        if (is_array($famProblems)) {
                            echo htmlspecialchars(implode("; ", $famProblems));
                        } else {
                            echo htmlspecialchars($famProblems);
                        }
                    ?>
                    </p>
                <?php endif; ?>
                
                <!-- Fitness -->
                <?php if (isset($profile['fitness'])): ?>
                    <p><strong>Fitness Activity:</strong> <?php echo htmlspecialchars($profile['fitness']); ?></p>
                    <?php if ($profile['fitness'] == 'yes' && isset($profile['fitness_specify'])): ?>
                        <p><strong>Fitness Activity Type:</strong> <?php echo htmlspecialchars($profile['fitness_specify']); ?></p>
                    <?php endif; ?>
                    <?php if ($profile['fitness'] == 'yes' && isset($profile['fitness_frequency'])): ?>
                        <p><strong>Fitness Frequency:</strong> <?php echo htmlspecialchars($profile['fitness_frequency']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Stress Level -->
                <?php if (isset($profile['stress'])): ?>
                    <p><strong>Stress Level:</strong> <?php echo htmlspecialchars($profile['stress']); ?></p>
                <?php endif; ?>

                <!-- Signature Section -->
                <h3>Signature</h3>
                <?php if (isset($profile['signature_path'])): ?>
                    <img src="<?php echo htmlspecialchars($profile['signature_path']); ?>" alt="Digital Signature" style="max-width: 400px; border: 1px solid #000;">
                <?php elseif (isset($profile['signature']) && !empty($profile['signature'])): ?>
                    <img src="<?php echo htmlspecialchars($profile['signature']); ?>" alt="Digital Signature" style="max-width: 400px; border: 1px solid #000;">
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