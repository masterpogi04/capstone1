<?php
session_start();
require_once '../vendor/autoload.php';
include '../db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    die("Student ID is required");
}

// Fetch student profile data
$stmt = $connection->prepare("
    SELECT sp.*, s.department_id, s.course_id, d.name as department_name, c.name as course_name,
           sp.signature_path
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
$profile = $result->fetch_assoc();

// Close the database connection
$stmt->close();
$connection->close();

// Prepare the signature image
$signature_html = '';
if (!empty($profile['signature_path'])) {
    $full_signature_path = 'C:/xampp/htdocs' . $profile['signature_path'];
    if (file_exists($full_signature_path)) {
        $signature_data = base64_encode(file_get_contents($full_signature_path));
        $signature_html = '<img src="data:image/png;base64,' . $signature_data . '" alt="Student Signature" style="max-width: 200px; max-height: 100px;">';
    }
}
// Get the logo path - assuming logo.png is in the same directory
$logo_path = __DIR__ . '/logo.png';
$logo_data = base64_encode(file_get_contents($logo_path));

// Helper function to generate checkbox HTML
function checkbox($name, $value, $checkedValues) {
    $checkedValues = explode(', ', $checkedValues);
    $checked = in_array($value, $checkedValues) || stripos(implode(', ', $checkedValues), $value) !== false ? 'checked' : '';
    return "<input type='checkbox' name='$name' value='$value' $checked> $value";
}

// New function specifically for gender/sex checkboxes
function genderCheckbox($name, $value, $gender) {
    $checked = strtoupper($gender) === strtoupper($value) ? 'checked' : '';
    return "<input type='checkbox' name='$name' value='$value' $checked> $value";
}

function getValueAfterPrefix($string, $prefix) {
    if (strpos($string, $prefix) === false) {
        return '';
    }
    
    $start = strpos($string, $prefix) + strlen($prefix);
    $substring = substr($string, $start);
    
    // Get everything before the next comma if it exists
    $end = strpos($substring, ',');
    if ($end !== false) {
        $substring = substr($substring, 0, $end);
    }
    
    return trim($substring);
}

// Update the style section in the HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Profile Form for Inventory</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            line-height: 1.2;
            padding: 20px;
            margin: 0;
        }
        
        .letterhead {
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        
         .logo {
            position: absolute;
            left: 100px;  /* Adjust this value to align with text */
            top: 0;
            width: 110px;
            height: 100px;
        }
        
        .letterhead-text {
            text-align: center;
            line-height: 1.2;
        }
        
        .letterhead-text h1 {
            font-size: 18px;
            margin: 0;
            font-family: "Times New Roman", serif;
            font-weight: bold;
        }
        
        .letterhead-text h2 {
            font-size: 14px;
            margin: 2px 0;
        }
        
        .letterhead-text p {
            font-size: 12px;
            margin: 2px 0;
        }
        
        .form-number {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 11px;
            font-style: italic;
        }
        
        .form-title {
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
            font-size: 14px;
        }
        .underline {
            border-bottom: 1px solid black;
            display: inline-block;
            
            margin: 0 5px;
        }
        
        /* personal section */

         .personal-section {
            font-size: 11px;
            line-height: 1.2;
        }
        .personal-section h4 {
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .full-name {
            margin-bottom: 10px;
        }

        .name-container {
            margin-top: 5px;
        }

        .name-line {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid black;
            margin-bottom: 2px;
        }

        .name-field {
            flex: 1;
            text-align: center;
            padding: 0 10px;
        }

        .name-labels {
            display: flex;
            justify-content: space-between;

        }

        .label-text {
            flex: 1;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .underline-field {
            border-bottom: 1px solid black;
            min-height: 14px;
            display: block;
            margin-bottom: 1px;
        }

        .field-label {
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        .contact-info {
            margin-bottom: 5px;
            font-size: 12px;
        }

        .address-field {
            margin-bottom: 3px;
            font-size: 11px;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .address-field label {
            white-space: nowrap;
            margin-right: 5px;
        }

        .address-field .underline {
            flex: 1;
            border-bottom: 1px solid black;
            display: inline-block;
            min-width: 400px;
            height: 1.2em;
            margin: 0;
            padding: 0;
        }

        .note {
            font-size: 11px;
            font-style: italic;
            margin-left: 20%;
        }

        .contact-row {
            display: flex;
            gap: 20px;
            font-size: 12px;
        }

        .contact-field {
            flex: 1;
        }

        .info-row {
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
        }

        .underline-field-small {
            border-bottom: 1px solid black;
            display: inline-block;
            width: 30px;
        }

        .underline-field-med {
            border-bottom: 1px solid black;
            display: inline-block;
            width: 100px;
        }

        .underline-field-large {
            border-bottom: 1px solid black;
            display: inline-block;
            width: 250px;
        }

        .ml-3 {
            margin-left: 35px;
        }

        label {
            font-weight: normal;
        }

        .course-info {
            margin-top: 5px;
        }

        /* family section */

        .family-section {
        margin: 8px 0 5px 0;
        font-size: 12px;
        }

        .family-section h4 {
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .family-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
            font-size: 12px;
        }

        .family-table th, .family-table td {
            padding: 1px 5px;
            text-align: center;
            font-size: 12px;
            line-height: 1.2;
        }

        .family-table th {
            font-weight: normal;
        }

        .family-table .relationship-note {
            font-size: 10px;
            font-style: italic;
            
            text-align: center;
            margin-top: -2px;
        }

        .underline-field {
            border-bottom: 1px solid black;
            display: inline-block;
            min-width: 120px;
            height: 12px;
        }

        .birth-order {
            margin-top: 2px;
            line-height: 1.2;
        }

        .birth-order label {
            margin-right: 15px;
            font-size: 10px;
        }

        .income-section {
            margin: 5px 0;
            font-size: 12px;
            line-height: 1.2;
        }

        .income-row {
            display: flex;
            align-items: center;
            flex-wrap: nowrap;
            gap: 10px;
            margin-top: 3px;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            margin-right: 10px;
        }

        .checkbox-label input[type="checkbox"] {
            transform: scale(0.8);
            margin-right: 3px;
        }

        /* education kinemberlu */

        .educational-section {
            margin: 8px 0 5px 0;
        }

        .educational-section h4 {
            font-family: Arial, sans-serif;
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .educational-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
        }

        .educational-table th,
        .educational-table td {
            padding: 2px 4px;
            text-align: center;
            font-size: 12px;
            line-height: 1.2;
            vertical-align: bottom;
        }

        .educational-table th {
            font-weight: normal;
        }

        .educational-table .underline-field {
            border-bottom: 1px solid black;
            display: block;
            min-height: 14px;
            margin-bottom: 1px;
        }

        .course-label {
            font-size: 12px;
            display: block;
            text-align: center;
            font-weight: normal;
        }

        /* career hihi */

        .career-section {
                margin: 8px 0 5px 0;
            }

            .career-section h4 {
                font-family: Arial, sans-serif;
                font-size: 14px;
                font-weight: bold;
                margin: 0 0 3px 0;
                text-transform: uppercase;
                text-decoration: underline;
            }

            .career-factors {
                margin: 5px 0;
                font-size: 11px;
                line-height: 1.4;
            }

            .career-factors div {
                margin-bottom: 2px; /* Smaller margin between rows */
            }

            .career-factors input[type="checkbox"] {
                margin-right: 5px; /* Smaller space between checkbox and label */
            }

            .checkbox-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 5px;
                margin: 5px 0;
            }

            .version-number {
                text-align: right;
                font-size: 11px;
                margin-top: 20px;
                color: #000;
                padding: 2px 5px;
                display: inline-block;
                position: absolute;
                bottom: 20px;
                right: 20px;
            }

            .page-break {
                page-break-before: always;
            }


        /* medical */

        .medical-section {
            margin: 8px 0 5px 0;
            page-break-inside: avoid;
        }

        .medical-section h4 {
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .medical-field {
            margin: 3px 0;
            font-size: 11px;
            line-height: 1.2;
        }

        .medical-checkbox-group {
            margin-left: 20px;
            line-height: 1.4;
        }

        .checkbox-row {
            margin: 2px 0;
            font-size: 11px;
        }

        .medical-underline {
            border-bottom: 1px solid black;
            display: inline-block;
            min-width: 200px;
            font-size: 11px;
        }

        .checkbox-inline {
            margin-right: 15px;
            display: inline-block;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <div class="form-number">OSAS-QF-12</div>
    
    <div class="letterhead">
        
        <div class="letterhead-text">
            <p>Republic of the Philippines</p>
            <img src="data:image/png;base64,' . $logo_data . '" class="logo">
            <h1>CAVITE STATE UNIVERSITY</h1>
            <h2>Don Severino delas Alas Campus</h2>
            <p>Indang, Cavite</p>
        </div>
    </div>
    
    <div class="form-title">STUDENT PROFILE FORM FOR INVENTORY</div>

    <div class="personal-section">
    <div class="full-name">
       <h4>FULL NAME:</h4>
        <div class="name-container">
    <div class="name-line" style="display: flex; justify-content: space-around; align-items: center;">
        <span style="flex: 1; text-align: center; margin-left:5%;">
            ' . htmlspecialchars($profile['last_name']) . '
        </span>
        <span style="flex: 1; text-align: center; margin-left:25%;">
            ' . htmlspecialchars($profile['first_name']) . '
        </span>
        <span style="flex: 1; text-align: center; margin-left:25%;">
            ' . htmlspecialchars($profile['middle_name']) . '
        </span>
    </div>
    <div class="name-labels" style="display: flex; justify-content: space-around; align-items: center; margin-top: 5px;">
        <span style="flex: 1; text-align: center; font-size: 11px; font-style: italic; margin-left:5%;">Last Name</span>
        <span style="flex: 1; text-align: center; font-size: 11px; font-style: italic; margin-left:25%;">First Name</span>
        <span style="flex: 1; text-align: center; font-size: 11px; font-style: italic; margin-left:30%;">Middle Name</span>
    </div>
</div>

    </div>
</div>

    <div class="contact-info">
        <div class="address-field">
            <label>Permanent Address:</label>
            <span class="underline" style="width: 82%;">
                ' . htmlspecialchars($profile['permanent_address']) . '
            </span>
        </div>

        <div class="address-field">
            <label>Current Address:</label>
            <span class="underline" style="width: 85%;">
                ' . htmlspecialchars($profile['current_address']) . '
            </span> <br>
            <span class="note">(if current address is not the same with permanent address)</span>
        </div>

        <div class="form-group">
        <label>Contact number:</label>
        <span class="underline" style="width: 31%;">' . $profile['contact_number'] . '</span>
        <label>E-mail Address:</label>
        <span class="underline" style="width: 38%;">' . $profile['email'] . '</span>
    </div>
    </div>

    <div class="personal-info">
        <div class="info-row">
            <label>Sex:</label>
            ' . genderCheckbox('sex', 'Male', $profile['gender']) . ' 
            ' . genderCheckbox('sex', 'Female', $profile['gender']) . ' 
            
            <label class="ml-3">Age:</label>
            <span class="underline-field-small" style=" text-align:center;">
                ' . htmlspecialchars($profile['age']) . '
            </span>
            
            <label class="ml-3">Date of Birth:</label>
            <span class="underline-field-med" style=" text-align:center;">
                ' . htmlspecialchars($profile['birthdate']) . '
            </span>
            
            <label class="ml-3">Place of Birth:</label>
            <span class="underline-field-med" style=" text-align:center;">
                ' . htmlspecialchars($profile['birthplace']) . '
            </span>
        </div>
    </div>

        <div class="info-row">
            <label>Nationality:</label>
            <span class="underline-field-med" style="width: 250px;  text-align:center;">
                ' . htmlspecialchars($profile['nationality']) . '
            </span>
            
            <label class="ml-3">Religion:</label>
            <span class="underline-field-med" style="width: 260px; text-align:center;">
                ' . htmlspecialchars($profile['religion']) . '
            </span>
        </div>

        <div class="info-row">
            <label>Civil Status:</label>
            ' . checkbox('civil_status', 'Single', $profile['civil_status']) . ' 
            ' . checkbox('civil_status', 'Married', $profile['civil_status']) . ' ,name of spouse:
            <span class="underline-field-med" style="width: 24%;">
                ' . htmlspecialchars($profile['spouse_name']) . '
            </span>
            ' . checkbox('civil_status', 'Widowed', $profile['civil_status']) . ' 
            ' . checkbox('civil_status', 'Separated', $profile['civil_status']) . ' 
            ' . checkbox('civil_status', 'Divorced', $profile['civil_status']) . ' 
        </div>

        <div class="course-info">
            <div class="info-row">
                <label>Course and Year:</label>
                <span class="underline-field-large" style="width: 37%;">
                    ' . htmlspecialchars($profile['course_name']) . ' - ' . htmlspecialchars($profile['year_level']) . '
                </span>
                
                <label class="ml-3">Student number:</label>
                <span class="underline-field-med" style="width: 28%; text-align:center;">
                    ' . htmlspecialchars($profile['student_id']) . '
                </span>
            </div>

            <div class="info-row">
                <label>Semester and School Year you first enrolled in CvSU:</label>
                <span class="underline-field-large" style="width: 56%;">
                    ' . htmlspecialchars($profile['semester_first_enrolled']) . '
                </span>
            </div>
        </div>
    </div>
</div>



   <div class="family-section">
    <h4>FAMILY BACKGROUND</h4>
    <table class="family-table">
        <tr>
            <th></th>
            <th>Father</th>
            <th>Mother</th>
            <th>
                Guardian
                <span class="underline-field" style="width: 60px;">
                    ' . htmlspecialchars($profile['guardian_relationship']) . '
                </span>
                <div class="relationship-note">(Specify relationship)</div>
            </th>
        </tr>
        <tr>
            <td style="text-align:left;">Full Name:</td>
            <td><span class="underline-field">' . htmlspecialchars($profile['father_name']) . '</span></td>
            <td><span class="underline-field">' . htmlspecialchars($profile['mother_name']) . '</span></td>
            <td><span class="underline-field">' . htmlspecialchars($profile['guardian_name']) . '</span></td>
        </tr>
        <tr>
            <td style="text-align:left;">Contact No:</td>
            <td><span class="underline-field">' . htmlspecialchars($profile['father_contact']) . '</span></td>
            <td><span class="underline-field">' . htmlspecialchars($profile['mother_contact']) . '</span></td>
            <td><span class="underline-field">' . htmlspecialchars($profile['guardian_contact']) . '</span></td>
        </tr>
        <tr>
            <td style="text-align:left;">Occupation:</td>
            <td><span class="underline-field">' . htmlspecialchars($profile['father_occupation']) . '</span></td>
            <td><span class="underline-field">' . htmlspecialchars($profile['mother_occupation']) . '</span></td>
            <td><span class="underline-field">' . htmlspecialchars($profile['guardian_occupation']) . '</span></td>
        </tr>
    </table>
    <br>
    <div class="birth-order">
        Number of sibling/s: <span class="underline-field" style="width:30px; text-align:center;">' . htmlspecialchars($profile['siblings']) . '</span>
        Birth Order:
        ' . checkbox('birth_order', 'Eldest', ($profile['birth_order'] == 'Eldest' ? 'Eldest' : '')) . '
        ' . checkbox('birth_order', 'Second', ($profile['birth_order'] == 'Second' ? 'Second' : '')) . '
        ' . checkbox('birth_order', 'Middle', ($profile['birth_order'] == 'Middle' ? 'Middle' : '')) . '
        ' . checkbox('birth_order', 'Youngest', ($profile['birth_order'] == 'Youngest' ? 'Youngest' : '')) . '
        ' . checkbox('birth_order', 'Only Child', ($profile['birth_order'] == 'Only Child' ? 'Only Child' : '')) . '
    </div>
    <br>
   <div class="income-section">
    <div><strong>Estimated Monthly Family Income: (Please tick the appropriate box)</strong></div>
    <div class="income-row">
        ' . checkbox('family_income', 'below-10,000', $profile['family_income']) . '
        ' . checkbox('family_income', '11,000 – 20,000', $profile['family_income']) . '
        ' . checkbox('family_income', '21,000 – 30,000', $profile['family_income']) . '
        ' . checkbox('family_income', '31,000 – 40,000', $profile['family_income']) . '
        ' . checkbox('family_income', '41,000 – 50,000', $profile['family_income']) . '
        ' . checkbox('family_income', 'above 50,000', $profile['family_income']) . '
    </div>
</div>
</div>

<div class="educational-section">
    <h4>EDUCATIONAL BACKGROUND</h4>
    <table class="educational-table">
        <tr>
            <th style="width: 15%;"></th>
            <th style="width: 35%;">Name of School</th>
            <th style="width: 35%;">Address</th>
            <th style="width: 15%;">Year Graduated</th>
        </tr>
        <tr>
            <td style="text-align:left;">Elementary</td>
            <td>
                <span class="underline-field">
                    ' . (($info = explode(';', htmlspecialchars($profile['elementary']))) && isset($info[0]) ? $info[0] : '') . '
                </span>
            </td>
            <td>
                <span class="underline-field">
                    ' . (isset($info[1]) ? $info[1] : '') . '
                </span>
            </td>
            <td>
                <span class="underline-field">
                    ' . (isset($info[2]) ? $info[2] : '') . '
                </span>
            </td>
        </tr>
        <tr>
            <td style="text-align:left;">Secondary/SHS</td>
            <td>
                <span class="underline-field">
                    ' . (($info = explode(';', htmlspecialchars($profile['secondary']))) && isset($info[0]) ? $info[0] : '') . '
                </span>
            </td>
            <td>
                <span class="underline-field">
                    ' . (isset($info[1]) ? $info[1] : '') . '
                </span>
            </td>
            <td>
                <span class="underline-field">
                    ' . (isset($info[2]) ? $info[2] : '') . '
                </span>
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td>
                <span class="course-label">Course taken</span>
            </td>
        </tr>
        <tr>
            <td style="text-align:left;">For transferees:</td>
            <td>
                <span class="underline-field">
                    ' . (($info = explode(';', htmlspecialchars($profile['transferees']))) && isset($info[0]) ? $info[0] : '') . '
                </span>
            </td>
            <td>
                <span class="underline-field">
                    ' . (isset($info[1]) ? $info[1] : '') . '
                </span>
            </td>
            <td>
                <span class="underline-field">
                    ' . (isset($info[2]) ? $info[2] : '') . '
                </span>
            </td>
        </tr>
    </table>
</div>

    <div class="career-section">
    <h4>CAREER EXPLORATION INFORMATION</h4>
    
    <div class="career-factors">
        <label>What factors have influenced you most in choosing your course? Check [✓] at least three.</label>
        <div style="margin: 10px 0;">
            <div style="margin-bottom: 5px;">
                <input type="checkbox" ' . (strpos($profile['course_factors'], 'Financial Security after graduation') !== false ? 'checked' : '') . '> Financial Security after graduation
                <span style="margin-left: 35px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Parents Decision/Choice') !== false ? 'checked' : '') . '> Parents Decision/Choice
                </span>
                <span style="margin-left: 35px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Opportunity to help others/society') !== false ? 'checked' : '') . '> Opportunity to help others/society
                </span>
            </div>
            
            <div style="margin-bottom: 5px;">
                <input type="checkbox" ' . (strpos($profile['course_factors'], 'Childhood Dream') !== false ? 'checked' : '') . '> Childhood Dream
                <span style="margin-left: 117px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Status Recognition') !== false ? 'checked' : '') . '> Status Recognition
                </span>
                <span style="margin-left: 63px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Challenge/Adventure') !== false ? 'checked' : '') . '> Challenge/Adventure
                </span>
            </div>
            
            <div style="margin-bottom: 5px;">
                <input type="checkbox" ' . (strpos($profile['course_factors'], 'Leisure/Enjoyment') !== false ? 'checked' : '') . '> Leisure/Enjoyment
                <span style="margin-left: 112px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Independence') !== false ? 'checked' : '') . '> Independence
                </span>
                <span style="margin-left: 85px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Location of School') !== false ? 'checked' : '') . '> Location of School
                </span>
            </div>
            
            <div style="margin-bottom: 5px;">
                <input type="checkbox" ' . (strpos($profile['course_factors'], 'Pursuit of Knowledge') !== false ? 'checked' : '') . '> Pursuit of Knowledge
                <span style="margin-left: 100px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Moral Fulfilment') !== false ? 'checked' : '') . '> Moral Fulfilment
                </span>
                <span style="margin-left: 76px;">
                    <input type="checkbox" ' . (strpos($profile['course_factors'], 'Peer Influence') !== false ? 'checked' : '') . '> Peer Influence
                </span>
            </div>
            
            <div style="margin-top: 10px;">
                <input type="checkbox" ' . (strpos($profile['course_factors'], 'Other:') !== false ? 'checked' : '') . '> Other reason/s: 
                <span class="underline" style="width: 80%;">
                    ' . (strpos($profile['course_factors'], 'Other:') !== false ? 
                        htmlspecialchars(substr($profile['course_factors'], strpos($profile['course_factors'], 'Other:') + 7)) : 
                        '') . '
                </span>
            </div>
        </div>
    </div>

    <div class="version-number">V01-2018-05-28</div>
</div>

<div class="page-break"></div>

<div class="career-section">
    <div class="career-factors">
        <label><strong>Current Career Concerns:</strong></label>
        <div style="margin: 5px 0 0 20px;">
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'I need more information about my personal traits') !== false ? 'checked' : '') . '> I need more information about my personal traits, interests, skills, and values
                </label>
            </div>
            
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'I need more information about certain course') !== false ? 'checked' : '') . '> I need more information about certain course/s and occupation/s, specifically:
                    <span style="text-decoration: underline; display: inline-block; min-width: 200px;">
                        ' . (function($concerns) {
                            $concerns_array = explode('; ', $concerns);
                            foreach ($concerns_array as $concern) {
                                if (strpos($concern, 'I need more information about certain course/s and occupation/s:') === 0) {
                                    return htmlspecialchars(trim(substr($concern, strlen('I need more information about certain course/s and occupation/s:'))));
                                }
                            }
                            return '';
                        })($profile['career_concerns']) . '
                    </span>
                </label>
            </div>

            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'I have difficulty making a career decision') !== false ? 'checked' : '') . '> I have difficulty making a career decision/goal-setting
                </label>
            </div>
            
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'I have many goals that conflict') !== false ? 'checked' : '') . '> I have many goals that conflict with each other
                </label>
            </div>
            
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'My parents have different goals') !== false ? 'checked' : '') . '> My parents have different goals for me
                </label>
            </div>
            
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'I think I am not capable') !== false ? 'checked' : '') . '> I think I am not capable of anything
                </label>
            </div>
            
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'I know what I want') !== false ? 'checked' : '') . '> I know what I want, but someone else thinks I should do something else
                </label>
            </div>
            
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'I don\'t know and I am not sure') !== false ? 'checked' : '') . '> I don\'t know and I am not sure what to do after graduation
                </label>
            </div>
            
            <div style="margin-bottom: 5px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['career_concerns'], 'Others:') !== false ? 'checked' : '') . '> Others:
                    <span class="underline" style="width: 87%;">
                        ' . (strpos($profile['career_concerns'], 'Others:') !== false ? 
                            htmlspecialchars(substr($profile['career_concerns'], strpos($profile['career_concerns'], 'Others:') + 7)) : 
                            '') . '
                    </span>
                </label>
            </div>
        </div>
    </div>
</div>



    <div class="medical-section">
    <h4>MEDICAL HISTORY INFORMATION</h4>
    
    <div class="medical-field">
        <label>List any medications you are taking: </label>
        <span class="medical-underline" style="width: 57%;">' . htmlspecialchars($profile['medications']) . '</span>
        <input type="checkbox" ' . ($profile['medications'] == 'NO MEDICATIONS' ? 'checked' : '') . '> No, I don\'t take
    </div>

   <div class="medical-field">
    <label style="margin-bottom: 20px;">Do you have any of the following? Kindly put a check (✓)</label>
    <div class="medical-checkbox-group">
        <div class="checkbox-row" style="margin-left: 4%; margin-bottom: 2px;">
            <label style="position: relative;">
                <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['medical_conditions'], 'Asthma') !== false ? 'checked' : '') . '> Asthma
            </label>
            <span style="margin-left: 70px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['medical_conditions'], 'Hypertension') !== false ? 'checked' : '') . '> Hypertension
                </label>
            </span>
            <span style="margin-left: 70px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['medical_conditions'], 'Diabetes') !== false ? 'checked' : '') . '> Diabetes
                </label>
            </span>
            <span style="margin-left: 70px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['medical_conditions'], 'Insomnia') !== false ? 'checked' : '') . '> Insomnia
                </label>
            </span>
            <span style="margin-left: 70px;">
                <label style="position: relative;">
                    <input type="checkbox" style="position: absolute; left: -20px;" ' . (strpos($profile['medical_conditions'], 'Vertigo') !== false ? 'checked' : '') . '> Vertigo
                </label>
            </span>
        </div>
    </div>
</div>


    <div class="medical-field">
    <label style="margin-left: 5%;">Other medical condition, please specify: </label>
    <span class="medical-underline" style="width: 64%;">' . htmlspecialchars(getValueAfterPrefix($profile['medical_conditions'], 'Other:')) . '</span>
    </div>

    <div class="medical-field">
        <label style="margin-left: 5%;">Allergy - specifically, allergic to: </label>
        <span class="medical-underline" style="width: 70%;">' . htmlspecialchars(getValueAfterPrefix($profile['medical_conditions'], 'Allergy:')) . '</span>
    </div>

    <div class="medical-field">
        <label style="margin-left: 5%;">Scoliosis or physical condition, specify: </label>
        <span class="medical-underline" style="width: 65%;">' . htmlspecialchars(getValueAfterPrefix($profile['medical_conditions'], 'Scoliosis/Physical condition:')) . '</span>
    </div>


    <div class="medical-field">
        <label>Have you ever seriously considered or attempted suicide? </label>
        <input type="checkbox" ' . ($profile['suicide_attempt'] == 'no' ? 'checked' : '') . '> No
        <input type="checkbox" ' . ($profile['suicide_attempt'] == 'yes' ? 'checked' : '') . '> Yes
        <span style="margin-left: 10px;">Why: </span>
        <span class="medical-underline" style="width: 34%;">' . htmlspecialchars($profile['suicide_reason']) . '</span>
    </div>

        <div class="medical-field">
            <label>Have you ever had a problem with:</label>
            <div class="medical-checkbox-group">
                <div class="checkbox-row">
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['problems'], 'Alcohol/Substance Abuse') !== false ? 'checked' : '') . '> Alcohol/Substance Abuse
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['problems'], 'Eating Disorder') !== false ? 'checked' : '') . '> Eating Disorder
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['problems'], 'Depression') !== false ? 'checked' : '') . '> Depression
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['problems'], 'Aggression') !== false ? 'checked' : '') . '> Aggression
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['problems'], 'Others:') !== false ? 'checked' : '') . '> Others:
                        <span class="underline" style="width: 87%;">
                            ' . (strpos($profile['problems'], 'Others:') !== false ? 
                                htmlspecialchars(substr($profile['problems'], strpos($profile['problems'], 'Others:') + 7)) : 
                                '') . '
                        </span>
                    </label>
                </div>
            </div>
        </div>
    <div class="medical-field">
            <label>Have any member of your immediate family member had a problem with:</label>
            <div class="medical-checkbox-group">
                <div class="checkbox-row">
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['family_problems'], 'Alcohol/Substance Abuse') !== false ? 'checked' : '') . '> Alcohol/Substance Abuse
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['family_problems'], 'Eating Disorder') !== false ? 'checked' : '') . '> Eating Disorder
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['family_problems'], 'Depression') !== false ? 'checked' : '') . '> Depression
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['family_problems'], 'Aggression') !== false ? 'checked' : '') . '> Aggression
                    </label>
                    <label style="position: relative; display: block; margin-bottom: 5px; padding-left: 20px;">
                        <input type="checkbox" style="position: absolute; left: 0;" ' . (strpos($profile['family_problems'], 'Others:') !== false ? 'checked' : '') . '> Others:
                        <span class="underline" style="width: 87%;">
                            ' . (strpos($profile['family_problems'], 'Others:') !== false ? 
                                htmlspecialchars(substr($profile['family_problems'], strpos($profile['family_problems'], 'Others:') + 7)) : 
                                '') . '
                        </span>
                    </label>
                </div>
            </div>
        </div>

    <div class="medical-field">
        <label>Do you engage in physical fitness activity? </label>
        <input type="checkbox" ' . ($profile['fitness_activity'] == 'NO FITNESS' ? 'checked' : '') . '> No
        <input type="checkbox" ' . ($profile['fitness_activity'] != 'NO FITNESS' ? 'checked' : '') . '> Yes
        <span style="margin-left: 10px;">Specify: </span>
        <span class="medical-underline"  style="width: 45%;">' . ($profile['fitness_activity'] != 'NO FITNESS' ? htmlspecialchars($profile['fitness_activity']) : '') . '</span>
    </div><br>

   <div class="medical-field">
    <label style="margin-left: 43px;">If yes, how often: </label>
    <div class="checkbox-inline" style="margin-left: 15px; margin-bottom: 5px;">
        <label style="position: relative;">
            <input type="checkbox" style="position: absolute; left: -20px;" ' . ($profile['fitness_frequency'] == 'Everyday' ? 'checked' : '') . '> Everyday
        </label>
    </div><br>
    <div class="checkbox-inline" style="margin-left: 22%; margin-bottom: 5px;">
        <label style="position: relative;">
            <input type="checkbox" style="position: absolute; left: -20px;" ' . ($profile['fitness_frequency'] == '2-3 Week' ? 'checked' : '') . '> 2-3 times a week
        </label>
    </div><br>
    <div class="checkbox-inline" style="margin-left: 22%; margin-bottom: 5px;">
        <label style="position: relative;">
            <input type="checkbox" style="position: absolute; left: -20px;" ' . ($profile['fitness_frequency'] == '2-3 Month' ? 'checked' : '') . '> 2-3 times a month
        </label>
    </div>
</div>



    <div class="medical-field">
    <label style="margin-left: 43px;">How would you rate your current level of stress, 10 as highest & 1 as lowest:</label><br>
    <div class="checkbox-inline" style="margin-left: 25%; margin-bottom: 5px;">
        <label style="position: relative;">
            <input type="checkbox" style="position: absolute; left: -20px;" ' . (strtolower($profile['stress_level']) == 'low' ? 'checked' : '') . '> Low (1-3)
        </label>
    </div><br>
    <div class="checkbox-inline" style="margin-left: 25%; margin-bottom: 5px;">
        <label style="position: relative;">
            <input type="checkbox" style="position: absolute; left: -20px;" ' . (strtolower($profile['stress_level']) == 'average' ? 'checked' : '') . '> Average (4-7)
        </label>
    </div><br>
    <div class="checkbox-inline" style="margin-left: 25%; margin-bottom: 5px;">
        <label style="position: relative;">
            <input type="checkbox" style="position: absolute; left: -20px;" ' . (strtolower($profile['stress_level']) == 'high' ? 'checked' : '') . '> High (8-10)
        </label>
    </div>
</div>

</div>


    <div style="margin-top: 30px;">
    <b><i style="font-size: 12px;">I hereby attest that all information stated above is true and correct.</i></b>
    <div style="margin-top: 40px;">
        <div style="float: right; margin-right: 50px; text-align: center; width: 250px; position: relative;">
            <div style="position: absolute; top: -40px; left: 0; width: 100%; text-align: center;">
                ' . $signature_html . '
            </div>
            <p style="margin: 0; padding: 0; position: relative; z-index: 1;">' . htmlspecialchars(strtoupper($profile['first_name'] . ' ' . $profile['middle_name'] . ' ' . $profile['last_name'])) . '</p>
            <div style="border-top: 1px solid black; width: 100%; position: relative; z-index: 1;">
                <div style="font-size: 12px; padding-top: 5px;">
                    Signature over printed name
                </div>
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>
</div>


    <div class="version-number">V01-2018-05-28</div>
</body>
</html>
';



// Create Dompdf object
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->setChroot('C:/xampp/htdocs/capstone1');

$dompdf = new Dompdf($options);

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');
$dompdf->addInfo("Title", "OSAS-QF-12-Student-Profile-Form-for-Inventory");
$dompdf->addInfo("Author", "CEIT Guidance Facilitator");

// Render PDF
$dompdf->render();

// Output PDF
$dompdf->stream("student_profile_" . $profile['student_id'] . ".pdf", array("Attachment" => 0));


//working pdf