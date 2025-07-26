<?php
require_once 'config.php';
require_once 'auth_functions.php';

// Require admin access
require_admin();

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_user'])) {
        // Add new user
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
            $message = 'All fields are required for new users.';
            $message_type = 'error';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long.';
            $message_type = 'error';
        } else {
            if (create_user($conn, $username, $email, $password, $first_name, $last_name, $is_admin)) {
                $message = "User '$username' created successfully!";
                $message_type = 'success';
            } else {
                $message = 'Error creating user. Username or email may already exist.';
                $message_type = 'error';
            }
        }
        
    } elseif (isset($_POST['update_user'])) {
        // Update existing user
        $user_id = (int)$_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
            $message = 'All fields except password are required.';
            $message_type = 'error';
        } else {
            if (update_user($conn, $user_id, $username, $email, $first_name, $last_name, $is_admin, $is_active)) {
                $message = "User '$username' updated successfully!";
                $message_type = 'success';
                
                // Update password if provided
                if (!empty($_POST['new_password'])) {
                    if (strlen($_POST['new_password']) < 6) {
                        $message .= ' Warning: Password not updated - must be at least 6 characters.';
                    } else {
                        if (change_password($conn, $user_id, $_POST['new_password'])) {
                            $message .= ' Password updated.';
                        } else {
                            $message .= ' Warning: Password update failed.';
                        }
                    }
                }
            } else {
                $message = 'Error updating user. Username or email may already exist.';
                $message_type = 'error';
            }
        }
        
    } elseif (isset($_POST['toggle_status'])) {
        // Toggle user active status
        $user_id = (int)$_POST['user_id'];
        $current_status = (int)$_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $sql = "UPDATE users SET IsActive = ? WHERE UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $new_status, $user_id);
        
        if ($stmt->execute()) {
            $action = $new_status ? 'activated' : 'deactivated';
            $message = "User $action successfully!";
            $message_type = 'success';
        } else {
            $message = 'Error updating user status.';
            $message_type = 'error';
        }
        
    } elseif (isset($_POST['delete_user'])) {
        // Delete user (with safety checks)
        $user_id = (int)$_POST['user_id'];
        $confirm_delete = $_POST['confirm_delete'] ?? '';
        
        // Prevent deletion of current user
        if ($user_id == $current_user['user_id']) {
            $message = 'You cannot delete your own account.';
            $message_type = 'error';
        } elseif ($confirm_delete !== 'DELETE') {
            $message = 'Please type "DELETE" to confirm user deletion.';
            $message_type = 'error';
        } else {
            // Check if user has log entries
            $has_entries = false;
            $entry_count = 0;
            $tables = ['mainengines', 'generators', 'gears'];
            
            foreach ($tables as $table) {
                $check_sql = "SELECT COUNT(*) as count FROM $table WHERE RecordedBy = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param('i', $user_id);
                $check_stmt->execute();
                $count_result = $check_stmt->get_result()->fetch_assoc();
                $entry_count += $count_result['count'];
                
                if ($count_result['count'] > 0) {
                    $has_entries = true;
                }
            }
            
            if ($has_entries) {
                // Set RecordedBy to NULL for existing entries before deleting user
                foreach ($tables as $table) {
                    $update_sql = "UPDATE $table SET RecordedBy = NULL WHERE RecordedBy = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param('i', $user_id);
                    $update_stmt->execute();
                }
            }
            
            // Delete the user
            $delete_sql = "DELETE FROM users WHERE UserID = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param('i', $user_id);
            
            if ($delete_stmt->execute()) {
                $message = "User deleted successfully!";
                if ($has_entries) {
                    $message .= " ($entry_count log entries were updated to remove user reference)";
                }
                $message_type = 'success';
            } else {
                $message = 'Error deleting user: ' . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// Get all users
$users = get_all_users($conn);
$current_user = get_logged_in_user();

// Get user being edited if specified
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($users as $user) {
        if ($user['UserID'] == $edit_id) {
            $edit_user = $user;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Engine Room Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-management {
            max-width: 1200px;
            margin: 0 auto;
        }
        .user-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .form-group input, .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th {
            background: #34495e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .users-table tr:hover {
            background: #f8f9fa;
        }
        .user-status {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .user-status.active {
            background: #d4edda;
            color: #155724;
        }
        .user-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .user-role {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .user-role.admin {
            background: #fff3cd;
            color: #856404;
        }
        .user-role.user {
            background: #e2e3e5;
            color: #383d41;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            text-decoration: none;
            border-radius: 3px;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .btn-toggle {
            background: #e67e22;
            color: white;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .btn-edit:hover {
            background: #2980b9;
        }
        .btn-toggle:hover {
            background: #d35400;
        }
        .btn-delete:hover {
            background: #c0392b;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .cancel-edit {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }
        .cancel-edit:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="container user-management">
        <header>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>👥 User Management</h1>
                    <p><a href="index.php" class="btn btn-info">← Back to Home</a></p>
                </div>
                <div style="text-align: right;">
                    <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                        Admin: <strong><?= htmlspecialchars($current_user['full_name']) ?></strong>
                    </div>
                    <a href="logout.php" class="btn btn-secondary" style="font-size: 12px;">Logout</a>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit User Form -->
        <div class="user-form">
            <div class="section-header">
                <h2><?= $edit_user ? '✏️ Edit User' : '➕ Add New User' ?></h2>
                <?php if ($edit_user): ?>
                    <a href="?action=manage" class="cancel-edit">Cancel Edit</a>
                <?php endif; ?>
            </div>
            
            <form method="POST" autocomplete="on">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?= $edit_user['UserID'] ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required 
                               autocomplete="username"
                               value="<?= htmlspecialchars($edit_user['Username'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               autocomplete="email"
                               value="<?= htmlspecialchars($edit_user['Email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required 
                               autocomplete="given-name"
                               value="<?= htmlspecialchars($edit_user['FirstName'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required 
                               autocomplete="family-name"
                               value="<?= htmlspecialchars($edit_user['LastName'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?= $edit_user ? 'New Password (leave blank to keep current)' : 'Password *' ?></label>
                        <input type="password" id="password" name="<?= $edit_user ? 'new_password' : 'password' ?>" 
                               <?= $edit_user ? '' : 'required' ?> minlength="6" 
                               autocomplete="<?= $edit_user ? 'new-password' : 'new-password' ?>"
                               placeholder="<?= $edit_user ? 'Enter new password or leave blank' : 'At least 6 characters' ?>">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_admin" name="is_admin" 
                                   <?= ($edit_user && $edit_user['IsAdmin']) ? 'checked' : '' ?>>
                            <label for="is_admin">Administrator</label>
                        </div>
                        
                        <?php if ($edit_user): ?>
                            <div class="checkbox-group" style="margin-top: 10px;">
                                <input type="checkbox" id="is_active" name="is_active" 
                                       <?= ($edit_user && $edit_user['IsActive']) ? 'checked' : '' ?>>
                                <label for="is_active">Active</label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" name="<?= $edit_user ? 'update_user' : 'add_user' ?>" class="btn btn-success">
                    <?= $edit_user ? '💾 Update User' : '➕ Add User' ?>
                </button>
            </form>
        </div>

        <!-- Users List -->
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']) ?></strong><br>
                                <small style="color: #666;">@<?= htmlspecialchars($user['Username']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($user['Email']) ?></td>
                            <td>
                                <span class="user-role <?= $user['IsAdmin'] ? 'admin' : 'user' ?>">
                                    <?= $user['IsAdmin'] ? '👑 Admin' : '👤 User' ?>
                                </span>
                            </td>
                            <td>
                                <span class="user-status <?= $user['IsActive'] ? 'active' : 'inactive' ?>">
                                    <?= $user['IsActive'] ? '✅ Active' : '❌ Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($user['CreatedDate'])) ?></td>
                            <td>
                                <?= $user['LastLogin'] ? date('M j, Y g:i A', strtotime($user['LastLogin'])) : 'Never' ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?= $user['UserID'] ?>" class="btn-small btn-edit">✏️ Edit</a>
                                    
                                    <?php if ($user['UserID'] != $current_user['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['UserID'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $user['IsActive'] ?>">
                                            <button type="submit" name="toggle_status" class="btn-small btn-toggle"
                                                    onclick="return confirm('<?= $user['IsActive'] ? 'Deactivate' : 'Activate' ?> this user?')">
                                                <?= $user['IsActive'] ? '⏸️ Deactivate' : '▶️ Activate' ?>
                                            </button>
                                        </form>
                                        
                                        <button onclick="showDeleteModal(<?= $user['UserID'] ?>, '<?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName'], ENT_QUOTES) ?>')" 
                                                class="btn-small btn-delete">🗑️ Delete</button>
                                    <?php else: ?>
                                        <span class="btn-small" style="background: #e9ecef; color: #6c757d;">You</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; width: 90%;">
            <h3 style="color: #e74c3c; margin-top: 0;">⚠️ Delete User</h3>
            <p>Are you sure you want to permanently delete <strong id="deleteUserName"></strong>?</p>
            <p style="color: #666; font-size: 14px;">This action cannot be undone. Any log entries created by this user will have their "Recorded By" field cleared.</p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div style="margin: 20px 0;">
                    <label for="confirmDelete" style="display: block; margin-bottom: 5px; font-weight: bold;">Type "DELETE" to confirm:</label>
                    <input type="text" name="confirm_delete" id="confirmDelete" 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"
                           placeholder="Type DELETE to confirm" required>
                </div>
                
                <div style="text-align: right; gap: 10px; display: flex; justify-content: flex-end;">
                    <button type="button" onclick="hideDeleteModal()" 
                            style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="submit" name="delete_user" 
                            style="padding: 10px 20px; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        🗑️ Delete User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showDeleteModal(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('confirmDelete').value = '';
            document.getElementById('deleteModal').style.display = 'block';
        }

        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideDeleteModal();
            }
        });
    </script>
</body>
</html>
