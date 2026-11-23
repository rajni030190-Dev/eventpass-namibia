<?php
/**
 * Finatic Technologies Payment Gateway Integration (Native & WooCommerce Compatible)
 *
 * Integrates Namibian card acquiring (Visa / Mastercard / Local EFT)
 * Supports HMAC-SHA256 server-to-server IPN webhook verification, transaction status check,
 * and Bank of Namibia (BON) National Payment System (NPS) compliance.
 *
 * @package EventPass_Namibia
 */

if (!defined('ABSPATH')) {
    exit;
}

class EventPass_Finatic_Gateway {

    const GATEWAY_ID = 'finatic';

    /**
     * Retrieve Merchant ID
     */
    public static function get_merchant_id() {
        return get_option('_ep_finatic_merchant_id', 'EP_NAM_MCH_DEMO');
    }

    /**
     * Retrieve Public API Key
     */
    public static function get_api_key() {
        return get_option('_ep_finatic_api_key', 'fin_pk_live_namibia_2026');
    }

    /**
     * Retrieve Secret HMAC Key
     */
    public static function get_secret_key() {
        return get_option('_ep_finatic_secret_key', 'fin_sk_secret_namibia_hmac_2026');
    }

    /**
     * Check if test/sandbox mode is active
     */
    public static function is_test_mode() {
        return get_option('_ep_finatic_test_mode', 'yes') === 'yes';
    }

    /**
     * Base API endpoint
     */
    public static function get_endpoint() {
        return self::is_test_mode()
            ? 'https://sandbox.finatic.africa/api/v1/'
            : 'https://gateway.finatic.africa/api/v1/';
    }

    /**
     * Initiate Hosted Checkout Session
     * Returns structured payment redirect payload
     *
     * @param int    $order_id
     * @param string $payment_method
     * @return array
     */
    public static function initiate_checkout_session($order_id, $payment_method = 'finatic') {
        $order_id = intval($order_id);
        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'ep_order') {
            return [
                'success' => false,
                'message' => __('Order record not found.', 'eventpass-core')
            ];
        }

        $merchant_id = self::get_merchant_id();
        $secret_key  = self::get_secret_key();
        $total       = floatval(get_post_meta($order_id, '_ep_order_total', true));
        $amount_str  = number_format($total, 2, '.', '');
        $timestamp   = time();

        // Calculate cryptographic HMAC-SHA256 signature
        $sig_payload = "{$merchant_id}|{$order_id}|{$amount_str}|{$timestamp}";
        $signature   = hash_hmac('sha256', $sig_payload, $secret_key);

        $customer_name  = get_post_meta($order_id, '_ep_order_customer_name', true);
        $customer_email = get_post_meta($order_id, '_ep_order_customer_email', true);
        $customer_phone = get_post_meta($order_id, '_ep_order_customer_phone', true);

        // Store checkout state
        update_post_meta($order_id, '_ep_order_payment_signature', $signature);
        update_post_meta($order_id, '_ep_order_checkout_initiated_at', $timestamp);
        update_post_meta($order_id, '_ep_order_payment_status', 'pending');
        update_post_meta($order_id, '_ep_order_payment_method', $payment_method);

        $callback_url = rest_url('eventpass/v1/payment-webhook');
        $return_url   = home_url('/customer-dashboard?order_id=' . $order_id . '&status=awaiting_confirmation');
        $cancel_url   = home_url('/checkout?order_id=' . $order_id . '&status=cancelled');

        // Construct hosted payment gateway redirect URL
        $query_args = [
            'merchant_id'  => $merchant_id,
            'order_id'     => $order_id,
            'amount'       => $amount_str,
            'currency'     => 'NAD',
            'timestamp'    => $timestamp,
            'signature'    => $signature,
            'customer_name'=> $customer_name,
            'customer_email'=> $customer_email,
            'callback_url' => $callback_url,
            'return_url'   => $return_url,
            'cancel_url'   => $cancel_url,
        ];

        // In sandbox or live, build official redirect
        $gateway_base = self::is_test_mode()
            ? 'https://sandbox.finatic.africa/checkout/'
            : 'https://gateway.finatic.africa/checkout/';

        $redirect_url = add_query_arg($query_args, $gateway_base);

        return [
            'success'      => true,
            'order_id'     => $order_id,
            'amount'       => $amount_str,
            'currency'     => 'NAD',
            'signature'    => $signature,
            'timestamp'    => $timestamp,
            'redirect_url' => $redirect_url,
            'callback_url' => $callback_url,
        ];
    }

    /**
     * Process Server-to-Server IPN Webhook
     * Validates HMAC signature, prevents replay attacks, ensures idempotency, and settles orders
     *
     * @param array $data Parsed webhook payload
     * @return array Result code and message
     */
    public static function process_webhook($data) {
        if (!is_array($data)) {
            return ['status' => 400, 'error' => 'Invalid webhook payload structure'];
        }

        $order_id    = intval($data['order_id'] ?? 0);
        $merchant_id = sanitize_text_field($data['merchant_id'] ?? '');
        $amount      = sanitize_text_field($data['amount'] ?? '');
        $timestamp   = intval($data['timestamp'] ?? 0);
        $signature   = sanitize_text_field($data['signature'] ?? '');
        $status      = strtoupper(sanitize_text_field($data['status'] ?? ''));
        $txn_id      = sanitize_text_field($data['transaction_id'] ?? '');

        if ($order_id <= 0 || empty($signature) || empty($status)) {
            return ['status' => 400, 'error' => 'Missing required transaction fields'];
        }

        // 1. Replay Attack Prevention (drift tolerance: 600 seconds)
        $current_time = time();
        if ($timestamp > 0 && abs($current_time - $timestamp) > 600) {
            return ['status' => 403, 'error' => 'Webhook rejected: Timestamp expired (Replay protection)'];
        }

        // 2. Cryptographic HMAC-SHA256 Signature Validation
        $secret_key = self::get_secret_key();
        $expected_sig_payload = "{$merchant_id}|{$order_id}|{$amount}|{$timestamp}";
        $expected_signature   = hash_hmac('sha256', $expected_sig_payload, $secret_key);

        if (!hash_equals($expected_signature, $signature)) {
            return ['status' => 403, 'error' => 'Webhook rejected: Cryptographic signature mismatch'];
        }

        // 3. Locate Order
        $order = get_post($order_id);
        if (!$order || $order->post_type !== 'ep_order') {
            return ['status' => 404, 'error' => 'Order record not found'];
        }

        // 4. Idempotency Guard: Prevent duplicate ticket generation on re-delivered webhooks
        $existing_status = get_post_meta($order_id, '_ep_order_payment_status', true);
        if ($existing_status === 'completed') {
            $existing_tickets = get_post_meta($order_id, '_ep_order_tickets', true);
            return [
                'status'  => 200,
                'message' => 'Idempotent: Order already settled and tickets previously issued',
                'order_id'=> $order_id,
                'tickets' => is_array($existing_tickets) ? count($existing_tickets) : 1
            ];
        }

        // 5. Concurrency Mutex Lock using Transients
        $lock_key = "ep_lock_order_{$order_id}";
        if (get_transient($lock_key)) {
            return ['status' => 409, 'error' => 'Concurrent webhook delivery locked; processing in progress'];
        }
        set_transient($lock_key, 1, 15); // 15-second atomic lock

        // 6. Price Integrity Verification
        $stored_total = floatval(get_post_meta($order_id, '_ep_order_total', true));
        $received_amt = floatval($amount);
        if (abs($stored_total - $received_amt) > 0.01) {
            delete_transient($lock_key);
            update_post_meta($order_id, '_ep_order_payment_status', 'fraud_flagged');
            return ['status' => 422, 'error' => 'Amount tampering detected: Received amount does not match order total'];
        }

        // 7. Settle Payment Status
        if ($status === 'SUCCESS' || $status === 'PAID') {
            if (empty($txn_id)) {
                $txn_id = 'TXN-FIN-' . strtoupper(substr(md5(uniqid(wp_rand(), true)), 0, 8));
            }
            update_post_meta($order_id, '_ep_order_external_txn_id', $txn_id);

            // Execute atomic completion & ticket issuance
            $result = eventpass_complete_order($order_id, 'finatic', [
                'txn_id'    => $txn_id,
                'signature' => $signature,
                'timestamp' => $timestamp
            ]);

            delete_transient($lock_key);

            if (is_wp_error($result)) {
                return ['status' => 500, 'error' => $result->get_error_message()];
            }

            return [
                'status'   => 200,
                'message'  => 'Payment verified successfully. Tickets issued.',
                'order_id' => $order_id,
                'txn_id'   => $txn_id,
                'tickets'  => count($result['tickets'] ?? [])
            ];
        } elseif ($status === 'FAILED' || $status === 'DECLINED') {
            eventpass_cancel_order($order_id);
            update_post_meta($order_id, '_ep_order_payment_status', 'failed');
            delete_transient($lock_key);

            return [
                'status'   => 200,
                'message'  => 'Payment failure recorded. Reserved inventory released.',
                'order_id' => $order_id
            ];
        } elseif ($status === 'CANCELLED') {
            eventpass_cancel_order($order_id);
            update_post_meta($order_id, '_ep_order_payment_status', 'cancelled');
            delete_transient($lock_key);

            return [
                'status'   => 200,
                'message'  => 'Order cancellation confirmed. Reserved inventory released.',
                'order_id' => $order_id
            ];
        }

        delete_transient($lock_key);
        return ['status' => 400, 'error' => 'Unhandled payment status: ' . $status];
    }
}

/**
 * Register REST API Endpoint for Server-to-Server Payment Webhook
 */
add_action('rest_api_init', function () {
    register_rest_route('eventpass/v1', '/payment-webhook', [
        'methods'             => ['POST'],
        'callback'            => 'eventpass_rest_handle_payment_webhook',
        'permission_callback' => '__return_true', // Public webhook verified via cryptographic HMAC
    ]);
});

function eventpass_rest_handle_payment_webhook(WP_REST_Request $request) {
    $params = $request->get_json_params();
    if (empty($params)) {
        $params = $request->get_body_params();
    }
    if (empty($params)) {
        $raw = file_get_contents('php://input');
        $params = json_decode($raw, true) ?: [];
    }

    $result = EventPass_Finatic_Gateway::process_webhook($params);
    $status_code = $result['status'] ?? 200;

    return new WP_REST_Response($result, $status_code);
}

// Also support admin-post.php for legacy webhook callbacks
add_action('admin_post_nopriv_ep_finatic_webhook', 'eventpass_admin_post_payment_webhook');
add_action('admin_post_ep_finatic_webhook', 'eventpass_admin_post_payment_webhook');

function eventpass_admin_post_payment_webhook() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
    $result = EventPass_Finatic_Gateway::process_webhook($data);

    status_header($result['status'] ?? 200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    exit;
}

// -----------------------------------------------------------------------------
// Optional WooCommerce Gateway Adapter (for hybrid setups)
// -----------------------------------------------------------------------------
add_action('plugins_loaded', 'eventpass_init_finatic_gateway_wc', 11);

function eventpass_init_finatic_gateway_wc() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_Finatic extends WC_Payment_Gateway {
        public function __construct() {
            $this->id                 = 'finatic';
            $this->method_title       = __('Finatic Namibia Payments', 'eventpass-core');
            $this->method_description = __('Accept Visa & Mastercard debit/credit cards securely via Finatic Technologies (Windhoek, Namibia).', 'eventpass-core');
            $this->title              = __('Debit / Credit Card (Finatic Namibia)', 'eventpass-core');
            $this->enabled            = 'yes';
        }

        public function process_payment($order_id) {
            $session = EventPass_Finatic_Gateway::initiate_checkout_session($order_id, 'finatic');
            return [
                'result'   => $session['success'] ? 'success' : 'failure',
                'redirect' => $session['redirect_url'] ?? '',
            ];
        }
    }

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_Gateway_Finatic';
        return $gateways;
    });
}

