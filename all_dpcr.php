<?php
// Set page title
$page_title = "All DPCR Reports - EPMS";

// Start session
session_start();

// Check if user is logged in and is president
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'president') {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get filter parameters
$filter_period = isset($_GET['period']) ? $_GET['period'] : "all";
$filter_department = isset($_GET['department']) ? $_GET['department'] : "all";
$filter_status = isset($_GET['status']) ? $_GET['status'] : "all";

// Build the query
$query = "SELECT r.*, d.name as department_name, u.name as head_name, 
          (SELECT COUNT(*) FROM dpcr_entries WHERE record_id = r.id) as entries_count
          FROM records r 
          JOIN users u ON r.user_id = u.id 
          LEFT JOIN departments d ON u.department_id = d.id
          WHERE r.form_type = 'DPCR'";

$params = [];
$types = "";

if ($filter_period != "all") {
    $query .= " AND r.period = ?";
    $params[] = $filter_period;
    $types .= "s";
}

if ($filter_department != "all") {
    $query .= " AND u.department_id = ?";
    $params[] = $filter_department;
    $types .= "i";
}

if ($filter_status != "all") {
    $query .= " AND r.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$query .= " ORDER BY r.date_submitted DESC, r.id DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records_result = $stmt->get_result();

// Get all periods for filter dropdown
$periods_query = "SELECT DISTINCT period FROM records WHERE form_type = 'DPCR' ORDER BY period DESC";
$periods_result = $conn->query($periods_query);

// Get all departments for filter dropdown
$departments_query = "SELECT id, name FROM departments ORDER BY name";
$departments_result = $conn->query($departments_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            padding-bottom: 20px;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Department Performance Commitment and Review (DPCR)</h1>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="all_dpcr.php" method="GET" class="row g-3">
                        <div class="col-md-4">
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
                        
                        <div class="col-md-4">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" name="department" id="department">
                                <option value="all" <?php echo ($filter_department == "all") ? "selected" : ""; ?>>All Departments</option>
                                <?php while ($dept = $departments_result->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo ($filter_department == $dept['id']) ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status" id="status">
                                <option value="all" <?php echo ($filter_status == "all") ? "selected" : ""; ?>>All Status</option>
                                <option value="Draft" <?php echo ($filter_status == "Draft") ? "selected" : ""; ?>>Draft</option>
                                <option value="Pending" <?php echo ($filter_status == "Pending") ? "selected" : ""; ?>>Pending</option>
                                <option value="Approved" <?php echo ($filter_status == "Approved") ? "selected" : ""; ?>>Approved</option>
                                <option value="For Revision" <?php echo ($filter_status == "For Revision") ? "selected" : ""; ?>>For Revision</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <a href="all_dpcr.php" class="btn btn-outline-secondary ms-2">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                            <a href="#" id="print-consolidated-btn" class="btn btn-primary ms-2">
                                <i class="bi bi-printer"></i> Print Consolidated Report
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Function to update the print button's href
                function updatePrintButtonHref() {
                    const period = document.getElementById('period').value;
                    const department = document.getElementById('department').value;
                    const status = document.getElementById('status').value;
                    
                    const queryParams = new URLSearchParams({
                        period: period,
                        department: department,
                        status: status
                    });
                    
                    const printBtn = document.getElementById('print-consolidated-btn');
                    printBtn.href = `print_consolidated_dpcr.php?${queryParams.toString()}`;
                }

                // Update the href on initial page load
                updatePrintButtonHref();

                // Update the href whenever a filter changes
                document.getElementById('period').addEventListener('change', updatePrintButtonHref);
                document.getElementById('department').addEventListener('change', updatePrintButtonHref);
                document.getElementById('status').addEventListener('change', updatePrintButtonHref);
            });
            </script>
            
            <!-- Records Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">DPCR Reports</h5>
                </div>
                <div class="card-body">
                    <?php if ($records_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Department</th>
                                        <th>Head</th>
                                        <th>Period</th>
                                        <th>Entries</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                    <?php while ($record = $records_result->fetch_assoc()): 
                                    
                                        // Logic to determine if a record is "New"
                                        $is_new = false;
                                        if ($record['document_status'] === 'Pending' && !empty($record['date_submitted'])) {
                                            $submitted_time = strtotime($record['date_submitted']);
                                            $current_time = time();
                                            if (($current_time - $submitted_time) < 86400) { // 24 hours in seconds
                                                $is_new = true;
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['department_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['head_name']); ?></td>
                                        <td><?php echo htmlspecialchars($record['period']); ?></td>
                                        <td><?php echo $record['entries_count']; ?> items</td>
                                        <td>
                                            <?php 
                                            $status_badge = 'secondary';
                                            switch ($record['document_status']) {
                                                case 'Draft': $status_badge = 'secondary'; break;
                                                case 'Approved': $status_badge = 'success'; break;
                                                case 'Pending': $status_badge = 'warning'; break;
                                                case 'For Revision': $status_badge = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_badge; ?>">
                                                <?php echo $record['document_status']; ?>
                                            </span>
                                            <?php if ($is_new): ?>
                                            <span class="badge bg-danger ms-1 badge-new" style="cursor: pointer;" title="Click to dismiss">New</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            echo $record['date_submitted'] 
                                                ? date('M d, Y', strtotime($record['date_submitted'])) 
                                                : '<span class="text-muted">Not submitted</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="print_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-printer"></i> Print
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="bi bi-file-earmark-x fs-1 text-muted"></i>
                            </div>
                            <h5 class="text-muted">No DPCR reports found</h5>
                            <p>Try changing the filter criteria or check back later</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newBadges = document.querySelectorAll('.badge-new');
            newBadges.forEach(function(badge) {
                badge.addEventListener('click', function() {
                    // Hide the badge when clicked
                    this.style.display = 'none';
                });
            });
        });
    </script>
</body>
</html> 