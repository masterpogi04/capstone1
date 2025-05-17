<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection file
include "../db.php";

// Check if database connection is established
if (!isset($connection)) {
    die("Database connection variable (\$connection) is not set. Check your db.php file.");
}

if ($connection instanceof mysqli) {
    if ($connection->connect_error) {
        die("Database connection failed: " . $connection->connect_error);
    }
} else {
    die("Invalid database connection object. Expected mysqli instance.");
}


// Check if the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: login.php");
    exit();
}

// Function to get departments
function getDepartments() {
    global $connection;
    $stmt = $connection->prepare("SELECT * FROM departments ORDER BY name");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Function to get courses by department
function getCoursesByDepartment($department_id) {
    global $connection;
    $stmt = $connection->prepare("SELECT * FROM courses WHERE department_id = ? ORDER BY name");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


$message = "";

// Function to check for disabled section
function checkForDisabledSection($connection, $department_id, $course_id, $year_level, $section_no) {
    $check_sql = "SELECT id, status, academic_year 
                 FROM sections 
                 WHERE department_id = ? 
                 AND course_id = ? 
                 AND year_level = ? 
                 AND section_no = ? 
                 AND status = 'disabled'";
    
    $check_stmt = $connection->prepare($check_sql);
    if (!$check_stmt) {
        error_log("Error preparing statement: " . $connection->error);
        return null;
    }
    
    $check_stmt->bind_param("iiss", $department_id, $course_id, $year_level, $section_no);
    if (!$check_stmt->execute()) {
        error_log("Error executing statement: " . $check_stmt->error);
        return null;
    }
    
    $result = $check_stmt->get_result();
    $disabled_section = $result->fetch_assoc();
    $check_stmt->close();
    
    return $disabled_section;
}

// Function to reactivate section
function reactivateSection($connection, $section_id, $adviser_id) {
    $update_sql = "UPDATE sections 
                   SET status = 'active', 
                       adviser_id = ?
                   WHERE id = ?";
    
    $update_stmt = $connection->prepare($update_sql);
    if (!$update_stmt) {
        error_log("Error preparing update statement: " . $connection->error);
        return false;
    }
    
    $update_stmt->bind_param("ii", $adviser_id, $section_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
    
    return $success;
}

// Main section handling logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_section'])) {
    $department_id = $_POST['department'];
    $course_id = $_POST['course'];
    $year_level = $_POST['year_level'];
    $section_no = $_POST['section_no'];
    $academic_year_start = $_POST['academic_year_start'];
    $academic_year_end = $_POST['academic_year_end'];
    $academic_year = $academic_year_start . ' - ' . $academic_year_end;
    $adviser_id = $_SESSION['user_id'];

    // Start transaction
    $connection->begin_transaction();

    try {
        // First, check for any existing active section
        $check_active_sql = "SELECT id FROM sections 
                           WHERE department_id = ? 
                           AND course_id = ? 
                           AND year_level = ? 
                           AND section_no = ? 
                           AND status = 'active'";
        
        $check_active_stmt = $connection->prepare($check_active_sql);
        $check_active_stmt->bind_param("iiss", $department_id, $course_id, $year_level, $section_no);
        $check_active_stmt->execute();
        $active_result = $check_active_stmt->get_result();
        
        if ($active_result->num_rows > 0) {
            $message = "Error: An active section with these details already exists!";
        } else {
            // Check for disabled section
            $disabled_section = checkForDisabledSection($connection, $department_id, $course_id, $year_level, $section_no);
            
            if ($disabled_section) {
                // Reactivate the existing disabled section
                if (reactivateSection($connection, $disabled_section['id'], $adviser_id)) {
                    $message = "Section has been reactivated successfully!";
                } else {
                    throw new Exception("Error reactivating section");
                }
            } else {
                // No existing section found - create new one
                $message = createNewSection($connection, $academic_year_start, $department_id, $course_id, 
                                         $year_level, $section_no, $academic_year, $adviser_id);
            }
        }
        
        $connection->commit();
        
    } catch (Exception $e) {
        $connection->rollback();
        $message = "Error: " . $e->getMessage();
        error_log($message);
    } finally {
        if (isset($check_active_stmt)) {
            $check_active_stmt->close();
        }
    }
}

// Helper function to create new section
function createNewSection($connection, $year_start, $department_id, $course_id, $year_level, $section_no, $academic_year, $adviser_id) {
    // Generate the new section ID format
    $year = substr($year_start, -2);
    $random_numbers = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
    $new_section_id = $year . $random_numbers;

    $sql = "INSERT INTO sections (id, department_id, course_id, year_level, 
            section_no, academic_year, adviser_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $connection->prepare($sql);
    
    if ($stmt === false) {
        return "Error preparing insert statement: " . $connection->error;
    }
    
    $stmt->bind_param("siisssi", $new_section_id, $department_id, $course_id, 
                      $year_level, $section_no, $academic_year, $adviser_id);

    if ($stmt->execute()) {
        return "New section created successfully!";
    } else {
        return "Error creating new section: " . $stmt->error;
    }
    $stmt->close();
}

$departments = getDepartments();

// Fetch courses if a department is selected
$selectedDepartment = isset($_POST['department']) ? $_POST['department'] : (isset($_GET['department']) ? $_GET['department'] : null);
$courses = $selectedDepartment ? getCoursesByDepartment($selectedDepartment) : [];

if (!empty($message)) {
    error_log("Message set: " . $message);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Section</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>

    body{
        background-color: #004d4d
    }
    :root {
        --primary-color: #3498db;
        --secondary-color: #2980b9;
        --text-color: #2c3e50;
        --input-background: #fff;
        --input-border: #bdc3c7;
        --success-color: #2ecc71;
        --error-color: #e74c3c;
    }

    .main-content {
        padding: 20px;
        justify-content: center;
        align-items: center;
        display: flex;
    }

    /* Form Container Styles */
    .form-container {
        max-width: 650px;
        width: 95%;
        padding: 2rem;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .form-container h2 {
        color: #003366;
        margin-bottom: 1.5rem;
        text-align: center;
        font-size: 1.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        padding-bottom: 0.5rem;
    }

    /* Select and Input Styles */
    .form-container select,
    .form-container input {
        width: 100%;
        padding: 12px;
        margin: 8px 0 20px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        background-color: #fff;
        transition: all 0.3s ease;
    }

    /* Custom Select Styling */
    .form-container select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23003366' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 12px;
        padding-right: 40px;
    }

    /* Label Styles */
    .form-container label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 16px;
    }

    /* Academic Year Input Group */
    .academic-year-group {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
    }

    .academic-year-group input {
        flex: 1;
        margin: 0;
    }

    .academic-year-group span {
        font-weight: 600;
        color: #2c3e50;
    }
/* Back Button*/
.modern-back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #2EDAA8;
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(46, 218, 168, 0.15);
    letter-spacing: 0.3px;
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button:active {
    transform: translateY(0);
    box-shadow: 0 1px 4px rgba(46, 218, 168, 0.15);
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}
    /* Submit Button Styles */
    .btn-primary {
        display: block;
        width: 100%;
        max-width: 400px;
        margin: 30px auto 0;
        padding: 14px 28px;
        background-color: #4a90e2;
        color: white;
        border: none;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-primary:hover {
        background-color: #357abd;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    /* Focus States */
    .form-container select:focus,
    .form-container input:focus {
        border-color: #4a90e2;
        box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        outline: none;
    }

    /* Message Styles */
    .message {
        text-align: center;
        margin-bottom: 20px;
        padding: 12px;
        border-radius: 4px;
        font-weight: 600;
    }

    .success {
        background-color: var(--success-color);
        color: white;
    }

    .error {
        background-color: var(--error-color);
        color: white;
    }

    /* Responsive Breakpoints */
    @media screen and (max-width: 768px) {
        .form-container {
            width: 90%;
            padding: 1.5rem;
        }

        .btn-primary {
            max-width: 100%;
            padding: 12px 24px;
            font-size: 15px;
        }

        .form-container label {
            font-size: 15px;
        }

        .form-container select,
        .form-container input {
            padding: 10px;
            font-size: 15px;
        }
    }

    @media screen and (max-width: 480px) {
        .form-container {
            width: 95%;
            padding: 1rem;
        }

        .academic-year-group {
            flex-direction: column;
            gap: 8px;
        }

        .academic-year-group span {
            text-align: center;
        }

        .btn-primary {
            padding: 10px 20px;
            font-size: 14px;
        }

        .form-container label {
            font-size: 14px;
        }

        .form-container select,
        .form-container input {
            padding: 8px;
            font-size: 14px;
            margin: 6px 0 16px;
        }
    }

    /* Touch Device Optimizations */
    @media (hover: none) {
        .form-container select,
        .form-container input {
            font-size: 16px;
        }

        .btn-primary {
            -webkit-tap-highlight-color: transparent;
        }

        .btn-primary:active {
            transform: translateY(1px);
        }
    }

    /* Ensure form is responsive when sidebar is collapsed */
    @media screen and (max-width: 992px) {
        .main-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .form-container {
            margin: 1rem auto;
        }
    }
</style>

<body>
    <main class="main-content">
        <div class="form-container">
        <a href="adviser_homepage.php" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back</span>
</a>
            <h2>Add New Section</h2>
            <form method="post" action="" id="addSectionForm">
               <label for="department">Department:</label>
                <select name="department" id="department" required onchange="this.form.submit()">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>" <?php echo $selectedDepartment == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="course">Program:</label>
                <select name="course" id="course" required>
                    <option value="">Select Programs</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="year_level">Year Level:</label>
                <select name="year_level" id="year_level" required>
                    <option value="">Select Year Level</option>
                    <option value="First Year">First Year</option>
                    <option value="Second Year">Second Year</option>
                    <option value="Third Year">Third Year</option>
                    <option value="Fourth Year">Fourth Year</option>
                    <option value="Fifth Year">Fifth Year</option>
                    <option value="Irregular">Irregular</option>
                </select>
                <br>
                <label for="section_no">Section no:</label>
                <input type="text" name="section_no" id="section_no" required oninput="validateSectionNo(this)" pattern="\d*" inputmode="numeric">
                <br>
                <label for="academic_year_start">Academic Year:</label>
<div style="display: flex; gap: 10px;">
    <input type="number" 
           name="academic_year_start" 
           id="academic_year_start" 
           required 
           value="<?php echo date('Y'); ?>"
           style="width: 45%; background-color: #f0f0f0;"
           readonly>
    <span style="align-self: center;">to</span>
    <input type="number" 
           name="academic_year_end" 
           id="academic_year_end" 
           required 
           style="width: 45%; background-color: #f0f0f0;"
           readonly>
</div>

             <center><button type="button" id="submitBtn" class="btn btn-primary">Add Section</button>
                <button type="button" class="btn btn-primary" onclick="window.location.href='view_section.php'">View Section</button>
             </center>
            </form>
        </div>
        <br><br>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addSectionForm');
    const submitBtn = document.getElementById('submitBtn');
    const sectionNoInput = document.getElementById('section_no');
    const startYearInput = document.getElementById('academic_year_start');
    const endYearInput = document.getElementById('academic_year_end');
    
    // Get current year
    const currentYear = new Date().getFullYear();

    // Set initial academic year values
    startYearInput.value = currentYear;
    endYearInput.value = currentYear + 1;

    // Make sure the hidden fields are always populated
    function updateAcademicYearFields() {
        startYearInput.value = currentYear;
        endYearInput.value = currentYear + 1;
        
        // Remove readonly temporarily for form submission
        startYearInput.removeAttribute('readonly');
        endYearInput.removeAttribute('readonly');
    }

    // Department change handler
    document.getElementById('department').addEventListener('change', function() {
        updateAcademicYearFields();
        form.submit();
    });

    // Section number validation
    sectionNoInput.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        if (!/[0-9]/.test(char)) {
            e.preventDefault();
        }
    });

    // Form submission handler
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();

        // Update academic year values before submission
        updateAcademicYearFields();

        // Validate form
        if (form.checkValidity()) {
            Swal.fire({
                title: 'Confirm Addition',
                text: "Are you sure you want to add this section?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Add hidden input for section addition
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'add_section';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    
                    // Submit form
                    form.submit();
                } else {
                    // Reset readonly attributes if submission is canceled
                    startYearInput.setAttribute('readonly', 'readonly');
                    endYearInput.setAttribute('readonly', 'readonly');
                }
            });
        } else {
            form.reportValidity();
        }
    });

<?php if (!empty($message)): ?>
Swal.fire({
    title: '<?php echo strpos($message, 'Error') !== false ? 'Error' : 'Success'; ?>',
    text: '<?php echo addslashes($message); ?>',
    icon: '<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>',
    confirmButtonText: 'OK'
}).then((result) => {
    // Check if message does NOT contain "Error"
    if ('<?php echo strpos($message, 'Error') === false ?>') {
        // Redirect to view sections page on success
        window.location.href = 'add_section.php';
    }
});
<?php endif; ?>
});
</script>
</body>
</html>