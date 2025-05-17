<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}
 
$student_id = $_GET['student_id'] ?? null;
$department_id = $_GET['department_id'] ?? null;
$course_id = $_GET['course_id'] ?? null;
$year_level = $_GET['year_level'] ?? null;
$section_id = $_GET['section_id'] ?? null;

$studentNotFound = false;

if (!$student_id) {
    header("Location: view_profiles.php");
    exit();
}

// Fetch student details
$stmt = $connection->prepare("
    SELECT sp.*, s.department_id, s.course_id, d.name as department_name, c.name as course_name, s.section_no
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

if (!$student) {
    $studentNotFound = true;
}

// Fetch facilitator name
$facilitator_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$facilitator = $result->fetch_assoc();

// Construct full name from components
$facilitator_name = trim($facilitator['first_name'] . ' ' . 
    ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
    $facilitator['last_name']);
?>
<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_GET['student_id'] ?? null;
$department_id = $_GET['department_id'] ?? null;
$course_id = $_GET['course_id'] ?? null;
$year_level = $_GET['year_level'] ?? null;
$section_id = $_GET['section_id'] ?? null;

$studentNotFound = false;

if (!$student_id) {
    header("Location: view_profiles.php");
    exit();
}

// Fetch student details
$stmt = $connection->prepare("
    SELECT sp.*, s.department_id, s.course_id, d.name as department_name, c.name as course_name, s.section_no
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

if (!$student) {
    $studentNotFound = true;
}

// Fetch facilitator name
$facilitator_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT first_name, middle_initial, last_name FROM tbl_facilitator WHERE id = ?");
if ($stmt === false) {
    die("Error preparing query: " . $connection->error);
}
$stmt->bind_param("i", $facilitator_id);
$stmt->execute();
$result = $stmt->get_result();
$facilitator = $result->fetch_assoc();

// Construct full name from components
$facilitator_name = trim($facilitator['first_name'] . ' ' . 
    ($facilitator['middle_initial'] ? $facilitator['middle_initial'] . '. ' : '') . 
    $facilitator['last_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
       :root {
    /* Enhanced Color Palette */
    --primary-color: #15803d;
    --primary-light: #22c55e;
    --primary-dark: #166534;
    --secondary-color: #86efac;
    --background-color: #f0fdf4;
    --surface-color: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-tertiary: #94a3b8;
    --error-color: #ef4444;
    --success-color: #22c55e;
    --warning-color: #f59e0b;
      --blue-color: #2563eb;         /* Primary blue */
    --blue-light: #3b82f6;         /* Lighter blue */
    --blue-dark: #1e40af;          /* Darker blue */
    --blue-accent: #93c5fd;        /* Accent blue */
    
    /* Elevation Shadows */
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    
    /* Typography */
    --font-sm: 0.875rem;
    --font-base: 1rem;
    --font-lg: 1.125rem;
    --font-xl: 1.25rem;
    --font-2xl: 1.5rem;
    
    /* Spacing */
    --spacing-xs: 0.5rem;
    --spacing-sm: 0.75rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    
    /* Layout */
    --border-radius: 0.5rem;
    --border-radius-lg: 0.75rem;
    --max-width: 1200px;
    --header-height: 4rem;
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Base Styles */
body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background-color: var(--background-color);
    color: var(--text-primary);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

/* Enhanced Header */
h1, h2, h3, h4, h5, h6 {
    color: var(--text-primary);
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: var(--spacing-lg);
}

h1 {
    font-size: var(--font-2xl);
    position: relative;
    padding-bottom: var(--spacing-sm);
}

h1::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 4px;
    
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 160px; /* Increased to accommodate fixed header */
}

/* Navigation Improvements */
.tab-navigation {
    position:absolute;
    top: 0;
    left: 0;
    right: 0;
    background: var(--surface-color);
    padding: 1rem 2rem;
    box-shadow: var(--shadow-md);
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 1000;
    border-bottom: 1px solid #e5e7eb;
}
/* Header Section */
.header-section {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.header-title {
    margin: 0;
    font-size: 1.5rem;
    color: var(--primary-color);
}
/* Tab Buttons Container */
.tab-buttons {
    display: flex;
    gap: 0.5rem;
    width: 100%;
    justify-content: center;
    flex-wrap: wrap;
}
/* Enhanced Tab Buttons */
.tab-button {
    padding: var(--spacing-sm) var(--spacing-lg);
    border: none;
    background: transparent;
    color: var(--text-secondary);
    font-weight: 600;
    font-size: var(--font-sm);
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.tab-button::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background: var(--primary-color);
    transition: var(--transition);
    border-radius: 3px 3px 0 0;
}

.tab-button:hover {
    color: var(--primary-color);
}

.tab-button:hover::before {
    width: 100%;
}

.tab-button.active {
    color: var(--primary-color);
    background: rgba(34, 197, 94, 0.1);
}

.tab-button.active::before {
    width: 100%;
}

/* Content Area Improvements */
.content {
    background: var(--surface-color);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    margin: 0 auto 2rem;
    box-shadow: var(--shadow-lg);
}

  /* Form Styling */
  .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            padding: 12px 16px;
            transition: var(--transition);
            font-size: 1rem;
        }

        .readonly-input {
            background-color: #f8fafc;
            color: #64748b;
        }

        /* Card Layout */
        .info-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

/* Enhanced Buttons */
.edit-buttons {
    display: flex;
    gap: var(--spacing-md);
    justify-content: flex-end;
    margin-top: var(--spacing-xl);
    padding: var(--spacing-lg);
   
}

.btn {
    padding: var(--spacing-sm) var(--spacing-xl);
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: var(--font-sm);
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    position: relative;
    overflow: hidden;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.btn-blue {
    background: var(--blue-color);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    padding: var(--spacing-sm) var(--spacing-md);
    transition: var(--transition);
}

.btn-blue:hover {
    background: var(--blue-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Back Button */
.btn-back {
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
}

.btn-back:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.btn-back i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}


/* Enhanced Signature Section */
.signature-container {
    background: linear-gradient(135deg, #dcfce7 0%, #f0fdf4 100%);
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-xl);
    text-align: center;
    margin: var(--spacing-xl) 0;
    border: 1px solid rgba(34, 197, 94, 0.2);
}

.signature-image {
    max-width: 200px;
    border-bottom: 3px solid var(--primary-color);
    padding: var(--spacing-lg);
    background: var(--surface-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    margin: var(--spacing-lg) auto;
}



/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tab-content {
    animation: fadeIn 0.3s ease-out;
}

/* Enhanced Responsive Design */
@media (max-width: 768px) {
    :root {
        --spacing-xl: 1.5rem;
        --spacing-lg: 1rem;
    }

    .tab-navigation {
        flex-wrap: wrap;
        padding: var(--spacing-sm);
        gap: var(--spacing-xs);
    }
    
    .tab-button {
        flex: 1 1 auto;
        min-width: 120px;
        text-align: center;
        font-size: calc(var(--font-sm) - 1px);
    }
    
    .edit-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .content {
        margin: var(--spacing-sm);
        padding: var(--spacing-lg);
    }

    h1 {
        font-size: var(--font-xl);
    }
}

/* Accessibility Improvements */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

.screen-reader-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

    </style>
</head>
<body>
    <br>
    <div class= "container">
  
    

    <?php if (!$studentNotFound): ?>
        <!-- Tab Navigation -->
        <div class="tab-navigation">
    <div class="header-section">
    <h1 class="header-title">Student Profile Form For Inventory</h1>
        <a href="view_select_sections.php?department_id=<?php echo urlencode($department_id); ?>&course_id=<?php echo urlencode($course_id); ?>&year_level=<?php echo urlencode($year_level); ?>&section_id=<?php echo urlencode($section_id); ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Student List
        </a>
       
    </div>
    <div class="tab-buttons">
        <button class="tab-button active" onclick="openTab(event, 'personalInfo')">Personal Info</button>
        <button class="tab-button" onclick="openTab(event, 'educationalInfo')">Educational Info</button>
        <button class="tab-button" onclick="openTab(event, 'familyBackground')">Family Background</button>
        <button class="tab-button" onclick="openTab(event, 'careerInfo')">Career Info</button>
        <button class="tab-button" onclick="openTab(event, 'medicalHistory')">Medical History</button>
    </div>
</div>
    <div class="content">
           

            <!-- Personal Information Tab -->
            <div id="personalInfo" class="tab-content active">
                <h2>Personal Information</h2>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_name']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Student ID:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['department_name']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Course:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['course_name']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Permanent Address:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['permanent_address'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Current Address:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['current_address'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Contact Number:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['contact_number'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Gender:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['gender']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Birthdate:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['birthdate'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Age:</label>
                    <input type="text" class="form-control readonly-input" value="<?php 
                        // Check if birthdate exists
                        if (!empty($student['birthdate'])) {
                            $birthdate = new DateTime($student['birthdate']);
                            $today = new DateTime();
                            $age = $birthdate->diff($today)->y;
                            echo $age;
                        } else {
                            echo "N/A";
                        }
                    ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Civil Status:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['civil_status'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Religion:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['religion'] ?? ''); ?>" readonly>
                </div>
            </div>

            <!-- Educational Information Tab -->
            <div id="educationalInfo" class="tab-content">
                <h2>Educational Information</h2>
                <div class="form-group">
                    <label>Year Level:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['year_level'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Semester First Enrolled:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['semester_first_enrolled'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Elementary:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['elementary'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Secondary:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['secondary'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Transferee:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['transferees'] ?? ''); ?>" readonly>
                </div>
            </div>

            <!-- Family Background Tab -->
            <div id="familyBackground" class="tab-content">
                <h2>Family Background</h2>
                <div class="form-group">
                    <label>Father's Name:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Father's Contact Number:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['father_contact'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Father's Occupation:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['father_occupation'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Mother's Name:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Mother's Contact:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['mother_contact'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Mother's Occupation:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['mother_occupation'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Guardian's Name:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_name'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Guardian's Relationship:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_relationship'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Guardian's Contact:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_contact'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Guardian's Occupation:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['guardian_occupation'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Number of Siblings:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['siblings'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Birth Order:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['birth_order'] ?? ''); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Family Income:</label>
                    <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['family_income'] ?? ''); ?>" readonly>
                </div>
            </div>

            <!-- Career Information Tab -->
            <div id="careerInfo" class="tab-content">
                <h2>Career Information</h2>
                <div class="form-group">
                    <label>Course Factors:</label>
                    <textarea class="form-control readonly-input" rows="3" readonly><?php echo htmlspecialchars($student['course_factors'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Career Concerns:</label>
                    <textarea class="form-control readonly-input" rows="3" readonly><?php echo htmlspecialchars($student['career_concerns'] ?? ''); ?></textarea>
                </div>
            </div>

           <!-- Medical History Tab -->
<!-- Medical History Tab -->
<div id="medicalHistory" class="tab-content">
    <h2>Medical History</h2>
    
    <!-- Medications Section -->
    <div class="form-group">
        <label>Medications:</label>
        <?php if (($student['medications'] ?? '') == 'NO MEDICATIONS'): ?>
        <div class="form-check disabled">
            <input type="checkbox" class="form-check-input" checked disabled>
            <label class="form-check-label">No, I don't take any medications</label>
        </div>
        <?php else: ?>
        <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['medications'] ?? ''); ?>" readonly>
        <?php endif; ?>
    </div>
    
    <!-- Medical Conditions Section -->
    <div class="form-group">
        <label>Do you have any of the following?</label>
        
        <?php if ($student['medical_conditions'] == 'NO MEDICAL CONDITIONS'): ?>
        <div class="form-check disabled">
            <input type="checkbox" class="form-check-input" checked disabled>
            <label class="form-check-label">No, I don't have any of these medical conditions</label>
        </div>
        <?php else: ?>
            <?php
            $conditions = ['Asthma', 'Hypertension', 'Diabetes', 'Insomnia', 'Vertigo'];
            $existing_conditions = explode(", ", $student['medical_conditions'] ?? '');
            
            // Only show checked conditions
            foreach ($conditions as $condition):
                if (in_array($condition, $existing_conditions)):
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label"><?php echo htmlspecialchars($condition); ?></label>
            </div>
            <?php 
                endif;
            endforeach;
            
            // Check for other condition
            $other_condition = '';
            $has_other_condition = false;
            foreach ($existing_conditions as $condition) {
                if (strpos($condition, 'Other:') === 0) {
                    $other_condition = trim(substr($condition, 6));
                    $has_other_condition = true;
                    break;
                }
            }
            
            // Check for allergy
            $allergy = '';
            $has_allergy = false;
            foreach ($existing_conditions as $condition) {
                if (strpos($condition, 'Allergy:') === 0) {
                    $allergy = trim(substr($condition, 8));
                    $has_allergy = true;
                    break;
                }
            }
            
            // Check for scoliosis
            $scoliosis = '';
            $has_scoliosis = false;
            foreach ($existing_conditions as $condition) {
                if (strpos($condition, 'Scoliosis/Physical condition:') === 0) {
                    $scoliosis = trim(substr($condition, 29));
                    $has_scoliosis = true;
                    break;
                }
            }
            
            // Show "No other medical conditions" if applicable
            if (!$has_other_condition): 
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">No other medical conditions</label>
            </div>
            <?php 
            endif;
            
            // Show other condition if present
            if ($has_other_condition): 
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">Other medical condition: <?php echo htmlspecialchars($other_condition); ?></label>
            </div>
            <?php 
            endif;
            
            // Show "No allergies" if applicable
            if (!$has_allergy): 
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">No allergies</label>
            </div>
            <?php 
            endif;
            
            // Show allergy if present
            if ($has_allergy): 
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">Allergy - specifically, allergic to: <?php echo htmlspecialchars($allergy); ?></label>
            </div>
            <?php 
            endif;
            
            // Show "No scoliosis" if applicable
            if (!$has_scoliosis): 
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">No scoliosis or physical condition</label>
            </div>
            <?php 
            endif;
            
            // Show scoliosis if present
            if ($has_scoliosis): 
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">Scoliosis or physical condition: <?php echo htmlspecialchars($scoliosis); ?></label>
            </div>
            <?php 
            endif;
            ?>
        <?php endif; ?>
    </div>
    
    <!-- Suicide Attempt -->
    <div class="form-group">
        <label>Suicide Consideration/Attempt:</label>
        <?php if (($student['suicide_attempt'] ?? '') == 'yes'): ?>
        <div class="form-check disabled">
            <input type="radio" class="form-check-input" checked disabled>
            <label class="form-check-label">Yes</label>
        </div>
        
        <?php if (!empty($student['suicide_reason'])): ?>
        <div class="form-group mt-2">
            <label>Explanation:</label>
            <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['suicide_reason']); ?>" readonly>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="form-check disabled">
            <input type="radio" class="form-check-input" checked disabled>
            <label class="form-check-label">No</label>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Personal Problems -->
    <div class="form-group">
        <label>Problems:</label>
        <?php if ($student['problems'] == 'NO PROBLEMS'): ?>
        <div class="form-check disabled">
            <input type="checkbox" class="form-check-input" checked disabled>
            <label class="form-check-label">No, I don't have any problems</label>
        </div>
        <?php else: ?>
            <?php
            $problems = ['Alcohol/Substance Abuse', 'Eating Disorder', 'Depression', 'Aggression'];
            $existing_problems = array_map('trim', explode(";", $student['problems'] ?? ''));
            $problem_others = '';
            
            foreach ($existing_problems as $key => $problem) {
                if (strpos($problem, 'Others:') === 0) {
                    $problem_others = trim(substr($problem, 7));
                    unset($existing_problems[$key]);
                }
            }
            
            // Only show checked problem options
            foreach ($problems as $problem):
                if (in_array($problem, $existing_problems)):
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label"><?php echo htmlspecialchars($problem); ?></label>
            </div>
            <?php 
                endif;
            endforeach;
            
            // Show Others if specified
            if (!empty($problem_others)):
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">Others: <?php echo htmlspecialchars($problem_others); ?></label>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Family Problems -->
    <div class="form-group">
        <label>Family Problems:</label>
        <?php if ($student['family_problems'] == 'NO PROBLEMS'): ?>
        <div class="form-check disabled">
            <input type="checkbox" class="form-check-input" checked disabled>
            <label class="form-check-label">No, they don't have any problems</label>
        </div>
        <?php else: ?>
            <?php
            $family_problems = ['Alcohol/Substance Abuse', 'Eating Disorder', 'Depression', 'Aggression'];
            $existing_family_problems = array_map('trim', explode(";", $student['family_problems'] ?? ''));
            $family_other_problem = '';
            
            foreach ($existing_family_problems as $key => $problem) {
                if (strpos($problem, 'Others:') === 0) {
                    $family_other_problem = trim(substr($problem, 7));
                    unset($existing_family_problems[$key]);
                }
            }
            
            // Only show checked family problem options
            foreach ($family_problems as $problem):
                if (in_array($problem, $existing_family_problems)):
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label"><?php echo htmlspecialchars($problem); ?></label>
            </div>
            <?php 
                endif;
            endforeach;
            
            // Show Others if specified
            if (!empty($family_other_problem)):
            ?>
            <div class="form-check disabled">
                <input type="checkbox" class="form-check-input" checked disabled>
                <label class="form-check-label">Others: <?php echo htmlspecialchars($family_other_problem); ?></label>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Fitness Activity -->
    <div class="form-group">
        <label>Physical Fitness Activity:</label>
        <?php if (($student['fitness_activity'] ?? '') == 'NO FITNESS'): ?>
        <div class="form-check disabled">
            <input type="radio" class="form-check-input" checked disabled>
            <label class="form-check-label">No</label>
        </div>
        <?php else: ?>
        <div class="form-check disabled">
            <input type="radio" class="form-check-input" checked disabled>
            <label class="form-check-label">Yes</label>
        </div>
        
        <?php if (!empty($student['fitness_activity'])): ?>
        <div class="form-group mt-2">
            <label>Activity:</label>
            <input type="text" class="form-control readonly-input" value="<?php echo htmlspecialchars($student['fitness_activity']); ?>" readonly>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($student['fitness_frequency'])): ?>
        <div class="form-group mt-2">
            <label>Frequency:</label>
            <?php if (($student['fitness_frequency'] ?? '') == 'Everyday'): ?>
            <div class="form-check disabled">
                <input type="radio" class="form-check-input" checked disabled>
                <label class="form-check-label">Everyday</label>
            </div>
            <?php elseif (($student['fitness_frequency'] ?? '') == '2-3 Week'): ?>
            <div class="form-check disabled">
                <input type="radio" class="form-check-input" checked disabled>
                <label class="form-check-label">2-3 times a week</label>
            </div>
            <?php elseif (($student['fitness_frequency'] ?? '') == '2-3 Month'): ?>
            <div class="form-check disabled">
                <input type="radio" class="form-check-input" checked disabled>
                <label class="form-check-label">2-3 times a month</label>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Stress Level -->
    <div class="form-group">
        <label>Stress Level:</label>
        <?php if (($student['stress_level'] ?? '') == 'low'): ?>
        <div class="form-check disabled">
            <input type="radio" class="form-check-input" checked disabled>
            <label class="form-check-label">Low (1-3)</label>
        </div>
        <?php elseif (($student['stress_level'] ?? '') == 'average'): ?>
        <div class="form-check disabled">
            <input type="radio" class="form-check-input" checked disabled>
            <label class="form-check-label">Average (4-7)</label>
        </div>
        <?php elseif (($student['stress_level'] ?? '') == 'high'): ?>
        <div class="form-check disabled">
            <input type="radio" class="form-check-input" checked disabled>
            <label class="form-check-label">High (8-10)</label>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Signature Section -->
    <div class="signature-container">
        <h3>Student Signature</h3>
        <p>I hereby attest that all information stated above is true and correct.</p>
        <?php if (!empty($student['signature_path'])): ?>
            <img src="<?php echo htmlspecialchars($student['signature_path']); ?>" alt="Student Signature" class="signature-image">
        <?php else: ?>
            <p>No signature available</p>
        <?php endif; ?>
    </div>
</div>
      <!--
        <p class="student-name">
            <?php
            $middleInitial = !empty($student['middle_name']) ? strtoupper(substr($student['middle_name'], 0, 1)) . '.' : '';
            $formattedName = strtoupper($student['first_name'] . ' ' . $middleInitial . ' ' . $student['last_name']);
            echo htmlspecialchars($formattedName);
            ?>
        </p>
            -->
    </div>
    
    <!-- Edit buttons moved inside Medical History tab -->
    <div class="edit-buttons">
        <a href="archive_single_student_profile.php?student_id=<?php echo $student_id; ?>" class="btn btn-warning"  onclick="return confirm('Are you sure you want to Archive this profile? This action cannot be undone.');">Archive Profile</a>

       


        <a href="edit_personal_info.php?student_id=<?php echo $student_id; ?>" class="btn btn-primary">Edit Profile</a>
        <a href="view_student_profile-generate_pdf.php?student_id=<?php echo $student_id; ?>" class="btn btn-blue" target="_blank">Export to PDF</a>
             </div>
</div>
        
    <?php else: ?>
        <div id="errorModal" class="modal fade show" tabindex="-1" role="dialog" style="display: block;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error</h5>
                    </div>
                    <div class="modal-body">
                        <p>Student Profile not found. Redirecting to SELECT SECTION NUMBER page in <span id="countdown">5</span> seconds...</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

 

    <script>
        // Enhanced tab switching with smooth transitions
        function openTab(evt, tabName) {
            const tabcontent = document.getElementsByClassName("tab-content");
            const tablinks = document.getElementsByClassName("tab-button");
            
            // Hide all tabs with transition
            Array.from(tabcontent).forEach(tab => {
                tab.style.opacity = '0';
                tab.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    tab.style.display = 'none';
                }, 300);
            });

            // Remove active class from all buttons
            Array.from(tablinks).forEach(link => {
                link.className = link.className.replace(" active", "");
            });

            // Show selected tab with transition
            setTimeout(() => {
                const selectedTab = document.getElementById(tabName);
                selectedTab.style.display = 'block';
                setTimeout(() => {
                    selectedTab.style.opacity = '1';
                    selectedTab.style.transform = 'translateY(0)';
                }, 50);
            }, 300);

            evt.currentTarget.className += " active";
        }

        // Initialize the page with the first tab
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.tab-button').click();
            
            // Add smooth scroll behavior
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });

        // Countdown and redirect for error modal
        <?php if ($studentNotFound): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var seconds = 5;
            var countdownElement = document.getElementById('countdown');
            var intervalId = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(intervalId);
                    var redirectUrl = 'view_select_sections.php?' +
                        'department_id=<?php echo urlencode($department_id); ?>&' +
                        'course_id=<?php echo urlencode($course_id); ?>&' +
                        'year_level=<?php echo urlencode($year_level); ?>&' +
                        'section_id=<?php echo urlencode($section_id); ?>';
                    window.location.href = redirectUrl;
                }
            }, 1000);
        });
        <?php endif; ?>
    </script>
</body>
</html>