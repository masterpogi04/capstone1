<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

// Handle AJAX requests for address data
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    switch ($_GET['action']) {
        case 'getBarangays':
            $city = $_GET['city'];
            $stmt = $connection->prepare("
                SELECT b.barangay_name 
                FROM cavite_barangays b
                JOIN cavite_cities c ON b.city_id = c.id
                WHERE c.city_name = ?
                ORDER BY b.barangay_name
            ");
            $stmt->bind_param("s", $city);
            $stmt->execute();
            $result = $stmt->get_result();
            $barangays = [];
            while ($row = $result->fetch_assoc()) {
                $barangays[] = $row['barangay_name'];
            }
            echo json_encode($barangays);
            exit;

        case 'getPostalCode':
            $city = $_GET['city'];
            $stmt = $connection->prepare("
                SELECT postal_code 
                FROM cavite_cities 
                WHERE city_name = ?
            ");
            $stmt->bind_param("s", $city);
            $stmt->execute();
            $result = $stmt->get_result();
            $postalCode = $result->fetch_assoc()['postal_code'] ?? '';
            echo json_encode(['postal_code' => $postalCode]);
            exit;
    }
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$formData = $_SESSION['student_profile'] ?? [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Create permanent address string
    $permanentAddress = "{$_POST['houseno_street']}, {$_POST['barangay']}, {$_POST['city']}, {$_POST['province']}, {$_POST['zipcode']}, {$_POST['country']}";
    $currentAddress = isset($_POST['sameAsPermAddress']) ? $permanentAddress : $_POST['currentAddress'];
    
    // Store form data in session
    $_SESSION['student_profile'] = array_merge($_SESSION['student_profile'] ?? [], $_POST);
    $_SESSION['student_profile']['PermanentAddress'] = $permanentAddress;
    $_SESSION['student_profile']['currentAddress'] = $currentAddress;

    // Handle spouse information
    $spouse_name = null;
    $spouse_occupation = null;
    if ($_POST['civilStatus'] === 'Married') {
        $spouse_name = $_POST['spouseName'] ?? null;
        $spouse_occupation = $_POST['spouseOccupation'] ?? null;
    }

    // Get the course_id based on the course name
    $course_query = "SELECT id FROM courses WHERE name = ?";
    $course_stmt = $connection->prepare($course_query);
    if ($course_stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    $course_stmt->bind_param("s", $_POST['course']);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    $course_id = null;
    if ($course_row = $course_result->fetch_assoc()) {
        $course_id = $course_row['id'];
    } else {
        // Handle the case where the course is not found
        die("Error: Course not found");
    }

     // Generate profile ID
    $profile_id = generateProfileId($connection);

    // Insert or update the data in the database
    $sql = "INSERT INTO student_profiles (
        profile_id, student_id, course_id, last_name, first_name, middle_name, 
        permanent_address, current_address, province, city, barangay, zipcode,
        houseno_street, contact_number, email, gender, birthdate, age, birthplace,
        nationality, religion, civil_status, year_level, semester_first_enrolled,
        spouse_name, spouse_occupation
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        course_id = VALUES(course_id),
        last_name = VALUES(last_name),
        first_name = VALUES(first_name),
        middle_name = VALUES(middle_name),
        permanent_address = VALUES(permanent_address),
        current_address = VALUES(current_address),
        province = VALUES(province),
        city = VALUES(city),
        barangay = VALUES(barangay),
        zipcode = VALUES(zipcode),
        houseno_street = VALUES(houseno_street),
        contact_number = VALUES(contact_number),
        email = VALUES(email),
        gender = VALUES(gender),
        birthdate = VALUES(birthdate),
        age = VALUES(age),
        birthplace = VALUES(birthplace),
        nationality = VALUES(nationality),
        religion = VALUES(religion),
        civil_status = VALUES(civil_status),
        year_level = VALUES(year_level),
        semester_first_enrolled = VALUES(semester_first_enrolled),
        spouse_name = VALUES(spouse_name),
        spouse_occupation = VALUES(spouse_occupation)";

    $stmt = $connection->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }

    $stmt->bind_param("sssssssssssssssssissssssss",
        $profile_id,
        $student_id,
        $course_id,
        $_POST['last_name'],
        $_POST['first_name'],
        $_POST['middle_name'],
        $permanentAddress,
        $currentAddress,
        $_POST['province'],
        $_POST['city'],
        $_POST['barangay'],
        $_POST['zipcode'],
        $_POST['houseno_street'],
        $_POST['contactNumber'],
        $_POST['email'],
        $_POST['gender'],
        $_POST['birthdate'],
        $_POST['age'],
        $_POST['birthplace'],
        $_POST['nationality'],
        $_POST['religion'],
        $_POST['civilStatus'],
        $_POST['year_level'],
        $_POST['semester'],
        $spouse_name,
        $spouse_occupation
    );

    if ($stmt->execute()) {
        $_SESSION['profile_id'] = $profile_id;
        header("Location: family_background.php");
        exit;
    } else {
        $error = "Error: " . $stmt->error;
        // Log the error for debugging
        error_log("Database error in personal_info.php: " . $stmt->error);
    }
}

// Fetch student data from the database
if ($student_id) {
    $stmt = $connection->prepare("
        SELECT s.first_name, s.last_name, s.middle_name, s.student_id, s.section_id, s.email, s.gender,
               d.name AS department, c.name AS course
        FROM tbl_student s
        JOIN sections sec ON s.section_id = sec.id
        JOIN departments d ON sec.department_id = d.id
        JOIN courses c ON sec.course_id = c.id
        WHERE s.student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
}

if ($student_id) {
    // Fetch existing profile data if it exists
    $profile_stmt = $connection->prepare("
        SELECT *
        FROM student_profiles
        WHERE student_id = ?
        ORDER BY profile_id DESC
        LIMIT 1
    ");
    $profile_stmt->bind_param("s", $student_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    $profile_data = $profile_result->fetch_assoc();
    $profile_stmt->close();

    // If profile data exists, merge it with formData
    if ($profile_data) {
        // Parse permanent address into components
        $address_parts = explode(', ', $profile_data['permanent_address']);
        if (count($address_parts) >= 6) {
            $formData['houseno_street'] = $address_parts[0];
            $formData['barangay'] = $address_parts[1];
            $formData['city'] = $address_parts[2];
            $formData['province'] = $address_parts[3];
            $formData['zipcode'] = $address_parts[4];
            $formData['country'] = $address_parts[5];
        }

        // Set other form data
        $formData['PermanentAddress'] = $profile_data['permanent_address'];
        $formData['currentAddress'] = $profile_data['current_address'];
        $formData['contactNumber'] = $profile_data['contact_number'];
        $formData['birthdate'] = $profile_data['birthdate'];
        $formData['age'] = $profile_data['age'];
        $formData['birthplace'] = $profile_data['birthplace'];
        $formData['nationality'] = $profile_data['nationality'];
        $formData['religion'] = $profile_data['religion'];
        $formData['civilStatus'] = $profile_data['civil_status'];
        $formData['spouseName'] = $profile_data['spouse_name'];
        $formData['spouseOccupation'] = $profile_data['spouse_occupation'];
        $formData['year_level'] = $profile_data['year_level'];
        $formData['semester'] = $profile_data['semester_first_enrolled'];

        // Set the same as permanent address checkbox
        $formData['sameAsPermAddress'] = ($profile_data['permanent_address'] === $profile_data['current_address']);
    }
}

if ($profile_data) {
    $address_parts = explode(', ', $profile_data['permanent_address']);
    if (count($address_parts) >= 6) {
        $formData['houseno_street'] = $address_parts[0];
        $formData['barangay'] = $address_parts[1];
        $formData['city'] = $address_parts[2];
        $formData['province'] = $address_parts[3];
        $formData['zipcode'] = $address_parts[4];
        $formData['country'] = $address_parts[5];
        
        // Also set the full addresses
        $formData['PermanentAddress'] = $profile_data['permanent_address'];
        $formData['currentAddress'] = $profile_data['current_address'];
    }
}

// Add this code block after your existing profile data fetch
if ($profile_data) {
    // Extract address components from permanent address
    if (isset($profile_data['permanent_address'])) {
        $address_parts = explode(', ', $profile_data['permanent_address']);
        if (count($address_parts) >= 6) {
            $formData['houseno_street'] = $address_parts[0];
            $formData['barangay'] = $address_parts[1];
            $formData['city'] = $address_parts[2];
            $formData['province'] = $address_parts[3];
            $formData['zipcode'] = $address_parts[4];
            $formData['country'] = $address_parts[5];
        }
    }

    // Also set individual fields from the database
    $formData['province'] = $profile_data['province'];
    $formData['city'] = $profile_data['city'];
    $formData['barangay'] = $profile_data['barangay'];
    $formData['houseno_street'] = $profile_data['houseno_street'];
    $formData['zipcode'] = $profile_data['zipcode'];
}

function generateProfileId($connection) {
    $query = "SELECT MAX(CAST(SUBSTRING(profile_id, 9) AS UNSIGNED)) as max_id FROM student_profiles";
    $result = $connection->query($query);
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return 'Stu_pro_' . str_pad($next_id, 9, '0', STR_PAD_LEFT);
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>
<body>

    <div class="container mt-5">
        <div class="progress mb-3">
            <div class="progress-bar" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">20%</div>
        </div>
        <form id="studentProfileForm" method="POST">
            <div class="form-section active" id="section1">
                <h5>Personal Information</h5>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="last_name">Last Name:</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $student['last_name'] ?? ''; ?>" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="first_name">First Name:</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $student['first_name'] ?? ''; ?>" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="middle_name">Middle Name:</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo $student['middle_name'] ?? ''; ?>" readonly>
                    </div>
                </div>

                <label for="city"><b>Permanent Address </label></b> <br>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="province">Province </label>
                        <select class="form-control address-component" id="province" name="province">
                            <option value="">Select Province</option>
                            <option value="Cavite">Cavite</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="city">City *</label>
                        <select class="form-control address-component" id="city" name="city" required>
                            <option value="">Select City</option>
                            <option value="Alfonso">Alfonso</option>
                            <option value="Amadeo">Amadeo</option>
                            <option value="City Of Bacoor">City Of Bacoor</option>
                            <option value="City Of Carmona">City Of Carmona</option>
                            <option value="City Of Cavite">City Of Cavite</option>
                            <option value="City Of Dasmariñas">City Of Dasmariñas</option>
                            <option value="City Of General Trias">City Of General Trias</option>
                            <option value="City Of Imus">City Of Imus</option>
                            <option value="City Of Tagaytay">City Of Tagaytay</option>
                            <option value="City Of Trece Martires">City Of Trece Martires</option>
                            <option value="Gen. Mariano Alvarez">Gen. Mariano Alvarez</option>
                            <option value="General Emilio Aguinaldo">General Emilio Aguinaldo</option>
                            <option value="Indang">Indang</option>
                            <option value="Kawit">Kawit</option>
                            <option value="Magallanes">Magallanes</option>
                            <option value="Maragondon">Maragondon</option>
                            <option value="Mendez">Mendez</option>
                            <option value="Naic">Naic</option>
                            <option value="Noveleta">Noveleta</option>
                            <option value="Rosario">Rosario</option>
                            <option value="Silang">Silang</option>
                            <option value="Tanza">Tanza</option>
                            <option value="Ternate">Ternate</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="barangay">Barangay *</label>
                        <select class="form-control address-component" id="barangay" name="barangay" required>
                        <option value="">Select Barangay</option>
                    </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="zipcode">Zip/Postal Code</label>
                        <input type="text" class="form-control address-component" id="zipcode" name="zipcode" 
                           required placeholder="e.g 4125" pattern="\d{4}" maxlength="4" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="houseno_street">House No. & Street Name</label>
                        <input type="text" class="form-control address-component" id="houseno_street" name="houseno_street" required >
                    </div>

                    <div class="form-group col-md-4">
                        <label for="country">Country *</label>
                        <select class="form-control address-component" id="country" name="country" required>
                            <option value="">Select Country</option>
                            <option value="Philippines" selected>Philippines</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="PermanentAddress">Permanent Address: *</label>
                    <div class="alert alert-info" role="alert">
                        <small>
                            <i class="fas fa-info-circle"></i> Please review and edit if necessary. Add any additional details (e.g., house number, street name) not covered by the selections above.
                        </small>
                    </div>
                    <input type="text" class="form-control" id="PermanentAddress" name="PermanentAddress" value="<?php echo htmlspecialchars($formData['PermanentAddress'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sameAsPermAddress" name="sameAsPermAddress">
                        <label class="form-check-label" for="sameAsPermAddress">
                            **Current address is the same as permanent address**
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="currentAddress">Current Address:</label>
                    <input type="text" class="form-control" id="currentAddress" name="currentAddress" placeholder="If different from permanent address" value="<?php echo htmlspecialchars($formData['currentAddress'] ?? ''); ?>">
                </div>
                <div class="form-row">
                   <div class="form-group col-md-6">
                        <label for="contactNumber">Contact Number: *</label>
                        <input type="tel" class="form-control" id="contactNumber" name="contactNumber"  
                            required placeholder="e.g 09123456789" 
                            title="Please enter a valid 11-digit phone number starting with 09" 
                            value="<?php echo htmlspecialchars($formData['fieldName'] ?? ''); ?>">
                        <small class="form-text text-muted">Please enter an 11-digit phone number starting with 09.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="email">Email Address:</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $student['email'] ?? ''; ?>" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="gender">Sex:</label>
                    <input type="text" class="form-control" id="gender" name="gender" value="<?php echo $student['gender'] ?? ''; ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="birthdate">Date of Birth: *</label>
                    <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($formData['birthdate'] ?? ''); ?>"required>
                </div>
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="text" class="form-control" id="age" name="age" value="<?php echo htmlspecialchars($formData['age'] ?? ''); ?>"  readonly>
                </div>
                <div class="form-group">
                    <label for="birthplace">Place of Birth: *</label>
                    <input type="text" class="form-control" id="birthplace" name="birthplace" value="<?php echo htmlspecialchars($formData['birthplace'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="nationality">Nationality: *</label>
                    <input type="text" class="form-control" id="nationality" name="nationality" value="<?php echo htmlspecialchars($formData['nationality'] ?? ''); ?>" required>
                </div>

            <div class="form-group">
                <label for="religion">Religion: *</label>
                <select class="form-control" id="religion" name="religion" required>
                    <option value="">Select</option>
                    <option value="Catholic" <?php echo ($formData['religion'] ?? '') === 'Catholic' ? 'selected' : ''; ?>>Catholic</option>
                    <option value="Christianity" <?php echo ($formData['religion'] ?? '') === 'Christianity' ? 'selected' : ''; ?>>Christianity</option>
                    <option value="Islam" <?php echo ($formData['religion'] ?? '') === 'Islam' ? 'selected' : ''; ?>>Islam</option>
                    <option value="Iglesia ni Cristo" <?php echo ($formData['religion'] ?? '') === 'Iglesia ni Cristo' ? 'selected' : ''; ?>>Iglesia ni Cristo</option>
                    <option value="Buddhism" <?php echo ($formData['religion'] ?? '') === 'Buddhism' ? 'selected' : ''; ?>>Buddhism</option>
                    <option value="Hinduism" <?php echo ($formData['religion'] ?? '') === 'Hinduism' ? 'selected' : ''; ?>>Hinduism</option>
                    <option value="Judaism" <?php echo ($formData['religion'] ?? '') === 'Judaism' ? 'selected' : ''; ?>>Judaism</option>
                    <option value="Aglipayan" <?php echo ($formData['religion'] ?? '') === 'Aglipayan' ? 'selected' : ''; ?>>Aglipayan</option>
                    <option value="Evangelical" <?php echo ($formData['religion'] ?? '') === 'Evangelical' ? 'selected' : ''; ?>>Evangelical</option>
                    <option value="Jehovah's Witnesses" <?php echo ($formData['religion'] ?? '') === "Jehovah's Witnesses" ? 'selected' : ''; ?>>Jehovah's Witnesses</option>
                    <option value="Seventh-day Adventist" <?php echo ($formData['religion'] ?? '') === 'Seventh-day Adventist' ? 'selected' : ''; ?>>Seventh-day Adventist</option>
                    <option value="Other" <?php echo ($formData['religion'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div id="otherReligionField" style="display: none;">
                <label for="otherReligion">Please specify your religion:</label>
                <input type="text" class="form-control" id="otherReligion" name="otherReligion" value="<?php echo htmlspecialchars($formData['otherReligion'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="civilStatus">Civil Status: *</label>
                <select class="form-control" id="civilStatus" name="civilStatus" required>
                    <option value="">Select</option>
                    <option value="Single" <?php echo ($formData['civilStatus'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="Married" <?php echo ($formData['civilStatus'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                    <option value="Widowed" <?php echo ($formData['civilStatus'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    <option value="Divorced" <?php echo ($formData['civilStatus'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                    <option value="Separated" <?php echo ($formData['civilStatus'] ?? '') === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                </select>
            </div>

            <div id="spouseInfoFields" style="display: none;">
                <div class="form-group">
                    <label for="spouseName">Spouse's Name:</label>
                    <input type="text" class="form-control" id="spouseName" name="spouseName" value="<?php echo htmlspecialchars($formData['spouseName'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="spouseOccupation">Spouse's Occupation:</label>
                    <input type="text" class="form-control" id="spouseOccupation" name="spouseOccupation" value="<?php echo htmlspecialchars($formData['spouseOccupation'] ?? ''); ?>">
                </div>
            </div>

            <!-- Hidden fields for original department and course -->
                <input type="hidden" name="department" value="<?php echo $student['department'] ?? ''; ?>">
                <input type="hidden" name="course" value="<?php echo $student['course'] ?? ''; ?>">
                <!-- Displayed read-only fields for department and course -->
                <div class="form-group">
                <label for="department_display">Department:</label>
                <input type="text" class="form-control" id="department_display" value="<?php echo $student['department'] ?? ''; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="course_display">Course:</label>
                <input type="text" class="form-control" id="course_display" value="<?php echo $student['course'] ?? ''; ?>" readonly>
            </div>
                
                <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="year_level">Year Level: *</label>
                    <select class="form-control" id="year_level" name="year_level" required>
                        <option value="">Select Year Level</option>
                        <option value="First Year">First Year</option>
                        <option value="Second Year">Second Year</option>
                        <option value="Third Year">Third Year</option>
                        <option value="Fourth Year">Fourth Year</option>
                        <option value="Fifth Year">Fifth Year</option>
                        <option value="Irregular">Irregular</option>
                    </select>
                </div>
                 <div class="form-group col-md-6">
                    <label for="student_id">Student Number:</label>
                    <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo $student['student_id'] ?? ''; ?>" readonly>
                </div>
                </div>

               <div class="form-group">
                <label>Semester and School Year You First Enrolled: *</label>
                <div class="row">
                    <div class="col-md-4">
                        <select class="form-control" id="enrollmentSemester" required>
                            <option value="">Select Semester</option>
                            <option value="First">First Semester</option>
                            <option value="Second">Second Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" id="enrollmentYear" required>
                            <option value="">Select School Year</option>
                            <!-- Dynamically generate options for the past 10 years -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="semester" name="semester" readonly placeholder="Combined Enrollment Info">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-navigation">Next</button>
        </form>
    </div>

    <script>
        function formatPhoneNumber(input) {
        // Remove non-digit characters
        let value = input.value.replace(/\D/g, '');
        
        // Ensure it starts with '09'
        if (!value.startsWith('09') && value.length > 0) {
            if (value.startsWith('9')) {
                value = '0' + value;
            } else {
                value = '09';
            }
        }
        
        // Limit to 11 digits
        if (value.length > 11) {
            value = value.substring(0, 11);
        }
        
        // Update the input value
        input.value = value;
    }
        $(document).ready(function() {
            document.getElementById('contactNumber').addEventListener('input', function(e) {
            formatPhoneNumber(this);
        });
            const birthdateInput = document.getElementById('birthdate');
            const today = new Date();
            const tenYearsAgo = new Date(today.getFullYear() - 10, today.getMonth(), today.getDate());
            birthdateInput.setAttribute('max', tenYearsAgo.toISOString().split('T')[0]);

            birthdateInput.addEventListener('change', function() {
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
                    // Update the age input if it exists
                    const ageInput = document.getElementById('age');
                    if (ageInput) {
                        ageInput.value = age;
                    }
                }
            });

            // Handle religion selection
            $('#religion').change(function() {
                if ($(this).val() === 'Other') {
                    $('#otherReligionField').show();
                } else {
                    $('#otherReligionField').hide();
                }
            });

            // Handle civil status selection
            $('#civilStatus').change(function() {
                if ($(this).val() === 'Married') {
                    $('#spouseInfoFields').show();
                } else {
                    $('#spouseInfoFields').hide();
                }
            });

            // Trigger change events on page load to handle pre-selected values
            $('#religion').trigger('change');
            $('#civilStatus').trigger('change');
        });

        $(document).ready(function() {
            // Function to update the permanent address
            function updatePermanentAddress() {
                var barangay = $('#barangay').val();
                var city = $('#city').val();
                var province = $('#province').val();
                var zipcode = $('#zipcode').val();
                var houseno_street = $('#houseno_street').val();
                var country = $('#country').val();

                var permanentAddress = '';
                if (houseno_street) permanentAddress += houseno_street + ', ';
                if (barangay) permanentAddress += barangay + ', ';
                if (city) permanentAddress += city + ', ';
                if (province) permanentAddress += province + ', ';
                if (zipcode) permanentAddress += zipcode + ', ';
                if (country) permanentAddress += country;

                // Remove trailing comma and space if present
                permanentAddress = permanentAddress.replace(/,\s*$/, '');

                $('#PermanentAddress').val(permanentAddress);
            }
        // City change event handler
            $('#city').change(function() {
                const selectedCity = $(this).val();
                if (selectedCity) {
                    // Get barangays
                    $.get('personal_info.php', {
                        action: 'getBarangays',
                        city: selectedCity
                    }, function(data) {
                        const barangaySelect = $('#barangay');
                        barangaySelect.empty();
                        barangaySelect.append('<option value="">Select Barangay</option>');
                        data.forEach(function(barangay) {
                            barangaySelect.append(`<option value="${barangay}">${barangay}</option>`);
                        });
                    if (savedBarangay) {
                    barangaySelect.val(savedBarangay);
                    savedBarangay = ''; // Clear the saved value after using it
                }

                // Update permanent address after setting barangay
                updatePermanentAddress();
            }, 'json');

                    // Get postal code
                    $.get('personal_info.php', {
                        action: 'getPostalCode',
                        city: selectedCity
                    }, function(data) {
                        $('#zipcode').val(data.postal_code);
                        updatePermanentAddress();
                    }, 'json');
                } else {
                    $('#barangay').empty().append('<option value="">Select Barangay</option>');
                    $('#zipcode').val('');
                    updatePermanentAddress();
                }
            });

        // Update permanent address when any address component changes
        $('.address-component').on('change keyup', updatePermanentAddress);

        // Initial update of permanent address
        updatePermanentAddress();
    });
        document.getElementById('contactNumber').addEventListener('keypress', function(e) {
    // Allow only number inputs
    var charCode = (e.which) ? e.which : e.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        e.preventDefault();
    }
});
        document.addEventListener('DOMContentLoaded', function() {
    var yearSelect = document.getElementById('enrollmentYear');
    var semesterSelect = document.getElementById('enrollmentSemester');
    var hiddenInput = document.getElementById('semester');
    var currentYear = new Date().getFullYear();
    
    for (var i = 0; i < 10; i++) {
        var year = currentYear - i;
        var option = document.createElement('option');
        option.value = year + '-' + (year + 1);
        option.textContent = year + '-' + (year + 1);
        yearSelect.appendChild(option);
    }

    function updateHiddenInput() {
        if (semesterSelect.value && yearSelect.value) {
            hiddenInput.value = semesterSelect.value + ' Semester, ' + yearSelect.value;
        } else {
            hiddenInput.value = '';
        }
    }

    semesterSelect.addEventListener('change', updateHiddenInput);
    yearSelect.addEventListener('change', updateHiddenInput);

    // For form submission
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!hiddenInput.value) {
            e.preventDefault();
            alert('Please select both semester and school year.');
        }
    });
});
        document.getElementById('sameAsPermAddress').addEventListener('change', function() {
            var currentAddressInput = document.getElementById('currentAddress');
            if (this.checked) {
                currentAddressInput.value = document.getElementById('PermanentAddress').value;
                currentAddressInput.disabled = true;
            } else {
                currentAddressInput.value = '';
                currentAddressInput.disabled = false;
            }
        });

        document.getElementById('PermanentAddress').addEventListener('input', function() {
            var checkbox = document.getElementById('sameAsPermAddress');
            var currentAddressInput = document.getElementById('currentAddress');
            if (checkbox.checked) {
                currentAddressInput.value = this.value;
            }
        });

    $(document).ready(function() {
    // Save form data to sessionStorage before form submission
    $('#studentProfileForm').on('submit', function() {
        const formData = {};
        $(this).serializeArray().forEach(item => {
            formData[item.name] = item.value;
        });
        
        // Save special fields
        formData.civilStatus = $('#civilStatus').val();
        formData.religion = $('#religion').val();
        if ($('#religion').val() === 'Other') {
            formData.otherReligion = $('#otherReligion').val();
        }
        if ($('#civilStatus').val() === 'Married') {
            formData.spouseName = $('#spouseName').val();
            formData.spouseOccupation = $('#spouseOccupation').val();
        }
        formData.sameAsPermAddress = $('#sameAsPermAddress').is(':checked');
        
        // Store in sessionStorage
        sessionStorage.setItem('studentProfileData', JSON.stringify(formData));
    }); 

    // Load form data from sessionStorage on page load
    const loadSavedFormData = () => {
        const savedData = sessionStorage.getItem('studentProfileData');
        if (savedData) {
            const formData = JSON.parse(savedData);
            
            // Save the barangay value before triggering city change
            savedBarangay = formData.barangay || '';

            // Set all other form fields first
            Object.keys(formData).forEach(key => {
                const field = $(`[name="${key}"]`);
                if (field.length && key !== 'barangay') {  // Skip barangay here
                    field.val(formData[key]);
                }
            });

            // Trigger city change to load barangays
            if (formData.city) {
                $('#city').trigger('change');
            }

            // Handle other special fields (existing code)
            if (formData.religion === 'Other') {
                $('#otherReligionField').show();
                $('#otherReligion').val(formData.otherReligion);
            }

            if (formData.civilStatus === 'Married') {
                $('#spouseInfoFields').show();
                $('#spouseName').val(formData.spouseName);
                $('#spouseOccupation').val(formData.spouseOccupation);
            }

            if (formData.sameAsPermAddress) {
                $('#sameAsPermAddress').prop('checked', true);
                $('#currentAddress').val(formData.PermanentAddress);
                $('#currentAddress').prop('disabled', true);
            }

            // Handle enrollment info
            if (formData.semester) {
                const [semester, year] = formData.semester.split(', ');
                $('#enrollmentSemester').val(semester.replace(' Semester', ''));
                $('#enrollmentYear').val(year);
            }

            // Trigger other change events
            $('#religion').trigger('change');
            $('#civilStatus').trigger('change');
        }
    };

    // Save form data to sessionStorage before form submission
    $('#studentProfileForm').on('submit', function() {
        const formData = {};
        $(this).serializeArray().forEach(item => {
            formData[item.name] = item.value;
        });
        
        // Add special fields
        formData.civilStatus = $('#civilStatus').val();
        formData.religion = $('#religion').val();
        if ($('#religion').val() === 'Other') {
            formData.otherReligion = $('#otherReligion').val();
        }
        if ($('#civilStatus').val() === 'Married') {
            formData.spouseName = $('#spouseName').val();
            formData.spouseOccupation = $('#spouseOccupation').val();
        }
        formData.sameAsPermAddress = $('#sameAsPermAddress').is(':checked');
        formData.barangay = $('#barangay').val();  // Explicitly save barangay
        
        // Store in sessionStorage
        sessionStorage.setItem('studentProfileData', JSON.stringify(formData));
    });

    // Load saved data when page loads
    loadSavedFormData();

    // Add event listener for browser back/forward navigation
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            loadSavedFormData();
        }
    });
});
    </script>
</body>
</html>