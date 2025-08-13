<?php
/**
 * Payment Processing Integration
 * Handles Stripe and PayPal integration for subscription management
 */

class PaymentProcessor {
    private $stripe_secret_key;
    private $paypal_client_id;
    private $paypal_client_secret;
    private $environment; // 'sandbox' or 'production'
    
    public function __construct($config) {
        $this->stripe_secret_key = $config['stripe_secret_key'];
        $this->paypal_client_id = $config['paypal_client_id'];
        $this->paypal_client_secret = $config['paypal_client_secret'];
        $this->environment = $config['environment'] ?? 'sandbox';
    }
    
    /**
     * Create Stripe subscription
     */
    public function createStripeSubscription($customer_id, $plan_id, $customer_email) {
        // In production, you would use the Stripe PHP SDK
        // composer require stripe/stripe-php
        
        $stripe_data = [
            'customer_email' => $customer_email,
            'plan_id' => $plan_id,
            'customer_id' => $customer_id,
            'success_url' => $this->getSuccessUrl(),
            'cancel_url' => $this->getCancelUrl()
        ];
        
        // For demo purposes, return a mock checkout session
        return [
            'success' => true,
            'checkout_url' => 'https://checkout.stripe.com/demo-session-' . uniqid(),
            'session_id' => 'cs_demo_' . uniqid()
        ];
        
        /*
        // Production Stripe integration would look like this:
        \Stripe\Stripe::setApiKey($this->stripe_secret_key);
        
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $plan_id,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'customer_email' => $customer_email,
            'metadata' => [
                'customer_id' => $customer_id
            ],
            'success_url' => $this->getSuccessUrl() . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->getCancelUrl(),
        ]);
        
        return [
            'success' => true,
            'checkout_url' => $session->url,
            'session_id' => $session->id
        ];
        */
    }
    
    /**
     * Create PayPal subscription
     */
    public function createPayPalSubscription($customer_id, $plan_id, $customer_email) {
        // PayPal subscription creation
        $paypal_data = [
            'plan_id' => $plan_id,
            'customer_id' => $customer_id,
            'return_url' => $this->getSuccessUrl(),
            'cancel_url' => $this->getCancelUrl()
        ];
        
        // Mock PayPal response
        return [
            'success' => true,
            'approval_url' => 'https://www.paypal.com/checkoutnow?token=demo_' . uniqid(),
            'subscription_id' => 'I-DEMO' . strtoupper(uniqid())
        ];
        
        /*
        // Production PayPal integration using PayPal SDK
        $request = new PayPalCheckoutSdk\Orders\OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => $this->getPlanPrice($plan_id)
                ]
            ]],
            'application_context' => [
                'return_url' => $this->getSuccessUrl(),
                'cancel_url' => $this->getCancelUrl()
            ]
        ];
        
        $client = PayPalCheckoutSdk\Core\PayPalHttpClient($this->getPayPalEnvironment());
        $response = $client->execute($request);
        
        return [
            'success' => true,
            'approval_url' => $response->result->links[1]->href,
            'order_id' => $response->result->id
        ];
        */
    }
    
    /**
     * Handle webhook notifications
     */
    public function handleWebhook($payload, $signature, $provider) {
        if ($provider === 'stripe') {
            return $this->handleStripeWebhook($payload, $signature);
        } elseif ($provider === 'paypal') {
            return $this->handlePayPalWebhook($payload, $signature);
        }
        
        return ['success' => false, 'error' => 'Unknown provider'];
    }
    
    private function handleStripeWebhook($payload, $signature) {
        // Verify webhook signature
        $webhook_secret = 'whsec_your_webhook_secret';
        
        try {
            // In production: $event = \Stripe\Webhook::constructEvent($payload, $signature, $webhook_secret);
            $event = json_decode($payload, true); // Demo only
            
            switch ($event['type']) {
                case 'checkout.session.completed':
                    return $this->handleSubscriptionActivated($event['data']['object']);
                    
                case 'invoice.payment_succeeded':
                    return $this->handlePaymentSucceeded($event['data']['object']);
                    
                case 'invoice.payment_failed':
                    return $this->handlePaymentFailed($event['data']['object']);
                    
                case 'customer.subscription.deleted':
                    return $this->handleSubscriptionCanceled($event['data']['object']);
                    
                default:
                    return ['success' => true, 'message' => 'Unhandled event type'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function handlePayPalWebhook($payload, $signature) {
        // Verify PayPal webhook signature
        $event = json_decode($payload, true);
        
        switch ($event['event_type']) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                return $this->handleSubscriptionActivated($event['resource']);
                
            case 'BILLING.SUBSCRIPTION.CANCELLED':
                return $this->handleSubscriptionCanceled($event['resource']);
                
            case 'PAYMENT.SALE.COMPLETED':
                return $this->handlePaymentSucceeded($event['resource']);
                
            default:
                return ['success' => true, 'message' => 'Unhandled event type'];
        }
    }
    
    private function handleSubscriptionActivated($subscription_data) {
        // Update customer license status
        global $conn;
        
        $customer_id = $subscription_data['metadata']['customer_id'] ?? null;
        if (!$customer_id) {
            return ['success' => false, 'error' => 'No customer ID in subscription data'];
        }
        
        // Update license
        $update_query = "UPDATE customer_licenses SET 
                        status = 'active',
                        subscription_id = ?,
                        updated_at = NOW()
                        WHERE customer_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ss', $subscription_data['id'], $customer_id);
        $stmt->execute();
        
        // Log the activation
        $this->logPaymentEvent($customer_id, 'subscription_activated', $subscription_data);
        
        return ['success' => true, 'message' => 'Subscription activated'];
    }
    
    private function handlePaymentSucceeded($payment_data) {
        // Log successful payment and extend subscription
        $customer_id = $payment_data['metadata']['customer_id'] ?? null;
        
        if ($customer_id) {
            // Extend subscription period
            $extend_query = "UPDATE customer_licenses SET 
                            expires_at = DATE_ADD(expires_at, INTERVAL 1 MONTH),
                            updated_at = NOW()
                            WHERE customer_id = ?";
            $stmt = $conn->prepare($extend_query);
            $stmt->bind_param('s', $customer_id);
            $stmt->execute();
            
            $this->logPaymentEvent($customer_id, 'payment_succeeded', $payment_data);
        }
        
        return ['success' => true, 'message' => 'Payment processed'];
    }
    
    private function handlePaymentFailed($payment_data) {
        // Handle failed payment
        $customer_id = $payment_data['metadata']['customer_id'] ?? null;
        
        if ($customer_id) {
            // Don't immediately suspend, but log the failure
            $this->logPaymentEvent($customer_id, 'payment_failed', $payment_data);
            
            // Send notification email (implement separately)
            // $this->sendPaymentFailedNotification($customer_id);
        }
        
        return ['success' => true, 'message' => 'Payment failure recorded'];
    }
    
    private function handleSubscriptionCanceled($subscription_data) {
        // Handle subscription cancellation
        $customer_id = $subscription_data['metadata']['customer_id'] ?? null;
        
        if ($customer_id) {
            // Set status to canceled but don't immediately expire
            $cancel_query = "UPDATE customer_licenses SET 
                            status = 'canceled',
                            updated_at = NOW()
                            WHERE customer_id = ?";
            $stmt = $conn->prepare($cancel_query);
            $stmt->bind_param('s', $customer_id);
            $stmt->execute();
            
            $this->logPaymentEvent($customer_id, 'subscription_canceled', $subscription_data);
        }
        
        return ['success' => true, 'message' => 'Subscription canceled'];
    }
    
    private function logPaymentEvent($customer_id, $event_type, $data) {
        global $conn;
        
        $log_query = "INSERT INTO payment_logs (customer_id, event_type, event_data, created_at) 
                      VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $event_data = json_encode($data);
        $stmt->bind_param('sss', $customer_id, $event_type, $event_data);
        $stmt->execute();
    }
    
    private function getSuccessUrl() {
        $base_url = $_SERVER['HTTP_HOST'];
        return "https://{$base_url}/subscription_success.php";
    }
    
    private function getCancelUrl() {
        $base_url = $_SERVER['HTTP_HOST'];
        return "https://{$base_url}/subscription.php?canceled=1";
    }
    
    private function getPlanPrice($plan_id) {
        $prices = [
            'trial' => 0,
            'basic' => 49,
            'professional' => 149,
            'enterprise' => 499
        ];
        
        return $prices[$plan_id] ?? 0;
    }
}
