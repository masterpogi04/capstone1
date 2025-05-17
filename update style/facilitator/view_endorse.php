<?php
// Database connection parameters
$host = 'localhost';
$db   = 'capstone1';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Fetch the specific referral
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM referrals WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$referral = $stmt->fetch();

if (!$referral) {
    die("Referral not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSU Referral Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 100px;
            height: 100px;
            margin-bottom: 10px;
        }
        h1, h2, h3 {
            margin: 5px 0;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: bold;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            display: inline-block;
            margin-top: 30px;
            text-align: center;
        }
        .back-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .download-pdf-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="cvsu.jpg" alt="Cavite State University Logo" class="logo">
            <h2>Republic of the Philippines</h2>
            <h1>CAVITE STATE UNIVERSITY</h1>
            <h3>Don Severino delas Alas Campus</h3>
            <h3>Indang, Cavite</h3>
            <h2>REFERRAL FORM</h2>
        </div>
        
        <div class="form-group">
            <span class="form-label">Date:</span>
            <?= htmlspecialchars($referral['date']) ?>
        </div>

        <div class="form-group">
            <p>To the GUIDANCE COUNSELOR:</p>
            <p>This is to refer the student, 
                <strong><?= htmlspecialchars($referral['first_name'] . ' ' . $referral['middle_name'] . ' ' . $referral['last_name']) ?></strong> / 
                <strong><?= htmlspecialchars($referral['course_year']) ?></strong>
                to your office for counselling.</p>
        </div>
        <br>

        <div class="form-group">
            <p>Reason for referral:</p>
            <p><strong><?= htmlspecialchars($referral['reason_for_referral']) ?></strong></p>
            <?php if ($referral['reason_for_referral'] == 'Violation'): ?>
                <p>Violation to school rules, specifically: 
                <strong><?= htmlspecialchars($referral['violation_details']) ?></strong></p>
            <?php elseif ($referral['reason_for_referral'] == 'Other'): ?>
                <p>Other concern, specify: 
                <strong><?= htmlspecialchars($referral['other_concerns']) ?></strong></p>
            <?php endif; ?>
        </div>
        <br>


        <p>Thank you.</p>

        <div class="form-group">
            <div class="signature-line">
                <?= htmlspecialchars($referral['faculty_name']) ?>
            </div>
            <p>(Signature over printed name of Faculty/Employee)</p>
        </div>

        <div class="form-group">
            <span class="form-label">Acknowledged by:</span>
            <strong><?= htmlspecialchars($referral['acknowledged_by']) ?></strong>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button class="download-pdf-button" onclick="window.print()" style="margin-right: 10px;">Download PDF</button>
        <button class="back-button" onclick="window.history.back()">Back</button>
    </div>


    
</body>
</html>