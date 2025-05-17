<?php
// Check if a session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php'; 


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $_SESSION['family_background'] = $_POST;
    $student_data = $_POST;

    // Handle "Not Available" for father and mother
    $father_fields = ['fatherName', 'fatherContact', 'fatherOccupation'];
    $mother_fields = ['motherName', 'motherContact', 'motherOccupation'];

    if (isset($student_data['fatherNotAvailable'])) {
        foreach ($father_fields as $field) {
            $student_data[$field] = 'N/A';
        }
    }

    if (isset($student_data['motherNotAvailable'])) {
        foreach ($mother_fields as $field) {
            $student_data[$field] = 'N/A';
        }
    }

    // Handle birth order for only child
    if ($student_data['siblings'] === '0') {
        $student_data['birthOrder'] = 'Only Child';
    }

    // Prepare the UPDATE statement
    $sql = "UPDATE student_profiles SET 
        father_name = ?, father_contact = ?, father_occupation = ?,
        mother_name = ?, mother_contact = ?, mother_occupation = ?,
        guardian_name = ?, guardian_relationship = ?, guardian_contact = ?, guardian_occupation = ?,
        siblings = ?, birth_order = ?, family_income = ?
    WHERE student_id = ?";

    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }

    // Bind parameters
    $stmt->bind_param("ssssssssssssss",
        $student_data['fatherName'],
        $student_data['fatherContact'],
        $student_data['fatherOccupation'],
        $student_data['motherName'],
        $student_data['motherContact'],
        $student_data['motherOccupation'],
        $student_data['guardianName'],
        $student_data['guardianRelationship'],
        $student_data['guardianContact'],
        $student_data['guardianOccupation'],
        $student_data['siblings'],
        $student_data['birthOrder'],
        $student_data['familyIncome'],
        $_SESSION['student_profile']['student_id']
    );

    if ($stmt->execute()) {
        $_SESSION['student_profile'] = array_merge($_SESSION['student_profile'] ?? [], $student_data);
        header("Location: educational_career.php");
        exit;
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Function to get session value or default
function getSessionValue($key, $default = '') {
    return isset($_SESSION['family_background'][$key]) ? $_SESSION['family_background'][$key] : $default;
}
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
    
</head>

<body>
  
    <div class="container mt-5">
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 40%;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">40%</div>
        </div>
        <form method="POST" action="">
            <div class="form-section active" id="section2">
                <h5>Family Background</h5>
                
                <!-- Father's Information -->
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="fatherName">Father's Name:</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="fatherNotAvailable" name="fatherNotAvailable" 
                                <?php echo getSessionValue('fatherNotAvailable') ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="fatherNotAvailable">Not Available</label>
                        </div>
                    </div>
                    <input type="text" class="form-control" id="fatherName" name="fatherName" required 
                        value="<?php echo getSessionValue('fatherName'); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="fatherContact">Father's Contact Number:</label>
                        <input type="tel" class="form-control" id="fatherContact" name="fatherContact" 
                            value="<?php echo getSessionValue('fatherContact'); ?>" placeholder="e.g 09123456789" pattern="[0-9]{11}" maxlength="11">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fatherOccupation">Father's Occupation:</label>
                        <input type="text" class="form-control" id="fatherOccupation" name="fatherOccupation" 
                            value="<?php echo getSessionValue('fatherOccupation'); ?>">
                    </div>
                </div>

                <!-- Mother's Information -->
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="motherName">Mother's Name:</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="motherNotAvailable" name="motherNotAvailable" 
                                <?php echo getSessionValue('motherNotAvailable') ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="motherNotAvailable">Not Available</label>
                        </div>
                    </div>
                    <input type="text" class="form-control" id="motherName" name="motherName" required 
                        value="<?php echo getSessionValue('motherName'); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="motherContact">Mother's Contact Number:</label>
                        <input type="tel" class="form-control" id="motherContact" name="motherContact" 
                            value="<?php echo getSessionValue('motherContact'); ?>" placeholder="e.g 09123456789" pattern="[0-9]{11}" maxlength="11">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="motherOccupation">Mother's Occupation:</label>
                        <input type="text" class="form-control" id="motherOccupation" name="motherOccupation" 
                            value="<?php echo getSessionValue('motherOccupation'); ?>">
                    </div>
                </div>

                <!-- Guardian's Information -->
                <div class="form-group">
                    <label for="guardianName">Guardian's Name:</label>
                    <input type="text" class="form-control" id="guardianName" name="guardianName" required 
                        value="<?php echo getSessionValue('guardianName'); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="guardianRelationship">Guardian's Relationship:</label>
                        <input type="text" class="form-control" id="guardianRelationship" name="guardianRelationship" required 
                            value="<?php echo getSessionValue('guardianRelationship'); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="guardianContact">Guardian's Contact Number:</label>
                        <input type="tel" class="form-control" id="guardianContact" name="guardianContact" required 
                            value="<?php echo getSessionValue('guardianContact'); ?>" 
                            placeholder="e.g 09123456789" pattern="[0-9]{11}" maxlength="11" 
                            title="Please enter a valid 11-digit phone number">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="guardianOccupation">Guardian's Occupation:</label>
                        <input type="text" class="form-control" id="guardianOccupation" name="guardianOccupation" required 
                            value="<?php echo getSessionValue('guardianOccupation'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="siblings">Number of Siblings:</label>
                    <input type="text" class="form-control" id="siblings" name="siblings" pattern="^[0-9]+$" inputmode="numeric" required 
                        value="<?php echo getSessionValue('siblings'); ?>">
                    <small class="form-text text-muted">Enter the number of siblings you have, excluding yourself. Enter 0 if you are an only child.</small>
                </div>

                <!-- Birth Order -->
                <p>Birth Order</p>
                <div class="form-group">
                    <?php $savedBirthOrder = getSessionValue('birthOrder'); ?>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="radioEldest" name="birthOrder" value="Eldest" 
                            <?php echo $savedBirthOrder === 'Eldest' ? 'checked' : ''; ?> onchange="updateBirthOrderValue(1)">
                        <label class="custom-control-label" for="radioEldest">Eldest</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="radioSecond" name="birthOrder" value="Second" 
                            <?php echo $savedBirthOrder === 'Second' ? 'checked' : ''; ?> onchange="updateBirthOrderValue(2)">
                        <label class="custom-control-label" for="radioSecond">Second</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="radioMiddle" name="birthOrder" value="Middle" 
                            <?php echo $savedBirthOrder === 'Middle' ? 'checked' : ''; ?> onchange="updateBirthOrderValue(3)">
                        <label class="custom-control-label" for="radioMiddle">Middle</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="radioYoungest" name="birthOrder" value="Youngest" 
                            <?php echo $savedBirthOrder === 'Youngest' ? 'checked' : ''; ?> onchange="updateBirthOrderValue(4)">
                        <label class="custom-control-label" for="radioYoungest">Youngest</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="radioOnlyChild" name="birthOrder" value="OnlyChild" 
                            <?php echo $savedBirthOrder === 'OnlyChild' ? 'checked' : ''; ?> onchange="updateBirthOrderValue(5)">
                        <label class="custom-control-label" for="radioOnlyChild">Only child</label>
                    </div>
                    <input type="hidden" id="birthOrderValue" name="birthOrderValue" value="<?php echo getSessionValue('birthOrderValue'); ?>">
                </div>


                <!-- Family Income -->
                <p>Estimated Monthly Family Income: (Please select the appropriate option)</p>
                <div class="form-group">
                    <?php $savedIncome = getSessionValue('familyIncome'); ?>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="incomeBelow10000" name="familyIncome" value="below-10,000" 
                            <?php echo $savedIncome === 'below-10,000' ? 'checked' : ''; ?> onchange="updateFamilyIncomeValue(1)">
                        <label class="custom-control-label" for="incomeBelow10000">below-10,000</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="income11000To20000" name="familyIncome" value="11,000 – 20,000" 
                            <?php echo $savedIncome === '11,000 – 20,000' ? 'checked' : ''; ?> onchange="updateFamilyIncomeValue(2)">
                        <label class="custom-control-label" for="income11000To20000">11,000 – 20,000</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="income21000To30000" name="familyIncome" value="21,000 – 30,000" 
                            <?php echo $savedIncome === '21,000 – 30,000' ? 'checked' : ''; ?> onchange="updateFamilyIncomeValue(3)">
                        <label class="custom-control-label" for="income21000To30000">21,000 – 30,000</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="income31000To40000" name="familyIncome" value="31,000 – 40,000" 
                            <?php echo $savedIncome === '31,000 – 40,000' ? 'checked' : ''; ?> onchange="updateFamilyIncomeValue(4)">
                        <label class="custom-control-label" for="income31000To40000">31,000 – 40,000</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="income41000To50000" name="familyIncome" value="41,000 – 50,000" 
                            <?php echo $savedIncome === '41,000 – 50,000' ? 'checked' : ''; ?> onchange="updateFamilyIncomeValue(5)">
                        <label class="custom-control-label" for="income41000To50000">41,000– 50,000</label>
                    </div>
                    <div class="custom-control custom-radio mb-3">
                        <input type="radio" class="custom-control-input" id="incomeAbove50000" name="familyIncome" value="above 50,000" 
                            <?php echo $savedIncome === 'above 50,000' ? 'checked' : ''; ?> onchange="updateFamilyIncomeValue(6)">
                        <label class="custom-control-label" for="incomeAbove50000">above 50,000</label>
                    </div>
                    <input type="hidden" id="familyIncomeValue" name="familyIncomeValue" value="<?php echo getSessionValue('familyIncomeValue'); ?>">
                </div>

                <br>
                <button type="button" class="btn btn-secondary btn-navigation" onclick="window.location.href='personal_info.php'">Previous</button>
                <button type="submit" class="btn btn-primary btn-navigation">Next</button>
            </div>
        </form>
    </div>


    <script>
    document.querySelectorAll('#guardianContact, #fatherContact, #motherContact').forEach(element => {
        element.addEventListener('keypress', function(e) {
            // Allow only number inputs
            var charCode = (e.which) ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                e.preventDefault();
            }
        });
    });

    $(document).ready(function() {
        // Function to handle Not Available checkboxes and field states
        function handleNotAvailable(checkbox, nameField, contactField, occupationField) {
            const $checkbox = $(checkbox);
            const $fields = $(`${nameField}, ${contactField}, ${occupationField}`);

            // Set initial state based on checkbox
            function updateFields() {
                const isChecked = $checkbox.is(':checked');
                $fields.prop('disabled', isChecked);
                if (isChecked) {
                    $fields.val('N/A');
                }
            }

            // Initialize on page load
            updateFields();

            // Handle checkbox changes
            $checkbox.change(updateFields);
        }

        // Function to update birth order options based on siblings
        function updateBirthOrderOptions(siblings) {
        siblings = parseInt(siblings) || 0;
        
        // First disable/enable options based on siblings count
        if (siblings === 0) {
            // Only child scenario
            $('#radioOnlyChild').prop('disabled', false).prop('checked', true);
            $('#radioEldest, #radioSecond, #radioMiddle, #radioYoungest').prop('disabled', true);
        } else {
            // Disable Only Child option when there are siblings
            $('#radioOnlyChild').prop('disabled', true).prop('checked', false);
            $('#radioEldest, #radioYoungest').prop('disabled', false);
            
            if (siblings === 1) {
                // With 1 sibling, only eldest and youngest are valid
                $('#radioSecond, #radioMiddle').prop('disabled', true);
            } else if (siblings === 2) {
                // With 2 siblings, enable second and middle options
                $('#radioMiddle').prop('disabled', false);
                $('#radioSecond').prop('disabled', true);
            } else {
                // More than 2 siblings
                $('#radioSecond').prop('disabled', false);
                if (siblings % 2 !== 0) {
                    $('#radioMiddle').prop('disabled', true);
                } else {
                    $('#radioMiddle').prop('disabled', false);
                }
            }
        }

        // If the currently selected option is disabled, default to Eldest
        if ($('input[name="birthOrder"]:checked').prop('disabled')) {
            $('#radioEldest').prop('checked', true);
        }
    }
        // Initialize Not Available handlers
        handleNotAvailable('#fatherNotAvailable', '#fatherName', '#fatherContact', '#fatherOccupation');
        handleNotAvailable('#motherNotAvailable', '#motherName', '#motherContact', '#motherOccupation');

        // Handle siblings input change
        $('#siblings').on('input', function() {
            updateBirthOrderOptions(this.value);
        });

        // Form validation before submission
        $('form').on('submit', function(e) {
            let siblings = parseInt($('#siblings').val()) || 0;
            let selectedBirthOrder = $('input[name="birthOrder"]:checked').val();
            let isValid = true;

            if (selectedBirthOrder === 'Middle' && siblings % 2 !== 0) {
                e.preventDefault();
                alert('Middle birth order is not valid for an odd number of siblings');
                return false;
            }
            
            if ((selectedBirthOrder === 'Second' || selectedBirthOrder === 'Middle') && siblings === 1) {
                e.preventDefault();
                alert('Invalid birth order selection for number of siblings');
                return false;
            }

            $('input[required]').each(function() {
                if (!$(this).val() && !$(this).prop('disabled')) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!$('input[name="birthOrder"]:checked').val()) {
                isValid = false;
                alert('Please select a birth order');
            }

            if (!$('input[name="familyIncome"]:checked').val()) {
                isValid = false;
                alert('Please select an estimated monthly family income');
            }

            if (!isValid) {
                e.preventDefault();
                alert('Please fill all required fields');
            }
        });

        // Siblings input validation
        $('#siblings').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        $('#siblings').on('keypress', function(e) {
            var charCode = (e.which) ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        });

        // Save form state on any change
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', () => {
                const formData = new FormData(form);
                const formState = {
                    fields: Object.fromEntries(formData),
                    disabledStates: {
                        father: $('#fatherNotAvailable').is(':checked'),
                        mother: $('#motherNotAvailable').is(':checked')
                    }
                };
                sessionStorage.setItem('familyBackgroundForm', JSON.stringify(formState));
            });
        });

        // Initial form load
        const savedData = sessionStorage.getItem('familyBackgroundForm');
        if (savedData) {
            const formState = JSON.parse(savedData);
            
            // First update birth order options based on siblings
            const siblings = parseInt(formState.fields.siblings) || 0;
            updateBirthOrderOptions(siblings);
            
            // Then restore all field values
            Object.entries(formState.fields).forEach(([name, value]) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) {
                    if (input.type === 'radio') {
                        const radio = form.querySelector(`[name="${name}"][value="${value}"]`);
                        if (radio && !radio.disabled) {
                            radio.checked = true;
                        }
                    } else if (input.type === 'checkbox') {
                        input.checked = value === 'on';
                    } else {
                        input.value = value;
                    }
                }
            });

            // Finally, restore parent availability states
            if (formState.disabledStates) {
                if (formState.disabledStates.father) {
                    $('#fatherNotAvailable').prop('checked', true).trigger('change');
                }
                if (formState.disabledStates.mother) {
                    $('#motherNotAvailable').prop('checked', true).trigger('change');
                }
            }

            // Double-check birth order options after all restoration
            updateBirthOrderOptions(siblings);
        }
    });

    function updateBirthOrderValue(value) {
        document.getElementById('birthOrderValue').value = value;
    }

    function updateFamilyIncomeValue(value) {
        document.getElementById('familyIncomeValue').value = value;
    }
</script>
</body>
</html>