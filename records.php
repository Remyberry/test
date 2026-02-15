<?php
// Set page title
$page_title = "My Records - EPMS";

// Include header
include_once('includes/header.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$department_id = $_SESSION['user_department_id'] ?? null;

// Build the query based on user role
$params = [];
$types = "";

if ($user_role == 'regular_employee') {
    // Regular employees see only their own records
    $sql = "SELECT r.*, u.name as employee_name 
            FROM records r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.user_id = ?
            ORDER BY r.date_submitted DESC, r.id DESC";
    $params[] = $user_id;
    $types .= "i";
} elseif ($user_role == 'department_head') {
    // Department heads see only their own records
    $sql = "SELECT r.*, u.name as employee_name
            FROM records r
            JOIN users u ON r.user_id = u.id
            WHERE r.user_id = ?
            ORDER BY r.date_submitted DESC, r.id DESC";
    $params[] = $user_id;
    $types .= "i";} elseif ($user_role == 'president') {
    // President sees all records
    $sql = "SELECT r.*, u.name as employee_name, d.name as department_name
            FROM records r 
            JOIN users u ON r.user_id = u.id 
            LEFT JOIN departments d ON u.department_id = d.id
            ORDER BY r.date_submitted DESC, r.id DESC";
} elseif ($user_role == 'admin') {
    // Admin sees all records
    $sql = "SELECT r.*, u.name as employee_name, d.name as department_name
            FROM records r 
            JOIN users u ON r.user_id = u.id 
            LEFT JOIN departments d ON u.department_id = d.id
            ORDER BY r.date_submitted DESC, r.id DESC";
} else {
    // Default fallback - show only own records
    $sql = "SELECT r.*, u.name as employee_name 
            FROM records r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.user_id = ?
            ORDER BY r.date_submitted DESC, r.id DESC";
    $params[] = $user_id;
    $types .= "i";
}

// Get records
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records_result = $stmt->get_result();

// Get filter parameters
$filter_type = isset($_GET['type']) ? $_GET['type'] : "all";
$filter_status = isset($_GET['status']) ? $_GET['status'] : "all";
$filter_period = isset($_GET['period']) ? $_GET['period'] : "all";

// Apply filters if set
if ($filter_type != "all" || $filter_status != "all" || $filter_period != "all") {
    $filtered_records = [];
    while ($record = $records_result->fetch_assoc()) {
        $include = true;
        
        if ($filter_type != "all" && $record['form_type'] != $filter_type) {
            $include = false;
        }
        
        if ($filter_status != "all" && $record['document_status'] != $filter_status) {
            $include = false;
        }
        
        if ($filter_period != "all" && $record['period'] != $filter_period) {
            $include = false;
        }
        
        if ($include) {
            $filtered_records[] = $record;
        }
    }
} else {
    // No filters, get all records
    $filtered_records = [];
    while ($record = $records_result->fetch_assoc()) {
        $filtered_records[] = $record;
    }
}

// Get distinct periods for filter dropdown
$periods_query = "SELECT DISTINCT period FROM records";
if ($user_role == 'regular_employee') {
    $periods_query .= " WHERE user_id = ?";
    $stmt = $conn->prepare($periods_query);
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare($periods_query);
}
$stmt->execute();
$periods_result = $stmt->get_result();

// Check for success or error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>

<!-- Records Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">My Records</h1>
        <div>
            <?php if ($user_role == 'regular_employee' || $user_role == 'department_head'): ?>
            <div class="btn-group">
                <a href="ipcr.php?action=new" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> New IPCR
                </a>
                <a href="idp.php?action=new" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle"></i> New IDP
                </a>
                <?php if ($user_role == 'department_head'): ?>
                <a href="dpcr.php?action=new" class="btn btn-sm btn-info text-white">
                    <i class="bi bi-plus-circle"></i> New DPCR
                </a>
                <?php endif; ?>
            </div>
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
    
    <!-- Toast Notifications -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
        <?php if ($success_message): ?>
        <div id="successToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bi bi-check-circle me-2"></i>
                <strong class="me-auto">Success</strong>
                <small>Just now</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo $success_message; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div id="errorToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong class="me-auto">Error</strong>
                <small>Just now</small>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo $error_message; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="records.php" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="type" class="form-label">Form Type</label>
                    <select class="form-select" name="type" id="type">
                        <option value="all" <?php echo ($filter_type == "all") ? "selected" : ""; ?>>All Types</option>
                        <option value="DPCR" <?php echo ($filter_type == "DPCR") ? "selected" : ""; ?>>DPCR</option>
                        <option value="IPCR" <?php echo ($filter_type == "IPCR") ? "selected" : ""; ?>>IPCR</option>
                        <option value="IDP" <?php echo ($filter_type == "IDP") ? "selected" : ""; ?>>IDP</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" name="status" id="status">
                        <option value="all" <?php echo ($filter_status == "all") ? "selected" : ""; ?>>All Status</option>
                        <option value="Draft" <?php echo ($filter_status == "Draft") ? "selected" : ""; ?>>Draft</option>
                        <option value="Pending" <?php echo ($filter_status == "Pending") ? "selected" : ""; ?>>Pending</option>
                        <option value="Approved" <?php echo ($filter_status == "Approved") ? "selected" : ""; ?>>Approved</option>
                        <option value="Rejected" <?php echo ($filter_status == "Rejected") ? "selected" : ""; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="period" class="form-label">Period</label>
                    <select class="form-select" name="period" id="period">
                        <option value="all" <?php echo ($filter_period == "all") ? "selected" : ""; ?>>All Periods</option>
                        <?php while ($period = $periods_result->fetch_assoc()): ?>
                            <option value="<?php echo $period['period']; ?>" <?php echo ($filter_period == $period['period']) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($period['period']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <a href="records.php" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Records List -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Records</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (count($filtered_records) > 0): ?>
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Form Type</th>
                            <?php if ($user_role == 'department_head' || $user_role == 'president' || $user_role == 'admin'): ?>
                            <th>Employee</th>
                            <?php endif; ?>
                            <?php if ($user_role == 'president' || $user_role == 'admin'): ?>
                            <th>Department</th>
                            <?php endif; ?>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_records as $record): ?>
                        <tr>
                            <td>
                                <?php 
                                $icon_class = '';
                                switch ($record['form_type']) {
                                    case 'DPCR':
                                        $icon_class = 'bi-building text-info';
                                        break;
                                    case 'IPCR':
                                        $icon_class = 'bi-person-vcard text-primary';
                                        break;
                                    case 'IDP':
                                        $icon_class = 'bi-journal-text text-success';
                                        break;
                                }
                                ?>
                                <i class="bi <?php echo $icon_class; ?> me-1"></i>
                                <?php echo $record['form_type']; ?>
                            </td>
                            
                            <?php if ($user_role == 'department_head' || $user_role == 'president' || $user_role == 'admin'): ?>
                            <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                            <?php endif; ?>
                            
                            <?php if ($user_role == 'president' || $user_role == 'admin'): ?>
                            <td><?php echo htmlspecialchars($record['department_name'] ?? 'No Department'); ?></td>
                            <?php endif; ?>
                            
                            <td><?php echo htmlspecialchars($record['period']); ?></td>
                            
                            <td>
                                <?php 
                                $status_badge = 'secondary';
                                $status_icon = '';
                                switch ($record['document_status']) {
                                    case 'Draft':
                                        $status_badge = 'secondary';
                                        $status_icon = 'bi-file-earmark-text';
                                        break;
                                    case 'Approved':
                                        $status_badge = 'success';
                                        $status_icon = 'bi-check-circle-fill';
                                        break;
                                    case 'Pending':
                                        $status_badge = 'warning';
                                        $status_icon = 'bi-hourglass-split';
                                        break;
                                    case 'For Review':
                                        $status_badge = 'warning';
                                        $status_icon = 'bi-file-earmark-arrow-down-fill';
                                        break;
                                    case 'Distributed':
                                        $status_badge = 'info';
                                        $status_icon = 'bi-file-earmark-arrow-up-fill';
                                        break;
                                    case 'Rejected':
                                        $status_badge = 'danger';
                                        $status_icon = 'bi-x-circle-fill';
                                        break;
                                }
                                
                                // Check if this is a recent submission
                                $is_new = false;
                                if ($record['document_status'] == 'Pending' && $record['date_submitted']) {
                                    $submitted_time = strtotime($record['date_submitted']);
                                    $current_time = time();
                                    $is_new = ($current_time - $submitted_time) < 86400; // Less than 24 hours old
                                }
                                ?>
                                <span class="badge bg-<?php echo $status_badge; ?> d-flex align-items-center d-inline-flex">
                                    <i class="bi <?php echo $status_icon; ?> me-1"></i>
                                    <?php echo $record['document_status']; ?>
                                    <?php if ($is_new): ?>
                                        <span class="badge bg-danger ms-1 badge-new">New</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if ($record['date_submitted']): ?>
                                    <?php echo date('M d, Y', strtotime($record['date_submitted'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not submitted</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <div class="btn-group">
                                    <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    
                                    <?php if ($record['document_status'] == 'Draft' && ($record['user_id'] == $user_id || $user_role == 'admin')): ?>
                                    <a href="<?php echo strtolower($record['form_type']); ?>.php?action=edit&id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="submit_draft.php?id=<?php echo $record['id']; ?>" 
                                       onclick="return confirm('Are you sure you want to submit this draft for review? You will not be able to edit it after submission.')"
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Submit
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($record['document_status'] == 'Pending' && 
                                              (($user_role == 'department_head' && $record['form_type'] != 'DPCR') || 
                                               $user_role == 'president')): ?>
                                    <a href="review_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm <?php echo $is_new ? 'btn-warning pulse-button' : 'btn-outline-warning'; ?>">
                                        <i class="bi bi-clipboard-check <?php echo $is_new ? 'pulse-icon' : ''; ?>"></i>
                                        <?php echo $is_new ? '<strong>Review Now</strong>' : 'Review'; ?>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($record['document_status'] == 'Approved'): ?>
                                    <a href="print_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-printer"></i> Print
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (($record['document_status'] == 'Draft' || $record['document_status'] == 'Pending') && 
                                                     ($record['user_id'] == $user_id || $user_role == 'admin')): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $record['id']; ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Delete Confirmation Modal -->
                                <?php if (($record['document_status'] == 'Draft' || $record['document_status'] == 'Pending') && 
                                        ($record['user_id'] == $user_id || $user_role == 'admin')): ?>
                                <div class="modal fade" id="deleteModal<?php echo $record['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $record['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $record['id']; ?>">Confirm Deletion</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete this <?php echo $record['form_type']; ?> record for <?php echo htmlspecialchars($record['period']); ?>?</p>
                                                <div class="alert alert-warning">
                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                    This action cannot be undone. All data associated with this record will be permanently removed.
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <a href="delete_record.php?id=<?php echo $record['id']; ?>" class="btn btn-danger">Delete Record</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-file-earmark-x fs-1 text-muted"></i>
                    </div>
                    <h5 class="text-muted">No records found</h5>
                    <p>
                        <?php if ($user_role == 'regular_employee' || $user_role == 'department_head'): ?>
                            Create a new form to get started
                        <?php else: ?>
                            No records match your filter criteria
                        <?php endif; ?>
                    </p>
                    
                    <?php if ($user_role == 'regular_employee'): ?>
                    <div class="mt-3">
                        <a href="ipcr.php?action=new" class="btn btn-primary me-2">Create IPCR</a>
                        <a href="idp.php?action=new" class="btn btn-success">Create IDP</a>
                    </div>
                    <?php elseif ($user_role == 'department_head'): ?>
                    <div class="mt-3">
                        <a href="ipcr.php?action=new" class="btn btn-primary me-2">Create IPCR</a>
                        <a href="idp.php?action=new" class="btn btn-success me-2">Create IDP</a>
                        <a href="dpcr.php?action=new" class="btn btn-info text-white">Create DPCR</a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Initialize toasts and add auto-hide functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get toasts
        const successToast = document.getElementById('successToast');
        const errorToast = document.getElementById('errorToast');
        
        // Auto-hide toasts after 5 seconds
        if (successToast) {
            setTimeout(function() {
                const toast = new bootstrap.Toast(successToast);
                toast.hide();
            }, 5000);
        }
        
        if (errorToast) {
            setTimeout(function() {
                const toast = new bootstrap.Toast(errorToast);
                toast.hide();
            }, 8000); // Error messages stay a bit longer
        }
    });
</script>

<?php
// Include footer
include_once('includes/footer.php');
?> 