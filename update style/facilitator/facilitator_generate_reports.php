<?php
session_start();
include '../db.php';
require 'C:/xampp/htdocs/capstone1/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

function generateReport($connection, $reportType, $startDate, $endDate) {
    $query = "";
    switch ($reportType) {
        case 'monthly_summary':
            $query = "SELECT COUNT(*) as total_requests, 
                             SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                             SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                             SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                             COUNT(DISTINCT document_request) as distinct_documents
                      FROM document_requests 
                      WHERE request_time BETWEEN ? AND ?";
            break;
        case 'department_wise':
            $query = "SELECT d.name as department, COUNT(*) as total_requests,
                             COUNT(DISTINCT dr.document_request) as distinct_documents
                      FROM document_requests dr
                      JOIN tbl_student s ON dr.student_id = s.student_id
                      JOIN sections sec ON s.section_id = sec.id
                      JOIN departments d ON sec.department_id = d.id
                      WHERE dr.request_time BETWEEN ? AND ?
                      GROUP BY d.name";
            break;
        case 'course_wise':
            $query = "SELECT c.name as course, COUNT(*) as total_requests,
                             COUNT(DISTINCT dr.document_request) as distinct_documents
                      FROM document_requests dr
                      JOIN tbl_student s ON dr.student_id = s.student_id
                      JOIN sections sec ON s.section_id = sec.id
                      JOIN courses c ON sec.course_id = c.id
                      WHERE dr.request_time BETWEEN ? AND ?
                      GROUP BY c.name";
            break;
        case 'department_course_wise':
            $query = "SELECT d.name as department, c.name as course, COUNT(*) as total_requests,
                             COUNT(DISTINCT dr.document_request) as distinct_documents
                      FROM document_requests dr
                      JOIN tbl_student s ON dr.student_id = s.student_id
                      JOIN sections sec ON s.section_id = sec.id
                      JOIN departments d ON sec.department_id = d.id
                      JOIN courses c ON sec.course_id = c.id
                      WHERE dr.request_time BETWEEN ? AND ?
                      GROUP BY d.name, c.name";
            break;
        case 'document_wise':
            $query = "SELECT document_request, COUNT(*) as total_requests,
                             SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                             SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                             SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
                      FROM document_requests 
                      WHERE request_time BETWEEN ? AND ?
                      GROUP BY document_request";
            break;
    }

    $stmt = $connection->prepare($query);
    if ($stmt === false) {
        die("Error preparing statement: " . $connection->error);
    }
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

$reportData = null;
$reportType = '';
$startDate = '';
$endDate = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'];
    $startDate = $_POST['start_date'] . ' 00:00:00';
    $endDate = $_POST['end_date'] . ' 23:59:59';

    if ($reportType === 'all') {
        $allReportTypes = ['monthly_summary', 'department_wise', 'course_wise', 'department_course_wise', 'document_wise'];
        $reportData = [];
        foreach ($allReportTypes as $type) {
            $reportData[$type] = generateReport($connection, $type, $startDate, $endDate);
        }
    } else {
        $reportData = generateReport($connection, $reportType, $startDate, $endDate);
    }

    if (isset($_POST['download_pdf'])) {
        header("Location: generate_pdf_document_request.php");
        exit;
    }
}

function calculateTotals($reportData, $reportType) {
    $totals = array();
    foreach ($reportData as $row) {
        foreach ($row as $key => $value) {
            if (is_numeric($value)) {
                if (!isset($totals[$key])) {
                    $totals[$key] = 0;
                }
                $totals[$key] += $value;
            }
        }
    }
    return $totals;
}

function getReportTypeName($reportType) {
    switch ($reportType) {
        case 'monthly_summary':
            return 'Monthly Summary';
        case 'department_wise':
            return 'Department Wise';
        case 'course_wise':
            return 'Course Wise';
        case 'department_course_wise':
            return 'Department and Course Wise';
        case 'document_wise':
            return 'Document Wise';
        default:
            return ucwords(str_replace('_', ' ', $reportType));
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
</head>
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
            background-color: #ff9f1c;
            padding: 15px;
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .container {
            background-color: #ffffff;
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: bold;
            color: #0d693e;
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
            align-self: flex-start;
            margin-bottom: 20px;
            background-color: #ff9f1c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .back-button:hover {
            background-color: #e88e00;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
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
    </style>
<body>
    <div class="header">
        GENERATE REPORT
    </div>
    <div class="container">
        <a href="facilitator_requested_documents.php" class="btn back-button">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <h2>Generate Report</h2>
        <form method="POST">
            <div class="form-group">
                <label for="report_type">Report Type</label>
                <select class="form-control" id="report_type" name="report_type">
                    <option value="all">All Reports</option>
                    <option value="monthly_summary">Monthly Summary</option>
                    <option value="department_wise">Department-wise Requests</option>
                    <option value="course_wise">Course-wise Requests</option>
                    <option value="department_course_wise">Department and Course-wise Requests</option>
                    <option value="document_wise">Document-wise Requests</option>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>

        <?php if ($reportData): ?>
            <div class="mt-5">
                <h3>Report Preview</h3>
                <?php
                if ($reportType === 'all') {
                    foreach ($reportData as $type => $data) {
                        displayReportTable($type, $data, $startDate, $endDate);
                    }
                } else {
                    displayReportTable($reportType, $reportData, $startDate, $endDate);
                }
                ?>
                <form action="generate_pdf_document_request.php" method="post" target="_blank">
                    <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($reportType); ?>">
                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    <input type="hidden" name="report_data" value="<?php echo htmlspecialchars(json_encode($reportData)); ?>">
                    <button type="submit" class="btn btn-success mt-3">
                        <i class="fas fa-file-pdf"></i> Generate PDF
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
function displayReportTable($reportType, $data, $startDate, $endDate) {
    ?>
    <h4><?php echo getReportTypeName($reportType); ?></h4>
    <p><strong>Period: <?php echo date('F j, Y', strtotime($startDate)); ?> - 
       <?php echo date('F j, Y', strtotime($endDate)); ?></strong></p>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <?php foreach (array_keys($data[0]) as $header): ?>
                        <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $header))); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php
                    $totals = calculateTotals($data, $reportType);
                    $firstColumn = true;
                    foreach (array_keys($data[0]) as $key):
                        if ($firstColumn):
                            $firstColumn = false;
                    ?>
                            <td><strong>Total</strong></td>
                    <?php else: ?>
                        <td>
                            <?php
                            if (isset($totals[$key])) {
                                echo "<strong>" . htmlspecialchars($totals[$key]) . "</strong>";
                            } else {
                                echo "-";
                            }
                            ?>
                        </td>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}
?>