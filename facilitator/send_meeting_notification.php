<?php
session_start();
include '../db.php';
require '../vendor/autoload.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    $report_id = $_POST['report_id'] ?? null;
    $notification_type = $_POST['notification_type'] ?? '';
    $adviser_message = $_POST['adviser_message'] ?? '';
    $witness_id = $_POST['witness_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;

    if (!$report_id || !$notification_type) {
        throw new Exception("Missing required parameters");
    } 

    // Initialize PHPMailer function remains the same
    function setupMailer() {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ceitguidanceoffice@gmail.com'; 
        $mail->Password = 'qapb ebhc owts ioel';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('ceitguidanceoffice@gmail.com', 'CEIT Guidance Office');
        return $mail;
    }

    // Update to use iProg SMS API instead of Infobip
    function sendSMS($phoneNumber, $message) {
        // Log the function call
        error_log("sendSMS called with number: " . $phoneNumber);
        
        // Clean up the number by removing any non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Format phone number for the API
        if (substr($phoneNumber, 0, 2) === '63') {
            $formattedNumber = $phoneNumber;
        } else if (substr($phoneNumber, 0, 1) === '0') {
            $formattedNumber = '63' . substr($phoneNumber, 1);
        } else {
            $formattedNumber = '63' . $phoneNumber;
        }
        
        // Log the formatted number
        error_log("Formatted phone number: " . $formattedNumber);
        
        try {
            // iProg SMS API credentials
            $api_token = '8558e190982b948ee3277f164e9b94b103ffcc7c';
            
            // Prepare the API URL
            $url = 'https://sms.iprogtech.com/api/v1/sms_messages';
            
            // Prepare the data for the HTTP request
            $data = [
                'api_token' => $api_token,
                'message' => $message,
                'phone_number' => $formattedNumber
            ];
            
            // Initialize cURL session
            $ch = curl_init($url);
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            
            // Execute the cURL request
            $response = curl_exec($ch);
            
            // Check for cURL errors
            if (curl_errno($ch)) {
                throw new Exception("cURL Error: " . curl_error($ch));
            }
            
            // Close cURL session
            curl_close($ch);
            
            // Decode the JSON response
            $result = json_decode($response, true);
            
            // Log the response
            error_log("SMS API Response: " . print_r($result, true));
            
            // Check if the API call was successful
            if (isset($result['status']) && $result['status'] == 200) {
                return $result['message_id'] ?? true;
            } else {
                throw new Exception("SMS API Error: " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            // General exception handling
            error_log("Exception in sendSMS: " . $e->getMessage());
            throw new Exception("SMS sending failed: " . $e->getMessage());
        }
    }

    // Get meeting details
    $meeting_query = "SELECT meeting_date, venue FROM meetings WHERE incident_report_id = ? ORDER BY meeting_date DESC LIMIT 1";
    $meeting_stmt = $connection->prepare($meeting_query);
    if (!$meeting_stmt) {
        throw new Exception("Database prepare failed for meeting query: " . $connection->error);
    }
    $meeting_stmt->bind_param("s", $report_id);
    $meeting_stmt->execute();
    $meeting_result = $meeting_stmt->get_result();
    $meeting_data = $meeting_result->fetch_assoc();
    
    if (!$meeting_data) {
        throw new Exception("No meeting data found for this report");
    }

    $success = true;
    $errors = [];

    switch($notification_type) {
        case 'adviser':
            if (!empty($_POST['adviser_message'])) {
                // Get adviser email
                $adviser_query = "SELECT a.email, a.id
                                 FROM sections s
                                 JOIN tbl_adviser a ON s.adviser_id = a.id
                                 JOIN tbl_student ts ON s.id = ts.section_id
                                 JOIN student_violations sv ON ts.student_id = sv.student_id
                                 WHERE sv.incident_report_id = ?
                                 LIMIT 1";
                $adviser_stmt = $connection->prepare($adviser_query);
                if (!$adviser_stmt) {
                    throw new Exception("Database prepare failed for adviser query: " . $connection->error);
                }
                $adviser_stmt->bind_param("s", $report_id);
                $adviser_stmt->execute();
                $adviser_result = $adviser_stmt->get_result();
                $adviser_data = $adviser_result->fetch_assoc();
                
                if (!$adviser_data || empty($adviser_data['email'])) {
                    throw new Exception("Adviser email not found");
                }
                
                try {
                    $mail = setupMailer();
                    $mail->addAddress($adviser_data['email']);
                    $mail->isHTML(false);
                    $mail->Subject = 'Student Meeting Notification';
                    $mail->Body = $_POST['adviser_message'];
                    
                    if (!$mail->send()) {
                        throw new Exception("Mailer Error: " . $mail->ErrorInfo);
                    }
                    
                    // Create notification for adviser
                    $adviserNotifQuery = "INSERT INTO notifications (user_type, user_id, message, link) 
                                        VALUES (?, ?, ?, ?)";
                    $stmt = $connection->prepare($adviserNotifQuery);
                    if (!$stmt) {
                        throw new Exception("Failed to prepare adviser notification query: " . $connection->error);
                    }
                    
                    $user_type = 'adviser';
                    $notif_message = "A meeting has been scheduled for your student's incident report";
                    $link = "view_student_incident_reports.php?id=" . $report_id;
                    
                    if (!$stmt->bind_param("ssss", $user_type, $adviser_data['id'], $notif_message, $link)) {
                        throw new Exception("Parameter binding failed: " . $stmt->error);
                    }
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create adviser notification: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    $success = false;
                    $errors[] = "Failed to send email to adviser: " . $e->getMessage();
                }
            } else {
                $errors[] = "Missing adviser message content";
            }
            break;

        case 'student_email':
            if (!$student_id) {
                throw new Exception("Missing student ID");
            }
            
            // Get student details
            $student_query = "SELECT 
                s.student_id,
                s.first_name as student_fname,
                s.last_name as student_lname,
                sp.email as student_email
                FROM tbl_student s
                LEFT JOIN student_profiles sp ON s.student_id = sp.student_id
                WHERE s.student_id = ?";
            $student_stmt = $connection->prepare($student_query);
            if (!$student_stmt) {
                throw new Exception("Database prepare failed for student query: " . $connection->error);
            }
            $student_stmt->bind_param("s", $student_id);
            $student_stmt->execute();
            $student_result = $student_stmt->get_result();
            $student_data = $student_result->fetch_assoc();
            
            if (!$student_data || empty($student_data['student_email'])) {
                throw new Exception("Student email not found");
            }
            
            $student_email_message = $_POST['student_email_message_' . $student_id] ?? '';
            if (empty($student_email_message)) {
                throw new Exception("Missing student email message content");
            }
            
            try {
                $mail = setupMailer();
                $mail->addAddress($student_data['student_email']);
                $mail->isHTML(false);
                $mail->Subject = 'Meeting Schedule';
                $mail->Body = $student_email_message;
                
                if (!$mail->send()) {
                    throw new Exception("Mailer Error: " . $mail->ErrorInfo);
                }
                
                // Create notification for student
                $studentNotifQuery = "INSERT INTO notifications (user_type, user_id, message, link) 
                                     VALUES (?, ?, ?, ?)";
                $stmt = $connection->prepare($studentNotifQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare student notification query: " . $connection->error);
                }
                
                $user_type = 'student';
                $notif_message = "You have a scheduled meeting on " . date('F j, Y \a\t g:i A', strtotime($meeting_data['meeting_date']));
                $link = "view_meeting_details.php?id=" . $report_id;
                
                if (!$stmt->bind_param("ssss", $user_type, $student_data['student_id'], $notif_message, $link)) {
                    throw new Exception("Parameter binding failed: " . $stmt->error);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create student notification: " . $stmt->error);
                }
            } catch (Exception $e) {
                $success = false;
                $errors[] = "Failed to send email to student: " . $e->getMessage();
            }
            break;

        case 'witness':
            if (!$witness_id) {
                throw new Exception("Witness data not found");
            }
            
            // Get witness data
            $witness_query = "SELECT * FROM incident_witnesses WHERE id = ? AND incident_report_id = ?";
            $witness_stmt = $connection->prepare($witness_query);
            if (!$witness_stmt) {
                throw new Exception("Database prepare failed for witness query: " . $connection->error);
            }
            $witness_stmt->bind_param("is", $witness_id, $report_id);
            $witness_stmt->execute();
            $witness_result = $witness_stmt->get_result();
            $witness_data = $witness_result->fetch_assoc();
            
            if (!$witness_data) {
                throw new Exception("No witness data found for ID: " . $witness_id);
            }
            
            if (!empty($witness_data['witness_email'])) {
                // Get the witness-specific message
                $witness_message = $_POST['witness_email_message_' . $witness_id] ?? '';
                
                if (empty($witness_message)) {
                    $errors[] = "Missing witness message content";
                    break;
                }
                
                try {
                    $mail = setupMailer();
                    $mail->addAddress($witness_data['witness_email']);
                    $mail->isHTML(false);
                    $mail->Subject = 'Meeting Schedule - Witness Request';
                    $mail->Body = $witness_message;
                    
                    if (!$mail->send()) {
                        throw new Exception("Mailer Error: " . $mail->ErrorInfo);
                    }
                    
                    // Create notification for witness if they have a user account
                    if ($witness_data['witness_type'] == 'student' && !empty($witness_data['witness_id'])) {
                        $witnessNotifQuery = "INSERT INTO notifications (user_type, user_id, message, link) 
                                            VALUES (?, ?, ?, ?)";
                        $stmt = $connection->prepare($witnessNotifQuery);
                        if (!$stmt) {
                            throw new Exception("Failed to prepare witness notification query: " . $connection->error);
                        }
                        
                        $user_type = 'student'; // Assuming witness is a student
                        $notif_message = "You are requested to attend a meeting as a witness on " . 
                                        date('F j, Y \a\t g:i A', strtotime($meeting_data['meeting_date']));
                        $link = "view_meeting_details.php?id=" . $report_id;
                        
                        if (!$stmt->bind_param("ssss", $user_type, $witness_data['witness_id'], $notif_message, $link)) {
                            throw new Exception("Parameter binding failed: " . $stmt->error);
                        }
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to create witness notification: " . $stmt->error);
                        }
                    }
                } catch (Exception $e) {
                    $success = false;
                    $errors[] = "Failed to send email to witness: " . $e->getMessage();
                }
            } else {
                $errors[] = "Missing witness email address";
            }
            break;

        case 'sms':
            if (!$student_id) {
                throw new Exception("Missing student ID");
            }
            
            // Get student contact details
            $student_query = "SELECT 
                s.student_id,
                s.first_name as student_fname,
                s.last_name as student_lname,
                sp.contact_number
                FROM tbl_student s
                LEFT JOIN student_profiles sp ON s.student_id = sp.student_id
                WHERE s.student_id = ?";
            $student_stmt = $connection->prepare($student_query);
            if (!$student_stmt) {
                throw new Exception("Database prepare failed for student query: " . $connection->error);
            }
            $student_stmt->bind_param("s", $student_id);
            $student_stmt->execute();
            $student_result = $student_stmt->get_result();
            $student_data = $student_result->fetch_assoc();
            
            if (!$student_data || empty($student_data['contact_number'])) {
                throw new Exception("Student contact number not found");
            }
            
            $sms_message = $_POST['sms_message_' . $student_id] ?? '';
            if (empty($sms_message)) {
                throw new Exception("Missing SMS message content");
            }
            
            try {
                error_log("SMS Notification Request - Phone: " . $student_data['contact_number']);
                $sms_result = sendSMS($student_data['contact_number'], $sms_message);
                if (!$sms_result) {
                    throw new Exception("SMS sending failed - no result returned");
                }
                
                // Create notification for student
                $studentNotifQuery = "INSERT INTO notifications (user_type, user_id, message, link) 
                                     VALUES (?, ?, ?, ?)";
                $stmt = $connection->prepare($studentNotifQuery);
                if (!$stmt) {
                    throw new Exception("Failed to prepare student notification query: " . $connection->error);
                }
                
                $user_type = 'student';
                $notif_message = "SMS notification sent for meeting";
                $link = "view_meeting_details.php?id=" . $report_id;
                
                if (!$stmt->bind_param("ssss", $user_type, $student_data['student_id'], $notif_message, $link)) {
                    throw new Exception("Parameter binding failed: " . $stmt->error);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create student notification: " . $stmt->error);
                }
            } catch (Exception $e) {
                $success = false;
                $errors[] = "Failed to send SMS: " . $e->getMessage();
                error_log("SMS Exception: " . $e->getMessage());
            }
            break;

        default:
            throw new Exception("Invalid notification type");
    }

    // Ensure proper JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Notification sent successfully' : 'Failed to send notification',
        'errors' => $errors
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Meeting notification error: " . $e->getMessage());
    
    // Send proper JSON response even in case of error
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "An error occurred: " . $e->getMessage(),
        'errors' => [$e->getMessage()]
    ]);
}
?>