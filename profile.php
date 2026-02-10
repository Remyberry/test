<?php
// Set page title
$page_title = "My Profile - EPMS";

// Include header
include_once('includes/header.php');

// Check if form is submitted
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields";
    } else {
        // Database connection
        require_once 'includes/db_connect.php';
        
        // Update user profile
        $user_id = $_SESSION['user_id'];
        
        // Check if email is already in use by another user
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Email is already in use by another user";
        } else {
            // If changing password
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error_message = "New password and confirm password do not match";
                } else {
                    // Verify current password
                    $sql = "SELECT password FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    
                    if (password_verify($current_password, $user['password'])) {
                        // Hash new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update user with new password
                        $sql = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
                        
                        if ($stmt->execute()) {
                            // Update session variables
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_email'] = $email;
                            $success_message = "Profile updated successfully";
                        } else {
                            $error_message = "Error updating profile: " . $conn->error;
                        }
                    } else {
                        $error_message = "Current password is incorrect";
                    }
                }
            } else {
                // Update user without changing password
                $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $name, $email, $user_id);
                
                if ($stmt->execute()) {
                    // Update session variables
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $success_message = "Profile updated successfully";
                } else {
                    $error_message = "Error updating profile: " . $conn->error;
                }
            }
        }
    }
}

// Get current user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_role = $_SESSION['user_role'];
$user_avatar = null;
$department_name = "";

// Database connection
require_once 'includes/db_connect.php';

// Get department name and user avatar
$sql = "SELECT d.name, u.avatar FROM departments d JOIN users u ON d.id = u.department_id WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $department_name = $row['name'];
    $user_avatar = $row['avatar'];
}

// Format role for display
$role_display = "";
switch ($user_role) {
    case 'admin':
        $role_display = "Administrator";
        break;
    case 'president':
        $role_display = "President";
        break;
    case 'department_head':
        $role_display = "Department Head";
        break;
    case 'regular_employee':
        $role_display = "Employee";
        break;
    default:
        $role_display = $user_role;
}

// Handle avatar upload success/error messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<!-- Profile Content -->
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar-container mx-auto mb-3" style="width: 150px; height: 150px; position: relative;">
                        <?php if ($user_avatar): ?>
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="avatar" class="profile-avatar">
                        <?php else: ?>
                            <img src="images/default-avatar.png" alt="default avatar" class="profile-avatar">
                        <?php endif; ?>
                        <div class="avatar-edit" data-bs-toggle="modal" data-bs-target="#changeAvatarModal">
                            <i class="bi bi-pencil-fill"></i>
                        </div>
                    </div>
                    <h5 class="my-3"><?php echo $user_name; ?></h5>
                    <p class="text-muted mb-1"><?php echo $role_display; ?></p>
                    <p class="text-muted mb-4"><?php echo $department_name; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
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
                    
                    <form method="POST" action="profile.php">
                        <div class="mb-3 row">
                            <label for="name" class="col-sm-3 col-form-label">Name</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $user_name; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="email" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user_email; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="role" class="col-sm-3 col-form-label">Role</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control-plaintext" id="role" value="<?php echo $role_display; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="department" class="col-sm-3 col-form-label">Department</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control-plaintext" id="department" value="<?php echo $department_name; ?>" readonly>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Change Password</h5>
                        <p class="text-muted mb-3">Leave blank if you don't want to change your password</p>
                        
                        <div class="mb-3 row">
                            <label for="current_password" class="col-sm-3 col-form-label">Current Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">
                                    Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, and one number.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 row">
                            <label for="confirm_password" class="col-sm-3 col-form-label">Confirm Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Avatar Modal -->
<div class="modal fade" id="changeAvatarModal" tabindex="-1" aria-labelledby="changeAvatarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeAvatarModalLabel">Change Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="avatar-form" action="update_avatar.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="avatar-upload" class="form-label">Select new profile picture</label>
                        <input class="form-control" type="file" id="avatar-upload" name="avatar" accept="image/*">
                        <div class="form-text">
                            Maximum file size: 2MB. Allowed formats: JPEG, PNG, GIF.
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <div class="preview-container" style="display: none;">
                            <p>Preview:</p>
                            <img id="avatar-preview" src="#" alt="Avatar Preview" style="max-width: 200px; max-height: 200px; border-radius: 50%;">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="avatar-form" class="btn btn-primary">Upload Image</button>
            </div>
        </div>
    </div>
</div>

<!-- Add the CSS link in the header -->
<link href="assets/css/profile.css" rel="stylesheet">

<!-- Add the JavaScript file -->
<script src="assets/js/profile.js"></script>

<?php
// Include footer
include_once('includes/footer.php');

$conn->close();
?> 