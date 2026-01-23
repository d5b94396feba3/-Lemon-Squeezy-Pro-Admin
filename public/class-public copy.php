<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Public {
    private $plugin_slug;
    private $version;

    public function __construct( $plugin_slug, $version ) {
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_slug . '-public', 
            WPPP_PLUGIN_URL . 'assets/css/public/public.css', 
            array(), 
            $this->version, 
            'all'
        );
    }

    public function enqueue_scripts() {
        $environment = get_option('paddle_environment', 'sandbox');
        // V2 CDN is always the same, but the token determines the environment
        $paddle_url  = 'https://cdn.paddle.com/paddle/v2/paddle.js';

        wp_enqueue_script(
            'paddle-js',
            $paddle_url,
            array(),
            null,
            true
        );
    }

    public function render_checkout_button($atts) {
        $atts = shortcode_atts([
            'price_id' => '',
            'text'     => __('Buy Now', 'wp-paddle-pro'),
            'class'    => 'paddle-checkout-button button-primary'
        ], $atts);

        if (empty($atts['price_id'])) return '';

        // Get Credentials from Settings
        $client_token = get_option('paddle_client_side_token');
        $environment  = get_option('paddle_environment', 'sandbox');
        $user_id      = get_current_user_id();

        if (empty($client_token)) {
            return '<p style="color:red;">Config Error: Client-Side Token missing.</p>';
        }

        ob_start();
        ?>
        <button class="<?php echo esc_attr($atts['class']); ?>" id="wppp-buy-<?php echo esc_attr($atts['price_id']); ?>">
            <?php echo esc_html($atts['text']); ?>
        </button>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                // 1. Initialize Paddle
                Paddle.Environment.set('<?php echo ($environment === 'live') ? 'production' : 'sandbox'; ?>');
                Paddle.Initialize({ 
                    token: '<?php echo esc_js($client_token); ?>' 
                });

                // 2. Click Handler
                document.getElementById('wppp-buy-<?php echo esc_js($atts['price_id']); ?>').addEventListener('click', function() {
                    Paddle.Checkout.open({ 
                        items: [{ 
                            priceId: '<?php echo esc_js($atts['price_id']); ?>', 
                            quantity: 1 
                        }],
                        customData: { 
                            wp_user_id: <?php echo absint($user_id); ?> 
                        }
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function render_order_summary($atts) {
        $tx_id = isset($_GET['paddle_transaction_id']) ? sanitize_text_field($_GET['paddle_transaction_id']) : '';
        if (!$tx_id) return '<p>' . esc_html__('No transaction found.', 'wp-paddle-pro') . '</p>';

        require_once WPPP_PLUGIN_PATH . 'includes/mycred/class-mycred-handler.php';
        $mycred = new \PaddleToMycred\Mycred_Handler();
        
        // Use the handler function you provided
        $data = [
            'balance' => function_exists('mycred_get_users_balance') ? mycred_get_users_balance(get_current_user_id()) : 0,
            'rank_name' => 'Member' // You can expand this logic as needed
        ];

        ob_start();
        if (file_exists(WPPP_PLUGIN_PATH . 'public/partials/order-summary-template.php')) {
            include WPPP_PLUGIN_PATH . 'public/partials/order-summary-template.php';
        }
        return ob_get_clean();
    }
}