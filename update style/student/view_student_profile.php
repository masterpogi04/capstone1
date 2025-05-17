<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

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
        }

        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
            line-height: 1.6;
        }

        .header {
            background-color: #ff9042;
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 600;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
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
        }
    </style>
</head>
<body>
    <div class="header">
       
            <h1 class="text-center">Student Profile Form for Inventory</h1>
        </div>
    </div>
    <div class="container">
    <a href="student_homepage.php" class="modern-back-button">
            <i class="fas fa-arrow-left"></i>
            <span>Back</span>
        </a>
    <div class="text-center mt-4">
            </div>
        <div class="guidance-note">
            <h4>Need to make changes?</h4>
            <p>If you wish to update any information on this form, please visit the Guidance Office. Our staff will be happy to assist you with any necessary changes.</p>
        </div>

        <form>
            <h2>Personal Information</h2>
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" class="form-control readonly-input" id="full_name" value="<?php echo htmlspecialchars($profile['last_name'] . ', ' . $profile['first_name'] . ' ' . $profile['middle_name']); ?>" readonly>
            </div>
            <?php
            $personal_info = ['student_id', 'gender', 'birthdate', 'age', 'civil_status', 'province', 'city', 'contact_number', 'email'];
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
            $medical_info = ['medications', 'medical_conditions', 'suicide_attempt', 'suicide_reason', 'problems', 'fitness_activity', 'fitness_frequency', 'stress_level'];
            foreach ($medical_info as $key):
                if (isset($profile[$key])):
            ?>
                <div class="form-group">
                    <label for="<?php echo $key; ?>"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</label>
                    <?php if (in_array($key, ['suicide_reason', 'problems', 'medical_conditions'])): ?>
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
</body>
</html>