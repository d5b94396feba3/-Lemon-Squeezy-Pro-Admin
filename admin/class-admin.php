<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Admin {
    private $plugin_slug;
    private $version;
    private $db;

    public function __construct($plugin_slug, $version = '1.0') {
        $this->plugin_slug = $plugin_slug;  
        $this->version     = $version;
        
        // Initialize DB Manager
        require_once WPPP_PLUGIN_PATH . 'includes/db/class-db-manager.php';
        $this->db = new WPPP_Plugin_DB_Manager($this->plugin_slug);
    }

    public function add_admin_menu() {
        add_menu_page(
            WPPP_PLUGIN_NAME,
            'Paddle Pro',
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_transactions_page'),
            'dashicons-cart',
            30
        );

        add_submenu_page(
            $this->plugin_slug,
            __( 'Products', 'wp-paddle-pro' ),
            __( 'Products', 'wp-paddle-pro' ),
            'manage_options',
            $this->plugin_slug . '-products',
            array($this, 'display_products_page')
        );

        add_submenu_page(
            $this->plugin_slug,
            __( 'Settings', 'wp-paddle-pro' ),
            __( 'Settings', 'wp-paddle-pro' ),
            'manage_options',
            $this->plugin_slug . '-settings',
            array($this, 'display_settings_page')
        );
    }

    public function display_products_page() {
        // Handle Form Submission
        if ( isset( $_POST['wppp_add_product'] ) && check_admin_referer( 'wppp_add_product_action' ) ) {
            $this->handle_product_form_submission();
        }
    
        $products = $this->db->get_all_products();
        include WPPP_PLUGIN_PATH . 'admin/partials/admin-products.php';
    }
    
    /**
     * Handles the logic for processing the product form.
     * Follows WP Standards: No direct echo, proper error handling.
     */
    private function handle_product_form_submission() {
        $price_id = sanitize_text_field( $_POST['price_id'] );
        $name     = sanitize_text_field( $_POST['name'] );
        $file_url = '';
    
        // Handle File Upload
        if ( ! empty( $_FILES['product_file']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/wppp-protected';
    
            // Secure directory creation
            if ( ! file_exists( $target_dir ) ) {
                wp_mkdir_p( $target_dir );
            }
    
            $file     = $_FILES['product_file'];
            $tmp_name = $file['tmp_name']; // Standard PHP key (tmp_path is incorrect)
            $filename = wp_unique_filename( $target_dir, $file['name'] );
            $new_path = $target_dir . '/' . $filename;
    
            // Securely move file
            if ( move_uploaded_file( $tmp_name, $new_path ) ) {
                $file_url = $new_path;
            } else {
                add_settings_error( 'wppp_messages', 'wppp_file_error', __( 'Failed to move file to protected storage.', 'wp-paddle-pro' ), 'error' );
                return;
            }
        } else {
            $file_url = sanitize_text_field( $_POST['file_url'] );
        }
    
        if ( ! empty( $file_url ) ) {
            $inserted = $this->db->add_product( $price_id, $name, $file_url );
    
            if ( $inserted ) {
                add_settings_error( 'wppp_messages', 'wppp_success', __( 'Product saved to protected storage!', 'wp-paddle-pro' ), 'updated' );
            } else {
                add_settings_error( 'wppp_messages', 'wppp_db_error', __( 'Database insertion failed. The Price ID might already exist.', 'wp-paddle-pro' ), 'error' );
            }
        }
        
        // Set transient to show notice after redirect if necessary, 
        // or simply let settings_errors() handle it in the partial.
        set_transient( 'settings_errors', get_settings_errors(), 30 );
    }

    public function display_transactions_page() {
        include_once WPPP_PLUGIN_PATH . 'admin/partials/admin-transactions.php';
    }

    public function display_settings_page() {
        include_once WPPP_PLUGIN_PATH . 'admin/partials/admin-settings.php';
    }

    /**
     * Registers all settings found in your custom integration script
     */
    public function register_settings() {
        // Group 1: General (API Credentials)
        register_setting($this->plugin_slug . '_general_settings', 'paddle_environment', 'sanitize_text_field');
        register_setting($this->plugin_slug . '_general_settings', 'paddle_client_side_token', 'sanitize_text_field');
        register_setting($this->plugin_slug . '_general_settings', 'paddle_api_key', 'sanitize_text_field');
        register_setting($this->plugin_slug . '_general_settings', 'paddle_webhook_secret', 'sanitize_text_field');
        
        // Group 2: Checkout (Redirects)
        register_setting($this->plugin_slug . '_checkout_settings', 'paddle_thank_you_page_url', 'esc_url_raw');
    }

    public function handle_test_connection_ajax() {
        check_ajax_referer('wppp_test_nonce');
    
        $api_key = get_option('paddle_api_key');
        $env     = get_option('paddle_environment', 'sandbox');
        
        // Official V2 API Endpoint
        $url = ($env === 'live') ? 'https://api.paddle.com/products' : 'https://sandbox-api.paddle.com/products';
    
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API Key is missing.']);
        }
    
        // Clean the key: Remove whitespaces or hidden characters
        $clean_key = trim($api_key);
    
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization'  => 'Bearer ' . $clean_key, // Space after Bearer is critical
                'Paddle-Version' => '1',                   // Required for Billing V2
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
            ],
            'timeout' => 15,
            'sslverify' => false, // Set to true on live, false can help on some localhost setups
        ]);
    
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }
    
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if ($code === 200) {
            wp_send_json_success(['message' => 'Connection Verified! Successfully reached Paddle.']);
        } else {
            // Capture Paddle's specific error message if available
            $detail = isset($body['error']['detail']) ? $body['error']['detail'] : 'HTTP ' . $code;
            wp_send_json_error(['message' => $detail]);
        }
    }

    public function enqueue_styles($hook) {
        if (strpos($hook, $this->plugin_slug) === false) return;
        wp_enqueue_style($this->plugin_slug . '-admin-css', WPPP_PLUGIN_URL . 'assets/css/admin/admin.css', array(), $this->version);
    }

    public function enqueue_scripts() {
        // Enqueue Admin JS
        wp_enqueue_script($this->plugin_slug . '-product-upload-js', WPPP_PLUGIN_URL . 'assets/js/admin/product-upload.js', array('jquery'), $this->version, true);
                
        wp_localize_script($this->plugin_slug . '-product-upload-js', 'wppp_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wppp_admin_nonce')
        ));
    }
}