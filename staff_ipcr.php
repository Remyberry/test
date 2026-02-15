<?php
// Set page title
$page_title = "Staff IPCR - EPMS";

// Include header
include_once('includes/header.php');

// Check for success or error messages
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Check if user has the right role to access this page
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'department_head') {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get department ID and name
$department_id = $_SESSION['user_department_id'];
$department_query = "SELECT name FROM departments WHERE id = ?";
$stmt = $conn->prepare($department_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
$department_name = ($result->num_rows > 0) ? $result->fetch_assoc()['name'] : 'Unknown Department';

// Get department staff (excluding the department head)
$staff_query = "SELECT id, name, email FROM users WHERE department_id = ? AND role = 'regular_employee'";
$stmt = $conn->prepare($staff_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$staff_result = $stmt->get_result();

// Get staff IPCR records (excluding the department head)
$ipcr_query = "SELECT r.*, u.name as employee_name, u.email as employee_email
               FROM records r
               INNER JOIN users u ON r.user_id = u.id
               WHERE u.department_id = ? AND r.form_type = 'IPCR' AND u.id != ?
               ORDER BY r.date_submitted DESC";
$stmt = $conn->prepare($ipcr_query);
$stmt->bind_param("ii", $department_id, $_SESSION['user_id']);
$stmt->execute();
$ipcr_result = $stmt->get_result();

// Store all records for counting and displaying
$all_ipcr_records = [];
while ($row = $ipcr_result->fetch_assoc()) {
    $all_ipcr_records[] = $row;
}

// Reset the pointer
mysqli_data_seek($ipcr_result, 0);

// Check for new submissions in the last 24 hours
$new_submissions = false;
foreach ($all_ipcr_records as $record) {
    if ($record['document_status'] == 'Distributed') {
        $submitted_time = strtotime($record['date_submitted']);
        $current_time = time();
        if (($current_time - $submitted_time) < 86400) { // Less than 24 hours old
            $new_submissions = true;
            break;
        }
    }
}

// Calculate status counts
$status_counts = [
    'Pending' => 0,
    'Approved' => 0,
    'Rejected' => 0,
    'Draft' => 0,
    'Distributed' => 0,
    'For Review' => 0
];

foreach ($all_ipcr_records as $record) {
    if (isset($record['document_status'])) {
        $status_counts[$record['document_status']]++;
    }
}

// Handle filters if submitted
$filter_employee = isset($_GET['employee']) ? $_GET['employee'] : "all";
$filter_period = isset($_GET['period']) ? $_GET['period'] : "all";
$filter_status = isset($_GET['document_status']) ? $_GET['document_status'] : "all";

if ($filter_employee != "all" || $filter_period != "all" || $filter_status != "all") {
    // Start building the filtered query with basic conditions
    $filtered_query = "SELECT r.*, u.name as employee_name
                      FROM records r
                      INNER JOIN users u ON r.user_id = u.id
                      WHERE u.department_id = ? AND r.form_type = 'IPCR' AND u.id != ?";
    $bind_types = "ii";
    $bind_params = [$department_id, $_SESSION['user_id']];
    
    // Add employee filter
    if ($filter_employee != "all") {
        $filtered_query .= " AND r.user_id = ?";
        $bind_types .= "i";
        $bind_params[] = $filter_employee;
    }
    
    // Add period filter
    if ($filter_period != "all") {
        $filtered_query .= " AND r.period = ?";
        $bind_types .= "s";
        $bind_params[] = $filter_period;
    }
    
    // Add status filter
    if ($filter_status != "all") {
        $filtered_query .= " AND r.document_status = ?";
        $bind_types .= "s";
        $bind_params[] = $filter_status;
    }
    
    $filtered_query .= " ORDER BY r.date_submitted DESC";
    
    $stmt = $conn->prepare($filtered_query);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $ipcr_result = $stmt->get_result();
}

// Get distinct periods for filter dropdown
$periods_query = "SELECT DISTINCT period FROM records WHERE form_type = 'IPCR' AND 
                 user_id IN (SELECT id FROM users WHERE department_id = ? AND id != ?)";
$stmt = $conn->prepare($periods_query);
$stmt->bind_param("ii", $department_id, $_SESSION['user_id']);
$stmt->execute();
$periods_result = $stmt->get_result();
?>

<!-- Staff IPCR Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Staff IPCR Management - <?php echo htmlspecialchars($department_name); ?></h1>
        <div>
            <button class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-calendar"></i> 
                <?php echo date('F d, Y'); ?>
            </button>
            <button class="btn btn-sm btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
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
    
    <!-- Notification for new submissions -->
    <?php 
    if ($new_submissions): 
    ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-bell-fill me-2 fs-4 pulse-icon"></i>
            <div>
                <strong>New IPCR submissions!</strong> You have new IPCR forms submitted by staff members that need your review.
                <a href="staff_ipcr.php?document_status=Pending" class="alert-link">Click here to review them</a>.
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Staff Statistics</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="bg-light rounded p-3 text-center">
                        <h6 class="text-muted mb-2">Total Staff</h6>
                        <h2 class="mb-0"><?php echo $staff_result->num_rows; ?></h2>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="bg-light rounded p-3 text-center">
                        <h6 class="text-muted mb-2">Submitted IPCRs</h6>
                        <h2 class="mb-0"><?php echo count($all_ipcr_records); ?></h2>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="bg-light rounded p-3 text-center">
                        <h6 class="text-muted mb-2">Pending Review</h6>
                        <h2 class="mb-0"><?php echo $status_counts['For Review']; ?></h2>
                        <?php if ($status_counts['For Review'] > 0): ?>
                        <span class="badge bg-warning text-dark badge-new">Needs Action</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="bg-light rounded p-3 text-center">
                        <h6 class="text-muted mb-2">Approved</h6>
                        <h2 class="mb-0"><?php echo $status_counts['Approved']; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Staff IPCR Records</h5>
            <?php if ($status_counts['For Review'] > 0): ?>
            <span class="badge bg-warning text-dark"><?php echo $status_counts['For Review']; ?> Pending Review</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form action="staff_ipcr.php" method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label for="employee" class="form-label">Employee</label>
                        <select class="form-select" name="employee" id="employee">
                            <option value="all" <?php echo ($filter_employee == "all") ? "selected" : ""; ?>>All Employees</option>
                            <?php 
                            mysqli_data_seek($staff_result, 0);
                            while ($staff = $staff_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $staff['id']; ?>" <?php echo ($filter_employee == $staff['id']) ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($staff['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
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
                    <div class="col-md-3 mb-2">
                        <label for="document_status" class="form-label">Status</label>
                        <select class="form-select" name="document_status" id="document_status">
                            <option value="all" <?php echo ($filter_status == "all") ? "selected" : ""; ?>>All Status</option>
                            <option value="Pending" <?php echo ($filter_status == "Pending") ? "selected" : ""; ?>>Pending</option>
                            <option value="Approved" <?php echo ($filter_status == "Approved") ? "selected" : ""; ?>>Approved</option>
                            <option value="Rejected" <?php echo ($filter_status == "Rejected") ? "selected" : ""; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="staff_ipcr.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- IPCR Records Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Period</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ipcr_result->num_rows > 0): ?>
                            <?php while ($ipcr = $ipcr_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ipcr['employee_name']); ?></td>
                                    <td><?php echo htmlspecialchars($ipcr['period']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ipcr['date_submitted'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_badge = 'secondary';
                                        $is_new = false;
                                        switch ($ipcr['document_status']) {
                                            case 'Approved':
                                                $status_badge = 'success';
                                                break;
                                            case 'Pending':
                                                $status_badge = 'warning';
                                                // Check if this is a recent submission (within the last 24 hours)
                                                $submitted_time = strtotime($ipcr['date_submitted']);
                                                $current_time = time();
                                                $is_new = ($current_time - $submitted_time) < 86400; // 24 hours in seconds
                                                break;
                                            case 'For Review':
                                                $status_badge = 'warning';
                                                // Check if this is a recent submission (within the last 24 hours)
                                                $submitted_time = strtotime($ipcr['date_submitted']);
                                                $current_time = time();
                                                $is_new = ($current_time - $submitted_time) < 86400; // 24 hours in seconds
                                                break;
                                            case 'Rejected':
                                                $status_badge = 'danger';
                                                break;
                                            case 'Draft':
                                                $status_badge = 'secondary';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_badge; ?>">
                                            <?php echo $ipcr['document_status']; ?>
                                            <?php if ($is_new): ?>
                                            <span class="badge bg-danger ms-1 badge-new">New</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_record.php?id=<?php echo $ipcr['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($ipcr['document_status'] == 'Pending'): ?>
                                                <a href="edit_record.php?id=<?php echo $ipcr['id']; ?>" class="btn btn-sm <?php echo $is_new ? 'btn-success pulse-button' : 'btn-outline-success'; ?>">
                                                    <i class="bi bi-check-circle <?php echo $is_new ? 'pulse-icon' : ''; ?>"></i> 
                                                    <?php echo $is_new ? '<strong>Review Now</strong>' : 'Review'; ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($ipcr['document_status'] == 'For Review'): ?>
                                                <a href="edit_record.php?id=<?php echo $ipcr['id']; ?>" class="btn btn-sm <?php echo $is_new ? 'btn-success pulse-button' : 'btn-outline-success'; ?>">
                                                    <i class="bi bi-check-circle <?php echo $is_new ? 'pulse-icon' : ''; ?>"></i> 
                                                    <?php echo $is_new ? '<strong>Review Now</strong>' : 'Review'; ?>
                                                </a>
                                            <?php endif; ?>
                                            <a href="print_record.php?id=<?php echo $ipcr['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                            <?php if ($ipcr['document_status'] == 'Draft'): ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="submitDraftForEmployee(<?php echo $ipcr['id']; ?>)">
                                                    <i class="bi bi-arrow-up-circle"></i> Submit Draft
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $ipcr['id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $ipcr['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $ipcr['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $ipcr['id']; ?>">Confirm Deletion</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete the IPCR record for <?php echo htmlspecialchars($ipcr['employee_name']); ?> (<?php echo htmlspecialchars($ipcr['period']); ?>)?</p>
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                            This action cannot be undone. All data associated with this record will be permanently removed.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="delete_record.php?id=<?php echo $ipcr['id']; ?>" class="btn btn-danger">Delete Record</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="py-5">
                                        <i class="bi bi-clipboard-x fs-1 text-muted mb-3 d-block"></i>
                                        <h4 class="text-muted mb-3">No IPCR records found</h4>
                                        <p class="mb-3">There are no IPCR records submitted by staff in your department yet.</p>
                                        <div class="alert alert-info d-inline-block">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <span>Please inform your staff to create and submit their IPCR forms for your review.</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function submitDraftForEmployee(recordId) {
        if (confirm('Are you sure you want to submit this draft on behalf of the employee? This will change its status to Pending.')) {
            window.location.href = 'submit_draft.php?id=' + recordId;
        }
    }
    
    // Browser notifications for new submissions
    document.addEventListener('DOMContentLoaded', function() {
        // Check if browser supports notifications
        if ('Notification' in window) {
            // Check if there are new submissions
            const newSubmissions = <?php echo $new_submissions ? 'true' : 'false'; ?>;
            
            if (newSubmissions && Notification.permission === 'granted') {
                // Create and show notification
                const notification = new Notification('New IPCR Submissions', {
                    body: 'You have new IPCR forms that need your review.',
                    icon: 'images/logo.png',
                });
                
                notification.onclick = function() {
                    window.focus();
                    window.location.href = 'staff_ipcr.php?document_status=Pending';
                };
            } else if (newSubmissions && Notification.permission !== 'denied') {
                // Request permission
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        const notification = new Notification('New IPCR Submissions', {
                            body: 'You have new IPCR forms that need your review.',
                            icon: 'images/logo.png',
                        });
                        
                        notification.onclick = function() {
                            window.focus();
                            window.location.href = 'staff_ipcr.php?document_status=Pending';
                        };
                    }
                });
            }
        }
        
        // Auto-scroll to new submissions if there are any
        const newSubmissions = <?php echo $new_submissions ? 'true' : 'false'; ?>;
        if (newSubmissions) {
            // Find the first new submission
            const newSubmissionElement = document.querySelector('.pulse-button');
            if (newSubmissionElement) {
                // Scroll to it with animation
                setTimeout(() => {
                    newSubmissionElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 1000);
            }
        }
    });
</script>

<?php
// Include footer
include_once('includes/footer.php');
?>