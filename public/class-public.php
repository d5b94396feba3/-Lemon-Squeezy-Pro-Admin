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
        // Load Dashicons for the UI icons
        wp_enqueue_style( 'dashicons' );
        
        // Load our public CSS
        wp_enqueue_style( 
            $this->plugin_slug . '-public', 
            WPPP_PLUGIN_URL . 'assets/css/public/customer-dashboard.css', 
            array(), 
            $this->version, 
            'all' 
        );
    }

    public function enqueue_scripts() {
        $client_token  = get_option('paddle_client_side_token');
        $environment   = get_option('paddle_environment', 'sandbox');
        $thank_you_url = get_option('paddle_thank_you_page_url');

        wp_enqueue_script( 'paddle-js', 'https://cdn.paddle.com/paddle/v2/paddle.js', array(), null, true );

        wp_enqueue_script( $this->plugin_slug . '-checkout', WPPP_PLUGIN_URL . 'assets/js/public/checkout.js', array('jquery', 'paddle-js'), $this->version, true );

        wp_localize_script( $this->plugin_slug . '-checkout', 'wppp_vars', array(
            'token'       => esc_js($client_token),
            'environment' => ($environment === 'live') ? 'production' : 'sandbox',
            'user_id'     => get_current_user_id(),
            'is_logged_in'=> is_user_logged_in(),
            'thank_you'   => esc_url($thank_you_url),
            'login_msg'   => __('Please login to purchase this item.', 'wp-paddle-pro')
        ));
    }

    public function render_checkout_button($atts) {
        $atts = shortcode_atts([
            'price_id' => '',
            'text'     => __('Buy Now', 'wp-paddle-pro'),
            'class'    => 'paddle-checkout-button button-primary'
        ], $atts);

        if (empty($atts['price_id'])) return '';

        ob_start();
        ?>
        <div class="wppp-checkout-container">
            <button class="<?php echo esc_attr($atts['class']); ?> wppp-paddle-checkout" 
                    data-price-id="<?php echo esc_attr($atts['price_id']); ?>">
                <?php echo esc_html($atts['text']); ?>
            </button>
            <div class="paddle-login-message" style="display: none; color: #c00; margin-top: 8px; font-size: 13px;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_order_summary($atts) {
        $tx_id = isset($_GET['paddle_transaction_id']) ? sanitize_text_field($_GET['paddle_transaction_id']) : '';
        if (!$tx_id) return '<p>' . esc_html__('No transaction found.', 'wp-paddle-pro') . '</p>';

        require_once WPPP_PLUGIN_PATH . 'includes/mycred/class-mycred-handler.php';
        $mycred = new \PaddleToMycred\Mycred_Handler();
        
        $mycred_data = $mycred->mycred_balance_with_rank();

        ob_start();
        if (file_exists(WPPP_PLUGIN_PATH . 'public/partials/order-summary-template.php')) {
            include WPPP_PLUGIN_PATH . 'public/partials/order-summary-template.php';
        }
        return ob_get_clean();
    }

    /**
     * Secure Download Proxy
     */
    public function handle_secure_download() {
        if ( ! isset($_GET['wppp_download']) || ! is_user_logged_in() ) return;

        $product_id = absint($_GET['wppp_download']);
        $user_id    = get_current_user_id();

        require_once WPPP_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        $db = new WPPP_Plugin_DB_Manager($this->plugin_slug);

        // Verify purchase
        if ( ! $db->user_has_purchased_product($user_id, $product_id) ) {
            wp_die(__('Access Denied. You have not purchased this product.', 'wp-paddle-pro'), '', array('response' => 403));
        }

        $product = $db->get_product_by_id($product_id);
        if ( ! $product || empty($product['file_path']) ) wp_die(__('File not found.', 'wp-paddle-pro'));

        $file_path = $product['file_path'];

        // Convert URL to Path if saved as URL
        if ( strpos($file_path, 'http') !== false ) {
            $upload_dir = wp_upload_dir();
            $file_path  = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_path);
        }

        if ( ! file_exists($file_path) ) wp_die(__('The file does not exist on the server.', 'wp-paddle-pro'));

        // Serve File
        header("Content-Type: application/octet-stream");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=\"" . basename($file_path) . "\"");
        header("Content-Length: " . filesize($file_path));
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        
        readfile($file_path);
        exit;
    }

    public function render_customer_dashboard() {
        if ( !is_user_logged_in() ) return '<p>' . esc_html__('Please login to view downloads.', 'wp-paddle-pro') . '</p>';
        
        require_once WPPP_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        $db = new WPPP_Plugin_DB_Manager($this->plugin_slug);
        $downloads = $db->get_user_purchased_products(get_current_user_id());
        
        ob_start();
        include WPPP_PLUGIN_PATH . 'public/partials/customer-dashboard-template.php'; // Recommended to use partial
        return ob_get_clean();
    }
}