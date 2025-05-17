<?php
session_start();
include '../db.php';

// Check if the user is logged in as a facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $ids_to_delete = $_POST['delete'];
    
    // Prepare the delete statement
    $delete_stmt = $connection->prepare("DELETE FROM document_requests WHERE request_id = ?");
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($ids_to_delete as $id) {
        $delete_stmt->bind_param("s", $id);
        
        if ($delete_stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $delete_stmt->close();
    
    // Set a message based on the results
    if ($success_count > 0) {
        $_SESSION['message'] = "Successfully deleted $success_count request(s).";
        if ($error_count > 0) {
            $_SESSION['message'] .= " Failed to delete $error_count request(s).";
        }
    } else {
        $_SESSION['message'] = "Failed to delete any requests.";
    }
} else {
    $_SESSION['message'] = "No requests selected for deletion.";
}

// Redirect back to the dashboard
header("Location: facilitator_dashboard.php");
exit();
?>