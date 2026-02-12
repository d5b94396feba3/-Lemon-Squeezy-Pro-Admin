<?php
defined( 'ABSPATH' ) || exit;

class LSPA_Plugin_Paddle_Core {

    private $plugin_slug;

    public function __construct($plugin_slug) {
        $this->plugin_slug = $plugin_slug;
    }

    public function register_webhook_route() {
        register_rest_route('wp-paddle-pro/v1', '/webhook', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_paddle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_paddle_webhook(WP_REST_Request $request) {
        $body = $request->get_body();
        $signature = $request->get_header('paddle-signature');
        $secret = get_option('paddle_webhook_secret');

        if (!$this->verify_signature($body, $signature, $secret)) {
            error_log('WPPP Webhook Error: Invalid Signature.');
            return new WP_REST_Response('Invalid signature', 401);
        }

        $payload = json_decode($body, true);
        $event_type = $payload['event_type'] ?? '';

        // Handle both paid (V2) and completed events
        if ($event_type === 'transaction.paid' || $event_type === 'transaction.completed') {
            $this->process_transaction($payload['data']);
        }

        return new WP_REST_Response('OK', 200);
    }

    private function verify_signature($payload, $signature, $secret) {
        if (empty($signature) || empty($secret)) return false;
        $parts = explode(';', $signature);
        $ts = $h1 = '';
        foreach ($parts as $part) {
            if (strpos($part, 'ts=') === 0) $ts = substr($part, 3);
            if (strpos($part, 'h1=') === 0) $h1 = substr($part, 3);
        }
        if (!$ts || !$h1) return false;
        $signed_payload = $ts . ':' . $payload;
        return hash_equals(hash_hmac('sha256', $signed_payload, $secret), $h1);
    }

    private function process_transaction($data) {
        $tx_id = $data['id'];
        $custom_data = $data['custom_data'] ?? [];
        $user_id = isset($custom_data['wp_user_id']) ? absint($custom_data['wp_user_id']) : 0;
        
        if (!$user_id) return;
    
        if (!class_exists('LSPA_Plugin_DB_Manager')) {
            require_once LSPA_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        }
        
        $db = new LSPA_Plugin_DB_Manager($this->plugin_slug);
    
        // IDEMPOTENCY CHECK
        if ($db->transaction_exists($tx_id)) {
            error_log('WPPP: Skipping duplicate webhook for transaction ' . $tx_id);
            return;
        }

        // Get the Price ID from the items array in the webhook payload
        $items = $data['items'] ?? [];
        $first_item = !empty($items) ? $items[0] : null;
        $price_id = $first_item ? $first_item['price_id'] : '';
        // --- FIX ENDS HERE ---
        
        $amount = isset($data['details']['totals']['total']) ? $data['details']['totals']['total'] / 100 : 0;
    
        // 1. Log to DB (Now $price_id is correctly defined)
        $db->log_transaction(
            $user_id, 
            $tx_id, 
            $price_id, 
            $amount, 
            $data['currency_code'] ?? 'USD', 
            'completed'
        );
    
        $mycred = new \PaddleToMycred\Mycred_Handler();
        
        $points_mapping = $this->get_points_mapping();
    
        foreach ($items as $item) {
            $item_price_id = $item['price_id'] ?? '';
            if (isset($points_mapping[$item_price_id])) {
                $pkg = $points_mapping[$item_price_id];
                $mycred->grant_points_on_paddle_purchase($user_id, $pkg['points'], $pkg['name'], ['id' => $tx_id, 'price_id' => $item_price_id]);
            }
        }
    
        // 3. Create WC Order
        $this->create_wc_order($user_id, $items, $tx_id, $points_mapping);
    }

    private function create_wc_order($user_id, $items, $tx_id, $mapping) {
        if (!class_exists('WooCommerce')) return;

        // Create the order object
        $order = wc_create_order(['customer_id' => $user_id]);
        
        if (is_wp_error($order)) {
            error_log('WPPP Error: Could not create WC Order - ' . $order->get_error_message());
            return;
        }

        foreach ($items as $item) {
            $price_id = $item['price_id'] ?? '';
            $pkg = $mapping[$price_id] ?? null;
            
            if ($pkg) {
                // Use a safer way to add the product
                // If the product doesn't exist in WC, we add it as a 'fee' or 'custom item'
                $order->add_product(null, 1, [
                    'name'     => $pkg['name'],
                    'subtotal' => $pkg['price'],
                    'total'    => $pkg['price'],
                ]);
            }
        }

        // Set order data using modern CRUD methods
        $order->set_payment_method('paddle');
        $order->set_payment_method_title('Paddle');
        $order->set_transaction_id($tx_id);
        
        // Ensure totals are calculated before saving
        $order->calculate_totals();

        // Update status - This triggers the myCRED hooks safely
        $order->update_status('completed', __('Verified via Paddle Webhook.', 'wp-paddle-pro'));
        
        // Final save to ensure all meta is locked in
        $order->save();
    }

    private function get_points_mapping() {
        return [
            'pri_01jy4d7qj409nbxx2x6yadweq4' => ['points' => 1500, 'name' => '1500 Power Points', 'price' => 12.00],
            'pri_01kdb86rkbken' => ['points' => 500, 'name' => 'Starter Pack', 'price' => 1.50],
            'pri_01kdb8gz5m5h3' => ['points' => 1600, 'name' => 'Pro Pack', 'price' => 3.50],
            'pri_01kdb8bfakrb3xxq7r' => ['points' => 4000, 'name' => 'Power Pack', 'price' => 7.50],
        ];
    }
}