<?php
defined( 'ABSPATH' ) || exit;

class LSPA_Plugin_Public {
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
            LSPA_PLUGIN_URL . 'assets/css/public/customer-dashboard.css', 
            array(), 
            $this->version, 
            'all' 
        );
        wp_enqueue_style( 
            $this->plugin_slug . '-order-summary', 
            LSPA_PLUGIN_URL . 'assets/css/public/customer-order-summary.css', 
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

        wp_enqueue_script( $this->plugin_slug . '-checkout', LSPA_PLUGIN_URL . 'assets/js/public/checkout.js', array('jquery', 'paddle-js'), $this->version, true );

        wp_localize_script( $this->plugin_slug . '-checkout', 'lspa_vars', array(
            'token'       => esc_js($client_token),
            'environment' => ($environment === 'live') ? 'production' : 'sandbox',
            'user_id'     => get_current_user_id(),
            'is_logged_in'=> is_user_logged_in(),
            'thank_you'   => esc_url($thank_you_url),
            'login_msg'   => __('Please login to purchase this item.', 'wp-paddle-pro')
        ));
    }


    public function render_checkout_button($atts) {
        // 1. Define attributes and allow for 'paddle_checkout' filtering
        $atts = shortcode_atts([
            'price_id' => '',
            'text'     => __( 'Buy Now', 'wp-paddle-pro' ),
            'class'    => 'w-full btn-gradient-purple text-white font-bold text-lg px-8 py-4 rounded-full shadow-xl transition-all duration-300 transform hover:scale-[1.02] hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[var(--color-light-purple)]'
        ], $atts, 'paddle_checkout');

        // 2. Safety check: Exit if price_id is missing
        if ( empty( $atts['price_id'] ) ) {
            return '';
        }

        $custom_classes = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $atts['class'] ) ) );
        
        $final_classes = esc_attr( $atts['class'] ) . ' wppp-paddle-checkout';

        ob_start();
        ?>
        <div class="wppp-checkout-container">
            <button 
                type="button"
                class="<?php echo $final_classes; ?>" 
                data-price-id="<?php echo esc_attr( $atts['price_id'] ); ?>"
                aria-label="<?php echo esc_attr( $atts['text'] ); ?>"
            >
                <?php echo esc_html( $atts['text'] ); ?> 
                <i class="fas fa-credit-card ml-2" aria-hidden="true"></i>
            </button>
            
            <div class="paddle-login-message" style="display: none; color: #c00; margin-top: 8px; font-size: 13px; text-align: center;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_order_summary($atts) {
        // 1. Get and sanitize input
        $tx_id = isset($_GET['paddle_transaction_id']) ? sanitize_text_field($_GET['paddle_transaction_id']) : '';
                
        // Define My Account URL dynamically
        $my_account_url = function_exists( 'wc_get_page_permalink' )
                        ? wc_get_page_permalink( 'myaccount' ) 
                        : home_url( '/my-account/' );

        // Define allowed HTML for security (wp_kses)
        $allowed_html = array(
            'div' => array('class' => array()),
            'a'   => array('href' => array(), 'class' => array()),
            'i'   => array('class' => array(), 'aria-hidden' => array()),
        );

        $container_start = '<div class="wppp-summary-container">';
        $container_end   = '</div>';

        // 2. Check if user is logged in
        if ( ! is_user_logged_in() ) {
            $message = sprintf( 
                '<div class="wppp-status-box wppp-status-error">
                    <i class="fas fa-user-lock mr-2" aria-hidden="true"></i> %s 
                    <a href="%s" class="ml-2 underline font-bold">%s</a>
                </div>', 
                esc_html__( 'Please login to view your order summary.', 'wp-paddle-pro' ),
                esc_url( $my_account_url ),
                esc_html__( 'Login to My Account', 'wp-paddle-pro' )
            );

            return wp_kses( $container_start . $message . $container_end, $allowed_html );
        }

        // 3. Check for Transaction ID
        if ( ! $tx_id ) {
            $message = sprintf( 
                '<div class="wppp-status-box wppp-status-error">
                    <i class="fas fa-exclamation-circle mr-2" aria-hidden="true"></i> %s
                </div>', 
                esc_html__( 'No transaction ID provided.', 'wp-paddle-pro' ) 
            );
            return wp_kses( $container_start . $message . $container_end, $allowed_html );
        }
        
        $db      = new LSPA_Plugin_DB_Manager( $this->plugin_slug );
        $user_id = get_current_user_id();

        if ( ! $db->is_transaction_valid_for_user( $tx_id, $user_id ) ) {
            return $container_start . sprintf( 
                '<div class="wppp-status-box wppp-status-error">
                    <i class="fas fa-times-circle mr-2"></i> %s
                </div>', 
                esc_html__( 'Invalid transaction or transaction not found.', 'wp-paddle-pro' ) 
            ) . $container_end;
        }

        // 4. Retrieve myCred Data
        if ( file_exists( LSPA_PLUGIN_PATH . 'includes/mycred/class-mycred-handler.php' ) ) {
            require_once LSPA_PLUGIN_PATH . 'includes/mycred/class-mycred-handler.php';
            $mycred      = new \PaddleToMycred\Mycred_Handler();
            $mycred_data = $mycred->mycred_balance_with_rank();
        } else {
            $mycred_data = array( 'balance' => 0, 'rank_name' => '' );
        }

        // 5. Render Template
        $template_path = LSPA_PLUGIN_PATH . 'public/partials/order-summary-template.php';
        
        ob_start();
        echo $container_start;
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            printf( 
                '<div class="wppp-status-box wppp-status-success">
                    <i class="fas fa-check-circle mr-2"></i> %s
                </div>', 
                esc_html__( 'Thank you! Your order is complete.', 'wp-paddle-pro' ) 
            );
        }
        echo $container_end;
        
        return ob_get_clean();
    }

    public function handle_secure_download() {
        if ( ! isset($_GET['lspa_download']) || ! is_user_logged_in() ) return;

        // Security: Check nonce if you add one to the download link
        $product_id = absint($_GET['lspa_download']);
        $user_id    = get_current_user_id();

        require_once LSPA_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        $db = new LSPA_Plugin_DB_Manager($this->plugin_slug);

        if ( ! $db->user_has_purchased_product($user_id, $product_id) ) {
            wp_die( esc_html__('Access Denied. You have not purchased this product.', 'wp-paddle-pro'), '', array('response' => 403));
        }

        $product = $db->get_product_by_id($product_id);
        if ( ! $product || empty($product['file_path']) ) {
            wp_die( esc_html__('File not found.', 'wp-paddle-pro') );
        }

        $file_path = $product['file_path'];

        if ( strpos($file_path, 'http') !== false ) {
            $upload_dir = wp_upload_dir();
            $file_path  = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_path);
        }

        // Standard WP security check for file existence
        if ( ! @file_exists($file_path) ) {
            wp_die( esc_html__('The file does not exist on the server.', 'wp-paddle-pro') );
        }

        // WP Standard: Clear output buffer and set no-cache
        if (ob_get_level()) ob_end_clean();
        nocache_headers();

        header("Content-Type: application/octet-stream");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=\"" . basename($file_path) . "\"");
        header("Content-Length: " . filesize($file_path));
        
        readfile($file_path);
        exit;
    }

    /**
     * Shortcode to render the customer download dashboard.
     * Usage: [paddle_dashboard]
     */
    public function render_customer_dashboard() {
        
        if ( ! is_user_logged_in() ) {
            // Use wc_get_page_permalink to ensure they go to the WooCommerce My Account page
            $my_account_url = function_exists( 'wc_get_page_permalink' ) 
                ? wc_get_page_permalink( 'myaccount' ) 
                : home_url( '/my-account/' );
    
            return sprintf(
                '<div class="wppp-customer-dashboard wppp-no-purchases">
                    <div class="wppp-empty-icon"><span class="dashicons dashicons-lock"></span></div>
                    <p class="wppp-dashboard-subtitle">%s</p>
                    <div style="margin-top: 20px;">
                        <a href="%s" class="wppp-button">%s</a>
                    </div>
                </div>',
                esc_html__( 'Please login to view and access your purchased downloads.', 'wp-paddle-pro' ),
                esc_url( $my_account_url ),
                esc_html__( 'Login to Account', 'wp-paddle-pro' )
            );
        }

        // Initialize DB
        if ( ! class_exists( 'LSPA_Plugin_DB_Manager' ) ) {
            require_once LSPA_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        }
        
        $db = new LSPA_Plugin_DB_Manager( $this->plugin_slug );
        $downloads = $db->get_user_purchased_products( get_current_user_id() );

        // 2. Load the template safely via Output Buffering
        ob_start();
        
        $template_path = LSPA_PLUGIN_PATH . 'public/partials/customer-dashboard-template.php';
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback inside the buffer if template is missing
            error_log( "WPPP Error: Dashboard template not found at $template_path" );
            printf(
                '<div class="wppp-customer-dashboard">
                    <p>%s</p>
                </div>',
                esc_html__( 'Dashboard UI could not be loaded. Please contact support.', 'wp-paddle-pro' )
            );
        }

        return ob_get_clean();
    }
}