<?php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    header("Location: view_profiles.php");
    exit();
}

// Fetch student data
$stmt = $connection->prepare("
    SELECT sp.*, s.department_id, s.course_id, d.name as department_name, c.name as course_name,
           ts.email as student_email, ts.gender as student_gender
    FROM student_profiles sp
    JOIN tbl_student ts ON sp.student_id = ts.student_id
    JOIN sections s ON ts.section_id = s.id
    JOIN departments d ON s.department_id = d.id
    JOIN courses c ON s.course_id = c.id
    WHERE sp.student_id = ?
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle spouse information
    $spouse_name = null;
    $spouse_occupation = null;
    if ($_POST['civilStatus'] === 'Married') {
        $spouse_name = $_POST['spouseName'] ?? null;
        $spouse_occupation = $_POST['spouseOccupation'] ?? null;
    }

    // Update student profile
    $sql = "UPDATE student_profiles SET 
        last_name = ?, 
        first_name = ?, 
        middle_name = ?, 
        permanent_address = ?, 
        current_address = ?, 
        contact_number = ?, 
        birthdate = ?, 
        age = ?, 
        birthplace = ?, 
        nationality = ?, 
        religion = ?, 
        civil_status = ?, 
        year_level = ?, 
        semester_first_enrolled = ?,
        spouse_name = ?,
        spouse_occupation = ?
        WHERE student_id = ?";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param("sssssssssssssssss",
        $_POST['last_name'],
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['permanent_address'],
        $_POST['current_address'],
        $_POST['contactNumber'],
        $_POST['birthdate'],
        $_POST['age'],
        $_POST['birthplace'],
        $_POST['nationality'],
        $_POST['religion'],
        $_POST['civilStatus'],
        $_POST['year_level'],
        $_POST['semester'],
        $spouse_name,
        $spouse_occupation,
        $student_id
    );

    if ($stmt->execute()) {
            header("Location: edit_family_background.php?student_id=" . $student_id);
            exit;
        } else {
            $error = "Error updating profile: " . $stmt->error;
        }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Profile - Personal Information</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
     <link rel="stylesheet" type="text/css" href="edit_student_profile_form.css">
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
   
</head>
<body>
    <div class="header">
        <h1>Edit Student Profile Form</h1>
    </div>
    <div class="content">
        <h2>Personal Information</h2>
        <form id="editPersonalInfoForm" method="POST">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="last_name">Last Name:</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($student['last_name']); ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="first_name">First Name:</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($student['first_name']); ?>" readonly>
                </div>
                <div class="form-group col-md-4">
                    <label for="middle_name">Middle Name:</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name" 
                           value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>" readonly>
                </div>
            </div>

            <!-- Address Information -->
            <div class="form-group">
                <label for="permanent_address">Permanent Address:</label>
                <input type="text" class="form-control" id="permanent_address" name="permanent_address" 
                       value="<?php echo htmlspecialchars($student['permanent_address']); ?>" required>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="sameAsPermAddress" name="sameAsPermAddress">
                    <label class="form-check-label" for="sameAsPermAddress">
                        Current address is the same as permanent address
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="current_address">Current Address:</label>
                <input type="text" class="form-control" id="current_address" name="current_address" 
                       value="<?php echo htmlspecialchars($student['current_address']); ?>">
            </div>

            <!-- Contact Information -->
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="contactNumber">Contact Number:</label>
                    <input type="tel" class="form-control" id="contactNumber" name="contactNumber" 
                           value="<?php echo htmlspecialchars($student['contact_number']); ?>"
                           required pattern="[0-9]{11}" maxlength="11">
                </div>
                <div class="form-group col-md-6">
                    <label for="email">Email Address:</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($student['student_email']); ?>" readonly>
                </div>
            </div>

           <div class="form-group">
                    <label for="gender">Sex:</label>
                    <input type="text" class="form-control" id="gender" name="gender" value="<?php echo $student['gender'] ?? ''; ?>" readonly>
                </div>
               <div class="form-group">
                    <label for="birthdate">Date of Birth: *</label>
                    <input type="date" 
                           class="form-control" 
                           id="birthdate" 
                           name="birthdate" 
                           value="<?php echo htmlspecialchars($student['birthdate'] ?? ''); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="text" 
                           class="form-control" 
                           id="age" 
                           name="age" 
                           value="<?php echo htmlspecialchars($student['age'] ?? ''); ?>" 
                           readonly>
                </div>
                <div class="form-group">
                    <label for="birthplace">Place of Birth: *</label>
                    <input type="text" 
                           class="form-control" 
                           id="birthplace" 
                           name="birthplace" 
                           value="<?php echo htmlspecialchars($student['birthplace'] ?? ''); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="nationality">Nationality: *</label>
                    <input type="text" 
                           class="form-control" 
                           id="nationality" 
                           name="nationality" 
                           value="<?php echo htmlspecialchars($student['nationality'] ?? ''); ?>" 
                           required>
                </div>

            <div class="form-group">
                <label for="religion">Religion: *</label>
                <select class="form-control" id="religion" name="religion" required>
                    <option value="">Select</option>
                    <option value="Catholic" <?php echo ($student['religion'] ?? '') === 'Catholic' ? 'selected' : ''; ?>>Catholic</option>
                    <option value="Christianity" <?php echo ($student['religion'] ?? '') === 'Christianity' ? 'selected' : ''; ?>>Christianity</option>
                    <option value="Islam" <?php echo ($student['religion'] ?? '') === 'Islam' ? 'selected' : ''; ?>>Islam</option>
                    <option value="Iglesia ni Cristo" <?php echo ($student['religion'] ?? '') === 'Iglesia ni Cristo' ? 'selected' : ''; ?>>Iglesia ni Cristo</option>
                    <option value="Buddhism" <?php echo ($student['religion'] ?? '') === 'Buddhism' ? 'selected' : ''; ?>>Buddhism</option>
                    <option value="Hinduism" <?php echo ($student['religion'] ?? '') === 'Hinduism' ? 'selected' : ''; ?>>Hinduism</option>
                    <option value="Judaism" <?php echo ($student['religion'] ?? '') === 'Judaism' ? 'selected' : ''; ?>>Judaism</option>
                    <option value="Aglipayan" <?php echo ($student['religion'] ?? '') === 'Aglipayan' ? 'selected' : ''; ?>>Aglipayan</option>
                    <option value="Evangelical" <?php echo ($student['religion'] ?? '') === 'Evangelical' ? 'selected' : ''; ?>>Evangelical</option>
                    <option value="Jehovah's Witnesses" <?php echo ($student['religion'] ?? '') === "Jehovah's Witnesses" ? 'selected' : ''; ?>>Jehovah's Witnesses</option>
                    <option value="Seventh-day Adventist" <?php echo ($student['religion'] ?? '') === 'Seventh-day Adventist' ? 'selected' : ''; ?>>Seventh-day Adventist</option>
                    <option value="Other" <?php echo ($student['religion'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div id="otherReligionField" style="display: <?php echo ($student['religion'] ?? '') === 'Other' ? 'block' : 'none'; ?>;">
                <label for="otherReligion">Please specify your religion:</label>
                <input type="text" 
                       class="form-control" 
                       id="otherReligion" 
                       name="otherReligion" 
                       value="<?php echo htmlspecialchars($student['otherReligion'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="civilStatus">Civil Status: *</label>
                <select class="form-control" id="civilStatus" name="civilStatus" required>
                    <option value="">Select</option>
                    <option value="Single" <?php echo ($student['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="Married" <?php echo ($student['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                    <option value="Widowed" <?php echo ($student['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    <option value="Divorced" <?php echo ($student['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                    <option value="Separated" <?php echo ($student['civil_status'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                </select>
            </div>

            <div id="spouseInfoFields" style="display: <?php echo ($student['civil_status'] ?? '') === 'Married' ? 'block' : 'none'; ?>">
                <div class="form-group">
                    <label for="spouseName">Spouse's Name:</label>
                    <input type="text" 
                           class="form-control" 
                           id="spouseName" 
                           name="spouseName" 
                           value="<?php echo htmlspecialchars($student['spouse_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="spouseOccupation">Spouse's Occupation:</label>
                    <input type="text" 
                           class="form-control" 
                           id="spouseOccupation" 
                           name="spouseOccupation" 
                           value="<?php echo htmlspecialchars($student['spouse_occupation'] ?? ''); ?>">
                </div>
            </div>

            <!-- Hidden fields for original department and course -->
                <input type="hidden" name="department" value="<?php echo $student['department_name'] ?? ''; ?>">
                <input type="hidden" name="course" value="<?php echo $student['course_name'] ?? ''; ?>">
                <div class="form-group">
                    <label for="department_display">Department:</label>
                    <input type="text" class="form-control" id="department_display" value="<?php echo $student['department_name'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="course_display">Course:</label>
                    <input type="text" class="form-control" id="course_display" value="<?php echo $student['course_name'] ?? ''; ?>" readonly>
                </div>
                
                <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="year_level">Year Level: *</label>
                    <select class="form-control" id="year_level" name="year_level" required>
                        <option value="">Select Year Level</option>
                        <option value="First Year" <?php echo ($student['year_level'] ?? '') === 'First Year' ? 'selected' : ''; ?>>First Year</option>
                        <option value="Second Year" <?php echo ($student['year_level'] ?? '') === 'Second Year' ? 'selected' : ''; ?>>Second Year</option>
                        <option value="Third Year" <?php echo ($student['year_level'] ?? '') === 'Third Year' ? 'selected' : ''; ?>>Third Year</option>
                        <option value="Fourth Year" <?php echo ($student['year_level'] ?? '') === 'Fourth Year' ? 'selected' : ''; ?>>Fourth Year</option>
                        <option value="Fifth Year" <?php echo ($student['year_level'] ?? '') === 'Fifth Year' ? 'selected' : ''; ?>>Fifth Year</option>
                        <option value="Irregular" <?php echo ($student['year_level'] ?? '') === 'Irregular' ? 'selected' : ''; ?>>Irregular</option>
                    </select>
                </div>
                 <div class="form-group col-md-6">
                    <label for="student_id">Student Number:</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo $student['student_id'] ?? ''; ?>" readonly>
                </div>
                </div>

               <div class="form-group">
                <label>Semester and School Year You First Enrolled CvSU:</label>
                <input type="text" class="form-control" id="semester" name="semester" 
                       value="<?php echo htmlspecialchars($student['semester_first_enrolled']); ?>" readonly>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="view_student_profile.php?student_id=<?php echo $student_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle same as permanent address checkbox
            $('#sameAsPermAddress').change(function() {
                if (this.checked) {
                    $('#current_address').val($('#permanent_address').val());
                    $('#current_address').prop('readonly', true);
                } else {
                    $('#current_address').prop('readonly', false);
                }
            });

            // Update current address when permanent address changes (if checkbox is checked)
            $('#permanent_address').on('input', function() {
                if ($('#sameAsPermAddress').is(':checked')) {
                    $('#current_address').val($(this).val());
                }
            });

            // Contact number validation
            $('#contactNumber').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
            });

            // Age calculation from birthdate
            $('#birthdate').change(function() {
                const birthdate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthdate.getFullYear();
                const monthDiff = today.getMonth() - birthdate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                    age--;
                }

                if (confirm(`Are you ${age} years old?`)) {
                    $('#age').val(age);
                } else {
                    this.value = '';
                    $('#age').val('');
                }
            });

            // Civil status handler
            $('#civilStatus').change(function() {
                if ($(this).val() === 'Married') {
                    $('#spouseInfoFields').show();
                } else {
                    $('#spouseInfoFields').hide();
                }
            });

        });
        document.getElementById('birthdate').addEventListener('change', function() {
            const birthdate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthdate.getFullYear();
            const monthDiff = today.getMonth() - birthdate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }

            const confirmed = confirm(`Are you ${age} years old?`);
            if (!confirmed) {
                this.value = ''; // Clear the input if not confirmed
            } else {
                document.getElementById('age').value = age;
            }
        });

        $(document).ready(function() {
  
        $('#religion').change(function() {
            if ($(this).val() === 'Other') {
                $('#otherReligionField').show();
            } else {
                $('#otherReligionField').hide();
                $('#otherReligion').val('');
            }
        });

        // If "Other" is selected on page load, show the other religion field
        if ($('#religion').val() === 'Other') {
            $('#otherReligionField').show();
        }
    });
        $(document).ready(function() {
    
        $('#civilStatus').change(function() {
            if ($(this).val() === 'Married') {
                $('#spouseInfoFields').show();
            } else {
                $('#spouseInfoFields').hide();
                $('#spouseName').val('');
                $('#spouseOccupation').val('');
            }
        });

       
        if ($('#civilStatus').val() === 'Married') {
            $('#spouseInfoFields').show();
        }
    });
    </script>
</body>
</html>