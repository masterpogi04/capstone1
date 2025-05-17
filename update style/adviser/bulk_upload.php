<?php
session_start();
include "../db.php";
include "adviser_sidebar.php";

// Check if the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: login.php");
    exit();
}

// Check if database connection is established
if (!isset($connection) || $connection->connect_error) {
    die("Database connection failed: " . ($connection->connect_error ?? "Unknown error"));
}

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;

if ($section_id === 0) {
    die("No valid section ID provided.");
}

$message = '';

if (isset($_POST['import_csv'])) {
    $file = $_FILES['csv_file'];
    $allowed_ext = ['csv'];
    $filename = $file['name'];
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($file_ext, $allowed_ext)) {
        $file_tmp = $file['tmp_name'];
        $handle = fopen($file_tmp, "r");
        
        // Read the header row
        $header = fgetcsv($handle);
        $header = array_map('strtolower', $header);
        
        // Find the indices of required columns
        $name_index = array_search('student name', $header);
        $id_index = array_search('student number', $header);
        $sex_index = array_search('sex', $header);

        if ($name_index === false || $id_index === false || $sex_index === false) {
            $message = "Error: CSV file must contain 'Student Name', 'Student Number', and 'Sex' columns.";
        } else {
            $imported_count = 0;
            $updated_count = 0;
            $skipped_count = 0;
            $connection->begin_transaction();
            
            try {
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $student_number = trim($data[$id_index]);
                    $student_name = trim($data[$name_index]);
                    $gender = trim($data[$sex_index]);

                    if (empty($student_number) || empty($student_name) || empty($gender)) {
                        continue; // Skip empty rows
                    }

                    // Split the name by comma first
                    $name_parts = explode(',', $student_name);
                    if (count($name_parts) < 2) continue; 

                    $last_name = trim($name_parts[0]);
                    $first_and_middle = trim($name_parts[1]);

                    // Split the remaining part to get first name and middle initial
                    $first_middle_parts = explode(' ', $first_and_middle);
                    $first_name = '';
                    $middle_name = '';

                    // Process the first name and middle initial
                    for ($i = 0; $i < count($first_middle_parts); $i++) {
                        $part = trim($first_middle_parts[$i]);
                        if (empty($part)) continue;

                        if (substr($part, -1) === '.') {
                            // This is likely the middle initial
                            $middle_name = trim($part, '. '); // Remove the period and any spaces
                        } else {
                            // This is part of the first name
                            $first_name = trim($first_name . ' ' . $part);
                        }
                    }

                    // Clean up the data
                    $first_name = trim($first_name);
                    $middle_name = trim($middle_name);
                    $last_name = trim($last_name);

                    if (empty($first_name) || empty($last_name)) {
                        continue; // Skip if essential name parts are missing
                    }

                    // Check if student already exists in any section
                    $check_sql = "SELECT section_id FROM tbl_student WHERE student_id = ?";
                    $check_stmt = $connection->prepare($check_sql);
                    $check_stmt->bind_param("s", $student_number);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $existing_section = $check_result->fetch_assoc()['section_id'];
                        if ($existing_section != $section_id) {
                            $skipped_count++;
                            continue; // Skip this student as they're already in another section
                        }
                    }
                    $check_stmt->close();

                    $sql = "INSERT INTO tbl_student (student_id, first_name, middle_name, last_name, gender, section_id) 
                            VALUES (?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            first_name = VALUES(first_name),
                            middle_name = VALUES(middle_name),
                            last_name = VALUES(last_name),
                            gender = VALUES(gender),
                            section_id = VALUES(section_id)";

                    $stmt = $connection->prepare($sql);

                    if ($stmt === false) {
                        throw new Exception("Prepare failed: " . $connection->error);
                    }

                    $stmt->bind_param("sssssi", $student_number, $first_name, $middle_name, $last_name, $gender, $section_id);

                    if ($stmt->execute()) {
                        if ($stmt->affected_rows == 1) {
                            $imported_count++;
                        } elseif ($stmt->affected_rows == 2) {
                            $updated_count++;
                        }
                    } else {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }

                    $stmt->close();
                }
                
                $connection->commit();
                fclose($handle);

                // Count the total number of students in the section
                $count_sql = "SELECT COUNT(*) as count FROM tbl_student WHERE section_id = ?";
                $count_stmt = $connection->prepare($count_sql);
                $count_stmt->bind_param("i", $section_id);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $total_students = $count_result->fetch_assoc()['count'];
                $count_stmt->close();

                $message = "Import successful. $imported_count new students added, $updated_count students updated, $skipped_count students skipped (already in other sections). Total students in section: $total_students";
            } catch (Exception $e) {
                $connection->rollback();
                $message = "Error: " . $e->getMessage();
            }
        }
    } else {
        $message = "Invalid file format. Please upload a CSV file.";
    }
}

// Fetch section details
$section_sql = "SELECT * FROM sections WHERE id = ? AND adviser_id = ?";
$stmt = $connection->prepare($section_sql);
$stmt->bind_param("ii", $section_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$section = $result->fetch_assoc();
$stmt->close();

if (!$section) {
    die("Section not found or you don't have permission to access it.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Students</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">   
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    
     <style>
       :root {
    --primary-color: #0d693e;
    --secondary-color: #004d4d;
    --accent-color: #F4A261;
    --hover-color: #094e2e;
    --text-color: #2c3e50;
    --border-color: #e0e0e0;
    --container-padding: clamp(1rem, 3vw, 2rem);
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.8;
    margin: 0;
    padding: 0;
    background-color: #f0f4f8;
    color: #333;
    min-height: 100vh;
}

/* Main Container */
.container {
    width: min(95%, 600px);
    margin: 0 auto;
    background-color: #fff;
    padding: var(--container-padding);
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    position: relative;
    transition: all 0.3s ease;
}

/* Adjusting container position based on sidebar */
@media screen and (min-width: 1025px) {
    .container {
        margin: 200px auto;
       
    }
}

/* Large tablets and small desktops */
@media screen and (max-width: 1024px) {
    .container {
        width: min(90%, 600px);
        margin: 2rem auto;
        margin-left: calc(200px + 5%); /* Adjusted for smaller sidebar */
    }
}

/* Tablets */
@media screen and (max-width: 768px) {
    .container {
        width: 90%;
        margin: 1.5rem auto;
        margin-left: auto; /* Reset margin when sidebar changes */
        padding: 1.25rem;
    }
}

/* Large Mobile */
@media screen and (max-width: 576px) {
    .container {
        width: 95%;
        margin: 1rem auto;
        padding: 1rem;
        border-radius: 10px;
    }
}

/* Small Mobile */
@media screen and (max-width: 375px) {
    .container {
        width: 98%;
        margin: 0.5rem auto;
        padding: 0.875rem;
        border-radius: 8px;
    }
}

/* Handle notched devices */
@supports (padding: max(0px)) {
    .container {
        padding-left: max(var(--container-padding), env(safe-area-inset-left));
        padding-right: max(var(--container-padding), env(safe-area-inset-right));
    }
}

/* Container Content Layout */
.container-inner {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Content Sections */
.content-section {
    width: 100%;
}

/* Form Layout within Container */
.form-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Additional Layout Helpers */
.flex-center {
    display: flex;
    align-items: center;
    justify-content: center;
}

.flex-column {
    display: flex;
    flex-direction: column;
}

/* Landscape Mode Optimization */
@media screen and (orientation: landscape) and (max-height: 600px) {
    .container {
        margin: 1rem auto;
        max-height: 85vh;
        overflow-y: auto;
    }
}

/* Print styles */
@media print {
    .container {
        width: 100%;
        margin: 0;
        padding: 1rem;
        box-shadow: none;
        border-radius: 0;
    }
}

h2 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 1.5rem;
    font-size: clamp(1.5rem, 4vw, 2.2rem);
    font-weight: 600;
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
.alert {
    border-radius: 8px;
    font-weight: 500;
    padding: 1rem;
    margin-bottom: 1.5rem;
    font-size: clamp(0.875rem, 2vw, 1rem);
}

.alert-info {
    background-color: #e1f5fe;
    border-color: #b3e5fc;
    color: #01579b;
}

.custom-file {
    margin-bottom: 1.25rem;
    position: relative;
}

.custom-file-input {
    position: relative;
    z-index: 2;
    width: 100%;
    height: calc(1.5em + 1rem + 2px);
    opacity: 0;
    cursor: pointer;
}

.custom-file-label {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    z-index: 1;
    height: calc(1.5em + 1rem + 2px);
    padding: 0.75rem 1rem;
    font-weight: 500;
    line-height: 1.5;
    color: var(--text-color);
    background-color: #fff;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    transition: all 0.3s ease;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.custom-file-label::after {
    position: absolute;
    top: -2px;
    right: -2px;
    bottom: -2px;
    z-index: 3;
    display: block;
    height: calc(1.5em + 1rem + 4px);
    padding: 0.75rem 1rem;
    line-height: 1.5;
    color: #fff;
    background-color: var(--primary-color);
    border: none;
    border-radius: 0 10px 10px 0;
    content: "Browse";
    font-weight: 500;
}

.btn-primary {
    background-color: var(--primary-color);
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;
    margin-top: 1.25rem;
    font-size: clamp(0.875rem, 2vw, 1rem);
}

.btn-primary:hover {
    background-color: var(--hover-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}


/* Media Queries */
@media screen and (max-width: 768px) {
    .container {
        width: 95%;
        margin: 1rem auto;
        padding: 1rem;
    }

    .custom-file-label::after {
        padding: 0.75rem 0.5rem;
        content: "...";
    }
}

@media screen and (max-width: 480px) {
    .container {
        margin: 0.5rem auto;
        padding: 0.875rem;
        border-radius: 8px;
    }

    .back-button {
        top: 0.75rem;
        left: 0.75rem;
        padding: 0.375rem 0.75rem;
    }

    .alert {
        padding: 0.75rem;
        margin-bottom: 1rem;
    }
}

/* Adjust for devices with notches or unusual aspect ratios */
@supports (padding: max(0px)) {
    .container {
        padding-left: max(1.5rem, env(safe-area-inset-left));
        padding-right: max(1.5rem, env(safe-area-inset-right));
    }
}



    </style>
</head>
<body>
    <div class="container">
    <a href="section_details.php?section_id=<?php echo $section_id; ?>" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back</span>
</a>
        <h2>Bulk Upload Students</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="custom-file">
                <input type="file" class="custom-file-input" id="csvFile" name="csv_file" required>
                <label class="custom-file-label" for="csvFile">Choose CSV file</label>
            </div>
            <button type="submit" name="import_csv" class="btn btn-primary mt-2">Import Students</button>
        </form>
    </div>
    <script>
        document.querySelector('.custom-file-input').addEventListener('change', function (e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    </script>
</body>
</html>