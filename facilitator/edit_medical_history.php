<?php
session_start();
include '../db.php';

if (isset($_SESSION['success_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Success!',
                text: '" . $_SESSION['success_message'] . "',
                icon: 'success',
                confirmButtonColor: '#3085d6'
            });
        });
    </script>";
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Error!',
                text: '" . $_SESSION['error_message'] . "',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
        });
    </script>";
    unset($_SESSION['error_message']);
}

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    header("Location: view_profiles.php");
    exit();
}

// Fetch student data from the database
$stmt = $connection->prepare("
    SELECT sp.*
    FROM student_profiles sp
    WHERE sp.student_id = ?
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process form submission
    $medications = $_POST['medications'] ?? 'NO MEDICATIONS';
    
    // Process conditions
    if (isset($_POST['no_conditions']) && $_POST['no_conditions'] == 'NO CONDITIONS') {
        $conditions = 'NO MEDICAL CONDITIONS';
    } else {
        // Start with the main medical conditions
        $conditions = isset($_POST['conditions']) ? implode(", ", $_POST['conditions']) : '';
        
        // Add other conditions if specified
        if (!empty($_POST['other_conditions'])) {
            $conditions .= ($conditions ? ", " : "") . "Other: " . $_POST['other_conditions'];
        }
        
        // Add allergy if specified
        if (!empty($_POST['allergy'])) {
            $conditions .= ($conditions ? ", " : "") . "Allergy: " . $_POST['allergy'];
        }
        
        // Add scoliosis if specified
        if (!empty($_POST['scoliosis'])) {
            $conditions .= ($conditions ? ", " : "") . "Scoliosis/Physical condition: " . $_POST['scoliosis'];
        }
        
        // If no conditions were specified, set default value
        if (empty($conditions)) {
            $conditions = 'NO MEDICAL CONDITIONS';
        }
    }

    $suicide_attempt = $_POST['suicide'];
    $suicide_reason = $suicide_attempt === 'yes' ? $_POST['suicide_reason'] : '';
    
    // Process problems
    if (isset($_POST['no_problems']) && $_POST['no_problems'] == 'NO PROBLEMS') {
        $problems = 'NO PROBLEMS';
    } else {
        $problems = isset($_POST['problem']) ? implode("; ", $_POST['problem']) : '';
        if (isset($_POST['problem_others_text']) && !empty($_POST['problem_others_text'])) {
            $problems .= ($problems ? "; " : "") . "Others: " . $_POST['problem_others_text'];
        }
    }

    // Process family problems
    if (isset($_POST['fam_no_problems']) && $_POST['fam_no_problems'] == 'No problems') {
        $family_problems = 'NO PROBLEMS';
    } else {
        $family_problems = isset($_POST['fam-problem']) ? implode("; ", $_POST['fam-problem']) : '';
        if (isset($_POST['fam_problem_others_text']) && !empty($_POST['fam_problem_others_text'])) {
            $family_problems = str_replace('Others', 'Others: ' . $_POST['fam_problem_others_text'], $family_problems);
        }
    }

    $fitness_activity = $_POST['fitness'] === 'yes' ? $_POST['fitness_specify'] : 'NO FITNESS';
    $fitness_frequency = $_POST['fitness'] === 'yes' ? $_POST['fitness_frequency'] : '';
    $stress_level = $_POST['stress'];

    // Update the database
    $update_stmt = $connection->prepare("
        UPDATE student_profiles SET
        medications = ?, medical_conditions = ?, suicide_attempt = ?, suicide_reason = ?,
        problems = ?, family_problems = ?, fitness_activity = ?, fitness_frequency = ?, stress_level = ?
        WHERE student_id = ?
    ");
    $update_stmt->bind_param("ssssssssss", 
        $medications, $conditions, $suicide_attempt, $suicide_reason,
        $problems, $family_problems, $fitness_activity, $fitness_frequency, $stress_level, $student_id
    );

    if ($update_stmt->execute()) {
        header("Location: view_student_profile.php?student_id=" . $student_id);
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating medical history.";
    }
}

// Prepare data for form
$existing_conditions = explode("; ", $student['medical_conditions'] ?? '');

// Extract specific condition values
$other_condition = '';
$allergy = '';
$scoliosis = '';

foreach ($existing_conditions as $key => $condition) {
    if (strpos($condition, 'Other:') === 0) {
        $other_condition = trim(substr($condition, 7));
        unset($existing_conditions[$key]);
    } else if (strpos($condition, 'Allergy:') === 0) {
        $allergy = trim(substr($condition, 8));
        unset($existing_conditions[$key]);
    } else if (strpos($condition, 'Scoliosis/Physical condition:') === 0) {
        $scoliosis = trim(substr($condition, 29));
        unset($existing_conditions[$key]);
    }
}

$existing_problems = array_map('trim', explode(";", $student['problems'] ?? ''));
$existing_family_problems = array_map('trim', explode(";", $student['family_problems'] ?? ''));

// Extract "Others" values for problems
$problem_others = '';
$family_other_problem = '';

foreach ($existing_problems as $key => $problem) {
    if (strpos($problem, 'Others:') === 0) {
        $problem_others = trim(substr($problem, 7));
        unset($existing_problems[$key]);
    }
}

foreach ($existing_family_problems as $key => $problem) {
    if (strpos($problem, 'Others:') === 0) {
        $family_other_problem = trim(substr($problem, 7));
        unset($existing_family_problems[$key]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Medical History</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="edit_student_profile_form.css">
</head>

<style>
:root {
  --primary: #2D4059;
  --primary-light: #3D5A80;
  --secondary: #EA5455;
  --accent: #1abc9c;
  --background: #f8fafc;
  --surface: #ffffff;
  --text: black;
  --text-light: #666666;
  --border: #e2e8f0;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

body {
  background: linear-gradient(135deg, #0d693e 0%, #004d4d 100%);
  min-height: 100vh;
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--text);
  line-height: 1.6;
}

.content {
  max-width: 1200px;
  margin: 2rem auto;
  padding: 2rem;
  background: white;
  border-radius: 16px;
  box-shadow: var(--shadow-lg);
}

.content h2 {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 2.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--accent);
  position: relative;
}

.section {
  background: var(--background);
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  border: 1px solid var(--border);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    color: black;
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 8px;
    width: 100%;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 2px rgba(240, 123, 63, 0.1);
    outline: none;
}

.form-control[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.form-check {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  font-size: 0.95rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.btn-primary {
  background: #2EDAA8;
  color: white;
}

.btn-primary:hover {
  background: #28C498;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f43f5e;
  color: white;
}

.btn-secondary:hover {
  background: #e11d48;
  transform: translateY(-1px);
}

.info-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.info-card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  color: var(--primary);
}

.info-card-header i {
  font-size: 1.25rem;
  color: var(--accent);
}

.field-required::after {
  content: "*";
  color: var(--secondary);
  margin-left: 0.25rem;
}

.error-feedback {
  color: var(--secondary);
  font-size: 0.875rem;
  margin-top: 0.375rem;
}

.success-feedback {
  background: #dcfce7;
  color: #166534;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

#spouseInfoFields,
#otherReligionField {
  background: var(--background);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.5rem;
  margin-top: 1rem;
  transition: all 0.3s ease;
}

@media (max-width: 768px) {
  .content {
    margin: 1rem;
    padding: 1.5rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
    margin: 0.5rem 0;
  }
}

.modern-back-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #2EDAA8;
  color: white;
  padding: 0.75rem 1.25rem;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 500;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(46, 218, 168, 0.15);
  transition: all 0.2s ease;
}

.modern-back-button:hover {
  background: #28C498;
  transform: translateY(-1px);
  box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
}

.modern-back-button i {
  font-size: 1.125rem;
}

select.form-control {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M8 12L2 6h12z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  padding-right: 2.5rem;
}
 
.floating-save {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  display: flex;
  gap: 1rem;
  z-index: 100;
}

@media (prefers-color-scheme: dark) {
  :root {
    --background: #1a1a1a;
    --surface: #2d2d2d;
    --text: #ffffff;
    --text-light: #a3a3a3;
    --border: #404040;
  }
}

.animate-fade {
  animation: fadeIn 0.3s ease-in-out;
} 

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

    .header {
    background-color: white;
    color: #1b651b;
    padding: 20px;
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    width: 100%;
}
</style>

<body>
    <div class="header">
        <h1>Edit Student Form</h1>
    </div>

    <div class="content">
        <form id="editMedicalHistoryForm" method="POST">
            <h2>Medical History Information</h2>
            
            <div class="form-group">
                <label for="medications">List any medications you are taking:</label>
                <input type="text" class="form-control" id="medications" name="medications" 
                       value="<?php echo htmlspecialchars($student['medications'] ?? ''); ?>">
            </div>
            <div class="form-group custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="no_medications" name="no_medications" 
                       <?php echo ($student['medications'] == 'NO MEDICATIONS') ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="no_medications">No, I don't take any medications</label>
            </div>

            <div class="form-group">
    <p>Do you have any of the following? Kindly check all that apply:</p>
    
    <!-- Main Medical Conditions Section -->
    <div class="condition-section"> 
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_main_conditions" 
                   name="no_conditions" value="NO CONDITIONS"
                   <?php echo ($student['medical_conditions'] == 'NO MEDICAL CONDITIONS') ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="no_main_conditions">
                No, I don't have any of these medical conditions
            </label>
        </div>
        
        <?php
        $conditions = ['Asthma', 'Hypertension', 'Diabetes', 'Insomnia', 'Vertigo'];
        foreach ($conditions as $condition) {
            $isChecked = in_array($condition, $existing_conditions) ? 'checked' : '';
            echo "<div class='custom-control custom-checkbox condition-option main-condition'>
                    <input type='checkbox' class='custom-control-input condition-checkbox' 
                           id='$condition' name='conditions[]' value='$condition' $isChecked>
                    <label class='custom-control-label' for='$condition'>$condition</label>
                  </div>";
        }
        ?>
    </div>

    <!-- Other Medical Conditions Section -->
    <div class="condition-section mt-3">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_other_conditions" 
                   name="no_other_conditions" value="NO_OTHER_CONDITIONS">
            <label class="custom-control-label" for="no_other_conditions">
                No other medical conditions
            </label>
        </div>
        <div class="form-group other-condition">
            <label for="other_conditions">Other medical condition, please specify:</label>
            <input type="text" class="form-control" id="other_conditions" name="other_conditions" 
                   value="<?php echo htmlspecialchars($other_condition); ?>">
        </div>
    </div>

    <!-- Allergies Section -->
    <div class="condition-section mt-3">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_allergies" 
                   name="no_allergies" value="NO_ALLERGIES">
            <label class="custom-control-label" for="no_allergies">
                No allergies
            </label>
        </div>
        <div class="form-group allergy-condition">
            <label for="allergy">Allergy - specifically, allergic to:</label>
            <input type="text" class="form-control" id="allergy" name="allergy" 
                   value="<?php echo htmlspecialchars($allergy); ?>">
        </div>
    </div>

    <!-- Scoliosis Section -->
    <div class="condition-section mt-3">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_scoliosis" 
                   name="no_scoliosis" value="NO_SCOLIOSIS">
            <label class="custom-control-label" for="no_scoliosis">
                No scoliosis or physical condition
            </label>
        </div>
        <div class="form-group scoliosis-condition">
            <label for="scoliosis">Scoliosis or physical condition, specify:</label>
            <input type="text" class="form-control" id="scoliosis" name="scoliosis" 
                   value="<?php echo htmlspecialchars($scoliosis); ?>">
        </div>
    </div>
</div>

            <div class="form-group">
                <label>Have you ever seriously considered or attempted suicide?</label><br>
                <div class="custom-control custom-radio">
                    <input type="radio" id="suicide_no" name="suicide" class="custom-control-input" value="no" 
                           <?php echo ($student['suicide_attempt'] ?? '') != 'yes' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="suicide_no">No</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="suicide_yes" name="suicide" class="custom-control-input" value="yes" 
                           <?php echo ($student['suicide_attempt'] ?? '') == 'yes' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="suicide_yes">Yes</label>
                </div>
                <div id="suicide_reason_container" style="display: <?php echo ($student['suicide_attempt'] ?? '') == 'yes' ? 'block' : 'none'; ?>;">
                    <label for="suicide_reason">Please explain:</label>
                    <input type="text" class="form-control mt-2" id="suicide_reason" name="suicide_reason" 
                           value="<?php echo htmlspecialchars($student['suicide_reason'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Have you ever had a problem with?</label><br>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="no_problems" name="no_problems" 
                           value="NO PROBLEMS" <?php echo ($student['problems'] == 'NO PROBLEMS') ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="no_problems">No, I don't have any problems</label>
                </div> 
                
                <?php
                $problems = ['Alcohol/Substance Abuse', 'Eating Disorder', 'Depression', 'Aggression'];
                foreach ($problems as $problem) {
                    $isChecked = in_array($problem, $existing_problems) ? 'checked' : '';
                    $id = str_replace(['/', ' '], '', $problem);
                    echo "<div class='custom-control custom-checkbox problem-option'>
                            <input type='checkbox' class='custom-control-input problem-checkbox' 
                                   id='$id' name='problem[]' value='$problem' $isChecked>
                            <label class='custom-control-label' for='$id'>$problem</label>
                          </div>";
                }
                ?>
                
                <!-- For personal problems - MODIFY THIS SECTION -->
                <div class="form-group mt-2">
                    <label for="problem_others_text">Others, please specify:</label>
                    <input type="text" class="form-control" id="problem_others_text" name="problem_others_text" 
                           value="<?php echo htmlspecialchars($problem_others); ?>">
                </div>
            </div>

            <!-- Family Problems Section -->
            <div class="form-group">
                <label>Have any member of your immediate family member had a problem with: </label><br>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="fam_no_problems" 
                           name="fam_no_problems" value="No problems" 
                           <?php echo ($student['family_problems'] == 'NO PROBLEMS') ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_no_problems">No, they don't have any problems</label>
                </div>
                
                <?php
                $family_problems = [
                    'Alcohol/Substance Abuse',
                    'Eating Disorder',
                    'Depression',
                    'Aggression'
                ];
                
                foreach ($family_problems as $problem) {
                    $isChecked = in_array($problem, $existing_family_problems) ? 'checked' : '';
                    $id = 'fam_' . str_replace(['/', ' '], '_', strtolower($problem));
                    echo "<div class='custom-control custom-checkbox'>
                            <input type='checkbox' class='custom-control-input fam-problem-checkbox' 
                                   id='$id' name='fam-problem[]' value='$problem' $isChecked>
                            <label class='custom-control-label' for='$id'>$problem</label>
                          </div>";
                }
                ?>
                
                <!-- For family problems - MODIFY THIS SECTION -->
                <div class="form-group mt-2">
                    <label for="fam_problem_others_text">Others, please specify:</label>
                    <input type="text" class="form-control" id="fam_problem_others_text" 
                           name="fam_problem_others_text" 
                           value="<?php echo htmlspecialchars($family_other_problem); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Do you engage in physical fitness activity?</label>
                <div class="custom-control custom-radio">
                    <input type="radio" id="fitness_no" name="fitness" class="custom-control-input" value="no" 
                           <?php echo ($student['fitness_activity'] == 'NO FITNESS') ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fitness_no">No</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="fitness_yes" name="fitness" class="custom-control-input" value="yes" 
                           <?php echo ($student['fitness_activity'] != 'NO FITNESS') ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fitness_yes">Yes, Specify:</label>
                    <input type="text" class="form-control mt-2" id="fitness_specify" name="fitness_specify" 
                           value="<?php echo ($student['fitness_activity'] != 'NO FITNESS') ? htmlspecialchars($student['fitness_activity']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>If yes, how often:</label>
                <div class="custom-control custom-radio">
                    <input type="radio" id="everyday" name="fitness_frequency" class="custom-control-input" 
                           value="Everyday" <?php echo ($student['fitness_frequency'] ?? '') == 'Everyday' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="everyday">Everyday</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="2-3_week" name="fitness_frequency" class="custom-control-input" 
                           value="2-3 Week" <?php echo ($student['fitness_frequency'] ?? '') == '2-3 Week' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="2-3_week">2-3 times a week</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="2-3_month" name="fitness_frequency" class="custom-control-input" 
                           value="2-3 Month" <?php echo ($student['fitness_frequency'] ?? '') == '2-3 Month' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="2-3_month">2-3 times a month</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>How would you rate your current level of stress, 10 as highest & 1 as lowest:</label>
                <div class="custom-control custom-radio">
                    <input type="radio" id="low" name="stress" class="custom-control-input" 
                           value="low" <?php echo ($student['stress_level'] ?? '') == 'low' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="low">Low (1-3)</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="average" name="stress" class="custom-control-input" 
                           value="average" <?php echo ($student['stress_level'] ?? '') == 'average' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="average">Average (4-7)</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="high" name="stress" class="custom-control-input" 
                           value="high" <?php echo ($student['stress_level'] ?? '') == 'high' ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="high">High (8-10)</label>
                </div>
            </div>

            <div class="form-group">
                <label>Student Signature:</label>
                <?php if (!empty($student['signature_path'])): ?>
                    <img src="<?php echo htmlspecialchars($student['signature_path']); ?>" alt="Student Signature" 
                         class="img-fluid" style="border: 1px solid #000;">
                <?php else: ?>
                    <p>No signature available</p>
                <?php endif; ?>
                <p class="text-muted">Signature cannot be changed here. Students can only sign this once.</p>
            </div>

        <div class="form-group d-flex justify-content-between">
            <div>
                <a href="edit_educational_career.php?student_id=<?php echo $student_id; ?>" class="btn btn-secondary">Back</a>
            </div>
            <div>
                <button type="submit" class="btn btn-primary mr-2">Save and Finish</button>
                <a href="view_student_profile.php?student_id=<?php echo $student_id; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> View Student Profile
                </a>
            </div>
        </div>
        </form>
    </div>

    <script>
$(document).ready(function() {

    // Function to check if a section has any values
    function checkSectionHasValue(section) {
        const hasCheckboxValue = section.find('input[type="checkbox"]').not('[id^="no_"]').is(':checked');
        const hasTextValue = section.find('input[type="text"]').filter(function() {
            return $(this).val().trim() !== '';
        }).length > 0;
        return hasCheckboxValue || hasTextValue;
    }

    // Function to initialize medical conditions based on database values
    function initializeMedicalConditions() {
    const medicalConditions = '<?php echo $student['medical_conditions']; ?>';
    console.log("Medical conditions from DB:", medicalConditions);
    
    if (medicalConditions === 'NO MEDICAL CONDITIONS') {
        $('#no_main_conditions').prop('checked', true);
        $('.condition-section input').not('#no_main_conditions').prop('disabled', true);
        $('#no_other_conditions, #no_allergies, #no_scoliosis')
            .prop('checked', true)
            .prop('disabled', true);
        $('.condition-checkbox').prop('checked', false);
        $('#other_conditions, #allergy, #scoliosis').val('');
    } else {
        // Split the conditions by semicolon
        const conditionArray = medicalConditions.split('; ');
        
        // Check for each main condition (Asthma, Hypertension, etc.)
        $('#Asthma, #Hypertension, #Diabetes, #Insomnia, #Vertigo').each(function() {
            const conditionName = $(this).val();
            if (conditionArray.includes(conditionName)) {
                $(this).prop('checked', true);
                $('#no_main_conditions').prop('checked', false);
            }
        });
        
        // Check for other condition types
        let hasOther = false;
        let hasAllergy = false;
        let hasScoliosis = false;
        
        for (const condition of conditionArray) {
            if (condition.startsWith('Other:')) {
                $('#other_conditions').val(condition.substring(7).trim());
                hasOther = true;
            } else if (condition.startsWith('Allergy:')) {
                $('#allergy').val(condition.substring(8).trim());
                hasAllergy = true;
            } else if (condition.startsWith('Scoliosis/Physical condition:')) {
                $('#scoliosis').val(condition.substring(29).trim());
                hasScoliosis = true;
            }
        }
        
        // Set the "No" checkboxes accordingly
        $('#no_other_conditions').prop('checked', !hasOther);
        $('#no_allergies').prop('checked', !hasAllergy);
        $('#no_scoliosis').prop('checked', !hasScoliosis);
        
        // Disable the appropriate input fields
        if (!hasOther) $('#other_conditions').prop('disabled', true);
        if (!hasAllergy) $('#allergy').prop('disabled', true);
        if (!hasScoliosis) $('#scoliosis').prop('disabled', true);
    }
}

    // Original functionality for medications
    $('#no_medications').change(function() {
        if(this.checked) {
            $('#medications').val('NO MEDICATIONS').prop('readonly', true);
        } else {
            $('#medications').val('').prop('readonly', false);
        }
    });

    // Original functionality for suicide question
    $('input[name="suicide"]').change(function() {
        if(this.value === 'yes') {
            $('#suicide_reason_container').show();
        } else {
            $('#suicide_reason_container').hide();
            $('#suicide_reason').val('');
        }
    });

    // Original functionality for fitness question
    $('input[name="fitness"]').change(function() {
        if(this.value === 'yes') {
            $('#fitness_specify').prop('disabled', false);
            $('input[name="fitness_frequency"]').prop('disabled', false);
        } else {
            $('#fitness_specify').val('').prop('disabled', true);
            $('input[name="fitness_frequency"]').prop('checked', false).prop('disabled', true);
        }
    });

    // Handle personal problems "Others" text field - Auto-add to problem[] when filled
    $('#problem_others_text').on('input', function() {
        if (this.value.trim() !== '') {
            // Add a hidden input for "Others" if it doesn't exist
            if ($('input[name="problem[]"][value="Others"]').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'problem[]',
                    value: 'Others'
                }).appendTo('#editMedicalHistoryForm');
            }
            // Uncheck "No problems" if it's checked
            $('#no_problems').prop('checked', false);
        } else {
            // Remove the hidden input if the text field is empty
            $('input[name="problem[]"][value="Others"]').remove();
        }
    });

    // Handle family problems "Others" text field - Auto-add to fam-problem[] when filled
    $('#fam_problem_others_text').on('input', function() {
        if (this.value.trim() !== '') {
            // Add a hidden input for "Others" if it doesn't exist
            if ($('input[name="fam-problem[]"][value="Others"]').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'fam-problem[]',
                    value: 'Others'
                }).appendTo('#editMedicalHistoryForm');
            }
            // Uncheck "No problems" if it's checked
            $('#fam_no_problems').prop('checked', false);
        } else {
            // Remove the hidden input if the text field is empty
            $('input[name="fam-problem[]"][value="Others"]').remove();
        }
    });

    // Handle no checkboxes for problems and family problems
    function handleNonMedicalNoCheckbox(noCheckboxId, targetInputs) {
        const $noCheckbox = $(noCheckboxId);
        const $section = $noCheckbox.closest('.form-group');
        
        function toggleInputs() {
            const $inputs = $section.find(targetInputs);
            if ($noCheckbox.is(':checked')) {
                $inputs.prop('checked', false).prop('disabled', true);
                $inputs.filter('input[type="text"]').val('');
                
                // Remove any hidden inputs for "Others"
                if (noCheckboxId === '#no_problems') {
                    $('input[name="problem[]"][value="Others"]').remove();
                } else if (noCheckboxId === '#fam_no_problems') {
                    $('input[name="fam-problem[]"][value="Others"]').remove();
                }
            } else {
                $inputs.prop('disabled', false);
            }
        }

        $noCheckbox.on('change', toggleInputs);
        
        $section.find(targetInputs).on('change input', function() {
            const hasValue = $(this).is(':checkbox') ? 
                $(this).is(':checked') : 
                $(this).val().trim() !== '';
            if (hasValue) {
                $noCheckbox.prop('checked', false);
            }
        });

        if ($noCheckbox.is(':checked')) {
            toggleInputs();
        }
    }

    // Initialize non-medical no checkboxes
    handleNonMedicalNoCheckbox('#no_problems', '.problem-checkbox, #problem_others_text');
    handleNonMedicalNoCheckbox('#fam_no_problems', '.fam-problem-checkbox, #fam_problem_others_text');

    // Monitor changes in medical condition sections
    $('.condition-section').each(function() {
        const $section = $(this);
        const $noCheckbox = $section.find('input[id^="no_"]');
        
        $section.find('input').not($noCheckbox).on('change input', function() {
            if (!checkSectionHasValue($section)) {
                $noCheckbox.prop('checked', true);
                $section.find('input').not($noCheckbox).prop('disabled', true);
            }
        });
    });

    // Initialize form based on database values
    initializeMedicalConditions();

    // Original form validation code
    $('#editMedicalHistoryForm').on('submit', function(e) {
        e.preventDefault();
        var isValid = true;

        // Validate each section
        function validateSection(noCheckboxId, inputs, message) {
            const $noCheckbox = $(noCheckboxId);
            const hasValue = $(inputs).filter(function() {
                return $(this).is(':checkbox') ? 
                    $(this).is(':checked') : 
                    $(this).val().trim() !== '';
            }).length > 0;

            if (!hasValue && !$noCheckbox.is(':checked')) {
                Swal.fire({
                    title: 'Error!',
                    text: message,
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            return true;
        }

        // Validate medications
        if (!validateSection('#no_medications', '#medications', 
            'Please either list medications or check "No medications"')) {
            return false;
        }

        // Validate all medical conditions sections
        $('.condition-section').each(function() {
            const $section = $(this);
            const $noCheckbox = $section.find('input[id^="no_"]');
            const $inputs = $section.find('input').not($noCheckbox);
            const sectionName = $section.find('label').first().text().trim();
            
            if (!validateSection('#' + $noCheckbox.attr('id'), $inputs,
                `Please either select a condition or check "No" for ${sectionName}`)) {
                isValid = false;
                return false;
            }
        });

        if (!isValid) return false;

        // Additional validations
        if (!$('input[name="suicide"]:checked').val()) {
            Swal.fire({
                title: 'Error!',
                text: 'Please answer the question about suicide attempts',
                icon: 'error'
            });
            return false;
        }

        if ($('input[name="suicide"]:checked').val() === 'yes' && 
            $('#suicide_reason').val().trim() === '') {
            Swal.fire({
                title: 'Error!',
                text: 'Please provide a reason for the suicide attempt',
                icon: 'error'
            });
            return false;
        }

        // Validate fitness activity
        if (!$('input[name="fitness"]:checked').val()) {
            Swal.fire({
                title: 'Error!',
                text: 'Please answer the question about physical fitness activity',
                icon: 'error'
            });
            return false;
        }

        if ($('input[name="fitness"]:checked').val() === 'yes') {
            if ($('#fitness_specify').val().trim() === '') {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please specify the physical fitness activity',
                    icon: 'error'
                });
                return false;
            }
            if (!$('input[name="fitness_frequency"]:checked').val()) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please select the frequency of physical fitness activity',
                    icon: 'error'
                });
                return false;
            }
        }

        // Validate stress level
        if (!$('input[name="stress"]:checked').val()) {
            Swal.fire({
                title: 'Error!',
                text: 'Please select your current stress level',
                icon: 'error'
            });
            return false;
        }

        // If all validations pass, show confirmation
        if (isValid) {
            Swal.fire({
                title: 'Save Changes?',
                text: 'Are you sure you want to save these changes to the medical history?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save changes',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Saving...',
                        text: 'Please wait while we save your changes.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    this.submit();
                }
            });
        }
    });

    // Set initial states
    if ($('#no_medications').is(':checked')) {
        $('#medications').prop('readonly', true);
    }
    if ($('input[name="suicide"]:checked').val() !== 'yes') {
        $('#suicide_reason_container').hide();
    }
    if ($('input[name="fitness"]:checked').val() !== 'yes') {
        $('#fitness_specify').prop('disabled', true);
        $('input[name="fitness_frequency"]').prop('disabled', true);
    }
    
    // Initialize "Others" fields - add hidden inputs if text fields have values
    if ($('#problem_others_text').val().trim() !== '') {
        $('<input>').attr({
            type: 'hidden',
            name: 'problem[]',
            value: 'Others'
        }).appendTo('#editMedicalHistoryForm');
    }
    
    if ($('#fam_problem_others_text').val().trim() !== '') {
        $('<input>').attr({
            type: 'hidden',
            name: 'fam-problem[]',
            value: 'Others'
        }).appendTo('#editMedicalHistoryForm');
    }
    
    // Only disable the problem others text field if "No problems" is checked
    if ($('#no_problems').is(':checked')) {
        $('#problem_others_text').prop('disabled', true);
    }
    
    // Only disable the family problem others text field if "No problems" is checked
    if ($('#fam_no_problems').is(':checked')) {
        $('#fam_problem_others_text').prop('disabled', true);
    }

    $('#no_scoliosis, #no_allergies, #no_other_conditions').on('change', function() {
        const $section = $(this).closest('.condition-section');
        const $input = $section.find('input[type="text"]');
        
        if ($(this).is(':checked')) {
            // If "No" is checked, disable and clear the text input
            $input.prop('disabled', true).val('');
        } else {
            // If "No" is unchecked, enable the text input
            $input.prop('disabled', false);
        }
    });

    // Initialize the individual sections
    $('#no_scoliosis, #no_allergies, #no_other_conditions').each(function() {
        if ($(this).is(':checked')) {
            const $section = $(this).closest('.condition-section');
            $section.find('input[type="text"]').prop('disabled', true).val('');
        }
    });

    // Main "No medical conditions" checkbox handler
    $('#no_main_conditions').on('change', function() {
        if ($(this).is(':checked')) {
            // ONLY target the specific 5 condition checkboxes by ID
            $('#Asthma, #Hypertension, #Diabetes, #Insomnia, #Vertigo').prop('checked', false).prop('disabled', true);
        } else {
            // Re-enable ONLY the 5 specific checkboxes by ID
            $('#Asthma, #Hypertension, #Diabetes, #Insomnia, #Vertigo').prop('disabled', false);
        }
    });

    // Main conditions checkboxes handler
    $('.main-condition input').on('change', function() {
        if ($('.main-condition input:checked').length > 0) {
            $('#no_main_conditions').prop('checked', false);
        }
    });
});
</script>
</body>
</html>