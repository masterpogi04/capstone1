<?php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'facilitator') {
    // Validate and sanitize input
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_SANITIZE_NUMBER_INT);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    if ($report_id && $notes !== false) {
        // Start a transaction
        $connection->begin_transaction();

        try {
            // Update the resolution notes
            $update_query = "UPDATE incident_reports SET resolution_notes = ? WHERE id = ?";
            $stmt = $connection->prepare($update_query);
            $stmt->bind_param("si", $notes, $report_id);
            
            if ($stmt->execute()) {
                // If successful, commit the transaction
                $connection->commit();
                echo 'success';
            } else {
                throw new Exception("Failed to update notes");
            }
            $stmt->close();
        } catch (Exception $e) {
            // If an error occurred, roll back the transaction
            $connection->rollback();
            error_log("Error updating notes: " . $e->getMessage());
            echo 'error';
        }
    } else {
        echo 'invalid_input';
    }
} else {
    echo 'unauthorized';
}

$connection->close();
?>