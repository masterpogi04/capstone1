<?php
// Check if a session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Store form data in session when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_data = $_POST;
    $_SESSION['education_form_data'] = $student_data;
    
    $sql = "UPDATE student_profiles SET 
        elementary = ?, 
        secondary = ?, 
        transferees = ?, 
        course_factors = ?, 
        career_concerns = ?
    WHERE student_id = ?";

    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }

    // Process course factors
    $course_factors = [];
    if (isset($student_data['factors']) && is_array($student_data['factors'])) {
        foreach ($student_data['factors'] as $factor) {
            if ($factor === 'Other' && !empty($student_data['otherReason'])) {
                $course_factors[] = 'Other: ' . $student_data['otherReason'];
            } else {
                $course_factors[] = $factor;
            }
        }
    }
    $combined_course_factors = implode('; ', $course_factors);

    // Process career concerns
    $career_concerns = [];
    if (isset($student_data['careerConcerns']) && is_array($student_data['careerConcerns'])) {
        foreach ($student_data['careerConcerns'] as $concern) {
            if ($concern === "I need more information about certain course/s and occupation/s" && !empty($student_data['specificCourse'])) {
                $career_concerns[] = $concern . ": " . $student_data['specificCourse'];
            } else if ($concern === "Others" && !empty($student_data['otherCareerConcern'])) {
                $career_concerns[] = "Others: " . $student_data['otherCareerConcern'];
            } else {
                $career_concerns[] = $concern;
            }
        }
    }
    $combined_career_concerns = implode('; ', $career_concerns);

    $stmt->bind_param("ssssss",
        $student_data['elementary'],
        $student_data['secondary'],
        $student_data['transferee'],
        $combined_course_factors,
        $combined_career_concerns,
        $_SESSION['student_profile']['student_id']
    );

    if ($stmt->execute()) {
        $_SESSION['student_profile'] = array_merge($_SESSION['student_profile'] ?? [], $student_data);
        header("Location: medical_history.php");
        exit;
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Retrieve stored form data or fetch from database if available
$form_data = $_SESSION['education_form_data'] ?? [];

// Debug output
error_log("Initial form data: " . print_r($form_data, true));

if (empty($form_data) && isset($_SESSION['student_profile']['student_id'])) {
    $sql = "SELECT elementary, secondary, transferees, course_factors, career_concerns 
            FROM student_profiles 
            WHERE student_id = ?";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("s", $_SESSION['student_profile']['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $form_data = $row;
        
        // Extract course factors
        $form_data['factors'] = array_map('trim', explode(';', $row['course_factors']));
        
        // Extract career concerns
        $form_data['careerConcerns'] = array_map('trim', explode(';', $row['career_concerns']));
        
        // Extract other reason if present
        foreach ($form_data['factors'] as $factor) {
            if (strpos($factor, 'Other:') !== false) {
                $form_data['otherReason'] = trim(substr($factor, strpos($factor, ':') + 1));
                // Add 'Other' to factors array if not already present
                if (!in_array('Other', $form_data['factors'])) {
                    $form_data['factors'][] = 'Other';
                }
            }
        }
        
        // Extract specific course if present
        foreach ($form_data['careerConcerns'] as $concern) {
            if (strpos($concern, 'I need more information about certain course/s and occupation/s:') !== false) {
                $form_data['specificCourse'] = trim(substr($concern, strpos($concern, ':') + 1));
            }
            if (strpos($concern, 'Others:') !== false) {
                $form_data['otherCareerConcern'] = trim(substr($concern, strpos($concern, ':') + 1));
                // Add 'Others' to concerns array if not already present
                if (!in_array('Others', $form_data['careerConcerns'])) {
                    $form_data['careerConcerns'][] = 'Others';
                }
            }
        }
        
        $_SESSION['education_form_data'] = $form_data;
        
        // Debug output
        error_log("Processed form data: " . print_r($form_data, true));
    }
}

// Ensure arrays exist even if empty
$form_data['factors'] = $form_data['factors'] ?? [];
$form_data['careerConcerns'] = $form_data['careerConcerns'] ?? [];


    $sql = "SELECT elementary, secondary, transferees, course_factors, career_concerns 
            FROM student_profiles 
            WHERE student_id = ?";
    
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("s", $_SESSION['student_profile']['student_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $form_data = $row;
        $form_data['factors'] = explode('; ', $row['course_factors']);
        $form_data['careerConcerns'] = explode('; ', $row['career_concerns']);
        
        // Extract other reason if present
        if (preg_match('/Other: (.+)/', $row['course_factors'], $matches)) {
            $form_data['otherReason'] = $matches[1];
        }
        
        // Extract specific course if present
        if (preg_match('/I need more information about certain course\/s and occupation\/s: (.+)/', $row['career_concerns'], $matches)) {
            $form_data['specificCourse'] = $matches[1];
        }
        
        // Extract other career concern if present
        if (preg_match('/Others: (.+)/', $row['career_concerns'], $matches)) {
            $form_data['otherCareerConcern'] = $matches[1];
        }
        
        $_SESSION['education_form_data'] = $form_data;
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
   <div class="header">
        <h1>Student Profile Form for Inventory</h1>
    </div>

    <div class="container mt-5">
        <div class="progress">
            <div class="progress-bar" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">60%</div>
        </div>
        <form method="POST" action="">
            <div class="form-section active" id="section3">
                <h5>Educational Background</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Name of School</th>
                            <th>Address</th>
                            <th>Year Graduated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Elementary</td>
                            <td><input type="text" class="form-control" id="elementarySchool" name="elementarySchool" oninput="updateDisplayField('elementary')" required></td>
                            <td><input type="text" class="form-control" id="elementaryAddress" name="elementaryAddress" oninput="updateDisplayField('elementary')" required></td>
                            <td><input type="text" class="form-control" id="elementaryYear" name="elementaryYear" oninput="validateYear(this); updateDisplayField('elementary')" maxlength="4" required></td>
                        </tr>
                        <tr>
                            <td>Secondary/SHS</td>
                            <td><input type="text" class="form-control" id="secondarySchool" name="secondarySchool" oninput="updateDisplayField('secondary')" required></td>
                            <td><input type="text" class="form-control" id="secondaryAddress" name="secondaryAddress" oninput="updateDisplayField('secondary')" required></td>
                            <td><input type="text" class="form-control" id="secondaryYear" name="secondaryYear" oninput="validateYear(this); updateDisplayField('secondary')" maxlength="4" required></td>
                        </tr>
                        <tr>
                            <td>For transferees:</td>
                            <td><input type="text" class="form-control" id="transfereeSchool" name="transfereeSchool" oninput="updateDisplayField('transferee')"></td>
                            <td><input type="text" class="form-control" id="transfereeAddress" name="transfereeAddress" oninput="updateDisplayField('transferee')"></td>
                            <td><input type="text" class="form-control" id="transfereeCourse" name="transfereeCourse" placeholder="Course taken" oninput="updateDisplayField('transferee')"></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="form-group">
                    <label for="elementaryDisplay">Elementary Education:</label>
                    <input type="text" class="form-control" id="elementaryDisplay" name="elementary" readonly>
                </div>
                <div class="form-group">
                    <label for="secondaryDisplay">Secondary Education:</label>
                    <input type="text" class="form-control" id="secondaryDisplay" name="secondary" readonly>
                </div>
                <div class="form-group">
                    <label for="transfereeDisplay">Transferee Information:</label>
                    <input type="text" class="form-control" id="transfereeDisplay" name="transferee" readonly>
                </div>

                <h5>Career Exploration Information</h5>
                    <p>What factors have influenced you most in choosing your course? Check [/] at least three.</p>
                    <div class="form-check">
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck1" name="factors[]" value="Financial Security after graduation">
                            <label class="custom-control-label" for="customCheck1">Financial Security after graduation</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck2" name="factors[]" value="Childhood Dream">
                            <label class="custom-control-label" for="customCheck2">Childhood Dream</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck3" name="factors[]" value="Leisure/Enjoyment">
                            <label class="custom-control-label" for="customCheck3">Leisure/Enjoyment</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck4" name="factors[]" value="Parents Decision/Choice">
                            <label class="custom-control-label" for="customCheck4">Parents Decision/Choice</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck5" name="factors[]" value="Status Recognition">
                            <label class="custom-control-label" for="customCheck5">Status Recognition</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck6" name="factors[]" value="Independence">
                            <label class="custom-control-label" for="customCheck6">Independence</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck7" name="factors[]" value="Opportunity to help others/society">
                            <label class="custom-control-label" for="customCheck7">Opportunity to help others/society</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck8" name="factors[]" value="Challenge/Adventure">
                            <label class="custom-control-label" for="customCheck8">Challenge/Adventure</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck9" name="factors[]" value="Location of School">
                            <label class="custom-control-label" for="customCheck9">Location of School</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck10" name="factors[]" value="Pursuit of Knowledge">
                            <label class="custom-control-label" for="customCheck10">Pursuit of Knowledge</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck11" name="factors[]" value="Moral Fulfilment">
                            <label class="custom-control-label" for="customCheck11">Moral Fulfilment</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheck12" name="factors[]" value="Peer Influence">
                            <label class="custom-control-label" for="customCheck12">Peer Influence</label>
                        </div>
                        
                        <div class="custom-control custom-checkbox custom-control-other mb-3">
                            <input type="checkbox" class="custom-control-input course-factor" id="customCheckOther" name="factors[]" value="Other">
                            <label class="custom-control-label" for="customCheckOther">Other reason/s:</label>
                            <input type="text" class="form-control mt-2" id="otherReason" name="otherReason" placeholder="Please specify" style="display: none;">
                            </div>
                        </div>


                    <div class="form-group">
                        <label for="combinedFactors">Combined Factors (course_factors):</label>
                        <textarea class="form-control" id="combinedFactors" name="course_factors" rows="3" readonly></textarea>
                    </div>

                    <h5>Current Career Concerns</h5>
                    <p>Please check the current career concerns that you may be experiencing or wish to be addressed in the future. You may check more than one option.</p>

                    <div class="form-check">
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck1" name="careerConcerns[]" value="I need more information about my personal traits, interests, skills, and values">
                            <label class="custom-control-label" for="careerCheck1">I need more information about my personal traits, interests, skills, and values</label>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck2" name="careerConcerns[]" value="I need more information about certain course/s and occupation/s">
                            <label class="custom-control-label" for="careerCheck2">I need more information about certain course/s and occupation/s</label>
                            <div id="specificCourseInput" style="display: none; margin-top: 10px;">
                                <input type="text" class="form-control" id="specificCourse" name="specificCourse" placeholder="Enter specific course(s) or occupation(s)">
                            </div>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck3" name="careerConcerns[]" value="I have difficulty making a career decision/goal-setting">
                            <label class="custom-control-label" for="careerCheck3">I have difficulty making a career decision/goal-setting</label>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck4" name="careerConcerns[]" value="I have many goals that conflict with each other">
                            <label class="custom-control-label" for="careerCheck4">I have many goals that conflict with each other</label>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck5" name="careerConcerns[]" value="My parents have different goals for me">
                            <label class="custom-control-label" for="careerCheck5">My parents have different goals for me</label>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck6" name="careerConcerns[]" value="I think I am not capable of anything">
                            <label class="custom-control-label" for="careerCheck6">I think I am not capable of anything</label>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck7" name="careerConcerns[]" value="I know what I want, but someone else thinks I should do something else">
                            <label class="custom-control-label" for="careerCheck7">I know what I want, but someone else thinks I should do something else</label>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheck8" name="careerConcerns[]" value="I don't know and I am not sure what to do after graduation">
                            <label class="custom-control-label" for="careerCheck8">I don't know and I am not sure what to do after graduation</label>
                        </div>

                        <div class="custom-control custom-checkbox custom-control-other mb-3">
                            <input type="checkbox" class="custom-control-input career-concern" id="careerCheckOther" name="careerConcerns[]" value="Others">
                            <label class="custom-control-label" for="careerCheckOther">Others: </label>
                            <input type="text" class="form-control mt-2" id="otherCareerConcern" name="otherCareerConcern" placeholder="Please specify" style="display: none;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="combinedCareerConcerns">Combined Career Concerns:</label>
                        <textarea class="form-control" id="combinedCareerConcerns" name="combined_career_concerns" rows="3" readonly></textarea>
                    </div>

                    <button type="button" class="btn btn-secondary btn-navigation" onclick="window.location.href='family_background.php'">Previous</button>
                    <button type="submit" class="btn btn-primary btn-navigation">Next</button>
                
            </div>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded and parsed');
    console.log('Form data:', <?php echo json_encode($form_data); ?>);

    // Initial setup - Get all elements
    const otherReasonInput = document.getElementById('otherReason');
    const otherCareerConcernInput = document.getElementById('otherCareerConcern');
    const specificCourseInput = document.getElementById('specificCourse');
    const specificCourseDiv = document.getElementById('specificCourseInput');
    const factorCheckboxes = document.querySelectorAll('.course-factor');
    const careerConcernCheckboxes = document.querySelectorAll('.career-concern');
    const combinedFactorsField = document.getElementById('combinedFactors');
    const combinedCareerConcernsField = document.getElementById('combinedCareerConcerns');

    // Function to show and populate a field
    function showAndPopulateField(checkbox, input, value) {
        if (checkbox && input) {
            checkbox.checked = true;
            if (input.tagName === 'DIV') {
                input.style.cssText = 'display: block !important';
                const actualInput = input.querySelector('input');
                if (actualInput) {
                    actualInput.value = value;
                    actualInput.setAttribute('required', 'required');
                }
            } else {
                input.style.cssText = 'display: block !important';
                input.value = value;
                input.setAttribute('required', 'required');
            }
        }
    }

    // Find the correct Career Exploration heading and add alert
    const careerHeading = Array.from(document.querySelectorAll('h5'))
        .find(h => h.textContent === 'Career Exploration Information');
    const alertDiv = document.createElement('div');
    alertDiv.id = 'factorsAlert';
    alertDiv.className = 'alert alert-warning mt-2 mb-3';
    alertDiv.textContent = 'Please select at least 3 factors that influenced your course choice.';
    careerHeading.insertAdjacentElement('afterend', alertDiv);

    // Function to validate number of checked factors
    function validateCareerFactors() {
        const checkedFactors = document.querySelectorAll('.course-factor:checked').length;
        const alertDiv = document.getElementById('factorsAlert');
        
        if (checkedFactors < 3) {
            alertDiv.classList.remove('d-none');
            return false;
        } else {
            alertDiv.classList.add('d-none');
            return true;
        }
    }

    // Only hide fields initially if there's no session data
    <?php if (empty($form_data)): ?>
        if (otherReasonInput) otherReasonInput.style.display = 'none';
        if (otherCareerConcernInput) otherCareerConcernInput.style.display = 'none';
        if (specificCourseDiv) specificCourseDiv.style.display = 'none';
    <?php endif; ?>

    // Function to update display fields with semicolon delimiter
    function updateDisplayField(level) {
        let school = document.getElementById(level + 'School').value;
        let address = document.getElementById(level + 'Address').value;
        let yearOrCourse = (level === 'transferee') ? 
            document.getElementById(level + 'Course').value :
            document.getElementById(level + 'Year').value;

        let combinedValue = `${school}; ${address}; ${yearOrCourse}`;
        document.getElementById(level + 'Display').value = combinedValue;
    }

    // Year validation functions
    function validateYear(input) {
        input.value = input.value.replace(/\D/g, '');
        if (input.value.length > 4) {
            input.value = input.value.slice(0, 4);
        }

        const currentYear = new Date().getFullYear();
        let year = parseInt(input.value, 10);
        clearWarning(input);
        if (year < 1980 || year > currentYear) {
            showWarning(input, `Suggested range: 1980-${currentYear}`);
        }
        if (isNaN(year) && input.value !== '') {
            showWarning(input, 'Please enter a valid year');
        }
    }

    function showWarning(input, message) {
        let warning = input.nextElementSibling;
        if (!warning || !warning.classList.contains('year-warning')) {
            warning = document.createElement('div');
            warning.className = 'year-warning text-danger small mt-1';
            input.parentNode.insertBefore(warning, input.nextSibling);
        }
        warning.textContent = message;
    }

    function clearWarning(input) {
        const warning = input.nextElementSibling;
        if (warning && warning.classList.contains('year-warning')) {
            warning.remove();
        }
    }

    // Toggle function for inputs
    function toggleInput(inputId, checkbox) {
        var input;
        var container;
        
        if (inputId === 'specificCourse') {
            container = document.getElementById('specificCourseInput');
            input = document.getElementById('specificCourse');
        } else {
            input = document.getElementById(inputId);
            container = input;
        }
        
        if (checkbox.checked) {
            container.style.display = 'block';
            input.setAttribute('required', '');
        } else {
            container.style.display = 'none';
            input.removeAttribute('required');
            input.value = '';
        }
    }

    // Update functions for combined fields
    function updateCombinedFactors() {
        var selectedFactors = [];
        factorCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                if (checkbox.value === 'Other') {
                    var otherReason = otherReasonInput.value.trim();
                    if (otherReason) {
                        selectedFactors.push('Other: ' + otherReason);
                    }
                } else {
                    selectedFactors.push(checkbox.value);
                }
            }
        });
        combinedFactorsField.value = selectedFactors.join('; ');
    }

    function updateCombinedCareerConcerns() {
        var selectedConcerns = [];
        careerConcernCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                if (checkbox.value === "I need more information about certain course/s and occupation/s") {
                    var specificCourse = document.getElementById('specificCourse').value.trim();
                    if (specificCourse) {
                        selectedConcerns.push(checkbox.value + ": " + specificCourse);
                    } else {
                        selectedConcerns.push(checkbox.value);
                    }
                } else if (checkbox.value === "Others") {
                    var otherConcern = otherCareerConcernInput.value.trim();
                    if (otherConcern) {
                        selectedConcerns.push("Others: " + otherConcern);
                    }
                } else {
                    selectedConcerns.push(checkbox.value);
                }
            }
        });
        combinedCareerConcernsField.value = selectedConcerns.join('; ');
    }

    // Set up event listeners
    ['elementary', 'secondary', 'transferee'].forEach(function(level) {
        var schoolInput = document.getElementById(level + 'School');
        var addressInput = document.getElementById(level + 'Address');
        var yearInput = document.getElementById(level + (level === 'transferee' ? 'Course' : 'Year'));
        
        if (schoolInput) schoolInput.addEventListener('input', function() { updateDisplayField(level); });
        if (addressInput) addressInput.addEventListener('input', function() { updateDisplayField(level); });
        if (yearInput) {
            yearInput.addEventListener('input', function() { 
                if (level !== 'transferee') validateYear(this);
                updateDisplayField(level);
            });
        }
    });

    // Event listeners for checkboxes
    factorCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            if (this.value === 'Other') {
                toggleInput('otherReason', this);
            }
            updateCombinedFactors();
            validateCareerFactors();
        });
    });

    careerConcernCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            if (this.value === "I need more information about certain course/s and occupation/s") {
                toggleInput('specificCourse', this);
            } else if (this.value === "Others") {
                toggleInput('otherCareerConcern', this);
            }
            updateCombinedCareerConcerns();
        });
    });

    // Input event listeners for other fields
    if (otherReasonInput) {
        otherReasonInput.addEventListener('input', updateCombinedFactors);
    }

    if (otherCareerConcernInput) {
        otherCareerConcernInput.addEventListener('input', updateCombinedCareerConcerns);
    }

    if (specificCourseInput) {
        specificCourseInput.addEventListener('input', updateCombinedCareerConcerns);
    }

    // Form submission
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const checkedFactors = document.querySelectorAll('.course-factor:checked').length;
            
            if (checkedFactors < 3) {
                alertDiv.classList.remove('d-none');
                alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            updateCombinedFactors();
            updateCombinedCareerConcerns();
            form.submit();
        });
    }

    // Populate form fields with stored data
    <?php if (!empty($form_data)): ?>
        // Populate education fields
        if ('<?php echo addslashes($form_data['elementary'] ?? ''); ?>') {
            document.getElementById('elementaryDisplay').value = '<?php echo addslashes($form_data['elementary'] ?? ''); ?>';
            const [school, address, year] = '<?php echo addslashes($form_data['elementary'] ?? ''); ?>'.split('; ');
            document.getElementById('elementarySchool').value = school || '';
            document.getElementById('elementaryAddress').value = address || '';
            document.getElementById('elementaryYear').value = year || '';
        }
        
        if ('<?php echo addslashes($form_data['secondary'] ?? ''); ?>') {
            document.getElementById('secondaryDisplay').value = '<?php echo addslashes($form_data['secondary'] ?? ''); ?>';
            const [school, address, year] = '<?php echo addslashes($form_data['secondary'] ?? ''); ?>'.split('; ');
            document.getElementById('secondarySchool').value = school || '';
            document.getElementById('secondaryAddress').value = address || '';
            document.getElementById('secondaryYear').value = year || '';
        }
        
        if ('<?php echo addslashes($form_data['transferee'] ?? ''); ?>') {
            document.getElementById('transfereeDisplay').value = '<?php echo addslashes($form_data['transferee'] ?? ''); ?>';
            const [school, address, course] = '<?php echo addslashes($form_data['transferee'] ?? ''); ?>'.split('; ');
            document.getElementById('transfereeSchool').value = school || '';
            document.getElementById('transfereeAddress').value = address || '';
            document.getElementById('transfereeCourse').value = course || '';
        }

        // Populate course factors
        <?php if (!empty($form_data['factors'])): ?>
            <?php foreach ($form_data['factors'] as $factor): ?>
                var factor = '<?php echo addslashes($factor); ?>';
                console.log('Processing factor:', factor);
                if (factor.includes('Other:')) {
                    showAndPopulateField(
                        document.getElementById('customCheckOther'),
                        document.getElementById('otherReason'),
                        factor.split('Other:')[1].trim()
                    );
                } else {
                    var checkbox = Array.from(document.querySelectorAll('.course-factor')).find(cb => cb.value === factor);
                    if (checkbox) checkbox.checked = true;
                }
            <?php endforeach; ?>
        <?php endif; ?>

        // Populate career concerns
        <?php if (!empty($form_data['careerConcerns'])): ?>
            <?php foreach ($form_data['careerConcerns'] as $concern): ?>
                var concern = '<?php echo addslashes($concern); ?>';
                console.log('Processing concern:', concern);
                if (concern.includes('Others:')) {
                    showAndPopulateField(
                        document.getElementById('careerCheckOther'),
                        document.getElementById('otherCareerConcern'),
                        concern.split('Others:')[1].trim()
                    );
                } else if (concern.includes('I need more information about certain course/s and occupation/s')) {
                    showAndPopulateField(
                        document.getElementById('careerCheck2'),
                        document.getElementById('specificCourseInput'),
                        concern.includes(':') ? concern.split(':')[1].trim() : ''
                    );
                } else {
                    var checkbox = Array.from(document.querySelectorAll('.career-concern')).find(cb => cb.value === concern);
                    if (checkbox) checkbox.checked = true;
                }
            <?php endforeach; ?>
        <?php endif; ?>

        // Update combined fields after population
        setTimeout(() => {
            updateCombinedFactors();
            updateCombinedCareerConcerns();
            validateCareerFactors();
        }, 100);
    <?php endif; ?>
});
</script>
</body>
</html>