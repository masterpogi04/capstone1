<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$message = '';

// Handle contact number update if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_contact'])) {
    $contact_number = $_POST['contact_number'];
    
    // Validate phone number format (09 + 9 digits)
    if (preg_match('/^09\d{9}$/', $contact_number)) {
        $updateStmt = $connection->prepare("
            UPDATE student_profiles 
            SET contact_number = ? 
            WHERE student_id = ?
        ");
        
        if ($updateStmt === false) {
            $message = "Error: " . $connection->error;
        } else {
            $updateStmt->bind_param("ss", $contact_number, $student_id);
            
            if ($updateStmt->execute()) {
                $message = "Contact number updated successfully!";
            } else {
                $message = "Error updating contact number: " . $updateStmt->error;
            }
            $updateStmt->close();
        }
    } else {
        $message = "Invalid phone number format. Number must start with '09' followed by 9 digits.";
    }
}

$stmt = $connection->prepare("
    SELECT sp.*, s.department_id, s.course_id, d.name as department_name, c.name as course_name
    FROM student_profiles sp
    JOIN tbl_student ts ON sp.student_id = ts.student_id
    JOIN sections s ON ts.section_id = s.id
    JOIN departments d ON s.department_id = d.id
    JOIN courses c ON s.course_id = c.id
    WHERE sp.student_id = ?
");

if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

// Check if profile exists
$profileExists = ($profile !== null);

// Close the database connection
$stmt->close();
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2C3E50;
            --secondary-color: #3498DB;
            --accent-color: #E74C3C;
            --background-color: #ECF0F1;
            --card-color: #FFFFFF;
            --text-color: #2C3E50;
            --success-color: #28a745;
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

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

        .container {
            max-width: 1200px;
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .profile-section {
            background-color: var(--card-color);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .profile-section:hover {
            transform: translateY(-5px);
        }

        .section-title {
            color: var(--primary-color);
            border-bottom: 3px solid var(--secondary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .readonly-input {
            background-color: #F8F9FA;
            border: 1px solid #E9ECEF;
            border-radius: 8px;
            padding: 0.75rem;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .guidance-note {
            background: linear-gradient(135deg, #FFF3E0, #FFE0B2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid var(--accent-color);
        }

        .guidance-note h4 {
            color: var(--accent-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .signature-container {
            background: linear-gradient(135deg, #E8F5E9, #C8E6C9);
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
            text-align: center;
        }

        .signature-image {
            max-width: 300px;
            max-height: 100px;
            margin: 1rem 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .student-name {
            font-weight: 600;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px dashed #A5D6A7;
        }

        /* Back Button*/
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

        .modern-back-button:active {
            transform: translateY(0);
            box-shadow: 0 1px 4px rgba(46, 218, 168, 0.15);
        }

        .modern-back-button i {
            font-size: 0.9rem;
            position: relative;
            top: 1px;
        }

        textarea.readonly-input {
            min-height: 100px;
            resize: none;
        }

        /* No Profile Message Box */
        .no-profile-container {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 2.5rem;
            margin: 3rem auto;
            max-width: 800px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 2px dashed #ccc;
        }

        .no-profile-container i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .no-profile-container h3 {
            color: #343a40;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .no-profile-container p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .profile-form-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background-color: #1b651b;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(27, 101, 27, 0.2);
        }

        .profile-form-button:hover {
            background-color: #144d14;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(27, 101, 27, 0.3);
            color: white;
            text-decoration: none;
        }

        .profile-form-button:active {
            transform: translateY(0);
        }

        /* Edit button styles */
        .edit-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #3498DB;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-left: 10px;
        }

        .edit-button:hover {
            background-color: #2980B9;
            color: white;
        }

        .save-button {
            background-color: #1b651b;
            color: white;
        }

        .save-button:hover {
            background-color: #144d14;
        }

        .cancel-button {
            background-color: #6c757d;
            color: white;
        }

        .cancel-button:hover {
            background-color: #5a6268;
            color: white;
        }

        /* Input field for editing */
        .edit-input {
            border: 2px solid #3498DB;
            border-radius: 8px;
            padding: 0.75rem;
            color: var(--text-color);
            font-size: 0.95rem;
            background-color: white;
        }

        /* Alert message styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .profile-section {
                padding: 1.5rem;
            }
            
            .container {
                padding: 0 1rem;
            }

            .no-profile-container {
                padding: 1.5rem;
                margin: 2rem auto;
            }

            .no-profile-container i {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="text-center">Student Profile Form for Inventory</h1>
    </div>

    <?php if (!$profileExists): ?>
    <!-- No Profile Available Message -->
    <div class="container">
        <a href="student_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <div class="no-profile-container">
            <i class="fas fa-exclamation-circle"></i>
            <h3>No Student Profile Found</h3>
            <p>Please fill out the Student Profile Form for Inventory first to view your profile information.</p>
            <a href="student_profile_form.php" class="profile-form-button">
                <i class="fas fa-file-alt"></i>
                <span>Fill Out Profile Form</span>
            </a>
        </div>
    </div>
    <?php else: ?>
    <!-- Profile Exists - Show Profile Information -->
    <div class="container">
        <a href="student_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
        
        <div class="text-center mt-4"></div>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo (strpos($message, 'successfully') !== false) ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="guidance-note">
            <h4>Need to make changes?</h4>
            <p>If you wish to update information on this form (except contact number), please visit the Guidance Office. Our staff will be happy to assist you with any necessary changes.</p>
        </div>

        <form method="post">
            <h2>Personal Information</h2>
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" class="form-control readonly-input" id="full_name" value="<?php echo htmlspecialchars($profile['last_name'] . ', ' . $profile['first_name'] . ' ' . $profile['middle_name']); ?>" readonly>
            </div>
            <?php
            $personal_info = ['student_id', 'gender', 'birthdate', 'civil_status', 'province', 'city', 'email']; // Remove 'age' from array
            foreach ($personal_info as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <input type="text" class="form-control readonly-input" id="<?php echo $key; ?>" value="<?php echo htmlspecialchars($profile[$key]); ?>" readonly>
                </div>
            <?php 
                endif;
            endforeach;
            ?>

            <!-- Age field with dynamic calculation -->
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="text" class="form-control readonly-input" id="age" value="<?php 
                    if (!empty($profile['birthdate'])) {
                        $birthdate = new DateTime($profile['birthdate']);
                        $today = new DateTime();
                        echo $birthdate->diff($today)->y;
                    } else {
                        echo "N/A";
                    }
                ?>" readonly>
            </div>
                        
            <!-- Contact Number Field - Editable -->
            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <div class="input-group">
                    <input type="text" class="form-control <?php echo (isset($_GET['edit']) && $_GET['edit'] == 'contact') ? 'edit-input' : 'readonly-input'; ?>" 
                           id="contact_number" 
                           name="contact_number" 
                           value="<?php echo htmlspecialchars($profile['contact_number']); ?>" 
                           <?php echo (isset($_GET['edit']) && $_GET['edit'] == 'contact') ? '' : 'readonly'; ?>>
                    
                    <?php if (isset($_GET['edit']) && $_GET['edit'] == 'contact'): ?>
                        <div class="input-group-append">
                            <button type="submit" name="update_contact" class="btn edit-button save-button">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <a href="view_student_profile.php" class="btn edit-button cancel-button">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="input-group-append">
                            <a href="view_student_profile.php?edit=contact" class="edit-button">
                                <i class="fas fa-pencil-alt"></i> Edit
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (isset($_GET['edit']) && $_GET['edit'] == 'contact'): ?>
                    <small class="text-muted">Format: 09XXXXXXXXX (11 digits starting with 09)</small>
                <?php endif; ?>
            </div>

            <h2>Address Information</h2>
            <?php
            $address_info = ['permanent_address', 'current_address'];
            foreach ($address_info as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <input type="text" class="form-control readonly-input" id="<?php echo $key; ?>" value="<?php echo htmlspecialchars($profile[$key]); ?>" readonly>
                </div>
            <?php 
                endif;
            endforeach;
            ?>

            <h2>Educational Information</h2>
            <?php
            $educational_info = ['course_id', 'department_name', 'course_name', 'year_level', 'semester_first_enrolled'];
            foreach ($educational_info as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <input type="text" class="form-control readonly-input" id="<?php echo $key; ?>" value="<?php echo htmlspecialchars($profile[$key]); ?>" readonly>
                </div>
            <?php 
                endif;
            endforeach;
            ?>

            <h2>Family Background</h2>
            <?php
            $family_info = ['father_name', 'father_contact', 'father_occupation','mother_name', 'mother_contact', 'mother_occupation', 'guardian_name', 'guardian_relationship', 'guardian_contact', 'guardian_occupation', 'siblings', 'birth_order', 'family_income'];
            foreach ($family_info as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <input type="text" class="form-control readonly-input" id="<?php echo $key; ?>" value="<?php echo htmlspecialchars($profile[$key]); ?>" readonly>
                </div>
            <?php 
                endif;
            endforeach;
            ?>

            <h2>Educational Background</h2>
            <?php
            $educational_background = ['elementary', 'secondary', 'transferees'];
            foreach ($educational_background as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <input type="text" class="form-control readonly-input" id="<?php echo $key; ?>" value="<?php echo htmlspecialchars($profile[$key]); ?>" readonly>
                </div>
            <?php 
                endif;
            endforeach;
            ?>

            <h2>Career Information</h2>
            <?php
            $career_info = ['course_factors', 'career_concerns'];
            foreach ($career_info as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <textarea class="form-control readonly-input" id="<?php echo $key; ?>" rows="3" readonly><?php echo htmlspecialchars($profile[$key]); ?></textarea>
                </div>
            <?php 
                endif;
            endforeach;
            ?>

            <h2>Medical History</h2>
            <?php
            $medical_info = ['medications', 'medical_conditions', 'suicide_attempt', 'suicide_reason', 'problems', 'family_problems', 'fitness_activity', 'fitness_frequency', 'stress_level'];
            foreach ($medical_info as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <?php if (in_array($key, ['suicide_reason', 'problems', 'medical_conditions', 'family_problems'])): ?>
                        <textarea class="form-control readonly-input" id="<?php echo $key; ?>" rows="3" readonly><?php echo htmlspecialchars($profile[$key]); ?></textarea>
                    <?php else: ?>
                        <input type="text" class="form-control readonly-input" id="<?php echo $key; ?>" value="<?php echo htmlspecialchars($profile[$key]); ?>" readonly>
                    <?php endif; ?>
                </div>
            <?php 
                endif;
            endforeach;
            ?>

            <!-- Signature Section -->
            <div class="signature-container">
                <h3>Student Signature</h3>
                <p>I hereby attest that all information stated above is true and correct.</p>
                <?php if (!empty($profile['signature_path'])): ?>
                    <img src="<?php echo htmlspecialchars($profile['signature_path']); ?>" alt="Student Signature" class="signature-image">
                <?php else: ?>
                    <p>No signature available</p>
                <?php endif; ?>
                <p class="student-name">
                    <?php echo htmlspecialchars($profile['last_name'] . ', ' . $profile['first_name'] . ' ' . $profile['middle_name']); ?>
                </p>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <script>
        // JavaScript for phone number validation
        document.addEventListener('DOMContentLoaded', function() {
            const contactNumberInput = document.getElementById('contact_number');
            
            if (contactNumberInput) {
                contactNumberInput.addEventListener('input', function(e) {
                    // Remove any non-digit characters
                    let value = e.target.value.replace(/\D/g, '');
                    
                    // Ensure it starts with '09'
                    if (!value.startsWith('09') && value.length > 0) {
                        if (value.startsWith('9')) {
                            value = '0' + value;
                        } else {
                            value = '09';
                        }
                    }
                    
                    // Limit to 11 digits
                    if (value.length > 11) {
                        value = value.substring(0, 11);
                    }
                    
                    // Update the input value
                    e.target.value = value;
                });
            }
        });
    </script>
</body>
</html>