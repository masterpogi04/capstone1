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

// Function to get signature path
function getSignaturePath() {
    return isset($_SESSION['student_profile']['signature_path']) && 
           !empty($_SESSION['student_profile']['signature_path']) ? 
           $_SESSION['student_profile']['signature_path'] : '';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Store checkbox states first
    $no_medications = isset($_POST['no_medications']);
    $no_medical_list = isset($_POST['no_medical_list']);
    $no_allergies = isset($_POST['no_allergies']);
    $no_physical_conditions = isset($_POST['no_physical_conditions']);
    $no_problems = isset($_POST['no_problems']);
    $fam_no_problems = isset($_POST['fam_no_problems']);

    // Process medications
    $medications = $no_medications ? 'NO MEDICATIONS' : ($_POST['medications'] ?? '');

    // Process conditions
    $conditions = $no_medical_list ? 'NO MEDICAL CONDITIONS' : ($_POST['conditions'] ?? '');

    // Process allergies
    $allergy = $no_allergies ? 'NO ALLERGIES' : ($_POST['allergy'] ?? '');

    // Process physical conditions
    $scoliosis = $no_physical_conditions ? 'NO PHYSICAL CONDITIONS' : ($_POST['scoliosis'] ?? '');

    // Process problems
    $problems = $no_problems ? 'NO PROBLEMS' : ($_POST['problems'] ?? '');

    // Process family problems
    $fam_problems = $fam_no_problems ? 'NO FAMILY PROBLEMS' : ($_POST['fam-problems'] ?? '');

    // Store in session
    $_SESSION['medical_history'] = [
        'medications' => $medications,
        'no_medications' => $no_medications,
        'conditions' => $conditions,
        'no_medical_list' => $no_medical_list,
        'allergy' => $allergy,
        'no_allergies' => $no_allergies,
        'scoliosis' => $scoliosis,
        'no_physical_conditions' => $no_physical_conditions,
        'suicide' => $_POST['suicide'] ?? '',
        'suicide_reason' => $_POST['suicide_reason'] ?? '',
        'problems' => $problems,
        'no_problems' => $no_problems,
        'fam_problems' => $fam_problems,
        'fam_no_problems' => $fam_no_problems,
        'fitness' => $_POST['fitness'] ?? '',
        'fitness_specify' => $_POST['fitness_specify'] ?? '',
        'fitness_frequency' => $_POST['fitness_frequency'] ?? '',
        'stress' => $_POST['stress'] ?? '',
        'signature' => $_POST['signature'] ?? ''
    ];

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['status' => 'success']);
        exit;
    }

    // Handle signature upload
    // In your POST handling code, modify the signature handling section:
$signature_path = getSignaturePath(); // Default to existing path

if (isset($_POST['signature'])) {
    if (empty($_POST['signature'])) {
        // Signature was cleared - remove existing signature
        if (isset($_SESSION['student_profile']['signature_path'])) {
            $old_signature_path = $_SERVER['DOCUMENT_ROOT'] . $_SESSION['student_profile']['signature_path'];
            if (file_exists($old_signature_path)) {
                unlink($old_signature_path);
            }
            unset($_SESSION['student_profile']['signature_path']);
            $signature_path = '';
        }
    } elseif (strpos($_POST['signature'], 'data:image/png;base64,') === 0) {
        // New signature provided
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/capstone1/student/uploads/student_signatures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $signature_filename = 'signature_' . uniqid() . '.png';
        $signature_path = $uploadDir . $signature_filename;
        $signature_data = str_replace('data:image/png;base64,', '', $_POST['signature']);
        $signature_data = base64_decode($signature_data);

        if (file_put_contents($signature_path, $signature_data) !== false) {
            if (isset($_SESSION['student_profile']['signature_path'])) {
                $old_signature_path = $_SERVER['DOCUMENT_ROOT'] . $_SESSION['student_profile']['signature_path'];
                if (file_exists($old_signature_path)) {
                    unlink($old_signature_path);
                }
            }
            $signature_path = '/capstone1/student/uploads/student_signatures/' . $signature_filename;
            $_SESSION['student_profile']['signature_path'] = $signature_path;
        }
    }
}

    // Handle fitness activity
    $fitness_activity = ($_POST['fitness'] === 'no') ? 'NO FITNESS' : $_POST['fitness_specify'];
    $fitness_frequency = ($_POST['fitness'] === 'no') ? null : $_POST['fitness_frequency'];

    // Prepare SQL update
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

    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $connection->error);
        die("Prepare failed: " . $connection->error);
    }

    $stmt->bind_param("sssssssssss",
        $medications,
        $conditions,
        $_POST['suicide'],
        $_POST['suicide_reason'],
        $problems,
        $fam_problems,
        $fitness_activity,
        $fitness_frequency,
        $_POST['stress'],
        $signature_path,
        $_SESSION['student_profile']['student_id']
    );

    if ($stmt->execute()) {
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
    // Initialize signature pad
    var canvas = document.getElementById('signatureCanvas');
    var signaturePad = new SignaturePad(canvas);
    // Add this after initializing signaturePad
    signaturePad.addEventListener("endStroke", function() {
        if (!signaturePad.isEmpty()) {
            $('#signatureData').val(signaturePad.toDataURL());
        }
    });
    
    // Load existing signature if available
    var savedSignaturePath = "<?php echo getSignaturePath(); ?>";
    if (savedSignaturePath) {
        var img = new Image();
        img.onload = function() {
            var ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            signaturePad._isEmpty = false;
        };
        img.src = savedSignaturePath;
    }
    $('#medicalHistoryForm').on('submit', function(e) {
        // Update signature data before submission if there's a signature
        if (!signaturePad.isEmpty()) {
            $('#signatureData').val(signaturePad.toDataURL());
        }

        // Validate form
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }

        // Save field states
        saveFieldStates();

        return true;
    });
    $('#clearSignature').click(function() {
    signaturePad.clear();
    $('#signatureData').val('');
    // Also clear from session if needed
    if (typeof savedFieldStates !== 'undefined' && savedFieldStates.signature) {
        savedFieldStates.signature = '';
        sessionStorage.setItem('medicalFormStates', JSON.stringify(savedFieldStates));
    }
});

    // Handle "No" checkboxes and their corresponding fields
    $('#no_medications').change(function() {
        if (this.checked) {
            $('#medications').val('NO MEDICATIONS').prop('readonly', true);
        } else {
            $('#medications').val('').prop('readonly', false);
        }
        validateMedications();
        saveFieldStates();
    });

    $('#no_medical_list').change(function() {
        if (this.checked) {
            $('.condition-checkbox').prop('checked', false).prop('disabled', true);
            $('#other_conditions').val('').prop('readonly', true);
            $('.condition-options').addClass('text-muted');
        } else {
            $('.condition-checkbox').prop('disabled', false);
            $('#other_conditions').prop('readonly', false);
            $('.condition-options').removeClass('text-muted');
        }
        updateConditionsHiddenField();
        saveFieldStates();
    });

    $('#no_allergies').change(function() {
        if (this.checked) {
            $('#allergy').val('').prop('readonly', true);
            $('.allergy-input').addClass('text-muted');
        } else {
            $('#allergy').prop('readonly', false);
            $('.allergy-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
        saveFieldStates();
    });

    $('#no_physical_conditions').change(function() {
        if (this.checked) {
            $('#scoliosis').val('').prop('readonly', true);
            $('.physical-input').addClass('text-muted');
        } else {
            $('#scoliosis').prop('readonly', false);
            $('.physical-input').removeClass('text-muted');
        }
        updateConditionsHiddenField();
        saveFieldStates();
    });

    $('#no_problems').change(function() {
        if (this.checked) {
            $('.problem-checkbox').prop('checked', false).prop('disabled', true);
            $('#problem_others_text').val('').prop('readonly', true).hide();
            $('.problem-options').addClass('text-muted');
        } else {
            $('.problem-checkbox').prop('disabled', false);
            $('#problem_others_text').prop('readonly', false);
            $('.problem-options').removeClass('text-muted');
        }
        updateProblemsHiddenField(false);
        saveFieldStates();
    });

    $('#fam_no_problems').change(function() {
        if (this.checked) {
            $('.fam-problem-checkbox').prop('checked', false).prop('disabled', true);
            $('#fam_problem_others_text').val('').prop('readonly', true).hide();
            $('.fam-problem-options').addClass('text-muted');
        } else {
            $('.fam-problem-checkbox').prop('disabled', false);
            $('#fam_problem_others_text').prop('readonly', false);
            $('.fam-problem-options').removeClass('text-muted');
        }
        updateProblemsHiddenField(true);
        saveFieldStates();
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
        saveFieldStates();
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
        saveFieldStates();
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

    // Handle suicide question
    $('input[name="suicide"]').change(function() {
        if (this.value === 'yes') {
            $('#suicide_reason_container').show();
            $('#suicide_reason').prop('readonly', false);
        } else {
            $('#suicide_reason_container').hide();
            $('#suicide_reason').val('').prop('readonly', true);
        }
        saveFieldStates();
    });

    // Handle fitness question
    $('input[name="fitness"]').change(function() {
        if (this.value === 'yes') {
            $('#fitness_specify').prop('disabled', false);
            $('input[name="fitness_frequency"]').prop('disabled', false);
        } else {
            $('#fitness_specify').val('').prop('disabled', true);
            $('input[name="fitness_frequency"]').prop('checked', false).prop('disabled', true);
        }
        saveFieldStates();
    });

    // Update hidden fields for conditions
    function updateConditionsHiddenField() {
        let conditions = [];
        let hasAnyCondition = false;
        
        if ($('#no_medical_list').is(':checked') && 
            !$('#allergy').val().trim() && 
            !$('#scoliosis').val().trim() && 
            $('.condition-checkbox:checked').length === 0 && 
            !$('#other_conditions').val().trim()) {
            conditions.push('NO MEDICAL CONDITIONS');
        } else {
            $('.condition-checkbox:checked').each(function() {
                conditions.push($(this).val());
                hasAnyCondition = true;
            });
            
            let otherConditions = $('#other_conditions').val().trim();
            if (otherConditions) {
                conditions.push('Other: ' + otherConditions);
                hasAnyCondition = true;
            }
            
            let allergy = $('#allergy').val().trim();
            if (allergy && !$('#no_allergies').is(':checked')) {
                conditions.push('Allergy: ' + allergy);
                hasAnyCondition = true;
            }
            
            let scoliosis = $('#scoliosis').val().trim();
            if (scoliosis && !$('#no_physical_conditions').is(':checked')) {
                conditions.push('Scoliosis/Physical condition: ' + scoliosis);
                hasAnyCondition = true;
            }
        }
        
        $('#conditions_hidden').val(conditions.filter(Boolean).join('; '));
    }

    // Update hidden fields for problems (both personal and family)
    function updateProblemsHiddenField(isFamily) {
        const prefix = isFamily ? 'fam_' : '';
        let problems = [];
        
        if ($(`#${prefix}no_problems`).is(':checked')) {
            problems.push('NO PROBLEMS');
        } else {
            $(`.${prefix}problem-checkbox:checked`).each(function() {
                if ($(this).val() === 'Others') {
                    const othersText = $(`#${prefix}problem_others_text`).val().trim();
                    if (othersText) {
                        problems.push('Others: ' + othersText);
                    }
                } else {
                    problems.push($(this).val());
                }
            });
        }
        
        $(`#${prefix}problems_hidden`).val(problems.filter(Boolean).join('; '));
    }

    // Form validation functions
    function validateForm() {
        let isValid = true;
        
        if (!validateMedications()) isValid = false;
        if (!validateConditions()) isValid = false;
        if (!validateSuicide()) isValid = false;
        if (!validateProblems()) isValid = false;
        if (!validateFamilyProblems()) isValid = false;
        if (!validateFitness()) isValid = false;
        if (!validateStress()) isValid = false;
        
        return isValid;
    }

    function validateMedications() {
        if ($('#no_medications').is(':checked') || $('#medications').val().trim()) {
            $('#medications').removeClass('is-invalid');
            return true;
        } else {
            $('#medications').addClass('is-invalid');
            return false;
        }
    }

    function validateConditions() {
        let isValid = true;
        
        if (!$('#no_medical_list').is(':checked') && 
            $('.condition-checkbox:checked').length === 0 && 
            $('#other_conditions').val().trim() === '') {
            $('.medical-conditions-group').addClass('is-invalid');
            isValid = false;
        } else {
            $('.medical-conditions-group').removeClass('is-invalid');
        }
        
        if (!$('#no_allergies').is(':checked') && $('#allergy').val().trim() === '') {
            $('.allergy-group').addClass('is-invalid');
            isValid = false;
        } else {
            $('.allergy-group').removeClass('is-invalid');
        }
        
        if (!$('#no_physical_conditions').is(':checked') && $('#scoliosis').val().trim() === '') {
            $('.physical-condition-group').addClass('is-invalid');
            isValid = false;
        } else {
            $('.physical-condition-group').removeClass('is-invalid');
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

    // Save field states to session storage
    function saveFieldStates() {
        const fieldStates = {
            noMedications: $('#no_medications').is(':checked'),
            medications: $('#medications').val(),
            noMedicalList: $('#no_medical_list').is(':checked'),
            conditionCheckboxes: {},
            otherConditions: $('#other_conditions').val(),
            noAllergies: $('#no_allergies').is(':checked'),
            allergy: $('#allergy').val(),
            noPhysicalConditions: $('#no_physical_conditions').is(':checked'),
            scoliosis: $('#scoliosis').val(),
            suicide: $('input[name="suicide"]:checked').val(),
            suicideReason: $('#suicide_reason').val(),
            noProblems: $('#no_problems').is(':checked'),
            problemCheckboxes: {},
            problemOthersChecked: $('#problem_others').is(':checked'),
            problemOthersText: $('#problem_others_text').val(),
            famNoProblems: $('#fam_no_problems').is(':checked'),
            famProblemCheckboxes: {},
            famProblemOthersChecked: $('#fam_problem_others').is(':checked'),
            famProblemOthersText: $('#fam_problem_others_text').val(),
            fitness: $('input[name="fitness"]:checked').val(),
            fitnessSpecify: $('#fitness_specify').val(),
            fitnessFrequency: $('input[name="fitness_frequency"]:checked').val(),
            stress: $('input[name="stress"]:checked').val(),
            signature: $('#signatureData').val()
        };

        // Save condition checkbox states
        $('.condition-checkbox').each(function() {
            fieldStates.conditionCheckboxes[this.id] = $(this).is(':checked');
        });

        // Save problem checkbox states
        $('.problem-checkbox').each(function() {
            fieldStates.problemCheckboxes[this.id] = $(this).is(':checked');
        });

        // Save family problem checkbox states
        $('.fam-problem-checkbox').each(function() {
            fieldStates.famProblemCheckboxes[this.id] = $(this).is(':checked');
        });

        sessionStorage.setItem('medicalFormStates', JSON.stringify(fieldStates));
    }

    // Apply saved field states
    function applyFieldStates(fieldStates) {
        if (!fieldStates) return;

        // Apply medications state
        if (fieldStates.noMedications) {
            $('#no_medications').prop('checked', true).trigger('change');
            $('#medications').val(fieldStates.medications || 'NO MEDICATIONS');
        } else if (fieldStates.medications) {
            $('#medications').val(fieldStates.medications);
        }

        // Apply medical conditions state
        if (fieldStates.noMedicalList) {
            $('#no_medical_list').prop('checked', true).trigger('change');
        } else {
            for (const [id, checked] of Object.entries(fieldStates.conditionCheckboxes || {})) {
                $(`#${id}`).prop('checked', checked);
            }
            if (fieldStates.otherConditions) {
                $('#other_conditions').val(fieldStates.otherConditions);
            }
        }

        // Apply allergies state
        if (fieldStates.noAllergies) {
            $('#no_allergies').prop('checked', true).trigger('change');
        } else if (fieldStates.allergy) {
            $('#allergy').val(fieldStates.allergy);
        }

        // Apply physical conditions state
        if (fieldStates.noPhysicalConditions) {
            $('#no_physical_conditions').prop('checked', true).trigger('change');
        } else if (fieldStates.scoliosis) {
            $('#scoliosis').val(fieldStates.scoliosis);
        }

        // Apply suicide state
        if (fieldStates.suicide) {
            $(`input[name="suicide"][value="${fieldStates.suicide}"]`).prop('checked', true).trigger('change');
            if (fieldStates.suicideReason) {
                $('#suicide_reason').val(fieldStates.suicideReason);
            }
        }

        // Apply problems state
        if (fieldStates.noProblems) {
            $('#no_problems').prop('checked', true).trigger('change');
        } else {
            for (const [id, checked] of Object.entries(fieldStates.problemCheckboxes || {})) {
                $(`#${id}`).prop('checked', checked);
            }
            if (fieldStates.problemOthersChecked) {
                $('#problem_others').prop('checked', true).trigger('change');
                $('#problem_others_text').val(fieldStates.problemOthersText || '');
            }
        }

        // Apply family problems state
        if (fieldStates.famNoProblems) {
            $('#fam_no_problems').prop('checked', true).trigger('change');
        } else {
            for (const [id, checked] of Object.entries(fieldStates.famProblemCheckboxes || {})) {
                $(`#${id}`).prop('checked', checked);
            }
            if (fieldStates.famProblemOthersChecked) {
                $('#fam_problem_others').prop('checked', true).trigger('change');
                $('#fam_problem_others_text').val(fieldStates.famProblemOthersText || '');
            }
        }

        // Apply fitness state
        if (fieldStates.fitness) {
            $(`input[name="fitness"][value="${fieldStates.fitness}"]`).prop('checked', true).trigger('change');
            if (fieldStates.fitnessSpecify) {
                $('#fitness_specify').val(fieldStates.fitnessSpecify);
            }
            if (fieldStates.fitnessFrequency) {
                $(`input[name="fitness_frequency"][value="${fieldStates.fitnessFrequency}"]`).prop('checked', true);
            }
        }

        // Apply stress state
        if (fieldStates.stress) {
            $(`input[name="stress"][value="${fieldStates.stress}"]`).prop('checked', true);
        }

        // Apply signature if exists
        if (fieldStates.signature) {
            $('#signatureData').val(fieldStates.signature);
        }
    }

    // Handle previous button click
    function handlePrevious() {
        saveFieldStates();
        
        // Enable any disabled fields before collecting form data
        var disabledFields = $('#medicalHistoryForm').find(':disabled').prop('disabled', false);
        var readonlyFields = $('#medicalHistoryForm').find('[readonly]').prop('readonly', false);
        
        // Create form data object
        const formData = new FormData(document.getElementById('medicalHistoryForm'));
        
        // Add signature if exists
        if (!signaturePad.isEmpty()) {
            formData.set('signature', signaturePad.toDataURL());
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

    // Initialize form from session data
    const sessionData = <?php echo isset($_SESSION['medical_history']) ? json_encode($_SESSION['medical_history']) : '{}'; ?>;
    if (sessionData) {
        if (sessionData.no_medications) {
            $('#no_medications').prop('checked', true).trigger('change');
            $('#medications').val('NO MEDICATIONS');
        } else if (sessionData.medications) {
            $('#medications').val(sessionData.medications);
        }

        if (sessionData.no_medical_list) {
            $('#no_medical_list').prop('checked', true).trigger('change');
        } else if (sessionData.conditions) {
            const conditions = Array.isArray(sessionData.conditions) ? 
                sessionData.conditions : 
                sessionData.conditions.split('; ');
            
            conditions.forEach(condition => {
                if (condition.startsWith('Other:')) {
                    $('#other_conditions').val(condition.replace('Other: ', ''));
                } else if (condition.startsWith('Allergy:')) {
                    $('#allergy').val(condition.replace('Allergy: ', ''));
                } else if (condition.startsWith('Scoliosis/Physical condition:')) {
                    $('#scoliosis').val(condition.replace('Scoliosis/Physical condition: ', ''));
                } else {
                    $(`.condition-checkbox[value="${condition}"]`).prop('checked', true);
                }
            });
        }

        if (sessionData.no_allergies) {
            $('#no_allergies').prop('checked', true).trigger('change');
        } else if (sessionData.allergy) {
            $('#allergy').val(sessionData.allergy);
        }

        if (sessionData.no_physical_conditions) {
            $('#no_physical_conditions').prop('checked', true).trigger('change');
        } else if (sessionData.scoliosis) {
            $('#scoliosis').val(sessionData.scoliosis);
        }

        if (sessionData.suicide) {
            $(`input[name="suicide"][value="${sessionData.suicide}"]`).prop('checked', true).trigger('change');
            if (sessionData.suicide_reason) {
                $('#suicide_reason').val(sessionData.suicide_reason);
            }
        }

        if (sessionData.no_problems) {
            $('#no_problems').prop('checked', true).trigger('change');
        } else if (sessionData.problems) {
            const problems = Array.isArray(sessionData.problems) ? 
                sessionData.problems : 
                sessionData.problems.split('; ');
                
            problems.forEach(problem => {
                if (problem.startsWith('Others:')) {
                    $('#problem_others').prop('checked', true);
                    $('#problem_others_text').val(problem.replace('Others: ', '')).show();
                } else {
                    $(`.problem-checkbox[value="${problem}"]`).prop('checked', true);
                }
            });
        }

        if (sessionData.fam_no_problems) {
            $('#fam_no_problems').prop('checked', true).trigger('change');
        } else if (sessionData.fam_problems) {
            const famProblems = Array.isArray(sessionData.fam_problems) ? 
                sessionData.fam_problems : 
                sessionData.fam_problems.split('; ');
                
            famProblems.forEach(problem => {
                if (problem.startsWith('Others:')) {
                    $('#fam_problem_others').prop('checked', true);
                    $('#fam_problem_others_text').val(problem.replace('Others: ', '')).show();
                } else {
                    $(`.fam-problem-checkbox[value="${problem}"]`).prop('checked', true);
                }
            });
        }

        if (sessionData.fitness) {
            $(`input[name="fitness"][value="${sessionData.fitness}"]`).prop('checked', true).trigger('change');
            if (sessionData.fitness_specify) {
                $('#fitness_specify').val(sessionData.fitness_specify);
            }
            if (sessionData.fitness_frequency) {
                $(`input[name="fitness_frequency"][value="${sessionData.fitness_frequency}"]`).prop('checked', true);
            }
        }

        if (sessionData.stress) {
            $(`input[name="stress"][value="${sessionData.stress}"]`).prop('checked', true);
        }

        if (sessionData.signature) {
            $('#signatureData').val(sessionData.signature);
        }
    }

    // Apply saved field states from sessionStorage if available
    const savedFieldStates = sessionStorage.getItem('medicalFormStates');
    if (savedFieldStates) {
        applyFieldStates(JSON.parse(savedFieldStates));
    }

    // Initial updates
    updateConditionsHiddenField();
    updateProblemsHiddenField(false);
    updateProblemsHiddenField(true);
});
</script>
</body>
</html>