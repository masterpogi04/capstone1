<?php
session_start();
include '../db.php';

// Validate user session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

// Create a simple diagnostic page to help troubleshoot archiving issues
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archiving Diagnostics</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .report-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .report-header {
            background-color: #f8f9fa;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .report-body {
            padding: 15px;
        }
        .student-list {
            margin-bottom: 10px;
        }
        .student-item {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .student-item:last-child {
            border-bottom: none;
        }
        .blockingReason {
            color: #dc3545;
            font-weight: bold;
        }
        .eligible {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Archiving Diagnostics</h1>
        
        <div class="alert alert-info">
            This tool helps identify why certain reports are not being archived even though they appear eligible.
        </div>
        
        <h2>Student ID Checker</h2>
        <form method="post" class="mb-4">
            <div class="form-group">
                <label for="studentId">Enter Student ID:</label>
                <input type="text" class="form-control" id="studentId" name="studentId" placeholder="Enter student ID to check">
            </div>
            <button type="submit" class="btn btn-primary">Check Student Reports</button>
        </form>
        
        <h2>Report ID Checker</h2>
        <form method="post" class="mb-4">
            <div class="form-group">
                <label for="reportId">Enter Report ID:</label>
                <input type="text" class="form-control" id="reportId" name="reportId" placeholder="Enter report ID to check eligibility">
            </div>
            <button type="submit" class="btn btn-primary">Check Report Eligibility</button>
        </form>
        
        <h2>System-wide Check</h2>
        <form method="post" class="mb-4">
            <input type="hidden" name="checkAll" value="true">
            <button type="submit" class="btn btn-warning">Check All Reports</button>
        </form>
        
        <div class="results mt-4">
            <?php
            // Function to determine if a report is eligible for archiving
            function isReportEligible($connection, $reportId) {
                $eligibleQuery = "SELECT 1 
                                  FROM incident_reports ir
                                  WHERE ir.id = ? 
                                  AND NOT EXISTS (
                                      SELECT 1 FROM student_violations sv 
                                      WHERE sv.incident_report_id = ir.id 
                                      AND sv.section_id IS NOT NULL
                                      AND sv.student_id IS NOT NULL
                                      AND sv.student_course != 'Graduated'
                                  )
                                  AND NOT EXISTS (
                                      SELECT 1 FROM incident_witnesses iw 
                                      WHERE iw.incident_report_id = ir.id 
                                      AND iw.section_id IS NOT NULL
                                      AND iw.witness_id IS NOT NULL
                                      AND iw.witness_course != 'Graduated'
                                  )";
                $stmt = $connection->prepare($eligibleQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $result = $stmt->get_result();
                return $result->num_rows > 0;
            }
            
            // Function to get detailed report information including all students and witnesses
            function getReportDetails($connection, $reportId) {
                $report = [];
                
                // Get basic report info
                $reportQuery = "SELECT * FROM incident_reports WHERE id = ?";
                $stmt = $connection->prepare($reportQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $report['info'] = $result->fetch_assoc();
                } else {
                    return null; // Report not found
                }
                
                // Get violations
                $violationsQuery = "SELECT * FROM student_violations WHERE incident_report_id = ?";
                $stmt = $connection->prepare($violationsQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $result = $stmt->get_result();
                $report['violations'] = [];
                while ($row = $result->fetch_assoc()) {
                    $report['violations'][] = $row;
                }
                
                // Get witnesses
                $witnessesQuery = "SELECT * FROM incident_witnesses WHERE incident_report_id = ?";
                $stmt = $connection->prepare($witnessesQuery);
                $stmt->bind_param("s", $reportId);
                $stmt->execute();
                $result = $stmt->get_result();
                $report['witnesses'] = [];
                while ($row = $result->fetch_assoc()) {
                    $report['witnesses'][] = $row;
                }
                
                // Check if eligible
                $report['eligible'] = isReportEligible($connection, $reportId);
                
                // If not eligible, identify blocking records
                if (!$report['eligible']) {
                    $report['blockingRecords'] = [];
                    
                    // Check each violation
                    foreach ($report['violations'] as $violation) {
                        if (
                            $violation['section_id'] !== null && 
                            $violation['student_id'] !== null && 
                            $violation['student_course'] !== 'Graduated'
                        ) {
                            $report['blockingRecords'][] = [
                                'type' => 'violation',
                                'id' => $violation['id'],
                                'student_id' => $violation['student_id'],
                                'student_name' => $violation['student_name'],
                                'reason' => 'Has section_id, student_id and course is not "Graduated": ' . $violation['student_course']
                            ];
                        }
                    }
                    
                    // Check each witness
                    foreach ($report['witnesses'] as $witness) {
                        if (
                            $witness['section_id'] !== null && 
                            $witness['witness_id'] !== null && 
                            $witness['witness_course'] !== 'Graduated'
                        ) {
                            $report['blockingRecords'][] = [
                                'type' => 'witness',
                                'id' => $witness['id'],
                                'witness_id' => $witness['witness_id'],
                                'witness_name' => $witness['witness_name'],
                                'reason' => 'Has section_id, witness_id and course is not "Graduated": ' . $witness['witness_course']
                            ];
                        }
                    }
                }
                
                return $report;
            }
            
            // Process student ID check
            if (isset($_POST['studentId']) && !empty($_POST['studentId'])) {
                $studentId = $_POST['studentId'];
                
                echo "<h3>Reports for Student ID: $studentId</h3>";
                
                // Find all reports involving this student
                $reportsQuery = "SELECT DISTINCT ir.id 
                                FROM incident_reports ir
                                LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
                                LEFT JOIN incident_witnesses iw ON ir.id = iw.incident_report_id
                                WHERE sv.student_id = ? OR iw.witness_id = ?
                                ORDER BY ir.date_reported DESC";
                $stmt = $connection->prepare($reportsQuery);
                $stmt->bind_param("ss", $studentId, $studentId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo "<div class='alert alert-warning'>No reports found for student ID: $studentId</div>";
                } else {
                    echo "<div class='alert alert-info'>Found " . $result->num_rows . " reports involving this student.</div>";
                    
                    // Check student status
                    $studentStatusQuery = "SELECT student_name, student_course, student_year_level, section_name 
                                          FROM student_violations 
                                          WHERE student_id = ? 
                                          UNION 
                                          SELECT witness_name, witness_course, witness_year_level, section_name 
                                          FROM incident_witnesses 
                                          WHERE witness_id = ?
                                          LIMIT 1";
                    $statusStmt = $connection->prepare($studentStatusQuery);
                    $statusStmt->bind_param("ss", $studentId, $studentId);
                    $statusStmt->execute();
                    $statusResult = $statusStmt->get_result();
                    
                    if ($statusResult->num_rows > 0) {
                        $student = $statusResult->fetch_assoc();
                        echo "<div class='card mb-3'>";
                        echo "<div class='card-header'>Student Status</div>";
                        echo "<div class='card-body'>";
                        echo "<p><strong>Name:</strong> " . htmlspecialchars($student['student_name']) . "</p>";
                        echo "<p><strong>Course:</strong> " . ($student['student_course'] === 'Graduated' ? 
                              "<span class='text-success'>Graduated</span>" : 
                              "<span class='text-danger'>" . htmlspecialchars($student['student_course']) . "</span>") . "</p>";
                        echo "<p><strong>Year Level:</strong> " . htmlspecialchars($student['student_year_level']) . "</p>";
                        echo "<p><strong>Section:</strong> " . htmlspecialchars($student['section_name']) . "</p>";
                        
                        if ($student['student_course'] !== 'Graduated') {
                            echo "<div class='alert alert-danger'>This student is not marked as Graduated, which may prevent reports from being archived.</div>";
                        }
                        
                        echo "</div>";
                        echo "</div>";
                    }
                    
                    while ($row = $result->fetch_assoc()) {
                        $reportId = $row['id'];
                        $reportDetails = getReportDetails($connection, $reportId);
                        
                        echo "<div class='report-card'>";
                        echo "<div class='report-header'>";
                        echo "<h4>Report ID: $reportId</h4>";
                        echo "<p>Status: " . ($reportDetails['eligible'] ? 
                              "<span class='eligible'>ELIGIBLE for archiving</span>" : 
                              "<span class='blockingReason'>NOT eligible for archiving</span>") . "</p>";
                        echo "</div>";
                        echo "<div class='report-body'>";
                        
                        echo "<p><strong>Description:</strong> " . htmlspecialchars($reportDetails['info']['description']) . "</p>";
                        echo "<p><strong>Date Reported:</strong> " . $reportDetails['info']['date_reported'] . "</p>";
                        
                        // Show violations
                        echo "<h5>Student Violations:</h5>";
                        echo "<div class='student-list'>";
                        foreach ($reportDetails['violations'] as $violation) {
                            $isBlocking = ($violation['section_id'] !== null && 
                                          $violation['student_id'] !== null && 
                                          $violation['student_course'] !== 'Graduated');
                            
                            echo "<div class='student-item" . ($isBlocking ? " bg-light" : "") . "'>";
                            echo "<div><strong>Name:</strong> " . htmlspecialchars($violation['student_name']) . "</div>";
                            echo "<div><strong>ID:</strong> " . ($violation['student_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Course:</strong> " . htmlspecialchars($violation['student_course'] ?? 'None') . "</div>";
                            echo "<div><strong>Year Level:</strong> " . htmlspecialchars($violation['student_year_level'] ?? 'None') . "</div>";
                            echo "<div><strong>Section ID:</strong> " . ($violation['section_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Section Name:</strong> " . htmlspecialchars($violation['section_name'] ?? 'None') . "</div>";
                            
                            if ($isBlocking) {
                                echo "<div class='blockingReason'>This student is blocking archival</div>";
                            }
                            
                            echo "</div>";
                        }
                        echo "</div>";
                        
                        // Show witnesses
                        echo "<h5>Witnesses:</h5>";
                        echo "<div class='student-list'>";
                        foreach ($reportDetails['witnesses'] as $witness) {
                            $isBlocking = ($witness['section_id'] !== null && 
                                          $witness['witness_id'] !== null && 
                                          $witness['witness_course'] !== 'Graduated');
                            
                            echo "<div class='student-item" . ($isBlocking ? " bg-light" : "") . "'>";
                            echo "<div><strong>Name:</strong> " . htmlspecialchars($witness['witness_name']) . "</div>";
                            echo "<div><strong>Type:</strong> " . htmlspecialchars($witness['witness_type']) . "</div>";
                            echo "<div><strong>ID:</strong> " . ($witness['witness_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Course:</strong> " . htmlspecialchars($witness['witness_course'] ?? 'None') . "</div>";
                            echo "<div><strong>Year Level:</strong> " . htmlspecialchars($witness['witness_year_level'] ?? 'None') . "</div>";
                            echo "<div><strong>Section ID:</strong> " . ($witness['section_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Section Name:</strong> " . htmlspecialchars($witness['section_name'] ?? 'None') . "</div>";
                            
                            if ($isBlocking) {
                                echo "<div class='blockingReason'>This witness is blocking archival</div>";
                            }
                            
                            echo "</div>";
                        }
                        echo "</div>";
                        
                        // Show blocking reasons if not eligible
                        if (!$reportDetails['eligible'] && !empty($reportDetails['blockingRecords'])) {
                            echo "<div class='alert alert-danger mt-3'>";
                            echo "<h5>Blocking Records:</h5>";
                            echo "<ul>";
                            foreach ($reportDetails['blockingRecords'] as $record) {
                                echo "<li><strong>" . ucfirst($record['type']) . ":</strong> " . 
                                     htmlspecialchars($record['reason']) . " - " . 
                                     htmlspecialchars($record[$record['type'] === 'violation' ? 'student_name' : 'witness_name']) . "</li>";
                            }
                            echo "</ul>";
                            echo "</div>";
                        }
                        
                        echo "</div>"; // end report-body
                        echo "</div>"; // end report-card
                    }
                }
            }
            
            // Process report ID check
            if (isset($_POST['reportId']) && !empty($_POST['reportId'])) {
                $reportId = $_POST['reportId'];
                
                echo "<h3>Report Check: $reportId</h3>";
                
                $reportDetails = getReportDetails($connection, $reportId);
                
                if (!$reportDetails) {
                    echo "<div class='alert alert-warning'>Report not found: $reportId</div>";
                } else {
                    echo "<div class='report-card'>";
                    echo "<div class='report-header'>";
                    echo "<h4>Report ID: $reportId</h4>";
                    echo "<p>Status: " . ($reportDetails['eligible'] ? 
                          "<span class='eligible'>ELIGIBLE for archiving</span>" : 
                          "<span class='blockingReason'>NOT eligible for archiving</span>") . "</p>";
                    echo "</div>";
                    echo "<div class='report-body'>";
                    
                    echo "<p><strong>Description:</strong> " . htmlspecialchars($reportDetails['info']['description']) . "</p>";
                    echo "<p><strong>Date Reported:</strong> " . $reportDetails['info']['date_reported'] . "</p>";
                    
                    // Show violations
                    echo "<h5>Student Violations:</h5>";
                    echo "<div class='student-list'>";
                    if (empty($reportDetails['violations'])) {
                        echo "<p>No student violations associated with this report.</p>";
                    } else {
                        foreach ($reportDetails['violations'] as $violation) {
                            $isBlocking = ($violation['section_id'] !== null && 
                                          $violation['student_id'] !== null && 
                                          $violation['student_course'] !== 'Graduated');
                            
                            echo "<div class='student-item" . ($isBlocking ? " bg-light" : "") . "'>";
                            echo "<div><strong>Name:</strong> " . htmlspecialchars($violation['student_name']) . "</div>";
                            echo "<div><strong>ID:</strong> " . ($violation['student_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Course:</strong> " . htmlspecialchars($violation['student_course'] ?? 'None') . "</div>";
                            echo "<div><strong>Year Level:</strong> " . htmlspecialchars($violation['student_year_level'] ?? 'None') . "</div>";
                            echo "<div><strong>Section ID:</strong> " . ($violation['section_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Section Name:</strong> " . htmlspecialchars($violation['section_name'] ?? 'None') . "</div>";
                            
                            if ($isBlocking) {
                                echo "<div class='blockingReason'>This student is blocking archival</div>";
                            }
                            
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                    
                    // Show witnesses
                    echo "<h5>Witnesses:</h5>";
                    echo "<div class='student-list'>";
                    if (empty($reportDetails['witnesses'])) {
                        echo "<p>No witnesses associated with this report.</p>";
                    } else {
                        foreach ($reportDetails['witnesses'] as $witness) {
                            $isBlocking = ($witness['section_id'] !== null && 
                                          $witness['witness_id'] !== null && 
                                          $witness['witness_course'] !== 'Graduated');
                            
                            echo "<div class='student-item" . ($isBlocking ? " bg-light" : "") . "'>";
                            echo "<div><strong>Name:</strong> " . htmlspecialchars($witness['witness_name']) . "</div>";
                            echo "<div><strong>Type:</strong> " . htmlspecialchars($witness['witness_type']) . "</div>";
                            echo "<div><strong>ID:</strong> " . ($witness['witness_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Course:</strong> " . htmlspecialchars($witness['witness_course'] ?? 'None') . "</div>";
                            echo "<div><strong>Year Level:</strong> " . htmlspecialchars($witness['witness_year_level'] ?? 'None') . "</div>";
                            echo "<div><strong>Section ID:</strong> " . ($witness['section_id'] ?? 'None') . "</div>";
                            echo "<div><strong>Section Name:</strong> " . htmlspecialchars($witness['section_name'] ?? 'None') . "</div>";
                            
                            if ($isBlocking) {
                                echo "<div class='blockingReason'>This witness is blocking archival</div>";
                            }
                            
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                    
                    // Show blocking reasons if not eligible
                    if (!$reportDetails['eligible'] && !empty($reportDetails['blockingRecords'])) {
                        echo "<div class='alert alert-danger mt-3'>";
                        echo "<h5>Blocking Records:</h5>";
                        echo "<ul>";
                        foreach ($reportDetails['blockingRecords'] as $record) {
                            echo "<li><strong>" . ucfirst($record['type']) . ":</strong> " . 
                                 htmlspecialchars($record['reason']) . " - " . 
                                 htmlspecialchars($record[$record['type'] === 'violation' ? 'student_name' : 'witness_name']) . "</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    }
                    
                    echo "</div>"; // end report-body
                    echo "</div>"; // end report-card
                }
            }
            
            // Process check all
            if (isset($_POST['checkAll']) && $_POST['checkAll'] === 'true') {
                // Find all eligible reports
                $eligibleQuery = "SELECT DISTINCT ir.id 
                                 FROM incident_reports ir
                                 WHERE NOT EXISTS (
                                     SELECT 1 FROM student_violations sv 
                                     WHERE sv.incident_report_id = ir.id 
                                     AND sv.section_id IS NOT NULL
                                     AND sv.student_id IS NOT NULL
                                     AND sv.student_course != 'Graduated'
                                 )
                                 AND NOT EXISTS (
                                     SELECT 1 FROM incident_witnesses iw 
                                     WHERE iw.incident_report_id = ir.id 
                                     AND iw.section_id IS NOT NULL
                                     AND iw.witness_id IS NOT NULL
                                     AND iw.witness_course != 'Graduated'
                                 )
                                 AND (
                                     EXISTS (SELECT 1 FROM student_violations sv WHERE sv.incident_report_id = ir.id)
                                     OR EXISTS (SELECT 1 FROM incident_witnesses iw WHERE iw.incident_report_id = ir.id)
                                 )
                                 ORDER BY ir.date_reported DESC";
                $stmt = $connection->prepare($eligibleQuery);
                $stmt->execute();
                $result = $stmt->get_result();
                
                echo "<h3>System-wide Check</h3>";
                
                if ($result->num_rows === 0) {
                    echo "<div class='alert alert-warning'>No reports are currently eligible for archiving.</div>";
                } else {
                    echo "<div class='alert alert-success'>Found " . $result->num_rows . " reports eligible for archiving.</div>";
                    
                    echo "<div class='card'>";
                    echo "<div class='card-header'>Eligible Reports</div>";
                    echo "<div class='card-body'>";
                    echo "<ul>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<li>" . $row['id'] . "</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    echo "</div>";
                }
                
                // Check for reports with graduated students that aren't eligible
                $potentialQuery = "SELECT DISTINCT ir.id 
                                  FROM incident_reports ir
                                  WHERE EXISTS (
                                      SELECT 1 FROM student_violations sv 
                                      WHERE sv.incident_report_id = ir.id 
                                      AND sv.student_course = 'Graduated'
                                  )
                                  OR EXISTS (
                                      SELECT 1 FROM incident_witnesses iw 
                                      WHERE iw.incident_report_id = ir.id 
                                      AND iw.witness_course = 'Graduated'
                                  )
                                  ORDER BY ir.date_reported DESC
                                  LIMIT 50";  // Limit to avoid too many results
                $stmt = $connection->prepare($potentialQuery);
                $stmt->execute();
                $resultPotential = $stmt->get_result();
                
                $potentialButBlocked = [];
                while ($row = $resultPotential->fetch_assoc()) {
                    $reportId = $row['id'];
                    if (!isReportEligible($connection, $reportId)) {
                        $potentialButBlocked[] = $reportId;
                    }
                }
                
                if (!empty($potentialButBlocked)) {
                    echo "<div class='alert alert-warning mt-4'>Found " . count($potentialButBlocked) . 
                         " reports with some graduated students that are NOT eligible for archiving.</div>";
                    
                    echo "<div class='card mt-3'>";
                    echo "<div class='card-header'>Reports with Some Graduated Students (Not Eligible)</div>";
                    echo "<div class='card-body'>";
                    echo "<ul>";
                    foreach ($potentialButBlocked as $reportId) {
                        echo "<li><a href='?reportId=$reportId'>" . $reportId . "</a> - <a href='#' onclick=\"document.getElementById('reportId').value='$reportId';document.getElementById('reportId').form.submit();return false;\">Check details</a></li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    echo "</div>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>