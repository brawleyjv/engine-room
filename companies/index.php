<?php
/**
 * Company-Specific Vessel API Router
 * Routes vessel sync requests to the correct company database
 */

// Get company domain from URL path
$path_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$companies_index = array_search('companies', $path_parts);

if ($companies_index === false || !isset($path_parts[$companies_index + 1])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Company domain not specified']);
    exit;
}

$company_domain = $path_parts[$companies_index + 1];
$api_endpoint = implode('/', array_slice($path_parts, $companies_index + 2));

// Validate company domain format
if (!preg_match('/^[a-z0-9-]+$/', $company_domain)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid company domain format']);
    exit;
}

// Set company-specific database configuration
$company_database = 'vessel_' . str_replace('-', '_', $company_domain);

// Override the default config with company-specific database
$GLOBALS['company_config'] = [
    'host' => 'localhost',
    'database' => $company_database,
    'username' => 'license_admin',
    'password' => 'rustyzeller',
    'company_domain' => $company_domain
];

// Route to appropriate endpoint
switch ($api_endpoint) {
    case 'api/vessel/logs.php':
        include __DIR__ . '/../../api/vessel/logs_company.php';
        break;
        
    case 'api/vessel/navigation.php':
        include __DIR__ . '/../../api/vessel/navigation_company.php';
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
        break;
}
?>
