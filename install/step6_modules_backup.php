<?php
// Step 6: Module Selection
$error = '';
$success = '';

// Define available modules with trial/basic access
$module_categories = [
    'basic_included' => [
        'title' => 'Included in Trial & All Plans',
        'description' => 'Core functionality available to all customers',
        'modules' => [
            'engine_basic' => [
                'name' => 'Engine Room Basic',
                'description' => 'Basic engine logging, RPM, temperatures, pressures',
                'included' => true,
                'icon' => '⚙️'
            ],
            'wheelhouse_basic' => [
                'name' => 'Wheelhouse Basic', 
                'description' => 'Navigation logs, position tracking, basic watch logs',
                'included' => true,
                'icon' => '🧭'
            ],
            'crew_basic' => [
                'name' => 'Crew Management Basic',
                'description' => 'Crew roster, basic scheduling, contact info',
                'included' => true,
                'icon' => '👥'
            ]
        ]
    ],
    'premium_modules' => [
        'title' => 'Premium Add-On Modules',
        'description' => 'Advanced features available for additional monthly fees',
        'modules' => [
            'engine_advanced' => [
                'name' => 'Engine Room Advanced',
                'description' => 'Advanced diagnostics, fuel optimization, predictive maintenance alerts',
                'price' => 39,
                'icon' => '🔧',
                'features' => ['Fuel efficiency tracking', 'Maintenance predictions', 'Performance analytics', 'Custom alerts']
            ],
            'wheelhouse_advanced' => [
                'name' => 'Wheelhouse Advanced',
                'description' => 'Weather routing, advanced navigation, AIS integration',
                'price' => 49,
                'icon' => '🗺️',
                'features' => ['Weather integration', 'Route optimization', 'AIS data', 'Electronic charts']
            ],
            'crew_advanced' => [
                'name' => 'Crew Management Advanced',
                'description' => 'Advanced scheduling, certifications, payroll integration',
                'price' => 29,
                'icon' => '📋',
                'features' => ['Advanced scheduling', 'Certification tracking', 'Payroll export', 'Performance reviews']
            ],
            'maintenance' => [
                'name' => 'Maintenance Management',
                'description' => 'Comprehensive maintenance tracking and scheduling',
                'price' => 59,
                'icon' => '🔨',
                'features' => ['Preventive maintenance', 'Work order management', 'Parts inventory', 'Vendor management']
            ],
            'compliance' => [
                'name' => 'Regulatory Compliance',
                'description' => 'Coast Guard reporting, inspection tracking, documentation',
                'price' => 79,
                'icon' => '📜',
                'features' => ['USCG reporting', 'Inspection schedules', 'Document management', 'Audit trails']
            ],
            'analytics' => [
                'name' => 'Business Analytics',
                'description' => 'Fleet performance analytics and business intelligence',
                'price' => 69,
                'icon' => '📊',
                'features' => ['Performance dashboards', 'Cost analysis', 'Efficiency reports', 'Custom KPIs']
            ]
        ]
    ]
];

if ($_POST) {
    $selected_modules = $_POST['modules'] ?? [];
    
    // Always include basic modules
    $final_modules = ['engine_basic', 'wheelhouse_basic', 'crew_basic'];
    
    // Add selected premium modules
    foreach ($selected_modules as $module) {
        if (isset($module_categories['premium_modules']['modules'][$module])) {
            $final_modules[] = $module;
        }
    }
    
    // Calculate monthly cost
    $monthly_cost = 0;
    foreach ($selected_modules as $module) {
        if (isset($module_categories['premium_modules']['modules'][$module]['price'])) {
            $monthly_cost += $module_categories['premium_modules']['modules'][$module]['price'];
        }
    }
    
    $_SESSION['install_data']['selected_modules'] = $final_modules;
    $_SESSION['install_data']['monthly_module_cost'] = $monthly_cost;
    
    header('Location: ?step=7');
    exit;
}

$data = $_SESSION['install_data'] ?? [];
            'price' => 45,
            'included' => false,
            'vessel_types' => ['towboat', 'workboat', 'tug', 'supply']
        ],
        'fuel_management' => [
            'name' => 'Fuel Management',
            'description' => 'Fuel purchasing, inventory, efficiency analysis, cost tracking',
            'price' => 30,
            'included' => false,
            'vessel_types' => ['all']
        ],
        'maintenance_pro' => [
            'name' => 'Maintenance Pro',
            'description' => 'Preventive maintenance, parts inventory, work orders',
            'price' => 40,
            'included' => false,
            'vessel_types' => ['all']
        ],
        'fishing_ops' => [
            'name' => 'Fishing Operations',
            'description' => 'Catch logs, fish holds, gear tracking, quota management',
            'price' => 50,
            'included' => false,
            'vessel_types' => ['fishing']
        ]
    ]
];

if ($_POST) {
    $selected_modules = $_POST['modules'] ?? [];
    
    // Always include basic modules
    $selected_modules[] = 'engine_basic';
    $selected_modules[] = 'wheelhouse_basic';
    $selected_modules = array_unique($selected_modules);
    
    // Calculate pricing
    $monthly_cost = 99; // Base price
    $addon_cost = 0;
    
    foreach ($selected_modules as $module_code) {
        foreach ($modules as $category => $category_modules) {
            if (isset($category_modules[$module_code]) && !$category_modules[$module_code]['included']) {
                $addon_cost += $category_modules[$module_code]['price'];
            }
        }
    }
    
    $_SESSION['install_data']['selected_modules'] = $selected_modules;
    $_SESSION['install_data']['monthly_cost'] = $monthly_cost;
    $_SESSION['install_data']['addon_cost'] = $addon_cost;
    $_SESSION['install_data']['total_cost'] = $monthly_cost + $addon_cost;
    
    header('Location: ?step=7');
    exit;
}

$data = $_SESSION['install_data'] ?? [];

// Function to check if module is available for vessel type
function isModuleAvailable($module, $vessel_type) {
    return in_array('all', $module['vessel_types']) || in_array($vessel_type, $module['vessel_types']);
}
?>

<h2>Select Your Modules</h2>
<p>Choose the modules you need for <strong><?php echo htmlspecialchars($data['vessel_name']); ?></strong> 
   (<?php echo htmlspecialchars(ucfirst($vessel_type)); ?> vessel)</p>

<form method="POST">
    
    <!-- Basic Modules (Included) -->
    <div style="background: #e8f5e8; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h3>✅ Included in Base Plan ($99/month)</h3>
        <?php foreach ($modules['basic'] as $code => $module): ?>
            <div style="padding: 10px 0; border-bottom: 1px solid #ddd;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong><?php echo $module['name']; ?></strong> - <span style="color: #2e7d32;">Included</span>
                        <br><small style="color: #666;"><?php echo $module['description']; ?></small>
                    </div>
                    <div style="color: #2e7d32; font-weight: bold;">FREE</div>
                </div>
                <input type="hidden" name="modules[]" value="<?php echo $code; ?>">
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Extended Modules -->
    <div style="background: #fff3e0; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h3>🔧 Extended Features</h3>
        <p style="margin-bottom: 15px; color: #666;">Enhance your basic modules with advanced capabilities:</p>
        
        <?php foreach ($modules['extended'] as $code => $module): ?>
            <?php if (isModuleAvailable($module, $vessel_type)): ?>
                <div style="padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;">
                    <label style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                        <div style="flex: 1;">
                            <input type="checkbox" name="modules[]" value="<?php echo $code; ?>" style="margin-right: 10px;">
                            <strong><?php echo $module['name']; ?></strong>
                            <br><small style="color: #666;"><?php echo $module['description']; ?></small>
                        </div>
                        <div style="color: #ff9800; font-weight: bold;">+$<?php echo $module['price']; ?>/month</div>
                    </label>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Add-on Modules -->
    <div style="background: #f3e5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h3>📦 Add-on Modules</h3>
        <p style="margin-bottom: 15px; color: #666;">Specialized modules for specific operational needs:</p>
        
        <?php foreach ($modules['addons'] as $code => $module): ?>
            <?php if (isModuleAvailable($module, $vessel_type)): ?>
                <div style="padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;">
                    <label style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                        <div style="flex: 1;">
                            <input type="checkbox" name="modules[]" value="<?php echo $code; ?>" style="margin-right: 10px;">
                            <strong><?php echo $module['name']; ?></strong>
                            <br><small style="color: #666;"><?php echo $module['description']; ?></small>
                        </div>
                        <div style="color: #7026b9; font-weight: bold;">+$<?php echo $module['price']; ?>/month</div>
                    </label>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Pricing Summary -->
    <div style="background: #e3f2fd; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h3>💰 Pricing Summary</h3>
        <div style="font-size: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Base Plan (1 vessel):</span>
                <span><strong>$99.00/month</strong></span>
            </div>
            <div id="addon-cost" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Add-on Modules:</span>
                <span><strong>$<span id="addon-amount">0.00</span>/month</strong></span>
            </div>
            <hr style="margin: 10px 0;">
            <div style="display: flex; justify-content: space-between; font-size: 18px;">
                <span><strong>Total Monthly Cost:</strong></span>
                <span><strong>$<span id="total-amount">99.00</span>/month</strong></span>
            </div>
        </div>
        
        <div style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 3px;">
            <strong>🎁 30-Day Free Trial</strong> - Try all features risk-free!
            <br><small>No credit card required. Cancel anytime during trial.</small>
        </div>
    </div>

    <!-- Trial Information -->
    <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h4>✨ What happens during your trial:</h4>
        <ul style="margin: 5px 0 0 20px;">
            <li>Full access to all selected modules for 30 days</li>
            <li>Complete vessel management functionality</li>
            <li>Data sync and backup capabilities</li>
            <li>Email support and documentation</li>
            <li>No automatic billing - you choose to continue</li>
        </ul>
    </div>

    <div style="margin-top: 20px;">
        <a href="?step=5" style="margin-right: 10px; text-decoration: none; color: #666;">← Back</a>
        <button type="submit" class="btn" style="font-size: 16px; padding: 12px 24px;">
            Start 30-Day Free Trial →
        </button>
    </div>
</form>

<script>
// Calculate pricing dynamically
function updatePricing() {
    const checkboxes = document.querySelectorAll('input[name="modules[]"]:checked');
    let addonCost = 0;
    
    checkboxes.forEach(function(checkbox) {
        const price = checkbox.parentElement.parentElement.querySelector('[style*="color: #ff9800"], [style*="color: #7026b9"]');
        if (price && price.textContent.includes('+$')) {
            const amount = parseFloat(price.textContent.replace(/[^0-9.]/g, ''));
            if (!isNaN(amount)) {
                addonCost += amount;
            }
        }
    });
    
    document.getElementById('addon-amount').textContent = addonCost.toFixed(2);
    document.getElementById('total-amount').textContent = (99 + addonCost).toFixed(2);
}

// Add event listeners to all checkboxes
document.querySelectorAll('input[name="modules[]"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', updatePricing);
});

// Initial calculation
updatePricing();
</script>
