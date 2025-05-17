<?php
session_start();
include "../db.php"; 

// Check if the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
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
$message_type = ''; // Add message type for better styling


if (isset($_POST['import_csv'])) {
    $file = $_FILES['csv_file'];
    $allowed_ext = ['csv'];
    $filename = $file['name'];
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Enhanced file validation
    if (empty($filename)) {
        $message = "Please select a file to upload.";
        $message_type = "error";
    } elseif (!in_array($file_ext, $allowed_ext)) {
        $message = "Invalid file format. Please upload a CSV file.";
        $message_type = "error";
    } elseif ($file['size'] > 5000000) { // 5MB limit
        $message = "File is too large. Maximum file size is 5MB.";
        $message_type = "error";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Upload failed. Please try again.";
        $message_type = "error";
    } else {
        $file_tmp = $file['tmp_name'];
        
        // Validate file can be opened
        if (!is_readable($file_tmp)) {
            $message = "Unable to read the uploaded file.";
            $message_type = "error";
        } else {
            $handle = fopen($file_tmp, "r");
            
            if (!$handle) {
                $message = "Error opening the file.";
                $message_type = "error";
            } else {
                // Read the header row
                $header = fgetcsv($handle);
                
                if ($header === false) {
                    $message = "Error: The CSV file appears to be empty or improperly formatted.";
                    $message_type = "error";
                } else {
                    $header = array_map('strtolower', array_map('trim', $header));
                    
                    // Find the indices of required columns
                    $name_index = array_search('student name', $header);
                    $id_index = array_search('student number', $header);
                    $sex_index = array_search('sex', $header);

                    if ($name_index === false || $id_index === false || $sex_index === false) {
                        $message = "Error: CSV file must contain 'Student Name', 'Student Number', and 'Sex' columns.";
                        $message_type = "error";
                    } else {
                        $imported_count = 0;
                        $updated_count = 0;
                        $reactivated_count = 0;
                        $skipped_count = 0;
                        $connection->begin_transaction();
                        
                        try {
                            while (($data = fgetcsv($handle)) !== FALSE) {
                                // Skip rows with fewer columns than the header
                                if (count($data) < count($header)) {
                                    continue;
                                }
                                
                                $student_number = trim($data[$id_index]);
                                $student_name = trim($data[$name_index]);
                                $gender = trim($data[$sex_index]);

                                if (empty($student_number) || empty($student_name) || empty($gender)) {
                                    continue; // Skip empty rows
                                }

                                // Process name parts (existing name processing)
                                $name_parts = explode(',', $student_name);
                                if (count($name_parts) < 2) continue;

                                $last_name = trim($name_parts[0]);
                                $first_and_middle = trim($name_parts[1]);
                                $first_middle_parts = explode(' ', $first_and_middle);
                                $first_name = '';
                                $middle_name = '';

                                for ($i = 0; $i < count($first_middle_parts); $i++) {
                                    $part = trim($first_middle_parts[$i]);
                                    if (empty($part)) continue;

                                    if (substr($part, -1) === '.') {
                                        $middle_name = trim($part, '. ');
                                    } else {
                                        $first_name = trim($first_name . ' ' . $part);
                                    }
                                }

                                $first_name = trim($first_name);
                                $middle_name = trim($middle_name);
                                $last_name = trim($last_name);

                                if (empty($first_name) || empty($last_name)) {
                                    continue;
                                }

                                // First check for active students in ANY section
                                $check_active_sql = "SELECT student_id FROM tbl_student WHERE student_id = ? AND status = 'active'";
                                $check_active_stmt = $connection->prepare($check_active_sql);
                                $check_active_stmt->bind_param("s", $student_number);
                                $check_active_stmt->execute();
                                $active_result = $check_active_stmt->get_result();

                                if ($active_result->num_rows > 0) {
                                    $skipped_count++;
                                    $check_active_stmt->close();
                                    continue;
                                }
                                $check_active_stmt->close();

                                // Then check for disabled students in ANY section
                                $check_disabled_sql = "SELECT * FROM tbl_student WHERE student_id = ? AND status = 'disabled'";
                                $check_disabled_stmt = $connection->prepare($check_disabled_sql);
                                $check_disabled_stmt->bind_param("s", $student_number);
                                $check_disabled_stmt->execute();
                                $disabled_result = $check_disabled_stmt->get_result();

                                if ($disabled_result->num_rows > 0) {
                                    // Update existing disabled student - preserve email and password
                                    $update_sql = "UPDATE tbl_student SET 
                                                first_name = ?, 
                                                middle_name = ?, 
                                                last_name = ?, 
                                                gender = ?,
                                                section_id = ?,
                                                status = 'active'
                                                WHERE student_id = ? AND status = 'disabled'";
                                    
                                    $update_stmt = $connection->prepare($update_sql);
                                    $update_stmt->bind_param("sssssi", 
                                                           $first_name, 
                                                           $middle_name, 
                                                           $last_name, 
                                                           $gender,
                                                           $section_id,
                                                           $student_number);
                                    
                                    if ($update_stmt->execute()) {
                                        $reactivated_count++;
                                    }
                                    $update_stmt->close();
                                } else {
                                    // Create new student if no existing record found
                                    $insert_sql = "INSERT INTO tbl_student 
                                                (student_id, first_name, middle_name, last_name, gender, section_id, status) 
                                                VALUES (?, ?, ?, ?, ?, ?, 'active')";

                                    $insert_stmt = $connection->prepare($insert_sql);
                                    if ($insert_stmt === false) {
                                        throw new Exception("Prepare failed: " . $connection->error);
                                    }

                                    $insert_stmt->bind_param("sssssi", 
                                                        $student_number, 
                                                        $first_name, 
                                                        $middle_name, 
                                                        $last_name, 
                                                        $gender, 
                                                        $section_id);

                                    if ($insert_stmt->execute()) {
                                        $imported_count++;
                                    } else {
                                        throw new Exception("Execute failed: " . $insert_stmt->error);
                                    }

                                    $insert_stmt->close();
                                }
                                $check_disabled_stmt->close();
                            }
                            
                            $connection->commit();
                            fclose($handle);

                            // Update total students count
                            $count_sql = "SELECT COUNT(*) as count FROM tbl_student WHERE section_id = ? AND status = 'active'";
                            $count_stmt = $connection->prepare($count_sql);
                            $count_stmt->bind_param("i", $section_id);
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            $total_students = $count_result->fetch_assoc()['count'];
                            $count_stmt->close();

                            $message = "Import successful. $imported_count new students added, $reactivated_count students reactivated, $skipped_count students skipped (already active in sections). Total active students in section: $total_students";
                            $message_type = "success";
                        } catch (Exception $e) {
                            $connection->rollback();
                            $message = "Error: " . $e->getMessage();
                            $message_type = "error";
                        }
                    }
                }
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
        }
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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">   
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
<style>
        :root {
            --primary-color: #0d693e;
            --secondary-color: #004d4d;
            --accent-color: #F4A261;
            --hover-color: #094e2e;
            --text-color: #2c3e50;
            --border-color: #e0e0e0;
            --error-color: #dc3545;
            --success-color: #28a745;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.8;
            background: linear-gradient(135deg, #0d693e, #004d4d);
            color: #333;
            min-height: 100vh;
            display: block;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Main Container */
        .container {
            margin: 20px auto;
            width: 95%;
            max-width: 600px;
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        /* Header and Typography */
        h2 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: clamp(1.5rem, 4vw, 2.2rem);
            font-weight: 600;
        }

        /* Back Button */
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

        /* Alerts */
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

        /* File Upload Elements */
        .file-upload-container {
            position: relative;
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .custom-file {
            position: relative;
            width: 100%;
            margin-bottom: 1.25rem;
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
            padding: .5rem 1rem;
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
            display: flex;
            align-items: center;
        }

        .custom-file-label::after {
            position: absolute;
            top: -2px;
            right: -2px;
            bottom: -2px;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            height: calc(1.5em + 1rem + 4px);
            padding: 0 1rem;
            color: #fff;
            background-color: var(--primary-color);
            border: none;
            border-radius: 0 10px 10px 0;
            content: "Browse";
            font-weight: 500;
        }

        /* Button Styles */
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

        .btn-primary:active, .btn-primary:focus {
            background-color: var(--hover-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transform: translateY(0);
        }

        /* File format helper text */
        .file-format-info {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .container {
                width: 90%;
                margin: 2rem auto;
            }
        }

        @media (max-width: 767.98px) {
            .container {
                width: 95%;
                padding: 1.5rem;
                margin: 1rem auto;
            }
            
            .custom-file-label::after {
                padding: 0 0.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .container {
                width: 98%;
                padding: 1.25rem;
                margin: 0.5rem auto;
                border-radius: 10px;
            }
            
            h2 {
                font-size: 1.5rem;
                margin-bottom: 1.25rem;
            }
            
            .modern-back-button {
                padding: 6px 14px;
                font-size: 0.85rem;
                margin-bottom: 20px;
            }
            
            .custom-file-label {
                font-size: 0.9rem;
            }
            
            .custom-file-label::after {
                content: "";
                width: 40px;
                font-size: 0;
                background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M15 6h-6v3l-4-4 4-4v3h6a6 6 0 0 1 6 6v3h-2v-3a4 4 0 0 0-4-4z"/></svg>');
                background-repeat: no-repeat;
                background-position: center;
                background-size: 20px;
                padding-bottom: 50px;
            }
            
            .btn-primary {
                padding: 0.625rem 1.25rem;
                font-size: 0.9rem;
            }
        }

        /* Very small screens */
        @media (max-width: 359.98px) {
            .container {
                padding: 1rem;
            }
            
            h2 {
                font-size: 1.4rem;
            }
        }

        /* Ensure form height doesn't collapse */
        form {
            min-height: 180px;
        }

        /* Accessibility enhancement */
        .custom-file-input:focus ~ .custom-file-label {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(13, 105, 62, 0.25);
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .container {
                background-color: #1a2026;
                color: #f0f2f5;
            }
            
            h2 {
                color: #f0f2f5;
            }
            
            .custom-file-label {
                background-color: #2c3440;
                color: #f0f2f5;
                border-color: #3e4a5a;
            }
            
            .file-format-info {
                color: #adb5bd;
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

        <form method="post" enctype="multipart/form-data" class="mb-4">
            <div class="file-upload-container">
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="csvFile" name="csv_file" accept=".csv" required>
                    <label class="custom-file-label" for="csvFile">Choose CSV file</label>
                </div>
                <div class="file-format-info">
                    <small><i class="fas fa-info-circle"></i> Only CSV files are accepted. The file should include columns for "Student Name", "Student Number", and "Sex".</small>
                </div>
            </div>
            <button type="submit" name="import_csv" class="btn btn-primary">
                <i class="fas fa-upload mr-2"></i>Import Students
            </button>
        </form>
    </div>
    
    <?php if (!empty($message)): ?>
    <script>
        Swal.fire({
            title: '<?php echo $message_type === "error" ? "Error" : "Import Status"; ?>',
            text: '<?php echo str_replace("'", "\'", $message); ?>',
            icon: '<?php echo $message_type === "error" ? "error" : "success"; ?>',
            confirmButtonText: 'OK',
            confirmButtonColor: '#0d693e'
        })<?php echo $message_type === "success" ? ".then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'section_details.php?section_id=" . $section_id . "';
            }
        })" : ""; ?>;
    </script>
    <?php endif; ?>

    <script>
        // Update file input label with selected filename
        document.querySelector('.custom-file-input').addEventListener('change', function (e) {
            const fileName = e.target.files[0]?.name || 'Choose CSV file';
            const nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
            
            // Validate file extension client-side
            if (fileName !== 'Choose CSV file') {
                const fileExt = fileName.split('.').pop().toLowerCase();
                if (fileExt !== 'csv') {
                    Swal.fire({
                        title: 'Invalid File',
                        text: 'Please select a CSV file only.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#0d693e'
                    });
                    e.target.value = ''; // Clear the input
                    nextSibling.innerText = 'Choose CSV file';
                }
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function (e) {
            const fileInput = document.getElementById('csvFile');
            if (!fileInput.files.length) {
                e.preventDefault();
                Swal.fire({
                    title: 'No File Selected',
                    text: 'Please select a CSV file to upload.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0d693e'
                });
            }
        });
    </script>
</body>
</html>