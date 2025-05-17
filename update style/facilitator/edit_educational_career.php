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
        $_SESSION['success_message'] = "Educational and career information updated successfully.";
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
<body>
    <div class="header">
        <h1>Edit Student Educational and Career Information</h1>
    </div>

    <div class="container mt-5">
        <form id="editEducationalCareerForm" method="POST">
            <h5>Educational Background</h5>
            
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

        if (concernsChecked < 1) {
            e.preventDefault();
            alert('Please select at least one career concern.');
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