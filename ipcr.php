<?php
ob_start();
// Set page title
$page_title = "Individual Performance Commitment and Review - EPMS";

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

// Get department name
$dept_query = "SELECT name FROM departments WHERE id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $user_department_id);
$stmt->execute();
$dept_result = $stmt->get_result();
$department_name = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['name'] : 'Unknown Department';

// =================================================================================
// NEW WORKFLOW LOGIC: Fetch data based on user role
// =================================================================================

$staff_list = [];
$pending_ipcr_for_employee = [];
$ipcr_history = [];

if ($user_role == 'department_head') {
    // DH: Get all staff in their department to distribute forms to
    $staff_query = "SELECT id, name FROM users WHERE department_id = ?";
    $staff_stmt = $conn->prepare($staff_query);
    $staff_stmt->bind_param("i", $user_department_id);
    $staff_stmt->execute();
    $staff_result = $staff_stmt->get_result();
    while ($row = $staff_result->fetch_assoc()) {
        $staff_list[] = $row;
    }
} 

if ($user_role == 'regular_employee' || $user_role == 'department_head') {
    // Employee and DH: Get IPCRs sent by DH that need to be filled out
    $pending_query = "SELECT r.*, u.name as dh_name 
                      FROM records r 
                      JOIN users u ON r.created_by = u.id
                      WHERE r.user_id = ? AND r.form_type = 'IPCR' AND r.document_status = 'Distributed'
                      ORDER BY r.date_created DESC";
    $pending_stmt = $conn->prepare($pending_query);
    $pending_stmt->bind_param("i", $user_id);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    while ($row = $pending_result->fetch_assoc()) {
        $pending_ipcr_for_employee[] = $row;
    }
}

// All roles can see their IPCR history (Submitted, In Review, Approved, etc.)
// For Employees, this shows their own forms. For DH, it shows forms they've created/distributed.
$history_query_sql = "";
if ($user_role == 'department_head') {
    // A DH sees their own IPCR history
    $history_query_sql = "SELECT r.*, u.name as employee_name FROM records r JOIN users u ON r.user_id = u.id WHERE r.user_id = ? AND r.form_type = 'IPCR' ORDER BY r.date_submitted DESC";
    $history_stmt = $conn->prepare($history_query_sql);
    $history_stmt->bind_param("i", $user_id);
} else {
    // An employee sees their own IPCR history
    $history_query_sql = "SELECT r.*, u.name as employee_name FROM records r JOIN users u ON r.user_id = u.id WHERE r.user_id = ? AND r.form_type = 'IPCR' ORDER BY r.date_submitted DESC";
    $history_stmt = $conn->prepare($history_query_sql);
    $history_stmt->bind_param("i", $user_id);
}
$history_stmt->execute();
$history_result = $history_stmt->get_result();
while ($row = $history_result->fetch_assoc()) {
    $ipcr_history[] = $row;
}

// --- Tab Activation Logic ---
$create_distribute_tab_active = false;
$pending_tab_active = false;
$history_tab_active = false;

// Determine default active tab
if ($user_role == 'department_head') {
    // If DH has pending IPCRs, make pending tab active, otherwise, create/distribute tab
    if (count($pending_ipcr_for_employee) > 0) {
        $pending_tab_active = true;
    } else {
        $create_distribute_tab_active = true;
    }
} elseif ($user_role == 'regular_employee') {
    // If employee has pending IPCRs, make pending tab active, otherwise, history tab
    if (count($pending_ipcr_for_employee) > 0) {
        $pending_tab_active = true;
    } else {
        $history_tab_active = true;
    }
}

// Override default if a specific tab is requested via GET
if (isset($_GET['tab'])) {
    $create_distribute_tab_active = false;
    $pending_tab_active = false;
    $history_tab_active = false;

    if ($_GET['tab'] == 'create_distribute') {
        $create_distribute_tab_active = true;
    } elseif ($_GET['tab'] == 'pending') {
        $pending_tab_active = true;
    } elseif ($_GET['tab'] == 'history') {
        $history_tab_active = true;
    }
}


function getComputationTypes() {
    return [
        'Type1' => 'Strategic (45%) and Core (55%)',
        'Type2' => 'Strategic (45%), Core (45%), and Support (10%)'
    ];
}

// --- NEW/MODIFIED: Initial setup for dynamic IPCR data ---
$ipcr_data = []; // Will hold loaded data if editing a draft (not fully implemented in the current file but necessary for logic)
$computation_type = 'Type1'; // Default type
$strategic_entries = [['mfo' => '', 'success_indicators' => '', 'accomplishments' => '']]; // Default one row for Strategic
$core_entries = [['mfo' => '', 'success_indicators' => '', 'accomplishments' => '']]; // Default one row for Core
$support_entries = [];

// Determine initial weights for display
$strategic_weight_display = '(45%)';
$core_weight_display = '(55%)';
$support_display_style = 'none';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['distribute_ipcr'])) {
    if ($user_role == 'department_head') {
        $period = $_POST['period'];
        $content = $_POST['content'] ?? '';
        
        // Basic validation
        if (empty($period) || empty($content)) {
            $error_message = "Evaluation Period and form content cannot be empty.";
        } else {
            $decoded_content = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE || (empty($decoded_content['strategic_functions']) && empty($decoded_content['core_functions']))) {
                $error_message = "Invalid form data. Please fill out at least one MFO.";
            } else {
                $distributed_count = 0;
                $error_count = 0;
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Define status in a variable and use a placeholder
                    $status_distributed = 'Distributed';
                    $insert_query = "INSERT INTO records (user_id, form_type, period, content, document_status, created_by, date_created) VALUES (?, 'IPCR', ?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($insert_query);
                    
                    if (!$stmt) {
                        throw new Exception("Failed to prepare statement: " . $conn->error);
                    }

                    foreach ($staff_list as $staff) {
                        $staff_id = $staff['id'];
                        // Bind the status variable to the new placeholder
                        $stmt->bind_param("isssi", $staff_id, $period, $content, $status_distributed, $user_id);
                        
                        if ($stmt->execute()) {
                            $distributed_count++;
                        } else {
                            $error_count++;
                        }
                    }
                    
                    // If all successful, commit the transaction
                    if ($error_count === 0 && $distributed_count > 0) {
                        $conn->commit();
                        $_SESSION['success_message'] = "Successfully distributed IPCR forms to " . $distributed_count . " staff member(s).";
                        header("Location: staff_ipcr.php");
                        exit();
                    } else {
                        // Otherwise, roll back
                        $conn->rollback();
                        if ($distributed_count === 0) {
                             $error_message = "Could not distribute any IPCR forms. Please ensure you have staff in your department.";
                        } else {
                             $error_message = "An error occurred. Distributed to " . $distributed_count . " staff, but failed for " . $error_count . ". The transaction has been rolled back.";
                        }
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "A database error occurred during distribution: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!-- IPCR Content -->
<div class="container-fluid py-4">
    <style>
        /* --- IPCR Form Table Enhancements for Readability and Spacing --- */

        /* Targets ALL input fields inside table cells in the IPCR body */
        #ipcr-table-body td input.form-control,
        #ipcr-table-body td textarea.form-control {
            /* Make the input background white for better contrast */
            background-color: #ffffff;
            /* Explicitly define a visible border that contrasts with the table border */
            border: 1px solid #c9c9c9; 
            /* Slightly increase vertical padding for more space inside the input */
            padding: 0.25rem 0.5rem;
            /* Important: Ensure the input fills the cell height */
            height: 100%;
            /* Use box-sizing to prevent padding/border from making the input overflow */
            box-sizing: border-box; 
            /* Remove default shadow if any */
            box-shadow: none; 
        }

        /* Specific styling for the small rating number boxes (Q, E, T) */
        #ipcr-table-body tr input.rating-input {
            text-align: center; /* Center the numbers for a cleaner look */
            border-radius: 0.15rem; 
            font-weight: 500;
        }

        /* Style for readonly fields (Averages, Supervisor Ratings) */
        #ipcr-table-body tr input[readonly] {
            background-color: #f8f9fa; /* A very light grey for visual distinction */
            cursor: not-allowed;
            color: #495057; /* Slightly darker text for readability */
        }

        /* Increase vertical padding on table cells themselves for better row spacing */
        #ipcr-table-body tr td {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            vertical-align: top; /* Align content to the top of the (now taller) cell */
        }

        /* Style for the remove button cell */
        .remove-row-cell {
            vertical-align: middle !important;
            padding-left: 0.2rem !important;
            padding-right: 0.2rem !important;
            text-align: center;
        }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
        }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Individual Performance Commitment and Review</h1>
        <div>
            <button class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-calendar"></i> 
                <?php echo date('F d, Y'); ?>
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
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs">
                <?php if ($user_role == 'department_head'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $create_distribute_tab_active ? 'active' : ''; ?>" href="#new-form" data-bs-toggle="tab">Create & Distribute IPCR</a>
                    </li>
                <?php endif; ?>
                <?php if ($user_role == 'department_head' || $user_role == 'regular_employee'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $pending_tab_active ? 'active' : ''; ?>" href="#pending-forms" data-bs-toggle="tab">Pending IPCRs
                            <?php if (count($pending_ipcr_for_employee) > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo count($pending_ipcr_for_employee); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $history_tab_active ? 'active' : ''; ?>" href="#history" data-bs-toggle="tab">IPCR History</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <?php if ($user_role == 'department_head'): ?>
                <div class="tab-pane fade <?php echo $create_distribute_tab_active ? 'show active' : ''; ?>" id="new-form">
                    <form action="ipcr.php" method="POST" id="ipcr-form">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="period" class="form-label">Evaluation Period</label>
                                <select class="form-select" name="period" id="period" required>
                                    <option value="">Select Period</option>
                                    <option value="Q1 <?php echo date('Y'); ?>">Q1 (January-March) <?php echo date('Y'); ?></option>
                                    <option value="Q2 <?php echo date('Y'); ?>">Q2 (April-June) <?php echo date('Y'); ?></option>
                                    <option value="Q3 <?php echo date('Y'); ?>">Q3 (July-September) <?php echo date('Y'); ?></option>
                                    <option value="Q4 <?php echo date('Y'); ?>">Q4 (October-December) <?php echo date('Y'); ?></option>
                                    <option value="Annual <?php echo date('Y'); ?>">Annual <?php echo date('Y'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="computation_type" class="form-label">Weight Distribution</label>
                                <select class="form-select" name="computation_type" id="computation_type" required>
                                    <?php $computation_types_list = getComputationTypes();
                                    foreach ($computation_types_list as $type => $description) {
                                        $selected = ($computation_type === $type) ? 'selected' : '';
                                        echo "<option value=\"$type\" $selected>$description</option>";
                                    } ?>
                                </select>
                                <div class="form-text">Type 2 is required if Support Functions are included.</div>
                            </div>
                        </div>
                        
                        <h5 class="text-center fw-bold my-4">IPCR TEMPLATE</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered mb-4">
                                <thead class="table-light">
                                    <tr>
                                        <th class="align-middle text-center" style="width: 35%">MAJOR FINAL OUTPUT (MFO)</th>
                                        <th class="align-middle text-center" style="width: 35%">SUCCESS INDICATORS (Targets + Measures)</th>
                                        <th class="align-middle text-center" style="width: 20%">REMARKS</th>
                                        <th class="align-middle text-center" style="width: 10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="ipcr-table-body">
                                    
                                    <tr>
                                        <td colspan="3" class="text-start bg-light fw-bold">
                                            I. STRATEGIC FUNCTIONS <span id="strategic_weight" class="float-end"><?php echo $strategic_weight_display; ?></span>
                                        </td>
                                        <td class="bg-light text-center">
                                            <button type="button" class="btn btn-sm btn-success add-row-btn" data-category="strategic" title="Add Row">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="function-row strategic-function-row">
                                        <td><textarea class="form-control form-control-sm" name="strategic_mfo[]"></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="strategic_success_indicators[]"></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="strategic_remarks[]"></textarea></td>
                                        <td class="remove-row-cell">
                                            <button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remove Row"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <td colspan="3" class="text-start bg-light fw-bold">
                                            II. CORE FUNCTIONS <span id="core_weight" class="float-end"><?php echo $core_weight_display; ?></span>
                                        </td>
                                        <td class="bg-light text-center">
                                            <button type="button" class="btn btn-sm btn-success add-row-btn" data-category="core" title="Add Row">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="function-row core-function-row">
                                        <td><textarea class="form-control form-control-sm" name="core_mfo[]"></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="core_success_indicators[]"></textarea></td>
                                        <td><textarea class="form-control form-control-sm" name="core_remarks[]"></textarea></td>
                                        <td class="remove-row-cell">
                                            <button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remove Row"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>

                                    <tr class="support-functions-header" style="display: none;">
                                        <td colspan="3" class="text-start bg-light fw-bold">
                                            III. SUPPORT FUNCTIONS <span id="support_weight" class="float-end">(10%)</span>
                                        </td>
                                        <td class="bg-light text-center">
                                            <button type="button" class="btn btn-sm btn-success add-row-btn" data-category="support" title="Add Row">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tbody id="support_functions_rows" style="display: none;">
                                    </tbody>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" name="distribute_ipcr">
                                <i class="bi bi-send me-1"></i> Distribute to All Staff
                            </button>
                        </div>
                        <input type="hidden" name="content" id="form-content">
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($user_role == 'department_head' || $user_role == 'regular_employee'): ?>
                <div class="tab-pane fade <?php echo $pending_tab_active ? 'show active' : ''; ?>" id="pending-forms">
                    <h5 class="mb-3">IPCR Forms to Fill Out</h5>
                    <div class="list-group">
                        <?php if (count($pending_ipcr_for_employee) > 0): ?>
                            <?php foreach ($pending_ipcr_for_employee as $record): ?>
                                <a href="fill_ipcr.php?id=<?php echo $record['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">IPCR for <?php echo htmlspecialchars($record['period']); ?></h6>
                                        <small>Distributed by: <?php echo htmlspecialchars($record['dh_name']); ?> on <?php echo date('M d, Y', strtotime($record['date_created'])); ?></small>
                                    </div>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">You have no pending IPCR forms to fill out.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="tab-pane fade <?php echo $history_tab_active ? 'show active' : ''; ?>" id="history">
                    <div class="table-responsive">
                        <table class="table table-hover">
                             <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Date Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($ipcr_history) > 0): ?>
                                    <?php foreach ($ipcr_history as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['period']); ?></td>
                                        <td>
                                            <?php 
                                            $status = htmlspecialchars($record['document_status']);
                                            $badge_class = 'bg-secondary';
                                            if ($status === 'Pending' || $status === 'For Review') $badge_class = 'bg-warning text-dark';
                                            if ($status === 'Approved') $badge_class = 'bg-success';
                                            if ($status === 'Rejected') $badge_class = 'bg-danger';
                                            if ($status === 'Distributed') $badge_class = 'bg-info text-dark';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td><?php echo $record['date_submitted'] ? date('M d, Y', strtotime($record['date_submitted'])) : date('M d, Y', strtotime($record['date_created'])); ?></td>
                                        <td>
                                            <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i> View</a>
                                            <?php
                                            $can_edit = false;

                                            // Check if user is a Dept Head AND the record does NOT belong to them
                                            if ($user_role == 'department_head' && $record['user_id'] != $user_id) {
                                                if (in_array($record['document_status'], ['Distributed', 'For Review', 'Rejected'])) {
                                                    $can_edit = true;
                                                }
                                            } 
                                            // Regular employees can only edit if Rejected and it IS their own record
                                            elseif ($user_role == 'regular_employee' && $record['document_status'] == 'Rejected' && $record['user_id'] == $user_id) {
                                                $can_edit = true;
                                            }
                                            ?>

                                            <?php if ($can_edit): ?>
                                                <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i> Edit</a>
                                            <?php endif; ?>

                                            <a href="print_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-printer"></i> Print</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No IPCR records found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
    // --- Dynamic Row Template Function for DH Form ---
    function getNewRowTemplate(category) {
        const cat_prefix = category.toLowerCase();
        return `
        <tr class="function-row ${cat_prefix}-function-row" data-category="${cat_prefix}">
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_mfo[]"></textarea></td>
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_success_indicators[]"></textarea></td>
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_remarks[]"></textarea></td>
            <td class="remove-row-cell">
                <button type="button" class="btn btn-danger btn-sm remove-row-btn" title="Remove Row"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
        `;
    }

    // --- Computation Type Logic (Update Weights and Support Visibility) ---
    function updateComputationTypeDisplay(computationType) {
        if (computationType === 'Type2') {
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(45%)');
            $('.support-functions-header, #support_functions_rows').show();
            if ($('#support_functions_rows').children('.support-function-row').length === 0) {
                $('#support_functions_rows').append(getNewRowTemplate('support'));
            }
        } else { // Type1
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(55%)');
            $('.support-functions-header, #support_functions_rows').hide();
        }
    }
    
    $(document).ready(function() {
        
        // --- 1. Dynamic Row Handling (Add/Remove) ---
        $(document).on('click', '.add-row-btn', function() {
            const category = $(this).data('category');
            const newRowHtml = getNewRowTemplate(category);
            
            if (category === 'support') {
                $('#support_functions_rows').append(newRowHtml);
            } else {
                const lastRow = $(`tr.${category}-function-row`).last();
                lastRow.after(newRowHtml);
            }
        });

        $(document).on('click', '.remove-row-btn', function() {
            const rowToRemove = $(this).closest('tr.function-row');
            const category = rowToRemove.data('category');
            
            if ($(`tr.${category}-function-row`).length > 1) {
                rowToRemove.remove();
            } else {
                alert(`You must have at least one entry for ${category.charAt(0).toUpperCase() + category.slice(1)} functions.`);
            }
        });

        // --- 2. Conditional Computation Type Display ---
        updateComputationTypeDisplay($('#computation_type').val());
        
        $('#computation_type').on('change', function() {
            updateComputationTypeDisplay($(this).val());
        });
        
        // --- 3. Form Submission Serialization for DH ---
        $('#ipcr-form').on('submit', function(e) {
            const formData = {
                period: $('#period').val(),
                computation_type: $('#computation_type').val(),
                strategic_functions: [],
                core_functions: [],
                support_functions: []
            };

            function getRowData(category) {
                let data = [];
                $(`tr.${category}-function-row`).each(function(index, row) {
                    const mfo = $(row).find(`textarea[name="${category}_mfo[]"]`).val().trim();
                    const indicators = $(row).find(`textarea[name="${category}_success_indicators[]"]`).val().trim();
                    
                    // Only include rows that have at least an MFO
                    if (mfo !== "" || indicators !== "") {
                        data.push({
                            mfo: mfo,
                            success_indicators: indicators,
                            remarks: $(row).find(`textarea[name="${category}_remarks[]"]`).val().trim(),
                            // Add empty fields for employee/rating data to maintain consistent JSON structure
                            accomplishments: '',
                            q: '', e: '', t: '', a: '',
                            supervisor_q: '', supervisor_e: '', supervisor_t: '', supervisor_a: ''
                        });
                    }
                });
                return data;
            }

            formData.strategic_functions = getRowData('strategic');
            formData.core_functions = getRowData('core');
            if ($('#computation_type').val() === 'Type2') {
                formData.support_functions = getRowData('support');
            }

            if (formData.strategic_functions.length === 0 && formData.core_functions.length === 0) {
                 e.preventDefault();
                 alert("The template must have at least one entry for either Strategic or Core functions.");
                 return;
            }

            $('#form-content').val(JSON.stringify(formData));
        });
    });
</script>

<?php
// Close database connection
$conn->close();

// Include footer
include_once('includes/footer.php');
ob_end_flush();
?> 