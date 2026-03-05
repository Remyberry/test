<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

// Set page title dynamically based on form type
$page_title = "Review " . (isset($_GET['form_type']) ? $_GET['form_type'] : "Record") . " - EPMS";

// Include header
include_once('includes/header.php');

// Include form workflow functions
include_once('includes/form_workflow.php');

// Check if user has the right role to access this page
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'department_head' && $_SESSION['user_role'] !== 'president' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid record ID";
    header("Location: records.php");
    exit();
}

$record_id = intval($_GET['id']);

// Database connection
require_once 'includes/db_connect.php';

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$department_id = $_SESSION['user_department_id'] ?? null;

// Get record information
$record_query = "SELECT r.*, u.name as employee_name, u.department_id, d.name as department_name
                 FROM records r
                 JOIN users u ON r.user_id = u.id
                 JOIN departments d ON u.department_id = d.id
                 WHERE r.id = ?";
$stmt = $conn->prepare($record_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record_result = $stmt->get_result();

// Check if record exists
if ($record_result->num_rows === 0) {
    $_SESSION['error_message'] = "Record not found";
    header("Location: records.php");
    exit();
}

$record = $record_result->fetch_assoc();

// Check if user has permission to review this record
$can_review = ($user_role == 'department_head' && $record['department_id'] == $department_id && $record['user_id'] != $user_id) || 
              ($user_role == 'president') ||
              ($user_role == 'admin');

if (!$can_review) {
    $_SESSION['error_message'] = "You don't have permission to review this record";
    header("Location: view_record.php?id=" . $record_id);
    exit();
}

// Check if record is pending For Review
if ($record['document_status'] !== 'Pending') {
    $_SESSION['error_message'] = "This record is not pending For Review";
    header("Location: view_record.php?id=" . $record_id);
    exit();
}

// Parse record content
$content = json_decode($record['content'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $_SESSION['error_message'] = "Error parsing record data: " . json_last_error_msg();
    header("Location: view_record.php?id=" . $record_id);
    exit();
}

// Create default entries if content is empty
if (empty($strategic_functions) && empty($core_functions) && empty($support_functions)) {
    // Check if there are specific functions in the JSON content
    if (!empty($content['strategic_functions'])) {
        $strategic_functions = $content['strategic_functions'];
    } else {
        // Add a default entry to allow rating
        $strategic_functions = [
            [
                'mfo' => 'No strategic outputs defined',
                'success_indicators' => '',
                'accomplishments' => '',
                'q' => '',
                'e' => '',
                't' => '',
                'a' => ''
            ]
        ];
    }
    
    if (!empty($content['core_functions'])) {
        $core_functions = $content['core_functions'];
    } else {
        // Add a default entry to allow rating
        $core_functions = [
            [
                'mfo' => 'No core outputs defined',
                'success_indicators' => '',
                'accomplishments' => '',
                'q' => '',
                'e' => '',
                't' => '',
                'a' => ''
            ]
        ];
    }
    
    if (!empty($content['support_functions'])) {
        $support_functions = $content['support_functions'];
    } else {
        // Add a default entry to allow rating
        $support_functions = [
            [
                'mfo' => 'Technology Workshop',
                'success_indicators' => 'Conduct 2 workshops for students',
                'accomplishments' => 'Conducted 1 workshop with 30 attendees',
                'q' => '',
                'e' => '',
                't' => '',
                'a' => ''
            ]
        ];
    }
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['status'])) {
        $review_status = $_POST['status'];
        $feedback = trim($_POST['feedback'] ?? ''); // Trim to correctly check for empty feedback
        $c_remarks = $_POST['remarks'] ?? '';

        // If "Approved" is clicked but feedback is given, treat as "For Revision" to allow user edits
        if ($review_status === 'Approved' && !empty($feedback)) {
            // This is the "Approve with comments" case, so we use the reject workflow to send it back.
            $result = rejectForm($conn, $record_id, $user_id, $feedback, $c_remarks);

        } else if ($review_status === 'Approved') { // This is a final, non-editable approval
            // Process ratings for IPCR form
            if ($record['form_type'] === 'IPCR') {
                // Process strategic functions
                if (isset($_POST['strategic_supervisor_q']) && is_array($_POST['strategic_supervisor_q'])) {
                    foreach ($_POST['strategic_supervisor_q'] as $index => $q_value) {
                        if (isset($content['strategic_functions'][$index])) {
                            $content['strategic_functions'][$index]['supervisor_q'] = $_POST['strategic_supervisor_q'][$index];
                            $content['strategic_functions'][$index]['supervisor_e'] = $_POST['strategic_supervisor_e'][$index];
                            $content['strategic_functions'][$index]['supervisor_t'] = $_POST['strategic_supervisor_t'][$index];
                            
                            // Save the remarks for this row
                            if (isset($_POST['strategic_remarks'][$index])) {
                                $content['strategic_functions'][$index]['remarks'] = $_POST['strategic_remarks'][$index];
                            }
                            
                            // Calculate average
                            $avg = ($content['strategic_functions'][$index]['supervisor_q'] + 
                                   $content['strategic_functions'][$index]['supervisor_e'] + 
                                   $content['strategic_functions'][$index]['supervisor_t']) / 3;
                            
                            $content['strategic_functions'][$index]['supervisor_a'] = number_format($avg, 2);
                        }
                    }
                }
                
                // Process core functions
                if (isset($_POST['core_supervisor_q']) && is_array($_POST['core_supervisor_q'])) {
                    foreach ($_POST['core_supervisor_q'] as $index => $q_value) {
                        if (isset($content['core_functions'][$index])) {
                            $content['core_functions'][$index]['supervisor_q'] = $_POST['core_supervisor_q'][$index];
                            $content['core_functions'][$index]['supervisor_e'] = $_POST['core_supervisor_e'][$index];
                            $content['core_functions'][$index]['supervisor_t'] = $_POST['core_supervisor_t'][$index];
                            
                            // Save the remarks for this row
                            if (isset($_POST['core_remarks'][$index])) {
                                $content['core_functions'][$index]['remarks'] = $_POST['core_remarks'][$index];
                            }
                            
                            // Calculate average
                            $avg = ($content['core_functions'][$index]['supervisor_q'] + 
                                   $content['core_functions'][$index]['supervisor_e'] + 
                                   $content['core_functions'][$index]['supervisor_t']) / 3;
                            
                            $content['core_functions'][$index]['supervisor_a'] = number_format($avg, 2);
                        }
                    }
                }
                
                // Process support functions
                if (isset($_POST['support_supervisor_q']) && is_array($_POST['support_supervisor_q'])) {
                    foreach ($_POST['support_supervisor_q'] as $index => $q_value) {
                        if (isset($content['support_functions'][$index])) {
                            $content['support_functions'][$index]['supervisor_q'] = $_POST['support_supervisor_q'][$index];
                            $content['support_functions'][$index]['supervisor_e'] = $_POST['support_supervisor_e'][$index];
                            $content['support_functions'][$index]['supervisor_t'] = $_POST['support_supervisor_t'][$index];
                            
                            // Save the remarks for this row
                            if (isset($_POST['support_remarks'][$index])) {
                                $content['support_functions'][$index]['remarks'] = $_POST['support_remarks'][$index];
                            }
                            
                            // Calculate average
                            $avg = ($content['support_functions'][$index]['supervisor_q'] + 
                                   $content['support_functions'][$index]['supervisor_e'] + 
                                   $content['support_functions'][$index]['supervisor_t']) / 3;
                            
                            $content['support_functions'][$index]['supervisor_a'] = number_format($avg, 2);
                        }
                    }
                }
                
                // Calculate final ratings
                $strategic_total = 0;
                $strategic_count = count($content['strategic_functions']);
                foreach ($content['strategic_functions'] as $func) {
                    $strategic_total += floatval($func['supervisor_a'] ?? 0);
                }
                $strategic_average = $strategic_count > 0 ? $strategic_total / $strategic_count : 0;
                $content['supervisor_strategic_average'] = number_format($strategic_average, 2);
                
                $core_total = 0;
                $core_count = count($content['core_functions']);
                foreach ($content['core_functions'] as $func) {
                    $core_total += floatval($func['supervisor_a'] ?? 0);
                }
                $core_average = $core_count > 0 ? $core_total / $core_count : 0;
                $content['supervisor_core_average'] = number_format($core_average, 2);
                
                $support_total = 0;
                $support_count = count($content['support_functions'] ?? []);
                foreach ($content['support_functions'] ?? [] as $func) {
                    $support_total += floatval($func['supervisor_a'] ?? 0);
                }
                $support_average = $support_count > 0 ? $support_total / $support_count : 0;
                $content['supervisor_support_average'] = number_format($support_average, 2);
                
                // Get the computation type from the original submission
                $computation_type = $content['computation_type'] ?? 'Type1';

                // Calculate weighted final rating based on the record's computation type
                $weighted_rating = 0;
                if ($computation_type === 'Type1') {
                    // Type1: Strategic (45%) and Core (55%)
                    $weighted_rating = ($strategic_average * 0.45) + ($core_average * 0.55);
                } else { // Type2
                    // Type2: Strategic (45%), Core (45%), and Support (10%)
                    $weighted_rating = ($strategic_average * 0.45) + ($core_average * 0.45) + ($support_average * 0.10);
                }
                $content['supervisor_final_rating'] = number_format($weighted_rating, 2);
                
                // Determine rating interpretation
                $rating_interpretation = '';
                if ($weighted_rating >= 4.5) {
                    $rating_interpretation = 'Outstanding';
                } else if ($weighted_rating >= 3.5) {
                    $rating_interpretation = 'Very Satisfactory';
                } else if ($weighted_rating >= 2.5) {
                    $rating_interpretation = 'Satisfactory';
                } else if ($weighted_rating >= 1.5) {
                    $rating_interpretation = 'Unsatisfactory';
                } else {
                    $rating_interpretation = 'Poor';
                }
                $content['supervisor_rating_interpretation'] = $rating_interpretation;
            }
            
            // Use the approveForm function from form_workflow.php
            $result = approveForm($conn, $record_id, $user_id, $content, $feedback, $c_remarks);
        
        } else { // Status was 'For Revision'
            // Use the rejectForm function from form_workflow.php
            $result = rejectForm($conn, $record_id, $user_id, $feedback, $c_remarks);
        }
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header("Location: view_record.php?id=" . $record_id);
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

// Prepare data for display based on form type
$strategic_functions = $content['strategic_functions'] ?? [];
$core_functions = $content['core_functions'] ?? [];
$support_functions = $content['support_functions'] ?? [];

// Define back link based on form type
$back_link = "staff_ipcr.php";
if ($record['form_type'] === 'IDP') {
    $back_link = "staff_idp.php";
} else if ($record['form_type'] === 'DPCR') {
    $back_link = "department_dpcr.php";
}
?>
<style>
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Review <?php echo $record['form_type']; ?></h1>
        <div>
            <a href="<?php echo $back_link; ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Staff <?php echo $record['form_type']; ?>
            </a>
            <button class="btn btn-sm btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Form
            </button>
        </div>
    </div>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-9">
            <!-- Record Content based on form type -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <?php if ($record['form_type'] === 'IPCR'): ?>
                        <h5 class="mb-0">Individual Performance Commitment and Review</h5>
                    <?php elseif ($record['form_type'] === 'IDP'): ?>
                        <h5 class="mb-0">Individual Development Plan</h5>
                    <?php else: ?>
                        <h5 class="mb-0">Department Performance Commitment and Review</h5>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Employee:</strong> <?php echo htmlspecialchars($record['employee_name']); ?></p>
                            <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($record['department_name']); ?></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1"><strong>Period:</strong> <?php echo htmlspecialchars($record['period']); ?></p>
                            <p class="mb-1"><strong>Submitted:</strong> <?php echo date('F d, Y', strtotime($record['date_submitted'])); ?></p>
                        </div>
                    </div>
                    
                    <form action="review_record.php?id=<?php echo $record_id; ?>" method="POST" id="reviewForm">
                    <?php if ($record['form_type'] === 'IPCR' && !empty($content)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2" class="align-middle col-sm-2">Major Final Output</th>
                                        <th rowspan="2" class="align-middle col-sm-2">Success Indicators</th>
                                        <th rowspan="2" class="align-middle col-sm-2">Accomplishments</th>
                                        <th colspan="4" class="text-center">Self-Rating</th>
                                        <th colspan="4" class="text-center">Supervisor Rating</th>
                                        <th rowspan="2" class="align-middle col-sm-1.5">Remarks</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center">Q</th>
                                        <th class="text-center">E</th>
                                        <th class="text-center">T</th>
                                        <th class="text-center">A</th>
                                        <th class="text-center">Q</th>
                                        <th class="text-center">E</th>
                                        <th class="text-center">T</th>
                                        <th class="text-center">A</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Strategic Functions -->
                                    <tr>
                                        <td colspan="11" class="bg-light fw-bold">Strategic Functions (45%)</td>
                                    </tr>
                                    <?php foreach ($strategic_functions as $index => $function): ?>
                                    <tr class="strategic-function-row">
                                        <td><?php echo htmlspecialchars($function['mfo'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($function['success_indicators'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($function['accomplishments'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['q'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['e'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['t'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['a'] ?? ''); ?></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="strategic_supervisor_q[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="strategic" 
                                                   data-rating="q"
                                                   value="<?php echo isset($function['supervisor_q']) ? htmlspecialchars($function['supervisor_q']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_q']) ? htmlspecialchars($function['supervisor_q']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="strategic_supervisor_e[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="strategic" 
                                                   data-rating="e"
                                                   value="<?php echo isset($function['supervisor_e']) ? htmlspecialchars($function['supervisor_e']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_e']) ? htmlspecialchars($function['supervisor_e']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="strategic_supervisor_t[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="strategic" 
                                                   data-rating="t"
                                                   value="<?php echo isset($function['supervisor_t']) ? htmlspecialchars($function['supervisor_t']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_t']) ? htmlspecialchars($function['supervisor_t']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm supervisor-average" 
                                                   name="strategic_supervisor_a[<?php echo $index; ?>]" readonly
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="strategic"
                                                   value="<?php echo isset($function['supervisor_a']) ? htmlspecialchars($function['supervisor_a']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="strategic_remarks[<?php echo $index; ?>]"
                                                   placeholder="Add remarks"
                                                   value="<?php echo isset($function['remarks']) ? htmlspecialchars($function['remarks']) : ''; ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr>
                                        <td colspan="11" class="bg-light fw-bold">Core Functions (<?php echo ($content['computation_type'] ?? 'Type1') === 'Type1' ? '55%' : '45%'; ?>)</td>
                                    </tr>
                                    <?php foreach ($core_functions as $index => $function): ?>
                                    <tr class="core-function-row">
                                        <td><?php echo htmlspecialchars($function['mfo'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($function['success_indicators'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($function['accomplishments'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['q'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['e'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['t'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['a'] ?? ''); ?></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="core_supervisor_q[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="core" 
                                                   data-rating="q"
                                                   value="<?php echo isset($function['supervisor_q']) ? htmlspecialchars($function['supervisor_q']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_q']) ? htmlspecialchars($function['supervisor_q']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="core_supervisor_e[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="core" 
                                                   data-rating="e"
                                                   value="<?php echo isset($function['supervisor_e']) ? htmlspecialchars($function['supervisor_e']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_e']) ? htmlspecialchars($function['supervisor_e']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="core_supervisor_t[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="core" 
                                                   data-rating="t"
                                                   value="<?php echo isset($function['supervisor_t']) ? htmlspecialchars($function['supervisor_t']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_t']) ? htmlspecialchars($function['supervisor_t']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm supervisor-average" 
                                                   name="core_supervisor_a[<?php echo $index; ?>]" readonly
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="core"
                                                   value="<?php echo isset($function['supervisor_a']) ? htmlspecialchars($function['supervisor_a']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="core_remarks[<?php echo $index; ?>]"
                                                   placeholder="Add remarks"
                                                   value="<?php echo isset($function['remarks']) ? htmlspecialchars($function['remarks']) : ''; ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Support Functions -->
                                    <?php if (!empty($support_functions) && ($content['computation_type'] ?? 'Type1') === 'Type2'): ?>
                                    <tr>
                                        <td colspan="11" class="bg-light fw-bold">Support Functions (10%)</td>
                                    </tr>
                                    <?php foreach ($support_functions as $index => $function): ?>
                                    <tr class="support-function-row">
                                        <td><?php echo htmlspecialchars($function['mfo'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($function['success_indicators'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($function['accomplishments'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['q'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['e'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['t'] ?? ''); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($function['a'] ?? ''); ?></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="support_supervisor_q[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="support" 
                                                   data-rating="q"
                                                   value="<?php echo isset($function['supervisor_q']) ? htmlspecialchars($function['supervisor_q']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_q']) ? htmlspecialchars($function['supervisor_q']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="support_supervisor_e[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="support" 
                                                   data-rating="e"
                                                   value="<?php echo isset($function['supervisor_e']) ? htmlspecialchars($function['supervisor_e']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_e']) ? htmlspecialchars($function['supervisor_e']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="support_supervisor_t[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="support" 
                                                   data-rating="t"
                                                   value="<?php echo isset($function['supervisor_t']) ? htmlspecialchars($function['supervisor_t']) : ''; ?>"
                                                   data-existing-value="<?php echo isset($function['supervisor_t']) ? htmlspecialchars($function['supervisor_t']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm supervisor-average" 
                                                   name="support_supervisor_a[<?php echo $index; ?>]" readonly
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="support"
                                                   value="<?php echo isset($function['supervisor_a']) ? htmlspecialchars($function['supervisor_a']) : ''; ?>">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="support_remarks[<?php echo $index; ?>]"
                                                   placeholder="Add remarks"
                                                   value="<?php echo isset($function['remarks']) ? htmlspecialchars($function['remarks']) : ''; ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Summary Ratings Section -->
                        <div class="card mt-3 mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Summary Ratings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Weighted Strategic Average</label>
                                            <input type="text" class="form-control" name="supervisor_strategic_average" id="supervisor_strategic_average" readonly
                                                  value="<?php echo isset($content['supervisor_strategic_average']) ? htmlspecialchars($content['supervisor_strategic_average']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Weighted Core Average</label>
                                            <input type="text" class="form-control" name="supervisor_core_average" id="supervisor_core_average" readonly
                                                  value="<?php echo isset($content['supervisor_core_average']) ? htmlspecialchars($content['supervisor_core_average']) : ''; ?>">
                                        </div>
                                    </div>
                                    <?php if (($content['computation_type'] ?? 'Type1') === 'Type2'): ?>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Weighted Support Average</label>
                                            <input type="text" class="form-control" name="supervisor_support_average" id="supervisor_support_average" readonly
                                                  value="<?php echo isset($content['supervisor_support_average']) ? htmlspecialchars($content['supervisor_support_average']) : ''; ?>">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Final Rating</label>
                                            <input type="text" class="form-control" name="supervisor_final_rating" id="supervisor_final_rating" readonly
                                                  value="<?php echo isset($content['supervisor_final_rating']) ? htmlspecialchars($content['supervisor_final_rating']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Rating Interpretation</label>
                                            <input type="text" class="form-control" name="supervisor_rating_interpretation" id="supervisor_rating_interpretation" readonly
                                                  value="<?php echo isset($content['supervisor_rating_interpretation']) ? htmlspecialchars($content['supervisor_rating_interpretation']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($record['form_type'] === 'IDP' && !empty($content)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Development Goals</th>
                                        <th width="20%">Required Competencies</th>
                                        <th width="20%">Action Plan / Activities</th>
                                        <th width="15%">Timeline</th>
                                        <th width="10%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Professional Development Section -->
                                    <?php if (isset($content['professional_development']) && is_array($content['professional_development']) && !empty($content['professional_development'])): ?>
                                        <tr>
                                            <td colspan="5" class="text-center bg-light fw-bold">PROFESSIONAL DEVELOPMENT</td>
                                        </tr>
                                        <?php foreach ($content['professional_development'] as $item): ?>
                                            <tr>
                                                <td><?php echo nl2br(htmlspecialchars($item['goals'] ?? '')); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($item['competencies'] ?? '')); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($item['actions'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($item['timeline'] ?? 'Not specified'); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $item['status'] ?? 'Not Started';
                                                    $status_class = "";
                                                    switch ($status) {
                                                        case 'Not Started':
                                                            $status_class = "secondary";
                                                            break;
                                                        case 'In Progress':
                                                            $status_class = "warning";
                                                            break;
                                                        case 'Completed':
                                                            $status_class = "success";
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Personal Development Section -->
                                    <?php if (isset($content['personal_development']) && is_array($content['personal_development']) && !empty($content['personal_development'])): ?>
                                        <tr>
                                            <td colspan="5" class="text-center bg-light fw-bold">PERSONAL DEVELOPMENT</td>
                                        </tr>
                                        <?php foreach ($content['personal_development'] as $item): ?>
                                            <tr>
                                                <td><?php echo nl2br(htmlspecialchars($item['goals'] ?? '')); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($item['competencies'] ?? '')); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($item['actions'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($item['timeline'] ?? 'Not specified'); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $item['status'] ?? 'Not Started';
                                                    $status_class = "";
                                                    switch ($status) {
                                                        case 'Not Started':
                                                            $status_class = "secondary";
                                                            break;
                                                        case 'In Progress':
                                                            $status_class = "warning";
                                                            break;
                                                        case 'Completed':
                                                            $status_class = "success";
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Career Advancement Section -->
                                    <?php if (isset($content['career_advancement']) && is_array($content['career_advancement']) && !empty($content['career_advancement'])): ?>
                                        <tr>
                                            <td colspan="5" class="text-center bg-light fw-bold">CAREER ADVANCEMENT</td>
                                        </tr>
                                        <?php foreach ($content['career_advancement'] as $item): ?>
                                            <tr>
                                                <td><?php echo nl2br(htmlspecialchars($item['goals'] ?? '')); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($item['competencies'] ?? '')); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($item['actions'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars($item['timeline'] ?? 'Not specified'); ?></td>
                                                <td>
                                                    <?php 
                                                    $status = $item['status'] ?? 'Not Started';
                                                    $status_class = "";
                                                    switch ($status) {
                                                        case 'Not Started':
                                                            $status_class = "secondary";
                                                            break;
                                                        case 'In Progress':
                                                            $status_class = "warning";
                                                            break;
                                                        case 'Completed':
                                                            $status_class = "success";
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            No content found for this <?php echo $record['form_type']; ?> record.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <!-- Review Form -->
            <div class="card mb-4 sticky-top" style="top: 1rem;">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Submit Review</h5>
                </div>
                <div class="card-body">
                    <?php if ($record['document_status'] === 'Pending'): ?>
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <span>Please review the <?php echo $record['form_type']; ?> submission and provide your decision below.</span>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Review Decision</label>
                            <div class="d-flex gap-2">
                                <div class="form-check form-check-inline flex-grow-1">
                                    <input class="form-check-input" type="radio" name="status" id="statusApprove" value="Approved" required>
                                    <label class="form-check-label btn btn-outline-success w-100 mb-0 d-flex align-items-center justify-content-center" for="statusApprove">
                                        <i class="bi bi-check-circle-fill me-2"></i> Approve
                                    </label>
                                </div>
                                <div class="form-check form-check-inline flex-grow-1">
                                    <input class="form-check-input" type="radio" name="status" id="statusReject" value="For Revision" required>
                                    <label class="form-check-label btn btn-outline-danger w-100 mb-0 d-flex align-items-center justify-content-center" for="statusReject">
                                        <i class="bi bi-x-circle-fill me-2"></i> Reject
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="feedback" class="form-label fw-bold">Feedback</label>
                            <textarea class="form-control" name="feedback" id="feedback" rows="5" placeholder="Provide feedback to the employee..."></textarea>
                            <div class="form-text">
                                This feedback will be visible to the employee and will help them understand your decision.
                                Be constructive and specific with your comments.
                            </div>
                        </div>
                        
                        <!-- Add Remarks field (Only visible to department heads and not shown to regular employees) -->
                        <div class="mb-3">
                            <label for="remarks" class="form-label fw-bold">Remarks <span class="badge bg-info">Department Head Only</span></label>
                            <textarea class="form-control" name="remarks" id="remarks" rows="4" placeholder="Add confidential remarks (not visible to employees)..."></textarea>
                            <div class="form-text text-danger">
                                <i class="bi bi-lock-fill me-1"></i> These remarks will only be visible to department heads and administrators.
                                Regular employees will not be able to see this content.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="submitReviewBtn">
                                <i class="bi bi-send-fill me-2"></i> Submit Review
                            </button>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <small>Before submitting, please ensure you've provided ratings for all criteria. The average scores will be calculated automatically.</small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-3">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="bi bi-info-circle-fill fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="alert-heading mb-1">Review Status: 
                                    <?php
                                        $status_badge_class = ($record['document_status'] === 'Approved') ? 'success' : 'danger';
                                        echo '<span class="badge bg-' . $status_badge_class . '">' . $record['document_status'] . '</span>'; 
                                    ?>
                                    </h6>
                                    <p class="mb-0 small">This record has already been reviewed on <?php echo date('F d, Y', strtotime($record['reviewed_at'])); ?>.</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($record['feedback'])): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Feedback Provided</label>
                                <div class="border rounded p-3 bg-light">
                                    <?php echo nl2br(htmlspecialchars($record['feedback'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php echo $back_link; ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-left"></i> Back to Staff <?php echo $record['form_type']; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this JavaScript to enhance the form interaction -->
<script>
    const computationType = '<?php echo $content['computation_type'] ?? 'Type1'; ?>';
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // For approve/reject buttons
        const approveRadio = document.getElementById('statusApprove');
        const rejectRadio = document.getElementById('statusReject');
        const approveLabel = document.querySelector('label[for="statusApprove"]');
        const rejectLabel = document.querySelector('label[for="statusReject"]');
        const submitBtn = document.getElementById('submitReviewBtn');
        
        if (approveRadio && rejectRadio) {
            approveRadio.addEventListener('change', function() {
                if (this.checked) {
                    approveLabel.classList.remove('btn-outline-success');
                    approveLabel.classList.add('btn-success');
                    rejectLabel.classList.remove('btn-danger');
                    rejectLabel.classList.add('btn-outline-danger');
                    
                    // When approving, we need ratings filled in
                    document.querySelectorAll('.supervisor-rating').forEach(input => {
                        input.required = true;
                    });
                }
            });
            
            rejectRadio.addEventListener('change', function() {
                if (this.checked) {
                    rejectLabel.classList.remove('btn-outline-danger');
                    rejectLabel.classList.add('btn-danger');
                    approveLabel.classList.remove('btn-success');
                    approveLabel.classList.add('btn-outline-success');
                    
                    // When rejecting, ratings aren't required
                    document.querySelectorAll('.supervisor-rating').forEach(input => {
                        input.required = false;
                    });
                }
            });
        }
        
        // Set default values for inputs without values to 1
        document.querySelectorAll('.supervisor-rating').forEach(input => {
            if (!input.value) {
                input.value = ''; // Default rating is 3 out of 5
            }
        });
        
        // For calculating rating averages
        const supervisorRatings = document.querySelectorAll('.supervisor-rating');
        
        // Function to calculate average
        function calculateAverage(index, type) {
            const q = parseFloat(document.querySelector(`input[name="${type}_supervisor_q[${index}]"]`).value) || 0;
            const e = parseFloat(document.querySelector(`input[name="${type}_supervisor_e[${index}]"]`).value) || 0;
            const t = parseFloat(document.querySelector(`input[name="${type}_supervisor_t[${index}]"]`).value) || 0;
            
            if (q > 0 && e > 0 && t > 0) {
                const avg = (q + e + t) / 3;
                document.querySelector(`input[name="${type}_supervisor_a[${index}]"]`).value = avg.toFixed(2);
                return avg;
            }
            return 0;
        }
        
        // Add event listeners to all rating inputs
        supervisorRatings.forEach(input => {
            input.addEventListener('input', function() {
                // Validate input is between 1 and 5
                let value = parseFloat(this.value);
                if (value < 1) this.value = 1;
                if (value > 5) this.value = 5;
                
                const index = this.getAttribute('data-index');
                const type = this.getAttribute('data-type');
                calculateAverage(index, type);
                
                // Recalculate section averages
                calculateSectionAverages();
            });
            
            // Trigger calculation on initial load
            const index = input.getAttribute('data-index');
            const type = input.getAttribute('data-type');
            calculateAverage(index, type);
        });
        
        // Calculate section averages and final rating
        function calculateSectionAverages() {
            // Strategic functions
            let strategicTotal = 0;
            let strategicCount = 0;
            document.querySelectorAll('.strategic-function-row').forEach(row => {
                const index = row.querySelector('.supervisor-average').getAttribute('data-index');
                const avg = parseFloat(document.querySelector(`input[name="strategic_supervisor_a[${index}]"]`).value) || 0;
                if (avg > 0) {
                    strategicTotal += avg;
                    strategicCount++;
                }
            });
            
            const strategicAvg = strategicCount > 0 ? strategicTotal / strategicCount : 0;
            
            // Core functions
            let coreTotal = 0;
            let coreCount = 0;
            document.querySelectorAll('.core-function-row').forEach(row => {
                const index = row.querySelector('.supervisor-average').getAttribute('data-index');
                const avg = parseFloat(document.querySelector(`input[name="core_supervisor_a[${index}]"]`).value) || 0;
                if (avg > 0) {
                    coreTotal += avg;
                    coreCount++;
                }
            });
            
            const coreAvg = coreCount > 0 ? coreTotal / coreCount : 0;
            
            // Support functions
            let supportTotal = 0;
            let supportCount = 0;
            document.querySelectorAll('.support-function-row').forEach(row => {
                const index = row.querySelector('.supervisor-average').getAttribute('data-index');
                const avg = parseFloat(document.querySelector(`input[name="support_supervisor_a[${index}]"]`).value) || 0;
                if (avg > 0) {
                    supportTotal += avg;
                    supportCount++;
                }
            });
            
            const supportAvg = supportCount > 0 ? supportTotal / supportCount : 0;
            
            // Calculate final weighted rating
            let finalRating = 0;
            let strategicWeighted = 0;
            let coreWeighted = 0;
            let supportWeighted = 0;

            if (computationType === 'Type1') {
                strategicWeighted = strategicAvg * 0.45;
                coreWeighted = coreAvg * 0.55;
                finalRating = strategicWeighted + coreWeighted;
            } else { // Type2
                strategicWeighted = strategicAvg * 0.45;
                coreWeighted = coreAvg * 0.45;
                supportWeighted = supportAvg * 0.10;
                finalRating = strategicWeighted + coreWeighted + supportWeighted;
            }

            // Update the input fields with the WEIGHTED values
            document.getElementById('supervisor_strategic_average').value = strategicWeighted.toFixed(2);
            document.getElementById('supervisor_core_average').value = coreWeighted.toFixed(2);
            
            const supportAverageElement = document.getElementById('supervisor_support_average');
            if (supportAverageElement) {
                supportAverageElement.value = supportWeighted.toFixed(2);
            }
            
            document.getElementById('supervisor_final_rating').value = finalRating.toFixed(2);
            
            // Set rating interpretation
            let interpretation = '';
            if (finalRating >= 4.5) {
                interpretation = 'Outstanding';
            } else if (finalRating >= 3.5) {
                interpretation = 'Very Satisfactory';
            } else if (finalRating >= 2.5) {
                interpretation = 'Satisfactory';
            } else if (finalRating >= 1.5) {
                interpretation = 'Unsatisfactory';
            } else if (finalRating > 0) {
                interpretation = 'Poor';
            }
            
            document.getElementById('supervisor_rating_interpretation').value = interpretation;
        }
        
        // Initialize averages on page load
        calculateSectionAverages();
        
        // Form validation before submission
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                if (approveRadio && approveRadio.checked) {
                    // Check that all required fields are filled
                    let hasEmptyFields = false;
                    document.querySelectorAll('.supervisor-rating[required]').forEach(input => {
                        if (!input.value) {
                            hasEmptyFields = true;
                            input.classList.add('is-invalid');
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });
                    
                    if (hasEmptyFields) {
                        e.preventDefault();
                        alert('Please fill in all rating fields before approving.');
                        return false;
                    }
                }
                return true;
            });
        }
    });
</script>

<?php
// Include footer
include_once('includes/footer.php');

// End output buffering and send output to browser
ob_end_flush();