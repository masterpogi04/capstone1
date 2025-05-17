<?php
// update_meeting.php
ob_start();
session_start();
include '../db.php';

// Set proper content type header
header('Content-Type: application/json');

// Make sure no whitespace or other characters are output
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Check if user is logged in as facilitator
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate meeting ID
    if (!isset($_POST['meeting_id']) || !is_numeric($_POST['meeting_id'])) {
        throw new Exception('Invalid meeting ID');
    }

    $meeting_id = intval($_POST['meeting_id']);
    $venue = trim($_POST['venue'] ?? '');
    $persons_present = $_POST['persons_present'] ?? '';
    $meeting_minutes = trim($_POST['meeting_minutes'] ?? '');

    // Basic validation
    if (empty($venue) || empty($persons_present) || empty($meeting_minutes)) {
        throw new Exception('All fields are required');
    }

    // Convert persons present to JSON array
    $persons_array = array_map('trim', explode(',', $persons_present));
    $persons_json = json_encode($persons_array);

    if ($persons_json === false) {
        throw new Exception('Error encoding persons present data');
    }

    // Update query modified to match your database structure
    $update_query = "UPDATE meetings 
                    SET venue = ?,
                        persons_present = ?,
                        meeting_minutes = ?
                    WHERE id = ?";

    $stmt = $connection->prepare($update_query);
    
    if (!$stmt) {
        throw new Exception($connection->error);
    }

    $stmt->bind_param("sssi", 
        $venue,
        $persons_json,
        $meeting_minutes,
        $meeting_id
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
        $response = ['success' => true, 'message' => 'Meeting updated successfully'];
    } else {
        $response = ['success' => false, 'message' => 'Meeting not found'];
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connection)) {
        $connection->close();
    }
}

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Send the JSON response
echo json_encode($response);
exit;
?>