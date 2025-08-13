<?php
/**
 * Payment Webhook Handler
 * Processes webhooks from Stripe and PayPal
 */

require_once __DIR__ . '/config_installed.php';
require_once __DIR__ . '/payment_processor.php';

// Determine which provider sent the webhook
$provider = $_GET['provider'] ?? 'stripe';

// Get the raw POST data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'] ?? '';

// Initialize payment processor
$payment_config = [
    'stripe_secret_key' => 'sk_test_your_key_here',
    'paypal_client_id' => 'your_paypal_client_id',
    'paypal_client_secret' => 'your_paypal_secret',
    'environment' => 'sandbox'
];

$payment_processor = new PaymentProcessor($payment_config);

// Process the webhook
$result = $payment_processor->handleWebhook($payload, $signature, $provider);

// Log webhook for debugging
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'provider' => $provider,
    'payload_size' => strlen($payload),
    'signature_present' => !empty($signature),
    'result' => $result
];

file_put_contents('webhook_log.txt', json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);

// Return appropriate response
http_response_code($result['success'] ? 200 : 400);
header('Content-Type: application/json');

echo json_encode([
    'success' => $result['success'],
    'message' => $result['message'] ?? $result['error'] ?? 'Webhook processed'
]);
?>
