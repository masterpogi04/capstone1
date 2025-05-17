<?php
session_start();
include '../db.php';

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
    // Fields that can be updated
    $updateFields = [
        'father_name', 'father_contact', 'father_occupation',
        'mother_name', 'mother_contact', 'mother_occupation',
        'guardian_name', 'guardian_relationship', 'guardian_contact', 'guardian_occupation',
        'family_income'
    ];
    
    $updateData = [];
    $types = "";
    foreach ($updateFields as $field) {
        if (isset($_POST[$field])) {
            $updateData[$field] = $_POST[$field];
            $types .= "s"; // Assuming all fields are strings. Adjust if needed.
        }
    }
    
    if (!empty($updateData)) {
        $sql = "UPDATE student_profiles SET " . implode(" = ?, ", array_keys($updateData)) . " = ? WHERE student_id = ?";
        $stmt = $connection->prepare($sql);
        
        if ($stmt) {
            $params = array_values($updateData);
            $params[] = $student_id;
            $types .= "s"; // for student_id
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Family background information updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating information: " . $stmt->error;
            }
        } else {
            $_SESSION['error_message'] = "Error preparing statement: " . $connection->error;
        }
    }

    header("Location: edit_educational_career.php?student_id=" . $student_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Family Background</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" type="text/css" href="edit_student_profile_form.css">
</head>
<body>
    <div class="header">
        <h1>Edit Student Family Background</h1>
    </div>

    <div class="container mt-5">
        <form id="editFamilyBackgroundForm" method="POST">
            <h5>Family Background</h5>
            
            <!-- Father's Information -->
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="father_name">Father's Name:</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="fatherNotAvailable" <?php echo ($student['father_name'] == 'N/A') ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="fatherNotAvailable">Not Available</label>
                    </div>
                </div>
                <input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="father_contact">Father's Contact Number:</label>
                    <input type="tel" class="form-control" id="father_contact" name="father_contact" 
                           value="<?php echo htmlspecialchars($student['father_contact'] ?? ''); ?>"
                           pattern="[0-9]{11}" maxlength="11">
                    <small class="form-text text-muted">Please enter an 11-digit phone number.</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="father_occupation">Father's Occupation:</label>
                    <input type="text" class="form-control" id="father_occupation" name="father_occupation" value="<?php echo htmlspecialchars($student['father_occupation'] ?? ''); ?>">
                </div>
            </div>

            <!-- Mother's Information -->
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="mother_name">Mother's Name:</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="motherNotAvailable" <?php echo ($student['mother_name'] == 'N/A') ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="motherNotAvailable">Not Available</label>
                    </div>
                </div>
                <input type="text" class="form-control" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="mother_contact">Mother's Contact Number:</label>
                    <input type="tel" class="form-control" id="mother_contact" name="mother_contact" 
                           value="<?php echo htmlspecialchars($student['mother_contact'] ?? ''); ?>"
                           pattern="[0-9]{11}" maxlength="11">
                    <small class="form-text text-muted">Please enter an 11-digit phone number.</small>
                </div>
                <div class="form-group col-md-6">
                    <label for="mother_occupation">Mother's Occupation:</label>
                    <input type="text" class="form-control" id="mother_occupation" name="mother_occupation" value="<?php echo htmlspecialchars($student['mother_occupation'] ?? ''); ?>">
                </div>
            </div>

            <!-- Guardian's Information -->
            <div class="form-group">
                <label for="guardian_name">Guardian's Name:</label>
                <input type="text" class="form-control" id="guardian_name" name="guardian_name" value="<?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?>">
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="guardian_relationship">Guardian's Relationship:</label>
                    <input type="text" class="form-control" id="guardian_relationship" name="guardian_relationship" value="<?php echo htmlspecialchars($student['guardian_relationship'] ?? ''); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="guardian_contact">Guardian's Contact Number:</label>
                    <input type="tel" class="form-control" id="guardian_contact" name="guardian_contact" 
                           value="<?php echo htmlspecialchars($student['guardian_contact'] ?? ''); ?>"
                           pattern="[0-9]{11}" maxlength="11">
                    <small class="form-text text-muted">Please enter an 11-digit phone number.</small>
                </div>
                <div class="form-group col-md-4">
                    <label for="guardian_occupation">Guardian's Occupation:</label>
                    <input type="text" class="form-control" id="guardian_occupation" name="guardian_occupation" value="<?php echo htmlspecialchars($student['guardian_occupation'] ?? ''); ?>">
                </div>
            </div>

            <!-- Read-only fields -->
            <div class="form-group">
                <label for="siblings">Number of Siblings:</label>
                <input type="text" class="form-control" id="siblings" name="siblings" value="<?php echo htmlspecialchars($student['siblings'] ?? ''); ?>" readonly>
            </div>

            <div class="form-group">
                <label>Birth Order:</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['birth_order'] ?? ''); ?>" readonly>
            </div>

            <!-- Family Income -->
            <div class="form-group">
                <label for="family_income">Estimated Monthly Family Income:</label>
                <select class="form-control" id="family_income" name="family_income" required>
                    <option value="below-10,000" <?php echo ($student['family_income'] == 'below-10,000') ? 'selected' : ''; ?>>Below 10,000</option>
                    <option value="11,000 – 20,000" <?php echo ($student['family_income'] == '11,000 – 20,000') ? 'selected' : ''; ?>>11,000 - 20,000</option>
                    <option value="21,000 – 30,000" <?php echo ($student['family_income'] == '21,000 – 30,000') ? 'selected' : ''; ?>>21,000 - 30,000</option>
                    <option value="31,000 – 40,000" <?php echo ($student['family_income'] == '31,000 – 40,000') ? 'selected' : ''; ?>>31,000 - 40,000</option>
                    <option value="41,000 – 50,000" <?php echo ($student['family_income'] == '41,000 – 50,000') ? 'selected' : ''; ?>>41,000 - 50,000</option>
                    <option value="above 50,000" <?php echo ($student['family_income'] == 'above 50,000') ? 'selected' : ''; ?>>Above 50,000</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Save and Continue</button>
            <a href="edit_personal_info.php?student_id=<?php echo $student_id; ?>" class="btn btn-secondary">Back</a>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        // Function to handle parent not available
        function handleNotAvailable(checkbox, nameField, contactField, occupationField) {
            if (checkbox.is(':checked')) {
                nameField.val('N/A').prop('readonly', true);
                contactField.val('N/A').prop('readonly', true);
                occupationField.val('N/A').prop('readonly', true);
            } else {
                nameField.val('').prop('readonly', false);
                contactField.val('').prop('readonly', false);
                occupationField.val('').prop('readonly', false);
            }
        }

        // Father not available handler
        $('#fatherNotAvailable').change(function() {
            handleNotAvailable(
                $(this),
                $('#father_name'),
                $('#father_contact'),
                $('#father_occupation')
            );
        });

        // Mother not available handler
        $('#motherNotAvailable').change(function() {
            handleNotAvailable(
                $(this),
                $('#mother_name'),
                $('#mother_contact'),
                $('#mother_occupation')
            );
        });

        // Contact number validation
        $('input[type="tel"]').on('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
        });

        // Form validation
        $('#editFamilyBackgroundForm').on('submit', function(e) {
            var isValid = true;

            // Validate contact numbers
            $('input[type="tel"]').each(function() {
                if ($(this).val() !== 'N/A' && $(this).val() !== '' && $(this).val().length !== 11) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                    alert('Contact numbers must be 11 digits');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Initial check for parent availability
        if ($('#fatherNotAvailable').is(':checked')) {
            handleNotAvailable(
                $('#fatherNotAvailable'),
                $('#father_name'),
                $('#father_contact'),
                $('#father_occupation')
            );
        }
        if ($('#motherNotAvailable').is(':checked')) {
            handleNotAvailable(
                $('#motherNotAvailable'),
                $('#mother_name'),
                $('#mother_contact'),
                $('#mother_occupation')
            );
        }
    });
    </script>
</body>
</html>