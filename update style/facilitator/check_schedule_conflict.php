<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meeting_date'])) {
    $meeting_date = $_POST['meeting_date'];
    
    // Check for existing meetings at the same time
    $query = "SELECT id FROM meetings 
              WHERE meeting_date = ? 
              AND (meeting_minutes IS NULL OR meeting_minutes = '')";
              
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $meeting_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = [
        'hasConflict' => $result->num_rows > 0
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// If we get here, something went wrong
header('HTTP/1.1 400 Bad Request');
echo json_encode(['error' => 'Invalid request']);
?>