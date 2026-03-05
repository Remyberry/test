<?php
// Include session management
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$record_id = intval($_GET['id']);

// Database connection
require_once 'includes/db_connect.php';

// Check if the record exists and is a draft
$check_query = "SELECT r.*, u.name as employee_name, u.department_id FROM records r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.id = ? AND r.document_status = 'Draft'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = "Record not found or is not a draft.";
    header("Location: records.php");
    exit();
}

// Check if the user has permission to submit this draft
$record = $result->fetch_assoc();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($record['user_id'] != $user_id && $user_role !== 'admin' && $user_role !== 'president') {
    $_SESSION['error_message'] = "You don't have permission to submit this draft.";
    header("Location: records.php");
    exit();
}

// Update the record status to Pending
$update_query = "UPDATE records SET status = 'Pending', date_submitted = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $record_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Draft has been successfully submitted For Review.";
    
    // Send email notification to department head if this is an IPCR
    if ($record['form_type'] == 'IPCR') {
        // Get department head's email
        $dept_head_query = "SELECT u.email, u.name FROM users u 
                            WHERE u.department_id = ? AND u.role = 'department_head'";
        $stmt = $conn->prepare($dept_head_query);
        $stmt->bind_param("i", $record['department_id']);
        $stmt->execute();
        $dept_head_result = $stmt->get_result();
        
        if ($dept_head_result->num_rows > 0) {
            $dept_head = $dept_head_result->fetch_assoc();
            $dept_head_email = $dept_head['email'];
            $dept_head_name = $dept_head['name'];
            
            // Email subject and message
            $subject = "New IPCR Submission For Review";
            $employee_name = $record['employee_name'];
            $period = $record['period'];
            
            $message = "Dear $dept_head_name,\n\n";
            $message .= "A new Individual Performance Commitment and Review (IPCR) has been submitted for your review.\n\n";
            $message .= "Details:\n";
            $message .= "Employee: $employee_name\n";
            $message .= "Period: $period\n";
            $message .= "Submission Date: " . date('Y-m-d H:i:s') . "\n\n";
            $message .= "Please login to the EPMS system to review this submission.\n\n";
            $message .= "This is an automated message. Please do not reply to this email.";
            
            // Email headers
            $headers = "From: noreply@epms.com\r\n";
            $headers .= "Reply-To: noreply@epms.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Send email
            mail($dept_head_email, $subject, $message, $headers);
        }
    }
} else {
    $_SESSION['error_message'] = "Error submitting draft: " . $conn->error;
}

// Redirect back to records page
header("Location: records.php");
exit(); 