<?php
// Start session
session_start();

// Set dynamic page title based on user role
$user_role = $_SESSION['user_role'] ?? '';
$page_title = ($user_role == 'department_head') ? "Department Head Dashboard - EPMS" : "President Dashboard - EPMS";

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'president' && $_SESSION['user_role'] !== 'department_head')) {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['user_department_id'] ?? 0;

// Common data for both dashboards
$total_records = 0;
$total_pending = 0;
$records_by_type = [];

// President dashboard data
if ($user_role === 'president') {
    // Get counts for dashboard
    // Total departments
    $sql_departments = "SELECT COUNT(*) as total FROM departments";
    $result_departments = $conn->query($sql_departments);
    $total_departments = $result_departments->fetch_assoc()['total'];

    // Total employees
    $sql_employees = "SELECT COUNT(*) as total FROM users WHERE role IN ('department_head', 'regular_employee')";
    $result_employees = $conn->query($sql_employees);
    $total_employees = $result_employees->fetch_assoc()['total'];

    // Total records
    $sql_records = "SELECT COUNT(*) as total FROM records";
    $result_records = $conn->query($sql_records);
    $total_records = $result_records->fetch_assoc()['total'];

    // Pending reviews
    $sql_pending = "SELECT COUNT(*) as total FROM records WHERE document_status = 'Pending'";
    $result_pending = $conn->query($sql_pending);
    $total_pending = $result_pending->fetch_assoc()['total'];

    // Records by type
    $sql_records_by_type = "SELECT form_type, COUNT(*) as count FROM records GROUP BY form_type";
    $result_records_by_type = $conn->query($sql_records_by_type);
    while ($row = $result_records_by_type->fetch_assoc()) {
        $records_by_type[$row['form_type']] = $row['count'];
    }

    // Get department performance statistics
    $sql_departments_perf = "SELECT d.name as department_name, 
                        COUNT(r.id) as total_records,
                        SUM(CASE WHEN r.document_status = 'Approved' THEN 1 ELSE 0 END) as approved_records
                        FROM departments d
                        LEFT JOIN users u ON d.id = u.department_id
                        LEFT JOIN records r ON u.id = r.user_id
                        GROUP BY d.id
                        ORDER BY approved_records DESC";
    $result_departments_perf = $conn->query($sql_departments_perf);
    $departments_perf = [];
    while ($row = $result_departments_perf->fetch_assoc()) {
        $departments_perf[] = $row;
    }

    // Recent records
    $sql_recent_records = "SELECT r.id, r.form_type, r.period, r.document_status, r.date_submitted, 
                        u.name as user_name, d.name as department_name
                        FROM records r 
                        JOIN users u ON r.user_id = u.id 
                        LEFT JOIN departments d ON u.department_id = d.id
                        ORDER BY r.date_submitted DESC LIMIT 10";
    $result_recent_records = $conn->query($sql_recent_records);
    $recent_records = [];
    while ($row = $result_recent_records->fetch_assoc()) {
        $recent_records[] = $row;
    }
}
// Department Head dashboard data
else if ($user_role === 'department_head') {
    // Get department info
    $dept_query = "SELECT name FROM departments WHERE id = ?";
    $stmt = $conn->prepare($dept_query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $department_name = ($result->num_rows > 0) ? $result->fetch_assoc()['name'] : 'Unknown Department';
    
    // Get department staff count
    $sql_staff = "SELECT COUNT(*) as total FROM users WHERE department_id = ? AND role = 'regular_employee'";
    $stmt = $conn->prepare($sql_staff);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result_staff = $stmt->get_result();
    $total_staff = $result_staff->fetch_assoc()['total'];
    
    // Get department records count
    $sql_records = "SELECT COUNT(*) as total FROM records r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE u.department_id = ?";
    $stmt = $conn->prepare($sql_records);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result_records = $stmt->get_result();
    $total_records = $result_records->fetch_assoc()['total'];
    
    // Get pending IPCR submissions that need review
    $sql_pending = "SELECT COUNT(*) as total FROM records r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE u.department_id = ? AND r.document_status = 'Pending' AND r.form_type = 'IPCR'";
    $stmt = $conn->prepare($sql_pending);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result_pending = $stmt->get_result();
    $total_pending = $result_pending->fetch_assoc()['total'];
    
    // Get recent IPCR submissions with details for notification purposes
    $sql_recent_ipcr = "SELECT r.id, r.period, r.date_submitted, u.name as user_name 
                        FROM records r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE u.department_id = ? AND r.form_type = 'IPCR' AND r.document_status = 'Pending' 
                        ORDER BY r.date_submitted DESC LIMIT 5";
    $stmt = $conn->prepare($sql_recent_ipcr);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result_recent_ipcr = $stmt->get_result();
    $recent_ipcr = [];
    while ($row = $result_recent_ipcr->fetch_assoc()) {
        // Check if this is a recent submission (within the last 24 hours)
        $submitted_time = strtotime($row['date_submitted']);
        $current_time = time();
        $row['is_new'] = ($current_time - $submitted_time) < 86400; // 24 hours in seconds
        $recent_ipcr[] = $row;
    }
    
    // Get records by type for this department
    $sql_records_by_type = "SELECT r.form_type, COUNT(*) as count 
                            FROM records r 
                            JOIN users u ON r.user_id = u.id 
                            WHERE u.department_id = ? 
                            GROUP BY r.form_type";
    $stmt = $conn->prepare($sql_records_by_type);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result_records_by_type = $stmt->get_result();
    while ($row = $result_records_by_type->fetch_assoc()) {
        $records_by_type[$row['form_type']] = $row['count'];
    }
}

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
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .card-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
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
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        .pulse-bg {
            animation: pulse-bg 1.5s infinite;
        }
        @keyframes pulse-bg {
            0% {
                background-color: rgba(220, 53, 69, 0.7);
            }
            70% {
                background-color: rgba(220, 53, 69, 1);
            }
            100% {
                background-color: rgba(220, 53, 69, 0.7);
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
            <?php if ($user_role === 'president'): ?>
            <!-- PRESIDENT DASHBOARD -->
            <h1 class="h3 mb-4">President Dashboard</h1>
            
            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-building card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_departments; ?></h2>
                            <p class="mb-0">Departments</p>
                        </div>
                        <div class="card-footer bg-primary border-0 text-center">
                            <a href="all_dpcr.php" class="text-white">View DPCR Reports <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-success text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-people card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_employees; ?></h2>
                            <p class="mb-0">Employees</p>
                        </div>
                        <div class="card-footer bg-success border-0 text-center">
                            <a href="all_ipcr.php" class="text-white">View IPCR Reports <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-info text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-journal-text card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo isset($records_by_type['IDP']) ? $records_by_type['IDP'] : 0; ?></h2>
                            <p class="mb-0">IDp Reports</p>
                        </div>
                        <div class="card-footer bg-info border-0 text-center">
                            <a href="all_idp.php" class="text-white">View IDP Reports <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-warning text-dark h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-clipboard-check card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_pending; ?></h2>
                            <p class="mb-0">Pending Reviews</p>
                        </div>
                        <div class="card-footer bg-warning border-0 text-center">
                            <a href="records.php?status=Pending" class="text-dark">View Pending Reports <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Department Performance Row -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Department Performance Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Total Records</th>
                                            <th>Approved Reports</th>
                                            <th>Completion Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departments_perf as $dept): ?>
                                        <?php 
                                            $completion_rate = ($dept['total_records'] > 0) ? 
                                                round(($dept['approved_records'] / $dept['total_records']) * 100) : 0;
                                            
                                            $bar_class = "bg-danger";
                                            if ($completion_rate >= 80) {
                                                $bar_class = "bg-success";
                                            } elseif ($completion_rate >= 50) {
                                                $bar_class = "bg-warning";
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                            <td><?php echo $dept['total_records']; ?></td>
                                            <td><?php echo $dept['approved_records']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                        <div class="progress-bar <?php echo $bar_class; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $completion_rate; ?>%">
                                                        </div>
                                                    </div>
                                                    <span class="text-nowrap"><?php echo $completion_rate; ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Reports Row -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Recent Record Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Department</th>
                                            <th>Type</th>
                                            <th>Period</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_records as $record): ?>
                                        <?php
                                            $status_class = 'secondary';
                                            switch ($record['document_status']) {
                                                case 'Approved':
                                                    $status_class = 'success';
                                                    break;
                                                case 'Pending':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'For Revision':
                                                    $status_class = 'danger';
                                                    break;
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['department_name']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo $record['form_type']; ?></span></td>
                                            <td><?php echo htmlspecialchars($record['period']); ?></td>
                                            <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo $record['document_status']; ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($record['date_submitted'])); ?></td>
                                            <td>
                                                <a href="view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php elseif ($user_role === 'department_head'): ?>
            <!-- DEPARTMENT HEAD DASHBOARD -->
            <h1 class="h3 mb-4">Department Head Dashboard - <?php echo htmlspecialchars($department_name); ?></h1>
            
            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-people card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_staff; ?></h2>
                            <p class="mb-0">Staff Members</p>
                        </div>
                        <div class="card-footer bg-primary border-0 text-center">
                            <a href="staff_ipcr.php" class="text-white">View Staff <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-success text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-building card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo isset($records_by_type['DPCR']) ? $records_by_type['DPCR'] : 0; ?></h2>
                            <p class="mb-0">DPCR Reports</p>
                        </div>
                        <div class="card-footer bg-success border-0 text-center">
                            <a href="dpcr.php" class="text-white">Manage DPCR <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-info text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-person-vcard card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo isset($records_by_type['IPCR']) ? $records_by_type['IPCR'] : 0; ?></h2>
                            <p class="mb-0">IPCR Reports</p>
                        </div>
                        <div class="card-footer bg-info border-0 text-center">
                            <a href="staff_ipcr.php" class="text-white">Staff IPCR <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card <?php echo ($total_pending > 0) ? 'bg-warning pulse-bg' : 'bg-warning'; ?> text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center position-relative">
                            <?php if ($total_pending > 0): ?>
                            <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-warning">
                                <?php echo $total_pending; ?>
                                <span class="visually-hidden">pending reviews</span>
                            </span>
                            <?php endif; ?>
                            <i class="bi bi-clipboard-check card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_pending; ?></h2>
                            <p class="mb-0">Pending Reviews</p>
                        </div>
                        <div class="card-footer <?php echo ($total_pending > 0) ? 'bg-warning' : 'bg-warning'; ?> border-0 text-center">
                            <a href="staff_ipcr.php?status=Pending" class="text-white">Review Now <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (count($recent_ipcr) > 0): ?>
            <!-- Recent IPCR Submissions -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent IPCR Submissions</h5>
                            <?php if ($total_pending > 0): ?>
                            <span class="badge bg-warning"><?php echo $total_pending; ?> Pending Review</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($recent_ipcr as $ipcr): ?>
                                <a href="view_record.php?id=<?php echo $ipcr['id']; ?>" class="list-group-item list-group-item-action <?php echo $ipcr['is_new'] ? 'list-group-item-warning' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h5 class="mb-1">
                                            <?php echo htmlspecialchars($ipcr['user_name']); ?>
                                            <?php if ($ipcr['is_new']): ?>
                                            <span class="badge bg-warning ms-2">New</span>
                                            <?php endif; ?>
                                        </h5>
                                        <small><?php echo date('M d, Y h:i A', strtotime($ipcr['date_submitted'])); ?></small>
                                    </div>
                                    <p class="mb-1">Period: <?php echo htmlspecialchars($ipcr['period']); ?></p>
                                    <small class="text-muted">
                                        Submitted <?php echo human_time_diff(strtotime($ipcr['date_submitted']), time()); ?> ago
                                    </small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-center">
                            <a href="staff_ipcr.php" class="btn btn-primary">View All Staff IPCR</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Helper function to format time differences in a human-readable way
 */
function human_time_diff($from, $to = '') {
    if (empty($to)) {
        $to = time();
    }
    
    $diff = $to - $from;
    
    if ($diff < 60) {
        return 'less than a minute';
    }
    if ($diff < 3600) {
        $mins = round($diff / 60);
        return $mins . ' minute' . ($mins == 1 ? '' : 's');
    }
    if ($diff < 86400) {
        $hours = round($diff / 3600);
        return $hours . ' hour' . ($hours == 1 ? '' : 's');
    }
    $days = round($diff / 86400);
    return $days . ' day' . ($days == 1 ? '' : 's');
}
?> 