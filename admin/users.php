<?php
// Set page title
$page_title = "User Management - EPMS";

// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../access_denied.php");
    exit();
}

// Database connection
require_once '../includes/db_connect.php';

// Process form submissions for adding/editing users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $department_id = ($_POST['department_id'] !== '') ? $_POST['department_id'] : null;
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $email, $password, $role, $department_id);
        
        if ($stmt->execute()) {
            $success_message = "User added successfully!";
            
            // If the user is set as department head, update the department
            if ($role === 'department_head' && $department_id !== null) {
                $update_dept = $conn->prepare("UPDATE departments SET head_id = LAST_INSERT_ID() WHERE id = ?");
                $update_dept->bind_param("i", $department_id);
                $update_dept->execute();
            }
        } else {
            $error_message = "Error adding user: " . $stmt->error;
        }
    } elseif (isset($_POST['edit_user'])) {
        // Edit existing user
        $user_id = $_POST['user_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $department_id = ($_POST['department_id'] !== '') ? $_POST['department_id'] : null;
        
        // Get the current role and department for the user
        $current_data_stmt = $conn->prepare("SELECT role, department_id FROM users WHERE id = ?");
        $current_data_stmt->bind_param("i", $user_id);
        $current_data_stmt->execute();
        $current_data_result = $current_data_stmt->get_result();
        $current_data = $current_data_result->fetch_assoc();
        
        if (isset($_POST['update_password']) && !empty($_POST['password'])) {
            // Update user with new password
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, department_id = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $name, $email, $password, $role, $department_id, $user_id);
        } else {
            // Update user without changing password
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ?, department_id = ? WHERE id = ?");
            $stmt->bind_param("sssii", $name, $email, $role, $department_id, $user_id);
        }
        
        if ($stmt->execute()) {
            $success_message = "User updated successfully!";
            
            // Handle department head role changes
            if ($current_data['role'] !== 'department_head' && $role === 'department_head' && $department_id !== null) {
                // User became a department head, update the department
                $update_dept = $conn->prepare("UPDATE departments SET head_id = ? WHERE id = ?");
                $update_dept->bind_param("ii", $user_id, $department_id);
                $update_dept->execute();
            } elseif ($current_data['role'] === 'department_head' && $role !== 'department_head') {
                // User is no longer a department head, remove as head
                $update_dept = $conn->prepare("UPDATE departments SET head_id = NULL WHERE head_id = ?");
                $update_dept->bind_param("i", $user_id);
                $update_dept->execute();
            } elseif ($role === 'department_head' && $current_data['department_id'] !== $department_id) {
                // Department head changed departments
                // Remove as head from old department
                if ($current_data['department_id'] !== null) {
                    $remove_old = $conn->prepare("UPDATE departments SET head_id = NULL WHERE head_id = ?");
                    $remove_old->bind_param("i", $user_id);
                    $remove_old->execute();
                }
                
                // Add as head to new department
                if ($department_id !== null) {
                    $add_new = $conn->prepare("UPDATE departments SET head_id = ? WHERE id = ?");
                    $add_new->bind_param("ii", $user_id, $department_id);
                    $add_new->execute();
                }
            }
        } else {
            $error_message = "Error updating user: " . $stmt->error;
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = $_POST['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Check if user is a department head
            $check_head = $conn->prepare("SELECT id FROM departments WHERE head_id = ?");
            $check_head->bind_param("i", $user_id);
            $check_head->execute();
            $is_head_result = $check_head->get_result();
            
            if ($is_head_result->num_rows > 0) {
                // Remove as department head
                $remove_head = $conn->prepare("UPDATE departments SET head_id = NULL WHERE head_id = ?");
                $remove_head->bind_param("i", $user_id);
                $remove_head->execute();
            }
            
            // Delete the user's records entries
            $delete_dpcr = $conn->prepare("DELETE FROM dpcr_entries WHERE record_id IN (SELECT id FROM records WHERE user_id = ?)");
            $delete_dpcr->bind_param("i", $user_id);
            $delete_dpcr->execute();
            
            $delete_ipcr = $conn->prepare("DELETE FROM ipcr_entries WHERE record_id IN (SELECT id FROM records WHERE user_id = ?)");
            $delete_ipcr->bind_param("i", $user_id);
            $delete_ipcr->execute();
            
            $delete_idp = $conn->prepare("DELETE FROM idp_entries WHERE record_id IN (SELECT id FROM records WHERE user_id = ?)");
            $delete_idp->bind_param("i", $user_id);
            $delete_idp->execute();
            
            // Delete the user's records
            $delete_records = $conn->prepare("DELETE FROM records WHERE user_id = ?");
            $delete_records->bind_param("i", $user_id);
            $delete_records->execute();
            
            // Delete the user
            $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_user->bind_param("i", $user_id);
            $delete_user->execute();
            
            // Commit the transaction
            $conn->commit();
            $success_message = "User deleted successfully!";
        } catch (Exception $e) {
            // Rollback the transaction if an error occurred
            $conn->rollback();
            $error_message = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Get departments for dropdown
$departments_query = "SELECT id, name FROM departments ORDER BY name";
$departments_result = $conn->query($departments_query);
$departments = [];
while ($dept = $departments_result->fetch_assoc()) {
    $departments[$dept['id']] = $dept['name'];
}

// Get list of users
$users_query = "SELECT u.*, d.name as department_name 
               FROM users u 
               LEFT JOIN departments d ON u.department_id = d.id 
               ORDER BY u.name";
$users_result = $conn->query($users_query);

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
    <?php include '../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">User Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add New User
                </button>
            </div>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Users Table -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php 
                                        $role_badge = 'secondary';
                                        $role_text = 'User';
                                        switch ($user['role']) {
                                            case 'admin':
                                                $role_badge = 'danger';
                                                $role_text = 'Admin';
                                                break;
                                            case 'president':
                                                $role_badge = 'primary';
                                                $role_text = 'President';
                                                break;
                                            case 'department_head':
                                                $role_badge = 'success';
                                                $role_text = 'Department Head';
                                                break;
                                            case 'regular_employee':
                                                $role_badge = 'info';
                                                $role_text = 'Employee';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $role_badge; ?>">
                                            <?php echo $role_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($user['department_name'] ?? 'No Department'); ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary edit-user" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-role="<?php echo $user['role']; ?>"
                                                    data-department="<?php echo $user['department_id'] ?? ''; ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-user" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteUserModal"
                                                    data-id="<?php echo $user['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="president">President</option>
                                <option value="department_head">Department Head</option>
                                <option value="regular_employee" selected>Regular Employee</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="position" name="position" required>
                        </div>
                        <div class="mb-3">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">No Department</option>
                                <?php foreach ($departments as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="update_password" name="update_password">
                                <label class="form-check-label" for="update_password">
                                    Update Password
                                </label>
                            </div>
                            <div id="password_field_container" style="display: none;">
                                <label for="edit_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="president">President</option>
                                <option value="department_head">Department Head</option>
                                <option value="regular_employee">Regular Employee</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_department_id" class="form-label">Department</label>
                            <select class="form-select" id="edit_department_id" name="department_id">
                                <option value="">No Department</option>
                                <?php foreach ($departments as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user: <strong id="delete_user_name"></strong>?</p>
                    <p class="text-danger">This action cannot be undone. All records associated with this user will also be deleted.</p>
                </div>
                <form method="post">
                    <input type="hidden" id="delete_user_id" name="user_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update edit modal with user data
            document.querySelectorAll('.edit-user').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('edit_user_id').value = this.dataset.id;
                    document.getElementById('edit_name').value = this.dataset.name;
                    document.getElementById('edit_email').value = this.dataset.email;
                    document.getElementById('edit_role').value = this.dataset.role;
                    document.getElementById('edit_department_id').value = this.dataset.department;
                });
            });
            
            // Update delete modal with user data
            document.querySelectorAll('.delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_user_id').value = this.dataset.id;
                    document.getElementById('delete_user_name').textContent = this.dataset.name;
                });
            });
            
            // Show/hide password field based on checkbox
            document.getElementById('update_password').addEventListener('change', function() {
                document.getElementById('password_field_container').style.display = this.checked ? 'block' : 'none';
                if (!this.checked) {
                    document.getElementById('edit_password').value = '';
                }
            });
        });
    </script>
</body>
</html> 