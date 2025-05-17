<?php
session_start();
include '../db.php';

// Ensure the user is logged in and is a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: ../login.php");
    exit();
}

$department_id = $_GET['department_id'] ?? null;
$course_id = $_GET['course_id'] ?? null;
$year_level = $_GET['year_level'] ?? null;

if (!$department_id || !$course_id || !$year_level) {
    header("Location: view_profiles.php");
    exit();
}

// Fetch sections
$stmt = $connection->prepare("SELECT id, section_no FROM sections WHERE department_id = ? AND course_id = ? AND year_level = ?");
$stmt->bind_param("iis", $department_id, $course_id, $year_level);
$stmt->execute();
$result = $stmt->get_result();
$sections = $result->fetch_all(MYSQLI_ASSOC);

// Fetch department and course names
$stmt = $connection->prepare("SELECT d.name AS department_name, c.name AS course_name FROM departments d JOIN courses c ON d.id = c.department_id WHERE d.id = ? AND c.id = ?");
$stmt->bind_param("ii", $department_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$info = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Section - CEIT Guidance Office</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
</head>
<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #FAF3E0;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            background-color: #F4A261;
            color: black;
            padding: 10px 20px;
            text-align: left;
            font-size: 24px;
            font-weight: bold;
        }
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        .content {
            background-color: white;
            border: 2px solid #0f6a1a;
            border-radius: 10px;
            padding: 20px;
            max-width: 800px;
            width: 100%;
        }
        .btn-custom {
            background-color: #0f6a1a;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 18px;
            cursor: pointer;
        }
        .btn-custom:hover {
            background-color: #218838;
        }
        .btn-back {
            align-self: flex-start;
            background-color: #F4A261;
            color: black;
            border: none;
            padding: 10px 20px;
            font-size: 18px;
            cursor: pointer;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .btn-back:hover {
            background-color: #e76f51;
        }
        .form-group label {
            font-weight: bold;
            color: #004d4d;
        }
        #studentList {
            margin-top: 30px;
        }
        .footer {
            background-color: #F4A261;
            color: black;
            text-align: center;
            padding: 10px;
            width: 100%;
        }
    </style>
<body>
    <div class="header">
        CEIT - GUIDANCE OFFICE
        <i class="fas fa-bell float-right" style="font-size:24px;"></i>
    </div>

    <div class="content-wrapper">
        <a href="view_profiles.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Selections
        </a>
        <div class="content">
            <h2 class="text-center mb-4">Select Section</h2>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($info['department_name']); ?></p>
            <p><strong>Course:</strong> <?php echo htmlspecialchars($info['course_name']); ?></p>
            <p><strong>Year Level:</strong> <?php echo htmlspecialchars($year_level); ?></p>
            
            <form id="sectionSelector">
                <div class="form-group">
                    <label for="section">Section:</label>
                    <select class="form-control" id="section" name="section_id" required>
                        <option value="">Select a section</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['id']; ?>">
                                <?php echo htmlspecialchars($section['section_no']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-custom btn-block">View Students</button>
            </form>
            <div id="studentList"></div>
        </div>
    </div>

    <div class="footer">
        Contact number | Email | Copyright
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#sectionSelector').submit(function(e) {
            e.preventDefault();
            var sectionId = $('#section').val();
            if(sectionId) {
                $.ajax({
                    url: 'get_data.php',
                    type: 'GET',
                    data: {
                        action: 'get_students',
                        section_id: sectionId,
                        department_id: <?php echo json_encode($department_id); ?>,
                        course_id: <?php echo json_encode($course_id); ?>,
                        year_level: <?php echo json_encode($year_level); ?>
                    },
                    success: function(data) {
                        $('#studentList').html(data);
                    },
                    error: function() {
                        alert('An error occurred while fetching student data.');
                    }
                });
            }
        });
    });
    </script>
</body>
</html>