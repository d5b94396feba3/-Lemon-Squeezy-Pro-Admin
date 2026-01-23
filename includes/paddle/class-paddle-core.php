<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Paddle_Core {

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
            return new WP_REST_Response('Invalid signature', 401);
        }

        $payload = json_decode($body, true);
        $event_type = $payload['event_type'] ?? '';

        if ($event_type === 'transaction.completed') {
            $this->process_transaction($payload['data']);
        }

        return new WP_REST_Response('OK', 200);
    }

    private function verify_signature($payload, $signature, $secret) {
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

        $items = $data['items'] ?? [];
        $points_mapping = $this->get_points_mapping();

        // 1. Grant myCRED Points
        require_once WPPP_PLUGIN_PATH . 'includes/class-mycred-handler.php';
        $mycred = new PaddleToMycred\Mycred_Handler();

        foreach ($items as $item) {
            $price_id = $item['price_id'];
            if (isset($points_mapping[$price_id])) {
                $pkg = $points_mapping[$price_id];
                $mycred->grant_points_on_paddle_purchase($user_id, $pkg['points'], $pkg['name'], ['id' => $tx_id, 'price_id' => $price_id]);
            }
        }

        // 2. Create WooCommerce Order for Audit Trail
        $this->create_wc_order($user_id, $items, $tx_id, $points_mapping);
    }

    private function create_wc_order($user_id, $items, $tx_id, $mapping) {
        if (!class_exists('WooCommerce')) return;

        $order = wc_create_order(['customer_id' => $user_id]);
        foreach ($items as $item) {
            $pkg = $mapping[$item['price_id']] ?? null;
            if ($pkg) {
                $product = new WC_Product();
                $product->set_name($pkg['name']);
                $product->set_price($pkg['price']);
                $product->set_regular_price($pkg['price']);
                $order->add_product($product, 1);
            }
        }
        $order->set_payment_method('paddle');
        $order->update_meta_data('_paddle_transaction_id', $tx_id);
        $order->calculate_totals();
        $order->update_status('completed', 'Verified via Paddle Webhook');
    }

    private function get_points_mapping() {
        return [
            'pri_01kdb86rkbken' => ['points' => 500, 'name' => 'Starter Pack', 'price' => 1.50],
            'pri_01kdb8gz5m5h3' => ['points' => 1600, 'name' => 'Pro Pack', 'price' => 3.50],
            'pri_01kdb8bfakrb3xxq7r' => ['points' => 4000, 'name' => 'Power Pack', 'price' => 7.50],
        ];
    }
}