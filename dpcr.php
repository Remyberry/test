<?php
// Start output buffering at the very beginning of the file
ob_start();

// Set page title
$page_title = "Department Performance Commitment and Review - EPMS";

// Include header
include_once('includes/header.php');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'department_head' && $_SESSION['user_role'] != 'admin')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get user info
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['user_department_id'];

// Get department info
$dept_query = "SELECT d.*, u.name as head_name 
               FROM departments d 
               LEFT JOIN users u ON d.head_id = u.id
               WHERE d.id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$dept_result = $stmt->get_result();
$department = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc() : null;
$stmt->close();

// Get action from URL
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Function to generate periods (Semi-annual)
function generatePeriods() {
    $current_year = date('Y');
    $periods = [];
    
    // Generate semi-annual periods for the current year and the next year
    for ($i = 0; $i < 2; $i++) {
        $year = $current_year + $i;
        $periods[] = "January-June $year";
        $periods[] = "July-December $year";
    }
    
    return $periods;
}

// Get computation types from database (or hardcoded defaults if not in DB)
function getComputationTypes() {
    // Updated descriptions to reflect weight changes for Type 2
    return [
        'Type1' => 'Strategic (45%) and Core (55%)',
        'Type2' => 'Strategic (45%), Core (45%), and Support (10%)'
    ];
}

// Handle form submission (Commitment and Review)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_dpcr'])) {

    // Validate that a Performance Period is selected
    $period = $_POST['period'] ?? '';
    if (empty($period)) {
        $_SESSION['error_message'] = "Please select a Performance Period before submitting.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // --- START: NEW NESTED VALIDATION ---
    function validateDPCREntries_Nested($category, $post_data, &$error_message) {
        $cat_lower = strtolower($category);
        if (!isset($post_data[$cat_lower]) || !is_array($post_data[$cat_lower])) {
            // Not an error if the category is not required (e.g., optional Support)
            return true;
        }

        $has_at_least_one_complete_mfo = false;

        foreach ($post_data[$cat_lower] as $mfo_index => $mfo_data) {
            $major_output = trim($mfo_data['major_output'] ?? '');
            $indicators = $mfo_data['indicators'] ?? [];

            // Silently skip MFO blocks that are completely empty
            if (empty($major_output) && empty($indicators)) {
                continue;
            }

            if (empty($major_output)) {
                $error_message = "In $category Functions, one of the Major Final Output blocks is missing its title text.";
                return false;
            }

            if (empty($indicators)) {
                $error_message = "In $category Functions, the MFO titled '" . htmlspecialchars($major_output) . "' must have at least one Success Indicator.";
                return false;
            }
            
            $has_at_least_one_complete_indicator = false;
            foreach ($indicators as $ind_index => $ind_data) {
                $indicator_text = trim($ind_data['success_indicators'] ?? '');
                $accountable_text = trim($ind_data['accountable'] ?? '');
                
                // If any part of an indicator is filled, its core parts must be filled.
                if (!empty($indicator_text) || !empty($accountable_text) || !empty($ind_data['budget'])) {
                     if (empty($indicator_text) || empty($accountable_text)) {
                        $error_message = "In $category Functions, under MFO '" . htmlspecialchars($major_output) . "', indicator row #" . ($ind_index + 1) . " is incomplete. Please fill both 'Success Indicator' and 'Accountable' or clear the row.";
                        return false;
                     }
                     $has_at_least_one_complete_indicator = true;
                }
            }

            if (!$has_at_least_one_complete_indicator) {
                 $error_message = "In $category Functions, the MFO titled '" . htmlspecialchars($major_output) . "' must have at least one complete Success Indicator row. A row is considered complete if 'Success Indicator' and 'Accountable' are filled.";
                 return false;
            }
            
            $has_at_least_one_complete_mfo = true;
        }

        if (!$has_at_least_one_complete_mfo) {
            $error_message = "You must have at least one complete entry for $category Functions. An entry requires a Major Final Output and at least one complete indicator.";
            return false;
        }

        return true;
    }

    $validation_error = '';
    $computation_type_val = $_POST['computation_type'] ?? 'Type1';

    $redirect_url = 'dpcr.php?action=new';
    if (!empty($_GET['id'])) {
        $redirect_url = 'dpcr.php?action=edit&id=' . intval($_GET['id']);
    }

    if (!validateDPCREntries_Nested('Strategic', $_POST, $validation_error) || !validateDPCREntries_Nested('Core', $_POST, $validation_error)) {
        $_SESSION['error_message'] = $validation_error;
        header("Location: " . $redirect_url);
        exit();
    }
    if ($computation_type_val === 'Type2' && !validateDPCREntries_Nested('Support', $_POST, $validation_error)) {
        $_SESSION['error_message'] = $validation_error;
        header("Location: " . $redirect_url);
        exit();
    }
    // --- END: NEW NESTED VALIDATION ---
    
    $period = $_POST['period'] ?? '';
    $status = $_POST['document_status'] ?? 'Draft';
    $computation_type = $_POST['computation_type'] ?? 'Type1';
    
    // Handle date submission
    $date_submitted = null;
    if ($status === 'Submitted' || $status === 'Pending') {
        $date_submitted = date('Y-m-d H:i:s');
    }

    // --- START: NEW NESTED DATA COLLECTION ---
    $collectDpcrEntries_Nested = function($category, $post_data) {
        $cat_lower = strtolower($category);
        if (!isset($post_data[$cat_lower]) || !is_array($post_data[$cat_lower])) {
            return [];
        }

        $mfo_entries = [];
        foreach ($post_data[$cat_lower] as $mfo_data) {
            $major_output = trim($mfo_data['major_output'] ?? '');
            if (empty($major_output)) {
                continue; // Skip MFO if the output text is empty
            }

            $indicator_entries = [];
            if (isset($mfo_data['indicators']) && is_array($mfo_data['indicators'])) {
                foreach ($mfo_data['indicators'] as $ind_data) {
                    $indicator_text = trim($ind_data['success_indicators'] ?? '');
                    if(empty($indicator_text)) {
                        continue; // Skip indicator if its text is empty
                    }

                    $q_rating = !empty($ind_data['q_rating']) ? floatval($ind_data['q_rating']) : null;
                    $e_rating = !empty($ind_data['e_rating']) ? floatval($ind_data['e_rating']) : null;
                    $t_rating = !empty($ind_data['t_rating']) ? floatval($ind_data['t_rating']) : null;
                    $a_rating = ($q_rating !== null && $e_rating !== null && $t_rating !== null) ? round(($q_rating + $e_rating + $t_rating) / 3, 2) : null;
                    
                    $indicator_entries[] = [
                        'success_indicators' => $indicator_text,
                        'budget' => !empty($ind_data['budget']) ? floatval($ind_data['budget']) : null,
                        'accountable' => trim($ind_data['accountable'] ?? ''),
                        'actual_accomplishments' => trim($ind_data['actual_accomplishments'] ?? ''),
                        'q_rating' => $q_rating,
                        'e_rating' => $e_rating,
                        't_rating' => $t_rating,
                        'a_rating' => $a_rating,
                        'remarks' => trim($ind_data['remarks'] ?? '')
                    ];
                }
            }
            
            if (!empty($indicator_entries)) {
                $mfo_entries[] = [
                    'major_output' => $major_output,
                    'indicators' => $indicator_entries
                ];
            }
        }
        return $mfo_entries;
    };

    $strategic_functions = $collectDpcrEntries_Nested('strategic', $_POST);
    $core_functions = $collectDpcrEntries_Nested('core', $_POST);
    $support_functions = ($computation_type === 'Type2') ? $collectDpcrEntries_Nested('support', $_POST) : [];

    $dpcr_content_array = [
        'computation_type' => $computation_type,
        'strategic_functions' => $strategic_functions,
        'core_functions' => $core_functions,
        'support_functions' => $support_functions,
    ];
    $content_json = json_encode($dpcr_content_array, JSON_UNESCAPED_UNICODE);
    // --- END: NEW NESTED DATA COLLECTION ---

    $conn->begin_transaction();
    try {
        if ($record_id > 0) {
            $update_record = "UPDATE records SET period = ?, document_status = ?, date_submitted = ?, computation_type = ?, content = ? WHERE id = ?";
            $stmt = $conn->prepare($update_record);
            $stmt->bind_param("sssssi", $period, $status, $date_submitted, $computation_type, $content_json, $record_id);
        } else {
            $form_type = 'DPCR';
            $insert_record = "INSERT INTO records (user_id, form_type, period, document_status, date_submitted, computation_type, content) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_record);
            $stmt->bind_param("issssss", $user_id, $form_type, $period, $status, $date_submitted, $computation_type, $content_json);
        }
        $stmt->execute();
        if ($record_id === 0) {
            $record_id = $conn->insert_id;
        }
        $stmt->close();
        
        $conn->commit();
        
        $message = ($status === 'Submitted' || $status === 'Pending') ? 'DPCR submitted successfully!' : 'DPCR saved as draft!';
        $_SESSION['success_message'] = $message;
        
        header("Location: dpcr.php?action=view&id=" . $record_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        $redirect_id = $record_id > 0 ? $record_id : '';
        header("Location: dpcr.php?action=edit&id=" . $redirect_id);
        exit();
    }
}

// Load existing DPCR data if editing or viewing
$dpcr_data = [];
$strategic_functions = [];
$core_functions = [];
$support_functions = [];
$is_legacy_data = false; // Flag to identify old, flat data structure

if ($record_id > 0) {
    // Get record data
    $record_query = "SELECT *, content FROM records WHERE id = ? AND form_type = 'DPCR'";
    $stmt = $conn->prepare($record_query);
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $record_result = $stmt->get_result();
    
    if ($record_result->num_rows > 0) {
        $dpcr_data = $record_result->fetch_assoc();
        
        // Decode the JSON content
        $content_array = json_decode($dpcr_data['content'], true);
        
        if ($content_array === null) {
             // Handle JSON decode error if necessary, default to empty arrays
            $strategic_functions = [];
            $core_functions = [];
            $support_functions = [];
            $dpcr_data['computation_type'] = 'Type1'; 
        } else {
            // NEW: Check for new vs. old data structure.
            // The new structure has 'indicators' as a key in the first element.
            // The old structure has 'success_indicators'.
            if (isset($content_array['strategic_functions'][0]['indicators']) || isset($content_array['core_functions'][0]['indicators']) || isset($content_array['support_functions'][0]['indicators'])) {
                // This is the new nested structure
                $strategic_functions = $content_array['strategic_functions'] ?? [];
                $core_functions = $content_array['core_functions'] ?? [];
                $support_functions = $content_array['support_functions'] ?? [];
            } else if (!empty($content_array['strategic_functions']) || !empty($content_array['core_functions']) || !empty($content_array['support_functions'])) {
                // This is the OLD flat structure, transform it for read-only viewing.
                $is_legacy_data = true;
                $transform_legacy = function($entries) {
                    if (empty($entries)) return [];
                    $grouped = [];
                    // Group by major_output since it was repeated
                    foreach ($entries as $entry) {
                        $mfo = $entry['major_output'] ?? 'Uncategorized';
                        if (!isset($grouped[$mfo])) {
                            $grouped[$mfo] = [
                                'major_output' => $mfo,
                                'indicators' => []
                            ];
                        }
                        $grouped[$mfo]['indicators'][] = $entry;
                    }
                    return array_values($grouped);
                };
                $strategic_functions = $transform_legacy($content_array['strategic_functions'] ?? []);
                $core_functions = $transform_legacy($content_array['core_functions'] ?? []);
                $support_functions = $transform_legacy($content_array['support_functions'] ?? []);
            }

            $dpcr_data['computation_type'] = $content_array['computation_type'] ?? 'Type1';
        }
        
        // Check permissions and status
        if ($action === 'edit' && $dpcr_data['user_id'] != $user_id && $_SESSION['user_role'] !== 'admin') {
            $_SESSION['error_message'] = "You don't have permission to edit this DPCR!";
            header("Location: records.php");
            exit();
        }
        if ($action === 'edit' && $is_legacy_data) {
            $_SESSION['error_message'] = "This DPCR uses an outdated data format and can no longer be edited. You can view it or create a new one.";
            $action = 'view'; // Force to view mode
        }
        if ($action === 'edit' && $dpcr_data['document_status'] !== 'Draft') {
            $_SESSION['error_message'] = "Only drafts can be edited!";
            header("Location: dpcr.php?action=view&id=" . $record_id);
            exit();
        }
    } else {
        // Record ID provided but not found or not DPCR for this department
        $action = 'new';
        $record_id = 0;
    }
}

// NEW: If no record ID is provided on initial load, default to 'new' action
if ($record_id === 0) {
    $action = 'new';
}


// Initialize default variables for display if not set (to prevent "Undefined variable" warnings)
$period = $dpcr_data['period'] ?? '';
$status = $dpcr_data['document_status'] ?? 'Draft';
$computation_type = $dpcr_data['computation_type'] ?? 'Type1';

// --- Fetch Sent DPCR History ---
$sent_dpcr_history = [];
$history_query = "SELECT r.*, u.name as employee_name FROM records r JOIN users u ON r.user_id = u.id WHERE r.form_type = 'DPCR' ORDER BY r.date_created DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
while ($row = $history_result->fetch_assoc()) {
    $sent_dpcr_history[] = $row;
}
$history_stmt->close();


// --- Tab Activation Logic ---
$form_tab_active = false;
$history_tab_active = false;

// Determine active tab on page load
// Prioritize 'new' or 'edit' actions for the form tab
if ($action === 'new' || $action === 'edit' || (isset($_GET['id']) && !isset($_GET['tab']))) {
    $form_tab_active = true;
} elseif (isset($_GET['tab']) && $_GET['tab'] === 'history') { // Explicitly request history tab
    $history_tab_active = true;
} else {
    // Default to history tab if no specific form action/ID and no 'tab' param explicitly set to form
    $history_tab_active = true;
}


// Determine initial weights for display
$strategic_weight_display = ($computation_type === 'Type2') ? '(45%)' : '(45%)';
$core_weight_display = ($computation_type === 'Type2') ? '(45%)' : '(55%)';
$support_display_style = ($computation_type === 'Type2') ? 'block' : 'none';

// Helper for creating an empty indicator
function get_empty_indicator() {
    return ['success_indicators' => '', 'budget' => '', 'accountable' => '', 'actual_accomplishments' => '', 'q_rating' => '', 'e_rating' => '', 't_rating' => '', 'remarks' => ''];
}

// Helper for creating an empty MFO
function get_empty_mfo() {
    return ['major_output' => '', 'indicators' => [get_empty_indicator()]];
}


// Ensure we have at least one empty entry for each category when creating/editing
if ($action === 'new' || $action === 'edit') {
    if (empty($strategic_functions)) {
        $strategic_functions[] = get_empty_mfo();
    }
    if (empty($core_functions)) {
        $core_functions[] = get_empty_mfo();
    }
    if (empty($support_functions) && $computation_type === 'Type2') {
         $support_functions[] = get_empty_mfo();
    }
}


// Override defaults for new record
if ($action === 'new') {
    $periods = generatePeriods();
    $period = $periods[0] ?? date('Y') . ' Semester 1'; // Updated default
    $status = 'Draft';
    $computation_type = 'Type1';
    
    // Reset display based on new default
    $strategic_weight_display = '(45%)';
    $core_weight_display = '(55%)';
    $support_display_style = 'none';
}

// Check if we have success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// --- NEW RENDERING FUNCTIONS ---

/**
 * Generates the HTML for a single Success Indicator row within a Major Final Output block.
 */
function generateIndicatorRowHtml($indicator, $category, $mfo_index, $ind_index, $action) {
    $cat_prefix = strtolower($category);
    $is_view = $action === 'view';
    $name_prefix = "{$cat_prefix}[{$mfo_index}][indicators][{$ind_index}]";

    $has_review_data = !empty($indicator['actual_accomplishments']) || !empty($indicator['q_rating']) || !empty($indicator['e_rating']) || !empty($indicator['t_rating']) || !empty($indicator['remarks']);
    $show_review = !$is_view || $has_review_data;

    $html = '<div class="indicator-row ' . ($is_view ? '' : 'p-2 border-start border-primary border-3') . ' mb-3">';
    $html .= '<div class="row g-2">';

    // --- Commitment Fields ---
    $html .= '<div class="col-md-5"><label class="form-label">Success Indicator</label>';
    $html .= $is_view ? nl2br(htmlspecialchars($indicator['success_indicators'] ?? '')) : '<textarea class="form-control" name="' . $name_prefix . '[success_indicators]" rows="2" required>' . htmlspecialchars($indicator['success_indicators'] ?? '') . '</textarea>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-2"><label class="form-label">Budget</label>';
    $budget_display = isset($indicator['budget']) && is_numeric($indicator['budget']) ? number_format((float)$indicator['budget'], 2) : 'N/A';
    $html .= $is_view ? $budget_display : '<input type="number" class="form-control" name="' . $name_prefix . '[budget]" step="1" value="' . htmlspecialchars($indicator['budget'] ?? '') . '">';
    $html .= '</div>';

    $html .= '<div class="col-md-4"><label class="form-label">Accountable</label>';
    $html .= $is_view ? htmlspecialchars($indicator['accountable'] ?? '') : '<input type="text" class="form-control" name="' . $name_prefix . '[accountable]" value="' . htmlspecialchars($indicator['accountable'] ?? '') . '" required>';
    $html .= '</div>';
    
    if (!$is_view) {
        $html .= '<div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-danger btn-sm w-100 remove-indicator-row"><i class="bi bi-trash"></i></button></div>';
    }

    // --- Review Fields ---
    if ($show_review) {
        $html .= '<div class="col-12"><hr class="my-2" style="border-style: dashed;"></div>';
        $html .= '<div class="col-md-5"><label class="form-label text-success">Actual Accomplishments</label>';
        $html .= $is_view ? nl2br(htmlspecialchars($indicator['actual_accomplishments'] ?? '')) : '<textarea class="form-control" name="' . $name_prefix . '[actual_accomplishments]" rows="2">' . htmlspecialchars($indicator['actual_accomplishments'] ?? '') . '</textarea>';
        $html .= '</div>';

        $html .= '<div class="col-md-3"><label class="form-label text-success">Ratings (Q/E/T)</label><div class="input-group">';
        if ($is_view) {
            $html .= '<span class="form-control text-center">Q: <strong>' . htmlspecialchars($indicator['q_rating'] ?? '-') . '</strong></span>';
            $html .= '<span class="form-control text-center">E: <strong>' . htmlspecialchars($indicator['e_rating'] ?? '-') . '</strong></span>';
            $html .= '<span class="form-control text-center">T: <strong>' . htmlspecialchars($indicator['t_rating'] ?? '-') . '</strong></span>';
        } else {
            $html .= '<input type="number" class="form-control" name="' . $name_prefix . '[q_rating]" placeholder="Q" step="1" min="1" max="5" value="' . htmlspecialchars($indicator['q_rating'] ?? '') . '">';
            $html .= '<input type="number" class="form-control" name="' . $name_prefix . '[e_rating]" placeholder="E" step="1" min="1" max="5" value="' . htmlspecialchars($indicator['e_rating'] ?? '') . '">';
            $html .= '<input type="number" class="form-control" name="' . $name_prefix . '[t_rating]" placeholder="T" step="1" min="1" max="5" value="' . htmlspecialchars($indicator['t_rating'] ?? '') . '">';
        }
        $html .= '</div>';
        if ($is_view) {
             $html .= '<div class="mt-1 text-center bg-light border p-1">Average (A): <strong>' . htmlspecialchars($indicator['a_rating'] ?? '-') . '</strong></div>';
        }
        $html .= '</div>';

        $html .= '<div class="col-md-4"><label class="form-label text-success">Remarks</label>';
        $html .= $is_view ? nl2br(htmlspecialchars($indicator['remarks'] ?? '')) : '<textarea class="form-control" name="' . $name_prefix . '[remarks]" rows="2">' . htmlspecialchars($indicator['remarks'] ?? '') . '</textarea>';
        $html .= '</div>';
    }
    
    $html .= '</div></div>'; // end .row and .indicator-row
    return $html;
}

/**
 * Generates the HTML for a Major Final Output block, including its indicators.
 */
function generateMajorOutputHtml($mfo_entry, $category, $mfo_index, $action) {
    $is_view = $action === 'view';
    $cat_prefix = strtolower($category);
    $name_prefix = "{$cat_prefix}[{$mfo_index}]";

    $html = '<div class="mfo-block card card-body mb-4 shadow-sm">';
    
    // MFO Header
    $html .= '<div class="d-flex justify-content-between align-items-start">';
    $html .= '<div class="flex-grow-1">';
    $html .= '<label class="form-label fw-bold text-primary">Major Final Output</label>';
    $html .= $is_view ? '<h4>' . nl2br(htmlspecialchars($mfo_entry['major_output'] ?? '')) . '</h4>' : '<textarea class="form-control" name="' . $name_prefix . '[major_output]" rows="2" required>' . htmlspecialchars($mfo_entry['major_output'] ?? '') . '</textarea>';
    $html .= '</div>';
    if (!$is_view) {
         $html .= '<button type="button" class="btn btn-outline-danger ms-3 remove-mfo-block"><i class="bi bi-trash-fill"></i></button>';
    }
    $html .= '</div><hr>';

    // Indicators container
    $html .= '<div class="indicators-container ms-lg-3">';
    if (!empty($mfo_entry['indicators'])) {
        foreach ($mfo_entry['indicators'] as $ind_index => $indicator) {
            $html .= generateIndicatorRowHtml($indicator, $category, $mfo_index, $ind_index, $action);
        }
    }
    $html .= '</div>';

    // "Add Indicator" button
    if (!$is_view) {
        $html .= '<div class="mt-2"><button type="button" class="btn btn-success btn-sm add-indicator-row"><i class="bi bi-plus"></i> Add Success Indicator</button></div>';
    }
    
    $html .= '</div>'; // close .mfo-block
    return $html;
}

?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Department Performance Commitment and Review</h1>
        <div>
            <?php if ($action === 'view' && $record_id > 0 && isset($dpcr_data['document_status']) && $dpcr_data['document_status'] === 'Approved'): ?>
            <a href="print_record.php?id=<?php echo $record_id; ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-printer"></i> Print DPCR
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
    
    <div class="card shadow">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $form_tab_active ? 'active' : ''; ?>" href="#dpcr-form-tab" data-bs-toggle="tab">
                        <?php 
                        if ($action === 'new') echo 'Create New DPCR';
                        else if ($action === 'edit') echo 'Edit DPCR';
                        else echo 'View DPCR';
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $history_tab_active ? 'active' : ''; ?>" href="#dpcr-history-tab" data-bs-toggle="tab">Sent DPCR History</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- DPCR Form Tab Pane -->
                <div class="tab-pane fade <?php echo $form_tab_active ? 'show active' : ''; ?>" id="dpcr-form-tab">
                    <?php if (($action === 'new' && $record_id === 0) || ($action === 'edit' && $record_id > 0) || ($action === 'view' && $record_id > 0)): // If it's a valid action to show form/view content ?>
                        <?php if ($action === 'new' || $action === 'edit'): // Only open <form> tag for new/edit actions ?>
                            <form method="post" action="dpcr.php<?php echo $record_id > 0 ? '?id=' . $record_id : ''; ?>" id="dpcrForm">
                        <?php endif; ?>

                        <!-- Department Info & Period -->
                        <div class="row mb-4 bg-light p-3 border rounded">
                            <div class="col-md-4">
                                <label for="department" class="form-label fw-bold">Department</label>
                                <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($department['name'] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="head" class="form-label fw-bold">Department Head</label>
                                <input type="text" class="form-control" id="head" value="<?php echo htmlspecialchars($department['head_name'] ?? ''); ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="period" class="form-label fw-bold">Performance Period</label>
                                <?php if ($action === 'view'): ?>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($period); ?>" readonly>
                                    <input type="hidden" name="period" value="<?php echo htmlspecialchars($period); ?>">
                                <?php else: ?>
                                    <select class="form-select" id="period" name="period" required>
                                        <option value="">-- Select Period --</option>
                                        <?php foreach (generatePeriods() as $p): ?>
                                            <option value="<?php echo $p; ?>" <?php echo ($period === $p) ? 'selected' : ''; ?>>
                                                <?php echo $p; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-12 mt-3">
                                <span class="badge bg-primary fs-6 p-2">Current Status: <?php echo htmlspecialchars($status); ?></span>
                                <?php if ($is_legacy_data): ?>
                                    <span class="badge bg-warning text-dark fs-6 p-2">Legacy View</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Computation Type -->
                        <div class="mb-4">
                            <label for="computation_type" class="form-label fw-bold">DPCR Weight Distribution</label>
                            <?php if ($action === 'view'): ?>
                                <input type="text" class="form-control" value="<?php echo getComputationTypes()[$computation_type] ?? 'Type 1 Default'; ?>" readonly>
                                <input type="hidden" name="computation_type" value="<?php echo htmlspecialchars($computation_type); ?>">
                            <?php else: ?>
                                <select class="form-select" name="computation_type" id="computation_type" required>
                                    <?php
                                    $computation_types_list = getComputationTypes();
                                    foreach ($computation_types_list as $type => $description) {
                                        $selected = ($computation_type === $type) ? 'selected' : '';
                                        echo "<option value=\"$type\" $selected>$description</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">Changing this affects which sections are required/visible.</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Strategic Functions Section -->
                        <h4 class="mt-4 text-primary">
                            I. Strategic Functions 
                            <span id="strategic_weight" class="float-end"><?php echo $strategic_weight_display; ?></span>
                        </h4>
                        <div id="strategic_functions_container">
                            <?php foreach ($strategic_functions as $mfo_index => $mfo_entry): ?>
                                <?php echo generateMajorOutputHtml($mfo_entry, 'Strategic', $mfo_index, $action); ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($action === 'new' || $action === 'edit'): ?>
                        <button type="button" class="btn btn-sm btn-success mb-4" id="add_strategic_mfo">
                            <i class="bi bi-plus-circle"></i> Add Strategic MFO
                        </button>
                        <?php endif; ?>

                        <!-- Core Functions Section -->
                        <h4 class="mt-4 text-primary">
                            II. Core Functions 
                            <span id="core_weight" class="float-end"><?php echo $core_weight_display; ?></span>
                        </h4>
                        <div id="core_functions_container">
                             <?php foreach ($core_functions as $mfo_index => $mfo_entry): ?>
                                <?php echo generateMajorOutputHtml($mfo_entry, 'Core', $mfo_index, $action); ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($action === 'new' || $action === 'edit'): ?>
                        <button type="button" class="btn btn-sm btn-success mb-4" id="add_core_mfo">
                            <i class="bi bi-plus-circle"></i> Add Core MFO
                        </button>
                        <?php endif; ?>
                        
                        <!-- Support Functions Section -->
                        <div id="support_section" style="display: <?php echo $support_display_style; ?>;">
                            <h4 class="mt-4 text-primary" id="support_title">
                                III. Support Functions <span class="float-end">(10%)</span>
                            </h4>
                            <div id="support_functions_container">
                                <?php 
                                if ($computation_type === 'Type2' || !empty($support_functions)):
                                    foreach ($support_functions as $mfo_index => $mfo_entry): 
                                        echo generateMajorOutputHtml($mfo_entry, 'Support', $mfo_index, $action); 
                                    endforeach;
                                endif;
                                ?>
                            </div>
                            <?php if ($action === 'new' || $action === 'edit'): ?>
                            <button type="button" class="btn btn-sm btn-info mb-4" id="add_support_mfo">
                                <i class="bi bi-plus-circle"></i> Add Support MFO
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Form Action Buttons -->
                        <?php if ($action === 'new' || $action === 'edit'): ?>
                        <div class="d-flex justify-content-between mt-4 border-top pt-3">
                            <button type="submit" name="document_status" value="Draft" class="btn btn-primary me-2">Save as Draft</button>
                            <button type="submit" name="document_status" value="Pending" class="btn btn-success">Submit DPCR</button>
                            <input type="hidden" name="save_dpcr" value="1">
                        </div>
                        <?php endif; ?>
                        
                        <!-- View Mode Actions -->
                        <?php if ($action === 'view' && $record_id > 0): ?>
                        <div class="mt-4 text-center">
                            <?php if ($status === 'Draft' && !$is_legacy_data): ?>
                                <a href="dpcr.php?action=edit&id=<?php echo $record_id; ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit DPCR</a>
                            <?php endif; ?>
                            <a href="records.php" class="btn btn-secondary">Done Viewing</a>
                        </div>
                        <?php endif; ?>

                        <?php if ($action === 'new' || $action === 'edit'): // Close form if it was opened ?>
                        </form>
                        <?php endif; ?>

                    <?php else: // Not a valid action for form/view, display "No DPCR found" message ?>
                        <div class="text-center py-5">
                            <p class="lead">No DPCR found for the current department or request.</p>
                            <a href="dpcr.php?action=new" class="btn btn-lg btn-success">
                                <i class="bi bi-plus-circle"></i> Create New DPCR
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Sent DPCR History Tab Pane -->
                <div class="tab-pane fade <?php echo $history_tab_active ? 'show active' : ''; ?>" id="dpcr-history-tab">
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
                                <?php if (empty($sent_dpcr_history)): ?>
                                    <tr><td colspan="4" class="text-center">No sent DPCR records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($sent_dpcr_history as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['period']); ?></td>
                                        <td>
                                            <?php 
                                            $status = htmlspecialchars($record['document_status']);
                                            $badge_class = 'bg-secondary';
                                            if ($status === 'Pending' || $status === 'For Review') $badge_class = 'bg-warning text-dark';
                                            if ($status === 'Approved') $badge_class = 'bg-success';
                                            if ($status === 'Rejected') $badge_class = 'bg-danger';
                                            if ($status === 'Distributed' || $status === 'In Progress' || $status === 'For Completion Review' || $status === 'Submitted') $badge_class = 'bg-info text-white';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($record['date_submitted'] ?? $record['date_created'])); ?></td>
                                        <td>
                                            <a href="dpcr.php?action=view&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i> View</a>
                                            <?php if ($record['document_status'] == 'Draft' || $record['document_status'] == 'Rejected'): ?>
                                                <a href="dpcr.php?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil"></i> Edit</a>
                                            <?php endif; ?>
                                            <?php if ($record['document_status'] == 'Draft'): ?>
                                                <a href="delete_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this draft DPCR?');"><i class="bi bi-trash"></i> Delete</a>
                                            <?php endif; ?>
                                            <a href="print_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-info ms-1"><i class="bi bi-printer"></i> Print</a>
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

<!-- FIX: Load jQuery before our custom script uses the $ alias -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
// JavaScript for dynamic, nested form management
$(document).ready(function() {
    
    // --- Computation Type Logic ---
    function updateComputationTypeDisplay() {
        var computationType = $('#computation_type').val();
        
        if (computationType === 'Type2') {
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(45%)');
            $('#support_section').show();

            // If there are no MFOs in the Support section yet, add one
            if ($('#support_functions_container').children('.mfo-block').length === 0) {
                addMfoBlock('Support');
            }
            
        } else {
            $('#strategic_weight').text('(45%)');
            $('#core_weight').text('(55%)');
            $('#support_section').hide();
        }
    }
    
    $('#computation_type').on('change', updateComputationTypeDisplay);
    updateComputationTypeDisplay(); // Initial call
    
    // --- TEMPLATES ---

    const indicatorTemplate = (category, mfo_index, ind_index) => {
        const name_prefix = `${category}[${mfo_index}][indicators][${ind_index}]`;
        return `
        <div class="indicator-row p-2 border-start border-primary border-3 mb-3">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label">Success Indicator</label>
                    <textarea class="form-control" name="${name_prefix}[success_indicators]" rows="2" required></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Budget</label>
                    <input type="number" class="form-control" name="${name_prefix}[budget]" step="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Accountable</label>
                    <input type="text" class="form-control" name="${name_prefix}[accountable]" required>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm w-100 remove-indicator-row"><i class="bi bi-trash"></i></button>
                </div>
                <div class="col-12"><hr class="my-2" style="border-style: dashed;"></div>
                <div class="col-md-5">
                    <label class="form-label text-success">Actual Accomplishments</label>
                    <textarea class="form-control" name="${name_prefix}[actual_accomplishments]" rows="2"></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-success">Ratings (Q/E/T)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="${name_prefix}[q_rating]" placeholder="Q" step="1" min="1" max="5">
                        <input type="number" class="form-control" name="${name_prefix}[e_rating]" placeholder="E" step="1" min="1" max="5">
                        <input type="number" class="form-control" name="${name_prefix}[t_rating]" placeholder="T" step="1" min="1" max="5">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-success">Remarks</label>
                    <textarea class="form-control" name="${name_prefix}[remarks]" rows="2"></textarea>
                </div>
            </div>
        </div>`;
    };

    const mfoTemplate = (category, mfo_index) => {
        const name_prefix = `${category}[${mfo_index}]`;
        // An MFO starts with one indicator
        const first_indicator = indicatorTemplate(category, mfo_index, 0);

        return `
        <div class="mfo-block card card-body mb-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <label class="form-label fw-bold text-primary">Major Final Output</label>
                    <textarea class="form-control" name="${name_prefix}[major_output]" rows="2" required></textarea>
                </div>
                <button type="button" class="btn btn-outline-danger ms-3 remove-mfo-block"><i class="bi bi-trash-fill"></i></button>
            </div>
            <hr>
            <div class="indicators-container ms-lg-3">
                ${first_indicator}
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-success btn-sm add-indicator-row"><i class="bi bi-plus"></i> Add Success Indicator</button>
            </div>
        </div>`;
    };

    // --- DYNAMIC ROW/BLOCK MANAGEMENT ---

    function addMfoBlock(category) {
        const cat_lower = category.toLowerCase();
        const container = $(`#${cat_lower}_functions_container`);
        const mfo_index = container.children('.mfo-block').length;
        const new_mfo_html = mfoTemplate(cat_lower, mfo_index);
        container.append(new_mfo_html);
    }

    // Add MFO block
    $('#add_strategic_mfo').click(() => addMfoBlock('Strategic'));
    $('#add_core_mfo').click(() => addMfoBlock('Core'));
    $('#add_support_mfo').click(() => addMfoBlock('Support'));

    // Remove MFO block
    $(document).on('click', '.remove-mfo-block', function() {
        $(this).closest('.mfo-block').remove();
    });

    // Add Indicator row
    $(document).on('click', '.add-indicator-row', function() {
        const mfo_block = $(this).closest('.mfo-block');
        const indicators_container = mfo_block.find('.indicators-container');
        
        // Determine category and mfo_index from the first field in the MFO block
        const first_mfo_input = mfo_block.find('textarea[name*="[major_output]"]');
        const name = first_mfo_input.attr('name'); // e.g., strategic[0][major_output]
        
        const category = name.substring(0, name.indexOf('['));
        const mfo_index = name.match(/\[(\d+)\]/)[1];
        
        const ind_index = indicators_container.children('.indicator-row').length;
        
        const new_indicator_html = indicatorTemplate(category, mfo_index, ind_index);
        indicators_container.append(new_indicator_html);
    });

    // Remove Indicator row
    $(document).on('click', '.remove-indicator-row', function() {
        $(this).closest('.indicator-row').remove();
    });
});
</script>

<?php
// Include footer
include_once('includes/footer.php');

// End output buffering and flush the content to the browser
ob_end_flush(); 
?>
