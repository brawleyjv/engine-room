<?php
/**
 * Company Search API
 * Provides autocomplete functionality for company domains during login
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config_saas.php';

if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 2) {
    echo json_encode(['companies' => []]);
    exit;
}

$query = trim($_GET['q']);

try {
    $saas_config = getSaaSConfig();
    
    // Connect to license database
    $license_pdo = new PDO(
        "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
        $saas_config['license_username'], 
        $saas_config['license_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Search for companies by domain or name
    // Only return active companies that users can log into
    $stmt = $license_pdo->prepare("
        SELECT company_name, company_domain 
        FROM companies 
        WHERE (company_domain LIKE ? OR company_name LIKE ?) 
        AND subscription_status IN ('active', 'suspended')
        ORDER BY 
            CASE WHEN company_domain LIKE ? THEN 1 ELSE 2 END,
            company_name
        LIMIT 10
    ");
    
    $search_term = '%' . $query . '%';
    $exact_domain = $query . '%';
    
    $stmt->execute([$search_term, $search_term, $exact_domain]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['companies' => $companies]);
    
} catch (Exception $e) {
    error_log("Company search error: " . $e->getMessage());
    echo json_encode(['companies' => [], 'error' => 'Search service unavailable']);
}
?>
