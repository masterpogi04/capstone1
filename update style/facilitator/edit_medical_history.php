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
        $conditions = isset($_POST['conditions']) ? implode(", ", $_POST['conditions']) : '';
        if (!empty($_POST['other_conditions'])) {
            $conditions .= ($conditions ? ", " : "") . "Other: " . $_POST['other_conditions'];
        }
        if (!empty($_POST['allergy'])) {
            $conditions .= ($conditions ? ", " : "") . "Allergy: " . $_POST['allergy'];
        }
        if (!empty($_POST['scoliosis'])) {
            $conditions .= ($conditions ? ", " : "") . "Scoliosis/Physical condition: " . $_POST['scoliosis'];
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
    $_SESSION['success_message'] = "Medical history updated successfully.";
    header("Location: view_student_profile.php?student_id=" . $student_id);
    exit();
} else {
    $_SESSION['error_message'] = "Error updating medical history.";
}
}

// Prepare data for form
$existing_conditions = explode(", ", $student['medical_conditions'] ?? '');
$existing_problems = array_map('trim', explode(";", $student['problems'] ?? ''));
$existing_family_problems = array_map('trim', explode(";", $student['family_problems'] ?? ''));

// Extract "Others" values
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
.condition-section {
    border: 1px solid #dee2e6;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
}

.condition-section .custom-control {
    margin-bottom: 10px;
}
</style>

<body>
    <div class="header">
        <h1>Edit Student Medical History</h1>
    </div>

    <div class="container mt-5">
        <form id="editMedicalHistoryForm" method="POST">
            <h5>Medical History Information</h5>
            
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
                   name="no_main_conditions" value="NO_MAIN_CONDITIONS"
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
                   value="<?php 
                       $other_condition = '';
                       foreach ($existing_conditions as $condition) {
                           if (strpos($condition, 'Other:') === 0) {
                               $other_condition = trim(substr($condition, 6));
                               break;
                           }
                       }
                       echo htmlspecialchars($other_condition);
                   ?>">
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
                   value="<?php 
                       $allergy = '';
                       foreach ($existing_conditions as $condition) {
                           if (strpos($condition, 'Allergy:') === 0) {
                               $allergy = trim(substr($condition, 8));
                               break;
                           }
                       }
                       echo htmlspecialchars($allergy);
                   ?>">
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
                   value="<?php 
                       $scoliosis = '';
                       foreach ($existing_conditions as $condition) {
                           if (strpos($condition, 'Scoliosis/Physical condition:') === 0) {
                               $scoliosis = trim(substr($condition, 29));
                               break;
                           }
                       }
                       echo htmlspecialchars($scoliosis);
                   ?>">
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
                
                <div class="custom-control custom-checkbox problem-option">
                    <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_others" 
                           name="problem[]" value="Others" <?php echo !empty($problem_others) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="problem_others">Others:</label>
                    <input type="text" class="form-control mt-2" id="problem_others_text" name="problem_others_text" 
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
                
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" 
                           id="fam_problem_others" name="fam-problem[]" value="Others"
                           <?php echo !empty($family_other_problem) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_problem_others">Others:</label>
                    <input type="text" class="form-control mt-2" id="fam_problem_others_text" 
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
                         class="img-fluid" style="max-width: 400px; border: 1px solid #000;">
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
        
        if (medicalConditions === 'NO MEDICAL CONDITIONS') {
            $('#no_main_conditions').prop('checked', true);
            $('.condition-section input').not('#no_main_conditions').prop('disabled', true);
            $('#no_other_conditions, #no_allergies, #no_scoliosis')
                .prop('checked', true)
                .prop('disabled', true);
            $('.condition-checkbox').prop('checked', false);
            $('#other_conditions, #allergy, #scoliosis').val('');
        } else {
            // Check each section individually
            $('.condition-section').each(function() {
                const $section = $(this);
                if (!checkSectionHasValue($section)) {
                    const $noCheckbox = $section.find('input[id^="no_"]');
                    $noCheckbox.prop('checked', true);
                    $section.find('input').not($noCheckbox).prop('disabled', true);
                }
            });
        }
    }

    // Original functionality for medications
    $('#no_medications').change(function() {
        if (this.checked) {
            $('#medications').val('NO MEDICATIONS').prop('readonly', true);
        } else {
            $('#medications').val('').prop('readonly', false);
        }
    });

    // Original functionality for suicide question
    $('input[name="suicide"]').change(function() {
        if (this.value === 'yes') {
            $('#suicide_reason_container').show();
        } else {
            $('#suicide_reason_container').hide();
            $('#suicide_reason').val('');
        }
    });

    // Original functionality for fitness question
    $('input[name="fitness"]').change(function() {
        if (this.value === 'yes') {
            $('#fitness_specify').prop('disabled', false);
            $('input[name="fitness_frequency"]').prop('disabled', false);
        } else {
            $('#fitness_specify').val('').prop('disabled', true);
            $('input[name="fitness_frequency"]').prop('checked', false).prop('disabled', true);
        }
    });

    // Original functionality for problems and family problems
    $('#problem_others').change(function() {
        $('#problem_others_text').prop('disabled', !this.checked);
        if (!this.checked) $('#problem_others_text').val('');
    });

    $('#fam_problem_others').change(function() {
        $('#fam_problem_others_text').prop('disabled', !this.checked);
        if (!this.checked) $('#fam_problem_others_text').val('');
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
    if (!$('#problem_others').is(':checked')) {
        $('#problem_others_text').prop('disabled', true);
    }
    if (!$('#fam_problem_others').is(':checked')) {
        $('#fam_problem_others_text').prop('disabled', true);
    }
});
</script>
</body>
</html>