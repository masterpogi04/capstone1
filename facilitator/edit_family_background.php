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
        <h1>Edit Student Profile Form</h1>
    </div>

     <div class="content">
        <form id="editFamilyBackgroundForm" method="POST">
            <h2>Family Background</h2>
            
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