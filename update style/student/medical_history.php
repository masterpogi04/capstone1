<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

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
}

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_data = $_POST;

    // Handle signature
    if (isset($_POST['signature']) && !empty($_POST['signature'])) {
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


    <div class="container mt-5">
        <div class="progress mb-3">
            <div class="progress-bar" role="progressbar" style="width: 80%;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">80%</div>
        </div>

        <form method="POST" action="" id="medicalHistoryForm">
            <div class="form-section" id="section5">
                <h5>Medical History Information</h5>

               <div class="form-group">
                <label for="medications">List any medications you are taking:</label>
                <input type="text" class="form-control" id="medications" name="medications">
            </div>
            <div class="form-group custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="no_medications" name="no_medications">
                <label class="custom-control-label" for="no_medications">No, I don't take any medications</label>
            </div>

            <p>Do you have any of the following? Kindly check all that apply:</p>
<div class="form-group">
    <div class="medical-conditions-group mb-4">
        <label><strong>Medical Conditions:</strong></label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_medical_list" name="no_medical_list">
            <label class="custom-control-label" for="no_medical_list">No, I don't have any of the following conditions</label>
        </div>
        <div class="condition-options mt-2">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="asthma" name="conditions[]" value="Asthma">
                <label class="custom-control-label" for="asthma">Asthma</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="hypertension" name="conditions[]" value="Hypertension">
                <label class="custom-control-label" for="hypertension">Hypertension</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="diabetes" name="conditions[]" value="Diabetes">
                <label class="custom-control-label" for="diabetes">Diabetes</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="insomnia" name="conditions[]" value="Insomnia">
                <label class="custom-control-label" for="insomnia">Insomnia</label>
            </div>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input condition-checkbox" id="vertigo" name="conditions[]" value="Vertigo">
                <label class="custom-control-label" for="vertigo">Vertigo</label>
            </div>
            <div class="form-group mt-2">
                <label for="other_conditions">Other medical condition, please specify:</label>
                <input type="text" class="form-control" id="other_conditions" name="other_conditions">
            </div>
        </div>
    </div>

    <div class="allergy-group mb-4">
        <label><strong>Allergies:</strong></label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_allergies" name="no_allergies">
            <label class="custom-control-label" for="no_allergies">No, I don't have any allergies</label>
        </div>
        <div class="allergy-input mt-2">
            <label for="allergy">If yes, specifically allergic to:</label>
            <input type="text" class="form-control" id="allergy" name="allergy">
        </div>
    </div>

    <div class="physical-condition-group mb-4">
        <label><strong>Scoliosis/Physical Conditions:</strong></label>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="no_physical_conditions" name="no_physical_conditions">
            <label class="custom-control-label" for="no_physical_conditions">No, I don't have any Scoliosis/physical conditions</label>
        </div>
        <div class="physical-input mt-2">
            <label for="scoliosis">If yes, specify scoliosis/physical condition:</label>
            <input type="text" class="form-control" id="scoliosis" name="scoliosis">
        </div>
    </div>
</div>

<input type="hidden" id="conditions_hidden" name="conditions" value="">
                
                <div class="form-group">
                    <label>Have you ever seriously considered or attempted suicide?</label><br>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="suicide_no" name="suicide" class="custom-control-input" value="no">
                        <label class="custom-control-label" for="suicide_no">No</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="suicide_yes" name="suicide" class="custom-control-input" value="yes">
                        <label class="custom-control-label" for="suicide_yes">Yes</label>
                    </div>
                    <div id="suicide_reason_container" style="display: none;">
                        <label for="suicide_reason">Please explain:</label>
                        <input type="text" class="form-control mt-2" id="suicide_reason" name="suicide_reason">
                    </div>
                </div>
                    <!-- self -->
                    <div class="form-group">
                    <label>Have you ever had a problem with?</label><br>
                <div class="problem-options">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_alcohol" name="problems[]" value="Alcohol/Substance Abuse">
                        <label class="custom-control-label" for="problem_alcohol">Alcohol/Substance Abuse</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_eating" name="problems[]" value="Eating Disorder">
                        <label class="custom-control-label" for="problem_eating">Eating Disorder</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_depression" name="problems[]" value="Depression">
                        <label class="custom-control-label" for="problem_depression">Depression</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_aggression" name="problems[]" value="Aggression">
                        <label class="custom-control-label" for="problem_aggression">Aggression</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input problem-checkbox" id="problem_others" name="problems[]" value="Others">
                        <label class="custom-control-label" for="problem_others">Others:</label>
                        <input type="text" class="form-control mt-2" id="problem_others_text" name="problem_others_text">
                    </div>
                </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="no_problems" name="no_problems" value="No problems">
                        <label class="custom-control-label" for="no_problems">No, I don't have any problems</label>
                    </div>
                </div>

                <input type="hidden" id="problems_hidden" name="problems" value="">

                <!-- family -->
                <div class="form-group">
                <label>Have any member of your immediate family member had a problem with: </label><br>
            <div class="fam-problem-options">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_alcohol" name="fam-problem[]" value="Alcohol/Substance Abuse">
                    <label class="custom-control-label" for="fam_problem_alcohol">Alcohol/Substance Abuse</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_eating" name="fam-problem[]" value="Eating Disorder">
                    <label class="custom-control-label" for="fam_problem_eating">Eating Disorder</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_depression" name="fam-problem[]" value="Depression">
                    <label class="custom-control-label" for="fam_problem_depression">Depression</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_aggression" name="fam-problem[]" value="Aggression">
                    <label class="custom-control-label" for="fam_problem_aggression">Aggression</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input fam-problem-checkbox" id="fam_problem_others" name="fam-problem[]" value="Others">
                    <label class="custom-control-label" for="fam_problem_others">Others:</label>
                    <input type="text" class="form-control mt-2" id="fam_problem_others_text" name="fam_problem_others_text">
                </div>
            </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="fam_no_problems" name="fam_no_problems" value="No problems">
                    <label class="custom-control-label" for="fam_no_problems">No, they don't have any problems</label>
                </div>
            </div>

            <input type="hidden" id="fam_problems_hidden" name="fam-problems" value="">



                <div class="form-group">
                    <label>Do you engage in physical fitness activity?</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="fitness_no" name="fitness" class="custom-control-input" value="no">
                        <label class="custom-control-label" for="fitness_no">No</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="fitness_yes" name="fitness" class="custom-control-input" value="yes">
                        <label class="custom-control-label" for="fitness_yes">Yes, Specify:</label>
                        <input type="text" class="form-control mt-2" id="fitness_specify" name="fitness_specify">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>If yes, how often:</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="everyday" name="fitness_frequency" class="custom-control-input" value="Everyday">
                        <label class="custom-control-label" for="everyday">Everyday</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="2-3_week" name="fitness_frequency" class="custom-control-input" value="2-3 Week">
                        <label class="custom-control-label" for="2-3_week">2-3 times a week</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="2-3_month" name="fitness_frequency" class="custom-control-input" value="2-3 Month">
                        <label class="custom-control-label" for="2-3_month">2-3 times a month</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>How would you rate your current level of stress, 10 as highest & 1 as lowest:</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="low" name="stress" class="custom-control-input" value="low">
                        <label class="custom-control-label" for="low">Low (1-3)</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="average" name="stress" class="custom-control-input" value="average">
                        <label class="custom-control-label" for="average">Average (4-7)</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="high" name="stress" class="custom-control-input" value="high">
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
            <input type="hidden" name="signature" id="signatureData">
        </div>

        <div class="form-group mt-3">
            <button type="button" class="btn btn-secondary btn-navigation" onclick="window.location.href='educational_career.php'">Previous</button>
            <button type="submit" class="btn btn-primary btn-navigation">Review</button>
            </div>
        </form>
    </div>

     <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    

    <script>
$(document).ready(function() {
    var canvas = document.getElementById('signatureCanvas');
    var signaturePad = new SignaturePad(canvas);

    // Clear signature
    $('#clearSignature').click(function() {
        signaturePad.clear();
    });

     // Handle Others checkbox for personal problems
    $('#problem_others').change(function() {
        const othersText = $('#problem_others_text');
        if (this.checked) {
            othersText.prop('required', true).show();
            if (!othersText.val().trim()) {
                othersText.addClass('is-invalid');
            }
        } else {
            othersText.prop('required', false).removeClass('is-invalid').hide();
        }
    });

    // Handle Others checkbox for family problems
    $('#fam_problem_others').change(function() {
        const othersText = $('#fam_problem_others_text');
        if (this.checked) {
            othersText.prop('required', true).show();
            if (!othersText.val().trim()) {
                othersText.addClass('is-invalid');
            }
        } else {
            othersText.prop('required', false).removeClass('is-invalid').hide();
        }
    });

    // Handle text input for personal problems Others
    $('#problem_others_text').on('input', function() {
        if ($('#problem_others').is(':checked')) {
            $(this).toggleClass('is-invalid', !this.value.trim());
        }
    });

    // Handle text input for family problems Others
    $('#fam_problem_others_text').on('input', function() {
        if ($('#fam_problem_others').is(':checked')) {
            $(this).toggleClass('is-invalid', !this.value.trim());
        }
    });

    // Handle "No medications" checkbox
    $('#no_medications').change(function() {
        if(this.checked) {
            $('#medications').val('NO MEDICATIONS').prop('readonly', true);
        } else {
            $('#medications').val('').prop('readonly', false);
        }
        validateMedications();
    });

    // Handle medications input
    $('#medications').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_medications').prop('checked', false);
        }
    });

    // Add these event handlers for the condition checkboxes
    $('.condition-checkbox').change(function() {
        if($(this).is(':checked')) {
            $('#no_medical_list').prop('checked', false);
            $('.condition-options').removeClass('text-muted');
        }
        updateConditionsHiddenField();
    });

    // Add input handlers for each section
    $('#other_conditions').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_medical_list').prop('checked', false);
        }
        updateConditionsHiddenField();
    });

    $('#allergy').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_allergies').prop('checked', false);
        }
        updateConditionsHiddenField();
    });

    $('#scoliosis').on('input', function() {
        if($(this).val().trim() !== '') {
            $('#no_physical_conditions').prop('checked', false);
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
    });

    // Handle "No allergies" checkbox
    $('#no_allergies').change(function() {
        if(this.checked) {
            $('#allergy').val('').prop('readonly', true);
            $('.allergy-input').addClass('text-muted');
        } else {
            $('#allergy').val('').prop('readonly', false);
            $('.allergy-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
    });

    // Handle "No physical conditions" checkbox
    $('#no_physical_conditions').change(function() {
        if(this.checked) {
            $('#scoliosis').val('').prop('readonly', true);
            $('.physical-input').addClass('text-muted');
        } else {
            $('#scoliosis').val('').prop('readonly', false);
            $('.physical-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
    });



    // Handle suicide question
    $('input[name="suicide"]').change(function() {
        if(this.value === 'yes') {
            $('#suicide_reason_container').show();
        } else {
            $('#suicide_reason_container').hide();
            $('#suicide_reason').val('');
        }
    });

    // Handle fitness question
    $('input[name="fitness"]').change(function() {
        if(this.value === 'yes') {
            $('#fitness_specify, input[name="fitness_frequency"]').prop('disabled', false);
        } else {
            $('#fitness_specify').val('').prop('disabled', true);
            $('input[name="fitness_frequency"]').prop('checked', false).prop('disabled', true);
        }
    });

    // Prevent form submission on enter key
    $(window).keydown(function(event){
        if(event.keyCode == 13) {
            event.preventDefault();
            return false;
        }
    });

    $('#medicalHistoryForm').submit(function(e) {
    e.preventDefault();
    if (validateForm()) {
        // Get the current signature data
        var currentSignature = $('#signatureData').val();
        
        // Check if there's a new signature drawn or if there's an existing signature in session
        if (signaturePad.isEmpty() && !currentSignature && !hasExistingSignature()) {
            alert('Please provide a signature');
        } else {
            // If there's a new signature, update the hidden input
            if (!signaturePad.isEmpty()) {
                var signatureData = signaturePad.toDataURL();
                $('#signatureData').val(signatureData);
            }
            
            // Rest of your validation code
            if ($('#no_medications').is(':checked')) {
                $('#medications').val("NO MEDICATIONS").prop('disabled', false);
            }
            
            this.submit();
        }
    } else {
        alert('Please fill out all required fields.');
    }
});

// Add this function to check for existing signature
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
    updateProblemsHiddenField();
    validateMedications();
    validateConditions();
});

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
    
    if (noProblems.checked) {
        hiddenField.value = 'NO PROBLEMS';
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
    }
    
    // For debugging
    console.log('Updated hidden field:', hiddenField.value);
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
        if (disabled) {
            othersText.value = '';
        }
        
        if (disabled) {
            optionsContainer.addClass('text-muted');
        } else {
            optionsContainer.removeClass('text-muted');
        }
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

    if (sessionData.signature) {
            const image = new Image();
            image.onload = function() {
                const context = canvas.getContext('2d');
                context.drawImage(image, 0, 0);
                // Update the signature pad's internal state
                signaturePad._isEmpty = false;
            };
            image.src = sessionData.signature;
        }
    
    
    // Medications
    if (sessionData.medications) {
        $('#medications').val(sessionData.medications);
    }
    if (sessionData.no_medications) {
        $('#no_medications').prop('checked', true).trigger('change');
    }

    // Medical conditions
    if (sessionData.conditions) {
        const conditions = sessionData.conditions.split('; ');
        conditions.forEach(condition => {
            if (condition.startsWith('Other:')) {
                $('#other_conditions').val(condition.replace('Other: ', ''));
            } else if (condition === 'NO MEDICAL CONDITIONS') {
                $('#no_medical_list').prop('checked', true).trigger('change');
            } else {
                $(`input[value="${condition}"]`).prop('checked', true);
            }
        });
    }

    // Allergies
    if (sessionData.allergy) {
        $('#allergy').val(sessionData.allergy);
    }
    if (sessionData.no_allergies) {
        $('#no_allergies').prop('checked', true).trigger('change');
    }

    // Physical conditions
    if (sessionData.scoliosis) {
        $('#scoliosis').val(sessionData.scoliosis);
    }
    if (sessionData.no_physical_conditions) {
        $('#no_physical_conditions').prop('checked', true).trigger('change');
    }

    // Suicide
    if (sessionData.suicide) {
        $(`input[name="suicide"][value="${sessionData.suicide}"]`).prop('checked', true).trigger('change');
        if (sessionData.suicide_reason) {
            $('#suicide_reason').val(sessionData.suicide_reason);
        }
    }

    // Find the populateFormFromSession function and replace the Problems (self) section with:
// Problems (self)
if (sessionData.problems === 'NO PROBLEMS' || sessionData.no_problems) {
    $('#no_problems').prop('checked', true);
    $('.problem-checkbox').prop('disabled', true);
    $('#problem_others_text').prop('readonly', true);
    $('.problem-options').addClass('text-muted');
} else if (sessionData.problems) {
    const problems = sessionData.problems.split('; ');
    problems.forEach(problem => {
        if (problem.startsWith('Others:')) {
            $('#problem_others').prop('checked', true);
            $('#problem_others_text').val(problem.replace('Others: ', '')).prop('readonly', false);
        } else {
            $(`.problem-checkbox[value="${problem}"]`).prop('checked', true);
        }
    });
    $('.problem-checkbox').prop('disabled', false);
    $('#problem_others_text').prop('readonly', false);
    $('.problem-options').removeClass('text-muted');
}

// Replace the Problems (family) section with:
// Problems (family)
if (sessionData.fam_problems === 'NO PROBLEMS' || sessionData.fam_no_problems) {
    $('#fam_no_problems').prop('checked', true);
    $('.fam-problem-checkbox').prop('disabled', true);
    $('#fam_problem_others_text').prop('readonly', true);
    $('.fam-problem-options').addClass('text-muted');
} else if (sessionData.fam_problems) {
    const famProblems = sessionData.fam_problems.split('; ');
    famProblems.forEach(problem => {
        if (problem.startsWith('Others:')) {
            $('#fam_problem_others').prop('checked', true);
            $('#fam_problem_others_text').val(problem.replace('Others: ', '')).prop('readonly', false);
        } else {
            $(`.fam-problem-checkbox[value="${problem}"]`).prop('checked', true);
        }
    });
    $('.fam-problem-checkbox').prop('disabled', false);
    $('#fam_problem_others_text').prop('readonly', false);
    $('.fam-problem-options').removeClass('text-muted');
}

    // Fitness
    if (sessionData.fitness) {
        $(`input[name="fitness"][value="${sessionData.fitness}"]`).prop('checked', true).trigger('change');
        if (sessionData.fitness_specify) {
            $('#fitness_specify').val(sessionData.fitness_specify);
        }
        if (sessionData.fitness_frequency) {
            $(`input[name="fitness_frequency"][value="${sessionData.fitness_frequency}"]`).prop('checked', true);
        }
    }

    // Stress
    if (sessionData.stress) {
        $(`input[name="stress"][value="${sessionData.stress}"]`).prop('checked', true);
    }

    if (sessionData.signature) {
        const image = new Image();
        image.onload = function() {
            const canvas = document.getElementById('signatureCanvas');
            const ctx = canvas.getContext('2d');
            ctx.drawImage(image, 0, 0);
            // Store the signature data in the hidden input
            $('#signatureData').val(sessionData.signature);
        };
        image.src = sessionData.signature;
    }
}

// Call this when the document is ready
$(document).ready(function() {
    // Get session data from PHP
    const sessionData = <?php echo isset($_SESSION['medical_history']) ? json_encode($_SESSION['medical_history']) : 'null'; ?>;
    populateFormFromSession(sessionData);
});
</script>

</body>
</html>
