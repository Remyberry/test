<?php
// Set page title
$page_title = "View Record - EPMS";

// Include header
include_once('includes/header.php');

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

// Get user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$department_id = $_SESSION['user_department_id'] ?? null;

// Get record data
$record_query = "SELECT r.*, u.name as employee_name, u.department_id, 
                 d.name as department_name, dh.name as reviewer_name
                 FROM records r 
                 JOIN users u ON r.user_id = u.id 
                 LEFT JOIN departments d ON u.department_id = d.id
                 LEFT JOIN users dh ON r.created_by = dh.id
                 WHERE r.id = ?";
$stmt = $conn->prepare($record_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record_result = $stmt->get_result();

if ($record_result->num_rows === 0) {
    $_SESSION['error_message'] = "Record not found!";
    header("Location: records.php");
    exit();
}

$record = $record_result->fetch_assoc();

// Check permission to view this record
$has_permission = false;

// The record owner can always view
if ($record['user_id'] == $user_id) {
    $has_permission = true;
}
// Department head can view records from their department
else if ($user_role == 'department_head' && $record['department_id'] == $department_id) {
    $has_permission = true;
}
// Admin and president can view all records
else if ($user_role == 'admin' || $user_role == 'president') {
    $has_permission = true;
}

if (!$has_permission) {
    $_SESSION['error_message'] = "You don't have permission to view this record!";
    header("Location: records.php");
    exit();
}

// Check if form is being submitted for approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    // Verify user has permission to approve/reject
    $can_review = ($user_role == 'department_head' && $record['department_id'] == $department_id && $record['user_id'] != $user_id) || 
                  ($user_role == 'president') ||
                  ($user_role == 'admin');
    
    if (!$can_review) {
        $_SESSION['error_message'] = "You don't have permission to review this record!";
        header("Location: view_record.php?id=" . $record_id);
        exit();
    }
    
    // Update record status
    $new_status = ($_POST['review_action'] === 'approve') ? 'Approved' : 'For Revision';
    $comments = trim($_POST['comments'] ?? '');
    
    $update_query = "UPDATE records SET document_status = ?, reviewed_by = ?, date_reviewed = NOW(), comments = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sisi", $new_status, $user_id, $comments, $record_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Record has been " . strtolower($new_status) . "!";
    } else {
        $_SESSION['error_message'] = "Error updating record: " . $conn->error;
    }
    
    header("Location: records.php");
    exit();
}

// Get entries based on form type
$entries = [];

if ($record['form_type'] === 'DPCR') {
    $content = json_decode($record['content'], true);
    $strategic_functions = [];
    $core_functions = [];
    $support_functions = [];

    if ($content !== null && is_array($content)) {
        $flatten_data = function($entries) {
            if (empty($entries)) return [];
            if (isset($entries[0]['indicators'])) {
                $flat = [];
                foreach ($entries as $block) {
                    $mfo = $block['major_output'] ?? '';
                    foreach ($block['indicators'] as $ind) {
                        $ind['major_output'] = $mfo;
                        $flat[] = $ind;
                    }
                }
                return $flat;
            }
            return $entries;
        };
        
        $strategic_functions = $flatten_data($content['strategic_functions'] ?? []);
        $core_functions = $flatten_data($content['core_functions'] ?? []);
        $support_functions = $flatten_data($content['support_functions'] ?? []);
        $computation_type = $content['computation_type'] ?? 'Type1';
    }
    
    $entries = [
        'strategic' => $strategic_functions,
        'core' => $core_functions,
        'support' => $support_functions,
        'computation_type' => $computation_type ?? 'Type1'
    ];
} else if ($record['form_type'] === 'IPCR') {
    // First check if there are entries in the ipcr_entries table
    $entries_query = "SELECT * FROM ipcr_entries WHERE record_id = ? ORDER BY category, id";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $strategic_entries = [];
    $core_entries = [];
    $support_entries = [];
    
    if ($entries_result->num_rows > 0) {
        // If we have structured entries, use them
        while ($entry = $entries_result->fetch_assoc()) {
            if ($entry['category'] === 'Strategic') {
                $strategic_entries[] = $entry;
            } else if ($entry['category'] === 'Core') {
                $core_entries[] = $entry;
            } else if ($entry['category'] === 'Support') {
                $support_entries[] = $entry;
            }
        }
    } else if (!empty($record['content'])) {
        // If we have JSON content, parse it
        $content = json_decode($record['content'], true);

        if (isset($content['strategic_functions']) && is_array($content['strategic_functions'])) {
            foreach ($content['strategic_functions'] as $func) {
                $strategic_entries[] = [
                    'major_output' => $func['mfo'] ?? '',
                    'success_indicators' => $func['success_indicators'] ?? '',
                    'actual_accomplishments' => $func['accomplishments'] ?? '',
                    'q_rating' => $func['q'] ?? '',
                    'e_rating' => $func['e'] ?? '',
                    't_rating' => $func['t'] ?? '',
                    'final_rating' => $func['a'] ?? '',
                    'remarks' => $func['remarks'] ?? ''
                ];
            }
        }
        
        if (isset($content['core_functions']) && is_array($content['core_functions'])) {
            foreach ($content['core_functions'] as $func) {
                $core_entries[] = [
                    'major_output' => $func['mfo'] ?? '',
                    'success_indicators' => $func['success_indicators'] ?? '',
                    'actual_accomplishments' => $func['accomplishments'] ?? '',
                    'q_rating' => $func['q'] ?? '',
                    'e_rating' => $func['e'] ?? '',
                    't_rating' => $func['t'] ?? '',
                    'final_rating' => $func['a'] ?? '',
                    'remarks' => $func['remarks'] ?? ''
                ];
            }
        }
        
        if (isset($content['support_functions']) && is_array($content['support_functions'])) {
            foreach ($content['support_functions'] as $func) {
                $support_entries[] = [
                    'major_output' => $func['mfo'] ?? '',
                    'success_indicators' => $func['success_indicators'] ?? '',
                    'actual_accomplishments' => $func['accomplishments'] ?? '',
                    'q_rating' => $func['q'] ?? '',
                    'e_rating' => $func['e'] ?? '',
                    't_rating' => $func['t'] ?? '',
                    'final_rating' => $func['a'] ?? '',
                    'remarks' => $func['remarks'] ?? ''
                ];
            }
        }
    }
    
    $entries = [
        'strategic' => $strategic_entries,
        'core' => $core_entries,
        'support' => $support_entries
    ];
} else if ($record['form_type'] === 'IDP') {
    // First check if there are entries in the idp_entries table
    $entries_query = "SELECT * FROM idp_entries WHERE record_id = ? ORDER BY id";
    $stmt = $conn->prepare($entries_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $entries_result = $stmt->get_result();
    
    $idp_entries = [];
    
    if ($entries_result->num_rows > 0) {
        // If we have structured entries, use them
        while ($entry = $entries_result->fetch_assoc()) {
            $idp_entries[] = $entry;
        }
    } else if (!empty($record['content'])) {
        // If we have JSON content, parse it
        $content = json_decode($record['content'], true);
        
        // Process New IDP Structure (idp_goals)
        if (isset($content['idp_goals']) && is_array($content['idp_goals'])) {
            foreach ($content['idp_goals'] as $item) {
                $idp_entries[] = [
                    'development_needs' => 'Development Goal', // Generic label
                    'goals' => $item['objective'] ?? '',
                    'competencies' => '', // Not captured in new form
                    'actions' => $item['action_plan'] ?? '',
                    'timeline_display' => $record['period'] ?? '',
                    'status' => $item['status'] ?? 'Not Started'
                ];
            }
        }
        
        // Process Professional Development entries
        if (isset($content['professional_development']) && is_array($content['professional_development'])) {
            foreach ($content['professional_development'] as $item) {
                $idp_entries[] = [
                    'development_needs' => 'Professional', // Category marker
                    'goals' => $item['goals'] ?? '',
                    'competencies' => $item['competencies'] ?? '',
                    'actions' => $item['actions'] ?? '',
                    'timeline_display' => $item['timeline'] ?? '',
                    'status' => $item['status'] ?? 'Not Started'
                ];
            }
        }
        
        // Process Personal Development entries
        if (isset($content['personal_development']) && is_array($content['personal_development'])) {
            foreach ($content['personal_development'] as $item) {
                $idp_entries[] = [
                    'development_needs' => 'Personal', // Category marker
                    'goals' => $item['goals'] ?? '',
                    'competencies' => $item['competencies'] ?? '',
                    'actions' => $item['actions'] ?? '',
                    'timeline_display' => $item['timeline'] ?? '',
                    'status' => $item['status'] ?? 'Not Started'
                ];
            }
        }
        
        // Process Career Advancement entries
        if (isset($content['career_advancement']) && is_array($content['career_advancement'])) {
            foreach ($content['career_advancement'] as $item) {
                $idp_entries[] = [
                    'development_needs' => 'Career', // Category marker
                    'goals' => $item['goals'] ?? '',
                    'competencies' => $item['competencies'] ?? '',
                    'actions' => $item['actions'] ?? '',
                    'timeline_display' => $item['timeline'] ?? '',
                    'status' => $item['status'] ?? 'Not Started'
                ];
            }
        }
    }
    
    $entries = [
        'idp' => $idp_entries
    ];
}

// Check if user can review this record
$can_review = ($user_role == 'department_head' && $record['department_id'] == $department_id && $record['user_id'] != $user_id) || 
              ($user_role == 'president') ||
              ($user_role == 'admin');

// Check if success/error messages exist
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!-- View Record Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <?php echo $record['form_type']; ?> - 
            <?php echo htmlspecialchars($record['employee_name']); ?>
        </h1>
        <div>
            <a href="records.php" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Records
            </a>
            <?php if ($record['document_status'] === 'Approved'): ?>
            <a href="print_record.php?id=<?php echo $record_id; ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-printer"></i> Print
            </a>
            <?php endif; ?>
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
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Record Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Employee</div>
                    <div><?php echo htmlspecialchars($record['employee_name']); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Department</div>
                    <div><?php echo htmlspecialchars($record['department_name'] ?? 'Not Assigned'); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Period</div>
                    <div><?php echo htmlspecialchars($record['period']); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Status</div>
                    <div>
                        <?php 
                        $status_class = "";
                        switch ($record['document_status']) {
                            case 'Draft':
                                $status_class = "secondary";
                                break;
                            case 'Approved':
                                $status_class = "success";
                                break;
                            case 'Pending':
                                $status_class = "warning";
                                break;
                            case 'For Revision':
                                $status_class = "danger";
                                break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?>">
                            <?php echo $record['document_status']; ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($record['date_submitted']): ?>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Date Submitted</div>
                    <div><?php echo date('F d, Y', strtotime($record['date_submitted'])); ?></div>
            </div>
                <?php endif; ?>
                
                <?php if ($record['reviewed_by']): ?>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Reviewed By</div>
                    <div><?php echo htmlspecialchars($record['reviewer_name']); ?></div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="fw-bold">Date Reviewed</div>
                    <div><?php echo date('F d, Y', strtotime($record['date_reviewed'])); ?></div>
                </div>
                <?php endif; ?>
                
                <?php /*if (!empty($record['comments'])): ?>
                <div class="col-md-12 mb-3">
                    <div class="fw-bold">Comments</div>
                    <div><?php echo nl2br(htmlspecialchars($record['comments'])); ?></div>
                </div>
                <?php endif; */?>
            </div>
        </div>
    </div>
            
    <!-- Review Status and Feedback Section -->
    <?php if($record['document_status'] !== 'Draft'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Review Status</h5>
            <?php
                $status_badge_class = '';
                switch($record['document_status']) {
                    case 'Pending':
                        $status_badge_class = 'warning';
                        break;
                    case 'Approved':
                        $status_badge_class = 'success';
                        break;
                    case 'For Revision':
                        $status_badge_class = 'danger';
                        break;
                }
            ?>
            <span class="badge bg-<?php echo $status_badge_class; ?> fs-6"><?php echo $record['document_status']; ?></span>
        </div>
        <div class="card-body">
            <?php if($record['document_status'] === 'Pending'): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-hourglass-split me-2"></i>
                    <span>This form is currently pending review by your department head.</span>
                </div>
            <?php elseif($record['document_status'] === 'Approved' || $record['document_status'] === 'For Revision'): ?>
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <i class="bi bi-person-circle fs-1 text-muted"></i>
                    </div>
                    <div>
                        <p class="mb-0">
                            <strong>Reviewed by:</strong> 
                            <?php echo htmlspecialchars($record['reviewer_name'] ?? 'Department Head'); ?>
                        </p>
                        <p class="mb-0 text-muted">
                            <small>
                                <i class="bi bi-clock me-1"></i>
                                <?php echo isset($record['date_approved']) ? date('F d, Y \a\t h:i A', strtotime($record['date_approved'])) : 'Date not recorded'; ?>
                            </small>
                        </p>
                    </div>
                </div>
                
                <!-- Feedback Section -->
                <?php if (!empty($record['feedback'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Review Feedback</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="fw-bold">Feedback from <?php echo htmlspecialchars($record['reviewer_name'] ?? 'Reviewer'); ?>:</label>
                            <div class="border rounded p-3">
                                <?php echo nl2br(htmlspecialchars($record['feedback'])); ?>
                            </div>
                        </div>
                        
                        <?php 
                        // Only show remarks to department heads, admins, and presidents
                        if (($user_role == 'department_head' || $user_role == 'admin' || $user_role == 'president') && !empty($record['remarks'])): 
                        ?>
                        <div class="mb-0">
                            <label class="fw-bold">
                                Remarks <span class="badge bg-info">Department Head Only</span>
                            </label>
                            <div class="border rounded p-3 bg-light">
                                <?php echo nl2br(htmlspecialchars($record['remarks'])); ?>
                            </div>
                            <div class="form-text text-danger">
                                <i class="bi bi-lock-fill me-1"></i> These remarks are only visible to department heads and administrators.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if($record['document_status'] === 'For Revision'): ?>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Please review the feedback above and consider submitting a revised version.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
            
    <!-- DPCR Form Content -->
    <?php if ($record['form_type'] === 'DPCR'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Department Performance Commitment and Review (DPCR)</h5>
        </div>
        <div class="card-body">
            <?php
            $render_dpcr_section = function($title, $entries, $weight) {
                if (empty($entries)) return;
                ?>
                <div class="mb-4">
                    <h5 class="mb-3 text-primary"><?php echo $title; ?> <?php echo $weight; ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr class="text-center align-middle">
                                    <th width="15%">Major Final Output</th>
                                    <th width="15%">Success Indicators</th>
                                    <th width="10%">Budget</th>
                                    <th width="10%">Accountable</th>
                                    <th width="15%">Actual Accomplishments</th>
                                    <th width="5%">Q</th>
                                    <th width="5%">E</th>
                                    <th width="5%">T</th>
                                    <th width="5%">A</th>
                                    <th width="10%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
                                <tr class="align-middle">
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td class="text-end"><?php echo htmlspecialchars(isset($entry['budget']) && is_numeric($entry['budget']) ? number_format($entry['budget'], 2) : 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($entry['accountable']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? '')); ?></td>
                                    <td class="text-center fw-bold"><?php echo htmlspecialchars($entry['q_rating'] ?? '-'); ?></td>
                                    <td class="text-center fw-bold"><?php echo htmlspecialchars($entry['e_rating'] ?? '-'); ?></td>
                                    <td class="text-center fw-bold"><?php echo htmlspecialchars($entry['t_rating'] ?? '-'); ?></td>
                                    <td class="text-center bg-light fw-bold"><?php echo htmlspecialchars($entry['a_rating'] ?? '-'); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['remarks'] ?? '')); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
            };

            $computation_type = $entries['computation_type'] ?? 'Type1';
            $strategic_weight = ($computation_type === 'Type2') ? '(45%)' : '(45%)';
            $core_weight = ($computation_type === 'Type2') ? '(45%)' : '(55%)';

            $render_dpcr_section('I. Strategic Functions', $entries['strategic'], $strategic_weight);
            $render_dpcr_section('II. Core Functions', $entries['core'], $core_weight);
            if ($computation_type === 'Type2') {
                $render_dpcr_section('III. Support Functions', $entries['support'], '(10%)');
            }
            ?>
        </div>
    </div>
    <?php endif; ?>
                
    <!-- IPCR Form Content -->
    <?php if ($record['form_type'] === 'IPCR'): ?>
    <?php
                // =================================================================================
                // NEW IPCR EDITING LOGIC
                // Determine which fields should be disabled based on role and status
                // =================================================================================
                // VIEW MODE SETTINGS
                // In view_record.php, everything is read-only and visible to everyone
                $template_disabled = true;
                $submission_disabled = true;
                $review_disabled = true;
                $hide_supervisor_rating = false;
                ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Individual Performance Commitment and Review (IPCR)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($content)): ?>
                <div class="alert alert-warning">This IPCR record has no content to display.</div>
            <?php else: ?>
                <div id="ipcr-edit-section">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-4">
                            <thead class="table-light">
                                <tr>
                                    <th rowspan="2" class="align-middle text-center" style="width: 16%">MAJOR FINAL OUTPUT (MFO)</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 16%">SUCCESS INDICATORS</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 12%">ACTUAL ACCOMPLISHMENTS</th>
                                    <th colspan="4" class="text-center">SELF-RATING</th>
                                    <?php if (!$hide_supervisor_rating): ?>
                                    <th colspan="4" class="text-center">SUPERVISOR'S RATING</th>
                                    <?php endif; ?>
                                    <th rowspan="2" class="align-middle text-center" style="width: 12%">REMARKS / COMMENTS</th>
                                    <th rowspan="2" class="align-middle text-center" style="width: 2%"></th>
                                </tr>
                                <tr>
                                    <th class="text-center">Q</th><th class="text-center">E</th><th class="text-center">T</th><th class="text-center">A</th>
                                    <?php if (!$hide_supervisor_rating): ?>
                                    <th class="text-center">Q</th><th class="text-center">E</th><th class="text-center">T</th><th class="text-center">A</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="ipcr-table-body">
                                <!-- JS will build the table body from JSON content -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Employee Self-Rating Summary -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Self-Rating Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Strategic Functions Average:</strong>
                                    <p id="strategic-avg-display" class="fs-4 fw-bold"><?php echo htmlspecialchars($content['summary']['strategic_average'] ?? '0.00'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <strong>Core Functions Average:</strong>
                                    <p id="core-avg-display" class="fs-4 fw-bold"><?php echo htmlspecialchars($content['summary']['core_average'] ?? '0.00'); ?></p>
                                </div>
                                <div class="col-md-4" id="support-summary" style="display: <?php echo ($content['computation_type'] ?? '') === 'Type2' ? 'block' : 'none'; ?>;">
                                    <strong>Support Functions Average:</strong>
                                    <p id="support-avg-display" class="fs-4 fw-bold"><?php echo htmlspecialchars($content['summary']['support_average'] ?? '0.00'); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h4>Final Average Rating:</h4>
                                    <p id="final-rating-display" class="display-4 fw-bold text-primary"><?php echo htmlspecialchars($content['summary']['final_rating'] ?? '0.00'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h4>Adjectival Rating:</h4>
                                    <p id="adjectival-rating" class="display-6 text-primary"><?php echo htmlspecialchars($content['summary']['adjectival_rating'] ?? 'Poor'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supervisor Rating Summary -->
                    <div class="card mt-4" id="supervisor-summary-card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Supervisor Rating Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Strategic Functions Average:</strong>
                                    <p id="supervisor-strategic-avg-display" class="fs-4 fw-bold"><?php echo htmlspecialchars($content['supervisor_summary']['strategic_average'] ?? $content['supervisor_strategic_average'] ?? '0.00'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <strong>Core Functions Average:</strong>
                                    <p id="supervisor-core-avg-display" class="fs-4 fw-bold"><?php echo htmlspecialchars($content['supervisor_summary']['core_average'] ?? $content['supervisor_core_average'] ?? '0.00'); ?></p>
                                </div>
                                <div class="col-md-4" id="supervisor-support-summary" style="display: <?php echo ($content['computation_type'] ?? '') === 'Type2' ? 'block' : 'none'; ?>;">
                                    <strong>Support Functions Average:</strong>
                                    <p id="supervisor-support-avg-display" class="fs-4 fw-bold"><?php echo htmlspecialchars($content['supervisor_summary']['support_average'] ?? $content['supervisor_support_average'] ?? '0.00'); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h4>Final Average Rating:</h4>
                                    <p id="supervisor-final-rating-display" class="display-4 fw-bold text-primary"><?php echo htmlspecialchars($content['supervisor_summary']['final_rating'] ?? $content['supervisor_final_rating'] ?? '0.00'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h4>Adjectival Rating:</h4>
                                    <p id="supervisor-adjectival-rating" class="display-6 text-primary"><?php echo htmlspecialchars($content['supervisor_summary']['adjectival_rating'] ?? $content['supervisor_rating_interpretation'] ?? 'Poor'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 mt-4">
                        <label for="dh_comments" class="form-label">Comments & Recommendations for Development</label>
                        <textarea class="form-control" id="dh_comments" name="dh_comments" rows="3" readonly><?php echo htmlspecialchars($content['dh_comments'] ?? ''); ?></textarea>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- IDP Form Content -->
    <?php if ($record['form_type'] === 'IDP'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Individual Development Plan (IDP)</h5>
        </div>
        <div class="card-body">
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
                        <?php if (empty($entries['idp'])): ?>
                        <tr>
                            <td colspan="5" class="text-center">No development plans defined</td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            // Get all Professional Development entries first
                            $prof_entries = array_filter($entries['idp'], function($entry) {
                                return isset($entry['development_needs']) && $entry['development_needs'] === 'Professional'; 
                            });
                            
                            if (!empty($prof_entries)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center bg-light fw-bold">PROFESSIONAL DEVELOPMENT</td>
                                </tr>
                            <?php foreach ($prof_entries as $entry): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                        <td>
                                            <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
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
                                        <?php echo $entry['status']; ?>
                                    </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            // Get all Personal Development entries
                            $personal_entries = array_filter($entries['idp'], function($entry) {
                                return isset($entry['development_needs']) && $entry['development_needs'] === 'Personal'; 
                            });
                            
                            if (!empty($personal_entries)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center bg-light fw-bold">PERSONAL DEVELOPMENT</td>
                                </tr>
                            <?php foreach ($personal_entries as $entry): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                        <td>
                                            <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
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
                                        <?php echo $entry['status']; ?>
                                    </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            // Get all Career Development entries
                            $career_entries = array_filter($entries['idp'], function($entry) {
                                return isset($entry['development_needs']) && $entry['development_needs'] === 'Career'; 
                            });
                            
                            if (!empty($career_entries)): 
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center bg-light fw-bold">CAREER ADVANCEMENT</td>
                                </tr>
                            <?php foreach ($career_entries as $entry): ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'])); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                        <td>
                                            <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
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
                                        <?php echo $entry['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php 
                            // If there are entries that don't fit into the above categories, show them here
                            $other_entries = array_filter($entries['idp'], function($entry) {
                                return !isset($entry['development_needs']) || 
                                       ($entry['development_needs'] !== 'Professional' && 
                                        $entry['development_needs'] !== 'Personal' && 
                                        $entry['development_needs'] !== 'Career'); 
                            });
                            
                            foreach ($other_entries as $entry): 
                            ?>
                            <tr>
                                <td><?php echo nl2br(htmlspecialchars($entry['goals'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['competencies'] ?? '')); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($entry['actions'] ?? '')); ?></td>
                                <td>
                                    <?php if (isset($entry['timeline_start']) && isset($entry['timeline_end']) && $entry['timeline_start'] && $entry['timeline_end']): ?>
                                        <?php echo date('M d, Y', strtotime($entry['timeline_start'])); ?> to 
                                        <?php echo date('M d, Y', strtotime($entry['timeline_end'])); ?>
                                    <?php elseif (isset($entry['timeline_display'])): ?>
                                        <?php echo htmlspecialchars($entry['timeline_display']); ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = "";
                                    switch ($entry['status']) {
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
                                        <?php echo $entry['status']; ?>
                                    </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </div>
                </div>
                <?php endif; ?>
                
    <!-- Review Form for Approvers -->
    <?php if ($can_review && $record['document_status'] === 'Pending' && $record['form_type'] === 'IPCR'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Supervisor Rating</h5>
        </div>
        <div class="card-body">
            <form method="post" action="update_ratings.php">
                <input type="hidden" name="record_id" value="<?php echo $record_id; ?>">
                
                <!-- Strategic Functions Ratings -->
                <h5 class="mb-3">Strategic Functions (45%)</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Major Final Output</th>
                                <th width="20%">Success Indicators</th>
                                <th width="20%">Accomplishments</th>
                                <th width="25%" class="text-center">Supervisor Rating</th>
                                <th width="15%">Remarks</th>
                            </tr>
                            <tr class="text-center">
                                <th colspan="3"></th>
                                <th>
                                    <div class="row">
                                        <div class="col-4">Q</div>
                                        <div class="col-4">E</div>
                                        <div class="col-4">T</div>
                                    </div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['strategic'])): ?>
                            <tr>
                                <td colspan="5" class="text-center">No strategic outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['strategic'] as $index => $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? 'Not yet accomplished')); ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-q" 
                                                       name="strategic_supervisor-q[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_q_rating'] ?? ''; ?>" 
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-e" 
                                                       name="strategic-supervisor-e[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_e_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm strategic-supervisor-t" 
                                                       name="strategic-supervisor-t[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_t_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="strategic_remarks[<?php echo $index; ?>]" 
                                               value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>">
                                        <input type="hidden" name="strategic_id[<?php echo $index; ?>]" 
                                               value="<?php echo $entry['id'] ?? ''; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Core Functions Ratings -->
                <h5 class="mb-3">Core Functions (55%)</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Major Final Output</th>
                                <th width="20%">Success Indicators</th>
                                <th width="20%">Accomplishments</th>
                                <th width="25%" class="text-center">Supervisor Rating</th>
                                <th width="15%">Remarks</th>
                            </tr>
                            <tr class="text-center">
                                <th colspan="3"></th>
                                <th>
                                    <div class="row">
                                        <div class="col-4">Q</div>
                                        <div class="col-4">E</div>
                                        <div class="col-4">T</div>
                                    </div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries['core'])): ?>
                            <tr>
                                <td colspan="5" class="text-center">No core outputs defined</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entries['core'] as $index => $entry): ?>
                                <tr>
                                    <td><?php echo nl2br(htmlspecialchars($entry['major_output'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['success_indicators'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($entry['actual_accomplishments'] ?? 'Not yet accomplished')); ?></td>
                                    <td>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-q" 
                                                       name="core-supervisor-q[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_q_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-e" 
                                                       name="core-supervisor-e[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_e_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm core-supervisor-t" 
                                                       name="core-supervisor-t[<?php echo $index; ?>]" 
                                                       min="1" max="5" value="<?php echo $entry['supervisor_t_rating'] ?? ''; ?>"
                                                       data-index="<?php echo $index; ?>">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="core_remarks[<?php echo $index; ?>]" 
                                               value="<?php echo htmlspecialchars($entry['remarks'] ?? ''); ?>">
                                        <input type="hidden" name="core_id[<?php echo $index; ?>]" 
                                               value="<?php echo $entry['id'] ?? ''; ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Final Rating Summary -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Final Rating</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Strategic Functions Average (45%)</label>
                                    <input type="number" class="form-control" id="strategic_average" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Core Functions Average (55%)</label>
                                    <input type="number" class="form-control" id="core_average" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Final IPCR Rating</label>
                                    <input type="number" class="form-control" id="final_rating" name="final_rating" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="comments" class="form-label">Comments/Feedback</label>
                    <textarea class="form-control" id="comments" name="comments" rows="3"><?php echo htmlspecialchars($record['comments'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" name="action" value="save_ratings" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Ratings
                    </button>
                    <button type="submit" name="action" value="approve" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Save & Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
    
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="js/auto_scoring.js"></script>

<script>
$(document).ready(function() {
    
    // =================================================================================
    // DPCR FORM LOGIC (Original)
    // =================================================================================
    const addDpcrRowBtn = document.getElementById('add-dpcr-row');
    if (addDpcrRowBtn) {
        addDpcrRowBtn.addEventListener('click', function() {
            const tbody = document.getElementById('dpcr-table-body');
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td><input type="text" name="major_output[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="success_indicators[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="budget[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="accountable[]" class="form-control form-control-sm"></td>
                <td><input type="text" name="accomplishments[]" class="form-control form-control-sm"></td>
                <td><input type="number" name="q1[]" class="form-control form-control-sm" min="1" max="5" step="1"></td>
                <td><input type="number" name="q2[]" class="form-control form-control-sm" min="1" max="5" step="1"></td>
                <td><input type="number" name="q3[]" class="form-control form-control-sm" min="1" max="5" step="1"></td>
                <td><input type="number" name="q4[]" class="form-control form-control-sm" min="1" max="5" step="1"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="bi bi-trash"></i></button></td>
            `;
            
            tbody.appendChild(newRow);
        });
    }
    
    // Universal remove button for both DPCR and IPCR
    $(document).on('click', '.remove-row', function() {
        const tbody = $(this).closest('tbody');
        if (tbody.children('tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            alert('You cannot remove the last row.');
        }
    });

    const isIpcr = <?php echo json_encode($record['form_type'] === 'IPCR'); ?>;
    
    if (isIpcr) {
        const content = <?php echo json_encode($content); ?>;
        const computationType = content.computation_type || 'Type1';
        const template_disabled = <?php echo json_encode($template_disabled ?? true); ?>;
        const submission_disabled = <?php echo json_encode($submission_disabled ?? true); ?>;
        const review_disabled = <?php echo json_encode($review_disabled ?? true); ?>;
        const hide_supervisor_rating = <?php echo json_encode($hide_supervisor_rating ?? false); ?>;

    if (computationType === 'Type2') {
        $('#support-summary').show();
        $('#supervisor-support-summary').show();
    }

    function getRatingInterpretation(averageScore) {
        const score = parseFloat(averageScore);
        
        if (score >= 4.5) return "Outstanding";
        if (score >= 3.5) return "Very Satisfactory";
        if (score >= 2.5) return "Satisfactory";
        if (score >= 1.5) return "Unsatisfactory";
        return "Poor";
    }

        function updateSupervisorRatingSummary() {
            let strategicTotal = 0;
            let strategicCount = 0;
            $('tr.strategic-function-row').each(function() {
                const avg = parseFloat($(this).find('.supervisor-average-rating').val());
                if (!isNaN(avg)) {
                    strategicTotal += avg;
                    strategicCount++;
                }
            });
            const strategicAverage = strategicCount > 0 ? (strategicTotal / strategicCount) : 0;
            
            let coreTotal = 0;
            let coreCount = 0;
            $('tr.core-function-row').each(function() {
                const avg = parseFloat($(this).find('.supervisor-average-rating').val());
                if (!isNaN(avg)) {
                    coreTotal += avg;
                    coreCount++;
                }
            });
            const coreAverage = coreCount > 0 ? (coreTotal / coreCount) : 0;
            
            let supportAverage = 0;
            if (computationType === 'Type2') {
                let supportTotal = 0;
                let supportCount = 0;
                $('tr.support-function-row').each(function() {
                    const avg = parseFloat($(this).find('.supervisor-average-rating').val());
                    if (!isNaN(avg)) {
                        supportTotal += avg;
                        supportCount++;
                    }
                });
                supportAverage = supportCount > 0 ? (supportTotal / supportCount) : 0;
            }

            let finalRating = 0;
            let strategicWeighted = 0;
            let coreWeighted = 0;
            let supportWeighted = 0;

            if (computationType === 'Type1') {
                strategicWeighted = strategicAverage * 0.45;
                coreWeighted = coreAverage * 0.55;
                finalRating = strategicWeighted + coreWeighted;
            } else { // Type2
                strategicWeighted = strategicAverage * 0.45;
                coreWeighted = coreAverage * 0.45;
                supportWeighted = supportAverage * 0.10;
                finalRating = strategicWeighted + coreWeighted + supportWeighted;
            }

            $('#supervisor-strategic-avg-display').text(strategicWeighted.toFixed(2));
            $('#supervisor-core-avg-display').text(coreWeighted.toFixed(2));
             if (computationType === 'Type2') {
                $('#supervisor-support-avg-display').text(supportWeighted.toFixed(2));
            }
            
            $('#supervisor-final-rating-display').text(finalRating.toFixed(2));
            $('#supervisor-adjectival-rating').text(getRatingInterpretation(finalRating));
        }

        function updateEmployeeRatingSummary() {
            let strategicTotal = 0;
            let strategicCount = 0;
            $('tr.strategic-function-row').each(function() {
                const avg = parseFloat($(this).find('.average-rating').val());
                if (!isNaN(avg)) {
                    strategicTotal += avg;
                    strategicCount++;
                }
            });
            const strategicAverage = strategicCount > 0 ? (strategicTotal / strategicCount) : 0;
            
            let coreTotal = 0;
            let coreCount = 0;
            $('tr.core-function-row').each(function() {
                const avg = parseFloat($(this).find('.average-rating').val());
                if (!isNaN(avg)) {
                    coreTotal += avg;
                    coreCount++;
                }
            });
            const coreAverage = coreCount > 0 ? (coreTotal / coreCount) : 0;
            
            let supportAverage = 0;
            if (computationType === 'Type2') {
                let supportTotal = 0;
                let supportCount = 0;
                $('tr.support-function-row').each(function() {
                    const avg = parseFloat($(this).find('.average-rating').val());
                    if (!isNaN(avg)) {
                        supportTotal += avg;
                        supportCount++;
                    }
                });
                supportAverage = supportCount > 0 ? (supportTotal / supportCount) : 0;
            }

            let finalRating = 0;
            let strategicWeighted = 0;
            let coreWeighted = 0;
            let supportWeighted = 0;

            if (computationType === 'Type1') {
                strategicWeighted = strategicAverage * 0.45;
                coreWeighted = coreAverage * 0.55;
                finalRating = strategicWeighted + coreWeighted;
            } else { // Type2
                strategicWeighted = strategicAverage * 0.45;
                coreWeighted = coreAverage * 0.45;
                supportWeighted = supportAverage * 0.10;
                finalRating = strategicWeighted + coreWeighted + supportWeighted;
            }

            $('#strategic-avg-display').text(strategicWeighted.toFixed(2));
            $('#core-avg-display').text(coreWeighted.toFixed(2));
             if (computationType === 'Type2') {
                $('#support-avg-display').text(supportWeighted.toFixed(2));
            }
            
            $('#final-rating-display').text(finalRating.toFixed(2));
            $('#adjectival-rating').text(getRatingInterpretation(finalRating));
        }

        const renderIpcrTable = () => {
            const tableBody = $('#ipcr-table-body');
            tableBody.empty();

            const buildSection = (title, category, entries) => {
                // Ensure entries is an array (handle case where PHP json_encode returned an object for sparse arrays)
                const entriesArray = Array.isArray(entries) ? entries : (entries ? Object.values(entries) : []);

                if (entriesArray.length === 0) return;

                const colSpan = hide_supervisor_rating ? 9 : 13;
                tableBody.append(`<tr><td colspan="${colSpan}" class="text-start bg-light fw-bold">${title}</td></tr>`);
                
                entriesArray.forEach(entry => {
                    const row = `
                        <tr class="function-row ${category}-function-row">
                            <td><textarea class="form-control form-control-sm" name="${category}_mfo[]" ${template_disabled ? 'readonly' : ''}>${entry.mfo || ''}</textarea></td>
                            <td><textarea class="form-control form-control-sm" name="${category}_success_indicators[]" ${template_disabled ? 'readonly' : ''}>${entry.success_indicators || ''}</textarea></td>
                            <td><textarea class="form-control form-control-sm" name="${category}_accomplishments[]" ${submission_disabled ? 'readonly' : ''}>${entry.accomplishments || ''}</textarea></td>
                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${category}_q[]" min="1" max="5" step="1" maxlength="1" value="${entry.q || ''}" ${submission_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${category}_e[]" min="1" max="5" step="1" maxlength="1" value="${entry.e || ''}" ${submission_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${category}_t[]" min="1" max="5" step="1" maxlength="1" value="${entry.t || ''}" ${submission_disabled ? 'readonly' : ''}></td>
                            <td><input type="text" class="form-control form-control-sm average-rating" name="${category}_a[]" value="${entry.a || ''}" readonly></td>
                            ${!hide_supervisor_rating ? `
                            <td><input type="number" class="form-control form-control-sm rating-input supervisor-rating" name="${category}_supervisor_q[]" min="1" max="5" step="1" maxlength="1" value="${entry.supervisor_q || ''}" ${review_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input supervisor-rating" name="${category}_supervisor_e[]" min="1" max="5" step="1" maxlength="1" value="${entry.supervisor_e || ''}" ${review_disabled ? 'readonly' : ''}></td>
                            <td><input type="number" class="form-control form-control-sm rating-input supervisor-rating" name="${category}_supervisor_t[]" min="1" max="5" step="1" maxlength="1" value="${entry.supervisor_t || ''}" ${review_disabled ? 'readonly' : ''}></td>
                            <td><input type="text" class="form-control form-control-sm supervisor-average-rating" name="${category}_supervisor_a[]" value="${entry.supervisor_a || ''}" readonly></td>
                            ` : ''}
                            <td>
                                <textarea class="form-control form-control-sm" name="${category}_remarks[]" ${review_disabled ? 'readonly' : ''}>${entry.remarks || ''}</textarea>
                                ${hide_supervisor_rating ? `
                                <input type="hidden" name="${category}_supervisor_q[]" min="1" max="5" step="1" maxlength="1" value="${entry.supervisor_q || ''}">
                                <input type="hidden" name="${category}_supervisor_e[]" min="1" max="5" step="1" maxlength="1" value="${entry.supervisor_e || ''}">
                                <input type="hidden" name="${category}_supervisor_t[]" min="1" max="5" step="1" maxlength="1" value="${entry.supervisor_t || ''}">
                                <input type="hidden" name="${category}_supervisor_a[]" value="${entry.supervisor_a || ''}">
                                ` : ''}
                            </td>
                            <td></td> <!-- Placeholder for remove button if needed -->
                        </tr>
                    `;
                    tableBody.append(row);
                });
            };

            buildSection('I. STRATEGIC FUNCTIONS', 'strategic', content.strategic_functions);
            buildSection('II. CORE FUNCTIONS', 'core', content.core_functions);
            if (content.computation_type === 'Type2') {
                 buildSection('III. SUPPORT FUNCTIONS', 'support', content.support_functions);
            }
            updateSupervisorRatingSummary();
            updateEmployeeRatingSummary();
        };

        const calculateRowAverage = (row) => {
            const calculate = (prefix) => {
                const q = parseFloat($(row).find(`input[name$="${prefix}_q[]"]`).val()) || 0;
                const e = parseFloat($(row).find(`input[name$="${prefix}_e[]"]`).val()) || 0;
                const t = parseFloat($(row).find(`input[name$="${prefix}_t[]"]`).val()) || 0;
                let count = (q > 0 ? 1:0) + (e > 0 ? 1:0) + (t > 0 ? 1:0);
                const avg = count > 0 ? ((q + e + t) / count).toFixed(2) : '';
                
                let target_a_selector;
                if (prefix === 'supervisor') {
                    target_a_selector = `input[name$="supervisor_a[]"]`;
                } else {
                    target_a_selector = `input[name$="a[]"]:not([name*="supervisor"])`;
                }
                $(row).find(target_a_selector).val(avg);
            };
            calculate(''); // Self-rating
            calculate('supervisor'); // Supervisor rating
        };

        $('#ipcr-table-body').on('input', '.rating-input', function() {
            // Restrict input to single digit 1-5
            let val = $(this).val();
            val = val.replace(/[^1-5]/g, ''); // Allow only digits 1-5
            if (val.length > 1) val = val.slice(0, 1); // Limit to 1 character
            $(this).val(val);

            calculateRowAverage($(this).closest('tr'));
            if ($(this).hasClass('supervisor-rating')) {
                updateSupervisorRatingSummary();
            } else {
                updateEmployeeRatingSummary();
            }
        });
        console.log('IPCR table rendered and event listeners set.');
        renderIpcrTable();
        
    }
});
</script>
    <?php endif; ?>
    
    <!-- Regular Review Form for Approvers -->
    <?php if ($can_review && $record['document_status'] === 'Pending' && $record['form_type'] !== 'IPCR'): ?>
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Review Record</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="comments" class="form-label">Comments/Feedback</label>
                    <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" name="review_action" value="reject" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this record?')">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                    <button type="submit" name="review_action" value="approve" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
    const isIpcr = <?php echo json_encode($record['form_type'] === 'IPCR'); ?>;
    
    if (isIpcr) {
        const content = <?php echo !empty($record['content']) ? $record['content'] : '{}'; ?>;
        const hide_supervisor_rating = <?php echo json_encode($hide_supervisor_rating ?? false); ?>;
        
        const renderIpcrTable = () => {
            const tableBody = $('#ipcr-table-body');
            tableBody.empty();

            const buildSection = (title, category, entries) => {
                // Handle sparse arrays (objects) vs arrays
                const entriesArray = Array.isArray(entries) ? entries : (entries ? Object.values(entries) : []);
                
                if (entriesArray.length === 0) return;

                const colSpan = hide_supervisor_rating ? 9 : 13;
                tableBody.append(`<tr><td colspan="${colSpan}" class="text-start bg-light fw-bold">${title}</td></tr>`);
                
                entriesArray.forEach(entry => {
                    const row = `
                        <tr class="function-row ${category}-function-row">
                            <td><textarea class="form-control form-control-sm" readonly>${entry.mfo || ''}</textarea></td>
                            <td><textarea class="form-control form-control-sm" readonly>${entry.success_indicators || ''}</textarea></td>
                            <td><textarea class="form-control form-control-sm" readonly>${entry.accomplishments || ''}</textarea></td>
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.q || ''}" readonly></td>
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.e || ''}" readonly></td>
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.t || ''}" readonly></td>
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.a || ''}" readonly></td>
                            ${!hide_supervisor_rating ? `
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.supervisor_q || ''}" readonly></td>
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.supervisor_e || ''}" readonly></td>
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.supervisor_t || ''}" readonly></td>
                            <td><input type="text" class="form-control form-control-sm text-center" value="${entry.supervisor_a || ''}" readonly></td>
                            ` : ''}
                            <td><textarea class="form-control form-control-sm" readonly>${entry.remarks || ''}</textarea></td>
                            <td></td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            };

            buildSection('I. STRATEGIC FUNCTIONS', 'strategic', content.strategic_functions);
            buildSection('II. CORE FUNCTIONS', 'core', content.core_functions);
            if (content.computation_type === 'Type2') {
                 buildSection('III. SUPPORT FUNCTIONS', 'support', content.support_functions);
            }
        };
        
        console.log('IPCR table rendered and event listeners set.');
        renderIpcrTable();
    }
});
</script>

<?php
// Include footer
include_once('includes/footer.php');
?> 