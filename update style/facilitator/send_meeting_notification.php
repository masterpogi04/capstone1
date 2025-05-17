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
    $student_email_message = $_POST['student_email_message'] ?? '';
    $sms_message = $_POST['sms_message'] ?? '';

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

    // SMS function remains the same
    function sendSMS($phoneNumber, $message) {
        $baseUrl = 'qdegk3.api.infobip.com';
        $apiKey = 'a16b18666a35b086606b44c04022de4f-60d793ce-bae3-4e9e-9118-eb5794edc95b';

        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '+63' . substr($phoneNumber, 1);
        } elseif (substr($phoneNumber, 0, 2) !== '63') {
            $phoneNumber = '+63' . $phoneNumber;
        }

        $curl = curl_init();
        
        $payload = json_encode([
            "messages" => [
                [
                    "destinations" => [["to" => $phoneNumber]],
                    "from" => "CEIT-GUIDANCE",
                    "text" => $message
                ]
            ]
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://" . $baseUrl . "/sms/2/text/advanced",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                "Authorization: App " . $apiKey,
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("SMS sending failed: " . $err);
        }

        return true;
    }

    // Updated query with correct column names
    $query = "SELECT 
        s.student_id,
        s.first_name as student_fname,
        s.last_name as student_lname,
        sp.contact_number,
        sp.email as student_email,
        a.email as adviser_email,
        a.id as adviser_id,
        a.first_name as adviser_fname,
        a.middle_initial as adviser_middle,
        a.last_name as adviser_lname,
        m.meeting_date,
        m.venue
        FROM incident_reports ir
        JOIN student_violations sv ON ir.id = sv.incident_report_id
        JOIN tbl_student s ON sv.student_id = s.student_id
        LEFT JOIN student_profiles sp ON s.student_id = sp.student_id
        LEFT JOIN sections sec ON s.section_id = sec.id
        LEFT JOIN tbl_adviser a ON sec.adviser_id = a.id
        LEFT JOIN meetings m ON ir.id = m.incident_report_id
        WHERE ir.id = ?";

    $stmt = $connection->prepare($query);
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $connection->error);
    }

    $stmt->bind_param("s", $report_id);
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        throw new Exception("No data found for report ID: " . $report_id);
    }

    // Create adviser full name
    $data['adviser_name'] = $data['adviser_fname'] . ' ' . 
                           ($data['adviser_middle'] ? $data['adviser_middle'] . ' ' : '') . 
                           $data['adviser_lname'];

    $success = true;
    $errors = [];

    switch($notification_type) {
        case 'adviser':
            if (!empty($data['adviser_email']) && !empty($adviser_message)) {
                try {
                    $mail = setupMailer();
                    $mail->addAddress($data['adviser_email']);
                    $mail->isHTML(false);
                    $mail->Subject = 'Student Meeting Notification';
                    $mail->Body = $adviser_message;
                    
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
                    $notif_message = "Meeting notification sent for student " . $data['student_fname'] . " " . $data['student_lname'];
                    $link = "view_meeting_details.php?id=" . $report_id;
                    
                    if (!$stmt->bind_param("ssss", $user_type, $data['adviser_id'], $notif_message, $link)) {
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
                $errors[] = "Missing adviser email or message content";
            }
            break;

        case 'student_email':
            if (!empty($data['student_email']) && !empty($student_email_message)) {
                try {
                    $mail = setupMailer();
                    $mail->addAddress($data['student_email']);
                    $mail->isHTML(false);
                    $mail->Subject = 'Meeting Schedule';
                    $mail->Body = $student_email_message;
                    
                    if (!$mail->send()) {
                        throw new Exception("Mailer Error: " . $mail->ErrorInfo);
                    }
                } catch (Exception $e) {
                    $success = false;
                    $errors[] = "Failed to send email to student: " . $e->getMessage();
                }
            } else {
                $errors[] = "Missing student email or message content";
            }
            break;

        case 'sms':
            if (!empty($data['contact_number']) && !empty($sms_message)) {
                try {
                    if (!sendSMS($data['contact_number'], $sms_message)) {
                        throw new Exception("SMS sending failed");
                    }
                } catch (Exception $e) {
                    $success = false;
                    $errors[] = "Failed to send SMS to student: " . $e->getMessage();
                }
            } else {
                $errors[] = "Missing contact number or SMS message content";
            }
            break;

        default:
            throw new Exception("Invalid notification type");
    }

    // Create notification for student if email or SMS was sent successfully
    if ($success && ($notification_type == 'student_email' || $notification_type == 'sms')) {
        try {
            $studentNotifQuery = "INSERT INTO notifications (user_type, user_id, message, link) 
                                 VALUES (?, ?, ?, ?)";
            $stmt = $connection->prepare($studentNotifQuery);
            if (!$stmt) {
                throw new Exception("Failed to prepare student notification query: " . $connection->error);
            }
            
            $user_type = 'student';
            $notif_message = "You have a scheduled meeting on " . date('F j, Y \a\t g:i A', strtotime($data['meeting_date']));
            $link = "view_meeting_details.php?id=" . $report_id;
            
            if (!$stmt->bind_param("ssss", $user_type, $data['student_id'], $notif_message, $link)) {
                throw new Exception("Parameter binding failed: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create student notification: " . $stmt->error);
            }
        } catch (Exception $e) {
            $success = false;
            $errors[] = "Failed to create student notification: " . $e->getMessage();
        }
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