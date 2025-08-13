<?php
// Step 3: Admin User Setup
$error = '';
$success = '';

if ($_POST) {
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_password_confirm = $_POST['admin_password_confirm'] ?? '';
    $admin_role = $_POST['admin_role'] ?? 'captain';
    
    // Validation
    if (empty($admin_name) || empty($admin_email) || empty($admin_password)) {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($admin_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($admin_password !== $admin_password_confirm) {
        $error = 'Password confirmation does not match.';
    } else {
        // Store admin info in session
        $_SESSION['install_data']['admin_name'] = $admin_name;
        $_SESSION['install_data']['admin_email'] = $admin_email;
        $_SESSION['install_data']['admin_password'] = password_hash($admin_password, PASSWORD_DEFAULT);
        $_SESSION['install_data']['admin_role'] = $admin_role;
        
        header('Location: ?step=4');
        exit;
    }
}

$data = $_SESSION['install_data'] ?? [];
?>

<h2>Admin User Setup</h2>
<p>Create the primary administrator account for your vessel management system.</p>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
    <div class="form-group">
        <label for="admin_name">Administrator Name *</label>
        <input type="text" name="admin_name" id="admin_name" 
               value="<?php echo htmlspecialchars($data['admin_name'] ?? ''); ?>" 
               placeholder="Captain John Smith" required>
    </div>

    <div class="form-group">
        <label for="admin_email">Administrator Email *</label>
        <input type="email" name="admin_email" id="admin_email" 
               value="<?php echo htmlspecialchars($data['admin_email'] ?? ''); ?>" 
               placeholder="captain@acmemarine.com" required>
        <small style="color: #666;">This will be your login username</small>
    </div>

    <div class="form-group">
        <label for="admin_role">Primary Role</label>
        <select name="admin_role" id="admin_role">
            <option value="captain" <?php echo ($data['admin_role'] ?? '') == 'captain' ? 'selected' : ''; ?>>Captain</option>
            <option value="owner" <?php echo ($data['admin_role'] ?? '') == 'owner' ? 'selected' : ''; ?>>Owner/Manager</option>
            <option value="engineer" <?php echo ($data['admin_role'] ?? '') == 'engineer' ? 'selected' : ''; ?>>Chief Engineer</option>
            <option value="admin" <?php echo ($data['admin_role'] ?? '') == 'admin' ? 'selected' : ''; ?>>System Administrator</option>
        </select>
    </div>

    <div class="form-group">
        <label for="admin_password">Password *</label>
        <input type="password" name="admin_password" id="admin_password" 
               placeholder="Minimum 8 characters" required>
    </div>

    <div class="form-group">
        <label for="admin_password_confirm">Confirm Password *</label>
        <input type="password" name="admin_password_confirm" id="admin_password_confirm" 
               placeholder="Enter password again" required>
    </div>

    <div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 15px 0;">
        <h4>🔐 Security Note:</h4>
        <ul style="margin: 5px 0 0 20px;">
            <li>This account will have full system access</li>
            <li>You can add additional users after installation</li>
            <li>Use a strong password for security</li>
            <li>This email will receive system notifications</li>
        </ul>
    </div>

    <div style="margin-top: 20px;">
        <a href="?step=2" style="margin-right: 10px; text-decoration: none; color: #666;">← Back</a>
        <button type="submit" class="btn">Continue →</button>
    </div>
</form>
