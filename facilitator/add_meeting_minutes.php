<?php
// facilitator add_meeting_minutes.php
session_start();
include '../db.php';

// Initialize message variables
$success_message = '';
$error_message = '';

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

// First, fetch the facilitator's name
$facilitator_name = '';
if (isset($_SESSION['user_id'])) {
    $facilitator_query = "SELECT CONCAT(
        IFNULL(first_name, ''),
        ' ',
        CASE 
            WHEN middle_initial IS NOT NULL AND middle_initial != '' 
            THEN CONCAT(middle_initial, '. ')
            ELSE ''
        END,
        IFNULL(last_name, '')
    ) as full_name 
    FROM tbl_facilitator 
    WHERE id = ?";
    
    if ($stmt = $connection->prepare($facilitator_query)) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($facilitator = $result->fetch_assoc()) {
            $facilitator_name = trim($facilitator['full_name']); // trim to remove any extra spaces
        }
        $stmt->close();
    }
}

// If facilitator name couldn't be fetched, log the error
if (empty($facilitator_name)) {
    error_log("Failed to retrieve facilitator name for ID: " . $_SESSION['user_id']);
}

$report_id = isset($_GET['id']) ? $_GET['id'] : '';
$is_view_only = isset($_GET['view']) && $_GET['view'] === 'true';

// Function to save meeting minutes
function saveMinutesOnly($connection, $report_id, $meeting_date, $venue, $persons_present, $meeting_minutes, $prepared_by) {
    $connection->begin_transaction();
    try {
        // First, determine the next meeting sequence
        $sequence_query = "SELECT COALESCE(MAX(meeting_sequence), 0) + 1 AS next_sequence 
                          FROM meetings 
                          WHERE incident_report_id = ?";
        $sequence_stmt = $connection->prepare($sequence_query);
        $sequence_stmt->bind_param("s", $report_id);
        $sequence_stmt->execute();
        $sequence_result = $sequence_stmt->get_result();
        $sequence_row = $sequence_result->fetch_assoc();
        $next_sequence = $sequence_row['next_sequence'];
        
        $persons_present_json = json_encode($persons_present);
        
        // Insert new meeting minutes
        $insert_meeting = "INSERT INTO meetings 
                        (incident_report_id, meeting_date, venue, persons_present, meeting_minutes, prepared_by, meeting_sequence) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($insert_meeting);
        $stmt->bind_param("ssssssi", $report_id, $meeting_date, $venue, $persons_present_json, $meeting_minutes, $prepared_by, $next_sequence);
        $stmt->execute();
        
        $connection->commit();
        return true;
    } catch (Exception $e) {
        $connection->rollback();
        return false;
    }
}

// Fetch incident report and meeting details
if ($report_id) {
    // Validate the report_id
    if (!preg_match('/^[A-Za-z0-9\-]+$/', $report_id)) {
        die("Invalid report ID format");
    }

    $query = "SELECT ir.*, 
          GROUP_CONCAT(DISTINCT CONCAT(s.first_name, ' ', s.last_name)) as student_name,
          GROUP_CONCAT(DISTINCT c.name) as course_name
          FROM incident_reports ir 
          LEFT JOIN student_violations sv ON ir.id = sv.incident_report_id
          LEFT JOIN tbl_student s ON sv.student_id = s.student_id
          LEFT JOIN sections sec ON s.section_id = sec.id
          LEFT JOIN courses c ON sec.course_id = c.id
          WHERE ir.id = ?
          GROUP BY ir.id";
              
    $stmt = $connection->prepare($query);
    
    if ($stmt === false) {
        die("Prepare failed: " . $connection->error);
    }
    
    if (!$stmt->bind_param("s", $report_id)) {
        die("Binding parameters failed: " . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        die("Getting result failed: " . $stmt->error);
    }
    
    $incident = $result->fetch_assoc();
    if (!$incident) {
        die("No incident report found with ID: " . htmlspecialchars($report_id));
    }

    $stmt->close();
}

// Update the form handling section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meeting_date = $_POST['date_time'];
    $venue = $_POST['venue'];
    $meeting_minutes = trim($_POST['resolution_notes']);
    $prepared_by = $facilitator_name;
    $report_id = $_POST['report_id'];
    $persons_present = isset($_POST['persons_present']) ? array_filter($_POST['persons_present'], 'trim') : array();

    if (saveMinutesOnly($connection, $report_id, $meeting_date, $venue, $persons_present, $meeting_minutes, $prepared_by)) {
        $_SESSION['success_message'] = "Meeting minutes saved successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $report_id);
        exit();
    } else {
        $_SESSION['error_message'] = "Error saving meeting minutes.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $report_id);
        exit();
    }
}

    $existing_attendees = array();
    if (isset($incident['persons_present'])) {
        $existing_attendees = json_decode($incident['persons_present'], true) ?: array();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_view_only ? 'View Meeting Minutes' : 'Add Meeting Minutes'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
       /* Global Styles */
body {
    background: linear-gradient(135deg, #0d693e, #004d4d);
    min-height: 100vh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    margin: 0;
    padding: 2rem;
    color: #2d3748;
    line-height: 1.6;
}

.container {
    background-color: #ffffff;
    border-radius: 16px;
    padding: 2.5rem;
    margin: 2rem auto;
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    max-width: 1200px;
}

/* Back Button */
.back-button {
    background-color: #F4A261;
    border: none;
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.back-button:hover {
    background-color: #E76F51;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(231, 111, 81, 0.2);
}

/* Headings */
h1 {
    color: #1a365d;
    font-size: 2.25rem;
    font-weight: 600;
    margin-bottom: 2rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.75rem;
}

/* Card Styles */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    background-color: #0d693e;
    padding: 1.25rem;
    border-bottom: none;
}

.card-header h3 {
    color: white;
    font-size: 1.5rem;
    margin: 0;
    font-weight: 500;
}

.card-body {
    padding: 1.5rem;
}

/* Table Styles */
.table {
    margin-bottom: 0;
}

.table th {
    background-color: #f8fafc;
    color: #2d3748;
    font-weight: 600;
    padding: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}
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
}

.modern-back-button:hover {
    background-color: #28C498;
    transform: translateY(-1px);
    box-shadow: 0 3px 12px rgba(46, 218, 168, 0.25);
    color: white;
    text-decoration: none;
}

.modern-back-button i {
    font-size: 0.9rem;
    position: relative;
    top: 1px;
}
/* Form Controls */
.form-control {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
    font-size: 1rem;
}

.form-control:focus {
    border-color: #0d693e;
    box-shadow: 0 0 0 3px rgba(13, 105, 62, 0.1);
    outline: none;
}

textarea.form-control {
    min-height: 200px;
    resize: vertical;
}

/* Person Entry Styles */
.person-entry {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    align-items: center;
}

.remove-person {
    background: none;
    border: none;
    color: #dc3545;
    padding: 0.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.remove-person:hover {
    color: #bd2130;
    transform: scale(1.1);
}

.add-person {
    background-color: #0d693e;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.add-person:hover {
    background-color: #0a5432;
    transform: translateY(-1px);
}

/* Definition Lists */
dl.row {
    margin: 0;
}

dt {
    color: #4a5568;
    font-weight: 600;
    padding: 0.75rem 1rem;
}

dd {
    padding: 0.75rem 1rem;
    margin-bottom: 0;
}

/* Submit Button */
.btn-primary {
    background-color: #0d693e;
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background-color: #0a5432;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(13, 105, 62, 0.2);
}

/* Sweet Alert Customization */
.swal2-popup {
    border-radius: 12px;
    padding: 2rem;
}

.swal2-title {
    color: #1a365d !important;
    font-size: 1.5rem !important;
}

.swal2-html-container {
    color: #4a5568 !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    body {
        padding: 1rem;
    }

    .container {
        padding: 1.5rem;
        margin: 1rem;
    }

    h1 {
        font-size: 1.75rem;
    }

    .card-header h3 {
        font-size: 1.25rem;
    }

    .table td, .table th {
        padding: 0.75rem;
    }

    .btn-primary {
        width: 100%;
        justify-content: center;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.3s ease-out;
}

.person-entry {
    animation: fadeIn 0.3s ease-out;
}
    </style>
</head>
<body>
    <div class="container">
    <a href="view_approved_reports.php" class="modern-back-button">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Guidance Services</span>
</a>

        <h1>Minutes of the Meeting</h1>

        <?php if ($success_message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: <?php echo json_encode($success_message); ?>,
                        confirmButtonColor: '#0d693e'
                    });
                });
            </script>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: <?php echo json_encode($error_message); ?>,
                        confirmButtonColor: '#dc3545'
                    });
                });
            </script>
        <?php endif; ?>

        <?php if (isset($incident)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Incident Report Details</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Report ID:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['id']); ?></dd>

                        <dt class="col-sm-3">Student:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['student_name']); ?></dd>

                        <dt class="col-sm-3">Course:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['course_name']); ?></dd>

                        <dt class="col-sm-3">Violation:</dt>
                        <dd class="col-sm-9"><?php echo htmlspecialchars($incident['description']); ?></dd>
                    </dl>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report_id); ?>">
                <div class="card">
                    <div class="card-header">
                        <h3>Create Meeting Minutes</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Date / Time</th>
                                    <th>Venue</th>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="datetime-local" class="form-control" id="date_time" name="date_time" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="venue" name="venue" value="CEIT GUIDANCE OFFICE" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="2">Person/s Present</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div id="persons-container">
                                            <div class="person-entry">
                                                <input type="text" class="form-control" name="persons_present[]" placeholder="Enter person's present name" required>
                                                <button type="button" class="remove-person" onclick="removePerson(this)" title="Remove person">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="add-person" onclick="addPerson()">
                                                <i class="fas fa-plus-circle me-1"></i>Add Person</button>
                                        </td>
                                </tr>
                                <tr>
                                    <th colspan="2">Meeting Minutes</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="10" required placeholder="Enter detailed minutes of the meeting"></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="2">Prepared By</th>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <input type="text" class="form-control" id="prepared_by" name="prepared_by" 
                                            value="<?php echo !empty($facilitator_name) ? htmlspecialchars($facilitator_name) : 'Name not found'; ?>" 
                                            readonly>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-3">
                    <button type="submit" name="save_minutes" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Minutes
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">No incident report found.</div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function addPerson() {
                const container = document.getElementById('persons-container');
                    const inputs = container.querySelectorAll('input[name="persons_present[]"]');
            
                // Check if the last input field is empty
                const lastInput = inputs[inputs.length - 1];
                if (!lastInput.value.trim()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Add Person Present',
                        text: 'Please enter a name in the current field before adding a new one.',
                        confirmButtonColor: '#0d693e'
                    });
                    lastInput.focus();
                    return;
                }

                // If last input is not empty, proceed to add a new input field
                const newEntry = document.createElement('div');
                newEntry.className = 'person-entry';
                newEntry.innerHTML = `
                    <input type="text" class="form-control" name="persons_present[]" placeholder="Enter person's name">
                    <button type="button" class="remove-person" onclick="removePerson(this)" title="Remove person">
                        <i class="fas fa-times-circle"></i>
                    </button>
                `;
                container.appendChild(newEntry);
                newEntry.querySelector('input').focus();
            }

        function removePerson(button) {
            const container = document.getElementById('persons-container');
            if (container.children.length > 1) {
                Swal.fire({
                    title: 'Remove Person',
                    text: 'Are you sure you want to remove this person?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove'
                }).then((result) => {
                    if (result.isConfirmed) {
                        button.parentElement.remove();
                    }
                });
            } else {
                button.previousElementSibling.value = '';
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const inputs = document.querySelectorAll('input[name="persons_present[]"]');
            let hasValue = false;
            inputs.forEach(input => {
                if (input.value.trim()) hasValue = true;
            });
            
            if (!hasValue) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please add at least one person present.',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }

            const requiredFields = ['date_time', 'venue', 'resolution_notes'];
            const emptyFields = requiredFields.filter(field => !document.getElementById(field).value.trim());

            if (emptyFields.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Required Fields Missing',
                    text: 'Please fill in all required fields.',
                    confirmButtonColor: '#dc3545'
                });
                return;
            }

            Swal.fire({
                title: 'Save Meeting Minutes',
                text: 'Are you sure you want to save these meeting minutes?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d693e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, save it'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    </script>
</body>
</html>