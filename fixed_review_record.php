<?php
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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_record'])) {
        // Get all supervisor ratings and comments
        $ratings = [];
        
        if ($record['form_type'] === 'IPCR') {
            // Process strategic functions
            if (isset($_POST['strategic_supervisor_q']) && is_array($_POST['strategic_supervisor_q'])) {
                foreach ($_POST['strategic_supervisor_q'] as $index => $q_value) {
                    if (isset($content['strategic_functions'][$index])) {
                        $content['strategic_functions'][$index]['supervisor_q'] = $_POST['strategic_supervisor_q'][$index];
                        $content['strategic_functions'][$index]['supervisor_e'] = $_POST['strategic_supervisor_e'][$index];
                        $content['strategic_functions'][$index]['supervisor_t'] = $_POST['strategic_supervisor_t'][$index];
                        $content['strategic_functions'][$index]['supervisor_a'] = $_POST['strategic_supervisor_a'][$index];
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
                        $content['core_functions'][$index]['supervisor_a'] = $_POST['core_supervisor_a'][$index];
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
                        $content['support_functions'][$index]['supervisor_a'] = $_POST['support_supervisor_a'][$index];
                    }
                }
            }
            
            // Set final supervisor rating
            $content['supervisor_strategic_average'] = $_POST['supervisor_strategic_average'] ?? '';
            $content['supervisor_core_average'] = $_POST['supervisor_core_average'] ?? '';
            $content['supervisor_support_average'] = $_POST['supervisor_support_average'] ?? '';
            $content['supervisor_final_rating'] = $_POST['supervisor_final_rating'] ?? '';
            $content['supervisor_rating_interpretation'] = $_POST['supervisor_rating_interpretation'] ?? '';
        }
        
        // Get comments
        $comments = $_POST['comments'] ?? '';
        
        // Use the approveForm function from form_workflow.php
        $result = approveForm($conn, $record_id, $user_id, $content, $comments);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header("Location: view_record.php?id=" . $record_id);
            exit();
        } else {
            $error_message = $result['message'];
        }
    } else if (isset($_POST['reject_record'])) {
        // Get rejection comments
        $comments = $_POST['comments'] ?? '';
        
        // Use the rejectForm function from form_workflow.php
        $result = rejectForm($conn, $record_id, $user_id, $comments);
        
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
        <div class="col-md-8">
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
                    
                    <?php if ($record['form_type'] === 'IPCR' && !empty($content)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2" class="align-middle">Major Final Output</th>
                                        <th rowspan="2" class="align-middle">Success Indicators</th>
                                        <th rowspan="2" class="align-middle">Accomplishments</th>
                                        <th colspan="4" class="text-center">Self-Rating</th>
                                        <th colspan="4" class="text-center">Supervisor Rating</th>
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
                                                   data-rating="q">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="strategic_supervisor_e[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="strategic" 
                                                   data-rating="e">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="strategic_supervisor_t[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="strategic" 
                                                   data-rating="t">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm supervisor-average" 
                                                   name="strategic_supervisor_a[<?php echo $index; ?>]" readonly
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="strategic">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Core Functions -->
                                    <tr>
                                        <td colspan="11" class="bg-light fw-bold">Core Functions (45%)</td>
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
                                                   data-rating="q">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="core_supervisor_e[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="core" 
                                                   data-rating="e">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                   name="core_supervisor_t[<?php echo $index; ?>]" 
                                                   min="1" max="5" step="1" required
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="core" 
                                                   data-rating="t">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm supervisor-average" 
                                                   name="core_supervisor_a[<?php echo $index; ?>]" readonly
                                                   data-index="<?php echo $index; ?>" 
                                                   data-type="core">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <!-- Support Functions -->
                                    <?php if (!empty($support_functions)): ?>
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
                                                    data-rating="q">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                    name="support_supervisor_e[<?php echo $index; ?>]" 
                                                    min="1" max="5" step="1" required
                                                    data-index="<?php echo $index; ?>" 
                                                    data-type="support" 
                                                    data-rating="e">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm supervisor-rating" 
                                                    name="support_supervisor_t[<?php echo $index; ?>]" 
                                                    min="1" max="5" step="1" required
                                                    data-index="<?php echo $index; ?>" 
                                                    data-type="support" 
                                                    data-rating="t">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm supervisor-average" 
                                                    name="support_supervisor_a[<?php echo $index; ?>]" readonly
                                                    data-index="<?php echo $index; ?>" 
                                                    data-type="support">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
        
        <div class="col-md-4">
            <!-- Review Form -->
            <div class="card mb-4 sticky-top" style="top: 1rem;">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Submit Review</h5>
                </div>
                <div class="card-body">
                    <?php if ($record['document_status'] === 'Pending'): ?>
                        <form action="review_record.php?id=<?php echo $record_id; ?>" method="POST">
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
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send-fill me-2"></i> Submit Review
                                </button>
                            </div>
                        </form>
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
    document.addEventListener('DOMContentLoaded', function() {
        // For approve/reject buttons
        const approveRadio = document.getElementById('statusApprove');
        const rejectRadio = document.getElementById('statusReject');
        const approveLabel = document.querySelector('label[for="statusApprove"]');
        const rejectLabel = document.querySelector('label[for="statusReject"]');
        
        if (approveRadio && rejectRadio) {
            approveRadio.addEventListener('change', function() {
                if (this.checked) {
                    approveLabel.classList.remove('btn-outline-success');
                    approveLabel.classList.add('btn-success');
                    rejectLabel.classList.remove('btn-danger');
                    rejectLabel.classList.add('btn-outline-danger');
                }
            });
            
            rejectRadio.addEventListener('change', function() {
                if (this.checked) {
                    rejectLabel.classList.remove('btn-outline-danger');
                    rejectLabel.classList.add('btn-danger');
                    approveLabel.classList.remove('btn-success');
                    approveLabel.classList.add('btn-outline-success');
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once('includes/footer.php');
?> 