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
    <style>
        :root {
            --primary-color: #0f6a1a;
            --secondary-color: #F4A261;
            --background-color: #FAF3E0;
            --text-color: #2C3E50;
            --border-radius: 12px;
            --primary1-color: #0d693e;
            --secondary1-color: #004d4d;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary1-color), var(--secondary1-color));
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .info-card {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
        }

        .btn-custom {
            background: linear-gradient(145deg, var(--primary-color), #218838);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(15, 106, 26, 0.2);
            color: white;
        }
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
    
        .form-control {
            border-radius: var(--border-radius);
            border: 2px solid #E2E8F0;
            padding: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(15, 106, 26, 0.25);
        }

        .section-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        #studentList {
            margin-top: 2rem;
            border-top: 2px solid #E2E8F0;
            padding-top: 2rem;
        }

    
        .info-label {
            font-weight: 600;
            color: #004d4d;
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
            }
            
            .content {
                padding: 1.5rem;
            }
            
            .header-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
 

    <div class="content-wrapper">
        
        <div class="content">
        <a href="view_profiles.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> Back to Selections
        </a>
        
            <h2 class="section-title">Select Section</h2>
            
            <div class="info-card">
            <div><span class="info-label">Department:</span>  <?php echo htmlspecialchars($info['department_name']); ?></p>
            <div><span class="info-label">Course:</span> <?php echo htmlspecialchars($info['course_name']); ?></p>
            <div><span class="info-label">Year Level:</span>  <?php echo htmlspecialchars($year_level); ?></p>
    </div>
            <form id="sectionSelector">
                <div class="form-group">
                    <label for="section" class="info-label">Section:</label>
                    <select class="form-control" id="section" name="section_id" required>
                        <option value="">Select a section</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['id']; ?>">
                                <?php echo htmlspecialchars($section['section_no']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit"  class="btn btn-custom btn-block"><i class="fas fa-users mr-2"></i> View Students</button>
            </form>
            <div id="studentList"></div>
        </div>
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