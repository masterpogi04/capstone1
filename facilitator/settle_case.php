<?php
session_start();
include '../db.php';

// Check if user is logged in as facilitator
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'facilitator') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Function to create notifications (same as in view_all_minutes.php)
function createNotification($connection, $user_type, $user_id, $message, $link) {
    $query = "INSERT INTO notifications (user_type, user_id, message, link) VALUES (?, ?, ?, ?)";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssss", $user_type, $user_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'settle') {
    $report_id = $_POST['report_id'];
    $meeting_minutes = $_POST['meeting_minutes'];

    $connection->begin_transaction();
    try {
        // Update incident report status
        $update_query = "UPDATE incident_reports SET 
                        resolution_notes = ?,
                        status = 'Settled',
                        resolution_status = 'Resolved',
                        approval_date = NOW()
                        WHERE id = ?";
        $stmt = $connection->prepare($update_query);
        $stmt->bind_param("ss", $meeting_minutes, $report_id);
        $stmt->execute();

        // Update student_violations table
        $update_violations = "UPDATE student_violations SET status = 'Settled' 
                            WHERE incident_report_id = ?";
        $stmt = $connection->prepare($update_violations);
        $stmt->bind_param("s", $report_id);
        $stmt->execute();

        // Create notifications
        $notify_query = "SELECT sv.student_id, a.id as adviser_id
                        FROM student_violations sv 
                        JOIN tbl_student s ON sv.student_id = s.student_id
                        JOIN sections sec ON s.section_id = sec.id
                        JOIN tbl_adviser a ON sec.adviser_id = a.id
                        WHERE sv.incident_report_id = ?";
        $notify_stmt = $connection->prepare($notify_query);
        $notify_stmt->bind_param("s", $report_id);
        $notify_stmt->execute();
        $notify_result = $notify_stmt->get_result();
        $notify_info = $notify_result->fetch_assoc();

        if ($notify_info) {
            createNotification($connection, 'student', $notify_info['student_id'], 
                             "Your incident report has been marked as settled.",
                             "view_student_incident_reports.php?id=" . $report_id);
            
            createNotification($connection, 'adviser', $notify_info['adviser_id'],
                             "An incident report for your student has been marked as settled.",
                             "view_student_incident_reports.php?id=" . $report_id);
        }

        $connection->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $connection->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>