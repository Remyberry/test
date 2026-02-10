<?php
// Set page title
$page_title = "Admin Dashboard - EPMS";

// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../access_denied.php");
    exit();
}

// Database connection
require_once '../includes/db_connect.php';

// Get counts for dashboard
// Total users
$sql_users = "SELECT COUNT(*) as total FROM users";
$result_users = $conn->query($sql_users);
$total_users = $result_users->fetch_assoc()['total'];

// Total departments
$sql_departments = "SELECT COUNT(*) as total FROM departments";
$result_departments = $conn->query($sql_departments);
$total_departments = $result_departments->fetch_assoc()['total'];

// Total records
$sql_records = "SELECT COUNT(*) as total FROM records";
$result_records = $conn->query($sql_records);
$total_records = $result_records->fetch_assoc()['total'];

// Records by type
$sql_records_by_type = "SELECT form_type, COUNT(*) as count FROM records GROUP BY form_type";
$result_records_by_type = $conn->query($sql_records_by_type);
$records_by_type = [];
while ($row = $result_records_by_type->fetch_assoc()) {
    $records_by_type[$row['form_type']] = $row['count'];
}

// Records by status
$sql_records_by_status = "SELECT document_status, COUNT(*) as count FROM records GROUP BY document_status";
$result_records_by_status = $conn->query($sql_records_by_status);
$records_by_status = [];
while ($row = $result_records_by_status->fetch_assoc()) {
    $records_by_status[$row['document_status']] = $row['count'];
}

// Recent users
$sql_recent_users = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5";
$result_recent_users = $conn->query($sql_recent_users);
$recent_users = [];
while ($row = $result_recent_users->fetch_assoc()) {
    $recent_users[] = $row;
}

// Recent records
$sql_recent_records = "SELECT r.id, r.form_type, r.period, r.document_status, r.date_submitted, u.name as user_name 
                      FROM records r 
                      JOIN users u ON r.user_id = u.id 
                      ORDER BY r.date_submitted DESC LIMIT 5";
$result_recent_records = $conn->query($sql_recent_records);
$recent_records = [];
while ($row = $result_recent_records->fetch_assoc()) {
    $recent_records[] = $row;
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
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <h1 class="h3 mb-4">Admin Dashboard</h1>
            
            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-people card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_users; ?></h2>
                            <p class="mb-0">Total Users</p>
                        </div>
                        <div class="card-footer bg-primary border-0 text-center">
                            <a href="users.php" class="text-white">Manage Users <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-success text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-building card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_departments; ?></h2>
                            <p class="mb-0">Departments</p>
                        </div>
                        <div class="card-footer bg-success border-0 text-center">
                            <a href="departments.php" class="text-white">Manage Departments <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-info text-white h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-text card-icon"></i>
                            <h2 class="display-4 fw-bold"><?php echo $total_records; ?></h2>
                            <p class="mb-0">Total Records</p>
                        </div>
                        <div class="card-footer bg-info border-0 text-center">
                            <a href="reports.php" class="text-white">View Reports <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card stat-card bg-warning text-dark h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-gear card-icon"></i>
                            <h2 class="display-4 fw-bold">
                                <?php echo isset($records_by_status['Pending']) ? $records_by_status['Pending'] : 0; ?>
                            </h2>
                            <p class="mb-0">Pending Reviews</p>
                        </div>
                        <div class="card-footer bg-warning border-0 text-center">
                            <a href="settings.php" class="text-dark">System Settings <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Data Row -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Records by Type</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Form Type</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $form_types = ['DPCR', 'IPCR', 'IDP'];
                                        foreach ($form_types as $type): 
                                            $count = isset($records_by_type[$type]) ? $records_by_type[$type] : 0;
                                            $percentage = $total_records > 0 ? round(($count / $total_records) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo $type; ?></td>
                                            <td><?php echo $count; ?></td>
                                            <td>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <small><?php echo $percentage; ?>%</small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Records by Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $statuses = ['Draft', 'Pending', 'Approved', 'Rejected'];
                                        foreach ($statuses as $status): 
                                            $count = isset($records_by_status[$status]) ? $records_by_status[$status] : 0;
                                            $percentage = $total_records > 0 ? round(($count / $total_records) * 100, 1) : 0;
                                            
                                            $bar_class = 'bg-secondary';
                                            switch ($status) {
                                                case 'Draft': $bar_class = 'bg-secondary'; break;
                                                case 'Pending': $bar_class = 'bg-warning'; break;
                                                case 'Approved': $bar_class = 'bg-success'; break;
                                                case 'Rejected': $bar_class = 'bg-danger'; break;
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $status; ?></td>
                                            <td><?php echo $count; ?></td>
                                            <td>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar <?php echo $bar_class; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <small><?php echo $percentage; ?>%</small>
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
            
            <!-- Recent Activities Row -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Users</h5>
                            <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_users)): ?>
                                <p class="text-center text-muted my-3">No recent users found</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <?php 
                                                    $badge_class = 'bg-secondary';
                                                    switch ($user['role']) {
                                                        case 'admin': $badge_class = 'bg-danger'; break;
                                                        case 'president': $badge_class = 'bg-primary'; break;
                                                        case 'department_head': $badge_class = 'bg-success'; break;
                                                        case 'regular_employee': $badge_class = 'bg-info'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Reports</h5>
                            <a href="reports.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_records)): ?>
                                <p class="text-center text-muted my-3">No recent records found</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Employee</th>
                                                <th>Period</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_records as $record): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $icon_class = '';
                                                    switch ($record['form_type']) {
                                                        case 'DPCR': $icon_class = 'bi-building text-info'; break;
                                                        case 'IPCR': $icon_class = 'bi-person-vcard text-primary'; break;
                                                        case 'IDP': $icon_class = 'bi-journal-text text-success'; break;
                                                    }
                                                    ?>
                                                    <i class="bi <?php echo $icon_class; ?> me-1"></i>
                                                    <?php echo $record['form_type']; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['period']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_badge = 'secondary';
                                                    switch ($record['document_status']) {
                                                        case 'Draft': $status_badge = 'secondary'; break;
                                                        case 'Approved': $status_badge = 'success'; break;
                                                        case 'Pending': $status_badge = 'warning'; break;
                                                        case 'Rejected': $status_badge = 'danger'; break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_badge; ?>">
                                                        <?php echo $record['document_status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    echo $record['date_submitted'] 
                                                        ? date('M d, Y', strtotime($record['date_submitted'])) 
                                                        : '<span class="text-muted">Not submitted</span>';
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Settings Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">System Settings</h6>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#settingsModal">
                        <i class="bi bi-gear"></i> Manage Settings
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Setting</th>
                                    <th>Value</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get system settings
                                $settings_query = "SELECT * FROM system_settings ORDER BY id ASC";
                                $settings_result = $conn->query($settings_query);
                                
                                if ($settings_result->num_rows > 0) {
                                    while ($setting = $settings_result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($setting['setting_key']) . '</td>';
                                        echo '<td>' . htmlspecialchars($setting['setting_value']) . '</td>';
                                        echo '<td>' . htmlspecialchars($setting['description']) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="text-center">No settings found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

<?php
/**
 * Get a setting value from the database
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed Setting value or default
 */
function getSetting($key, $default = '') {
    global $conn;
    $query = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['setting_value'];
    }
    
    return $default;
}
?>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel">Manage System Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="settingsForm" action="update_settings.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">DPCR Computation Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="dpcr_computation_type" id="type1" value="Type1" 
                                <?php echo (getSetting('dpcr_computation_type') === 'Type1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="type1">
                                Type 1: Strategic (45%) and Core (55%)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="dpcr_computation_type" id="type2" value="Type2"
                                <?php echo (getSetting('dpcr_computation_type') === 'Type2') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="type2">
                                Type 2: Strategic (45%), Core (45%), and Support (10%)
                            </label>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">IPCR Rating Weights</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="quality_weight" class="form-label">Quality Weight (%)</label>
                                <input type="number" class="form-control" id="quality_weight" name="quality_weight" 
                                    value="<?php echo getSetting('quality_weight'); ?>" min="0" max="100" required>
                            </div>
                            <div class="col-md-4">
                                <label for="efficiency_weight" class="form-label">Efficiency Weight (%)</label>
                                <input type="number" class="form-control" id="efficiency_weight" name="efficiency_weight" 
                                    value="<?php echo getSetting('efficiency_weight'); ?>" min="0" max="100" required>
                            </div>
                            <div class="col-md-4">
                                <label for="timeliness_weight" class="form-label">Timeliness Weight (%)</label>
                                <input type="number" class="form-control" id="timeliness_weight" name="timeliness_weight" 
                                    value="<?php echo getSetting('timeliness_weight'); ?>" min="0" max="100" required>
                            </div>
                        </div>
                        <div class="form-text">The sum of these weights should equal 100%.</div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="system_name" class="form-label fw-bold">System Name</label>
                        <input type="text" class="form-control" id="system_name" name="system_name" 
                            value="<?php echo getSetting('system_name', 'Employee Performance Management System'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="organization_name" class="form-label fw-bold">Organization Name</label>
                        <input type="text" class="form-control" id="organization_name" name="organization_name" 
                            value="<?php echo getSetting('organization_name', 'City College of Angeles'); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="fiscal_year" class="form-label fw-bold">Current Fiscal Year</label>
                        <input type="number" class="form-control" id="fiscal_year" name="fiscal_year" 
                            value="<?php echo getSetting('fiscal_year', date('Y')); ?>" min="2020" max="2050">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="settingsForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation for IPCR weights
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const qualityWeight = parseInt(document.getElementById('quality_weight').value) || 0;
            const efficiencyWeight = parseInt(document.getElementById('efficiency_weight').value) || 0;
            const timelinessWeight = parseInt(document.getElementById('timeliness_weight').value) || 0;
            
            const totalWeight = qualityWeight + efficiencyWeight + timelinessWeight;
            
            if (totalWeight !== 100) {
                e.preventDefault();
                alert('IPCR rating weights must sum to 100%. Current total: ' + totalWeight + '%');
            }
        });
    });
    </script>
</body>
</html> 