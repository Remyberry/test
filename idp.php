<?php
// Set page title
$page_title = "Individual Development Plan - EPMS";

// Include header
include_once('includes/header.php');

// Include form workflow functions
include_once('includes/form_workflow.php');

// Check if user has the right role to access this page
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'regular_employee' && $_SESSION['user_role'] !== 'department_head' && $_SESSION['user_role'] !== 'president' && $_SESSION['user_role'] !== 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_department_id = $_SESSION['user_department_id'];
$employee_name = $_SESSION['user_name'] ?? 'Employee Name N/A';

// Get department name
$dept_query = "SELECT name FROM departments WHERE id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $user_department_id);
$stmt->execute();
$dept_result = $stmt->get_result();
$department_name = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['name'] : 'N/A';
$stmt->close();

// --- HANDLE POST SUBMISSION ---
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect Form Data
    $record_id = isset($_POST['record_id']) ? intval($_POST['record_id']) : 0;
    $period = $_POST['period'] ?? '';
    
    // Build IDP Data Array
    $main_objectives = $_POST['main_objectives'] ?? [];
    $plan_of_action = $_POST['plan_of_action'] ?? [];
    $statuses = $_POST['status'] ?? [];

    $is_phase_2_update = isset($_POST['is_phase_2_update']);
    if ($is_phase_2_update) {
        $dates_accomplished = $_POST['date_accomplished'] ?? [];
        $results = $_POST['results'] ?? [];
    }
    
    $new_idp_goals = [];
    $count = count($main_objectives);
    
    for ($i = 0; $i < $count; $i++) {
        $obj = trim($main_objectives[$i]);
        // Save row if objective is not empty (or if it's the only row, keep it empty structure)
        if (!empty($obj) || $count === 1) {
            $goal_entry = [
                'objective' => $obj,
                'action_plan' => trim($plan_of_action[$i] ?? ''),
                'status' => trim($statuses[$i] ?? 'Not Started'),
            ];

            if ($is_phase_2_update) {
                $goal_entry['date_accomplished'] = trim($dates_accomplished[$i] ?? '');
                $goal_entry['results'] = trim($results[$i] ?? '');
            }

            $new_idp_goals[] = $goal_entry;
        }
    }
    
    // Prepare JSON content
    $content_array = ['idp_goals' => $new_idp_goals];
    $content_json = json_encode($content_array, JSON_UNESCAPED_UNICODE);

    // 2. Process Action (Draft or Submit)
    if (isset($_POST['save_draft'])) {
        if ($record_id > 0) {
            // Update Existing Draft
            $update_query = "UPDATE records SET content = ?, period = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssii", $content_json, $period, $record_id, $user_id);
            if ($stmt->execute()) {
                $message = "IDP draft updated successfully.";
                $message_type = "success";
            } else {
                $message = "Error updating draft: " . $conn->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            // Create New Draft
            $insert_query = "INSERT INTO records (user_id, form_type, period, content, document_status) VALUES (?, 'IDP', ?, ?, 'Draft')";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iss", $user_id, $period, $content_json);
            if ($stmt->execute()) {
                $record_id = $conn->insert_id; // Capture new ID
                $message = "IDP saved as draft successfully.";
                $message_type = "success";
            } else {
                $message = "Error saving draft: " . $conn->error;
                $message_type = "danger";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['submit_idp'])) {
        // Submit for Review
        $dept_head_name = "your department head"; // Default value
        
        // Use the same logic as IPCR to get the department head's name
        $dept_head_query = "SELECT u.name, u.email FROM users u 
                            WHERE u.department_id = ? AND u.role = 'department_head'";
        $dept_stmt = $conn->prepare($dept_head_query);
        $dept_stmt->bind_param("i", $user_department_id);
        $dept_stmt->execute();
        $dept_head_result = $dept_stmt->get_result();
        $dept_head = ($dept_head_result->num_rows > 0) ? $dept_head_result->fetch_assoc() : null;
        if ($dept_head) {
            $dept_head_name = $dept_head['name'];
        }
        $dept_stmt->close();


        if (function_exists('submitForm')) {
            // Based on IPCR, we pass parameters to create/submit.
            $result = submitForm($conn, $user_id, 'IDP', $period, $content_json);
            
            if ($result['success']) {
                $message = "
                    <div class='mb-3'><strong class='fs-5'>IDP form submitted successfully!</strong></div>
                    <div class='d-flex align-items-center mb-2'>
                        <div class='me-3'><i class='fas fa-check-circle text-success fs-3'></i></div>
                        <div>
                            Your form has been sent to <strong>" . htmlspecialchars($dept_head_name) . "</strong> for review. 
                            You will be notified when your submission has been reviewed.
                        </div>
                    </div>";
                $message_type = "success";
                // If submitForm returns a record ID or we can fetch the latest, we could set $record_id here to show the print button immediately.
            } else {
                $message = "Error submitting IDP: " . $result['message'];
                $message_type = "danger";
            }
        } else {
            // Fallback manual submission if function missing
            $status = 'Pending'; // Or 'Submitted'
            if ($record_id > 0) {
                $update_query = "UPDATE records SET content = ?, period = ?, document_status = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssi", $content_json, $period, $status, $record_id);
                $stmt->execute();
            } else {
                $insert_query = "INSERT INTO records (user_id, form_type, period, content, document_status) VALUES (?, 'IDP', ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("isss", $user_id, $period, $content_json, $status);
                $stmt->execute();
                $record_id = $conn->insert_id;
            }
            $message = "<strong>Success!</strong> Your IDP has been submitted to " . htmlspecialchars($dept_head_name) . " for review.";
            $message_type = "success";
        }
    } elseif (isset($_POST['submit_completion_review'])) {
        // Submit for completion review
        if ($record_id > 0) {
            $new_status = 'For Completion Review';
            $update_query = "UPDATE records SET content = ?, period = ?, document_status = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssii", $content_json, $period, $new_status, $record_id, $user_id);

            if ($stmt->execute()) {
                $dept_head_name = "your department head"; // Default value
                
                $dept_head_query = "SELECT u.name FROM users u 
                                    WHERE u.department_id = ? AND u.role = 'department_head'";
                $dept_stmt = $conn->prepare($dept_head_query);
                $dept_stmt->bind_param("i", $user_department_id);
                $dept_stmt->execute();
                $dept_head_result = $dept_stmt->get_result();
                $dept_head = ($dept_head_result->num_rows > 0) ? $dept_head_result->fetch_assoc() : null;
                if ($dept_head) {
                    $dept_head_name = $dept_head['name'];
                }
                $dept_stmt->close();

                $message = "<strong>Success!</strong> Your updated IDP has been submitted to " . htmlspecialchars($dept_head_name) . " for final review.";
                $message_type = "success";
            } else {
                $message = "Error submitting IDP for completion review: " . $conn->error;
                $message_type = "danger";
            }
            $stmt->close();
        } else {
            $message = "Cannot submit for review. Invalid record ID.";
            $message_type = "danger";
        }
    }
}

// --- FETCH IDP HISTORY ---
$idp_history = [];
if ($user_role == 'regular_employee') {
    $history_query = "SELECT * FROM records WHERE user_id = ? AND form_type = 'IDP' ORDER BY date_submitted DESC, date_created DESC";
    $history_stmt = $conn->prepare($history_query);
    $history_stmt->bind_param("i", $user_id);
} else if ($user_role == 'department_head') {
    // A DH sees all IDPs from their department staff
    $history_query = "SELECT r.*, u.name as employee_name FROM records r JOIN users u ON r.user_id = u.id WHERE u.department_id = ? AND r.form_type = 'IDP' ORDER BY r.date_created DESC";
    $history_stmt = $conn->prepare($history_query);
    $history_stmt->bind_param("i", $user_department_id);
} else { // Admin, President see all
    $history_query = "SELECT r.*, u.name as employee_name FROM records r JOIN users u ON r.user_id = u.id WHERE r.form_type = 'IDP' ORDER BY r.date_created DESC";
    $history_stmt = $conn->prepare($history_query);
}

if(isset($history_stmt)) {
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    while ($row = $history_result->fetch_assoc()) {
        $idp_history[] = $row;
    }
    $history_stmt->close();
}

// --- FETCH DATA (If Editing or after Save) ---
$current_record_id = isset($_GET['id']) ? intval($_GET['id']) : ($record_id ?? 0);

// Auto-detect existing submission for current period if no ID provided
if ($current_record_id == 0) {
    $curr_year = date('Y');
    $curr_month = date('n');
    $start_m = ($curr_month <= 6) ? 1 : 7;
    $end_m = ($curr_month <= 6) ? 6 : 12;
    
    $check_sql = "SELECT id FROM records WHERE user_id = ? AND form_type = 'IDP' AND document_status IN ('Approved', 'Pending', 'In Progress', 'Submitted', 'For Review', 'For Completion Review') AND YEAR(date_submitted) = ? AND MONTH(date_submitted) BETWEEN ? AND ? LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iiii", $user_id, $curr_year, $start_m, $end_m);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows > 0) {
        $existing = $check_result->fetch_assoc();
        $current_record_id = $existing['id'];
    }
    $check_stmt->close();
}

$idp_entries = [];
$current_period = '';
$record_status = '';

if ($current_record_id > 0) {
    $fetch_query = "SELECT content, period, document_status FROM records WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($fetch_query);
    $stmt->bind_param("ii", $current_record_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $content_json = $row['content'];
        $content_array = json_decode($content_json, true);
        $idp_entries = $content_array['idp_goals'] ?? [];
        $current_period = $row['period'];
        $record_status = $row['document_status'];
    }
    $stmt->close();
}

// Default empty row if new
if (empty($idp_entries)) {
    $idp_entries[] = ['objective' => '', 'action_plan' => ''];
}

// Determine if we are in Phase 2 (Accomplishment Reporting)
// This happens after the DH's initial acceptance, which sets the status to 'In Progress'.
$is_phase_2 = (($record_status === 'In Progress') || ($record_status === 'For Completion Review'));

// Generate Period Options for Dropdown (Past, Current, Next, Next Year's)
$period_options = [];
$now = time();
$cur_year = date('Y', $now);
$cur_month = date('n', $now);
$cur_half = ($cur_month <= 6) ? 1 : 2;

// Offsets: -1 (Past), 0 (Current), 1 (Next), 2 (Next Year's)
$offsets = [-1, 0, 1, 2];
foreach ($offsets as $offset) {
    $y = $cur_year;
    $h = $cur_half + $offset;
    while ($h < 1) { $h += 2; $y--; }
    while ($h > 2) { $h -= 2; $y++; }
    $period_options[] = ($h == 1) ? "January $y - June $y" : "July $y - December $y";
}
?>
<!-- HTML Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Individual Development Plan</h1>
        <?php if ($current_record_id > 0): ?>
            <a href="print_record.php?id=<?php echo $current_record_id; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-print"></i> Print IDP
            </a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo (!$idp_history || $current_record_id > 0) ? 'active' : ''; ?>" href="#edit-idp" data-bs-toggle="tab">
                        <?php echo ($current_record_id > 0 ? ($is_phase_2 ? 'Update Progress' : 'Edit IDP') : 'Create IDP'); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($idp_history && $current_record_id == 0) ? 'active' : ''; ?>" href="#history" data-bs-toggle="tab">IDP History</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade <?php echo (!$idp_history || $current_record_id > 0) ? 'show active' : ''; ?>" id="edit-idp">
                    
                        <div class="card shadow mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Employee Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6"><p><strong>Employee:</strong> <?php echo htmlspecialchars($employee_name); ?></p></div>
                                    <div class="col-md-6"><p><strong>Department:</strong> <?php echo htmlspecialchars($department_name); ?></p></div>
                                </div>
                                <?php if($record_status): ?>
                                    <p><strong>Status:</strong> <span class="badge bg-<?php echo ($record_status == 'Approved' ? 'success' : (($record_status == 'Pending' || $record_status == 'For Review' || $record_status == 'For Completion Review') ? 'warning' : 'secondary')); ?>"><?php echo $record_status; ?></span></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" action="idp.php<?php echo ($current_record_id > 0 ? '?id='.$current_record_id : ''); ?>">
                            <input type="hidden" name="record_id" value="<?php echo $current_record_id; ?>">
                            <div class="mb-3">
                                <label for="period" class="form-label fw-bold">Period Covered:</label>
                                <?php if ($current_record_id > 0 && $record_status !== 'Draft' && $record_status !== 'Rejected'): ?>
                                    <input type="text" class="form-control" name="period" id="period" value="<?php echo htmlspecialchars($current_period); ?>" readonly>
                                <?php else: ?>
                                    <select class="form-select" name="period" id="period" required>
                                        <option value="">Select Period</option>
                                        <?php foreach ($period_options as $opt): ?>
                                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($current_period === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <?php if ($is_phase_2): ?>
                            <input type="hidden" name="is_phase_2_update" value="1">
                            <div class="alert alert-info border-start border-5 border-info shadow-sm" role="alert">
                                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Accomplishment Phase</h5>
                                <p class="mb-0">Your development plan has been approved. Please update the status of your goals below.</p>
                            </div>
                            <?php endif; ?>

                            <div class="card shadow mt-4">
                                <div class="card-header bg-primary text-white"><h5 class="mb-0">Development Goals (IDP)</h5></div>
                                <div class="card-body p-0 table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Main Objective/s</th>
                                                <th>Plan of Action</th>
                                                <?php if ($is_phase_2): ?>
                                                    <th>Status (is the plan accomplished or not):</th>
                                                    <th>Date Accomplished</th>
                                                    <th>Results of Plan</th>                                                
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody id="idp-entries-body">
                                            <?php foreach ($idp_entries as $index => $entry): ?>
                                                <tr class="idp-entry-row">
                                                    <td><textarea class="form-control" name="main_objectives[]" rows="5" placeholder="Enter objectives here..." <?php echo ($is_phase_2 || ($record_status && $record_status !== 'Draft' && $record_status !== 'Rejected')) ? 'readonly' : ''; ?>><?php echo htmlspecialchars($entry['objective'] ?? ''); ?></textarea></td>
                                                    <td><textarea class="form-control" name="plan_of_action[]" rows="5" placeholder="Enter plan of action here..." <?php echo ($is_phase_2 || ($record_status && $record_status !== 'Draft' && $record_status !== 'Rejected')) ? 'readonly' : ''; ?>><?php echo htmlspecialchars($entry['action_plan'] ?? ''); ?></textarea></td>
                                                    <?php if ($is_phase_2): ?>
                                                        <td>
                                                            <textarea class="form-control" name="status[]" rows="5" placeholder="Enter status (e.g., Accomplished, In Progress) and any relevant details..."><?php echo htmlspecialchars($entry['status'] ?? 'Not Started'); ?></textarea>
                                                        </td>
                                                        <td><input type="text" class="form-control" name="date_accomplished[]" placeholder="e.g., YYYY-MM-DD or Period" value="<?php echo htmlspecialchars($entry['date_accomplished'] ?? ''); ?>"></td>
                                                        <td><textarea class="form-control" name="results[]" rows="5" placeholder="Enter results or outcomes..."><?php echo htmlspecialchars($entry['results'] ?? ''); ?></textarea></td>
                                                    <?php else: ?>
                                                        <input type="hidden" name="status[]" value="Not Started">
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if ($is_phase_2): ?>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" name="save_draft" class="btn btn-secondary btn-lg"><i class="fas fa-save"></i> Save Progress</button>
                                    <button type="submit" name="submit_completion_review" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to submit for final review?');"><i class="fas fa-paper-plane"></i> Submit for Final Review</button>
                                </div>
                            <?php elseif ($current_record_id > 0 && $record_status !== 'Draft' && $record_status !== 'Rejected'): ?>
                                <div class="alert alert-info mt-4">This IDP has been submitted. Status: <strong><?php echo $record_status; ?></strong></div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" name="save_draft" class="btn btn-secondary btn-lg"><i class="fas fa-save"></i> Save as Draft</button>
                                    <button type="submit" name="submit_idp" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to submit this IDP?');"><i class="fas fa-paper-plane"></i> Submit for Review</button>
                                </div>
                            <?php endif; ?>
                        </form>
                </div>
                <div class="tab-pane fade <?php echo ($idp_history) ? 'show active' : ''; ?>" id="history">
                    <div class="table-responsive">
                        <table class="table table-hover">
                             <thead>
                                <tr>
                                    <?php if ($user_role != 'regular_employee'): ?><th>Employee</th><?php endif; ?>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Date Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($idp_history)): ?>
                                    <tr><td colspan="5" class="text-center">No IDP records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($idp_history as $record): ?>
                                    <tr>
                                        <?php if ($user_role != 'regular_employee'): ?><td><?php echo htmlspecialchars($record['employee_name']); ?></td><?php endif; ?>
                                        <td><?php echo htmlspecialchars($record['period']); ?></td>
                                        <td>
                                            <?php 
                                            $status = htmlspecialchars($record['document_status']);
                                            $badge_class = 'bg-secondary';
                                            if ($status === 'Pending' || $status === 'For Review') $badge_class = 'bg-warning text-dark';
                                            if ($status === 'Approved') $badge_class = 'bg-success';
                                            if ($status === 'Rejected') $badge_class = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($record['date_submitted'] ?? $record['date_created'])); ?></td>
                                        <td>
                                            <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i> View</a>
                                            <?php if ($user_role == 'regular_employee' && $record['document_status'] == 'In Progress'): ?>
                                                <a href="idp.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-success me-1"><i class="bi bi-check2-circle"></i> Update Progress</a>
                                            <?php endif; ?>
                                             <?php if ($user_role == 'regular_employee' && ($record['document_status'] == 'Draft' || $record['document_status'] == 'Rejected')): ?>
                                                <a href="idp.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i> Edit</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
?>