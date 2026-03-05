<?php
/**
 * EPMS - Employee Performance Management System
 * Form Routing and Approval Workflow Implementation
 * 
 * This file implements the form routing and approval algorithm shown in the flowchart:
 * 1. Regular Employee fills out form
 * 2. Employee completes self-rating
 * 3. System check form for completeness and self-rating
 * 4. If form is complete, route to Department Head for final rating and review
 * 5. Department Head reviews and rates employee
 * 6. Final rating is saved in the system
 */

// Function to check if a form is complete and has self-rating
function isFormCompleteAndRated($form_data, $form_type) {
    if (empty($form_data)) {
        return false;
    }
    
    // Different checks based on form type
    switch ($form_type) {
        case 'IPCR':
            // Get computation type, default to Type1
            $computation_type = $form_data['computation_type'] ?? 'Type1';

            // Check if form has strategic functions
            if (!isset($form_data['strategic_functions']) || !is_array($form_data['strategic_functions']) || empty($form_data['strategic_functions'])) {
                return false;
            }
            
            // Check if form has core functions
            if (!isset($form_data['core_functions']) || !is_array($form_data['core_functions']) || empty($form_data['core_functions'])) {
                return false;
            }
            
            // Check if at least one strategic function has rating
            $has_strategic_rating = false;
            foreach ($form_data['strategic_functions'] as $func) {
                // Check for non-empty MFO and set ratings (Q, E, T, A)
                if (!empty($func['mfo']) && isset($func['q']) && isset($func['e']) && isset($func['t']) && isset($func['a'])) {
                    $has_strategic_rating = true;
                    break;
                }
            }
            
            // Check if at least one core function has rating
            $has_core_rating = false;
            foreach ($form_data['core_functions'] as $func) {
                if (!empty($func['mfo']) && isset($func['q']) && isset($func['e']) && isset($func['t']) && isset($func['a'])) {
                    $has_core_rating = true;
                    break;
                }
            }

            $has_support_rating = true; // Default to true for Type1

            // CONDITIONAL CHECK for Support Functions (Type2)
            if ($computation_type === 'Type2') {
                $has_support_rating = false;
                // Must have support section
                if (!isset($form_data['support_functions']) || !is_array($form_data['support_functions'])) {
                    return false; 
                }
                
                // Must have at least one support function with rating
                foreach ($form_data['support_functions'] as $func) {
                    if (!empty($func['mfo']) && isset($func['q']) && isset($func['e']) && isset($func['t']) && isset($func['a'])) {
                        $has_support_rating = true;
                        break;
                    }
                }
            }
            
            return $has_strategic_rating && $has_core_rating && $has_support_rating;
            
        case 'DPCR':
            // For DPCR, just check if there are entries
            $has_entries = false;
            
            // Strategic functions
            if (isset($form_data['strategic_functions']) && is_array($form_data['strategic_functions'])) {
                foreach ($form_data['strategic_functions'] as $entry) {
                    if (!empty($entry['major_output']) && !empty($entry['success_indicators'])) {
                        $has_entries = true;
                        break;
                    }
                }
            }
            
            // Core functions
            if (!$has_entries && isset($form_data['core_functions']) && is_array($form_data['core_functions'])) {
                foreach ($form_data['core_functions'] as $entry) {
                    if (!empty($entry['major_output']) && !empty($entry['success_indicators'])) {
                        $has_entries = true;
                        break;
                    }
                }
            }
            
            return $has_entries;
            
        case 'IDP':
            // For IDP, check if there's at least one development goal
            $has_entries = false;
            
            if (isset($form_data['idp_goals']) && is_array($form_data['idp_goals'])) {
                foreach ($form_data['idp_goals'] as $entry) {
                    if (!empty($entry['objective']) && !empty($entry['action_plan'])) {
                        $has_entries = true;
                        break;
                    }
                }
            }
            
            if (!$has_entries && isset($form_data['professional_development']) && is_array($form_data['professional_development'])) {
                foreach ($form_data['professional_development'] as $entry) {
                    if (!empty($entry['goals']) && !empty($entry['actions'])) {
                        $has_entries = true;
                        break;
                    }
                }
            }
            
            if (!$has_entries && isset($form_data['personal_development']) && is_array($form_data['personal_development'])) {
                foreach ($form_data['personal_development'] as $entry) {
                    if (!empty($entry['goals']) && !empty($entry['actions'])) {
                        $has_entries = true;
                        break;
                    }
                }
            }
            
            return $has_entries;
            
        default:
            return false;
    }
}

// Function to route form to department head
function routeFormToDepartmentHead($conn, $record_id, $user_id) {
    // Get the user's department head
    $query = "SELECT d.head_id, u.name as head_name, u.email as head_email 
              FROM users e 
              JOIN departments d ON e.department_id = d.id 
              LEFT JOIN users u ON d.head_id = u.id 
              WHERE e.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return [
            'success' => false,
            'message' => 'Could not find department head information'
        ];
    }
    
    $dept_info = $result->fetch_assoc();
    
    if (!$dept_info['head_id']) {
        return [
            'success' => false,
            'message' => 'Department has no assigned head'
        ];
    }
    
    // Create notification for department head
    $notification_query = "INSERT INTO notifications (user_id, message, link, is_read) 
                          VALUES (?, ?, ?, 0)";
    
    $message = "New form submission requires your review";
    $link = "view_record.php?id=" . $record_id;
    
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("iss", $dept_info['head_id'], $message, $link);
    $stmt->execute();
    
    // Update record status to Pending
    $update_query = "UPDATE records SET document_status = 'Pending', date_submitted = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    
    // Return success with department head info
    return [
        'success' => true,
        'message' => 'Form successfully routed to ' . $dept_info['head_name'] . ' For Review',
        'head_id' => $dept_info['head_id'],
        'head_name' => $dept_info['head_name'],
        'head_email' => $dept_info['head_email']
    ];
}

// Function to submit form and process workflow
function submitForm($conn, $user_id, $form_type, $period, $content) {
    
    // --- START: Semi-Annual Submission Limit Check ---
    // This check prevents a user from submitting more than one of the same form type per half-year.
    $current_year = date('Y');
    $current_month = date('n');

    if ($current_month <= 6) {
        // First half of the year (January-June)
        $start_month = 1;
        $end_month = 6;
        $period_name = "first";
    } else {
        // Second half of the year (July-December)
        $start_month = 7;
        $end_month = 12;
        $period_name = "second";
    }

    $check_query = "SELECT COUNT(*) as submission_count 
                    FROM records 
                    WHERE user_id = ? 
                      AND form_type = ? 
                      AND document_status NOT IN ('Draft', 'For Revision')
                      AND YEAR(date_submitted) = ?
                      AND MONTH(date_submitted) BETWEEN ? AND ?";
                      
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("isiii", $user_id, $form_type, $current_year, $start_month, $end_month);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $row = $result->fetch_assoc();
    $submission_count = (int)$row['submission_count'];
    $stmt_check->close();

    if ($submission_count > 0) {
        return [
            'success' => false,
            'message' => "You have already submitted a {$form_type} for the {$period_name} half of {$current_year}. You are limited to one submission per semi-annual period."
        ];
    }
    // --- END: Semi-Annual Submission Limit Check ---

    try {
        // Decode content if it's a JSON string
        if (is_string($content)) {
            $content_data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Invalid form data: ' . json_last_error_msg()
                ];
            }
        } else {
            $content_data = $content;
        }
        
        // Validate form
        if (!isFormCompleteAndRated($content_data, $form_type)) {
            return [
                'success' => false,
                'message' => 'Form is incomplete or missing required ratings'
            ];
        }
        
        // Insert new record
        $conn->begin_transaction();
        
        $insert_query = "INSERT INTO records (user_id, form_type, period, content, document_status) 
                        VALUES (?, ?, ?, ?, 'Draft')";
        
        $content_json = is_string($content) ? $content : json_encode($content);
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isss", $user_id, $form_type, $period, $content_json);
        $stmt->execute();
        
        $record_id = $conn->insert_id;
        
        // Route the form to department head
        $route_result = routeFormToDepartmentHead($conn, $record_id, $user_id);
        
        if (!$route_result['success']) {
            // Rollback if routing fails
            $conn->rollback();
            return $route_result;
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => $route_result['message'],
            'record_id' => $record_id
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        
        return [
            'success' => false,
            'message' => 'Error submitting form: ' . $e->getMessage()
        ];
    }
}

// Function to approve form with ratings (used by department head)
function approveForm($conn, $record_id, $reviewer_id, $ratings, $feedback = '', $remarks = '') {
    try {
        $conn->begin_transaction();
        
        // Get existing record
        $query = "SELECT * FROM records WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Record not found'
            ];
        }
        
        $record = $result->fetch_assoc();
        
        // Check if ratings is a string (JSON) and decode if needed
        if (is_string($ratings)) {
            $ratings_data = json_decode($ratings, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If not valid JSON, assume it's already an array
                $ratings_data = $ratings;
            }
        } else {
            $ratings_data = $ratings;
        }
        
        // Prepare content to save
        $content_json = json_encode($ratings_data);
        
        // Update record
        $update_query = "UPDATE records SET 
                        content = ?, 
                        document_status = 'Approved', 
                        reviewed_by = ?, 
                        date_reviewed = NOW(),
                        feedback = ?,
                        confidential_remarks = ?
                        WHERE id = ?";
                        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sissi", $content_json, $reviewer_id, $feedback, $remarks, $record_id);
        $stmt->execute();
        
        // Notify the record owner
        $notification_query = "INSERT INTO notifications (user_id, message, link, is_read) 
                              VALUES (?, ?, ?, 0)";
        
        $message = "Your " . $record['form_type'] . " for " . $record['period'] . " has been APPROVED";
        $link = "view_record.php?id=" . $record_id;
        
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("iss", $record['user_id'], $message, $link);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => $record['form_type'] . ' has been approved successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        
        return [
            'success' => false,
            'message' => 'Error approving form: ' . $e->getMessage()
        ];
    }
}

// Function to reject form with comments (used by department head)
function rejectForm($conn, $record_id, $reviewer_id, $feedback, $remarks = '') {
    try {
        $conn->begin_transaction();
        
        // Get existing record
        $query = "SELECT * FROM records WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'success' => false,
                'message' => 'Record not found'
            ];
        }
        
        $record = $result->fetch_assoc();
        
        // Update record
        $update_query = "UPDATE records SET 
                        document_status = 'For Revision', 
                        reviewed_by = ?, 
                        date_reviewed = NOW(),
                        feedback = ?,
                        confidential_remarks = ?
                        WHERE id = ?";
                        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("issi", $reviewer_id, $feedback, $remarks, $record_id);
        $stmt->execute();
        
        // Notify the record owner
        $notification_query = "INSERT INTO notifications (user_id, message, link, is_read) 
                              VALUES (?, ?, ?, 0)";
        
        $message = "Your " . $record['form_type'] . " for " . $record['period'] . " has been For Revision";
        $link = "view_record.php?id=" . $record_id;
        
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("iss", $record['user_id'], $message, $link);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => $record['form_type'] . ' has been For Revision'
        ];
        
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->connect_errno === 0) {
            $conn->rollback();
        }
        
        return [
            'success' => false,
            'message' => 'Error rejecting form: ' . $e->getMessage()
        ];
    }
}
?> 