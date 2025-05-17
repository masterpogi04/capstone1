<?php
session_start();
include '../db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// Function to fetch all departments
function getAllDepartments($connection) {
    $query = "SELECT * FROM departments WHERE status = 'active' ORDER BY name";
    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch courses by department
function getCoursesByDepartment($connection, $departmentId) {
    $query = "SELECT * FROM courses WHERE department_id = ? AND status = 'active' ORDER BY name";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch all document types
function getAllDocumentTypes($connection) {
    $query = "SELECT DISTINCT document_request FROM document_requests ORDER BY document_request";
    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch all purpose types
function getAllPurposeTypes($connection) {
    $query = "SELECT DISTINCT purpose FROM document_requests ORDER BY purpose";
    $result = $connection->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to generate report based on filters
function generateReport($connection, $filters) {
    $query = "SELECT 
                d.name as department,
                c.name as course,
                dr.document_request,
                dr.purpose,
                COUNT(*) as total_requests,
                SUM(CASE WHEN dr.status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN dr.status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN dr.status = 'Rejected' THEN 1 ELSE 0 END) as rejected
              FROM document_requests dr
              JOIN tbl_student s ON dr.student_number = s.student_id
              JOIN sections sec ON s.section_id = sec.id
              JOIN departments d ON sec.department_id = d.id
              JOIN courses c ON sec.course_id = c.id
              WHERE dr.request_time BETWEEN ? AND ?";
    
    $params = [$filters['start_date'], $filters['end_date']];
    $types = "ss";
    
    // Add department filter if selected
    if (!empty($filters['department']) && $filters['department'] !== 'all') {
        $query .= " AND d.id = ?";
        $params[] = $filters['department'];
        $types .= "i";
    }
    
    // Add course filter if selected
    if (!empty($filters['course']) && $filters['course'] !== 'all') {
        $query .= " AND c.id = ?";
        $params[] = $filters['course'];
        $types .= "i";
    }
    
    // Add document filter if selected
    if (!empty($filters['document_type']) && $filters['document_type'] !== 'all') {
        if ($filters['document_type'] === 'good_moral') {
            $query .= " AND dr.document_request = 'Good Moral'";
        } else if ($filters['document_type'] === 'others') {
            $query .= " AND dr.document_request != 'Good Moral'";
        } else {
            $query .= " AND dr.document_request = ?";
            $params[] = $filters['document_type'];
            $types .= "s";
        }
    }
    
    // Add purpose filter if selected
    if (!empty($filters['purpose']) && $filters['purpose'] !== 'all') {
        $query .= " AND dr.purpose = ?";
        $params[] = $filters['purpose'];
        $types .= "s";
    }
    
    // Group by selected parameters
    $groupBy = [];
    
    if ($filters['group_by_department'] === 'yes') {
        $groupBy[] = "d.name";
    }
    
    if ($filters['group_by_course'] === 'yes') {
        $groupBy[] = "c.name";
    }
    
    if ($filters['group_by_document'] === 'yes') {
        $groupBy[] = "dr.document_request";
    }
    
    if ($filters['group_by_purpose'] === 'yes') {
        $groupBy[] = "dr.purpose";
    }
    
    if (!empty($groupBy)) {
        $query .= " GROUP BY " . implode(', ', $groupBy);
    }
    
    $stmt = $connection->prepare($query);
    if ($stmt === false) {
        die("Error preparing statement: " . $connection->error);
    }
    
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch all filter options
$departments = getAllDepartments($connection);
$documentTypes = getAllDocumentTypes($connection);
$purposeTypes = getAllPurposeTypes($connection);

// Initialize variables
$reportData = null;
$filters = [
    'department' => 'all',
    'course' => 'all',
    'document_type' => 'all',
    'purpose' => 'all',
    'start_date' => '',
    'end_date' => '',
    'group_by_department' => 'yes',
    'group_by_course' => 'yes',
    'group_by_document' => 'yes',
    'group_by_purpose' => 'yes'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set filters from form data
    $filters['department'] = $_POST['department'] ?? 'all';
    $filters['course'] = $_POST['course'] ?? 'all';
    $filters['document_type'] = $_POST['document_type'] ?? 'all';
    $filters['purpose'] = $_POST['purpose'] ?? 'all';
    $filters['start_date'] = $_POST['start_date'] . ' 00:00:00';
    $filters['end_date'] = $_POST['end_date'] . ' 23:59:59';
    $filters['group_by_department'] = $_POST['group_by_department'] ?? 'no';
    $filters['group_by_course'] = $_POST['group_by_course'] ?? 'no';
    $filters['group_by_document'] = $_POST['group_by_document'] ?? 'no';
    $filters['group_by_purpose'] = $_POST['group_by_purpose'] ?? 'no';
    
    // Generate report data
    $reportData = generateReport($connection, $filters);
    
    // Handle PDF generation
    if (isset($_POST['generate_pdf'])) {
        // Store report data in session for PDF generation
        $_SESSION['report_data'] = $reportData;
        $_SESSION['report_filters'] = $filters;
        
        // Redirect to PDF generation script
        header("Location: generate_pdf_document_request.php");
        exit;
    }
}

// Function to calculate totals
function calculateTotals($reportData) {
    $totals = [
        'total_requests' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];
    
    foreach ($reportData as $row) {
        $totals['total_requests'] += $row['total_requests'];
        $totals['pending'] += $row['pending'];
        $totals['approved'] += $row['approved'];
        $totals['rejected'] += $row['rejected'];
    }
    
    return $totals;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Generate Document Request Report</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0d693e, #004d4d);
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: #333;
        }
        
        .header {
            background-color: white;
            padding: 15px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #1b651b;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .container {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            padding: 30px;
            margin-top: 100px;
            margin-bottom: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #0d693e;
            font-size: 1.1rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .form-control {
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            padding: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #0d693e;
            box-shadow: 0 0 0 0.2rem rgba(13, 105, 62, 0.15);
        }
        
        .btn-primary {
            background-color: #007bff;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .back-button {
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
        
        .back-button:hover {
            background-color: #28C498;
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
            color: white;
            text-decoration: none;
        }
        
        .back-button i {
            font-size: 0.9rem;
            position: relative;
            top: 1px;
        }
        
        .table {
            font-size: 0.9rem;
            width: 100%;
            margin-bottom: 2rem;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .table th,
        .table td {
            padding: 12px;
            vertical-align: middle;
            border: none;
            text-align: center;
        }
        
        .table thead th {
            background-color: #0d693e;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr:nth-of-type(even) {
            background-color: #f8f9fa;
        }
        
        .table tbody tr:hover {
            background-color: #e9ecef;
        }
        
        .table tfoot {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .form-check-inline {
            margin-right: 1rem;
        }
        
        .group-by-option {
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid #e1e1e1;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        
        .group-by-option:hover {
            background-color: #f8f9fa;
            border-color: #0d693e;
        }
        
        .group-by-option input {
            cursor: pointer;
        }
        
        .group-by-option label {
            cursor: pointer;
            margin-bottom: 0;
        }
        
        .date-range-container {
            display: flex;
            gap: 15px;
        }
        
        .date-range-container .form-group {
            flex: 1;
        }
        
        .no-data-icon {
            color: #0d693e;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .date-range-container {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        @media print {
            .header, .back-button, .btn, .form-container {
                display: none;
            }
            
            .container {
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            
            .table {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        GENERATE DOCUMENT REQUEST REPORT
    </div>
    <div class="container">
        <a href="facilitator_requested_documents.php" class="btn back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <div class="form-container">
            <h4 class="mb-4">Filter Options</h4>
            <form method="POST" id="reportForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select class="form-control" id="department" name="department">
                                <option value="all">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($filters['department'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="course">Course</label>
                            <select class="form-control" id="course" name="course">
                                <option value="all">All Courses</option>
                                <!-- Courses will be loaded dynamically -->
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="document_type">Document Type</label>
                            <select class="form-control" id="document_type" name="document_type">
                                <option value="all">All Documents</option>
                                <option value="good_moral" <?php echo ($filters['document_type'] == 'good_moral') ? 'selected' : ''; ?>>Good Moral</option>
                                <option value="others" <?php echo ($filters['document_type'] == 'others') ? 'selected' : ''; ?>>Other Documents</option>
                                <?php foreach ($documentTypes as $doc): ?>
                                <option value="<?php echo htmlspecialchars($doc['document_request']); ?>" <?php echo ($filters['document_type'] == $doc['document_request']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($doc['document_request']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="purpose">Purpose</label>
                            <select class="form-control" id="purpose" name="purpose">
                                <option value="all">All Purposes</option>
                                <?php foreach ($purposeTypes as $purpose): ?>
                                <option value="<?php echo htmlspecialchars($purpose['purpose']); ?>" <?php echo ($filters['purpose'] == $purpose['purpose']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($purpose['purpose']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="date-range-container">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required value="<?php echo $filters['start_date'] ? date('Y-m-d', strtotime($filters['start_date'])) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required value="<?php echo $filters['end_date'] ? date('Y-m-d', strtotime($filters['end_date'])) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Group By</label>
                    <div class="d-flex flex-wrap">
                        <div class="form-check-inline group-by-option">
                            <input class="form-check-input" type="checkbox" id="group_by_department" name="group_by_department" value="yes" <?php echo ($filters['group_by_department'] === 'yes') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="group_by_department">Department</label>
                        </div>
                        <div class="form-check-inline group-by-option">
                            <input class="form-check-input" type="checkbox" id="group_by_course" name="group_by_course" value="yes" <?php echo ($filters['group_by_course'] === 'yes') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="group_by_course">Course</label>
                        </div>
                        <div class="form-check-inline group-by-option">
                            <input class="form-check-input" type="checkbox" id="group_by_document" name="group_by_document" value="yes" <?php echo ($filters['group_by_document'] === 'yes') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="group_by_document">Document Type</label>
                        </div>
                        <div class="form-check-inline group-by-option">
                            <input class="form-check-input" type="checkbox" id="group_by_purpose" name="group_by_purpose" value="yes" <?php echo ($filters['group_by_purpose'] === 'yes') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="group_by_purpose">Purpose</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" id="generateReportBtn">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                    <button type="submit" name="generate_pdf" class="btn btn-success" onclick="this.form.target='_blank';">
                        <i class="fas fa-file-pdf"></i> Export as PDF
                    </button>
                </div>
            </form>
        </div>
        
        <div id="reportResultsContainer">
        <?php if ($reportData): ?>
        <div class="mt-5">
            <h3>Report Results</h3>
            <p>
                <strong>Period:</strong> <span id="reportPeriod"><?php echo date('F j, Y', strtotime($filters['start_date'])); ?> - 
                <?php echo date('F j, Y', strtotime($filters['end_date'])); ?></span>
            </p>
            
            <div id="reportTableContainer">
            <?php if (empty($reportData)): ?>
                <div class="no-data-container text-center py-5">
                    <i class="fas fa-file-alt no-data-icon fa-5x text-muted mb-3"></i>
                    <h4 class="text-muted">No data available for the selected criteria</h4>
                    <p class="text-muted">Try adjusting your filters or date range</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <?php if ($filters['group_by_department'] === 'yes'): ?>
                                    <th>Department</th>
                                <?php endif; ?>
                                
                                <?php if ($filters['group_by_course'] === 'yes'): ?>
                                    <th>Course</th>
                                <?php endif; ?>
                                
                                <?php if ($filters['group_by_document'] === 'yes'): ?>
                                    <th>Document Type</th>
                                <?php endif; ?>
                                
                                <?php if ($filters['group_by_purpose'] === 'yes'): ?>
                                    <th>Purpose</th>
                                <?php endif; ?>
                                
                                <th>Total Requests</th>
                                <th>Pending</th>
                                <th>Approved</th>
                                <th>Rejected</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <?php if ($filters['group_by_department'] === 'yes'): ?>
                                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                                    <?php endif; ?>
                                    
                                    <?php if ($filters['group_by_course'] === 'yes'): ?>
                                        <td><?php echo htmlspecialchars($row['course']); ?></td>
                                    <?php endif; ?>
                                    
                                    <?php if ($filters['group_by_document'] === 'yes'): ?>
                                        <td><?php echo htmlspecialchars($row['document_request']); ?></td>
                                    <?php endif; ?>
                                    
                                    <?php if ($filters['group_by_purpose'] === 'yes'): ?>
                                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <?php endif; ?>
                                    
                                    <td><?php echo $row['total_requests']; ?></td>
                                    <td><?php echo $row['pending']; ?></td>
                                    <td><?php echo $row['approved']; ?></td>
                                    <td><?php echo $row['rejected']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <?php 
                                $colspan = 0;
                                if ($filters['group_by_department'] === 'yes') $colspan++;
                                if ($filters['group_by_course'] === 'yes') $colspan++;
                                if ($filters['group_by_document'] === 'yes') $colspan++;
                                if ($filters['group_by_purpose'] === 'yes') $colspan++;
                                ?>
                                <td colspan="<?php echo $colspan; ?>"><strong>Total</strong></td>
                                <?php $totals = calculateTotals($reportData); ?>
                                <td><strong><?php echo $totals['total_requests']; ?></strong></td>
                                <td><strong><?php echo $totals['pending']; ?></strong></td>
                                <td><strong><?php echo $totals['approved']; ?></strong></td>
                                <td><strong><?php echo $totals['rejected']; ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="mt-5 text-center py-5">
            <i class="fas fa-chart-bar fa-5x text-muted mb-3"></i>
            <h4 class="text-muted">No Report Generated Yet</h4>
            <p class="text-muted">Select your filters and click "Generate Report" to view data</p>
        </div>
        <?php endif; ?>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Function to load courses based on selected department
            function loadCourses(departmentId, selectedCourse) {
                $.ajax({
                    url: 'get_courses_by_department.php',
                    type: 'POST',
                    data: {
                        department_id: departmentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        var courseSelect = $('#course');
                        courseSelect.empty();
                        courseSelect.append('<option value="all">All Courses</option>');
                        
                        $.each(response, function(index, course) {
                            var selected = (course.id == selectedCourse) ? 'selected' : '';
                            courseSelect.append('<option value="' + course.id + '" ' + selected + '>' + course.name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error loading courses.');
                    }
                });
            }
            
            // Function to update report table via AJAX
            function updateReportTable() {
                // Check if report has been generated first
                if ($('#reportResultsContainer .mt-5').length === 0) {
                    return; // No report generated yet
                }
                
                // Show loading indicator
                $('#reportTableContainer').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Updating report...</p></div>');
                
                // Get current form values
                var formData = {
                    department: $('#department').val(),
                    course: $('#course').val(),
                    document_type: $('#document_type').val(),
                    purpose: $('#purpose').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    group_by_department: $('#group_by_department').is(':checked') ? 'yes' : 'no',
                    group_by_course: $('#group_by_course').is(':checked') ? 'yes' : 'no',
                    group_by_document: $('#group_by_document').is(':checked') ? 'yes' : 'no',
                    group_by_purpose: $('#group_by_purpose').is(':checked') ? 'yes' : 'no'
                };
                
                // Ensure at least one grouping option is selected
                if (formData.group_by_department === 'no' && formData.group_by_course === 'no' && 
                    formData.group_by_document === 'no' && formData.group_by_purpose === 'no') {
                    alert('Please select at least one grouping option (Department, Course, Document Type, or Purpose).');
                    return;
                }
                
                // Make AJAX request
                $.ajax({
                    url: 'update_report-request-document_ajax.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        // Update the report table
                        $('#reportTableContainer').html(response.html);
                        $('#reportPeriod').text(response.period);
                    },
                    error: function(xhr, status, error) {
                        $('#reportTableContainer').html('<div class="alert alert-danger">Error updating report: ' + error + '</div>');
                        console.error(xhr.responseText);
                    }
                });
            }
            
            // Load courses when department changes
            $('#department').change(function() {
                var departmentId = $(this).val();
                if (departmentId !== 'all') {
                    loadCourses(departmentId, '');
                } else {
                    $('#course').empty().append('<option value="all">All Courses</option>');
                }
            });
            
            // Load courses on page load if department is selected
            var initialDepartment = $('#department').val();
            var initialCourse = '<?php echo $filters['course']; ?>';
            
            if (initialDepartment !== 'all') {
                loadCourses(initialDepartment, initialCourse);
            }
            
            // Make entire group-by option clickable
            $('.group-by-option').click(function(e) {
                // Prevent default if clicking directly on the checkbox
                if (e.target.type !== 'checkbox') {
                    var checkbox = $(this).find('input[type="checkbox"]');
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    
                    // Trigger change event for real-time update
                    checkbox.trigger('change');
                }
            });
            
            // Real-time update when any group-by checkbox is changed
            $('input[name^="group_by_"]').change(function() {
                updateReportTable();
            });
            
            // Handle form submission for initial report generation
            $('#reportForm').submit(function(e) {
                if (!$(this).find('button[name="generate_pdf"]').is(':focus')) {
                    var groupByChecked = $('input[name^="group_by_"]:checked').length;
                    if (groupByChecked === 0) {
                        alert('Please select at least one grouping option (Department, Course, Document Type, or Purpose).');
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>