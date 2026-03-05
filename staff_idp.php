<?php
// Set page title
$page_title = "Staff IDP - EPMS";

// Include header
include_once('includes/header.php');

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

// Get staff IDP records
$idp_query = "SELECT r.*, u.name as employee_name, u.email as employee_email
               FROM records r
               INNER JOIN users u ON r.user_id = u.id
               WHERE u.department_id = ? AND r.form_type = 'IDP' AND u.id != ?
               ORDER BY r.date_submitted DESC";
$stmt = $conn->prepare($idp_query);
$stmt->bind_param("ii", $department_id, $_SESSION['user_id']);
$stmt->execute();
$idp_result = $stmt->get_result();

// Store all records for counting and displaying
$all_idp_records = [];
while ($row = $idp_result->fetch_assoc()) {
    $all_idp_records[] = $row;
}

// Calculate status counts
$status_counts = [
    'Pending' => 0,
    'In Progress' => 0,
    'Approved' => 0,
    'For Revision' => 0,
    'Draft' => 0,
    'For Completion Review' => 0, // Added for new review stage
    'For Review' => 0
];

foreach ($all_idp_records as $record) {
    if (isset($record['document_status'])) {
        if (isset($status_counts[$record['document_status']])) {
            $status_counts[$record['document_status']]++;
        } else {
            $status_counts[$record['document_status']] = 1;
        }
    }
}

// Handle filters if submitted
$filter_employee = isset($_GET['employee']) ? $_GET['employee'] : "all";
$filter_period = isset($_GET['period']) ? $_GET['period'] : "all";
$filter_status = isset($_GET['document_status']) ? $_GET['document_status'] : "all";

if ($filter_employee != "all" || $filter_period != "all" || $filter_status != "all") {
    // Start building the filtered query
    $filtered_query = "SELECT r.*, u.name as employee_name
                      FROM records r
                      INNER JOIN users u ON r.user_id = u.id
                      WHERE u.department_id = ? AND r.form_type = 'IDP' AND u.id != ?";
    $bind_types = "ii";
    $bind_params = [$department_id, $_SESSION['user_id']];
    
    if ($filter_employee != "all") {
        $filtered_query .= " AND r.user_id = ?";
        $bind_types .= "i";
        $bind_params[] = $filter_employee;
    }
    
    if ($filter_period != "all") {
        $filtered_query .= " AND r.period = ?";
        $bind_types .= "s";
        $bind_params[] = $filter_period;
    }
    
    if ($filter_status != "all") {
        $filtered_query .= " AND r.document_status = ?";
        $bind_types .= "s";
        $bind_params[] = $filter_status;
    }
    
    $filtered_query .= " ORDER BY r.date_submitted DESC";
    
    $stmt = $conn->prepare($filtered_query);
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $idp_result = $stmt->get_result();
} else {
    // Reset pointer if no filter
    $idp_result = $conn->query("SELECT r.*, u.name as employee_name FROM records r INNER JOIN users u ON r.user_id = u.id WHERE u.department_id = $department_id AND r.form_type = 'IDP' AND u.id != {$_SESSION['user_id']} ORDER BY r.date_submitted DESC");
}

// Get distinct periods
$periods_query = "SELECT DISTINCT period FROM records WHERE form_type = 'IDP' AND 
                 user_id IN (SELECT id FROM users WHERE department_id = ? AND id != ?)";
$stmt = $conn->prepare($periods_query);
$stmt->bind_param("ii", $department_id, $_SESSION['user_id']);
$stmt->execute();
$periods_result = $stmt->get_result();
?>

<!-- Staff IDP Content -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Staff IDP Management - <?php echo htmlspecialchars($department_name); ?></h1>
        <div>
            <button class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-calendar"></i> 
                <?php echo date('F d, Y'); ?>
            </button>
        </div>
    </div>
    
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
                        <h6 class="text-muted mb-2">Pending Initial Review</h6>
                        <h2 class="mb-0"><?php echo $status_counts['Pending']; ?></h2>
                        <?php if ($status_counts['Pending'] > 0): ?>
                        <span class="badge bg-warning text-dark">Needs Action</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="bg-light rounded p-3 text-center">
                        <h6 class="text-muted mb-2">Pending Final Review</h6>
                        <h2 class="mb-0"><?php echo $status_counts['For Completion Review'] ?? 0; ?></h2>
                        <?php if (($status_counts['For Completion Review'] ?? 0) > 0): ?>
                        <span class="badge bg-primary">Needs Action</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="bg-light rounded p-3 text-center">
                        <h6 class="text-muted mb-2">In Progress</h6>
                        <h2 class="mb-0"><?php echo $status_counts['In Progress']; ?></h2>
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
            <h5 class="mb-0">Staff IDP Records</h5>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form action="staff_idp.php" method="GET" class="mb-4">
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
                            <option value="Pending" <?php echo ($filter_status == "Pending") ? "selected" : ""; ?>>Pending (Initial)</option>
                            <option value="In Progress" <?php echo ($filter_status == "In Progress") ? "selected" : ""; ?>>In Progress</option>
                            <option value="For Review" <?php echo ($filter_status == "For Review") ? "selected" : ""; ?>>For Review (Final)</option>
                            <option value="For Completion Review" <?php echo ($filter_status == "For Completion Review") ? "selected" : ""; ?>>For Completion Review</option>
                            <option value="Approved" <?php echo ($filter_status == "Approved") ? "selected" : ""; ?>>Approved</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-filter"></i> Filter
                        </button>
                        <a href="staff_idp.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- IDP Records Table -->
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
                        <?php if ($idp_result->num_rows > 0): ?>
                            <?php while ($idp = $idp_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($idp['employee_name']); ?></td>
                                    <td><?php echo htmlspecialchars($idp['period']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($idp['date_submitted'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_badge = 'secondary';
                                        switch ($idp['document_status']) {
                                            case 'Approved': $status_badge = 'success'; break;
                                            case 'Pending': $status_badge = 'warning text-dark'; break;
                                            case 'In Progress': $status_badge = 'info text-dark'; break;
                                            case 'For Review': $status_badge = 'primary'; break;
                                            case 'For Completion Review': $status_badge = 'primary'; break; // Added
                                            case 'For Revision': $status_badge = 'danger'; break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_badge; ?>">
                                            <?php echo $idp['document_status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_record.php?id=<?php echo $idp['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($idp['document_status'] == 'Pending' || $idp['document_status'] == 'For Review' || $idp['document_status'] == 'For Completion Review'): ?>
                                                <a href="edit_record.php?id=<?php echo $idp['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="bi bi-check-circle"></i> Review
                                                </a>
                                            <?php endif; ?>
                                            <a href="print_record.php?id=<?php echo $idp['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="py-5">
                                        <i class="bi bi-journal-x fs-1 text-muted mb-3 d-block"></i>
                                        <h4 class="text-muted mb-3">No IDP records found</h4>
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

<?php
// Include footer
include_once('includes/footer.php');
?>
