<?php
/**
 * Vessel Logger SaaS Modules Configuration
 * Defines all available modules and their features
 */

// Module definitions with features and pricing
$VESSEL_MODULES = [
    // Basic package (included in $49/month)
    'basic_package' => [
        'name' => 'Basic Package',
        'price' => 49.00,
        'included_modules' => ['basic_wheelhouse', 'basic_crew', 'basic_engineroom'],
        'description' => 'Core maritime logging features for essential operations'
    ],
    
    // Individual modules ($24.95 each/month)
    'modules' => [
        // Wheelhouse modules
        'basic_wheelhouse' => [
            'name' => 'Basic Wheelhouse',
            'category' => 'wheelhouse',
            'price' => 0, // Included in basic
            'included_in_basic' => true,
            'features' => [
                'Position logging (pilot entered - marker, buoy, island name)',
                'Body of water tracking (Lower Mississippi River, Intercoastal Canal)',
                'Direction of travel (northbound, eastbound, downbound)',
                'Activities logging (departure, arrival, transit)',
                'General notes and observations'
            ]
        ],
        'enhanced_wheelhouse' => [
            'name' => 'Enhanced Wheelhouse',
            'category' => 'wheelhouse',
            'price' => 24.95,
            'included_in_basic' => false,
            'features' => [
                'Advanced navigation tracking',
                'Weather observations and conditions',
                'Tow/cargo integration',
                'Detailed waterway information',
                'Enhanced activity logging',
                'Trip planning and scheduling'
            ]
        ],
        
        // Crew modules
        'basic_crew' => [
            'name' => 'Basic Crew Management',
            'category' => 'crew',
            'price' => 0, // Included in basic
            'included_in_basic' => true,
            'features' => [
                'Crew member names and positions',
                'TWIC card expiry tracking',
                'Date onboard and departure logging',
                'Basic crew roster management',
                'Contact information storage'
            ]
        ],
        'enhanced_crew' => [
            'name' => 'Enhanced Crew Management',
            'category' => 'crew',
            'price' => 24.95,
            'included_in_basic' => false,
            'features' => [
                'Advanced certification tracking (all maritime documents)',
                'Training records and compliance',
                'Work hour tracking and fatigue management',
                'Performance evaluations',
                'Medical certification tracking',
                'Drug testing records',
                'Payroll integration hooks'
            ]
        ],
        
        // Engine Room modules
        'basic_engineroom' => [
            'name' => 'Basic Engine Room',
            'category' => 'engineroom',
            'price' => 0, // Included in basic
            'included_in_basic' => true,
            'features' => [
                'Engine RPM logging',
                'Temperature readings (coolant, oil, exhaust)',
                'Pressure readings (oil, coolant, turbo)',
                'Engine hours for mains and generators',
                'Gearbox oil pressure and temperature',
                'Support for single, twin, or triple screw configurations',
                'Basic averages for temps and pressures'
            ]
        ],
        'enhanced_engineroom' => [
            'name' => 'Enhanced Engine Room',
            'category' => 'engineroom',
            'price' => 24.95,
            'included_in_basic' => false,
            'features' => [
                'Advanced engine diagnostics',
                'Predictive maintenance alerts',
                'Detailed equipment tracking',
                'Maintenance scheduling and reminders',
                'Parts inventory management',
                'Performance trend analysis'
            ]
        ],
        
        'fuel_management' => [
            'name' => 'Fuel Management',
            'category' => 'engineroom',
            'price' => 24.95,
            'included_in_basic' => false,
            'features' => [
                'Fuel consumption tracking',
                'Fuel efficiency analytics',
                'Tank level monitoring',
                'Fuel cost analysis',
                'Consumption by engine/generator',
                'Fuel purchasing and delivery tracking'
            ]
        ],
        
        // Analytics modules
        'analytics_graphs' => [
            'name' => 'Analytics & Graphs',
            'category' => 'analytics',
            'price' => 24.95,
            'included_in_basic' => false,
            'features' => [
                'Interactive performance graphs',
                'Trend analysis and forecasting',
                'Customizable dashboards',
                'Automated report generation',
                'Fuel efficiency analytics',
                'Performance benchmarking',
                'Export to Excel/PDF'
            ]
        ],
        
        // Compliance modules
        'compliance_reporting' => [
            'name' => 'Compliance & Reporting',
            'category' => 'compliance',
            'price' => 24.95,
            'included_in_basic' => false,
            'features' => [
                'Coast Guard compliance reports',
                'Environmental impact tracking',
                'Safety incident reporting',
                'Inspection management',
                'Regulatory deadline tracking',
                'Audit trail and documentation',
                'Industry standard report formats'
            ]
        ],
        
        // Mobile & Offline modules
        'mobile_offline' => [
            'name' => 'Mobile & Offline',
            'category' => 'mobile',
            'price' => 24.95,
            'included_in_basic' => false,
            'features' => [
                'Progressive Web App (PWA)',
                'Offline data collection',
                'Automatic sync when online',
                'Mobile-optimized interface',
                'Touch-friendly data entry',
                'QR code scanning',
                'Photo and document upload'
            ]
        ]
    ]
];

// Function to get module by ID
function getModule($module_id) {
    global $VESSEL_MODULES;
    return $VESSEL_MODULES['modules'][$module_id] ?? null;
}

// Function to get modules by category
function getModulesByCategory($category) {
    global $VESSEL_MODULES;
    $modules = [];
    foreach ($VESSEL_MODULES['modules'] as $id => $module) {
        if ($module['category'] === $category) {
            $modules[$id] = $module;
        }
    }
    return $modules;
}

// Function to calculate total monthly cost
function calculateMonthlyCost($selected_modules) {
    global $VESSEL_MODULES;
    $total = $VESSEL_MODULES['basic_package']['price']; // Base price
    
    foreach ($selected_modules as $module_id) {
        $module = getModule($module_id);
        if ($module && !$module['included_in_basic']) {
            $total += $module['price'];
        }
    }
    
    return $total;
}

// Function to get basic package modules
function getBasicPackageModules() {
    global $VESSEL_MODULES;
    $basic_modules = [];
    foreach ($VESSEL_MODULES['modules'] as $id => $module) {
        if ($module['included_in_basic']) {
            $basic_modules[$id] = $module;
        }
    }
    return $basic_modules;
}

// Function to get add-on modules only
function getAddOnModules() {
    global $VESSEL_MODULES;
    $addon_modules = [];
    foreach ($VESSEL_MODULES['modules'] as $id => $module) {
        if (!$module['included_in_basic']) {
            $addon_modules[$id] = $module;
        }
    }
    return $addon_modules;
}

// Trial limitations
$TRIAL_LIMITS = [
    'max_vessels' => 1,
    'max_users' => 5,
    'duration_days' => 30,
    'included_modules' => ['basic_wheelhouse', 'basic_crew', 'basic_engineroom']
];

?>
