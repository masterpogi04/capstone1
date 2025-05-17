<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Function to get session value or default
function getSessionValue($key, $default = '') {
    return isset($_SESSION['medical_history'][$key]) ? $_SESSION['medical_history'][$key] : $default;
}

// Add this near the top of your PHP code, in the getSessionValue function or after it
function getSignaturePath() {
    // Check if signature path exists in student profile session
    if (isset($_SESSION['student_profile']['signature_path'])) {
        return $_SESSION['student_profile']['signature_path'];
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store form data in session
    $_SESSION['medical_history'] = [
        'medications' => $_POST['medications'] ?? '',
        'no_medications' => isset($_POST['no_medications']),
        'conditions' => $_POST['conditions'] ?? '',
        'no_medical_list' => isset($_POST['no_medical_list']),
        'allergy' => $_POST['allergy'] ?? '',
        'no_allergies' => isset($_POST['no_allergies']),
        'scoliosis' => $_POST['scoliosis'] ?? '',
        'no_physical_conditions' => isset($_POST['no_physical_conditions']),
        'suicide' => $_POST['suicide'] ?? '',
        'suicide_reason' => $_POST['suicide_reason'] ?? '',
        'problems' => $_POST['problems'] ?? '',
        'no_problems' => isset($_POST['no_problems']),
        'fam_problems' => $_POST['fam-problems'] ?? '',
        'fam_no_problems' => isset($_POST['fam_no_problems']),
        'fitness' => $_POST['fitness'] ?? '',
        'fitness_specify' => $_POST['fitness_specify'] ?? '',
        'fitness_frequency' => $_POST['fitness_frequency'] ?? '',
        'stress' => $_POST['stress'] ?? '',
        'signature' => $_POST['signature'] ?? ''
    ];

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // Just save to session and return success
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_data = $_POST;

    // Handle signature only if a new one is provided
    if (isset($_POST['signature']) && !empty($_POST['signature']) && 
        strpos($_POST['signature'], 'data:image/png;base64,') === 0) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/capstone1/student/uploads/student_signatures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $signature_filename = 'signature_' . uniqid() . '.png';
        $signature_path = $uploadDir . $signature_filename;
        $signature_data = $_POST['signature'];
        $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
        $signature_data = base64_decode($signature_data);

        if (file_put_contents($signature_path, $signature_data) !== false) {
            // Remove old signature file if exists
            if (isset($_SESSION['student_profile']['signature_path'])) {
                $old_signature_path = $_SERVER['DOCUMENT_ROOT'] . $_SESSION['student_profile']['signature_path'];
                if (file_exists($old_signature_path)) {
                    unlink($old_signature_path);
                }
            }
            $student_data['signature_path'] = '/capstone1/student/uploads/student_signatures/' . $signature_filename;
            $_SESSION['student_profile']['signature_path'] = $student_data['signature_path'];
        }
    } else {
        // No new signature provided, retain the existing one
        $student_data['signature_path'] = isset($_SESSION['student_profile']['signature_path']) ? 
            $_SESSION['student_profile']['signature_path'] : '';
    }

    // Handle fitness activity and frequency
    if ($student_data['fitness'] === 'no') {
        $student_data['fitness_activity'] = 'NO FITNESS';
        $student_data['fitness_frequency'] = null;
    } else {
        $student_data['fitness_activity'] = $student_data['fitness_specify'];
        $student_data['fitness_frequency'] = $student_data['fitness_frequency'];
    }

     if (!isset($student_data['problems']) || empty($student_data['problems'])) {
        $student_data['problems'] = 'NO PROBLEMS';
    }

    // Ensure family_problems is set
    if (!isset($student_data['fam-problems']) || empty($student_data['fam-problems'])) {
        $student_data['family_problems'] = 'NO PROBLEMS';
    } else {
        $student_data['family_problems'] = $student_data['fam-problems'];
    }

 
    // Prepare and execute SQL update
    $sql = "UPDATE student_profiles SET 
        medications = ?, 
        medical_conditions = ?, 
        suicide_attempt = ?, 
        suicide_reason = ?, 
        problems = ?,
        family_problems = ?,
        fitness_activity = ?, 
        fitness_frequency = ?, 
        stress_level = ?, 
        signature_path = ?
    WHERE student_id = ?";

    // Prepare the statement
    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $connection->error);
        die("Prepare failed: " . $connection->error);
    }

    // Bind parameters
    $stmt->bind_param("sssssssssss",
        $student_data['medications'],
        $student_data['conditions'],
        $student_data['suicide'],
        $student_data['suicide_reason'],
        $student_data['problems'],
        $student_data['fam-problems'],
        $student_data['fitness_activity'],
        $student_data['fitness_frequency'],
        $student_data['stress'],
        $student_data['signature_path'],
        $_SESSION['student_profile']['student_id']
    );

    // Execute the statement
    if ($stmt->execute()) {
        error_log("Data updated successfully for student ID: " . $_SESSION['student_profile']['student_id']);
        $_SESSION['student_profile'] = array_merge($_SESSION['student_profile'], $student_data);
        header("Location: review.php");
        exit;
    } else {
        error_log("Execute failed: " . $stmt->error);
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile Inventory - Medical History</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
     <link rel="stylesheet" type="text/css" href="student_profile_form.css">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    
</head>
<body>
<div class="header">
        <h1>Student Profile Form for Inventory</h1>
    </div>

    <div class="container mt-5">
        <div class="progress mb-3">
            <div class="progress-bar" role="progressbar" style="width: 80%;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">80%</div>
        </div>

        <form method="POST" action="" id="medicalHistoryForm">
            <div class="form-section" id="section5">
                <h5>Medical History Information</h5>

               <div class="form-group">
                <label for="medications">List any medications you are taking:</label>
                <input type="text" class="form-control" id="medications" name="medications" value="<?php echo getSessionValue('medications'); ?>">
            </div>
            <div class="form-group custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="no_medications" name="no_medications" <?php echo getSessionValue('no_medications') ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="no_medications">No, I don't take any medications</label>
            </div>

            <p>Do you have any of the following? Kindly check all that apply:</p>
<div class="form-group">
    <div class="medical-conditions-group mb-4">
        <label><strong>Medical Conditions:</strong></label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_medical_list" name="no_medical_list" <?php echo getSessionValue('no_medical_list') ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="no_medical_list">No, I don't have any of the following conditions</label>
        </div>
        <div class="condition-options mt-2">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="asthma" name="conditions[]" value="Asthma" <?php echo strpos(getSessionValue('conditions'), 'Asthma') !== false ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="asthma">Asthma</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="hypertension" name="conditions[]" value="Hypertension" <?php echo strpos(getSessionValue('conditions'), 'Hypertension') !== false ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="hypertension">Hypertension</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="diabetes" name="conditions[]" value="Diabetes" <?php echo strpos(getSessionValue('conditions'), 'Diabetes') !== false ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="diabetes">Diabetes</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="insomnia" name="conditions[]" value="Insomnia" <?php echo strpos(getSessionValue('conditions'), 'Insomnia') !== false ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="insomnia">Insomnia</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="vertigo" name="conditions[]" value="Vertigo" <?php echo strpos(getSessionValue('conditions'), 'Vertigo') !== false ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="vertigo">Vertigo</label>
            </div>
            <div class="form-group mt-2">
                <label for="other_conditions">Other medical condition, please specify:</label>
                <input type="text" class="form-control" id="other_conditions" name="other_conditions" value="<?php 
                    if (strpos(getSessionValue('conditions'), 'Other:') !== false) {
                        echo preg_replace('/.*Other: ([^;]+).*/', '$1', getSessionValue('conditions'));
                    }
                ?>">
            </div>
        </div>
    </div>

    <div class="allergy-group mb-4">
        <label><strong>Allergies:</strong></label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_allergies" name="no_allergies" <?php echo getSessionValue('no_allergies') ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="no_allergies">No, I don't have any allergies</label>
        </div>
        <div class="allergy-input mt-2">
            <label for="allergy">If yes, specifically allergic to:</label>
            <input type="text" class="form-control" id="allergy" name="allergy" value="<?php echo getSessionValue('allergy'); ?>">
        </div>
    </div>

    <div class="physical-condition-group mb-4">
        <label><strong>Scoliosis/Physical Conditions:</strong></label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_physical_conditions" name="no_physical_conditions" <?php echo getSessionValue('no_physical_conditions') ? 'checked' : ''; ?>>
            <label class="custom-control-label" for="no_physical_conditions">No, I don't have any Scoliosis/physical conditions</label>
        </div>
        <div class="physical-input mt-2">
            <label for="scoliosis">If yes, specify scoliosis/physical condition:</label>
            <input type="text" class="form-control" id="scoliosis" name="scoliosis" value="<?php echo getSessionValue('scoliosis'); ?>">
        </div>
    </div>
</div>

<input type="hidden" id="conditions_hidden" name="conditions" value="<?php echo getSessionValue('conditions'); ?>">
                
                <div class="form-group">
                    <label>Have you ever seriously considered or attempted suicide?</label><br>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="suicide_no" name="suicide" class="custom-control-input" value="no" <?php echo getSessionValue('suicide') === 'no' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="suicide_no">No</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="suicide_yes" name="suicide" class="custom-control-input" value="yes" <?php echo getSessionValue('suicide') === 'yes' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="suicide_yes">Yes</label>
                    </div>
                    <div id="suicide_reason_container" style="display: <?php echo getSessionValue('suicide') === 'yes' ? 'block' : 'none'; ?>;">
                        <label for="suicide_reason">Please explain:</label>
                        <input type="text" class="form-control mt-2" id="suicide_reason" name="suicide_reason" value="<?php echo getSessionValue('suicide_reason'); ?>">
                    </div>
                </div>
                    <!-- self -->
                    <div class="form-group">
                    <label>Have you ever had a problem with?</label><br>
                <div class="problem-options">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_alcohol" name="problems[]" value="Alcohol/Substance Abuse" <?php echo strpos(getSessionValue('problems'), 'Alcohol/Substance Abuse') !== false ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="problem_alcohol">Alcohol/Substance Abuse</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_eating" name="problems[]" value="Eating Disorder" <?php echo strpos(getSessionValue('problems'), 'Eating Disorder') !== false ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="problem_eating">Eating Disorder</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_depression" name="problems[]" value="Depression" <?php echo strpos(getSessionValue('problems'), 'Depression') !== false ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="problem_depression">Depression</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_aggression" name="problems[]" value="Aggression" <?php echo strpos(getSessionValue('problems'), 'Aggression') !== false ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="problem_aggression">Aggression</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_others" name="problems[]" value="Others" <?php echo strpos(getSessionValue('problems'), 'Others:') !== false ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="problem_others">Others:</label>
                        <input type="text" class="form-control mt-2" id="problem_others_text" name="problem_others_text" value="<?php 
                            if (strpos(getSessionValue('problems'), 'Others:') !== false) {
                                echo preg_replace('/.*Others: ([^;]+).*/', '$1', getSessionValue('problems'));
                            }
                        ?>">
                    </div>
                </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="no_problems" name="no_problems" value="No problems" <?php echo getSessionValue('no_problems') ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="no_problems">No, I don't have any problems</label>
                    </div>
                </div>

                <input type="hidden" id="problems_hidden" name="problems" value="<?php echo getSessionValue('problems'); ?>">

                <!-- family -->
                <div class="form-group">
                <label>Have any member of your immediate family member had a problem with: </label><br>
            <div class="fam-problem-options">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_alcohol" name="fam-problem[]" value="Alcohol/Substance Abuse" <?php echo strpos(getSessionValue('fam_problems'), 'Alcohol/Substance Abuse') !== false ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_problem_alcohol">Alcohol/Substance Abuse</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_eating" name="fam-problem[]" value="Eating Disorder" <?php echo strpos(getSessionValue('fam_problems'), 'Eating Disorder') !== false ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_problem_eating">Eating Disorder</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_depression" name="fam-problem[]" value="Depression" <?php echo strpos(getSessionValue('fam_problems'), 'Depression') !== false ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_problem_depression">Depression</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_aggression" name="fam-problem[]" value="Aggression" <?php echo strpos(getSessionValue('fam_problems'), 'Aggression') !== false ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_problem_aggression">Aggression</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_others" name="fam-problem[]" value="Others" <?php echo strpos(getSessionValue('fam_problems'), 'Others:') !== false ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_problem_others">Others:</label>
                    <input type="text" class="form-control mt-2" id="fam_problem_others_text" name="fam_problem_others_text" value="<?php 
                            if (strpos(getSessionValue('fam_problems'), 'Others:') !== false) {
                                echo preg_replace('/.*Others: ([^;]+).*/', '$1', getSessionValue('fam_problems'));
                            }
                        ?>">
                </div>
            </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="fam_no_problems" name="fam_no_problems" value="No problems" <?php echo getSessionValue('fam_no_problems') ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="fam_no_problems">No, they don't have any problems</label>
                </div>
            </div>

            <input type="hidden" id="fam_problems_hidden" name="fam-problems" value="<?php echo getSessionValue('fam_problems'); ?>">

                <div class="form-group">
                    <label>Do you engage in physical fitness activity?</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="fitness_no" name="fitness" class="custom-control-input" value="no" <?php echo getSessionValue('fitness') === 'no' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="fitness_no">No</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="fitness_yes" name="fitness" class="custom-control-input" value="yes" <?php echo getSessionValue('fitness') === 'yes' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="fitness_yes">Yes, Specify:</label>
                        <input type="text" class="form-control mt-2" id="fitness_specify" name="fitness_specify" value="<?php echo getSessionValue('fitness_specify'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>If yes, how often:</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="everyday" name="fitness_frequency" class="custom-control-input" value="Everyday" <?php echo getSessionValue('fitness_frequency') === 'Everyday' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="everyday">Everyday</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="2-3_week" name="fitness_frequency" class="custom-control-input" value="2-3 Week" <?php echo getSessionValue('fitness_frequency') === '2-3 Week' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="2-3_week">2-3 times a week</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="2-3_month" name="fitness_frequency" class="custom-control-input" value="2-3 Month" <?php echo getSessionValue('fitness_frequency') === '2-3 Month' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="2-3_month">2-3 times a month</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>How would you rate your current level of stress, 10 as highest & 1 as lowest:</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="low" name="stress" class="custom-control-input" value="low" <?php echo getSessionValue('stress') === 'low' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="low">Low (1-3)</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="average" name="stress" class="custom-control-input" value="average" <?php echo getSessionValue('stress') === 'average' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="average">Average (4-7)</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="high" name="stress" class="custom-control-input" value="high" <?php echo getSessionValue('stress') === 'high' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="high">High (8-10)</label>
                    </div>
                </div>

                <p>I hereby attest that all information stated above is true and correct.</p>

           <div class="form-group mb-3">
            <label for="signature" class="form-label">Digital Signature</label>
            <div>
                <canvas id="signatureCanvas" width="400" height="200" style="border: 1px solid #000;"></canvas>
            </div>
            <button type="button" id="clearSignature" class="btn btn-secondary mt-2">Clear Signature</button>
            <input type="hidden" name="signature" id="signatureData" value="<?php echo getSessionValue('signature'); ?>">
        </div>

        <div class="form-group mt-3">
            <button type="button" class="btn btn-secondary btn-navigation" onclick="handlePrevious()">Previous</button>
            <button type="submit" class="btn btn-primary btn-navigation">Review</button>
            </div>
        </form>
    </div>

     <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    

<script>
$(document).ready(function() {
    var canvas = document.getElementById('signatureCanvas');
    signaturePad = new SignaturePad(canvas);
    
    // Load existing signature if available
    var savedSignaturePath = "<?php echo getSignaturePath(); ?>";
    
    if (savedSignaturePath) {
        // Create full URL from the relative path
        var fullSignatureUrl = savedSignaturePath;
        
        // If path doesn't start with http or https, make it absolute
        if (!fullSignatureUrl.startsWith('http')) {
            fullSignatureUrl = window.location.origin + savedSignaturePath;
        }
        
        // Load the image onto the canvas
        var img = new Image();
        img.onload = function() {
            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            signaturePad._isEmpty = false;
        };
        img.onerror = function() {
            console.error("Error loading signature image:", fullSignatureUrl);
        };
        img.src = fullSignatureUrl;
    }

    // Clear signature button event handler
    $('#clearSignature').click(function() {
        signaturePad.clear();
        // Also clear the hidden field containing signature data
        $('#signatureData').val('');
    });

    // Handle Others checkbox for personal problems
    $('#problem_others').change(function() {
        const othersText = $('#problem_others_text');
        if (this.checked) {
            othersText.prop('required', true).show().prop('readonly', false);
            if (!othersText.val().trim()) {
                othersText.addClass('is-invalid');
            }
        } else {
            othersText.prop('required', false).removeClass('is-invalid').hide().prop('readonly', true);
        }
        updateProblemsHiddenField(false);
        saveFieldStates(); // Save state when checkbox changes
    });

    // Handle Others checkbox for family problems
    $('#fam_problem_others').change(function() {
        const othersText = $('#fam_problem_others_text');
        if (this.checked) {
            othersText.prop('required', true).show().prop('readonly', false);
            if (!othersText.val().trim()) {
                othersText.addClass('is-invalid');
            }
        } else {
            othersText.prop('required', false).removeClass('is-invalid').hide().prop('readonly', true);
        }
        updateProblemsHiddenField(true);
        saveFieldStates(); // Save state when checkbox changes
    });

    // Handle text input for personal problems Others
    $('#problem_others_text').on('input', function() {
        if ($('#problem_others').is(':checked')) {
            $(this).toggleClass('is-invalid', !this.value.trim());
        }
        updateProblemsHiddenField(false);
    });

    // Handle text input for family problems Others
    $('#fam_problem_others_text').on('input', function() {
        if ($('#fam_problem_others').is(':checked')) {
            $(this).toggleClass('is-invalid', !this.value.trim());
        }
        updateProblemsHiddenField(true);
    });

    // Handle "No medications" checkbox
    $('#no_medications').change(function() {
        if(this.checked) {
            $('#medications').val('NO MEDICATIONS').prop('readonly', true);
        } else {
            $('#medications').val('').prop('readonly', false);
        }
        validateMedications();
        saveFieldStates(); // Save state when checkbox changes
    });

    // Handle medications input
    $('#medications').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_medications').prop('checked', false);
        }
        validateMedications();
    });

    // Add these event handlers for the condition checkboxes
    $('.condition-checkbox').change(function() {
        if($(this).is(':checked')) {
            $('#no_medical_list').prop('checked', false);
            $('.condition-options').removeClass('text-muted');
            // Enable related fields
            $('.condition-checkbox').prop('disabled', false);
            $('#other_conditions').prop('readonly', false);
        }
        updateConditionsHiddenField();
        saveFieldStates(); // Save state when checkbox changes
    });

    // Add input handlers for each section
    $('#other_conditions').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_medical_list').prop('checked', false);
            $('.condition-checkbox').prop('disabled', false);
            $('#other_conditions').prop('readonly', false);
            $('.condition-options').removeClass('text-muted');
        }
        updateConditionsHiddenField();
    });

    $('#allergy').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_allergies').prop('checked', false);
            $('#allergy').prop('readonly', false);
            $('.allergy-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
    });

    $('#scoliosis').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_physical_conditions').prop('checked', false);
            $('#scoliosis').prop('readonly', false);
            $('.physical-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
    });

    // Handle "No medical conditions" checkbox
    $('#no_medical_list').change(function() {
        if(this.checked) {
            $('.condition-checkbox').prop('checked', false).prop('disabled', true);
            $('#other_conditions').val('').prop('readonly', true);
            $('.condition-options').addClass('text-muted');
        } else {
            $('.condition-checkbox').prop('disabled', false);
            $('#other_conditions').prop('readonly', false);
            $('.condition-options').removeClass('text-muted');
        }
        updateConditionsHiddenField();
        saveFieldStates(); // Save state when checkbox changes
    });

    // Handle "No allergies" checkbox
    $('#no_allergies').change(function() {
        if(this.checked) {
            $('#allergy').val('').prop('readonly', true);
            $('.allergy-input').addClass('text-muted');
        } else {
            $('#allergy').prop('readonly', false);
            $('.allergy-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
        saveFieldStates(); // Save state when checkbox changes
    });

    // Handle "No physical conditions" checkbox
    $('#no_physical_conditions').change(function() {
        if(this.checked) {
            $('#scoliosis').val('').prop('readonly', true);
            $('.physical-input').addClass('text-muted');
        } else {
            $('#scoliosis').prop('readonly', false);
            $('.physical-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
        saveFieldStates(); // Save state when checkbox changes
    });

    // Handle suicide question
    $('input[name="suicide"]').change(function() {
        if(this.value === 'yes') {
            $('#suicide_reason_container').show();
            $('#suicide_reason').prop('readonly', false);
        } else {
            $('#suicide_reason_container').hide();
            $('#suicide_reason').val('').prop('readonly', true);
        }
        saveFieldStates(); // Save state when radio changes
    });

    // Handle fitness question
    $('input[name="fitness"]').change(function() {
        if(this.value === 'yes') {
            $('#fitness_specify').prop('disabled', false);
            $('input[name="fitness_frequency"]').prop('disabled', false);
        } else {
            $('#fitness_specify').val('').prop('disabled', true);
            $('input[name="fitness_frequency"]').prop('checked', false).prop('disabled', true);
        }
        saveFieldStates(); // Save state when radio changes
    });

    // Prevent form submission on enter key
    $(window).keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            return false;
        }
    });

    // Modify your form submit handler to temporarily enable disabled fields before submission
    $('#medicalHistoryForm').submit(function(e) {
        e.preventDefault();
        if (validateForm()) {
            // If the signature pad has a signature, get its data
            if (!signaturePad.isEmpty()) {
                var signatureData = signaturePad.toDataURL();
                $('#signatureData').val(signatureData);
            } else {
                // If no new signature is drawn, keep the existing one if it exists
                var savedSignaturePath = "<?php echo getSignaturePath(); ?>";
                if (savedSignaturePath && $('#signatureData').val() === '') {
                    // No need to update, use existing signature path
                    console.log("Using existing signature path");
                }
            }
            
            // Save the disabled state of fields to session storage before enabling them
            saveFieldStates();
            
            // Temporarily enable all disabled fields so they're included in the form submission
            var disabledFields = $(this).find(':disabled').each(function() {
                // Store the disabled state as a data attribute
                $(this).data('wasDisabled', true);
                // Enable the field for submission
                $(this).prop('disabled', false);
            });
            
            // Temporarily enable all readonly fields for submission
            var readonlyFields = $(this).find('[readonly]').each(function() {
                $(this).data('wasReadonly', true);
                $(this).prop('readonly', false);
            });
            
            // Submit the form
            this.submit();
            
            // Re-disable the fields (though this won't execute due to page navigation)
            disabledFields.each(function() {
                if ($(this).data('wasDisabled')) {
                    $(this).prop('disabled', true);
                }
            });
            
            readonlyFields.each(function() {
                if ($(this).data('wasReadonly')) {
                    $(this).prop('readonly', true);
                }
            });
        } else {
            alert('Please fill out all required fields.');
        }
    });

    // Save field states to session storage
    function saveFieldStates() {
        const fieldStates = {
            noMedications: $('#no_medications').is(':checked'),
            medications: $('#medications').val(),
            conditionCheckboxesDisabled: $('.condition-checkbox').first().is(':disabled'),
            noMedicalList: $('#no_medical_list').is(':checked'),
            noAllergies: $('#no_allergies').is(':checked'),
            noPhysicalConditions: $('#no_physical_conditions').is(':checked'),
            suicide: $('input[name="suicide"]:checked').val() || '',
            suicideReason: $('#suicide_reason').val(),
            noProblems: $('#no_problems').is(':checked'),
            problemCheckboxesDisabled: $('.problem-checkbox').first().is(':disabled'),
            problemOthersChecked: $('#problem_others').is(':checked'),
            problemOthersText: $('#problem_others_text').val(),
            famNoProblems: $('#fam_no_problems').is(':checked'),
            famProblemCheckboxesDisabled: $('.fam-problem-checkbox').first().is(':disabled'),
            famProblemOthersChecked: $('#fam_problem_others').is(':checked'),
            famProblemOthersText: $('#fam_problem_others_text').val(),
            fitness: $('input[name="fitness"]:checked').val() || '',
            fitnessSpecify: $('#fitness_specify').val(),
            fitnessFrequency: $('input[name="fitness_frequency"]:checked').val() || '',
            stress: $('input[name="stress"]:checked').val() || ''
        };
        
        // Save condition checkbox states
        fieldStates.conditionCheckboxes = {};
        $('.condition-checkbox').each(function() {
            fieldStates.conditionCheckboxes[this.id] = $(this).is(':checked');
        });
        
        // Save problem checkbox states
        fieldStates.problemCheckboxes = {};
        $('.problem-checkbox').each(function() {
            fieldStates.problemCheckboxes[this.id] = $(this).is(':checked');
        });
        
        // Save family problem checkbox states
        fieldStates.famProblemCheckboxes = {};
        $('.fam-problem-checkbox').each(function() {
            fieldStates.famProblemCheckboxes[this.id] = $(this).is(':checked');
        });
        
        sessionStorage.setItem('medicalFormStates', JSON.stringify(fieldStates));
    }

    // Function to check for existing signature
    function hasExistingSignature() {
        var canvas = document.getElementById('signatureCanvas');
        var ctx = canvas.getContext('2d');
        var pixelData = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        
        // Check if the canvas has any non-transparent pixels
        for (var i = 3; i < pixelData.length; i += 4) {
            if (pixelData[i] > 0) {
                return true;
            }
        }
        return false;
    }

    // Update the updateConditionsHiddenField function
    function updateConditionsHiddenField() {
        let conditions = [];
        let hasAnyCondition = false;
        
        // Medical conditions
        if ($('#no_medical_list').is(':checked') && 
            !$('#allergy').val().trim() && 
            !$('#scoliosis').val().trim() && 
            $('.condition-checkbox:checked').length === 0 && 
            !$('#other_conditions').val().trim()) {
            conditions.push('NO MEDICAL CONDITIONS');
        } else {
            // Add checked conditions
            $('.condition-checkbox:checked').each(function() {
                conditions.push($(this).val());
                hasAnyCondition = true;
            });
            
            // Add other conditions if specified
            let otherConditions = $('#other_conditions').val().trim();
            if (otherConditions) {
                conditions.push('Other: ' + otherConditions);
                hasAnyCondition = true;
            }
            
            // Add allergies if specified
            let allergy = $('#allergy').val().trim();
            if (allergy) {
                conditions.push('Allergy: ' + allergy);
                hasAnyCondition = true;
            }
            
            // Add physical conditions if specified
            let scoliosis = $('#scoliosis').val().trim();
            if (scoliosis) {
                conditions.push('Scoliosis/Physical condition: ' + scoliosis);
                hasAnyCondition = true;
            }
        }
        
        // Filter out any empty values and join with semicolons
        $('#conditions_hidden').val(conditions.filter(Boolean).join('; '));
    }

    function validateMedications() {
        if ($('#no_medications').is(':checked') || $('#medications').val().trim() !== '') {
            $('#medications').removeClass('is-invalid');
            return true;
        } else {
            $('#medications').addClass('is-invalid');
            return false;
        }
    }

    function validateConditions() {
        let isValid = true;
        
        // Validate Medical Conditions section
        if (!$('#no_medical_list').is(':checked') && 
            $('.condition-checkbox:checked').length === 0 && 
            $('#other_conditions').val().trim() === '') {
            $('.medical-conditions-group').addClass('is-invalid');
            isValid = false;
        } else {
            $('.medical-conditions-group').removeClass('is-invalid');
        }
        
        // Validate Allergies section
        if (!$('#no_allergies').is(':checked') && 
            $('#allergy').val().trim() === '') {
            $('.allergy-group').addClass('is-invalid');
            isValid = false;
        } else {
            $('.allergy-group').removeClass('is-invalid');
        }
        
        // Validate Physical Conditions section
        if (!$('#no_physical_conditions').is(':checked') && 
            $('#scoliosis').val().trim() === '') {
            $('.physical-condition-group').addClass('is-invalid');
            isValid = false;
        } else {
            $('.physical-condition-group').removeClass('is-invalid');
        }
        
        return isValid;
    }

    function validateForm() {
        var isValid = true;

        if (!validateMedications()) {
            isValid = false;
        }

        if (!validateConditions()) {
            isValid = false;
        }

        if (!validateSuicide()) {
            isValid = false;
        }

        if (!validateProblems()) {
            isValid = false;
        }

        if (!validateFamilyProblems()) {
            isValid = false;
        }

        if (!validateFitness()) {
            isValid = false;
        }

        if (!validateStress()) {
            isValid = false;
        }

        return isValid;
    }

    function validateSuicide() {
        if ($('input[name="suicide"]:checked').length > 0) {
            $('input[name="suicide"]').removeClass('is-invalid');
            if ($('input[name="suicide"]:checked').val() === 'yes' && $('#suicide_reason').val().trim() === '') {
                $('#suicide_reason').addClass('is-invalid');
                return false;
            }
            $('#suicide_reason').removeClass('is-invalid');
            return true;
        } else {
            $('input[name="suicide"]').addClass('is-invalid');
            return false;
        }
    }

    function validateProblems() {
        if ($('#no_problems').is(':checked')) {
            $('.problem-checkbox').removeClass('is-invalid');
            $('#problem_others_text').removeClass('is-invalid');
            return true;
        }

        if ($('.problem-checkbox:checked').length === 0) {
            $('.problem-checkbox').addClass('is-invalid');
            return false;
        }

        // Check if Others is checked but text field is empty
        if ($('#problem_others').is(':checked') && !$('#problem_others_text').val().trim()) {
            $('#problem_others_text').addClass('is-invalid');
            return false;
        }

        $('.problem-checkbox').removeClass('is-invalid');
        $('#problem_others_text').removeClass('is-invalid');
        return true;
    }

    function validateFamilyProblems() {
        if ($('#fam_no_problems').is(':checked')) {
            $('.fam-problem-checkbox').removeClass('is-invalid');
            $('#fam_problem_others_text').removeClass('is-invalid');
            return true;
        }

        if ($('.fam-problem-checkbox:checked').length === 0) {
            $('.fam-problem-checkbox').addClass('is-invalid');
            return false;
        }

        // Check if Others is checked but text field is empty
        if ($('#fam_problem_others').is(':checked') && !$('#fam_problem_others_text').val().trim()) {
            $('#fam_problem_others_text').addClass('is-invalid');
            return false;
        }

        $('.fam-problem-checkbox').removeClass('is-invalid');
        $('#fam_problem_others_text').removeClass('is-invalid');
        return true;
    }

    function validateFitness() {
        if ($('input[name="fitness"]:checked').length > 0) {
            $('input[name="fitness"]').removeClass('is-invalid');
            if ($('input[name="fitness"]:checked').val() === 'yes') {
                if ($('#fitness_specify').val().trim() === '') {
                    $('#fitness_specify').addClass('is-invalid');
                    return false;
                }
                if ($('input[name="fitness_frequency"]:checked').length === 0) {
                    $('input[name="fitness_frequency"]').addClass('is-invalid');
                    return false;
                }
            }
            $('#fitness_specify, input[name="fitness_frequency"]').removeClass('is-invalid');
            return true;
        } else {
            $('input[name="fitness"]').addClass('is-invalid');
            return false;
        }
    }

    function validateStress() {
        if ($('input[name="stress"]:checked').length > 0) {
            $('input[name="stress"]').removeClass('is-invalid');
            return true;
        } else {
            $('input[name="stress"]').addClass('is-invalid');
            return false;
        }
    }
    
    // Initial updates and validations
    updateConditionsHiddenField();

    // Initialize problems hidden fields
    updateProblemsHiddenField(false);
    updateProblemsHiddenField(true);
    
    validateMedications();
    validateConditions();
    
    // Get session data from PHP and populate form
    const sessionData = <?php echo isset($_SESSION['medical_history']) ? json_encode($_SESSION['medical_history']) : 'null'; ?>;
    if (sessionData) {
        populateFormFromSession(sessionData);
    }
    
    // Restore field states from session storage if available
    const savedFieldStates = sessionStorage.getItem('medicalFormStates');
    if (savedFieldStates) {
        const fieldStates = JSON.parse(savedFieldStates);
        applyFieldStates(fieldStates);
    }
});

// Function to apply saved field states
function applyFieldStates(fieldStates) {
    if (!fieldStates) return;
    
    // Apply medications state
    if (fieldStates.noMedications) {
        $('#no_medications').prop('checked', true);
        $('#medications').val(fieldStates.medications || 'NO MEDICATIONS').prop('readonly', true);
    } else if (fieldStates.medications) {
        $('#medications').val(fieldStates.medications);
    }
    
    // Apply medical conditions state
    if (fieldStates.noMedicalList) {
        $('#no_medical_list').prop('checked', true);
        $('.condition-checkbox').prop('disabled', true).prop('checked', false);
        $('#other_conditions').prop('readonly', true).val('');
        $('.condition-options').addClass('text-muted');
    } else if (fieldStates.conditionCheckboxes) {
        // Restore individual checkbox states
        for (const [id, checked] of Object.entries(fieldStates.conditionCheckboxes)) {
            $(`#${id}`).prop('checked', checked);
        }
    }
    
    // Apply allergies state
    if (fieldStates.noAllergies) {
        $('#no_allergies').prop('checked', true);
        $('#allergy').prop('readonly', true).val('');
        $('.allergy-input').addClass('text-muted');
    }
    
    // Apply physical conditions state
    if (fieldStates.noPhysicalConditions) {
        $('#no_physical_conditions').prop('checked', true);
        $('#scoliosis').prop('readonly', true).val('');
        $('.physical-input').addClass('text-muted');
    }
    
    // Apply suicide state
    if (fieldStates.suicide) {
        $(`input[name="suicide"][value="${fieldStates.suicide}"]`).prop('checked', true);
        if (fieldStates.suicide === 'yes') {
            $('#suicide_reason_container').show();
            $('#suicide_reason').prop('readonly', false).val(fieldStates.suicideReason || '');
        } else {
            $('#suicide_reason_container').hide();
            $('#suicide_reason').prop('readonly', true).val('');
        }
    }
    
    // Apply problems state
    if (fieldStates.noProblems) {
        $('#no_problems').prop('checked', true);
        $('.problem-checkbox').prop('disabled', true).prop('checked', false);
        $('#problem_others_text').prop('readonly', true).val('').hide();
        $('.problem-options').addClass('text-muted');
    } else if (fieldStates.problemCheckboxes) {
        // Restore individual checkbox states
        for (const [id, checked] of Object.entries(fieldStates.problemCheckboxes)) {
            $(`#${id}`).prop('checked', checked);
        }
        
        if (fieldStates.problemOthersChecked) {
            $('#problem_others').prop('checked', true);
            $('#problem_others_text').prop('readonly', false).val(fieldStates.problemOthersText || '').show();
        }
    }
    
    // Apply family problems state
    if (fieldStates.famNoProblems) {
        $('#fam_no_problems').prop('checked', true);
        $('.fam-problem-checkbox').prop('disabled', true).prop('checked', false);
        $('#fam_problem_others_text').prop('readonly', true).val('').hide();
        $('.fam-problem-options').addClass('text-muted');
    } else if (fieldStates.famProblemCheckboxes) {
        // Restore individual checkbox states
        for (const [id, checked] of Object.entries(fieldStates.famProblemCheckboxes)) {
            $(`#${id}`).prop('checked', checked);
        }
        
        if (fieldStates.famProblemOthersChecked) {
            $('#fam_problem_others').prop('checked', true);
            $('#fam_problem_others_text').prop('readonly', false).val(fieldStates.famProblemOthersText || '').show();
        }
    }
    
    // Apply fitness state
    if (fieldStates.fitness) {
        $(`input[name="fitness"][value="${fieldStates.fitness}"]`).prop('checked', true);
        if (fieldStates.fitness === 'yes') {
            $('#fitness_specify').prop('disabled', false).val(fieldStates.fitnessSpecify || '');
            $('input[name="fitness_frequency"]').prop('disabled', false);
            if (fieldStates.fitnessFrequency) {
                $(`input[name="fitness_frequency"][value="${fieldStates.fitnessFrequency}"]`).prop('checked', true);
            }
        } else {
            $('#fitness_specify').prop('disabled', true).val('');
            $('input[name="fitness_frequency"]').prop('disabled', true).prop('checked', false);
        }
    }
    
    // Apply stress state
    if (fieldStates.stress) {
        $(`input[name="stress"][value="${fieldStates.stress}"]`).prop('checked', true);
    }
    
    // Update hidden fields after all states are applied
    updateConditionsHiddenField();
    updateProblemsHiddenField(false);
    updateProblemsHiddenField(true);
}

// Problems section JavaScript (both self and family)
document.addEventListener('DOMContentLoaded', function() {
    // Self problems
    const problemCheckboxes = document.querySelectorAll('.problem-checkbox');
    const noProblemsCheckbox = document.getElementById('no_problems');
    const problemsHidden = document.getElementById('problems_hidden');
    const problemOthersText = document.getElementById('problem_others_text');
    const problemOthersCheckbox = document.getElementById('problem_others');

    // Family problems
    const famProblemCheckboxes = document.querySelectorAll('.fam-problem-checkbox');
    const famNoProblemsCheckbox = document.getElementById('fam_no_problems');
    const famProblemsHidden = document.getElementById('fam_problems_hidden');
    const famProblemOthersText = document.getElementById('fam_problem_others_text');
    const famProblemOthersCheckbox = document.getElementById('fam_problem_others');

    function updateProblemsHiddenField(isFamily = false) {
        const checkboxes = isFamily ? famProblemCheckboxes : problemCheckboxes;
        const noProblems = isFamily ? famNoProblemsCheckbox : noProblemsCheckbox;
        const hiddenField = isFamily ? famProblemsHidden : problemsHidden;
        const othersText = isFamily ? famProblemOthersText : problemOthersText;
        const othersCheckbox = isFamily ? famProblemOthersCheckbox : problemOthersCheckbox;
        
        if (noProblems.checked) {
            hiddenField.value = 'NO PROBLEMS';
            // Disable all problem checkboxes
            checkboxes.forEach(cb => {
                cb.disabled = true;
                cb.checked = false;
            });
            // Disable and clear the Others text field
            othersText.disabled = true;
            othersText.value = '';
            othersText.readonly = true;
        } else {
            const selectedProblems = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => {
                    if (cb.value === 'Others' && othersText.value.trim()) {
                        return `Others: ${othersText.value.trim()}`;
                    }
                    return cb.value !== 'Others' ? cb.value : '';
                })
                .filter(value => value !== '');
            
            hiddenField.value = selectedProblems.length > 0 ? selectedProblems.join('; ') : '';
            
            // Enable all problem checkboxes
            checkboxes.forEach(cb => {
                cb.disabled = false;
            });
            
            // Enable the Others text field if Others checkbox is checked
            if (othersCheckbox.checked) {
                othersText.disabled = false;
                othersText.readonly = false;
            }
        }
        // Save field states after update
        saveFieldStates();
    }

    function toggleProblemCheckboxes(disabled, isFamily = false) {
        const checkboxes = isFamily ? famProblemCheckboxes : problemCheckboxes;
        const othersText = isFamily ? famProblemOthersText : problemOthersText;
        const optionsContainer = isFamily ? $('.fam-problem-options') : $('.problem-options');
        
        checkboxes.forEach(cb => {
            cb.disabled = disabled;
            if (disabled) {
                cb.checked = false;
            }
        });
        
        othersText.disabled = disabled;
        othersText.readonly = disabled;
        if (disabled) {
            othersText.value = '';
        }
        
        if (disabled) {
            optionsContainer.addClass('text-muted');
        } else {
            optionsContainer.removeClass('text-muted');
        }
        
        // Save field states after toggling
        saveFieldStates();
    }

    // Event listeners for self problems
    noProblemsCheckbox.addEventListener('change', function() {
        toggleProblemCheckboxes(this.checked, false);
        if (!this.checked) {
            // Enable all checkboxes when unchecked
            $('.problem-checkbox').prop('disabled', false);
            $('#problem_others_text').prop('readonly', false);
            $('.problem-options').removeClass('text-muted');
        }
        updateProblemsHiddenField(false);
    });

    problemCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                noProblemsCheckbox.checked = false;
                toggleProblemCheckboxes(false, false);
                
                // Special handling for Others checkbox
                if (this.value === 'Others') {
                    $('#problem_others_text').prop('readonly', false).show();
                }
            } else if (this.value === 'Others') {
                // Hide/disable Others text if unchecked
                $('#problem_others_text').prop('readonly', true).hide();
            }
            updateProblemsHiddenField(false);
        });
    });

    // Event listeners for family problems
    famNoProblemsCheckbox.addEventListener('change', function() {
        toggleProblemCheckboxes(this.checked, true);
        if (!this.checked) {
            // Enable all checkboxes when unchecked
            $('.fam-problem-checkbox').prop('disabled', false);
            $('#fam_problem_others_text').prop('readonly', false);
            $('.fam-problem-options').removeClass('text-muted');
        }
        updateProblemsHiddenField(true);
    });

    famProblemCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                famNoProblemsCheckbox.checked = false;
                toggleProblemCheckboxes(false, true);
                
                // Special handling for Others checkbox
                if (this.value === 'Others') {
                    $('#fam_problem_others_text').prop('readonly', false).show();
                }
            } else if (this.value === 'Others') {
                // Hide/disable Others text if unchecked
                $('#fam_problem_others_text').prop('readonly', true).hide();
            }
            updateProblemsHiddenField(true);
        });
    });

    problemOthersText.addEventListener('input', function() {
        if (this.value) {
            problemOthersCheckbox.checked = true;
            noProblemsCheckbox.checked = false;
            toggleProblemCheckboxes(false, false);
        }
        updateProblemsHiddenField(false);
    });

    famProblemOthersText.addEventListener('input', function() {
        if (this.value) {
            famProblemOthersCheckbox.checked = true;
            famNoProblemsCheckbox.checked = false;
            toggleProblemCheckboxes(false, true);
        }
        updateProblemsHiddenField(true);
    });

    // Initial updates
    updateProblemsHiddenField(false);
    updateProblemsHiddenField(true);
});

// Function to populate form fields from session data
function populateFormFromSession(sessionData) {
    if (!sessionData) return;

    // Medications
    if (sessionData.no_medications) {
        $('#no_medications').prop('checked', true).trigger('change');
    } else if (sessionData.medications) {
        $('#medications').val(sessionData.medications);
    }

    // Medical conditions
    if (sessionData.no_medical_list) {
        $('#no_medical_list').prop('checked', true).trigger('change');
    } else if (sessionData.conditions) {
        const conditions = Array.isArray(sessionData.conditions) 
            ? sessionData.conditions 
            : (typeof sessionData.conditions === 'string' ? sessionData.conditions.split('; ') : []);
            
        conditions.forEach(condition => {
            if (condition.startsWith('Other:')) {
                $('#other_conditions').val(condition.replace('Other: ', ''));
            } else if (condition.startsWith('Allergy:')) {
                $('#allergy').val(condition.replace('Allergy: ', ''));
            } else if (condition.startsWith('Scoliosis/Physical condition:')) {
                $('#scoliosis').val(condition.replace('Scoliosis/Physical condition: ', ''));
            } else {
                // Find and check the checkbox with this value
                $(`.condition-checkbox[value="${condition}"]`).prop('checked', true);
            }
        });
    }

    // Allergies
    if (sessionData.no_allergies) {
        $('#no_allergies').prop('checked', true).trigger('change');
    } else if (sessionData.allergy) {
        $('#allergy').val(sessionData.allergy);
    }

    // Physical conditions
    if (sessionData.no_physical_conditions) {
        $('#no_physical_conditions').prop('checked', true).trigger('change');
    } else if (sessionData.scoliosis) {
        $('#scoliosis').val(sessionData.scoliosis);
    }

    // Suicide
    if (sessionData.suicide) {
        $(`input[name="suicide"][value="${sessionData.suicide}"]`).prop('checked', true).trigger('change');
        if (sessionData.suicide === 'yes' && sessionData.suicide_reason) {
            $('#suicide_reason').val(sessionData.suicide_reason);
        }
    }

    // Problems (self)
    if (sessionData.no_problems) {
        $('#no_problems').prop('checked', true).trigger('change');
    } else if (sessionData.problems) {
        const problems = Array.isArray(sessionData.problems) 
            ? sessionData.problems 
            : (typeof sessionData.problems === 'string' ? sessionData.problems.split('; ') : []);
            problems.forEach(problem => {
            if (problem.startsWith('Others:')) {
                $('#problem_others').prop('checked', true);
                $('#problem_others_text').val(problem.replace('Others: ', '')).prop('readonly', false).show();
            } else {
                // Find the checkbox with this value and check it
                $(`.problem-checkbox[value="${problem}"]`).prop('checked', true);
            }
        });
        // Ensure "No problems" is unchecked
        $('#no_problems').prop('checked', false);
        // Enable the problem options
        $('.problem-checkbox').prop('disabled', false);
        $('#problem_others_text').prop('readonly', false);
        $('.problem-options').removeClass('text-muted');
    }

    // Problems (family)
    if (sessionData.fam_no_problems) {
        $('#fam_no_problems').prop('checked', true).trigger('change');
    } else if (sessionData.fam_problems) {
        const famProblems = Array.isArray(sessionData.fam_problems) 
            ? sessionData.fam_problems 
            : (typeof sessionData.fam_problems === 'string' ? sessionData.fam_problems.split('; ') : []);
            
        famProblems.forEach(problem => {
            if (problem.startsWith('Others:')) {
                $('#fam_problem_others').prop('checked', true);
                $('#fam_problem_others_text').val(problem.replace('Others: ', '')).prop('readonly', false).show();
            } else {
                // Find the checkbox with this value and check it
                $(`.fam-problem-checkbox[value="${problem}"]`).prop('checked', true);
            }
        });
        // Ensure "No problems" is unchecked
        $('#fam_no_problems').prop('checked', false);
        // Enable the problem options
        $('.fam-problem-checkbox').prop('disabled', false);
        $('#fam_problem_others_text').prop('readonly', false);
        $('.fam-problem-options').removeClass('text-muted');
    }

    // Fitness
    if (sessionData.fitness) {
        $(`input[name="fitness"][value="${sessionData.fitness}"]`).prop('checked', true).trigger('change');
        if (sessionData.fitness === 'yes') {
            if (sessionData.fitness_specify) {
                $('#fitness_specify').val(sessionData.fitness_specify).prop('disabled', false);
            }
            if (sessionData.fitness_frequency) {
                $(`input[name="fitness_frequency"][value="${sessionData.fitness_frequency}"]`).prop('checked', true);
                $('input[name="fitness_frequency"]').prop('disabled', false);
            }
        }
    }

    // Stress
    if (sessionData.stress) {
        $(`input[name="stress"][value="${sessionData.stress}"]`).prop('checked', true);
    }
    
    // Signature (if available)
    if (sessionData.signature) {
        $('#signatureData').val(sessionData.signature);
        
        // Display the signature on the canvas
        const image = new Image();
        image.onload = function() {
            const canvas = document.getElementById('signatureCanvas');
            const context = canvas.getContext('2d');
            context.drawImage(image, 0, 0);
            // Update the signature pad's internal state
            signaturePad._isEmpty = false;
        };
        image.src = sessionData.signature;
    }
    
    // After setting form values, save the state to sessionStorage
    saveFieldStates();
}

function handlePrevious() {
    // Save the current state of all fields to session storage
    saveFieldStates();
    
    // Enable any disabled fields before collecting form data
    var disabledFields = $('#medicalHistoryForm').find(':disabled').prop('disabled', false);
    var readonlyFields = $('#medicalHistoryForm').find('[readonly]').prop('readonly', false);
    
    // Create form data object
    const formData = new FormData(document.getElementById('medicalHistoryForm'));
    
    // Add signature if exists
    if (!signaturePad.isEmpty()) {
        formData.append('signature', signaturePad.toDataURL());
    }
    
    // Use fetch to submit the form data
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.ok) {
            // After successful save, navigate to previous page
            window.location.href = 'educational_career.php';
        } else {
            throw new Error('Network response was not ok');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error saving your data. Please try again.');
        
        // Re-disable fields if there's an error
        disabledFields.prop('disabled', true);
        readonlyFields.prop('readonly', true);
    });
}

// Function to save all field states to session storage
function saveFieldStates() {
    const fieldStates = {
        noMedications: $('#no_medications').is(':checked'),
        medications: $('#medications').val(),
        conditionCheckboxesDisabled: $('.condition-checkbox').first().is(':disabled'),
        noMedicalList: $('#no_medical_list').is(':checked'),
        noAllergies: $('#no_allergies').is(':checked'),
        noPhysicalConditions: $('#no_physical_conditions').is(':checked'),
        suicide: $('input[name="suicide"]:checked').val() || '',
        suicideReason: $('#suicide_reason').val(),
        noProblems: $('#no_problems').is(':checked'),
        problemCheckboxesDisabled: $('.problem-checkbox').first().is(':disabled'),
        problemOthersChecked: $('#problem_others').is(':checked'),
        problemOthersText: $('#problem_others_text').val(),
        famNoProblems: $('#fam_no_problems').is(':checked'),
        famProblemCheckboxesDisabled: $('.fam-problem-checkbox').first().is(':disabled'),
        famProblemOthersChecked: $('#fam_problem_others').is(':checked'),
        famProblemOthersText: $('#fam_problem_others_text').val(),
        fitness: $('input[name="fitness"]:checked').val() || '',
        fitnessSpecify: $('#fitness_specify').val(),
        fitnessFrequency: $('input[name="fitness_frequency"]:checked').val() || '',
        stress: $('input[name="stress"]:checked').val() || ''
    };
    
    // Save condition checkbox states
    fieldStates.conditionCheckboxes = {};
    $('.condition-checkbox').each(function() {
        fieldStates.conditionCheckboxes[this.id] = $(this).is(':checked');
    });
    
    // Save problem checkbox states
    fieldStates.problemCheckboxes = {};
    $('.problem-checkbox').each(function() {
        fieldStates.problemCheckboxes[this.id] = $(this).is(':checked');
    });
    
    // Save family problem checkbox states
    fieldStates.famProblemCheckboxes = {};
    $('.fam-problem-checkbox').each(function() {
        fieldStates.famProblemCheckboxes[this.id] = $(this).is(':checked');
    });
    
    sessionStorage.setItem('medicalFormStates', JSON.stringify(fieldStates));
}
</script>
</body>
</html>