<?php
/**
 * Company Admin Dashboard
 * Multi-tenant SaaS administration interface for company management
 * Handles onboarding, user management, subscription management
 */

session_start();
require_once __DIR__ . '/config_saas.php';

// Check if user is logged in as a company admin
if (!isset($_SESSION['company_id']) || !isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit;
}

$company_id = $_SESSION['company_id'];
$user_role = $_SESSION['user_role'];

// Only allow admin/owner access to this dashboard
if (!in_array($user_role, ['owner', 'admin'])) {
    header('Location: vessel/engineroom/dashboard.php');
    exit;
}

// Load company data
$company_data = getCompanyData($company_id);
if (!$company_data) {
    die('Company not found');
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_company':
            $result = updateCompanyInfo($company_id, $_POST);
            echo json_encode($result);
            exit;
            
        case 'add_user':
            $result = addCompanyUser($company_id, $_POST);
            echo json_encode($result);
            exit;
            
        case 'update_user':
            $result = updateCompanyUser($company_id, $_POST);
            echo json_encode($result);
            exit;
            
        case 'deactivate_user':
            $result = deactivateCompanyUser($company_id, $_POST['user_id']);
            echo json_encode($result);
            exit;
            
        case 'upgrade_plan':
            $result = upgradePlan($company_id, $_POST['new_plan']);
            echo json_encode($result);
            exit;
            
        case 'get_usage_stats':
            $stats = getUsageStats($company_id);
            echo json_encode($stats);
            exit;
    }
}

// Get company users
$company_users = getCompanyUsers($company_id);
$usage_stats = getUsageStats($company_id);
$available_plans = getAvailablePlans();
$payment_history = getPaymentHistory($company_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Admin - <?php echo htmlspecialchars($company_data['company_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .plan-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .plan-card.current {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .usage-bar {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        .usage-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s;
        }
        .usage-fill.warning {
            background: linear-gradient(90deg, #ffc107, #fd7e14);
        }
        .usage-fill.danger {
            background: linear-gradient(90deg, #dc3545, #e83e8c);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-ship me-2"></i>
                <?php echo htmlspecialchars($company_data['company_name']); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="office_dashboard.php">
                    <i class="fas fa-building me-1"></i> Fleet Dashboard
                </a>
                <a class="nav-link" href="vessel/engineroom/dashboard.php">
                    <i class="fas fa-cogs me-1"></i> Engine Room
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Status Banner -->
        <?php if ($company_data['subscription_plan'] === 'trial'): ?>
            <?php 
            $days_remaining = floor((strtotime($company_data['trial_end_date']) - time()) / (60 * 60 * 24));
            $banner_class = $days_remaining <= 3 ? 'alert-danger' : ($days_remaining <= 7 ? 'alert-warning' : 'alert-info');
            ?>
            <div class="alert <?php echo $banner_class; ?> alert-dismissible fade show">
                <i class="fas fa-clock me-2"></i>
                <strong>Trial Account:</strong> 
                <?php if ($days_remaining > 0): ?>
                    <?php echo $days_remaining; ?> days remaining. 
                    <a href="#" class="alert-link" onclick="showUpgradeModal()">Upgrade now</a> to continue using all features.
                <?php else: ?>
                    Your trial has expired. <a href="#" class="alert-link" onclick="showUpgradeModal()">Upgrade now</a> to restore access.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Main Tabs -->
        <ul class="nav nav-tabs" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                    <i class="fas fa-chart-line me-2"></i>Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">
                    <i class="fas fa-users me-2"></i>Users
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="subscription-tab" data-bs-toggle="tab" data-bs-target="#subscription" type="button">
                    <i class="fas fa-credit-card me-2"></i>Subscription
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="company-tab" data-bs-toggle="tab" data-bs-target="#company" type="button">
                    <i class="fas fa-building me-2"></i>Company Info
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button">
                    <i class="fas fa-receipt me-2"></i>Billing
                </button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row mt-4">
                    <!-- Usage Statistics -->
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-ship fa-2x mb-2"></i>
                                <h3><?php echo $usage_stats['vessel_count']; ?></h3>
                                <p class="mb-0">Active Vessels</p>
                                <?php if ($company_data['max_vessels'] > 0): ?>
                                    <small>of <?php echo $company_data['max_vessels']; ?> allowed</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-2x mb-2"></i>
                                <h3><?php echo $usage_stats['user_count']; ?></h3>
                                <p class="mb-0">Active Users</p>
                                <?php if ($company_data['max_users'] > 0): ?>
                                    <small>of <?php echo $company_data['max_users']; ?> allowed</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-2x mb-2"></i>
                                <h3><?php echo number_format($usage_stats['storage_used_mb']); ?> MB</h3>
                                <p class="mb-0">Storage Used</p>
                                <?php if ($company_data['max_storage_mb'] > 0): ?>
                                    <small>of <?php echo number_format($company_data['max_storage_mb']); ?> MB allowed</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar fa-2x mb-2"></i>
                                <h3><?php echo $usage_stats['logs_this_month']; ?></h3>
                                <p class="mb-0">Logs This Month</p>
                                <small>Activity Level</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usage Limits -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar me-2"></i>Resource Usage</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($company_data['max_vessels'] > 0): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Vessels</span>
                                            <span><?php echo $usage_stats['vessel_count']; ?> / <?php echo $company_data['max_vessels']; ?></span>
                                        </div>
                                        <div class="usage-bar">
                                            <?php 
                                            $vessel_percent = ($usage_stats['vessel_count'] / $company_data['max_vessels']) * 100;
                                            $vessel_class = $vessel_percent > 90 ? 'danger' : ($vessel_percent > 75 ? 'warning' : '');
                                            ?>
                                            <div class="usage-fill <?php echo $vessel_class; ?>" style="width: <?php echo min(100, $vessel_percent); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($company_data['max_users'] > 0): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Users</span>
                                            <span><?php echo $usage_stats['user_count']; ?> / <?php echo $company_data['max_users']; ?></span>
                                        </div>
                                        <div class="usage-bar">
                                            <?php 
                                            $user_percent = ($usage_stats['user_count'] / $company_data['max_users']) * 100;
                                            $user_class = $user_percent > 90 ? 'danger' : ($user_percent > 75 ? 'warning' : '');
                                            ?>
                                            <div class="usage-fill <?php echo $user_class; ?>" style="width: <?php echo min(100, $user_percent); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($company_data['max_storage_mb'] > 0): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Storage</span>
                                            <span><?php echo number_format($usage_stats['storage_used_mb']); ?> MB / <?php echo number_format($company_data['max_storage_mb']); ?> MB</span>
                                        </div>
                                        <div class="usage-bar">
                                            <?php 
                                            $storage_percent = ($usage_stats['storage_used_mb'] / $company_data['max_storage_mb']) * 100;
                                            $storage_class = $storage_percent > 90 ? 'danger' : ($storage_percent > 75 ? 'warning' : '');
                                            ?>
                                            <div class="usage-fill <?php echo $storage_class; ?>" style="width: <?php echo min(100, $storage_percent); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <a href="vessel/engineroom/dashboard.php" class="btn btn-primary w-100 mb-2">
                                            <i class="fas fa-cogs me-2"></i>Engine Room
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-success w-100 mb-2" onclick="showAddUserModal()">
                                            <i class="fas fa-user-plus me-2"></i>Add User
                                        </button>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="manage_vessels.php" class="btn btn-info w-100 mb-2">
                                            <i class="fas fa-ship me-2"></i>Manage Vessels
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-warning w-100 mb-2" onclick="showUpgradeModal()">
                                            <i class="fas fa-arrow-up me-2"></i>Upgrade Plan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4>User Management</h4>
                        <button class="btn btn-primary" onclick="showAddUserModal()">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($company_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['role'] === 'owner' ? 'danger' : ($user['role'] === 'admin' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($user['role'] !== 'owner'): ?>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deactivateUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-user-times"></i>
                                                        </button>
                                                    <?php endif; ?>
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

            <!-- Subscription Tab -->
            <div class="tab-pane fade" id="subscription" role="tabpanel">
                <div class="mt-4">
                    <h4>Subscription Management</h4>
                    
                    <div class="row mt-4">
                        <?php foreach ($available_plans as $plan): ?>
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card plan-card <?php echo $plan['plan_code'] === $company_data['subscription_plan'] ? 'current' : ''; ?>">
                                    <div class="card-header text-center">
                                        <h5><?php echo $plan['plan_name']; ?></h5>
                                        <?php if ($plan['plan_code'] === $company_data['subscription_plan']): ?>
                                            <span class="badge bg-primary">Current Plan</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <h3>$<?php echo number_format($plan['monthly_price'], 2); ?></h3>
                                            <small class="text-muted">per month</small>
                                        </div>
                                        
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-ship text-primary me-2"></i>
                                                <?php echo $plan['max_vessels'] == 0 ? 'Unlimited' : $plan['max_vessels']; ?> Vessels
                                            </li>
                                            <li><i class="fas fa-users text-primary me-2"></i>
                                                <?php echo $plan['max_users'] == 0 ? 'Unlimited' : $plan['max_users']; ?> Users
                                            </li>
                                            <li><i class="fas fa-database text-primary me-2"></i>
                                                <?php echo $plan['max_storage_mb'] == 0 ? 'Unlimited' : number_format($plan['max_storage_mb']); ?> MB Storage
                                            </li>
                                        </ul>

                                        <?php if ($plan['plan_code'] !== $company_data['subscription_plan']): ?>
                                            <button class="btn btn-outline-primary w-100" onclick="upgradePlan('<?php echo $plan['plan_code']; ?>')">
                                                <?php echo $plan['monthly_price'] > $company_data['monthly_price'] ? 'Upgrade' : 'Downgrade'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Company Info Tab -->
            <div class="tab-pane fade" id="company" role="tabpanel">
                <div class="mt-4">
                    <h4>Company Information</h4>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <form id="companyForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Company Name</label>
                                            <input type="text" class="form-control" name="company_name" value="<?php echo htmlspecialchars($company_data['company_name']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Contact Email</label>
                                            <input type="email" class="form-control" name="contact_email" value="<?php echo htmlspecialchars($company_data['contact_email']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" name="contact_phone" value="<?php echo htmlspecialchars($company_data['contact_phone']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Company Type</label>
                                            <select class="form-control" name="company_type">
                                                <option value="towboat" <?php echo $company_data['company_type'] === 'towboat' ? 'selected' : ''; ?>>Towboat/Barge</option>
                                                <option value="fishing" <?php echo $company_data['company_type'] === 'fishing' ? 'selected' : ''; ?>>Commercial Fishing</option>
                                                <option value="workboat" <?php echo $company_data['company_type'] === 'workboat' ? 'selected' : ''; ?>>Workboat Services</option>
                                                <option value="tug" <?php echo $company_data['company_type'] === 'tug' ? 'selected' : ''; ?>>Tugboat Services</option>
                                                <option value="supply" <?php echo $company_data['company_type'] === 'supply' ? 'selected' : ''; ?>>Supply Vessel</option>
                                                <option value="passenger" <?php echo $company_data['company_type'] === 'passenger' ? 'selected' : ''; ?>>Passenger Vessel</option>
                                                <option value="other" <?php echo $company_data['company_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Billing Address</label>
                                    <textarea class="form-control" name="billing_address" rows="3"><?php echo htmlspecialchars($company_data['billing_address']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" name="billing_city" value="<?php echo htmlspecialchars($company_data['billing_city']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">State</label>
                                            <input type="text" class="form-control" name="billing_state" value="<?php echo htmlspecialchars($company_data['billing_state']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Postal Code</label>
                                            <input type="text" class="form-control" name="billing_postal" value="<?php echo htmlspecialchars($company_data['billing_postal']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Tab -->
            <div class="tab-pane fade" id="billing" role="tabpanel">
                <div class="mt-4">
                    <h4>Billing History</h4>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Period</th>
                                            <th>Invoice</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($payment_history)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No payment history yet</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($payment_history as $payment): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : ($payment['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                            <?php echo ucfirst($payment['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j', strtotime($payment['billing_period_start'])); ?> - 
                                                        <?php echo date('M j, Y', strtotime($payment['billing_period_end'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($payment['invoice_url']): ?>
                                                            <a href="<?php echo $payment['invoice_url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addUserForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone (Optional)</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form handlers
        document.getElementById('companyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'update_company');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Company information updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_user');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        function showAddUserModal() {
            new bootstrap.Modal(document.getElementById('addUserModal')).show();
        }

        function showUpgradeModal() {
            // Switch to subscription tab
            bootstrap.Tab.getInstance(document.getElementById('subscription-tab')).show();
        }

        function upgradePlan(planCode) {
            if (confirm('Are you sure you want to change your subscription plan?')) {
                const formData = new FormData();
                formData.append('action', 'upgrade_plan');
                formData.append('new_plan', planCode);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Plan updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        function deactivateUser(userId) {
            if (confirm('Are you sure you want to deactivate this user?')) {
                const formData = new FormData();
                formData.append('action', 'deactivate_user');
                formData.append('user_id', userId);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User deactivated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }

        // Auto-refresh usage stats every 30 seconds
        setInterval(function() {
            const formData = new FormData();
            formData.append('action', 'get_usage_stats');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Update usage statistics in the UI
                // This would update the stat cards and usage bars
            });
        }, 30000);
    </script>
</body>
</html>

<?php
// Helper functions for the admin dashboard

function getCompanyData($company_id) {
    $saas_config = getSaaSConfig();
    $license_pdo = new PDO(
        "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
        $saas_config['license_username'], 
        $saas_config['license_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $license_pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCompanyUsers($company_id) {
    $company_data = getCompanyData($company_id);
    if (!$company_data) return [];
    
    // Connect to company database
    $company_pdo = new PDO(
        "mysql:host={$company_data['database_host']};dbname={$company_data['database_name']}", 
        $company_data['database_username'], 
        base64_decode($company_data['database_password']),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $company_pdo->prepare("SELECT * FROM users WHERE is_active = 1 ORDER BY created_at");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUsageStats($company_id) {
    $company_data = getCompanyData($company_id);
    if (!$company_data) return [];
    
    // Connect to company database
    $company_pdo = new PDO(
        "mysql:host={$company_data['database_host']};dbname={$company_data['database_name']}", 
        $company_data['database_username'], 
        base64_decode($company_data['database_password']),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Get vessel count
    $stmt = $company_pdo->query("SELECT COUNT(*) as count FROM vessels WHERE active = 1");
    $vessel_count = $stmt->fetch()['count'];
    
    // Get user count
    $stmt = $company_pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $user_count = $stmt->fetch()['count'];
    
    // Get storage usage (approximate)
    $stmt = $company_pdo->query("SELECT COUNT(*) as count FROM engine_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $logs_this_month = $stmt->fetch()['count'];
    
    // Estimate storage usage (rough calculation)
    $storage_used_mb = ($logs_this_month * 0.5); // Rough estimate: 0.5KB per log entry
    
    return [
        'vessel_count' => $vessel_count,
        'user_count' => $user_count,
        'storage_used_mb' => round($storage_used_mb, 2),
        'logs_this_month' => $logs_this_month
    ];
}

function getAvailablePlans() {
    $saas_config = getSaaSConfig();
    $license_pdo = new PDO(
        "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
        $saas_config['license_username'], 
        $saas_config['license_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $license_pdo->prepare("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order, monthly_price");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPaymentHistory($company_id) {
    $saas_config = getSaaSConfig();
    $license_pdo = new PDO(
        "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
        $saas_config['license_username'], 
        $saas_config['license_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $license_pdo->prepare("SELECT * FROM payments WHERE company_id = ? ORDER BY payment_date DESC LIMIT 20");
    $stmt->execute([$company_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateCompanyInfo($company_id, $data) {
    try {
        $saas_config = getSaaSConfig();
        $license_pdo = new PDO(
            "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
            $saas_config['license_username'], 
            $saas_config['license_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $license_pdo->prepare("
            UPDATE companies SET 
                company_name = ?, contact_email = ?, contact_phone = ?, company_type = ?,
                billing_address = ?, billing_city = ?, billing_state = ?, billing_postal = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['company_name'],
            $data['contact_email'],
            $data['contact_phone'],
            $data['company_type'],
            $data['billing_address'],
            $data['billing_city'],
            $data['billing_state'],
            $data['billing_postal'],
            $company_id
        ]);
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function addCompanyUser($company_id, $data) {
    try {
        $company_data = getCompanyData($company_id);
        if (!$company_data) {
            return ['success' => false, 'message' => 'Company not found'];
        }
        
        // Connect to company database
        $company_pdo = new PDO(
            "mysql:host={$company_data['database_host']};dbname={$company_data['database_name']}", 
            $company_data['database_username'], 
            base64_decode($company_data['database_password']),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Check if email already exists
        $stmt = $company_pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Add user
        $stmt = $company_pdo->prepare("
            INSERT INTO users (name, email, role, phone, password, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        
        // Generate temporary password
        $temp_password = 'temp' . rand(1000, 9999);
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['role'],
            $data['phone'] ?? '',
            password_hash($temp_password, PASSWORD_DEFAULT)
        ]);
        
        // TODO: Send welcome email with temporary password
        
        return ['success' => true, 'temp_password' => $temp_password];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function deactivateCompanyUser($company_id, $user_id) {
    try {
        $company_data = getCompanyData($company_id);
        if (!$company_data) {
            return ['success' => false, 'message' => 'Company not found'];
        }
        
        // Connect to company database
        $company_pdo = new PDO(
            "mysql:host={$company_data['database_host']};dbname={$company_data['database_name']}", 
            $company_data['database_username'], 
            base64_decode($company_data['database_password']),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $company_pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function upgradePlan($company_id, $new_plan) {
    try {
        $saas_config = getSaaSConfig();
        $license_pdo = new PDO(
            "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
            $saas_config['license_username'], 
            $saas_config['license_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Get plan details
        $stmt = $license_pdo->prepare("SELECT * FROM subscription_plans WHERE plan_code = ?");
        $stmt->execute([$new_plan]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            return ['success' => false, 'message' => 'Plan not found'];
        }
        
        // Update company plan
        $stmt = $license_pdo->prepare("
            UPDATE companies SET 
                subscription_plan = ?, 
                monthly_price = ?, 
                annual_price = ?,
                max_vessels = ?,
                max_users = ?,
                max_storage_mb = ?,
                enabled_features = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $new_plan,
            $plan['monthly_price'],
            $plan['annual_price'],
            $plan['max_vessels'],
            $plan['max_users'],
            $plan['max_storage_mb'],
            $plan['included_features'],
            $company_id
        ]);
        
        // Log the event
        $stmt = $license_pdo->prepare("
            INSERT INTO license_events (company_id, event_type, new_value, triggered_by) 
            VALUES (?, 'plan_upgraded', ?, 'admin')
        ");
        $stmt->execute([$company_id, $new_plan]);
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>
