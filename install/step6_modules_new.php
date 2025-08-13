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
?>

<h2>Select Your Modules</h2>
<p>Choose the features you need for your vessel operations. You can always add or remove modules later.</p>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">
    
    <!-- Basic Included Modules -->
    <div style="background: #e8f5e8; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
        <h3 style="color: #2e7d32; margin-top: 0;">
            <?php echo $module_categories['basic_included']['title']; ?>
        </h3>
        <p style="color: #555; margin-bottom: 20px;">
            <?php echo $module_categories['basic_included']['description']; ?>
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
            <?php foreach ($module_categories['basic_included']['modules'] as $module_id => $module): ?>
                <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 24px; margin-right: 10px;"><?php echo $module['icon']; ?></span>
                        <h4 style="margin: 0; color: #2e7d32;"><?php echo $module['name']; ?> ✓</h4>
                    </div>
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        <?php echo $module['description']; ?>
                    </p>
                    <div style="margin-top: 8px;">
                        <span style="background: #4caf50; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                            INCLUDED
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Premium Add-On Modules -->
    <div style="background: #fff3e0; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
        <h3 style="color: #f57c00; margin-top: 0;">
            <?php echo $module_categories['premium_modules']['title']; ?>
        </h3>
        <p style="color: #555; margin-bottom: 20px;">
            <?php echo $module_categories['premium_modules']['description']; ?>
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
            <?php foreach ($module_categories['premium_modules']['modules'] as $module_id => $module): ?>
                <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #e0e0e0; transition: border-color 0.2s;">
                    <label style="cursor: pointer; display: block;">
                        <div style="display: flex; align-items: flex-start; margin-bottom: 12px;">
                            <input type="checkbox" name="modules[]" value="<?php echo $module_id; ?>" 
                                   style="margin-right: 12px; margin-top: 4px; transform: scale(1.2);">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                    <span style="font-size: 24px; margin-right: 10px;"><?php echo $module['icon']; ?></span>
                                    <h4 style="margin: 0; color: #333;"><?php echo $module['name']; ?></h4>
                                </div>
                                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                                    <?php echo $module['description']; ?>
                                </p>
                                <?php if (isset($module['features'])): ?>
                                    <ul style="margin: 10px 0; padding-left: 20px; color: #555; font-size: 13px;">
                                        <?php foreach ($module['features'] as $feature): ?>
                                            <li><?php echo $feature; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <div style="margin-top: 10px;">
                                    <span style="background: #ff9800; color: white; padding: 4px 10px; border-radius: 15px; font-size: 14px; font-weight: bold;">
                                        $<?php echo $module['price']; ?>/month
                                    </span>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cost Summary -->
    <div style="background: #f5f5f5; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
        <h3>Monthly Cost Summary</h3>
        <div id="cost-summary">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Base Platform (Trial - 30 days)</span>
                <span style="font-weight: bold;">$0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>Selected Add-on Modules</span>
                <span id="modules-cost" style="font-weight: bold;">$0.00</span>
            </div>
            <hr>
            <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: bold;">
                <span>Total Monthly Cost (after trial)</span>
                <span id="total-cost">$49.00</span>
            </div>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                * After your 30-day free trial, you'll be charged the base platform fee of $49/month plus any selected modules.
                You can modify your modules at any time.
            </p>
        </div>
    </div>

    <div style="margin-top: 20px;">
        <a href="?step=5" style="margin-right: 10px; text-decoration: none; color: #666;">← Back</a>
        <button type="submit" class="btn">Continue to Summary →</button>
    </div>
</form>

<script>
function updateCostSummary() {
    const checkboxes = document.querySelectorAll('input[name="modules[]"]:checked');
    let modulesCost = 0;
    
    checkboxes.forEach(function(checkbox) {
        const moduleCard = checkbox.closest('div[style*="border: 2px solid"]');
        const priceText = moduleCard.querySelector('span[style*="background: #ff9800"]').textContent;
        const price = parseInt(priceText.match(/\$(\d+)/)[1]);
        modulesCost += price;
    });
    
    const basePlatformCost = 49; // Base platform cost after trial
    const totalCost = basePlatformCost + modulesCost;
    
    document.getElementById('modules-cost').textContent = '$' + modulesCost.toFixed(2);
    document.getElementById('total-cost').textContent = '$' + totalCost.toFixed(2);
}

// Add event listeners to all checkboxes
document.querySelectorAll('input[name="modules[]"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', updateCostSummary);
    
    // Add visual feedback for selected modules
    checkbox.addEventListener('change', function() {
        const moduleCard = this.closest('div[style*="border: 2px solid"]');
        if (this.checked) {
            moduleCard.style.borderColor = '#4caf50';
            moduleCard.style.backgroundColor = '#f8fff8';
        } else {
            moduleCard.style.borderColor = '#e0e0e0';
            moduleCard.style.backgroundColor = 'white';
        }
    });
});

// Initialize cost summary
updateCostSummary();
</script>
