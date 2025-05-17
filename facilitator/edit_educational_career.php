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
    // Process form submission
    $elementary = $_POST['elementary'];
    $secondary = $_POST['secondary'];
    $transferee = $_POST['transferee'];
    
    // Handle course factors
    $course_factors = isset($_POST['factors']) ? $_POST['factors'] : [];
    if (in_array('Other', $course_factors) && isset($_POST['otherReason']) && !empty($_POST['otherReason'])) {
        $course_factors = array_diff($course_factors, ['Other']);
        $course_factors[] = "Other: " . $_POST['otherReason'];
    } else {
        $course_factors = array_diff($course_factors, ['Other']);
    }
    $course_factors_string = implode("; ", $course_factors);

    // Handle career concerns
    $career_concerns = isset($_POST['careerConcerns']) ? $_POST['careerConcerns'] : [];
    
    // Process each career concern
    foreach ($career_concerns as $key => $concern) {
        if ($concern === 'I need more information about certain course/s and occupation/s' && 
            isset($_POST['courseInfo']) && 
            !empty($_POST['courseInfo'])) {
            $career_concerns[$key] = $concern . ': ' . $_POST['courseInfo'];
        }
    }

    if (in_array('Others', $career_concerns) && isset($_POST['otherCareerConcern']) && !empty($_POST['otherCareerConcern'])) {
        $career_concerns = array_diff($career_concerns, ['Others']);
        $career_concerns[] = "Others: " . $_POST['otherCareerConcern'];
    } else {
        $career_concerns = array_diff($career_concerns, ['Others']);
    }
    $career_concerns_string = implode("; ", $career_concerns);

    // Update the database
    $update_stmt = $connection->prepare("
        UPDATE student_profiles SET
        elementary = ?, secondary = ?, transferees = ?, course_factors = ?, career_concerns = ?
        WHERE student_id = ?
    ");
    $update_stmt->bind_param("ssssss", 
        $elementary, $secondary, $transferee, $course_factors_string, $career_concerns_string, $student_id
    );
    
    if ($update_stmt->execute()) {
        header("Location: edit_medical_history.php?student_id=" . $student_id);
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating educational and career information.";
    }
}

// Prepare data for form
$existing_factors = array_map('trim', explode(";", $student['course_factors'] ?? ''));
$existing_concerns = array_map('trim', explode(";", $student['career_concerns'] ?? ''));

$other_factor = '';
$other_concern = '';
$course_info = '';

// Extract "Other" values and course info if they exist
foreach ($existing_factors as $key => $factor) {
    if (strpos($factor, 'Other:') === 0) {
        $other_factor = trim(substr($factor, 6));
        unset($existing_factors[$key]);
    }
}

foreach ($existing_concerns as $key => $concern) {
    if (strpos($concern, 'Others:') === 0) {
        $other_concern = trim(substr($concern, 7));
        unset($existing_concerns[$key]);
    } elseif (strpos($concern, 'I need more information about certain course/s and occupation/s:') === 0) {
        $course_info = trim(substr($concern, strlen('I need more information about certain course/s and occupation/s:')));
        $existing_concerns[$key] = 'I need more information about certain course/s and occupation/s';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Educational and Career Information</title>
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
        <h1>Edit Student Form</h1>
    </div>

<div class="content">
        <form id="editEducationalCareerForm" method="POST">
            <h2>Educational Background</h2>
            
            <div class="form-group">
                <label for="elementary">Elementary Education:</label>
                <input type="text" 
                       class="form-control" 
                       id="elementary" 
                       name="elementary" 
                       placeholder="School Name; City/Municipality; Year Graduated"
                       title="Please use semicolon (;) to separate the School Name, City/Municipality, and Year Graduated. Example: BLDA; Indang, Cavite; 2011"
                       value="<?php echo htmlspecialchars($student['elementary'] ?? ''); ?>" 
                       required>
                <small class="form-text text-muted">Format: School Name; City/Municipality; Year Graduated. Use semicolon (;) as separator.</small>
            </div>

            <div class="form-group">
                <label for="secondary">Secondary Education:</label>
                <input type="text" 
                       class="form-control" 
                       id="secondary" 
                       name="secondary" 
                       placeholder="School Name; City/Municipality; Year Graduated"
                       title="Please use semicolon (;) to separate the School Name, City/Municipality, and Year Graduated. Example: OCT; Tagaytay, Cavite; 2019"
                       value="<?php echo htmlspecialchars($student['secondary'] ?? ''); ?>" 
                       required>
                <small class="form-text text-muted">Format: School Name; City/Municipality; Year Graduated. Use semicolon (;) as separator.</small>
            </div>

            <div class="form-group">
                <label for="transferee">For transferees:</label>
                <input type="text" 
                       class="form-control" 
                       id="transferee" 
                       name="transferee" 
                       placeholder="School Name; City/Municipality; Course (if applicable)"
                       title="If transferee, please use semicolon (;) to separate the School Name, City/Municipality, and Course. Example: PUP; Manila; Computer Science"
                       value="<?php echo htmlspecialchars($student['transferees'] ?? ''); ?>">
                <small class="form-text text-muted">If transferee, format: School Name; City/Municipality; Course. Use semicolon (;) as separator.</small>
            </div>

            <h5>Career Exploration Information</h5>
            <p>What factors have influenced you most in choosing your course? Check at least three.</p>
            <div class="form-check">
                <?php
                $factors = [
                    'Financial Security after graduation',
                    'Childhood Dream',
                    'Leisure/Enjoyment',
                    'Parents Decision/Choice',
                    'Status Recognition',
                    'Independence',
                    'Opportunity to help others/society',
                    'Challenge/Adventure',
                    'Location of School',
                    'Pursuit of Knowledge',
                    'Moral Fulfilment',
                    'Peer Influence'
                ];
                
                foreach ($factors as $factor) {
                    $isChecked = in_array($factor, array_map('trim', $existing_factors)) ? 'checked' : '';
                    echo "<div class='custom-control custom-checkbox mb-3'>
                            <input type='checkbox' class='custom-control-input course-factor' 
                                   id='" . str_replace(' ', '', $factor) . "' 
                                   name='factors[]' 
                                   value='$factor' 
                                   $isChecked>
                            <label class='custom-control-label' for='" . str_replace(' ', '', $factor) . "'>$factor</label>
                          </div>";
                }
                ?>
                <div class="custom-control custom-checkbox custom-control-other mb-3">
                    <input type="checkbox" class="custom-control-input course-factor" 
                           id="customCheckOther" 
                           name="factors[]" 
                           value="Other" 
                           <?php echo !empty($other_factor) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="customCheckOther">Other reason/s:</label>
                    <input type="text" class="form-control mt-2" 
                           id="otherReason" 
                           name="otherReason" 
                           placeholder="Please specify" 
                           value="<?php echo htmlspecialchars($other_factor); ?>"
                           <?php echo empty($other_factor) ? 'style="display:none;"' : ''; ?>>
                </div>
            </div>

            <h5>Current Career Concerns</h5>
            <p>Please check the current career concerns that you may be experiencing or wish to be addressed in the future. You may check more than one option.</p>
            <div class="form-check">
                <?php
                $concerns = [
                    'I need more information about my personal traits, interests, skills, and values',
                    'I need more information about certain course/s and occupation/s',
                    'I have difficulty making a career decision/goal-setting',
                    'I have many goals that conflict with each other',
                    'My parents have different goals for me',
                    'I think I am not capable of anything',
                    'I know what I want, but someone else thinks I should do something else',
                    'I dont know and I am not sure what to do after graduation'
                ];
                
                foreach ($concerns as $concern) {
                    $isChecked = in_array($concern, array_map('trim', $existing_concerns)) ? 'checked' : '';
                    
                    if ($concern === 'I need more information about certain course/s and occupation/s') {
                        echo "<div class='custom-control custom-checkbox mb-3'>
                                <input type='checkbox' class='custom-control-input career-concern' 
                                       id='" . str_replace(' ', '', $concern) . "' 
                                       name='careerConcerns[]' 
                                       value='$concern' 
                                       $isChecked>
                                <label class='custom-control-label' for='" . str_replace(' ', '', $concern) . "'>$concern</label>
                                <input type='text' class='form-control mt-2' 
                                       id='courseInfo' 
                                       name='courseInfo' 
                                       placeholder='Please specify which course/s or occupation/s'
                                       value='" . htmlspecialchars($course_info) . "'
                                       " . ($isChecked ? '' : 'style="display:none;"') . ">
                              </div>";
                    } else {
                        echo "<div class='custom-control custom-checkbox mb-3'>
                                <input type='checkbox' class='custom-control-input career-concern' 
                                       id='" . str_replace(' ', '', $concern) . "' 
                                       name='careerConcerns[]' 
                                       value='$concern' 
                                       $isChecked>
                                <label class='custom-control-label' for='" . str_replace(' ', '', $concern) . "'>$concern</label>
                              </div>";
                    }
                }
                ?>
                <div class="custom-control custom-checkbox custom-control-other mb-3">
                    <input type="checkbox" class="custom-control-input career-concern" 
                           id="careerCheckOther" 
                           name="careerConcerns[]" 
                           value="Others" 
                           <?php echo !empty($other_concern) ? 'checked' : ''; ?>>
                    <label class="custom-control-label" for="careerCheckOther">Others: </label>
                    <input type="text" class="form-control mt-2" 
                           id="otherCareerConcern" 
                           name="otherCareerConcern" 
                           placeholder="Please specify" 
                           value="<?php echo htmlspecialchars($other_concern); ?>"
                           <?php echo empty($other_concern) ? 'style="display:none;"' : ''; ?>>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save and Continue</button>
            <a href="edit_family_background.php?student_id=<?php echo $student_id; ?>" class="btn btn-secondary">Back</a>
        </form>
    </div>

    <script>
    $(document).ready(function() {
    // Toggle visibility of "Other" input fields
    $('#customCheckOther').change(function() {
        $('#otherReason').toggle(this.checked);
        if (!this.checked) {
            $('#otherReason').val('');
        }
    });

    $('#careerCheckOther').change(function() {
        $('#otherCareerConcern').toggle(this.checked);
        if (!this.checked) {
            $('#otherCareerConcern').val('');
        }
    });

    // Toggle course info field - Fixed selector
    $('input[value="I need more information about certain course/s and occupation/s"]').change(function() {
        $('#courseInfo').toggle(this.checked);
        if (!this.checked) {
            $('#courseInfo').val('');
        }
    });

    // Show/hide "Other" inputs on page load
    $('#otherReason').toggle($('#customCheckOther').is(':checked'));
    $('#otherCareerConcern').toggle($('#careerCheckOther').is(':checked'));
    $('#courseInfo').toggle($('input[value="I need more information about certain course/s and occupation/s"]').is(':checked'));

    // Form validation
    $('#editEducationalCareerForm').on('submit', function(e) {
        var factorsChecked = $('.course-factor:checked').length;
        var concernsChecked = $('.career-concern:checked').length;

        // Don't count "Other" if its text field is empty
        if ($('#customCheckOther').is(':checked') && !$('#otherReason').val().trim()) {
            factorsChecked--;
        }

        if ($('#careerCheckOther').is(':checked') && !$('#otherCareerConcern').val().trim()) {
            concernsChecked--;
        }

        if (factorsChecked < 3) {
            e.preventDefault();
            alert('Please select at least three factors influencing your course choice.');
            return false;
        }

        // Validate "Other" fields if checked
        if ($('#customCheckOther').is(':checked') && !$('#otherReason').val().trim()) {
            e.preventDefault();
            alert('Please specify the other reason for choosing your course.');
            return false;
        }

        if ($('#careerCheckOther').is(':checked') && !$('#otherCareerConcern').val().trim()) {
            e.preventDefault();
            alert('Please specify your other career concern.');
            return false;
        }

        // Validate course info if that option is checked
        if ($('input[value="I need more information about certain course/s and occupation/s"]').is(':checked') && !$('#courseInfo').val().trim()) {
            e.preventDefault();
            alert('Please specify which course/s or occupation/s you need more information about.');
            return false;
        }
    });
});
    </script>
</body>
</html>