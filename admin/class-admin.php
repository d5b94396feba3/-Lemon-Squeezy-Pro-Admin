<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Admin {
    private $plugin_slug;
    private $version;

    public function __construct($plugin_slug, $version = '1.0') {
        $this->plugin_slug = $plugin_slug;  
        $this->version = $version;
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
            __( 'Settings', 'wp-paddle-pro' ),
            __( 'Settings', 'wp-paddle-pro' ),
            'manage_options',
            $this->plugin_slug . '-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Registers all settings found in your custom integration script
     */
    public function register_settings() {
        // API Credentials & Tokens
        register_setting($this->plugin_slug . '_settings', 'paddle_client_side_token', 'sanitize_text_field');
        register_setting($this->plugin_slug . '_settings', 'paddle_api_key', 'sanitize_text_field');
        register_setting($this->plugin_slug . '_settings', 'paddle_webhook_secret', 'sanitize_text_field');
        
        // Environment & Configuration
        register_setting($this->plugin_slug . '_settings', 'paddle_environment', 'sanitize_text_field');
        register_setting($this->plugin_slug . '_settings', 'paddle_thank_you_page_url', 'esc_url_raw');
        
        // Backward compatibility for existing WPPP fields if needed
        register_setting($this->plugin_slug . '_settings', 'wppp_paddle_sandbox');
    }

    public function display_transactions_page() {
        include_once WPPP_PLUGIN_PATH . 'admin/partials/admin-transactions.php';
    }

    public function display_settings_page() {
        include_once WPPP_PLUGIN_PATH . 'admin/partials/admin-settings.php';
    }

    public function enqueue_styles($hook) {
        if (strpos($hook, $this->plugin_slug) === false) return;
        wp_enqueue_style($this->plugin_slug . '-admin-css', WPPP_PLUGIN_URL . 'assets/css/admin/admin.css', array(), $this->version);
    }
}