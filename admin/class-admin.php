<?php
defined( 'ABSPATH' ) || exit;

class AICA_Plugin_Admin {
    private $plugin_slug;
    private $version;
    private $private_key_option_name = 'docusign_encrypted_private_key';
    private $db_manager;
    private $mail_manager;
    private $llm_processor;

    public function __construct($plugin_slug, $version = '1.0') {
        $this->plugin_slug = $plugin_slug;  
        $this->version = $version;
        $this->mail_manager = new AICA_Plugin_Mail($this->plugin_slug);
        $this->llm_processor = new AICA_Plugin_LLM_Processor($this->plugin_slug);
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            'AI Chat Agent',
            'AI Chat Agent',
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_admin_dashboard'),
            'dashicons-media-document',
            30
        );

        // Submenu: Dashboard
        add_submenu_page(
            $this->plugin_slug,
            'Dashboard',
            'Dashboard',
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_admin_dashboard')
        );

        // Submenu: Settings
        add_submenu_page(
            $this->plugin_slug,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_slug . '-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // 1. Content Tab Settings
        register_setting($this->plugin_slug . '_content_settings', 'ai_agent_display_name');
        register_setting($this->plugin_slug . '_content_settings', 'ai_agent_welcome_msg');
        register_setting($this->plugin_slug . '_content_settings', 'ai_agent_placeholder');
    
        // 2. Style Tab Settings
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_logo_id');
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_bubble_icon');
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_primary_color');
    
        // 3. Prompt Tab Settings
        register_setting($this->plugin_slug . '_prompt_settings', 'ai_agent_system_prompt');
    
        // 4. Grok Tab Settings
        register_setting($this->plugin_slug . '_grok_settings', 'grok_api_key');
        register_setting($this->plugin_slug . '_grok_settings', 'grok_model');
        register_setting($this->plugin_slug . '_grok_settings', 'grok_temperature');
        register_setting($this->plugin_slug . '_prompt_settings', 'ai_agent_knowledge_file_id');

        // 5. Notifications Tab Settings
        register_setting($this->plugin_slug . '_notif_settings', 'ai_agent_admin_email');

        // Inside register_settings() under Style Tab Settings
        register_setting($this->plugin_slug . '_style_settings', 'ai_agent_theme_mode');
    }

    /**
     * Get all chat logs from the database
     */
    public function get_all_chat_logs() {
        $db = $this->get_db_manager();
        return $db->get_all_chat_logs();
    }

    public function get_chat_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . str_replace('-', '_', sanitize_key($this->plugin_slug)) . '_chat_logs';
        
        // Safety check
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return ['total' => 0, 'recent' => 0];
        }

        return [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'recent' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE last_updated >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
        ];
    }

    public function handle_delete_chat_ajax() {
        // This matches the nonce name sent in your JS
        check_ajax_referer('admin_proposal_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
    
        // JS sends 'id', not 'log_id'
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error('Invalid ID');
        }
    
        $db = $this->get_db_manager();
        if ($db->delete_chat_log($id)) {
            wp_send_json_success('Log deleted successfully');
        } else {
            wp_send_json_error('Database deletion failed');
        }
    }


    public function handle_export_chat_ajax() {
        // 1. Force clear output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        check_ajax_referer('admin_proposal_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $log_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        // Call the DB Manager
        $db = $this->get_db_manager();
        $logs = $db->get_logs_for_export($log_id);

        if (empty($logs)) {
            wp_send_json_error('No logs found.');
        }

        // 2. Generate CSV String
        ob_start();
        $df = fopen("php://output", 'w');
        fprintf($df, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
        
        fputcsv($df, array('ID', 'Date', 'Transcript'));

        foreach ($logs as $l) {
            $history = json_decode($l['full_history'], true);
            $transcript = "";
            if (is_array($history)) {
                foreach ($history as $msg) {
                    $role = ($msg['role'] === 'user') ? 'Visitor' : 'AI Agent';
                    $clean_text = wp_strip_all_tags($msg['content']);
                    $clean_content = str_replace(array("\r", "\n", '"'), array(" ", " ", "'"), $clean_text);
                    $transcript .= "[$role]: " . $clean_content . " | ";
                }
            }
            fputcsv($df, array($l['id'], $l['last_updated'], trim($transcript)));
        }
        
        fclose($df);
        $csv_data = ob_get_clean();

        wp_send_json_success(array(
            'csv_data' => $csv_data,
            'filename' => 'chat-export-' . ($log_id ? 'id-' . $log_id : 'all') . '-' . date('Y-m-d') . '.csv'
        ));
    }

    /**
     * AJAX handler to generate/fetch AI summary for a specific log
     */
    public function handle_get_chat_summary_ajax() {
        check_ajax_referer('admin_proposal_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        $db = $this->get_db_manager();
        $log = $db->get_chat_log_by_id($id);

        if (!$log || empty($log['full_history'])) {
            wp_send_json_error('Conversation history not found.');
        }

        $history = json_decode($log['full_history'], true);
        
        // Call the LLM Processor (this uses the summarizer prompt we set up)
        $summary = $this->llm_processor->get_chat_summary($history);

        if (empty($summary)) {
            wp_send_json_error('The AI was unable to generate a summary for this chat.');
        }

        wp_send_json_success($summary);
    }

    public function handle_sync_website_ajax() {
        check_ajax_referer('admin_proposal_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
        $step = isset($_POST['sync_step']) ? sanitize_text_field($_POST['sync_step']) : 'discovery';
        $db = $this->get_db_manager();
        
        // Initialize your scraper class
        $scraper = new AICA_Plugin_SimpleHTMLDOM();
    
        if ($step === 'discovery') {
            // CHANGED: Removed 'post' from the array, now only fetching 'page'
            $all_pages = get_posts([
                'post_type'      => ['page'], 
                'posts_per_page' => -1, 
                'post_status'    => 'publish'
            ]);
            
            $pages = [];
    
            $excluded_slugs = [
                'cart', 'checkout', 'my-account', 'logout', 'login', 'register', 
                'forgot', 'password-reset', 'change-password', 'payment-success',
                'payment-cancelled', 'thank-you', 'sample-page', 'blog', 'blogs'
            ];
            
            foreach ($all_pages as $page) {
                if (in_array($page->post_name, $excluded_slugs)) continue;
                
                $url = get_permalink($page->ID);
                $sync_data = $db->get_sync_status($url);
                
                $pages[] = [
                    'url'    => $url,
                    'title'  => $page->post_title,
                    'status' => $sync_data ? 'Synced' : 'Pending',
                    'last'   => $sync_data ? date_i18n(get_option('date_format'), strtotime($sync_data['last_synced'])) : '-'
                ];
            }
            wp_send_json_success(['pages' => $pages]);
        }
    
        if ($step === 'sync_page') {
            $url = esc_url_raw($_POST['url']);
            
            // Use the SCRAPER logic
            $content = $scraper->scrape_page($url);
            
            if (empty($content)) {
                $content = "Page source available at " . $url . " (No text content found by scraper)";
            }
    
            $db->update_knowledge_base($url, $content);
            
            wp_send_json_success([
                'status' => 'Synced', 
                'last'   => current_time('mysql')
            ]);
        }
    }

    public function handle_manual_cron_sync_ajax() {
        check_ajax_referer('admin_proposal_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
        // Call the Cron class directly
        if (class_exists('AICA_Plugin_WP_Cron')) {
            $cron = new AICA_Plugin_WP_Cron($this->plugin_slug);
            $cron->run_automated_sync(); // This is the method we built
            
            wp_send_json_success('Sync completed successfully!');
        } else {
            wp_send_json_error('Cron class not found.');
        }
    }

    public function handle_generate_system_prompt_ajax() {
        check_ajax_referer('admin_proposal_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    
        // Call the LLM Processor
        $new_prompt = $this->llm_processor->generate_automated_system_prompt();
    
        if (strpos($new_prompt, 'Error:') !== false) {
            wp_send_json_error($new_prompt);
        }
    
        wp_send_json_success($new_prompt);
    }
    
    /**
     * Display admin dashboard
     */
    public function display_admin_dashboard() {
        // Check if the partial file exists
        $dashboard_file = plugin_dir_path(__FILE__) . 'partials/admin-dashboard.php';
        if (file_exists($dashboard_file)) {
            include $dashboard_file;
        } else {
            echo '<div class="wrap"><h1>AI Chat Agent Dashboard</h1>';
            echo '<p>Welcome to the AI Chat Agent system. Use the "Create Proposal" menu to generate new proposals.</p>';
            echo '</div>';
        }
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        $settings_file = plugin_dir_path(__FILE__) . 'partials/admin-settings.php';
        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            echo '<div class="wrap"><h1>AI Chat Agent Settings</h1>';
            echo '<p>Settings file not found. Please check the plugin installation.</p>';
            echo '</div>';
        }
    }

    /**
     * Get DB manager instance
     */
    private function get_db_manager() {

        if (!isset($this->db_manager)) {
            $this->db_manager = new AICA_Plugin_DB_Manager($this->plugin_slug);
        }

        return $this->db_manager;
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, $this->plugin_slug) === false) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_slug . '-admin',
            AICA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style( $this->plugin_slug . '-public',  AICA_PLUGIN_URL . 'assets/css/public.css', array(), $this->version, 'all' );

    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, $this->plugin_slug) === false) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            $this->plugin_slug . '-admin',
            AICA_PLUGIN_URL . 'assets/js/admin/admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_slug . '-unsaved-changes',
            AICA_PLUGIN_URL . 'assets/js/admin/unsaved-changes.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_slug . '-knowledge-base',
            AICA_PLUGIN_URL . 'assets/js/admin/knowledge-base.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_slug . '-preview',
            AICA_PLUGIN_URL . 'assets/js/admin/preview.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script(
            $this->plugin_slug . '-admin',
            'adminProposalData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('admin_proposal_nonce')
            )
        );

        wp_localize_script(
            $this->plugin_slug . '-knowledge-base',
            'adminProposalData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('admin_proposal_nonce')
            )
        );
    }

}