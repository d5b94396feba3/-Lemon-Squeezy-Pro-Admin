<?php
defined( 'ABSPATH' ) || exit;

class WPPP_Plugin_Main {
    protected $loader;
    protected $plugin_name;
    protected $plugin_slug; 
    protected $version;

    public function __construct( $plugin_name, $plugin_slug, $version ) {
        $this->plugin_name = $plugin_name;
        $this->plugin_slug = $plugin_slug;
        $this->version     = $version;

        $this->loader = new WPPP_Plugin_Loader();
        $this->define_hooks();
    }

    private function define_hooks() {
        /**
         * 1. Admin Logic
         * Handles Settings, Transactions Table, and Admin Assets
         */
        if (is_admin()) {
            $admin = new WPPP_Plugin_Admin($this->plugin_slug, $this->version);
            
            $this->loader->add_action('admin_menu', $admin, 'add_admin_menu');
            $this->loader->add_action('admin_init', $admin, 'register_settings');
            $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
            
            // AJAX for transaction management
            $this->loader->add_action('wp_ajax_get_transaction_details', $admin, 'handle_get_tx_details_ajax');
        }

        /**
         * 2. Public Logic
         * Handles Styles, Scripts, and Shortcodes
         */
        $public = new WPPP_Plugin_Public($this->plugin_slug, $this->version);
        
        // Frontend Assets
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');
        
        // Shortcodes (Standard WP Hooks)
        $this->loader->add_shortcode('paddle_checkout', $public, 'render_checkout_button');
        $this->loader->add_shortcode('paddle_order_summary', $public, 'render_order_summary');

        /**
         * 3. Paddle Webhook Listener (REST API)
         * Creates the secure bridge between Paddle and WordPress
         */
        $paddle_core = new WPPP_Plugin_Paddle_Core($this->plugin_slug);
        $this->loader->add_action('rest_api_init', $paddle_core, 'register_webhook_route');
    }

    public function run() {
        $this->loader->run();
    }
}