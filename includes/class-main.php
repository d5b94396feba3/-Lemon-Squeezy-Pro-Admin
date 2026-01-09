<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Main {
    protected $loader;
    protected $plugin_name;
    protected $plugin_slug; 
    protected $version;

    public function __construct( $plugin_name, $plugin_slug, $version ) {
        $this->plugin_name = $plugin_name;
        $this->plugin_slug = $plugin_slug;
        $this->version     = $version;

        $this->loader = new AICA_Plugin_Loader();
        $this->define_hooks();
    }

    private function define_hooks() {
        // Admin hooks
        if (is_admin()) {
            $admin = new AICA_Plugin_Admin($this->plugin_slug, $this->version);
            
            // Menu and settings
            $this->loader->add_action('admin_menu', $admin, 'add_admin_menu');
            $this->loader->add_action('admin_init', $admin, 'register_settings');

            // Admin assets
            $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
            
            // Admin AJAX handlers
            $this->loader->add_action('wp_ajax_delete_chat_log', $admin, 'handle_delete_chat_ajax');
            // Inside define_hooks() in class-main.php
            $this->loader->add_action('wp_ajax_export_chat_log', $admin, 'handle_export_chat_ajax');
            $this->loader->add_action('wp_ajax_get_chat_summary', $admin, 'handle_get_chat_summary_ajax');      

            $this->loader->add_action('wp_ajax_sync_website_knowledge', $admin, 'handle_sync_website_ajax');
            $this->loader->add_action('wp_ajax_manual_cron_sync', $admin, 'handle_manual_cron_sync_ajax');
            $this->loader->add_action('wp_ajax_generate_ai_system_prompt', $admin, 'handle_generate_system_prompt_ajax');
        }

        // Public hooks
        $public = new AICA_Plugin_Public($this->plugin_slug, $this->version);
        
        // Public assets
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');
        $this->loader->add_action( 'wp_footer', $public, 'render_chat_ui' );

        // Existing hooks...
        $this->loader->add_action( 'wp_ajax_nopriv_send_chat_message', $public, 'handle_chat_message_ajax' );
        $this->loader->add_action( 'wp_ajax_send_chat_message', $public, 'handle_chat_message_ajax' );

        $this->loader->add_action( 'wp_ajax_nopriv_submit_chat_feedback', $public, 'handle_chat_feedback_ajax' );
        $this->loader->add_action( 'wp_ajax_submit_chat_feedback', $public, 'handle_chat_feedback_ajax' );

        $this->loader->add_action( 'wp_ajax_nopriv_clear_chat_history', $public, 'handle_clear_chat_ajax' );
        $this->loader->add_action( 'wp_ajax_clear_chat_history', $public, 'handle_clear_chat_ajax' );

        $this->loader->add_action( 'wp_ajax_nopriv_end_chat_session', $public, 'handle_end_chat_ajax' );
        $this->loader->add_action( 'wp_ajax_end_chat_session', $public, 'handle_end_chat_ajax' );

        $this->loader->add_action('wp_ajax_nopriv_get_recent_chats', $public, 'handle_fetch_recent_chats');
        $this->loader->add_action('wp_ajax_get_recent_chats', $public, 'handle_fetch_recent_chats');
    }

    public function run() {
        $this->loader->run();
        
        // Debug: Check if hooks are registered
        if (WP_DEBUG) {
            // error_log('Plugin Loader - Registered Hooks: ' . print_r($this->loader->get_hook_counts(), true));
        }
    }
}